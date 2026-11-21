<?php
declare(strict_types=1);

/**
 *	Nino features
 *	words-smoke.php		The word for the head and the foot of a page, held for the whole catalogue in one place: in German "Kopfbereich", "Fußbereich" and
 *										"Kopf-/Fußbereich", in English "header", "footer" and "header/footer". What a person reads of a feature - the lines of its manual, its
 *										texts, the labels and hints of its components, the names and descriptions of its blocks - has none of the old words: "Rahmen",
 *										"Header", "Footer", "Kopfzeile" and "Fußzeile" in German, "frame" in English. A technical name (frame-header, header-wrap,
 *										mail-footer.tpl, [frame ...]) is no word of it, and the few lines that mean something else are named below with their reason
 *
 *	Usage: php tests/words-smoke.php
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

$here = dirname( __DIR__ );

// The lines that say "Rahmen" or "frame" for another thing than the head and the foot of a page, each by a part of its text and the reason
$exceptions = [
	'de_DE' => [
		'Rahmen um den Block'							=> 'the frame of the accent colour the pointer draws round a block of the page (Builder, hover)',
		'wie der geladene Rahmen heißt'		=> 'the frame of a third-party page that the Embed component loads: an iframe',
		'nachdem ein Rahmen freigegeben'	=> 'the same iframe, released after the consent',
		'der Rahmen um sie scrollt'				=> 'the box that scrolls round the table of a collection: a wrapper, no head or foot',
		'Der Mailrahmen'									=> 'the head and the foot of a mail, not of a page: the doubt is named in the report of the change that made this suite',
	],
	'en_US' => [
		'Frame round the block'						=> 'the frame of the accent colour the pointer draws round a block of the page (Builder, hover)',
		'after a frame was released'			=> 'the iframe of the Embed component, released after the consent',
		'The mail frame'									=> 'the head and the foot of a mail, not of a page: the doubt is named in the report of the change that made this suite',
		'what the loaded frame is called'	=> 'the iframe of the Embed component, named for a screen reader',
	],
];
$old = [ 'de_DE' => '/Rahmen(?!linie)|\bHeader|\bFooter|Kopfzeile|Fußzeile|Fusszeile/u', 'en_US' => '/\b[Ff]rames?\b/' ];

/** What is left of a line when the technical names are taken out */
$plain = static fn( string $line ): string => (string) preg_replace( '/\[frame\b[^\]]*\]|\b[\w.\/]*(?:frame|header|footer)-[\w.-]+|\b[\w-]+-(?:header|footer)\b[\w.-]*/u', '', $line );

/** The lines a person reads, as [ where, locale, line ] */
$lines = [];

foreach( glob( $here. '/features/*', GLOB_ONLYDIR ) ?: [] as $dir ) {

	$name = basename( $dir );
	$read = static function( string $path ): array { return (array) ( static fn( string $file ): mixed => require $file )( $path ); };
	$walk = static function( mixed $value, string $where, array &$into ) use ( &$walk ): void {

		if( is_array( $value ) === false )
			return;

		foreach( $value as $key => $item ) {

			if( ( $key === 'de_DE' || $key === 'en_US' ) && is_string( $item ) === true )
				$into[] = [ $where, (string) $key, $item ];
			else
				$walk( $item, $where, $into );
		}
	};

	if( is_file( $dir. '/feature.php' ) === true )
		$walk( $read( $dir. '/feature.php' ), $name. '/feature.php', $lines );

	foreach( glob( $dir. '/text/*_*.php' ) ?: [] as $file )
		foreach( $read( $file ) as $item )
			if( is_string( $item ) === true )
				$lines[] = [ $name. '/text/'. basename( $file ), basename( $file, '.php' ), $item ];

	foreach( array_merge( glob( $dir. '/components/*/component.php' ) ?: [], glob( $dir. '/groups/*/group.php' ) ?: [] ) as $file )
		$walk( $read( $file ), $name. substr( $file, strlen( $dir ) ), $lines );

	foreach( glob( $dir. '/blocks/*/block.json' ) ?: [] as $file )
		$walk( json_decode( (string) file_get_contents( $file ), true ), $name. substr( $file, strlen( $dir ) ), $lines );
}

$found = [];

foreach( $lines as [ $where, $locale, $line ] ) {

	if( isset( $old[$locale] ) === false || preg_match( $old[$locale], $plain( $line ) ) !== 1 )
		continue;

	$named = false;

	foreach( array_keys( $exceptions[$locale] ) as $part )
		$named = $named || str_contains( $line, $part ) === true;

	if( $named === false )
		$found[] = $where. ' ('. $locale. '): '. mb_substr( $line, 0, 60 );
}

check( 'the lines of '. count( $lines ). ' texts, manuals, components and blocks were read', count( $lines ) > 1000 );
check( 'no line of the catalogue has "Rahmen", "Header", "Footer", "Kopfzeile" or "Fußzeile" in German or "frame" in English for the head and the foot of a page - the exceptions are named with their reason'. ( $found === [] ? '' : ' - '. implode( ' | ', $found ) ), $found === [] );
check( 'every exception still stands in the catalogue: a word that goes leaves the list',
	array_filter( array_merge( ...array_values( array_map( static fn( string $locale ): array => array_map( static fn( string $part ): bool => array_filter( $lines, static fn( array $line ): bool => $line[1] === $locale && str_contains( $line[2], $part ) ) !== [], array_keys( $exceptions[$locale] ) ), array_keys( $exceptions ) ) ) ), static fn( bool $stands ): bool => $stands === false ) === [] );
check( 'the detector finds the old words and lets the technical names be: "der Rahmen der Seite", "Header" and "frame" are found, frame-header.tpl, [frame header="a"], header-wrap and mail-footer.tpl are not',
	preg_match( $old['de_DE'], $plain( 'der Rahmen der Seite' ) ) === 1 && preg_match( $old['de_DE'], $plain( 'im Header' ) ) === 1 && preg_match( $old['en_US'], $plain( 'the frame' ) ) === 1
	&& preg_match( $old['en_US'], $plain( 'frame-header.tpl und [frame header="a"]' ) ) === 0 && preg_match( $old['de_DE'], $plain( 'header-wrap und mail-footer.tpl' ) ) === 0 && preg_match( $old['de_DE'], $plain( 'Rahmenlinie' ) ) === 0 );

echo "\n", $checks, ' checks, ', $failures, ' failed', "\n";
exit( $failures === 0 ? 0 : 1 );
