<?php
declare(strict_types=1);

/**
 *	Nino features
 *	legal-smoke.php	The sections of the privacy policy the features bring (install/elements/privacy.php, AGENTS.md section 4c): named in the
 *									manifest of the unit, the same sections in '*', de_DE and en_US, ids of the feature, a position in its range, only the tags
 *									and links of a field with blocks, no entity and no '[', and only placeholders that Nino's Legal module replaces.
 *
 *									Reads features/ only. Needs nothing of the kernel.
 *
 *	Usage: php tests/legal-smoke.php
 */

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

/*	Where in the policy the sections of each feature stand, by the key of the feature: the module keeps 100-399 and 900-999 and has sections at
	400, 410, 500 and 510, so the 400-599 ranges are shared with it; the rest is divided by what a section is about. A feature that brings a
	section adds its row here, and a row of a feature that brings none fails the test	*/
const LEGAL_RANGES = [
	'consent'   => [ 400, 499 ],
	'forms'     => [ 500, 599 ],
	'mailer'    => [ 500, 599 ],
	'newsletter' => [ 500, 599 ],
	'stats'     => [ 600, 699 ],
	'embed'     => [ 700, 799 ],
	'protected' => [ 800, 899 ],
	'hello'     => [ 800, 899 ],
	'sysadmin'  => [ 800, 899 ],
];

// What the Legal module replaces: /project/company/<group>/<name> and /project/website/general/<name>
const LEGAL_PLACEHOLDER = '#^/project/(?:company/[a-z0-9]+(?:-[a-z0-9]+)*|website/general)/[a-z0-9]+(?:-[a-z0-9]+)*$#';

$locales = [ 'de_DE', 'en_US' ];
$what = static fn( array $found ): string => $found === [] ? '' : ' - '. implode( ' | ', array_slice( $found, 0, 6 ) );

$named = [];
$buckets = [];
$ids = [];
$positions = [];
$form = [];
$contributors = [];
$sections = 0;

foreach( (array) scandir( $repo. '/features' ) as $directory ) {

	$dir = $repo. '/features/'. $directory;

	if( $directory[0] === '.' || is_file( $dir. '/install/elements/privacy.php' ) === false )
		continue;

	$manifest = (array) include $dir. '/feature.php';
	$unit = (array) include $dir. '/install/manifest.php';
	$privacy = (array) include $dir. '/install/elements/privacy.php';
	$key = (string) ( $manifest['key'] ?? '' );
	$contributors[] = $key;

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

		if( is_int( $order ) === false || isset( LEGAL_RANGES[$key] ) === false || $order < LEGAL_RANGES[$key][0] || $order > LEGAL_RANGES[$key][1] )
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

check( 'features bring sections of the privacy policy ('. $sections. ' of them in '. count( $contributors ). ' features)', count( $contributors ) > 5 && $sections > 5 );
check( 'the unit of each names its file under "elements"'. $what( $named ), $named === [] );
check( 'a file has the buckets *, de_DE and en_US and nothing else, and the same sections in each'. $what( $buckets ), $buckets === [] );
check( 'an id is the key of the feature or the key and a name, and a position lies in the range of the feature and is no other section\'s'. $what( $ids ), $ids === [] );
check( 'every title and text is in the form of a field with blocks: the tags and links it has, no entity, no "[", only the placeholders the module replaces'. $what( $form ), $form === [] );
check( 'the ranges are those of the features that bring a section, no more and no fewer'. $what( array_merge( array_diff( array_keys( LEGAL_RANGES ), $contributors ), array_diff( $contributors, array_keys( LEGAL_RANGES ) ) ) ),
	array_diff( array_keys( LEGAL_RANGES ), $contributors ) === [] && array_diff( $contributors, array_keys( LEGAL_RANGES ) ) === [] );

echo "\n". $checks. ' checks, '. $failures. " failed\n";
exit( $failures === 0 ? 0 : 1 );
