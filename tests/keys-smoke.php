<?php
declare(strict_types=1);

/**
 *	Nino features
 *	keys-smoke.php	The grammar of a text key, /<namespace>/<category>/<part>/<name> (AGENTS.md, section 4b), over what every feature ships in
 *									install/text/ and text/: four segments of lower case words, a namespace that is the feature's own, the same keys in de_DE
 *									and en_US, and /_nino/webpage<uri>/... only for a page that the feature's own route makes - the pages and the place of their
 *									route are the "pages" of the feature's own tests/catalogue.php (tests/declarations.php). A template of the unit gives its
 *									category to /template/<category>/...: a file directly in install/templates/ its name, a mail or a pdf in a folder of that name
 *									(install/templates/mail/<name>.tpl) the name of the folder and the file, mail-<name>, and no category stands in two templates, of
 *									one feature or of two. The route of a page is held to the address of the page in a file of the feature's own folder.
 *
 *									The rules are one function over a repository, run over this one and over a repository of probes in the temporary directory,
 *									each probe with one fault, which each rule has to find - and over a feature made of the example of the README.
 *
 *									Reads features/ only. Needs nothing of the kernel.
 *
 *	Usage: php tests/keys-smoke.php
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

// A word of a key: lower case letters and digits, joined by hyphens
const KEYS_WORD = '[a-z0-9]+(?:-[a-z0-9]+)*';

/*	The folders of install/templates/ whose templates carry words: a mail and a pdf of the unit. Their category is the folder and the file name,
	mail-<name>, as a file of the folder above would be named (Newsletter's mail-newsletter-confirm.tpl): the kernel gives a category to the
	files directly in templates/ alone, so a template below them is named here, and a folder of any other name has no words	*/
const KEYS_TEMPLATE_FOLDERS = [ 'mail', 'pdf' ];

/*	The labels of the Elements panel for the fields of an element type that a unit brings: the workbench's words and no site's, so the unit's
	blacklist keeps them out of the Text panel	*/
const KEYS_WORKBENCH = '#^/_admin/elements/field/[a-z0-9-]+/[a-z0-9-]+$#';

$locales = [ 'de_DE', 'en_US' ];

/**
 *	The categories of /template/<category>/... that the templates of a unit carry
 *
 *	@param		string		$dir					The folder of the feature
 *
 *	@return		array<string, array<int, string>>		category => the files below the folder of the feature that carry it
 */
function keysTemplates( string $dir ): array {

	$categories = [];

	foreach( (array) glob( $dir. '/install/templates/*.tpl' ) as $file )
		$categories[basename( (string) $file, '.tpl' )][] = 'install/templates/'. basename( (string) $file );

	foreach( KEYS_TEMPLATE_FOLDERS as $folder )
		foreach( (array) glob( $dir. '/install/templates/'. $folder. '/*.tpl' ) as $file )
			$categories[$folder. '-'. basename( (string) $file, '.tpl' )][] = 'install/templates/'. $folder. '/'. basename( (string) $file );

	return $categories;
}

/**
 *	What a feature ships that the grammar is held to: its key, its unit, its words in both places and the categories of its templates
 *
 *	@param		string		$dir					The folder of the feature
 *	@param		array			$locales
 *
 *	@return		array
 */
function keysFeature( string $dir, array $locales ): array {

	$manifest = (array) include $dir. '/feature.php';
	$unit = is_file( $dir. '/install/manifest.php' ) === true ? (array) include $dir. '/install/manifest.php' : [];
	$read = static function( string $file ): array {

		$keys = [];

		foreach( is_file( $file ) === true ? (array) include $file : [] as $key => $value )
			$keys[trim( (string) $key, '[]' )] = $value;

		return $keys;
	};

	$feature = [ 'dir' => $dir, 'key' => (string) ( $manifest['key'] ?? '' ), 'unit' => $unit, 'site' => [], 'panel' => [], 'templates' => keysTemplates( $dir ) ];

	foreach( $locales as $locale ) {
		$feature['site'][$locale] = $read( $dir. '/install/text/'. $locale. '.php' );
		$feature['panel'][$locale] = $read( $dir. '/text/'. $locale. '.php' );
	}

	return $feature;
}

/**
 *	The words a unit ships against the grammar
 *
 *	@param		string		$directory
 *	@param		array			$feature			From keysFeature()
 *	@param		array			$locales
 *
 *	@return		array										[ keys that are not of the shape, keys in a place that is not the feature's, pages: locale => uri => names, categories two files of the feature carry ]
 */
function keysUnit( string $directory, array $feature, array $locales ): array {

	$shape = [];
	$place = [];
	$pages = [];

	foreach( $locales as $locale )
		foreach( $feature['site'][$locale] as $key => $value ) {

			$page = preg_match( '#^/_nino/webpage(/[^\s]*?)/(name|title|description)$#D', $key, $found ) === 1;
			$workbench = preg_match( KEYS_WORKBENCH, $key ) === 1 && in_array( $key, (array) ( $feature['unit']['blacklist'] ?? [] ), true ) === true;

			if( $page === true ) {
				$pages[$locale][$found[1]][] = $found[2];
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

			if( ( $namespace === 'feature' && $category === $feature['key'] ) === false && ( $namespace === 'template' && isset( $feature['templates'][$category] ) === true ) === false )
				$place[] = $directory. ' '. $locale. ' '. $key;
		}

	$twice = [];

	foreach( $feature['templates'] as $category => $files )
		if( count( $files ) > 1 )
			$twice[] = $category. ' is carried by '. implode( ' and ', array_map( static fn( string $file ): string => 'features/'. $directory. '/'. $file, $files ) );

	return [ $shape, $place, $pages, $twice ];
}

/**
 *	Every rule of this suite over the features below a repository
 *
 *	@param		string		$repo
 *	@param		array			$locales
 *
 *	@return		array<string, array>		rule => what offends it; 'features' is the number read
 */
function keysProblems( string $repo, array $locales ): array {

	$features = [];

	foreach( (array) scandir( $repo. '/features' ) as $directory ) {

		$dir = $repo. '/features/'. $directory;

		if( $directory[0] === '.' || is_dir( $dir ) === false )
			continue;

		$features[$directory] = keysFeature( $dir, $locales );
	}

	$declarations = declarationsRead( $repo );
	$declared = declarationsOf( $declarations, 'pages' );
	$found = [ 'features' => $features, 'shape' => [], 'place' => [], 'twice' => [], 'uncopied' => [], 'unlisted' => [], 'apart' => [], 'unmade' => [], 'mismatched' => [], 'foreign' => [], 'form' => declarationsProblems( $declarations, [ 'pages' ] ) ];
	$pages = [];
	$carried = [];

	foreach( $features as $directory => $feature ) {

		[ $shaped, $placed, $paged, $clashed ] = keysUnit( $directory, $feature, $locales );

		$found['shape'] = array_merge( $found['shape'], $shaped );
		$found['place'] = array_merge( $found['place'], $placed );
		$found['twice'] = array_merge( $found['twice'], $clashed );

		if( $paged !== [] )
			$pages[$directory] = $paged;

		$copied = array_map( 'strval', array_values( (array) ( $feature['unit']['templates'] ?? [] ) ) );

		foreach( $feature['templates'] as $category => $files )
			foreach( $files as $file ) {

				$carried[$category][] = 'features/'. $directory. '/'. $file;

				// The kernel copies the templates the unit names under 'templates', each from install/templates/<name>, a folder made where it is needed
				if( str_contains( substr( $file, strlen( 'install/templates/' ) ), '/' ) === true && in_array( substr( $file, strlen( 'install/templates/' ) ), $copied, true ) === false )
					$found['uncopied'][] = 'features/'. $directory. '/install/manifest.php does not name '. substr( $file, strlen( 'install/templates/' ) ). ' under \'templates\', and the kernel copies only what the unit names';
			}

		foreach( [ 'site', 'panel' ] as $set )
			if( array_diff( array_keys( $feature[$set]['de_DE'] ), array_keys( $feature[$set]['en_US'] ) ) !== [] || array_diff( array_keys( $feature[$set]['en_US'] ), array_keys( $feature[$set]['de_DE'] ) ) !== [] )
				$found['mismatched'][] = $directory. ' '. ( $set === 'site' ? 'install/text' : 'text' );

		foreach( $locales as $locale )
			foreach( array_keys( $feature['panel'][$locale] ) as $key )
				if( str_starts_with( $key, '/_admin/' ) === false )
					$found['foreign'][] = $directory. ' '. $locale. ' '. $key;
	}

	// A category two features carry: the kernel adds words and never replaces them, so the second feature's words would be skipped
	foreach( $carried as $category => $files )
		if( count( array_unique( array_map( static fn( string $file ): string => explode( '/', $file )[1], $files ) ) ) > 1 )
			$found['twice'][] = $category. ' is carried by '. implode( ' and ', $files );

	foreach( $pages as $directory => $files ) {

		foreach( $files as $locale => $uris )
			foreach( $uris as $uri => $names ) {

				sort( $names );

				if( isset( $declared[$directory][$uri] ) === false )
					$found['unlisted'][$directory. ' '. $uri] = $declarations[$directory]['file']. ' does not declare the page '. $uri. ', which the unit of '. $directory. ' names';
				elseif( $names !== [ 'description', 'name', 'title' ] )
					$found['apart'][] = $directory. ' '. $locale. ' '. $uri. ' has '. implode( ', ', $names );
			}

		if( array_keys( $files ) !== $locales || array_keys( $files['de_DE'] ) !== array_keys( $files['en_US'] ) )
			$found['apart'][] = $directory. ': the pages of the two languages differ';
	}

	foreach( $declared as $directory => $uris )
		foreach( $uris as $uri => $evidence ) {

			$file = $declarations[$directory]['file'];

			if( isset( $pages[$directory]['de_DE'][$uri] ) === false )
				$found['unmade'][] = $file. ' declares the page '. $uri. ', and no unit of '. $directory. ' names it';

			$wrong = declarationsRoute( $features[$directory]['dir'], $uri, $evidence );

			if( $wrong !== null )
				$found['unmade'][] = $file. ' declares the page '. $uri. ', and '. $directory. ': '. $wrong;
		}

	$found['declared'] = $declared;

	return $found;
}

$what = static fn( array $found ): string => $found === [] ? '' : ' - '. implode( ' | ', array_slice( $found, 0, 6 ) );
$found = keysProblems( $repo, $locales );
$features = $found['features'];
$badKey = static fn( array $feature ): bool => preg_match( '#^'. KEYS_WORD. '$#D', $feature['key'] ) !== 1;

check( 'the features were read, each with a key that is a word of a text key'. $what( array_keys( array_filter( $features, $badKey ) ) ), count( $features ) > 20 && array_filter( $features, $badKey ) === [] );

echo "\nWhat a unit ships\n";

check( 'every key of install/text/ is of four segments of words, with no number as a name'. $what( $found['shape'] ), $found['shape'] === [] );
check( 'it is /feature/<the feature\'s key>/... or /template/<a template of its unit>/..., and no /project, /module, /_nino or /template/common'. $what( $found['place'] ), $found['place'] === [] );
check( 'no category of a template is carried twice, by two templates of a feature or of two features (a file directly in templates/ is named as it is, a mail or a pdf of a folder for the folder and itself)'. $what( $found['twice'] ), $found['twice'] === [] );

echo "\nThe pages of a feature\n";

check( 'a mail or a pdf in a folder of install/templates/ is named under \'templates\' by the unit, which is what the kernel copies'. $what( $found['uncopied'] ), $found['uncopied'] === [] );
check( 'the pages a feature declares are well formed'. $what( $found['form'] ), $found['form'] === [] );
check( 'a unit names only the pages its feature declares in tests/catalogue.php, each with name, title and description in both languages'. $what( array_merge( $found['unlisted'], $found['apart'] ) ), $found['unlisted'] === [] && $found['apart'] === [] );
check( 'every page a feature declares is named by its unit and has its route in the file of the feature that it names'. $what( $found['unmade'] ), $found['unmade'] === [] && $found['declared'] !== [] );

echo "\nThe two languages\n";

check( 'de_DE and en_US carry the same keys, in install/text/ and in text/ of every feature'. $what( $found['mismatched'] ), $found['mismatched'] === [] );
check( 'the words of a panel are /_admin/... keys'. $what( $found['foreign'] ), $found['foreign'] === [] );

echo "\nThe rules over a repository of probes\n";

// One feature that keeps every rule, and a feature for each rule with the one fault that breaks it; the example of the README is a feature as well
$text = static fn( array $keys ): string => '<?php return '. var_export( array_fill_keys( $keys, 'x' ), true ). ';';
$unit = static function( string $key, array $words, array $templates = [], array $panel = [], string $route = '', ?array $named = null ) use ( $text ): array {

	$site = array_merge( $words, [ '[[/feature/'. $key. '/text/word]]' ] );
	$files = [ 'feature.php' => '<?php return [ \'key\' => \''. $key. '\' ];', 'install/text/de_DE.php' => $text( $site ), 'install/text/en_US.php' => $text( $site ),
		'install/manifest.php' => '<?php return [ \'templates\' => '. var_export( $named ?? $templates, true ). ' ];' ];

	foreach( $templates as $file )
		$files['install/templates/'. $file] = '<p>[[/template/'. $key. '/x/y]]</p>';

	foreach( $panel as $locale => $keys )
		$files['text/'. $locale. '.php'] = $text( $keys );

	if( $route !== '' )
		$files[ucfirst( $key ). '.php'] = $route;

	return $files;
};
$page = static fn( string $uri ): array => array_map( static fn( string $detail ): string => '[[/_nino/webpage'. $uri. '/'. $detail. ']]', [ 'name', 'title', 'description' ] );
$route = static fn( string $uri ): string => '<?php $routes[\'GET:/'. $uri. '\'] = [ \'uri\' => \''. $uri. '\' ];';
$declare = static fn( string $code ): array => [ 'tests/catalogue.php' => '<?php return '. $code. ';' ];

$work = sys_get_temp_dir(). '/nino-keys-'. bin2hex( random_bytes( 6 ) );
$probes = [
	'Good'      => $unit( 'good', array_merge( $page( '/good' ), [ '[[/template/page-x/hero/title]]', '[[/template/mail-invoice/head/subject]]', '[[/template/pdf-offer/head/title]]' ] ), [ 'page-x.tpl', 'mail/invoice.tpl', 'pdf/offer.tpl' ], [], $route( '/good' ) ) + $declare( "[ 'pages' => [ '/good' => 'Good.php' ] ]" ),
	'Shape'     => $unit( 'shape', [ '[[/template/x/y]]' ] ),
	'Place'     => $unit( 'place', [ '[[/feature/other/x/y]]', '[[/template/page-none/hero/title]]' ] ),
	'Twice'     => $unit( 'twice', [], [ 'mail-twice.tpl', 'mail/twice.tpl' ] ),
	'Mail'      => $unit( 'mail', [], [ 'mail/shared.tpl' ] ),
	'Mailer'    => $unit( 'mailer', [], [ 'mail/shared.tpl' ] ),
	'Folder'    => $unit( 'folder', [ '[[/template/letters-note/head/subject]]', '[[/template/pdf-inner/head/title]]', '[[/template/invoice/head/subject]]' ], [ 'letters/note.tpl', 'pdf/deep/inner.tpl' ] ),
	'Unlisted'  => $unit( 'unlisted', $page( '/unlisted' ), [], [], $route( '/unlisted' ) ),
	'Half'      => $unit( 'half', array_merge( $page( '/half' ), $page( '/other' ) ), [], [], $route( '/half' ) ) + $declare( "[ 'pages' => [ '/half' => 'Half.php' ] ]" ),
	'Welcome'   => $unit( 'welcome', [ '[[/template/mail-welcome/head/subject]]' ], [ 'mail/welcome.tpl' ], [], '', [] ),
	'Apart'     => $unit( 'apart', [ '[[/_nino/webpage/apart/name]]', '[[/_nino/webpage/apart/title]]' ], [], [], $route( '/apart' ) ) + $declare( "[ 'pages' => [ '/apart' => 'Apart.php' ] ]" ),
	'Language'  => [ 'feature.php' => '<?php return [ \'key\' => \'language\' ];', 'install/text/de_DE.php' => $text( [ '[[/feature/language/text/word]]' ] ), 'install/text/en_US.php' => $text( [] ) ],
	'Workbench' => $unit( 'workbench', [], [], [ 'de_DE' => [ '/site/x/y/z' ], 'en_US' => [ '/site/x/y/z' ] ] ),
	'Ghost'     => $unit( 'ghost', [], [], [], $route( '/ghost' ) ) + $declare( "[ 'pages' => [ '/ghost' => 'Ghost.php' ] ]" ),
	'Remark'    => $unit( 'remark', $page( '/remark' ), [], [], '<?php // \'uri\' => \'/remark\'' . "\n" ) + $declare( "[ 'pages' => [ '/remark' => 'Remark.php' ] ]" ),
	'Notes'     => $unit( 'notes', $page( '/notes' ) ) + $declare( "[ 'pages' => [ '/notes' => 'tests/catalogue.php' ], 'route' => [ 'uri' => '/notes' ] ]" ),
	'Manifest'  => [ 'feature.php' => '<?php return [ \'key\' => \'manifest\', \'route\' => [ \'uri\' => \'/manifest\' ] ];' ] + $unit( 'manifest', $page( '/manifest' ) ) + $declare( "[ 'pages' => [ '/manifest' => 'feature.php' ] ]" ),
	'Dead'      => $unit( 'dead', $page( '/dead' ), [], [], '<?php // const WAY = \'/dead\';' . "\n" ) + $declare( "[ 'pages' => [ '/dead' => [ 'Dead.php', 'WAY' ] ] ]" ),
	'Silent'    => $unit( 'silent', $page( '/silent' ), [], [], '<?php // no route here' ) + $declare( "[ 'pages' => [ '/silent' => 'Silent.php' ] ]" ),
	'Out'       => $unit( 'out', $page( '/out' ) ) + $declare( "[ 'pages' => [ '/out' => 'link.php' ] ]" ),
	'Dotted'    => $unit( 'dotted', $page( '/dotted' ) ) + $declare( "[ 'pages' => [ '/dotted' => '../Good/Good.php' ] ]" ),
	'Built'     => $unit( 'built', $page( '/built/post' ), [], [], '<?php const WAY = \'/%s/post\'; const KEY = \'built\';' ) + $declare( "[ 'pages' => [ '/built/post' => [ 'Built.php', 'WAY', [ 'Built.php', 'KEY' ] ] ] ]" ),
	'Wrong'     => $unit( 'wrong', $page( '/wrong/post' ), [], [], '<?php const WAY = \'/%s/post\'; const KEY = \'other\';' ) + $declare( "[ 'pages' => [ '/wrong/post' => [ 'Wrong.php', 'WAY', [ 'Wrong.php', 'KEY' ] ] ] ]" ),
];

// The example of the README, as it stands there, as a feature that has what it names
$example = declarationsExample( (string) file_get_contents( $repo. '/README.md' ) );
$probes['Readme'] = $unit( 'readme', array_merge( $page( '/hello' ) ), [], [], $route( '/hello' ) ) + [ 'tests/catalogue.php' => '<?php return '. var_export( $example, true ). ';' ];
$probes['Readme']['Hello.php'] = $probes['Readme']['Readme.php'];

mkdir( $work. '/features', 0755, true );

foreach( $probes as $name => $files )
	declarationsWrite( $work. '/features/'. $name, $files );

// A file of the feature that is a link to a file of another one
symlink( $work. '/features/Good/Good.php', $work. '/features/Out/link.php' );

$probed = keysProblems( $work, $locales );
$of = static fn( array $lines, string $name ): bool => array_filter( $lines, static fn( string $line ): bool => str_contains( $line, $name ) === true ) !== [];
$only = static fn( array $lines, array $names ): bool => array_values( array_filter( [ 'Good', 'Shape', 'Place', 'Twice', 'Mail', 'Mailer', 'Folder', 'Unlisted', 'Half', 'Welcome', 'Apart', 'Language', 'Workbench', 'Ghost', 'Remark', 'Notes', 'Manifest', 'Dead', 'Silent', 'Out', 'Dotted', 'Built', 'Wrong', 'Readme' ],
	static fn( string $name ): bool => $of( $lines, $name ) ) ) === $names;

check( 'a feature that keeps every rule, a mail and a pdf in their folders among them, and a feature made of the example of the README, offend none',
	$example !== null && array_filter( [ $probed['shape'], $probed['place'], $probed['twice'], $probed['unlisted'], $probed['apart'], $probed['unmade'], $probed['mismatched'], $probed['foreign'], $probed['form'] ], static fn( array $lines ): bool => $of( $lines, 'Good' ) === true || $of( $lines, 'Readme' ) === true ) === [] );
check( 'a key that is no four words, a key of another feature, one of a template the unit has not and one of a folder that is none of the two - each is found',
	$only( $probed['shape'], [ 'Shape' ] ) && $only( $probed['place'], [ 'Place', 'Folder' ] ) && count( array_filter( $probed['place'], static fn( string $line ): bool => str_contains( $line, 'Folder' ) === true ) ) === 3 * 2 );
check( 'a category two templates carry is found, in one feature and in two, and the sentence names both files',
	$of( $probed['twice'], 'features/Twice/install/templates/mail-twice.tpl' ) && $of( $probed['twice'], 'features/Twice/install/templates/mail/twice.tpl' )
	&& $of( $probed['twice'], 'features/Mail/install/templates/mail/shared.tpl' ) && $of( $probed['twice'], 'features/Mailer/install/templates/mail/shared.tpl' ) );
check( 'a page the unit names and the feature does not declare - one of two pages of a feature as well - or declares with a way out of its folder, a page with two of its three words, a page of one language and a ghost page are found, and a mail the unit does not name for copying',
	$only( array_values( $probed['unlisted'] ), [ 'Unlisted', 'Half', 'Dotted' ] ) && $only( $probed['form'], [ 'Dotted' ] ) && $only( $probed['apart'], [ 'Apart' ] ) && $only( $probed['unmade'], [ 'Ghost', 'Remark', 'Notes', 'Manifest', 'Dead', 'Silent', 'Out', 'Wrong' ] ) && $only( $probed['uncopied'], [ 'Welcome' ] ) );
check( 'the route of a page is not found in a file that has none, in a comment, in tests/catalogue.php or feature.php, in a file outside the folder of the feature, in a constant that is only in a comment or makes another address - and is where the constants make it',
	$of( $probed['unmade'], 'Silent: Silent.php has no route' ) && $of( $probed['unmade'], 'Out: link.php is no php file of the feature' ) && $of( $probed['unmade'], 'Wrong: Wrong.php has no const WAY with KEY that is /wrong/post' ) && $of( $probed['unmade'], 'Built' ) === false );
check( 'the two languages that differ, and a panel word that is no /_admin/ word, are found', $only( $probed['mismatched'], [ 'Language' ] ) && $only( $probed['foreign'], [ 'Workbench' ] ) );

declarationsRemove( $work );

echo "\n". $checks. ' checks, '. $failures. " failed\n";
exit( $failures === 0 ? 0 : 1 );
