<?php
declare(strict_types=1);

/**
 *	Nino features
 *	keys-smoke.php	The grammar of a text key, /<namespace>/<category>/<part>/<name> (AGENTS.md, section 4b), over what every feature ships in
 *									install/text/ and text/: four segments of lower case words, a namespace that is the feature's own, the same keys in de_DE
 *									and en_US, and /_nino/webpage<uri>/... only for a page that the feature's own route makes.
 *
 *									Reads features/ only. Needs nothing of the kernel.
 *
 *	Usage: php tests/keys-smoke.php
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

// A word of a key: lower case letters and digits, joined by hyphens
const KEYS_WORD = '[a-z0-9]+(?:-[a-z0-9]+)*';

/*	The pages a feature makes with a route of its own, and so names with /_nino/webpage<uri>/(name|title|description), by the key of the
	feature: the uri of each page, and where the code of the feature makes its route - a file of the feature and what it holds there. A page that
	no unit names fails the test, and so does a route that is gone, so the list cannot outlive what it was for	*/
const KEYS_PAGES = [
	'hello'     => [ '/hello' => [ [ 'Hello.php', '/\'GET:\/\/hello\'/' ] ] ],
	'posts'     => [
		'/blog'      => [ [ 'Posts.php', '/INDEX_URI\s*=\s*\'\/%s\'/' ], [ 'Sections/Sections.php', '/DEFAULT_KEY\s*=\s*\'blog\'/' ] ],
		'/blog/post' => [ [ 'Posts.php', '/POST_URI\s*=\s*\'\/%s\/post\'/' ], [ 'Sections/Sections.php', '/DEFAULT_KEY\s*=\s*\'blog\'/' ] ],
	],
	'protected' => [ '/.protected' => [ [ 'ProtectedArea.php', '/LOGIN_URI\s*=\s*\'\/\.protected\'/' ] ] ],
	'newsletter' => [
		'/.newsletter'             => [ [ 'Newsletter.php', '/\'GET:\/\/\.newsletter\'/' ] ],
		'/.newsletter/unsubscribe' => [ [ 'Newsletter.php', '/\'GET:\/\/\.newsletter\/unsubscribe\'/' ] ],
	],
	// Its pages are the routes of its unit, not of its code
	'websitestart' => [
		'/home'    => [ [ 'install/manifest.php', '/\'GET:\/\/\'\s*=>\s*\[\s*\'uri\'\s*=>\s*\'\/home\'/' ] ],
		'/contact' => [ [ 'install/manifest.php', '/\'GET:\/\/contact\'\s*=>\s*\[\s*\'uri\'\s*=>\s*\'\/contact\'/' ] ],
		'/404'     => [ [ 'install/manifest.php', '/\'GET:\/\/404\'\s*=>\s*\[\s*\'uri\'\s*=>\s*\'\/404\'/' ] ],
	],
];

/*	The labels of the Elements panel for the fields of an element type that a unit brings: the workbench's words and no site's, so the unit's
	blacklist keeps them out of the Text panel	*/
const KEYS_WORKBENCH = '#^/_admin/elements/field/[a-z0-9-]+/[a-z0-9-]+$#';

$locales = [ 'de_DE', 'en_US' ];
$features = [];

foreach( (array) scandir( $repo. '/features' ) as $directory ) {

	$dir = $repo. '/features/'. $directory;

	if( $directory[0] === '.' || is_dir( $dir ) === false )
		continue;

	$manifest = (array) include $dir. '/feature.php';
	$unit = is_file( $dir. '/install/manifest.php' ) === true ? (array) include $dir. '/install/manifest.php' : [];
	$read = static function( string $file ): array {

		$keys = [];

		foreach( is_file( $file ) === true ? (array) include $file : [] as $key => $value )
			$keys[trim( (string) $key, '[]' )] = $value;

		return $keys;
	};

	$features[$directory] = [ 'dir' => $dir, 'key' => (string) ( $manifest['key'] ?? '' ), 'unit' => $unit ];

	foreach( $locales as $locale ) {
		$features[$directory]['site'][$locale] = $read( $dir. '/install/text/'. $locale. '.php' );
		$features[$directory]['panel'][$locale] = $read( $dir. '/text/'. $locale. '.php' );
	}

	// The templates the unit copies into a project name the categories of /template/<category>/...
	$features[$directory]['templates'] = array_map( static fn( string $file ): string => basename( $file, '.tpl' ), (array) glob( $dir. '/install/templates/*.tpl' ) );
}

$what = static fn( array $found ): string => $found === [] ? '' : ' - '. implode( ' | ', array_slice( $found, 0, 6 ) );

check( 'the features were read, each with a key that is a word of a text key'. $what( array_keys( array_filter( $features, static fn( array $feature ): bool => preg_match( '#^'. KEYS_WORD. '$#D', $feature['key'] ) !== 1 ) ) ),
	count( $features ) > 20 && array_filter( $features, static fn( array $feature ): bool => preg_match( '#^'. KEYS_WORD. '$#D', $feature['key'] ) !== 1 ) === [] );

echo "\nWhat a unit ships\n";

$shape = [];
$place = [];
$pages = [];

foreach( $features as $directory => $feature )
	foreach( $locales as $locale )
		foreach( $feature['site'][$locale] as $key => $value ) {

			$page = preg_match( '#^/_nino/webpage(/[^\s]*?)/(name|title|description)$#D', $key, $found ) === 1;
			$workbench = preg_match( KEYS_WORKBENCH, $key ) === 1 && in_array( $key, (array) ( $feature['unit']['blacklist'] ?? [] ), true ) === true;

			if( $page === true ) {
				$pages[$feature['key']][$locale][$found[1]][] = $found[2];
				continue;
			}

			if( $workbench === true )
				continue;

			// Four segments of words, a number is no name
			if( preg_match( '#^/[a-z]+(?:/'. KEYS_WORD. '){3}$#D', $key ) !== 1 || preg_match( '#/[0-9]+$#', $key ) === 1 || is_string( $value ) === false ) {
				$shape[] = $directory. ' '. $locale. ' '. $key;
				continue;
			}

			[ , $namespace, $category ] = explode( '/', $key );

			if( ( $namespace === 'feature' && $category === $feature['key'] ) === false && ( $namespace === 'template' && in_array( $category, $feature['templates'], true ) === true ) === false )
				$place[] = $directory. ' '. $locale. ' '. $key;
		}

check( 'every key of install/text/ is of four segments of words, with no number as a name'. $what( $shape ), $shape === [] );
check( 'it is /feature/<the feature\'s key>/... or /template/<a template of its unit>/..., and no /project, /module, /_nino or /template/common'. $what( $place ), $place === [] );

echo "\nThe pages of a feature\n";

$unlisted = [];
$unmade = [];
$apart = [];

foreach( $pages as $key => $files ) {

	foreach( $files as $locale => $uris )
		foreach( $uris as $uri => $names ) {

			sort( $names );

			if( isset( KEYS_PAGES[$key][$uri] ) === false )
				$unlisted[] = $key. ' '. $uri;
			elseif( $names !== [ 'description', 'name', 'title' ] )
				$apart[] = $key. ' '. $locale. ' '. $uri. ' has '. implode( ', ', $names );
		}

	if( array_keys( $files ) !== $locales || array_keys( $files['de_DE'] ) !== array_keys( $files['en_US'] ) )
		$apart[] = $key. ': the pages of the two languages differ';
}

foreach( KEYS_PAGES as $key => $uris )
	foreach( $uris as $uri => $evidence ) {

		$feature = array_values( array_filter( $features, static fn( array $one ): bool => $one['key'] === $key ) )[0] ?? null;

		if( isset( $pages[$key]['de_DE'][$uri] ) === false )
			$unmade[] = $key. ' '. $uri. ' is named by no unit';

		foreach( $evidence as [ $file, $pattern ] )
			if( $feature === null || is_file( $feature['dir']. '/'. $file ) === false || preg_match( $pattern, (string) file_get_contents( $feature['dir']. '/'. $file ) ) !== 1 )
				$unmade[] = $key. ' '. $uri. ' has no route in '. $file;
	}

check( 'a unit names only the pages its feature makes a route for, each with name, title and description in both languages'. $what( array_merge( $unlisted, $apart ) ), $unlisted === [] && $apart === [] );
check( 'every page the list names is named by a unit and still has its route in the feature\'s code'. $what( $unmade ), $unmade === [] );

echo "\nThe two languages\n";

$mismatched = [];
$foreign = [];

foreach( $features as $directory => $feature )
	foreach( [ 'site', 'panel' ] as $set ) {

		$german = array_keys( $feature[$set]['de_DE'] );
		$english = array_keys( $feature[$set]['en_US'] );

		if( array_diff( $german, $english ) !== [] || array_diff( $english, $german ) !== [] )
			$mismatched[] = $directory. ' '. ( $set === 'site' ? 'install/text' : 'text' );
	}

foreach( $features as $directory => $feature )
	foreach( $locales as $locale )
		foreach( array_keys( $feature['panel'][$locale] ) as $key )
			if( str_starts_with( $key, '/_admin/' ) === false )
				$foreign[] = $directory. ' '. $locale. ' '. $key;

check( 'de_DE and en_US carry the same keys, in install/text/ and in text/ of every feature'. $what( $mismatched ), $mismatched === [] );
check( 'the words of a panel are /_admin/... keys'. $what( $foreign ), $foreign === [] );

echo "\n". $checks. ' checks, '. $failures. " failed\n";
exit( $failures === 0 ? 0 : 1 );
