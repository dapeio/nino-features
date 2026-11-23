<?php
declare(strict_types=1);

/**
 *	Nino features
 *	legal-smoke.php	The sections of the privacy policy the features bring (install/elements/privacy.php, AGENTS.md section 4c): named in the
 *									manifest of the unit, the same sections in '*', de_DE and en_US, ids of the feature, a position in its range, only the tags
 *									and links of a field with blocks, no entity and no '[', and only placeholders that Nino's Legal module replaces.
 *									The range of a feature is the "legal" of its own tests/catalogue.php (tests/declarations.php): a feature that brings sections
 *									names one, a feature that brings none names none, and two ranges are one and the same or apart.
 *
 *									Reads features/ only. Needs nothing of the kernel.
 *
 *	Usage: php tests/legal-smoke.php
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

/*	Where in the policy the sections stand: the module keeps 100-399 and 900-999 and has sections at 400, 410, 500 and 510, so the 400-599
	ranges are shared with it; what the features may use lies between, and a feature says its own range in its tests/catalogue.php, divided by
	what a section is about (cookies and consent 400-499, contact, forms and mail 500-599, statistics 600-699, embedded content 700-799, further
	features 800-899). Two features may say the same range; no two say ranges that cut into each other	*/
const LEGAL_ZONE = [ 400, 899 ];

// What the Legal module replaces: /project/company/<group>/<name> and /project/website/general/<name>
const LEGAL_PLACEHOLDER = '#^/project/(?:company/[a-z0-9]+(?:-[a-z0-9]+)*|website/general)/[a-z0-9]+(?:-[a-z0-9]+)*$#';

$what = static fn( array $found ): string => $found === [] ? '' : ' - '. implode( ' | ', array_slice( $found, 0, 6 ) );

/**
 *	Every rule of this suite over the features below a repository
 *
 *	@param		string		$repo
 *
 *	@return		array<string, mixed>		rule => what offends it
 */
function legalProblems( string $repo ): array {

	$locales = [ 'de_DE', 'en_US' ];

	$named = [];
	$buckets = [];
	$ids = [];
	$positions = [];
	$form = [];
	$contributors = [];
	$sections = 0;
	$declarations = declarationsRead( $repo );
	$ranges = declarationsOf( $declarations, 'legal' );

	foreach( (array) scandir( $repo. '/features' ) as $directory ) {

		$dir = $repo. '/features/'. $directory;

		if( $directory[0] === '.' || is_file( $dir. '/install/elements/privacy.php' ) === false )
			continue;

		$manifest = (array) include $dir. '/feature.php';
		$unit = (array) include $dir. '/install/manifest.php';
		$privacy = (array) include $dir. '/install/elements/privacy.php';
		$key = (string) ( $manifest['key'] ?? '' );
		$contributors[$directory] = $key;

		if( ( $unit['elements']['privacy'] ?? null ) !== 'elements/privacy.php' )
			$named[] = $directory;

		if( array_diff( array_keys( $privacy ), [ '*', 'de_DE', 'en_US' ] ) !== [] )
			$buckets[] = $directory. ' has a bucket besides *, de_DE and en_US (a type of its own, a model, a title)';

		$stars = array_keys( array_diff_key( (array) ( $privacy['*'] ?? [] ), [ '*' => 1 ] ) );
		$german = array_keys( (array) ( $privacy['de_DE'] ?? [] ) );
		$english = array_keys( (array) ( $privacy['en_US'] ?? [] ) );

		sort( $stars );
		sort( $german );
		sort( $english );

		if( $stars === [] || $stars !== $german || $german !== $english )
			$buckets[] = $directory. ' has other sections in *, de_DE and en_US';

		foreach( $german as $id ) {

			$id = (string) $id;
			$sections++;

			if( preg_match( '#^'. preg_quote( $key, '#' ). '(?:-[a-z0-9]+(?:-[a-z0-9]+)*)?$#D', $id ) !== 1 )
				$ids[] = $directory. ' '. $id. ' is not the key of the feature or the key and a name';

			if( isset( $positions['id'][$id] ) === true )
				$ids[] = $directory. ' '. $id. ' is also the id of '. $positions['id'][$id];

			$positions['id'][$id] = $directory;
			$order = $privacy['*'][$id]['order'] ?? null;

			// A feature without a range is told of that once, below, with the file it is missing in
			if( is_int( $order ) === false || ( isset( $ranges[$directory] ) === true && ( $order < $ranges[$directory][0] || $order > $ranges[$directory][1] ) ) )
				$ids[] = $directory. ' '. $id. ' has a position outside the range of the feature';
			elseif( isset( $positions['order'][$order] ) === true )
				$ids[] = $directory. ' '. $id. ' stands where '. $positions['order'][$order]. ' does';
			else
				$positions['order'][$order] = $id;

			foreach( $locales as $locale ) {

				$title = $privacy[$locale][$id]['title'] ?? null;
				$text = $privacy[$locale][$id]['text'] ?? null;
				$where = $directory. ' '. $id. ' '. $locale;

				if( is_string( $title ) === false || trim( $title ) === '' || $title !== strip_tags( $title ) || preg_match( '/[&\[\]]/', $title ) === 1 )
					$form[] = $where. ': the title is empty or has markup';

				if( is_string( $text ) === false || trim( $text ) === '' ) {
					$form[] = $where. ': the text is empty';
					continue;
				}

				if( preg_match( '/[&\[\]]/', $text ) === 1 )
					$form[] = $where. ': the text has a "&", a "[" or a "]"';

				preg_match_all( '#</?([a-z0-9]+)\b[^>]*>#i', $text, $tags );

				foreach( array_unique( array_map( 'strtolower', $tags[1] ) ) as $tag )
					if( in_array( $tag, [ 'p', 'br', 'ul', 'ol', 'li', 'strong', 'em', 'a' ], true ) === false )
						$form[] = $where. ': the tag <'. $tag. '> is not one of a field with blocks';

				preg_match_all( '#<a\b([^>]*)>#i', $text, $links );

				foreach( $links[1] as $attributes )
					if( preg_match( '#^ href="(?:\#privacy-[a-z0-9]+(?:-[a-z0-9]+)*|https://[^"\s]+)"$#', $attributes ) !== 1 )
						$form[] = $where. ': a link is neither an anchor of the policy nor https, or has an attribute besides href';

				preg_match_all( '/(?<!#)#(\/[^#\s<>]{1,200})#(?!#)/', $text, $candidates );

				foreach( $candidates[1] as $candidate )
					if( preg_match( LEGAL_PLACEHOLDER, $candidate ) !== 1 )
						$form[] = $where. ': #'. $candidate. '# is no placeholder the module replaces';
			}
		}
	}

	// A range for each feature that brings a section and for none that brings none - and the file to change is named
	$unsaid = [];

	foreach( $contributors as $directory => $key )
		if( isset( $ranges[$directory] ) === false && array_filter( $declarations[$directory]['problems'], static fn( array $problem ): bool => $problem[0] === 'legal' ) === [] )
			$unsaid[] = $declarations[$directory]['file']. ' has no "legal" range, and '. $directory. ' brings sections of the privacy policy';

	foreach( array_diff_key( $ranges, $contributors ) as $directory => $range )
		$unsaid[] = $declarations[$directory]['file']. ' names a "legal" range, and '. $directory. ' has no install/elements/privacy.php';

	// The zone the module leaves to the features, and no range that cuts into another one
	$apart = [];

	foreach( $ranges as $directory => $range ) {

		if( $range[0] < LEGAL_ZONE[0] || $range[1] > LEGAL_ZONE[1] )
			$apart[] = $declarations[$directory]['file']. ' says '. $range[0]. '-'. $range[1]. ', and the features have '. LEGAL_ZONE[0]. '-'. LEGAL_ZONE[1];

		foreach( $ranges as $other => $against )
			if( strcmp( $directory, $other ) < 0 && $range !== $against && $range[0] <= $against[1] && $against[0] <= $range[1] )
				$apart[] = $declarations[$directory]['file']. ' says '. $range[0]. '-'. $range[1]. ' and '. $declarations[$other]['file']. ' says '. $against[0]. '-'. $against[1];
	}

	return [ 'contributors' => $contributors, 'sections' => $sections, 'named' => $named, 'buckets' => $buckets, 'ids' => $ids, 'form' => $form, 'declared' => declarationsProblems( $declarations, [ 'legal' ] ), 'unsaid' => $unsaid, 'apart' => $apart ];
}

$what = static fn( array $found ): string => $found === [] ? '' : ' - '. implode( ' | ', array_slice( $found, 0, 6 ) );
$found = legalProblems( $repo );

check( 'features bring sections of the privacy policy ('. $found['sections']. ' of them in '. count( $found['contributors'] ). ' features)', count( $found['contributors'] ) > 5 && $found['sections'] > 5 );
check( 'the unit of each names its file under "elements"'. $what( $found['named'] ), $found['named'] === [] );
check( 'a file has the buckets *, de_DE and en_US and nothing else, and the same sections in each'. $what( $found['buckets'] ), $found['buckets'] === [] );
check( 'an id is the key of the feature or the key and a name, and a position lies in the range of the feature and is no other section\'s'. $what( $found['ids'] ), $found['ids'] === [] );
check( 'every title and text is in the form of a field with blocks: the tags and links it has, no entity, no "[", only the placeholders the module replaces'. $what( $found['form'] ), $found['form'] === [] );
check( 'the declarations of the ranges are well formed'. $what( $found['declared'] ), $found['declared'] === [] );
check( 'the ranges are those of the features that bring a section, no more and no fewer: each feature names its own in tests/catalogue.php'. $what( $found['unsaid'] ), $found['unsaid'] === [] && $found['contributors'] !== [] );
check( 'a range lies where the module leaves room, and it is the range of another feature or apart from it'. $what( $found['apart'] ), $found['apart'] === [] );

echo "\nThe rules over a repository of probes\n";

// A section with every rule kept, and a feature for each rule with the one fault that breaks it. A section is written as the file of a feature writes it
$section = static fn( string $key, int $order, string $text = '<p>Text.</p>' ): string => '<?php return '. var_export( [
	'*' => [ $key => [ 'order' => $order ] ],
	'de_DE' => [ $key => [ 'title' => 'Titel', 'text' => $text ] ],
	'en_US' => [ $key => [ 'title' => 'Title', 'text' => $text ] ],
], true ). ';';
$probe = static function( string $key, string $privacy, string $range = '', string $unit = 'privacy' ): array {

	$files = [ 'feature.php' => '<?php return [ \'key\' => \''. $key. '\' ];', 'install/manifest.php' => '<?php return [ \'elements\' => [ \''. $unit. '\' => \'elements/privacy.php\' ] ];', 'install/elements/privacy.php' => $privacy ];

	if( $range !== '' )
		$files['tests/catalogue.php'] = '<?php return [ \'legal\' => '. $range. ' ];';

	return $files;
};
$readme = declarationsExample( (string) file_get_contents( $repo. '/README.md' ) );
$probes = [
	'Good'     => $probe( 'good', $section( 'good', 450 ), '[ 400, 499 ]' ),
	'Same'     => $probe( 'same', $section( 'same', 460 ), '[ 400, 499 ]' ),
	// The declarations of the example come first: a union keeps the key it has, and the example is what the feature says
	'Readme'   => [ 'tests/catalogue.php' => '<?php return '. var_export( $readme, true ). ';' ] + $probe( 'readme', $section( 'readme', 550 ) ),
	'Unnamed'  => $probe( 'unnamed', $section( 'unnamed', 520 ), '[ 500, 599 ]', 'other' ),
	'Buckets'  => $probe( 'buckets', str_replace( "'*' =>", "'model' => 'x', '*' =>", $section( 'buckets', 530 ) ), '[ 500, 599 ]' ),
	'Id'       => $probe( 'id', $section( 'other-id', 540 ), '[ 500, 599 ]' ),
	'Out'      => $probe( 'out', $section( 'out', 650 ), '[ 500, 599 ]' ),
	'Twin'     => $probe( 'twin', $section( 'twin', 450 ), '[ 400, 499 ]' ),
	'Form'     => $probe( 'form', $section( 'form', 560, '<p>An entity &amp; a <b>tag</b> and #/project/other/x/y#</p>' ), '[ 500, 599 ]' ),
	'Rangeless' => $probe( 'rangeless', $section( 'rangeless', 580 ) ),
	'Sectionless' => [ 'feature.php' => '<?php return [ \'key\' => \'sectionless\' ];', 'tests/catalogue.php' => '<?php return [ \'legal\' => [ 500, 599 ] ];' ],
	'Wide'     => $probe( 'wide', $section( 'wide', 150 ), '[ 100, 199 ]' ),
	'Cut'      => $probe( 'cut', $section( 'cut', 750 ), '[ 450, 799 ]' ),
	'Turned'   => $probe( 'turned', $section( 'turned', 850 ), '[ 899, 800 ]' ),
];

$work = sys_get_temp_dir(). '/nino-legal-'. bin2hex( random_bytes( 6 ) );

foreach( $probes as $name => $files )
	declarationsWrite( $work. '/features/'. $name, $files );

$probed = legalProblems( $work );
$has = static fn( array $lines, string $name ): bool => array_filter( $lines, static fn( string $line ): bool => str_contains( $line, $name ) === true ) !== [];
$of = static fn( array $lines, array $names ): array => array_values( array_filter( $names, static fn( string $name ): bool => $has( $lines, $name ) === true ) );
$all = array_keys( $probes );

check( 'a feature that keeps every rule, a shared range among them, and a feature made of the example of the README, offend none',
	$readme !== null && $of( array_merge( $probed['named'], $probed['buckets'], $probed['ids'], $probed['form'], $probed['declared'], $probed['unsaid'], array_filter( $probed['apart'], static fn( string $line ): bool => str_contains( $line, 'Cut' ) === false ) ), [ 'Good', 'Same', 'Readme' ] ) === [] );
check( 'a unit that names no file for the section, a bucket besides the three, an id that is not the key of the feature, a position out of the range, and a position another feature has are each found',
	$of( $probed['named'], $all ) === [ 'Unnamed' ] && $of( $probed['buckets'], $all ) === [ 'Buckets' ] && $of( $probed['ids'], $all ) === [ 'Id', 'Out', 'Twin' ] );
check( 'an entity, a tag that is no tag of a field with blocks and a placeholder the module does not replace are found', $of( $probed['form'], $all ) === [ 'Form' ] && count( $probed['form'] ) === 3 * 2 );
check( 'a feature with a section and no range, and a range and no section, are found, and the sentence names the file', $of( $probed['unsaid'], $all ) === [ 'Rangeless', 'Sectionless' ]
	&& $has( $probed['unsaid'], 'features/Rangeless/tests/catalogue.php' ) && $has( $probed['unsaid'], 'features/Sectionless/tests/catalogue.php' ) );
check( 'a range that leaves the room the module leaves and one that cuts into the range of other features are found, a range that is turned round is found as no range, and two that are the same are not',
	$has( $probed['apart'], 'features/Wide/tests/catalogue.php says 100-199' ) && $has( $probed['apart'], 'features/Cut/tests/catalogue.php says 450-799 and features/Good' )
	&& array_filter( $probed['apart'], static fn( string $line ): bool => str_contains( $line, 'Good' ) === true && str_contains( $line, 'Same' ) === true ) === [] && $of( $probed['declared'], $all ) === [ 'Turned' ] );

declarationsRemove( $work );

echo "\n". $checks. ' checks, '. $failures. " failed\n";
exit( $failures === 0 ? 0 : 1 );
