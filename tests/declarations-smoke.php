<?php
declare(strict_types=1);

/**
 *	Nino features
 *	declarations-smoke.php	What a feature tells the tests of the catalogue about itself (features/<Name>/tests/catalogue.php, tests/declarations.php):
 *													every file that is there returns an array of the keys the tests read and nothing else, each key in its form, and a
 *													file that is wrong in any of them is named in the sentence. The reader is held to it with a repository of probes.
 *
 *													Reads features/ only, and writes a work folder of its own in the temporary directory, which it takes away again.
 *													Needs nothing of the kernel.
 *
 *	Usage: php tests/declarations-smoke.php
 */

require __DIR__. '/declarations.php';

$repo = dirname( __DIR__ );

$failures	= 0;
$checks		= 0;

function check( string $label, bool $condition ): void {
	global $failures, $checks;
	$checks++;
	echo ( $condition === true ? '  ok  ' : 'FAIL  ' ), '- ', $label, "\n";
	if( $condition === false )
		$failures++;
}

$what = static fn( array $found ): string => $found === [] ? '' : ' - '. implode( ' | ', array_slice( $found, 0, 6 ) );

echo "The files of the features\n";

$all = declarationsRead( $repo );
$present = array_filter( $all, static fn( array $feature ): bool => $feature['present'] === true );

check( 'features say something of themselves ('. count( $present ). ' of '. count( $all ). ' have a '. DECLARATIONS_FILE. ')', count( $all ) > 20 && count( $present ) > 10 );
check( 'each file returns an array with keys the tests read, each in its form'. $what( declarationsProblems( $all ) ), declarationsProblems( $all ) === [] );

// The example is run as a feature in legal-smoke and keys-smoke; here the three places that carry it say the same and it is of the form
$examples = [];

foreach( [ 'README.md', 'README.de.md', 'AGENTS.md' ] as $document )
	$examples[$document] = declarationsExample( (string) file_get_contents( $repo. '/'. $document ) );

check( 'README.md, README.de.md and AGENTS.md carry one and the same example of the file, and it is of the form the tests read'. $what( array_keys( array_filter( $examples, static fn( mixed $example ): bool => $example === null ) ) ),
	array_filter( $examples, static fn( mixed $example ): bool => $example === null ) === [] && count( array_unique( array_map( 'serialize', $examples ) ) ) === 1
	&& declarationsShape( 'example', reset( $examples ) ) === [] && array_diff( array_keys( (array) reset( $examples ) ), [ 'surface', 'reading' ] ) === array_keys( (array) reset( $examples ) ) );

echo "\nThe reader\n";

$work = sys_get_temp_dir(). '/nino-declarations-'. bin2hex( random_bytes( 6 ) );

mkdir( $work. '/features', 0755, true );

// A repository of probes: the file of each one is what a feature might write
$probes = [
	'Plain'   => null,
	'Right'   => "<?php return [ 'legal' => [ 500, 599 ], 'pages' => [ '/probe' => 'Probe.php', '/built' => [ 'Probe.php', 'WAY', [ 'Sections/Sections.php', 'KEY' ] ] ], 'surface' => 'a surface' ];",
	'String'  => "<?php return 'legal';",
	'Typo'    => "<?php return [ 'legall' => [ 500, 599 ] ];",
	'Turned'  => "<?php return [ 'legal' => [ 599, 500 ] ];",
	'Words'   => "<?php return [ 'legal' => [ '500', '599' ] ];",
	'Named'   => "<?php return [ 'legal' => [ 'from' => 500, 'to' => 599 ] ];",
	'NoPages' => "<?php return [ 'pages' => [] ];",
	'NoSlash' => "<?php return [ 'pages' => [ 'probe' => 'Probe.php' ] ];",
	'Dots'    => "<?php return [ 'pages' => [ '/probe' => '../Other/Other.php' ] ];",
	'Root'    => "<?php return [ 'pages' => [ '/probe' => '/etc/passwd' ] ];",
	'Lower'   => "<?php return [ 'pages' => [ '/probe' => [ 'Probe.php', 'way' ] ] ];",
	'Deep'    => "<?php return [ 'pages' => [ '/probe' => [ 'Probe.php', 'WAY', [ 'x.php', 'KEY', 'more' ] ] ] ];",
	'NoCode'  => "<?php return [ 'pages' => [ '/probe' => [] ] ];",
	'Pattern' => "<?php return [ 'pages' => [ '/probe' => [ [ 'Probe.php', '/x/' ] ] ] ];",
	'NoFile'  => "<?php return [ 'pages' => [ '/probe' => '' ] ];",
	'Blank'   => "<?php return [ 'surface' => ' ' ];",
	'Number'  => "<?php return [ 'reading' => 1 ];",
	'Both'    => "<?php return [ 'surface' => 'a', 'reading' => 'b' ];",
];

foreach( $probes as $name => $code ) {

	mkdir( $work. '/features/'. $name. '/tests', 0755, true );

	if( $code !== null )
		file_put_contents( $work. '/features/'. $name. '/'. DECLARATIONS_FILE, $code );
}

$read = declarationsRead( $work );
$wrong = array_diff_key( $probes, [ 'Plain' => 1, 'Right' => 1 ] );
$unnamed = [];

foreach( $wrong as $name => $code )
	if( $read[$name]['problems'] === [] || array_filter( declarationsProblems( [ $read[$name] ] ), static fn( string $sentence ): bool => str_contains( $sentence, 'features/'. $name. '/'. DECLARATIONS_FILE ) === true ) === [] )
		$unnamed[] = $name;

check( 'a file that is no array, has a key nobody reads, or has a key in another form is refused with a sentence that names the file'. $what( $unnamed ), $unnamed === [] && count( $wrong ) > 10 );
check( 'a feature with no file, and a file in the right form, are no problem, and the reader hands over what a file says by its key',
	$read['Plain']['present'] === false && $read['Plain']['problems'] === [] && $read['Right']['problems'] === [] && declarationsProblems( [ $read['Plain'], $read['Right'] ] ) === []
	&& declarationsOf( $read, 'legal' ) === [ 'Right' => [ 500, 599 ] ] && array_keys( declarationsOf( $read, 'surface' ) ) === [ 'Right' ] );
$ofLegal = declarationsProblems( $read, [ 'legal' ] );
$namesOf = fn( array $sentences ): array => array_values( array_filter( array_keys( $probes ), static fn( string $name ): bool => array_filter( $sentences, static fn( string $sentence ): bool => str_contains( $sentence, 'features/'. $name. '/' ) === true ) !== [] ) );

check( 'a test that asks for its own keys is told of the files that are no array and of the keys it reads, and of nothing else',
	$namesOf( $ofLegal ) === [ 'String', 'Turned', 'Words', 'Named' ] && $namesOf( declarationsProblems( $read, [ 'surface', 'reading' ] ) ) === [ 'String', 'Blank', 'Number', 'Both' ] );

$remove = static function( string $path ) use ( &$remove ): void {

	if( is_dir( $path ) === true && is_link( $path ) === false ) {

		foreach( array_diff( (array) scandir( $path ), [ '.', '..' ] ) as $entry )
			$remove( $path. '/'. $entry );

		rmdir( $path );
	}
	else
		unlink( $path );
};

$remove( $work );

echo "\n". $checks. ' checks, '. $failures. " failed\n";
exit( $failures === 0 ? 0 : 1 );
