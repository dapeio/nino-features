<?php
declare(strict_types=1);

/**
 *	Nino features
 *	keys-smoke.php	The grammar of a text key, /<namespace>/<category>/<part>/<name>
 *									(see AGENTS.md, "Text keys", and the Features manual of Nino),
 *									and everything the catalogue ships to it. Two parts:
 *
 *									Part 1 needs nothing of the kernel and runs against every
 *									checkout:
 *
 *										1. what a feature's install unit writes into a project's text
 *										   files is /feature/<its key>/<part>/<name> or
 *										   /template/<category>/<part>/<name> - no /project, no
 *										   /template/common, no /module, no /_nino - reads the same
 *										   in both languages, and is global or per language, never
 *										   both
 *										2. the blacklist entries of a unit follow the grammar
 *										3. a feature's key is a word of one
 *										4. no old key family is left anywhere - code, templates,
 *										   texts, documents - and the key literals in the code name
 *										   a namespace of the grammar, the system or a runtime fill
 *
 *									Part 2 checks the features against a kernel that has the grammar
 *									(\Nino\Modules\Template::category() decides it), and only the
 *									features bin/applicable.php names for that checkout:
 *
 *										5. every key a template of the catalogue reads is a runtime
 *										   fill, a key of the system, or delivered - by the feature,
 *										   or by the kernel's base unit or modules - and a template
 *										   reads template keys of its own category or of "common"
 *										   only
 *										6. a template the kernel delivers too is the kernel's, byte
 *										   for byte, and a template with keys of its own has a
 *										   category
 *										7. the words of the keys are looked up in the workbench's
 *										   vocabulary, where the kernel has one - a note, not a
 *										   failure
 *
 *									Where Part 2 cannot run, it says so in a line starting "note".
 *
 *	Usage: php tests/keys-smoke.php
 *	       NINO_ROOT=/path/to/nino php tests/keys-smoke.php
 */

$repo = dirname( __DIR__ );
$root = getenv( 'NINO_ROOT' ) ?: dirname( $repo ). '/nino';
$root = realpath( $root ) ?: $root;

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
function keysCheck( string $label, bool $condition ): void {
	global $failures, $checks;
	$checks++;
	if( $condition === true ) {
		echo "  ok  - $label\n";
		return;
	}
	$failures++;
	echo "FAIL  - $label\n";
}

// A word of a key: lower-case letters and digits, joined by hyphens
const KEYS_WORD = '[a-z0-9]+(?:-[a-z0-9]+)*';

// What the system writes by itself: a page's details after its Element-URI, a language's name after its code
const KEYS_SYSTEM = [ '#^/_nino/webpage(/.+)/(name|title|description|uri)$#', '#^/_nino/locale/[a-z]{2}_[A-Z]{2}/name$#' ];

// The one thing a feature's unit may write that is not of the grammar: the workbench's own labels
// for the fields of an Elements type it brings, which its blacklist keeps out of the Text panel
const KEYS_WORKBENCH = '#^/_admin/elements/field/[a-z0-9-]+/[a-z0-9-]+$#';

/**
 *	@param		string		$key
 *
 *	@return 	bool										Whether the key is /<namespace>/<category>/<part>/<name>
 */
function keysGrammar( string $key ): bool {
	return preg_match( '#^/(?:template|project|feature|module)(?:/'. KEYS_WORD. '){3}$#D', $key ) === 1;
}

/**
 *	@param		string		$key
 *
 *	@return 	bool										Whether it is a key the system writes
 */
function keysSystem( string $key ): bool {

	foreach( KEYS_SYSTEM as $pattern )
		if( preg_match( $pattern, $key ) === 1 )
			return true;

	return false;
}

/**
 *	@param		string		$dir
 *	@param		array			$extensions		Without the dot
 *
 *	@return 	array										Absolute paths below a directory, recursively, sorted
 */
function keysFiles( string $dir, array $extensions ): array {

	$found = [];
	if( is_dir( $dir ) === false )
		return $found;

	foreach( new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $dir, FilesystemIterator::SKIP_DOTS ) ) as $file )
		if( $file->isFile() === true && in_array( $file->getExtension(), $extensions, true ) === true )
			$found[] = $file->getPathname();

	sort( $found );

	return $found;
}

/**
 *	The keys of one text fragment, without their brackets
 *
 *	@param		string		$file
 *
 *	@return 	array										key => value
 */
function keysOfFragment( string $file ): array {

	$keys = [];
	foreach( is_file( $file ) === true ? (array) include $file : [] as $bracketKey => $value )
		$keys[ trim( (string) $bracketKey, '[]' ) ] = $value;

	return $keys;
}

/**
 *	The keys one template reads: every [[/...]] from the inside out, [json /...],
 *	[image /...] and the strings of a section's json that name a key
 *
 *	@param		string		$source
 *
 *	@return 	array										[ [ key, composed, image ], ... ] - composed: a placeholder was in the key, put as "\x01" (the response uri as "/\x01")
 */
function keysReadBy( string $source ): array {

	$reads = [];
	$work 	= $source;

	do {
		$work = (string) preg_replace_callback( '/\[\[([^\[\]]+)\]\]/', static function( array $match ) use ( &$reads ): string {

			$key = $match[1];

			if( $key[0] !== '/' )
				return "\x01";

			$reads[] = [ 'key' => $key, 'composed' => str_contains( $key, "\x01" ), 'image' => false ];

			return $key === '/nino/http/response/uri' ? "/\x01" : "\x01";
		}, $work, -1, $replaced );
	} while( $replaced > 0 );

	if( preg_match_all( '/\[json\s+(\/[^\s\]]+)/', $source, $json ) > 0 )
		foreach( $json[1] as $key )
			$reads[] = [ 'key' => $key, 'composed' => false, 'image' => false ];

	if( preg_match_all( '/\[image\s+(\/[^\s\]]+)/', $source, $image ) > 0 )
		foreach( $image[1] as $key )
			$reads[] = [ 'key' => $key, 'composed' => false, 'image' => true ];

	// The bindings and the background of a section the Template Builder wrote
	if( preg_match_all( '/<!--\s*nino:section\s+(\{.*?\})\s*-->/s', $source, $sections ) > 0 )
		foreach( $sections[1] as $sectionJson ) {
			preg_match_all( '/"backgroundImage":"(\/[^"]+)"/', $sectionJson, $backgrounds );
			foreach( $backgrounds[1] as $key )
				$reads[] = [ 'key' => $key, 'composed' => false, 'image' => true ];
			preg_match_all( '/"(\/(?:template|project|feature|module|_nino)\/[^"]+)"/', (string) preg_replace( '/"backgroundImage":"[^"]*"/', '', $sectionJson ), $bound );
			foreach( $bound[1] as $key )
				$reads[] = [ 'key' => $key, 'composed' => false, 'image' => false ];
		}

	return $reads;
}

/**
 *	@param		string		$key					A composed key with its placeholders replaced by "\x01"
 *
 *	@return 	bool										Whether it is one of the shapes a key may be put together in: a
 *																	page's details after the response uri, a language's name after its
 *																	code, or four segments of which a placeholder is a whole segment
 *																	or the identifier ending a list's part
 */
function keysComposedShape( string $key ): bool {

	if( preg_match( '#^/_nino/webpage/\x01/(name|title|description)$#', $key ) === 1 || preg_match( '#^/_nino/locale/\x01/name$#', $key ) === 1 )
		return true;

	foreach( explode( '/', ltrim( $key, '/' ) ) as $index => $segment )
		if( str_contains( $segment, "\x01" ) === true && $segment !== "\x01" && ( $index !== 2 || preg_match( '#^'. KEYS_WORD. '-\x01$#', $segment ) !== 1 ) )
			return false;

	return keysGrammar( str_replace( "\x01", 'x', $key ) );
}

/**
 *	@param		string		$name					A file name, page-home.tpl
 *
 *	@return 	string|null							Its category as the kernel's \Nino\Modules\Template::category() has it:
 *																	the name without .tpl, where that is a word of a key
 */
function keysCategoryOf( string $name ): ?string {

	$name = str_ends_with( $name, '.tpl' ) === true ? substr( $name, 0, -4 ) : $name;

	return preg_match( '#^'. KEYS_WORD. '$#D', $name ) === 1 ? $name : null;
}


// --- The features -----------------------------------------------------------------

$features = [];
foreach( scandir( $repo. '/features' ) ?: [] as $directory ) {

	if( $directory[0] === '.' || is_dir( $repo. '/features/'. $directory ) === false )
		continue;

	$dir 				= $repo. '/features/'. $directory;
	$manifest 	= (array) include $dir. '/feature.php';
	$unitDir 		= $dir. '/install';
	$unit 			= is_file( $unitDir. '/manifest.php' ) === true ? (array) include $unitDir. '/manifest.php' : [];

	$text = [ 'global' => keysOfFragment( $unitDir. '/text/global.php' ) ];
	foreach( [ 'de_DE', 'en_US' ] as $locale )
		$text[$locale] = keysOfFragment( $unitDir. '/text/'. $locale. '.php' );

	// What the unit copies into a project: what its manifest lists, and every file of install/templates/
	$templates = array_map( 'strval', (array) ( $unit['templates'] ?? [] ) );
	foreach( glob( $unitDir. '/templates/*.tpl' ) ?: [] as $file )
		$templates[] = basename( $file );

	$features[$directory] = [
		'directory' => $directory,
		'dir' 			=> $dir,
		'key' 			=> (string) ( $manifest['key'] ?? '' ),
		'unit' 			=> $unit,
		'text' 			=> $text,
		'keys' 			=> array_merge( array_keys( $text['global'] ), array_keys( $text['de_DE'] ), array_keys( $text['en_US'] ) ),
		'blacklist' => array_map( 'strval', (array) ( $unit['blacklist'] ?? [] ) ),
		'templates' => array_values( array_unique( $templates ) ),
	];
}

echo "Checkout: $root\n\n";


// --- 1 to 3. The units and the manifests -------------------------------------------

echo "1. What a feature's install unit writes\n";

keysCheck( 'the catalogue has its features: more than twenty, each with a key', count( $features ) > 20 && array_filter( $features, static fn( array $feature ): bool => $feature['key'] === '' ) === [] );

$badShape = [];
$badPlace = [];
foreach( $features as $feature )
	foreach( [ 'global', 'de_DE', 'en_US' ] as $file )
		foreach( $feature['text'][$file] as $key => $value ) {

			$workbench = preg_match( KEYS_WORKBENCH, $key ) === 1 && in_array( $key, $feature['blacklist'], true ) === true;

			if( keysGrammar( $key ) === false && $workbench === false )
				$badShape[] = $feature['directory']. ' '. $file. ' '. $key;
			else if( $workbench === false ) {

				[ , $namespace, $category ] = explode( '/', $key );

				// /feature/<its key>/..., or /template/<a category>/... - and a category is a template of its own,
				// never the common words, which the kernel's base unit delivers
				if( ( $namespace === 'feature' && $category !== $feature['key'] )
					|| ( $namespace === 'template' && ( $category === 'common' || in_array( $category, array_filter( array_map( 'keysCategoryOf', $feature['templates'] ) ), true ) === false ) )
					|| in_array( $namespace, [ 'project', 'module' ], true ) === true )
					$badPlace[] = $feature['directory']. ' '. $file. ' '. $key;
			}

			if( is_string( $value ) === false )
				$badShape[] = $feature['directory']. ' '. $file. ' '. $key. ' (no string)';
		}

keysCheck( 'every key a unit delivers follows the grammar - Social\'s labels for the fields of its Elements type, blacklisted, are the one exception'. ( $badShape === [] ? '' : ' - '. implode( ', ', array_slice( $badShape, 0, 6 ) ) ), $badShape === [] );
keysCheck( 'a unit delivers /feature/<its own key>/... and /template/<a category of one of its templates>/..., never /project, /module, /_nino or /template/common'. ( $badPlace === [] ? '' : ' - '. implode( ', ', array_slice( $badPlace, 0, 6 ) ) ), $badPlace === [] );

$numbered = [];
foreach( $features as $feature )
	foreach( $feature['keys'] as $key )
		if( preg_match( '#/[0-9]+$#', $key ) === 1 )
			$numbered[] = $feature['directory']. ' '. $key;
keysCheck( 'no key ends in a number: a name says what the text is, not which one it is'. ( $numbered === [] ? '' : ' - '. implode( ', ', $numbered ) ), $numbered === [] );

$mismatched = [];
$doubled = [];
foreach( $features as $feature ) {
	if( array_diff( array_keys( $feature['text']['de_DE'] ), array_keys( $feature['text']['en_US'] ) ) !== [] || array_diff( array_keys( $feature['text']['en_US'] ), array_keys( $feature['text']['de_DE'] ) ) !== [] )
		$mismatched[] = $feature['directory'];
	foreach( array_keys( $feature['text']['global'] ) as $key )
		if( isset( $feature['text']['de_DE'][$key] ) === true || isset( $feature['text']['en_US'][$key] ) === true )
			$doubled[] = $feature['directory']. ' '. $key;
}
keysCheck( 'English and German carry the same keys in every unit'. ( $mismatched === [] ? '' : ' - '. implode( ', ', $mismatched ) ), $mismatched === [] );
keysCheck( 'no key is global and per language at once'. ( $doubled === [] ? '' : ' - '. implode( ', ', $doubled ) ), $doubled === [] );
echo "\n";

echo "2. The blacklists\n";

$badBlacklist = [];
foreach( $features as $feature )
	foreach( $feature['blacklist'] as $entry )
		if( keysGrammar( $entry ) === false && preg_match( KEYS_WORKBENCH, $entry ) !== 1 )
			$badBlacklist[] = $feature['directory']. ' '. $entry;
keysCheck( 'every blacklist entry of a unit follows the grammar'. ( $badBlacklist === [] ? '' : ' - '. implode( ', ', $badBlacklist ) ), $badBlacklist === [] );

$notOwn = [];
foreach( $features as $feature )
	foreach( $feature['blacklist'] as $entry )
		if( preg_match( KEYS_WORKBENCH, $entry ) !== 1 && str_starts_with( $entry, '/feature/'. $feature['key']. '/' ) === false )
			$notOwn[] = $feature['directory']. ' '. $entry;
keysCheck( 'a unit blacklists what its own code fills at request time: /feature/<its key>/...'. ( $notOwn === [] ? '' : ' - '. implode( ', ', $notOwn ) ), $notOwn === [] );
echo "\n";

echo "3. The keys of the features\n";

$badKeys = [];
foreach( $features as $feature )
	if( preg_match( '#^'. KEYS_WORD. '$#D', $feature['key'] ) !== 1 )
		$badKeys[] = $feature['directory']. ' '. $feature['key'];
keysCheck( 'every feature\'s key is a word of a text key: it is the category of /feature/<key>/...'. ( $badKeys === [] ? '' : ' - '. implode( ', ', $badKeys ) ), $badKeys === [] );
echo "\n";


// --- 4. Old forms and key literals --------------------------------------------------

echo "4. No old key family is left\n";

// The families a key used to have - not the first segments they started with, which are words
// an url path has as well (/embed/..., /posts/my-first-post). Nino's own list, and the families
// of this catalogue's own features
$old = [
	'/page-[a-z0-9][a-z0-9-]*/', '/page-(?=[\'"`\[<])', '/webpage(?![\w-])', '/company/', '/website/', '/global/', '/cookiebanner/',
	'/form/(?:label|info|email|subject|required|title)', '/mail/(?:sender|style|owner|user|newsletter)',
	'/slider/label', '/newsletter/(?:label|info|page|confirm|unsubscribe)', '/maintenance/(?:title|text)',
	'/nino/locales/(?:title|locale)', '/date/year',
	'/compare/(?:before|after|handle)', '/consent/(?:title|text|policy-label|accept-all|necessary-only|save|open|category)',
	'/copy/(?:do|done|failed)', '/countdown/(?:days?|hours?|minutes?|seconds?|done)', '/embed/(?:load|note|open|frame)',
	'/hello/(?:note|page)', '/modeswitch/(?:label|light|system|dark)', '/toc/(?:title|anchor)',
	'/posts/(?:index|label|prev|next|nav)', '/protected/(?:title|text|label|return|error)',
	'/ticker/toggle', '/typewriter/toggle', '/lightbox/label', '/gallery/caption', '/page-suche/', '/page/search/',
	'/(?:compare|countdown|modeswitch|posts)/(?=[\'"`])',
];
// A family that ends in a word ends there: /slider/label is not /slider/labels. One that
// ends in a slash goes on into the key. Before it no key or word character stands, so
// /project/company/... is not /company/...
$oldPattern = '~(?<![\w.-])(?:'. implode( '|', array_map( static fn( string $family ): string => str_ends_with( $family, '/' ) === true ? $family : $family. '(?![\w-])', $old ) ). '|theme\.(?:header|footer|%s)(?![\w-]))~';

$leftovers = [];
$scanned = 0;
$shipped = array_merge( keysFiles( $repo. '/features', [ 'php', 'js', 'tpl', 'css', 'md' ] ), [ $repo. '/AGENTS.md', $repo. '/README.md', $repo. '/README.de.md' ] );
foreach( $shipped as $file ) {

	$relative = ltrim( substr( $file, strlen( $repo ) ), '/' );

	// A changelog keeps its old entries as they were, and a test names old forms to show they are gone
	if( str_contains( '/'. $relative, '/tests/' ) === true || basename( $relative ) === 'CHANGELOG.md' || is_file( $file ) === false )
		continue;

	$scanned++;

	foreach( explode( "\n", (string) file_get_contents( $file ) ) as $number => $line )
		if( preg_match( $oldPattern, $line, $hit ) === 1 )
			$leftovers[] = $relative. ':'. ( $number + 1 ). ' '. trim( $hit[0] );
}
keysCheck( 'no shipped file ('. $scanned. ' of them: code, templates, texts, stylesheets, documents) names a key family of an old form, a page text of the old shape, /webpage, /date/year or a theme.* frame'
	. ( $leftovers === [] ? '' : ' - '. implode( '; ', array_slice( $leftovers, 0, 10 ) ). ( count( $leftovers ) > 10 ? ' ... '. count( $leftovers ). ' in all' : '' ) ), $leftovers === [] && $scanned > 300 );

// The pattern is no mere formality: it finds the forms it is for, and leaves the new ones alone
$probes = [ '[[/company/name]]', '/page-home/welcome/title', '[[/webpage/home/title]]', '/form/label/email', '/mail/user/title', '/date/year', 'theme.header.tpl', '[[/slider/label/prev]]',
	'[[/consent/accept-all]]', '[[/countdown/days]]', '[[/embed/load]]', '/posts/nav/prev', '"/posts/"', '[[/protected/return]]', '/ticker/toggle', '[[/lightbox/label/close]]', '[[/newsletter/page/title]]', '[[/page-suche/query]]' ];
$probeMissed = array_values( array_filter( $probes, static fn( string $probe ): bool => preg_match( $oldPattern, $probe ) !== 1 ) );
$probeMisses = [];
foreach( [ '[[/project/company/general/name]]', '/template/page-home/welcome/title', '[[/_nino/webpage/home/title]]', '/template/common/form/email', '/nino/date/year', 'frame-header.tpl',
	'[[/feature/consent/action/accept-all]]', '[[/feature/countdown/unit-day/many]]', '/embed/abc', '/posts/my-first-post', '/feature/newsletter/label/submit', '/templates/page-home', '[[/feature/posts/navigation/prev]]' ] as $probe )
	if( preg_match( $oldPattern, $probe ) === 1 )
		$probeMisses[] = $probe;
keysCheck( 'the pattern finds each of the '. count( $probes ). ' old forms it is meant for, and none of the new ones - nor an url path such as /embed/ or /posts/my-first-post'. ( $probeMissed === [] && $probeMisses === [] ? '' : ' - missed '. implode( ', ', $probeMissed ). ', found '. implode( ', ', $probeMisses ) ), $probeMissed === [] && $probeMisses === [] );

// The key literals in the code: a namespace of the grammar, the system or a runtime fill. The workbench's own
// words, /_admin/..., are the workbench's
$stray = [];
$literals = 0;
$codeLiterals = [];
foreach( keysFiles( $repo. '/features', [ 'php', 'js' ] ) as $file ) {

	$relative = ltrim( substr( $file, strlen( $repo ) ), '/' );

	if( str_contains( '/'. $relative, '/tests/' ) === true )
		continue;

	$candidates = [];

	foreach( explode( "\n", (string) file_get_contents( $file ) ) as $line ) {

		// A comment names a key as an example, and an example is the project's to have or not
		$comment = preg_match( '~^\s*(?:\*|//|/\*)~', $line ) === 1;

		foreach( [ '/\[\[(\/[^\[\]\s\'"]+)/', '/renderTextfill\(\s*[^,()]+,\s*\'(\/[^\']*)\'/', '/getText\(\s*\'(\/[^\']*)\'/' ] as $pattern )
			if( preg_match_all( $pattern, $line, $found ) > 0 )
				foreach( $found[1] as $key )
					$candidates[] = [ 'key' => $key, 'comment' => $comment ];
	}

	foreach( $candidates as $candidate ) {

		$key 			= $candidate['key'];
		$segment 	= explode( '/', ltrim( $key, '/' ) )[0];

		if( $segment === '_admin' )
			continue;

		$literals++;

		if( $candidate['comment'] === false )
			$codeLiterals[] = [ 'file' => $relative, 'key' => $key ];

		if( in_array( $segment, [ 'template', 'project', 'feature', 'module', '_nino', 'nino' ], true ) === false )
			$stray[] = $relative. ' '. $key;
	}
}
keysCheck( 'every key literal in the features\' code ('. $literals. ') names a namespace of the grammar, the system or a runtime fill'. ( $stray === [] ? '' : ' - '. implode( '; ', array_slice( array_unique( $stray ), 0, 8 ) ) ), $stray === [] && $literals > 40 );
echo "\n";


// --- Part 2: against a kernel that has the grammar -----------------------------------

$harness = $root. '/tests/harness.php';
$part2 = false;

if( is_file( $harness ) === false )
	echo "note - no Nino checkout with tests/harness.php at $root: the keys are checked against a kernel in Part 2, and there is none\n";
else {

	// This repository's features/ is where the kernel looks them up
	defined( 'NINO_FEATURES_DIR' ) === true || define( 'NINO_FEATURES_DIR', $repo. '/features' );
	require $harness;

	if( class_exists( '\Nino\Modules\Template' ) === false || method_exists( '\Nino\Modules\Template', 'category' ) === false )
		echo "note - the Nino at $root (". \Nino\VERSION. ") has no \\Nino\\Modules\\Template::category(): it is from before the text key grammar, so the keys are not checked against it\n";
	else
		$part2 = true;
}

if( $part2 === true ) {

	$appData = ninoSandbox( 'keys' );
	$appData['/nino/dir'] 		= '';
	$appData['/nino/public'] 	= '/public';

	// Which features this checkout can run: bin/applicable.php says, the same answer the CI copies by
	$applicable = trim( (string) shell_exec( escapeshellarg( PHP_BINARY ). ' '. escapeshellarg( $repo. '/bin/applicable.php' ). ' '. escapeshellarg( $root ). ' 2>/dev/null' ) );
	$applicable = $applicable === '' ? [] : explode( "\n", $applicable );

	echo "Part 2: against Nino ". \Nino\VERSION. " - ". count( $applicable ). " of ". count( $features ). " features run on it\n\n";

	// What the kernel delivers: the base unit, the page units and the modules' units
	$kernelUnits = [ 'base' => $root. '/_admin/install/library/base' ];
	foreach( glob( $root. '/_admin/install/library/pages/*', GLOB_ONLYDIR ) ?: [] as $dir )
		$kernelUnits['page:'. basename( $dir )] = $dir;
	foreach( glob( $root. '/_nino/Nino/Modules/*/install', GLOB_ONLYDIR ) ?: [] as $dir )
		$kernelUnits['module:'. strtolower( basename( dirname( $dir ) ) )] = $dir;

	$kernelKeys 			= [];
	$kernelBaseKeys 	= [];
	$kernelTemplates 	= [];
	$imageSlots 			= [];
	foreach( $kernelUnits as $id => $dir ) {

		foreach( [ 'global', 'de_DE', 'en_US' ] as $file )
			foreach( array_keys( keysOfFragment( $dir. '/text/'. $file. '.php' ) ) as $key ) {
				$kernelKeys[$key] = true;
				if( $id === 'base' )
					$kernelBaseKeys[$key] = true;
			}

		foreach( glob( $dir. '/templates/*.tpl' ) ?: [] as $file )
			$kernelTemplates[ basename( $file ) ][] = $file;

		$kernelManifest = is_file( $dir. '/manifest.php' ) === true ? (array) include $dir. '/manifest.php' : [];
		foreach( array_keys( (array) ( $kernelManifest['imageSlots'] ?? [] ) ) as $slot )
			$imageSlots[(string) $slot] = true;
	}

	$runtimeFills = \Nino\Html::runtimeFillKeys( $appData );

	// The features' own units, for where a key of one feature is read from another feature's template
	$featureKeys = [];
	foreach( $features as $feature )
		foreach( array_merge( $feature['keys'], $feature['blacklist'] ) as $key )
			$featureKeys[$key] = true;

	// The Design frames are the project's frame-header.tpl and frame-footer.tpl once applied
	$frameTarget = [];
	preg_match( "#FRAME_TARGET\s*=\s*'/templates/(frame-%s)\.tpl'#", (string) file_get_contents( $repo. '/features/Design/Compiler/Compiler.php' ), $frameTarget );

	// Every template of the catalogue that reads keys, with the category it carries its own under:
	// a template the unit copies into the project has its file's, a Design frame its target's, and
	// a template inside a feature or a preset of the Template Builder has none and reads the common words
	$templateFiles = [];
	foreach( $features as $feature ) {

		if( in_array( $feature['directory'], $applicable, true ) === false )
			continue;

		foreach( glob( $feature['dir']. '/install/templates/*.tpl' ) ?: [] as $file )
			$templateFiles[] = [ 'feature' => $feature, 'name' => basename( $file ), 'file' => $file, 'category' => keysCategoryOf( basename( $file ) ), 'owner' => true ];

		foreach( glob( $feature['dir']. '/templates/*.tpl' ) ?: [] as $file )
			$templateFiles[] = [ 'feature' => $feature, 'name' => basename( $file ), 'file' => $file, 'category' => null, 'owner' => false ];

		foreach( keysFiles( $feature['dir']. '/library', [ 'tpl' ] ) as $file ) {

			$frame = $feature['directory'] === 'Design' && preg_match( '#/library/(header|footer)/v[0-9]+/template\.tpl$#', $file, $part ) === 1 && isset( $frameTarget[1] ) === true
				? str_replace( '%s', $part[1], $frameTarget[1] )
				: null;

			$templateFiles[] = [ 'feature' => $feature, 'name' => ltrim( substr( $file, strlen( $feature['dir'] ) ), '/' ), 'file' => $file, 'category' => $frame, 'owner' => $frame !== null ];
		}
	}


	// --- 5. Every key a template reads --------------------------------------------------

	echo "5. The templates: every key they read exists, in their own category or common\n";

	keysCheck( 'the kernel\'s base unit delivers the words the frames read: /template/frame-header/navigation/label, /template/frame-footer/contact/title, /template/common/navigation/footer',
		isset( $kernelBaseKeys['/template/frame-header/navigation/label'], $kernelBaseKeys['/template/frame-footer/contact/title'], $kernelBaseKeys['/template/common/navigation/footer'] ) === true );
	keysCheck( 'the Design frames were found: headers and footers, each with the category of the template it becomes', count( array_filter( $templateFiles, static fn( array $template ): bool => $template['category'] === 'frame-header' ) ) > 0
		&& count( array_filter( $templateFiles, static fn( array $template ): bool => $template['category'] === 'frame-footer' ) ) > 0 );

	// Where a key of the grammar is delivered: /template/common, /project and the words of the frames by the
	// kernel's base unit, /module by a module's unit, /template/<a category> and /feature/<k> by a feature's
	$delivered = static function( string $key, array $own ) use ( $kernelBaseKeys, $kernelKeys, $featureKeys ): bool {

		[ , $namespace, $category ] = explode( '/', $key );

		return match( true ) {
			$namespace === 'project' || ( $namespace === 'template' && ( $category === 'common' || in_array( $category, [ 'frame-header', 'frame-footer' ], true ) === true ) ) => isset( $kernelBaseKeys[$key] ),
			$namespace === 'module' => isset( $kernelKeys[$key] ),
			$namespace === 'template' => in_array( $key, $own, true ),
			default => isset( $featureKeys[$key] ),
		};
	};

	$unknown 	= [];
	$strangers = [];
	$reads 		= 0;
	foreach( $templateFiles as $template ) {

		$feature = $template['feature'];

		foreach( keysReadBy( (string) file_get_contents( $template['file'] ) ) as $read ) {

			$key = $read['key'];

			// The workbench's own words, which a feature's panel template reads
			if( str_starts_with( $key, '/_admin/' ) === true )
				continue;

			$reads++;

			if( $read['composed'] === true ) {
				if( keysComposedShape( $key ) === false )
					$unknown[] = $template['name']. ' reads the composed key '. str_replace( "\x01", '[[...]]', $key );
				continue;
			}

			if( in_array( $key, $runtimeFills, true ) === true || keysSystem( $key ) === true )
				continue;

			if( $read['image'] === true && keysGrammar( $key ) === false ) {
				if( isset( $imageSlots[$key] ) === false )
					$unknown[] = $template['name']. ' shows the image slot '. $key. ' that no unit declares';
				continue;
			}

			if( keysGrammar( $key ) === false ) {
				$unknown[] = $template['name']. ' reads '. $key;
				continue;
			}

			[ , $namespace, $category ] = explode( '/', $key );

			// Template keys: its own category or common. A template without a category reads the common words only
			if( $namespace === 'template' && $category !== 'common' && $category !== $template['category'] )
				$strangers[] = $template['name']. ' reads '. $key;

			if( $read['image'] === true )
				continue;

			if( $delivered( $key, $feature['keys'] ) === false )
				$unknown[] = $template['name']. ' reads '. $key. ', which nothing it may use delivers';
		}
	}
	keysCheck( 'the templates read keys ('. $reads. ' reads): every one is a runtime fill, a key of the system, a placeholder put together in a shape that is allowed, a slot a unit declares, or a key that the feature, the kernel\'s base unit or a module, or the feature it belongs to delivers'
		. ( $unknown === [] ? '' : ' - '. implode( '; ', array_slice( array_unique( $unknown ), 0, 6 ) ) ), $unknown === [] && $reads > 200 );
	keysCheck( 'a template reads template keys of its own category or of /template/common only - the templates inside a feature and the presets of the Template Builder have no category, so only common'
		. ( $strangers === [] ? '' : ' - '. implode( '; ', array_slice( array_unique( $strangers ), 0, 6 ) ) ), $strangers === [] );

	// The code reads keys too: every one it names in full - not put together, which the feature's own test renders -
	// is a runtime fill, a key of the system or one that is delivered
	$allOwn = [];
	foreach( $features as $feature )
		$allOwn = array_merge( $allOwn, $feature['keys'] );

	$missing = [];
	$fullKeys = 0;
	foreach( $codeLiterals as $literal ) {

		$key = $literal['key'];

		if( keysGrammar( $key ) === false )
			continue;

		$fullKeys++;

		if( $delivered( $key, $allOwn ) === false )
			$missing[] = $literal['file']. ' '. $key;
	}
	keysCheck( 'every key the features\' code names in full ('. $fullKeys. ') is delivered by a unit of a feature, the kernel\'s base unit or a module, or filled at request time'
		. ( $missing === [] ? '' : ' - '. implode( '; ', array_slice( array_unique( $missing ), 0, 8 ) ) ), $missing === [] && $fullKeys > 20 );

	// A feature that reads a key of the kernel's names the Nino that has it in this form: a 1.3 has neither
	// /project, /module, /template/common, the frames' words nor /_nino, so a feature that admits one is wrong
	$kernelReaders = [];
	foreach( $templateFiles as $template )
		foreach( keysReadBy( (string) file_get_contents( $template['file'] ) ) as $read )
			if( preg_match( '#^/(?:project|module|_nino)/#', $read['key'] ) === 1 || preg_match( '#^/template/(?:common|frame-header|frame-footer)/#', $read['key'] ) === 1 )
				$kernelReaders[$template['feature']['directory']][] = $read['key'];
	foreach( $codeLiterals as $literal )
		if( preg_match( '#^/(?:project|module|_nino)/#', $literal['key'] ) === 1 || preg_match( '#^/template/(?:common|frame-header|frame-footer)/#', $literal['key'] ) === 1 )
			$kernelReaders[ explode( '/', $literal['file'] )[1] ][] = $literal['key'];

	$tooOld = [];
	foreach( $kernelReaders as $directory => $readKeys )
		if( \Nino\Features::satisfies( (string) ( (array) include $repo. '/features/'. $directory. '/feature.php' )['nino'], '1.3.2' ) === true )
			$tooOld[] = $directory. ' reads '. $readKeys[0];
	keysCheck( 'a feature that reads a key of the kernel\'s (/project, /module, /template/common, a frame\'s words, /_nino) asks for a Nino that has it: none of the '. count( $kernelReaders ). ' admits 1.3'
		. ( $tooOld === [] ? '' : ' - '. implode( ', ', $tooOld ) ), $tooOld === [] && count( $kernelReaders ) >= 8 );

	// A feature's unit delivers a /template/<category> key only for a template it copies
	$orphans = [];
	foreach( $features as $feature ) {

		if( in_array( $feature['directory'], $applicable, true ) === false )
			continue;

		$categories = array_filter( array_map( 'keysCategoryOf', $feature['templates'] ) );

		foreach( $feature['keys'] as $key )
			if( keysGrammar( $key ) === true && explode( '/', $key )[1] === 'template' && in_array( explode( '/', $key )[2], $categories, true ) === false )
				$orphans[] = $feature['directory']. ' '. $key;
	}
	keysCheck( 'a /template/<category> key of a unit has a template of that category in the unit'. ( $orphans === [] ? '' : ' - '. implode( ', ', array_slice( $orphans, 0, 6 ) ) ), $orphans === [] );

	// What a unit's own texts name inside their values: nested fills
	$unresolved = [];
	foreach( $features as $feature ) {

		if( in_array( $feature['directory'], $applicable, true ) === false )
			continue;

		foreach( [ 'de_DE', 'en_US' ] as $file )
			foreach( $feature['text'][$file] as $key => $value )
				if( is_string( $value ) === true && preg_match_all( '/\[\[([^\[\]]+)\]\]/', $value, $nested ) > 0 )
					foreach( $nested[1] as $inner )
						if( in_array( $inner, $runtimeFills, true ) === false && isset( $kernelBaseKeys[$inner] ) === false && in_array( $inner, $feature['keys'], true ) === false )
							$unresolved[] = $feature['directory']. ' '. $key. ' reads '. $inner;
	}
	keysCheck( 'every fill a value names - /project/website/general/url in a subject - is delivered by the unit, by the base unit or by the kernel'. ( $unresolved === [] ? '' : ' - '. implode( '; ', array_slice( $unresolved, 0, 6 ) ) ), $unresolved === [] );
	echo "\n";


	// --- 6. Categories --------------------------------------------------------------------

	echo "6. The categories: one file name, one template\n";

	// A template a feature copies under the name of one the kernel copies, or another feature does, is that one
	$different = [];
	$byName = [];
	foreach( $templateFiles as $template )
		if( $template['owner'] === true && str_contains( $template['file'], '/install/templates/' ) === true )
			$byName[ $template['name'] ][] = $template['file'];

	foreach( $byName as $name => $files ) {
		$others = array_merge( $files, $kernelTemplates[$name] ?? [] );
		if( count( $others ) > 1 && count( array_unique( array_map( 'md5_file', $others ) ) ) > 1 )
			$different[] = $name;
	}
	keysCheck( 'a template that two units deliver by the same file name - mail-header.tpl and mail-footer.tpl, the kernel\'s and the Newsletter\'s - is one template, byte for byte'
		. ( $different === [] ? '' : ' - '. implode( ', ', $different ) ), $different === [] && isset( $byName['mail-header.tpl'], $kernelTemplates['mail-header.tpl'] ) === true );

	// A template that carries keys of its own is a template with a category, which the kernel's own function says
	$noCategory = [];
	foreach( $templateFiles as $template )
		if( $template['owner'] === true && \Nino\Modules\Template::category( $template['name'] ) !== $template['category'] && str_contains( $template['file'], '/install/templates/' ) === true )
			$noCategory[] = $template['name'];
	keysCheck( 'the category of every template a unit copies is the kernel\'s category(): the file name without .tpl'. ( $noCategory === [] ? '' : ' - '. implode( ', ', $noCategory ) ), $noCategory === [] );

	$withoutCategory = [];
	foreach( $features as $feature )
		foreach( $feature['templates'] as $name )
			if( \Nino\Modules\Template::category( $name ) === null )
				$withoutCategory[] = $feature['directory']. ' '. $name;
	keysCheck( 'every template a unit copies has a category - its name is a word of a key'. ( $withoutCategory === [] ? '' : ' - '. implode( ', ', $withoutCategory ) ), $withoutCategory === [] );
	echo "\n";


	// --- 7. The vocabulary ----------------------------------------------------------------

	echo "7. The words of the keys\n";

	// The workbench words are a list of /_admin/common/word/<slug> of the kernel, where it has one. A part, a name or
	// an identifier it does not know is shown humanised in the Text panel until it takes the word up - worth a line, no failure
	$vocabulary = [];
	foreach( [ 'en_US' ] as $locale )
		foreach( array_keys( keysOfFragment( $root. '/_admin/text/'. $locale. '.php' ) ) as $key )
			if( str_starts_with( $key, '/_admin/common/word/' ) === true )
				$vocabulary[ substr( $key, strlen( '/_admin/common/word/' ) ) ] = true;

	if( $vocabulary === [] )
		echo "note - this Nino has no /_admin/common/word/ vocabulary yet: the words of the keys are not looked up\n";
	else {

		$unlisted = [];
		foreach( $features as $feature ) {

			if( in_array( $feature['directory'], $applicable, true ) === false )
				continue;

			foreach( $feature['keys'] as $key )
				if( keysGrammar( $key ) === true ) {
					[ , , , $part, $name ] = array_merge( explode( '/', $key ), [ '' ] );
					// A part of a list is <list>-<identifier> (unit-day), a name may join words (cta-label): each word is looked up, a number is none
					foreach( [ $part, $name ] as $word )
						if( $word !== '' && isset( $vocabulary[$word] ) === false )
							foreach( explode( '-', $word ) as $piece )
								if( $piece !== '' && ctype_digit( $piece ) === false && isset( $vocabulary[$piece] ) === false )
									$unlisted[$piece][] = $feature['directory'];
				}
		}

		ksort( $unlisted );
		foreach( $unlisted as $word => $in )
			echo "note - '$word' (". implode( ', ', array_unique( $in ) ). ") is no word of the workbench's vocabulary\n";
		keysCheck( 'the vocabulary was read: '. count( $vocabulary ). ' words', count( $vocabulary ) > 50 );
	}
	echo "\n";

	\Nino\Filesystem::removeDir( ninoSandboxDir( $appData ) );
}

echo "$checks checks, $failures failed\n";
exit( $failures > 0 ? 1 : 0 );
