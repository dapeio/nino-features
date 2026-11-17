<?php
declare(strict_types=1);

/**
 *	Nino features
 *	themes-fonts-smoke.php	Contract test for the fonts of the feature Themes - the families of public/fonts/, the signed list of a
 *													repository and the install of one family of it:
 *
 *														A. the pieces: the actions, the files of the panel, the two settings, the limits, the words, what no file does
 *														B. the settings and the state of the repository: off, internal, a key that is none - and a view that asks nobody
 *														C. the list: the signature, what is fetched and how, what is refused as a whole, what is kept and for how long
 *														D. an install: what lands in public/fonts/, what the brand and the stylesheet make of it, an update
 *														E. an install that is refused: the archive, its files, its names, its host, a folder that is somebody else's,
 *														   two at a time - and that nothing of it is left
 *														F. a delete and the licence
 *														G. rights
 *														H. the script of the tab, over a stand-in of the page
 *
 *													The repository is the one of tests/fixtures/repository.php: a key pair of its own, a list and archives that
 *													are made here and handed to the kernel's client through its stub. No request leaves this machine, and the
 *													repository the feature names by default is never asked - the stub fails the run where it is.
 *
 *	Usage: php features/Themes/tests/themes-fonts-smoke.php
 *				 NINO_ROOT=../nino php features/Themes/tests/themes-fonts-smoke.php
 */

$root = getenv( 'NINO_ROOT' ) ?: dirname( __DIR__, 3 );
defined( 'NINO_FEATURES_DIR' ) === true || define( 'NINO_FEATURES_DIR', dirname( __DIR__, 2 ) );
require $root. '/tests/harness.php';
require __DIR__. '/fixtures/repository.php';

use Nino\Modules\Themes\Brand;
use Nino\Modules\Themes\Repository;
use Nino\Modules\Themes\Typefaces;

$featureDir	= dirname( __DIR__ );
$sandboxes	= [];
$numbers		= [];

const FONTS_LIST = 'https://fonts.example.test/webfonts.json';

/**
 *	A site as a sandbox of its own, booted the way \Nino\init() does it: Assets, Basis and the feature, the bundle of the stylesheet, and the
 *	settings of the repository - the list of the fixture and its key, unless the test says otherwise. The request and the lookup of a name are
 *	replaced from the first moment on
 *
 *	@param		array|null	$settings			The settings of the feature as config.php holds them; null for those of the fixture
 *
 *	@return 	array											$appData
 */
function fontsSite( ?array $settings = null ): array {

	global $root, $sandboxes, $keys;

	$appData = ninoSandbox( 'themes-fonts' );
	$sandboxes[] = ninoSandboxDir( $appData );

	// The root of the site is the checkout: the standard parts of Basis are files of the kernel below it
	$appData['./nino/filesystem/path'] = $root;
	$appData['/nino/html/assets'] = [ '/.cache/style.css' => [ '/_nino/Nino.css' ], '/.cache/script.js' => [ '/_nino/Nino.js' ] ];
	$appData['/nino/modules'] = [ '\\Nino\\Modules\\Assets', '\\Nino\\Modules\\Basis', '\\Nino\\Modules\\Themes' ];
	$appData['/nino/features'] = [ 'themes' => [ 'version' => '1.0.0', 'settings' => $settings ?? [ 'fontsUrl' => FONTS_LIST, 'fontsKey' => $keys[2] ] ] ];

	// The themes of tests/fixtures/themes are themes of the site: Craft (probe) suggests a font
	\Nino\Callbacks::registerCallback( $appData, \Nino\Modules\Themes\Reader::ROOTS, static fn( array &$appData, array $roots ): array => array_merge( $roots, [ __DIR__. '/fixtures/themes' ] ) );

	\Nino\Html::init( $appData );
	\Nino\Features::registerComponents( $appData );
	\Nino\Features::registerParts( $appData );
	\Nino\Features::registerAssets( $appData );
	\Nino\Modules::callModules( $appData, 'init' );

	// Nothing of a run reaches a network or a name server: every request and every lookup is answered here, until a test serves something
	fontsServe( $appData, [] );

	return $appData;
}

/**
 *	The repository as the kernel's client sees it: what each address answers, and every request that was made. An address that is not
 *	served answers 404, as a server does; the answer of an address may be a callable that is handed the options. An answer over the
 *	limit of the request is cut off the way the client cuts it - no body, and the sentence of the limit. The repository the feature
 *	names by default is never served and fails the run where it is asked.
 *
 *	@param		array 				&$appData			(reference) A site
 *	@param		array					$serves				url => bytes, or a callable ( options ) => answer
 *
 *	@return 	ArrayObject										The requests: [ url, options ], in the order they were made
 */
function fontsServe( array &$appData, array $serves ): ArrayObject {

	$asked = new ArrayObject();

	$appData['./nino/fetch/stub'] = static function( string $url, array $options ) use ( $serves, $asked ): array {

		$asked[] = [ $url, $options ];

		if( str_contains( $url, 'getnino.dev' ) === true ) {
			fwrite( STDERR, "the test asked the repository of the feature: ". $url. "\n" );
			exit( 3 );
		}

		if( isset( $serves[$url] ) === false )
			return [ 'ok' => false, 'status' => 404, 'body' => '', 'error' => 'http 404' ];

		if( is_callable( $serves[$url] ) === true )
			return $serves[$url]( $options );

		if( strlen( $serves[$url] ) > $options['maxBytes'] )
			return [ 'ok' => false, 'status' => 200, 'body' => '', 'error' => 'the answer exceeds '. $options['maxBytes']. ' bytes' ];

		return [ 'ok' => true, 'status' => 200, 'body' => $serves[$url], 'error' => '' ];
	};

	// No name is looked up on this machine either: a name is public unless the test says where it leads
	if( isset( $appData['./nino/fetch/resolve'] ) === false )
		$appData['./nino/fetch/resolve'] = static fn( string $host ): array => [ '93.184.216.34' ];

	return $asked;
}

/**
 *	What a repository serves for a list of families: the list, its signature and every archive
 *
 *	@param		array			$families			As fontsFamily() makes them
 *	@param		array			$extra				Fields of the document that say another thing
 *
 *	@return 	array											url => bytes
 */
function fontsRepository( array $families, array $extra = [] ): array {

	global $keys;

	$json = fontsList( array_column( $families, 'entry' ), $extra );
	$serves = [ FONTS_LIST => $json, FONTS_LIST. '.sig' => fontsSign( $json, $keys[0] ) ];

	foreach( $families as $family )
		$serves[$family['entry']['archive']] = $family['archive'];

	return $serves;
}

/**
 *	One action of the API, as the workbench posts it
 *
 *	@param		array 		&$appData			(reference) A site
 *	@param		string		$action				eg. 'themes/fonts-install'
 *	@param		array			$data
 *
 *	@return 	array											[ status, body ]
 */
function fontsCall( array &$appData, string $action, array $data = [] ): array {

	[ $status, $body ] = ninoCall( $appData, [ \Nino\Admin\Admin::class, 'handlePost' ], $data, $action );

	return [ $status, $body ];
}

/**
 *	Every file below a folder with its bytes and its mode, and every link: what a folder is, to say that it did not change
 *
 *	@param		string		$dir
 *
 *	@return 	string
 */
function fontsDigest( string $dir ): string {

	if( is_dir( $dir ) === false )
		return '-';

	$all = [];

	foreach( new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $dir, FilesystemIterator::SKIP_DOTS ), RecursiveIteratorIterator::SELF_FIRST ) as $file ) {
		$path = (string) $file->getPathname();
		$all[] = substr( $path, strlen( $dir ) ). '|'. ( is_link( $path ) === true ? 'link' : ( is_dir( $path ) === true ? 'dir' : hash_file( 'sha256', $path ). '|'. substr( sprintf( '%o', fileperms( $path ) ), -4 ) ) );
	}

	sort( $all );

	return hash( 'sha256', implode( "\n", $all ) );
}

/**
 *	What an install or a delete left of its work: a folder of public/fonts/ whose name begins with a dot, and anything below data/ but the list
 *	that is kept and the lock. Nothing, where every one cleaned up after itself
 *
 *	@param		array 		&$appData			(reference) A site
 *
 *	@return 	array
 */
function fontsWork( array &$appData ): array {

	$left = [];
	$fonts = \Nino\Filesystem::path( $appData, '/fonts' );
	$data = \Nino\Filesystem::path( $appData, dirname( Typefaces::LOCK ) );

	foreach( is_dir( $fonts ) === true ? (array) scandir( $fonts ) : [] as $name )
		if( in_array( $name, [ '.', '..' ], true ) === false && str_starts_with( (string) $name, '.' ) === true )
			$left[] = 'public/fonts/'. $name;

	foreach( array_diff( is_dir( $data ) === true ? (array) scandir( $data ) : [], [ '.', '..', 'list.json', 'install.lock' ] ) as $name )
		$left[] = 'data/'. $name;

	return $left;
}

// A run that ends in the middle - an error of php, a pin that kills it - leaves no sandbox behind either
register_shutdown_function( static function() use ( &$sandboxes ): void {

	foreach( $sandboxes as $sandbox ) {

		if( is_dir( $sandbox ) === false )
			continue;

		foreach( new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $sandbox, FilesystemIterator::SKIP_DOTS ), RecursiveIteratorIterator::CHILD_FIRST ) as $file )
			if( is_link( (string) $file->getPathname() ) === true )
				unlink( (string) $file->getPathname() );

		\Nino\Filesystem::removeDir( $sandbox );
	}
} );

$keys = fontsKeys();
$fontsOf = static fn( array &$appData ): string => \Nino\Filesystem::path( $appData, '/fonts' );

// ------------------------------------------------------------------------------------------------------------------------------------
echo "A. The pieces\n";

$site = fontsSite();
ninoUser( $site, 'designer@example.com' );

$actions = array_keys( Typefaces::actions() );
check( 'A. the fonts have five actions of their own beside the eleven of the themes, each is one the workbench dispatches and each is a method of Typefaces',
	$actions === [ 'themes/fonts', 'themes/fonts-load', 'themes/fonts-install', 'themes/fonts-remove', 'themes/fonts-license' ] && count( \Nino\Modules\Themes\Admin::actions() ) === 11 && array_intersect( $actions, array_keys( \Nino\Modules\Themes\Admin::actions() ) ) === []
	&& array_diff( $actions, array_keys( \Nino\Admin\Admin::actions( $site ) ) ) === [] && array_filter( Typefaces::actions(), static fn( array $target ): bool => is_callable( $target ) === false ) === [] );

$panels = \Nino\Admin\Admin::panels( $site );
$tabs = $panels['themes']['tabs'] ?? [];
check( 'A. the pane of the themes has three tabs and one strip, the kernel\'s: the panel\'s own screen, the fonts - a tab the panel brings, under the right of the panel - and last the settings, which the registry attaches for the two settings of the manifest under the right of the features',
	\Nino\Modules\Themes\Admin::tabs() === [ Typefaces::class ] && array_keys( $tabs ) === [ 'themes-fonts', 'themes-settings' ] && $tabs['themes-fonts']['class'] === Typefaces::class && $tabs['themes-fonts']['perm'] === '/_admin/themes/manage' && $tabs['themes-fonts']['parent'] === 'themes'
	&& $tabs['themes-fonts']['panes'] === [ 'themes-fonts-list' ] && $tabs['themes-fonts']['tab'] === '/_admin/themes/fonts/tab/fonts' && $tabs['themes-settings']['class'] === \Nino\Modules\Features\Settings::class && $tabs['themes-settings']['perm'] === '/_admin/features/manage'
	&& $tabs['themes-settings']['feature'] === 'themes' && $panels['themes']['own'] === true );

$assets = Typefaces::assets();
$script = (string) file_get_contents( $featureDir. '/assets/admin-fonts.js' );
$sheet = (string) file_get_contents( $featureDir. '/assets/admin-fonts.css' );
check( 'A. the tab names two files, its script and its stylesheet, both files of the feature, and the panel names its own two as before',
	count( $assets ) === 2 && str_ends_with( $assets[0], '/features/Themes/assets/admin-fonts.js' ) === true && str_ends_with( $assets[1], '/features/Themes/assets/admin-fonts.css' ) === true && $script !== '' && $sheet !== '' && count( \Nino\Modules\Themes\Admin::assets() ) === 2
	&& $tabs['themes-fonts']['assets'] === $assets && str_contains( $script, "const MOUNT = 'themes-fonts-list';" ) === true && str_contains( $script, "wn.Nino.admin['themes-fonts'] = {" ) === true );
check( 'A. the script of the tab keeps to the CSP of the workbench: nothing is built from a string, nothing is written as markup, no style is set - and it makes no request of its own, to no address',
	preg_match( '/\beval\s*\(|new\s+Function|setTimeout\s*\(\s*[\'"]|insertAdjacentHTML|document\.write|\.outerHTML\s*=/', $script ) === 0 && preg_match( '/innerHTML\s*=\s*[^\'\s]/', $script ) === 0 && preg_match( '/\.style\b|setAttribute\(\s*[\'"]style/', $script ) === 0
	&& preg_match( '/\bfetch\s*\(|XMLHttpRequest|sendBeacon|WebSocket|EventSource|https?:\/\/(?!github\.com\/dapeio)/', $script ) === 0 );

preg_match_all( '/^([^\s@}\/][^{]*)\{/m', $sheet, $rules );
check( 'A. the stylesheet of the tab is scoped to its mount: every one of its '. count( $rules[1] ). ' rules begins with the id of the mount, and it loads nothing',
	count( $rules[1] ) > 10 && array_filter( $rules[1], static fn( string $selector ): bool => str_starts_with( trim( $selector ), '#themes-fonts-list ' ) === false ) === [] && preg_match( '/url\s*\(|@import/', $sheet ) === 0 );

$manifest = \Nino\Features::get( $site, 'themes' );
$settings = ( require $featureDir. '/feature.php' )['settings'];
check( 'A. the manifest declares the two settings of the repository and no other: an address, whose default is the list of the feature, and a text for the key, empty by default - both with a label and a hint in two languages',
	is_array( $manifest ) === true && $manifest['problems'] === [] && array_keys( $settings ) === [ 'fontsUrl', 'fontsKey' ] && $settings['fontsUrl']['type'] === 'url' && $settings['fontsUrl']['default'] === Repository::DEFAULT_URL
	&& Repository::DEFAULT_URL === 'https://webfonts.getnino.dev/webfonts.json' && $settings['fontsKey']['type'] === 'text' && $settings['fontsKey']['default'] === ''
	&& array_keys( $settings['fontsUrl']['label'] ) === [ 'en_US', 'de_DE' ] && array_keys( $settings['fontsUrl']['hint'] ) === [ 'en_US', 'de_DE' ] && array_keys( $settings['fontsKey']['label'] ) === [ 'en_US', 'de_DE' ] && array_keys( $settings['fontsKey']['hint'] ) === [ 'en_US', 'de_DE' ] );

$constant = static fn( string $class, string $name ): mixed => ( new ReflectionClassConstant( $class, $name ) )->getValue();
check( 'A. the limits are what they are written as: a list of a megabyte, a signature of 4096 bytes, an archive of ten megabytes, a file of four, a family of sixteen, twelve faces, two thousand families, fifteen and sixty seconds, a day, an hour for what a killed request left',
	$constant( Repository::class, 'MAX_LIST_BYTES' ) === 1048576 && $constant( Repository::class, 'MAX_SIGNATURE_BYTES' ) === 4096 && $constant( Repository::class, 'MAX_ARCHIVE_BYTES' ) === 10485760 && $constant( Repository::class, 'MAX_FILE_BYTES' ) === 4194304
	&& $constant( Repository::class, 'MAX_FAMILY_BYTES' ) === 16777216 && $constant( Repository::class, 'MAX_FACES' ) === 12 && $constant( Repository::class, 'MAX_FAMILIES' ) === 2000 && $constant( Repository::class, 'TIMEOUT_LIST' ) === 15
	&& $constant( Repository::class, 'TIMEOUT_ARCHIVE' ) === 60 && $constant( Repository::class, 'FRESH' ) === 86400 && $constant( Repository::class, 'FORMAT' ) === 1 && $constant( Typefaces::class, 'MAX_LICENSE_BYTES' ) === 65536 && $constant( Typefaces::class, 'WORK_AGE' ) === 3600 );
check( 'A. what the repository takes for a name the brand can use is what the brand takes: the pattern of a family, the length of a folder and the number of faces are those of Brand',
	$constant( Repository::class, 'BRAND_FAMILY' ) === $constant( Brand::class, 'FAMILY' ) && $constant( Repository::class, 'BRAND_SLUG_LENGTH' ) === $constant( Brand::class, 'SLUG_LENGTH' ) && $constant( Repository::class, 'MAX_FACES' ) === $constant( Brand::class, 'FACES' ) );
check( 'A. the key of the feature is a public key that openssl reads, of the curve the scheme signs with; the list and the lock are below data/ in a folder whose name begins with a dot, the work of an install is a folder of public/fonts/ whose name begins with one, and the licence of a family is a file with the ending of a text',
	is_array( $details = openssl_pkey_get_details( openssl_pkey_get_public( Repository::PUBLIC_KEY ) ) ) === true && ( $details['ec']['curve_name'] ?? '' ) === 'prime256v1' && Typefaces::WORK === '.work-' && Repository::CACHE === '/data/theme/.fonts/list.json' && Typefaces::LOCK === '/data/theme/.fonts/install.lock' && Typefaces::LICENSE === 'LICENSE.txt' );

// what no file of the two classes does
$sources = [];

foreach( [ 'Repository/Repository.php', 'Typefaces/Typefaces.php' ] as $file )
	$sources[$file] = (string) file_get_contents( $featureDir. '/'. $file );

$code = implode( "\n", $sources );
check( 'A. the two classes ask the network through the client of the kernel and through nothing else, run no program, read no archive with a class that writes it to a disk, and name nothing that only one C library has',
	substr_count( $code, '\\Nino\\Fetch::get(' ) === 3 && preg_match( '/\bcurl_|fsockopen|stream_socket|stream_context|allow_url|\bexec\s*\(|shell_exec|proc_open|passthru|popen\s*\(|\bsystem\s*\(|PharData|ZipArchive|\beval\s*\(|unserialize|\binclude\b|\brequire\b/', $code ) === 0
	&& preg_match( '/GLOB_'. 'BRACE|strptime|setlocale|TRANSLIT|\bglob\s*\(/', $code ) === 0 && preg_match( '/file_get_contents\(\s*[\'"]https?:/', $code ) === 0 );

$words = [ 'en_US' => \Nino\Admin\Admin::textFills( $site, '/features/Themes/text', 'en_US' ), 'de_DE' => \Nino\Admin\Admin::textFills( $site, '/features/Themes/text', 'de_DE' ) ];
preg_match_all( '/\'(themes_fonts_[a-z_]+)\'/', $code, $codes );
preg_match_all( '/word\(\s*\'([a-z-]+\/[a-z-]+)\'\s*[,)]/', $script, $said );
$codes = array_values( array_unique( array_merge( $codes[1], array_map( static fn( string $kind ): string => 'themes_fonts_'. $kind, Repository::REFUSALS ) ) ) );
$missing = [];

foreach( $words as $locale => $fills ) {

	foreach( $codes as $failure )
		if( trim( (string) ( $fills['[[/_admin/error/'. $failure. ']]'] ?? '' ) ) === '' )
			$missing[] = $locale. ' '. $failure;

	foreach( array_unique( $said[1] ) as $key )
		if( trim( (string) ( $fills['[[/_admin/themes/fonts/'. $key. ']]'] ?? '' ) ) === '' )
			$missing[] = $locale. ' '. $key;

	// The words the script puts together: a state of the repository, a category, a role of the brand, why a family cannot be installed
	foreach( array_merge( array_map( static fn( string $kind ): string => 'available/'. $kind, array_diff( Repository::REFUSALS, [ 'unreachable', 'signature', 'parse' ] ) ), array_map( static fn( string $category ): string => 'category/'. $category, Repository::CATEGORIES ),
		[ 'role/font-title', 'role/font-text', 'role/font-button', 'available/problem-family', 'available/problem-system', 'tab/fonts' ] ) as $key )
		if( trim( (string) ( $fills['[[/_admin/themes/fonts/'. $key. ']]'] ?? '' ) ) === '' )
			$missing[] = $locale. ' '. $key;
}

// No word waits for a reader that is gone: each is asked for by the script, put together by it, the word of the tab, or the sentence of a failure
preg_match_all( '#/_admin/themes/fonts/([a-z-]+/[a-z-]+)#', $script, $named );
$read = array_merge( $said[1], $named[1], array_map( static fn( string $kind ): string => 'available/'. $kind, Repository::REFUSALS ), array_map( static fn( string $category ): string => 'category/'. $category, Repository::CATEGORIES ), [ 'role/font-title', 'role/font-text', 'role/font-button', 'available/problem-family', 'available/problem-system', 'tab/fonts', 'msg/updated', 'msg/updated-used', 'available/from', 'available/from-own' ] );
$unread = [];

foreach( array_keys( $words['en_US'] ) as $key )
	if( preg_match( '#^\[\[/_admin/themes/fonts/(.+)\]\]$#', $key, $part ) === 1 && in_array( $part[1], $read, true ) === false )
		$unread[] = $part[1];

foreach( array_keys( $words['en_US'] ) as $key )
	if( preg_match( '#^\[\[/_admin/error/(themes_fonts_.+)\]\]$#', $key, $part ) === 1 && in_array( $part[1], $codes, true ) === false )
		$unread[] = $part[1];

check( 'A. no word of the fonts is left without a reader: each is asked for by the script or is the sentence of a failure the classes name'. ( $unread === [] ? '' : ' - '. implode( ', ', $unread ) ), $unread === [] );

$fontKeys = static fn( array $fills ): array => array_values( array_filter( array_keys( $fills ), static fn( string $key ): bool => str_contains( $key, '/themes/fonts/' ) === true || str_contains( $key, 'themes_fonts_' ) === true ) );
check( 'A. every failure the two classes name ('. count( $codes ). ') and every word the script says ('. count( array_unique( $said[1] ) ). ') has a sentence in both languages, and the two languages have the same '. count( $fontKeys( $words['en_US'] ) ). ' keys'. ( $missing === [] ? '' : ' - '. implode( ', ', $missing ) ),
	$missing === [] && count( $codes ) >= 18 && count( array_unique( $said[1] ) ) > 40 && $fontKeys( $words['en_US'] ) === $fontKeys( $words['de_DE'] ) && count( $fontKeys( $words['en_US'] ) ) > 80 );

check( 'A. the log names the three actions that change something and shows the slug as a slug, whatever was posted',
	Typefaces::log( 'themes/fonts-load', [] ) === 'Load Font List' && Typefaces::log( 'themes/fonts-install', [ 'slug' => 'open-sans' ] ) === 'Install Font open-sans'
	&& Typefaces::log( 'themes/fonts-remove', [ 'slug' => "a\n<b>". str_repeat( 'x', 200 ) ] ) === 'Delete Font a??b?'. str_repeat( 'x', 55 ) && Typefaces::log( 'themes/fonts', [] ) === '' && Typefaces::log( 'themes/fonts-license', [] ) === '' );

// ------------------------------------------------------------------------------------------------------------------------------------
echo "\nB. The settings and the state of the repository\n";

$open = fontsFamily( 'open-sans', 'Open Sans', [ [ 400, 'normal' ], [ 700, 'normal' ], [ 400, 'italic' ] ], [ 'version' => '1.2' ] );
$serif = fontsFamily( 'fraunces', 'Fraunces', [ [ '100 900', 'normal' ] ], [ 'category' => 'serif', 'version' => '2.0' ] );
$other = fontsKeys();
$fixture = [ 'fontsUrl' => FONTS_LIST, 'fontsKey' => $keys[2] ];

/**
 *	The site with other settings and another lookup, shown and asked to load: the state the view says, the answer of the load, and whom the two
 *	asked - the repository and the lookup
 */
$stateOf = static function( array $settings, ?callable $resolve = null ) use ( &$site, $fixture ): array {

	$looked = new ArrayObject();

	$site['/nino/features']['themes']['settings'] = $settings;
	$site['./nino/fetch/resolve'] = static function( string $host ) use ( $looked, $resolve ): array|false {
		$looked[] = $host;
		return $resolve === null ? [ '93.184.216.34' ] : $resolve( $host );
	};

	$asked = fontsServe( $site, [] );
	[ $status, $view ] = fontsCall( $site, 'themes/fonts' );
	$viewAsked = count( $asked ) + count( $looked );
	// The repository of the feature itself is shown and never asked
	[ $loadStatus, $load ] = $settings === [] ? [ 0, [] ] : fontsCall( $site, 'themes/fonts-load' );

	$site['/nino/features']['themes']['settings'] = $fixture;
	unset( $site['./nino/fetch/resolve'] );
	fontsServe( $site, [] );

	return [ 'status' => $status, 'state' => $view['repository']['state'] ?? '', 'host' => $view['repository']['host'] ?? '', 'ownKey' => $view['repository']['ownKey'] ?? null, 'viewAsked' => $viewAsked, 'load' => $loadStatus, 'code' => $load['code'] ?? '', 'asked' => array_column( (array) $asked, 0 ), 'looked' => (array) $looked ];
};

$answer = $stateOf( [] );
check( 'B. with no setting saved the repository is the one of the feature with the key of the feature, it can be asked - and showing the view asks nobody: no request, no name looked up, no file written',
	$answer['status'] === 200 && $answer['state'] === 'ready' && $answer['host'] === 'webfonts.getnino.dev' && $answer['ownKey'] === false && $answer['viewAsked'] === 0 && Repository::DEFAULT_URL === 'https://webfonts.getnino.dev/webfonts.json'
	&& is_dir( \Nino\Filesystem::path( $site, dirname( Typefaces::LOCK ) ) ) === false && is_dir( $fontsOf( $site ) ) === false );

$empty = $stateOf( [ 'fontsUrl' => '', 'fontsKey' => $keys[2] ] );
$plainHttp = $stateOf( [ 'fontsUrl' => 'http://fonts.example.test/webfonts.json', 'fontsKey' => $keys[2] ] );
check( 'B. an address that is empty or no https address switches the repository off: the view says so, a load answers 409 and nothing is asked',
	[ $empty['state'], $empty['load'], $empty['code'], $empty['asked'], $empty['looked'] ] === [ 'off', 409, 'themes_fonts_off', [], [] ] && [ $plainHttp['state'], $plainHttp['code'], $plainHttp['asked'], $plainHttp['looked'] ] === [ 'off', 'themes_fonts_off', [], [] ] );

// the settings as the form of the kernel saves them: a site of its own, whose config.php is written
$form = fontsSite( [] );
$saved = \Nino\Features::saveSettings( $form, 'themes', [ 'fontsUrl' => ' https://fonts.example.test/webfonts.json ', 'fontsKey' => "\n". $keys[2]. "\n" ] );
$kept = [ Repository::url( $form ), Repository::key( $form ) ];
$refused = \Nino\Features::saveSettings( $form, 'themes', [ 'fontsUrl' => 'javascript:alert(1)' ] );
check( 'B. the two settings are saved by the kernel in the form of the manifest: an address and a key are kept trimmed and are what the repository reads, what is no address is refused by name and the setting stays, and an empty address is kept and switches the repository off',
	$saved === [] && $kept === [ FONTS_LIST, trim( $keys[2] ) ] && isset( $refused['fontsUrl'] ) === true && Repository::url( $form ) === FONTS_LIST && \Nino\Features::saveSettings( $form, 'themes', [ 'fontsUrl' => '', 'fontsKey' => '' ] ) === []
	&& Repository::state( $form ) === 'off' && Repository::key( $form ) === Repository::PUBLIC_KEY );

// the host: by its spelling, which is what the view goes by
$spelled = [];

foreach( [ 'https://127.0.0.1/x', 'https://[::1]/x', 'https://2130706433/x', 'https://0x7f000001/x', 'https://8.8.8.8/x', 'https://localhost/x', 'https://printer/x', 'https://nas.local/x', 'https://fonts.internal/x', 'https://-bad.example.com/x', 'https://example.123/x', 'http://fonts.example.test/x' ] as $url )
	$spelled[$url] = \Nino\Fetch::isPublic( $site, $url, false ) === false && \Nino\Fetch::isPublic( $site, $url ) === false;

$literal = $stateOf( [ 'fontsUrl' => 'https://169.254.169.254/latest/meta-data', 'fontsKey' => $keys[2] ] );
check( 'B. an address in place of a name - dotted, whole, hexadecimal, IPv6, a public one as well - the name of a machine without a dot and a name of a local network are no host that is asked: said by the view without a lookup, refused by a load with no request and no lookup'. ( array_unique( array_values( $spelled ) ) === [ true ] ? '' : ' - NOT: '. implode( ', ', array_keys( array_filter( $spelled, static fn( bool $ok ): bool => $ok === false ) ) ) ),
	count( $spelled ) === 12 && array_unique( array_values( $spelled ) ) === [ true ] && \Nino\Fetch::isPublic( $site, FONTS_LIST, false ) === true && [ $literal['state'], $literal['load'], $literal['code'], $literal['asked'], $literal['looked'] ] === [ 'internal', 409, 'themes_fonts_internal', [], [] ] );

// the host: by what the lookup answers, where a request follows
$leads = [];

foreach( [ '127.0.0.1', '10.1.2.3', '192.168.0.1', '169.254.169.254', '100.64.0.1', '::1', 'fd12::1', 'fe80::1', '::ffff:8.8.8.8', '::93.184.216.34', '64:ff9b::a00:1', 'no address', 42 ] as $address ) {
	$site['./nino/fetch/resolve'] = static fn( string $host ): array => [ '93.184.216.34', $address ];
	$leads[(string) $address] = \Nino\Fetch::isPublic( $site, FONTS_LIST ) === false;
}

$reaches = [];

foreach( [ '93.184.216.34', '2606:2800:220:1:248:1893:25c8:1946', '64:ff9b::5db8:d822' ] as $address ) {
	$site['./nino/fetch/resolve'] = static fn( string $host ): array => [ $address ];
	$reaches[$address] = \Nino\Fetch::isPublic( $site, FONTS_LIST ) === true;
}

$internal = $stateOf( $fixture, static fn( string $host ): array => [ '93.184.216.34', '10.0.0.8' ] );
$nothing = $stateOf( $fixture, static fn( string $host ): bool => false );
$nobody = $stateOf( $fixture, static fn( string $host ): array => [] );
$public = $stateOf( $fixture );
check( 'B. a name is asked where every address the lookup gives it is a public one - IPv4, IPv6, a public IPv4 one behind the prefix of a NAT64 gateway - and not where one of them is loopback, private, link-local, of a carrier, an IPv4 address written as IPv6 or no address at all: the load is refused before any request'. ( array_unique( array_merge( array_values( $leads ), array_values( $reaches ) ) ) === [ true ] ? '' : ' - NOT: '. implode( ', ', array_keys( array_filter( $leads + $reaches, static fn( bool $ok ): bool => $ok === false ) ) ) ),
	count( $leads ) === 13 && count( $reaches ) === 3 && array_unique( array_merge( array_values( $leads ), array_values( $reaches ) ) ) === [ true ]
	&& [ $internal['state'], $internal['viewAsked'], $internal['load'], $internal['code'], $internal['asked'], $internal['looked'] ] === [ 'ready', 0, 409, 'themes_fonts_internal', [], [ 'fonts.example.test' ] ]
	&& [ $public['code'], $public['asked'], $public['looked'] ] === [ 'themes_fonts_unreachable', [ FONTS_LIST ], [ 'fonts.example.test' ] ] );
check( 'B. a name the lookup answers nothing for is not asked: no answer is no leave, whether the lookup says false or names no address', [ $nothing['load'], $nothing['code'], $nothing['asked'] ] === [ 409, 'themes_fonts_internal', [] ] && [ $nobody['load'], $nobody['code'], $nobody['asked'] ] === [ 409, 'themes_fonts_internal', [] ] );

file_put_contents( ninoSandboxDir( $site ). '/key.pem', $keys[2] );
$bad = [];

foreach( [ 'words' => 'not a key', 'a private key' => $keys[1], 'a cut key' => substr( $keys[2], 0, 80 ), 'the path of a file of the server that holds a key' => 'file://'. ninoSandboxDir( $site ). '/key.pem' ] as $label => $key ) {
	$site['/nino/features']['themes']['settings'] = [ 'fontsUrl' => FONTS_LIST, 'fontsKey' => $key ];
	$bad[$label] = Repository::state( $site );
}

$words = $stateOf( [ 'fontsUrl' => FONTS_LIST, 'fontsKey' => 'not a key' ] );
$own = fontsCall( $site, 'themes/fonts' )[1];
check( 'B. a key that is no public key in PEM is said by the view and refuses a load with 409 and no request - words, a private key, a cut key, the path of a file - and a key of one\'s own is said to be one, with the host of the repository: neither the key nor the address is sent to the panel',
	array_unique( array_values( $bad ) ) === [ 'badkey' ] && count( $bad ) === 4 && [ $words['state'], $words['load'], $words['code'], $words['asked'] ] === [ 'badkey', 409, 'themes_fonts_badkey', [] ]
	&& $own['repository']['state'] === 'ready' && $own['repository']['ownKey'] === true && $own['repository']['host'] === 'fonts.example.test' && str_contains( json_encode( $own ), 'BEGIN PUBLIC KEY' ) === false && str_contains( json_encode( $own ), 'webfonts.json' ) === false );

// ------------------------------------------------------------------------------------------------------------------------------------
echo "\nC. The list\n";

$serves = fontsRepository( [ $open, $serif ] );
$asked = fontsServe( $site, $serves );
[ $status, $body ] = fontsCall( $site, 'themes/fonts-load' );
$cacheFile = \Nino\Filesystem::path( $site, Repository::CACHE );
check( 'C. a list whose signature holds is loaded: two requests and no more - the list, then its signature at .sig - each with its limit of bytes and of time, and the answer lists the families',
	$status === 200 && $body['loaded'] === 2 && (array) $asked === [ [ FONTS_LIST, [ 'timeout' => 15, 'maxBytes' => 1048576, 'addresses' => [ '93.184.216.34' ] ] ], [ FONTS_LIST. '.sig', [ 'timeout' => 15, 'maxBytes' => 4096, 'addresses' => [ '93.184.216.34' ] ] ] ]
	&& array_column( $body['available']['families'], 'slug' ) === [ 'open-sans', 'fraunces' ] && $body['available']['stale'] === false );
check( 'C. the panel is told of a family what a person chooses by and not where its archive is nor its digest, and the list is kept below data/ for the view that follows, which asks nobody',
	array_keys( $body['available']['families'][0] ) === [ 'slug', 'family', 'category', 'version', 'license', 'source', 'weights', 'styles', 'size', 'problem', 'installed', 'foreign' ] && $body['available']['families'][1]['weights'] === [ '100 900' ]
	&& str_contains( json_encode( $body ), '.tar.gz' ) === false && str_contains( json_encode( $body ), $open['entry']['sha256'] ) === false && str_contains( json_encode( $body ), ninoSandboxDir( $site ) ) === false
	&& fontsCall( $site, 'themes/fonts' )[1]['available']['families'][0]['family'] === 'Open Sans' && count( $asked ) === 2 );

$stored = \Nino\Filesystem::getFileContent( $site, Repository::CACHE, null );
$kept = (string) file_get_contents( $cacheFile );

/**
 *	A load of a repository that serves something else, on the site that holds the good list: its answer, what was asked, and whether the list
 *	that was kept is still the one of before
 */
$loadOf = static function( array $serves ) use ( &$site, $cacheFile, $kept ): array {

	$asked = fontsServe( $site, $serves );
	[ $status, $body ] = fontsCall( $site, 'themes/fonts-load' );

	return [ $status, $body['code'] ?? '', array_column( (array) $asked, 0 ), (string) file_get_contents( $cacheFile ) === $kept ];
};

$json = $serves[FONTS_LIST];
$signature = [
	'another key'			=> $loadOf( [ FONTS_LIST => $json, FONTS_LIST. '.sig' => fontsSign( $json, $other[0] ) ] ),
	'a byte changed'	=> $loadOf( [ FONTS_LIST => str_replace( 'Open Sans', 'Open Sanz', $json ), FONTS_LIST. '.sig' => $serves[FONTS_LIST. '.sig'] ] ),
	'words'						=> $loadOf( [ FONTS_LIST => $json, FONTS_LIST. '.sig' => 'signed, honestly' ] ),
];
check( 'C. a list whose signature does not hold is not believed, 502 with its own sentence, and the list that was kept stays: '. implode( ', ', array_keys( $signature ) ),
	array_unique( array_map( static fn( array $answer ): string => json_encode( [ $answer[0], $answer[1], $answer[3] ] ), array_values( $signature ) ) ) === [ json_encode( [ 502, 'themes_fonts_signature', true ] ) ] );
check( 'C. a list without a signature is not read at all: there is no unsigned way in', $loadOf( [ FONTS_LIST => $json ] ) === [ 502, 'themes_fonts_unreachable', [ FONTS_LIST, FONTS_LIST. '.sig' ], true ] );

$redirect = $loadOf( [ FONTS_LIST => static fn( array $options ): array => [ 'ok' => false, 'status' => 302, 'body' => '', 'error' => 'http 302' ], 'https://elsewhere.example.test/webfonts.json' => $json, 'https://elsewhere.example.test/webfonts.json.sig' => $serves[FONTS_LIST. '.sig'] ] );
$large = $loadOf( [ FONTS_LIST => $json. str_repeat( ' ', 1048577 - strlen( $json ) ), FONTS_LIST. '.sig' => $serves[FONTS_LIST. '.sig'] ] );
$kernelClient = (string) file_get_contents( $root. '/_nino/Nino/Fetch/Fetch.php' );
check( 'C. a list that answers with a redirect is not followed to the other host, and one of more than a megabyte is not read: one request, 502, the kept list stays - and the client of the kernel, which every request goes through, follows no redirect and fetches https alone',
	$redirect === [ 502, 'themes_fonts_unreachable', [ FONTS_LIST ], true ] && $large === [ 502, 'themes_fonts_unreachable', [ FONTS_LIST ], true ] && str_contains( $kernelClient, 'CURLOPT_FOLLOWLOCATION		=> false' ) === true && str_contains( $kernelClient, "'follow_location'	=> 0" ) === true
	&& \Nino\Fetch::get( $site, 'http://fonts.example.test/webfonts.json' )['ok'] === false );

// what format 1 says, by the reader itself - and once through the load
$entry = $open['entry'];
$files = static fn( string $name, int|string $weight = 400, string $style = 'normal' ): array => [ 'files' => [ [ 'weight' => $weight, 'style' => $style, 'file' => $name ] ], 'weights' => [ $weight ], 'styles' => [ $style ] ];
$parsed = [
	'another format'							=> Repository::parse( fontsList( [ $entry ], [ 'format' => 2 ] ) ),
	'a slug that climbs'					=> Repository::parse( fontsList( [ array_replace( $entry, [ 'slug' => '../open-sans' ] ) ] ) ),
	'a slug with a null byte'			=> Repository::parse( fontsList( [ array_replace( $entry, [ 'slug' => "open\0sans" ] ) ] ) ),
	'a family with a quote'				=> Repository::parse( fontsList( [ array_replace( $entry, [ 'family' => 'Open"; } body { display: none' ] ) ] ) ),
	'an archive over http'				=> Repository::parse( fontsList( [ array_replace( $entry, [ 'archive' => 'http://fonts.example.test/a.tar.gz' ] ) ] ) ),
	'a digest that is short'			=> Repository::parse( fontsList( [ array_replace( $entry, [ 'sha256' => 'abc' ] ) ] ) ),
	'a size over the limit'				=> Repository::parse( fontsList( [ array_replace( $entry, [ 'size' => 10485761 ] ) ] ) ),
	'a file that climbs'					=> Repository::parse( fontsList( [ array_replace( $entry, $files( '../../index.php' ) ) ] ) ),
	'a file that is a script'			=> Repository::parse( fontsList( [ array_replace( $entry, $files( '400.php' ) ) ] ) ),
	'thirteen files'							=> Repository::parse( fontsList( [ fontsFamily( 'many', 'Many', array_map( static fn( int $step ): array => [ 100 + $step * 50, 'normal' ], range( 0, 12 ) ) )['entry'] ] ) ),
	'a slug twice'								=> Repository::parse( fontsList( [ $entry, $entry ] ) ),
	'one bad entry among good ones'	=> Repository::parse( fontsList( [ $entry, $serif['entry'], array_replace( $entry, [ 'slug' => '../x' ] ) ] ) ),
	'more than two thousand families'	=> Repository::parse( fontsList( array_map( static fn( int $number ): array => array_replace( $entry, [ 'slug' => 'family-'. $number ] ), range( 1, 2001 ) ) ) ),
	'no json'											=> Repository::parse( '<html>' ),
];
$read = array_keys( array_filter( $parsed, static fn( array|string $answer ): bool => is_string( $answer ) === false ) );
$bad = fontsList( [ $entry, array_replace( $serif['entry'], [ 'slug' => '../x' ] ) ] );
$tolerant = Repository::parse( fontsList( [ $entry + [ 'preview' => 'https://fonts.example.test/p.woff2', 'released' => '2026-10-12' ] ], [ 'publisher' => 'x' ] ) );
check( 'C. a list that is not what format 1 says is refused as a whole - '. implode( ', ', array_keys( $parsed ) ). ' - a signed one as well, 502, and the kept list stays; a key the reader does not know is ignored and not kept'. ( $read === [] ? '' : ' - NOT: '. implode( ', ', $read ) ),
	count( $parsed ) === 14 && $read === [] && $loadOf( [ FONTS_LIST => $bad, FONTS_LIST. '.sig' => fontsSign( $bad, $keys[0] ) ] ) === [ 502, 'themes_fonts_parse', [ FONTS_LIST, FONTS_LIST. '.sig' ], true ]
	&& is_array( $tolerant ) === true && array_keys( $tolerant['families'][0] ) === [ 'slug', 'family', 'category', 'version', 'license', 'source', 'weights', 'styles', 'files', 'archive', 'sha256', 'size' ] );

$long = fontsFamily( str_repeat( 'a', 61 ), 'Long', [ [ 400, 'normal' ] ] );
$dotted = fontsFamily( 'noto-dotted', 'Noto Sans 2.0', [ [ 400, 'normal' ] ] );
$system = fontsFamily( 'system-sans', 'System Sans', [ [ 400, 'normal' ] ] );
check( 'C. a family the brand cannot name - a dot in its name, a slug of more than sixty characters, the slug of a stack of the system - is listed with the reason and is no reason to refuse the list',
	Repository::problem( $long['entry'] ) === 'family' && Repository::problem( $dotted['entry'] ) === 'family' && Repository::problem( $system['entry'] ) === 'system' && Repository::problem( $open['entry'] ) === '' && is_array( Repository::parse( fontsList( [ $long['entry'], $dotted['entry'], $system['entry'] ] ) ) ) === true );

// what is kept is the document and its signature, and it is believed for the signature every time it is read
$evil = fontsFamily( 'evil', 'Evil', [ [ 400, 'normal' ] ], [ 'host' => 'attacker.example.com' ] );
$tampered = [];

foreach( [
	'an entry added to the list'						=> [ 'list' => fontsList( [ $open['entry'], $serif['entry'], $evil['entry'] ] ) ],
	'a list and a signature of another key'	=> [ 'list' => fontsList( [ $evil['entry'] ] ), 'signature' => fontsSign( fontsList( [ $evil['entry'] ] ), $other[0] ) ],
	'no signature'													=> [ 'signature' => '' ],
	'families beside the list'							=> [ 'list' => 42, 'families' => [ $evil['entry'] ] ],
] as $label => $change ) {

	\Nino\Filesystem::putFileContent( $site, Repository::CACHE, array_replace( $stored, $change ) );
	$asked = fontsServe( $site, [ $evil['entry']['archive'] => $evil['archive'], $open['entry']['archive'] => $open['archive'] ] );
	$tampered[$label] = Repository::cached( $site ) === null && fontsCall( $site, 'themes/fonts' )[1]['available'] === null && fontsCall( $site, 'themes/fonts-install', [ 'slug' => 'open-sans' ] )[0] === 404
		&& fontsCall( $site, 'themes/fonts-install', [ 'slug' => 'evil' ] )[0] === 404 && count( $asked ) === 0 && is_dir( $fontsOf( $site ) ) === false;
}

\Nino\Filesystem::putFileContent( $site, Repository::CACHE, $stored );
check( 'C. what is kept below data/ is the list byte for byte, its signature, the address and the time; a file another hand wrote is no list, since its signature does not hold over it - nothing is offered, nothing is installed and nobody is asked: '. implode( ', ', array_keys( $tampered ) ),
	array_keys( $stored ) === [ 'fetched', 'url', 'list', 'signature' ] && $stored['list'] === $serves[FONTS_LIST] && count( $tampered ) === 4 && array_unique( array_values( $tampered ) ) === [ true ] && Repository::cached( $site ) !== null );

$rekeyed = $site;
$rekeyed['/nino/features']['themes']['settings']['fontsKey'] = $other[2];
$moved = $site;
$moved['/nino/features']['themes']['settings']['fontsUrl'] = 'https://other.example.test/webfonts.json';
check( 'C. a list that was signed with a key the settings do not name any more, or fetched under another address than they name, is no list: nothing is offered and an install finds nothing',
	Repository::cached( $rekeyed ) === null && fontsCall( $rekeyed, 'themes/fonts-install', [ 'slug' => 'open-sans' ] )[0] === 404 && Repository::cached( $moved ) === null && fontsCall( $moved, 'themes/fonts' )[1]['available'] === null );

$asked = fontsServe( $site, $serves );
$ages = [];

foreach( [ 'a day and a second ago' => time() - 86401, 'an hour from now' => time() + 3600 ] as $label => $fetched ) {
	\Nino\Filesystem::putFileContent( $site, Repository::CACHE, array_replace( $stored, [ 'fetched' => $fetched ] ) );
	$ages[$label] = fontsCall( $site, 'themes/fonts' )[1]['available']['stale'] === true && fontsCall( $site, 'themes/fonts-install', [ 'slug' => 'open-sans' ] )[1]['code'] === 'themes_fonts_stale';
}

check( 'C. a list that was fetched more than a day ago, or at a time that is still to come, is shown as old and nothing is installed from it, 409 and no request - it is loaded once more first',
	array_unique( array_values( $ages ) ) === [ true ] && count( $asked ) === 0 && is_dir( $fontsOf( $site ) ) === false && fontsCall( $site, 'themes/fonts-load' )[0] === 200 && fontsCall( $site, 'themes/fonts' )[1]['available']['stale'] === false );

// ------------------------------------------------------------------------------------------------------------------------------------
echo "\nD. An install\n";

$asked = fontsServe( $site, $serves );
$started = microtime( true );
[ $status, $body ] = fontsCall( $site, 'themes/fonts-install', [ 'slug' => 'open-sans' ] );
$numbers[] = 'install of a family of three files, one request and the checks: '. round( ( microtime( true ) - $started ) * 1000, 1 ). ' ms';
$folder = $fontsOf( $site ). '/open-sans';
$declared = json_decode( (string) file_get_contents( $folder. '/font.json' ), true );
$modes = [];

foreach( array_diff( (array) scandir( $folder ), [ '.', '..' ] ) as $name )
	$modes[] = substr( sprintf( '%o', fileperms( $folder. '/'. $name ) ), -4 );

check( 'D. an install asks for the archive of the family and for nothing else - one request, with the size the list names and one byte as its limit - and the family stands in public/fonts/<slug>/: its .woff2 files byte for byte, its licence as LICENSE.txt and a font.json, no other file, none that can be run',
	$status === 200 && $body['result'] === [ 'slug' => 'open-sans', 'updated' => false ] && (array) $asked === [ [ $open['entry']['archive'], [ 'timeout' => 60, 'maxBytes' => strlen( $open['archive'] ) + 1, 'addresses' => [ '93.184.216.34' ] ] ] ]
	&& array_values( array_diff( (array) scandir( $folder ), [ '.', '..' ] ) ) === [ '400-italic.woff2', '400.woff2', '700.woff2', 'LICENSE.txt', 'font.json' ] && file_get_contents( $folder. '/400.woff2' ) === $open['files']['400.woff2']
	&& str_contains( (string) file_get_contents( $folder. '/LICENSE.txt' ), 'SIL Open Font License' ) === true && array_unique( $modes ) === [ '0644' ] );
check( 'D. font.json is what the brand reads - the name, the fallback of the category, the faces - with the slug, the version, the licence, the category and the source beside it, and nothing of the work is left: no folder of it in public/fonts/, below data/ the list and the lock',
	$declared === [ 'name' => 'Open Sans', 'fallback' => 'sans-serif', 'faces' => [ [ 'weight' => 400, 'style' => 'normal', 'file' => '400.woff2' ], [ 'weight' => 700, 'style' => 'normal', 'file' => '700.woff2' ], [ 'weight' => 400, 'style' => 'italic', 'file' => '400-italic.woff2' ] ],
		'slug' => 'open-sans', 'category' => 'sans', 'version' => '1.2', 'license' => 'OFL-1.1', 'source' => 'https://github.com/example/open-sans' ] && fontsWork( $site ) === [] && array_values( array_diff( (array) scandir( $fontsOf( $site ) ), [ '.', '..' ] ) ) === [ 'open-sans' ] );

$mine = $body['installed'][0];
$list = fontsCall( $site, 'themes/list' )[1];
check( 'D. the view lists the family as installed, from here, with its licence and unused - and the font is in the choice of the brand at once: the answer of the install names it among the fonts, as the list of the themes does',
	count( $body['installed'] ) === 1 && $mine['key'] === 'open-sans' && count( $mine['faces'] ) === 3 && $mine['license'] === 'OFL-1.1' && $mine['licensed'] === true && $mine['version'] === '1.2' && $mine['made'] === true && $mine['used'] === []
	&& $body['available']['families'][0]['installed'] === '1.2' && in_array( [ 'key' => 'open-sans', 'name' => 'Open Sans', 'system' => false ], $body['fonts'], true ) === true && $body['fonts'] === $list['fonts'] );

// the axis of a weight, and what the stylesheet makes of both
fontsCall( $site, 'themes/fonts-install', [ 'slug' => 'fraunces' ] );
$brand = [];

foreach( Brand::KEYS as $key )
	$brand[$key] = [ 'own' => false ];

$none = $brand;
$brand['font-title'] = [ 'own' => true, 'value' => 'fraunces' ];
$brand['font-text'] = [ 'own' => true, 'value' => 'open-sans' ];
[ $brandStatus ] = fontsCall( $site, 'themes/brand', [ 'theme' => 'nino', 'brand' => $brand, 'brandBase' => $list['brandStamp'] ] );
[ $applyStatus ] = fontsCall( $site, 'themes/apply', [ 'theme' => 'nino' ] );
$css = (string) file_get_contents( ninoSandboxDir( $site ). '/private/assets/theme.css' );
preg_match_all( '/url\("([^"]+)"\)/', $css, $urls );
$usedOf = static fn( array $view ): array => array_column( $view['installed'], 'used', 'key' );
check( 'D. the brand takes the two families and the stylesheet of the website loads them from the site: four @font-face, each from [[/nino/public]]/fonts/, the axis as "100 900", no address of another host in the file - and the view says where the brand uses each',
	$brandStatus === 200 && $applyStatus === 200 && substr_count( $css, '@font-face' ) === 4 && str_contains( $css, '@font-face { font-family: "Fraunces"; font-style: normal; font-weight: 100 900; font-display: swap; src: url("[[/nino/public]]/fonts/fraunces/100-900.woff2") format("woff2"); }' ) === true
	&& count( $urls[1] ) === 4 && array_filter( $urls[1], static fn( string $url ): bool => str_starts_with( $url, '[[/nino/public]]/fonts/' ) === false ) === [] && preg_match( '#https?://|//[a-z]#', $css ) === 0
	&& $usedOf( fontsCall( $site, 'themes/fonts' )[1] ) === [ 'fraunces' => [ 'font-title' ], 'open-sans' => [ 'font-text' ] ] );

// an update: the family whole, in place of the one that was there
$newer = fontsFamily( 'open-sans', 'Open Sans', [ [ 400, 'normal' ], [ 600, 'normal' ] ], [ 'version' => '1.3' ] );
$freshBefore = fontsCall( $site, 'themes/list' )[1]['active'];
fontsServe( $site, fontsRepository( [ $newer, $serif ] ) );
$offered = fontsCall( $site, 'themes/fonts-load' )[1]['available']['families'][0];
[ $status, $body ] = fontsCall( $site, 'themes/fonts-install', [ 'slug' => 'open-sans' ] );
$staleAfter = fontsCall( $site, 'themes/list' )[1]['active'];
fontsCall( $site, 'themes/apply', [ 'theme' => 'nino', 'force' => true ] );
$rebuilt = (string) file_get_contents( ninoSandboxDir( $site ). '/private/assets/theme.css' );
check( 'D. a newer version of an installed family is offered and replaces it whole, even while the brand uses it: the files of the new one and none of the old, said to be an update - and the stylesheet that names the old files is stale for its fonts until it is built once more',
	$offered['installed'] === '1.2' && $offered['version'] === '1.3' && $status === 200 && $body['result'] === [ 'slug' => 'open-sans', 'updated' => true ] && array_values( array_diff( (array) scandir( $folder ), [ '.', '..' ] ) ) === [ '400.woff2', '600.woff2', 'LICENSE.txt', 'font.json' ]
	&& fontsWork( $site ) === [] && [ $freshBefore['stale'], $freshBefore['fonts'] ] === [ false, false ] && [ $staleAfter['stale'], $staleAfter['fonts'] ] === [ true, true ] && str_contains( $rebuilt, '/fonts/open-sans/600.woff2' ) === true && str_contains( $rebuilt, '700.woff2' ) === false
	&& fontsCall( $site, 'themes/list' )[1]['active']['stale'] === false && Typefaces::drifted( $site, 'src: url("[[/nino/public]]/fonts/not-there/400.woff2")' ) === true && Typefaces::drifted( $site, 'body { color: red }' ) === false );

// the licence is a text, whatever it says, and nothing in the folder of a family is a file a web server has no type for
$page = "<html><body><script>document.title='x'</script>Open Font License</body></html>";
$markup = fontsFamily( 'html-licence', 'Html Licence', [ [ 400, 'normal' ] ], [ 'tar' => [ [ 'html-licence/', '', '5' ], [ 'html-licence/font.json', (string) json_encode( array_intersect_key( fontsFamily( 'html-licence', 'Html Licence', [ [ 400, 'normal' ] ] )['entry'], array_flip( Repository::MANIFEST ) ) ) ],
	[ 'html-licence/LICENSE', $page ], [ 'html-licence/400.woff2', fontsWoff2( 105, '400normal' ) ] ] ] );
fontsServe( $site, fontsRepository( [ $newer, $serif, $markup ] ) );
fontsCall( $site, 'themes/fonts-load' );
[ $status ] = fontsCall( $site, 'themes/fonts-install', [ 'slug' => 'html-licence' ] );
$endings = [];

foreach( new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $fontsOf( $site ), FilesystemIterator::SKIP_DOTS ) ) as $file )
	$endings[] = pathinfo( (string) $file->getFilename(), PATHINFO_EXTENSION );

sort( $endings );
check( 'D. a licence that is written as a page is a text all the same: it stands in the folder as LICENSE.txt, the panel is sent it as a text - and every file of the three families in public/fonts/ has an ending a web server knows a type for: json, txt or woff2',
	$status === 200 && file_get_contents( $fontsOf( $site ). '/html-licence/LICENSE.txt' ) === $page && is_file( $fontsOf( $site ). '/html-licence/LICENSE' ) === false && fontsCall( $site, 'themes/fonts-license', [ 'slug' => 'html-licence' ] )[1]['text'] === $page
	&& count( $endings ) === 10 && array_values( array_unique( $endings ) ) === [ 'json', 'txt', 'woff2' ] );
fontsCall( $site, 'themes/fonts-remove', [ 'slug' => 'html-licence' ] );

// ------------------------------------------------------------------------------------------------------------------------------------
echo "\nE. An install that is refused\n";

$plain = fontsFamily( 'inter', 'Inter', [ [ 400, 'normal' ], [ 700, 'normal' ] ] );
$archiveUrl = $plain['entry']['archive'];

/**
 *	An install of one family of the name inter from a repository that serves it some way, on the site as it stands: the answer, what was asked,
 *	whether public/ and the work are as before, and whether the lock is free for the next. What it installed is deleted behind it
 *
 *	@param		array				$family				As fontsFamily() makes it
 *	@param		array				$change				url => what it answers, in place of what the repository serves
 *	@param		callable|null	$prepare		( &$appData ) - what is done before the install
 */
$installOf = static function( array $family, array $change = [], ?callable $prepare = null ) use ( &$site, $serif, $newer ): array {

	fontsServe( $site, fontsRepository( [ $family, $serif, $newer ] ) );
	fontsCall( $site, 'themes/fonts-load' );

	if( $prepare !== null )
		$prepare( $site );

	$asked = fontsServe( $site, array_replace( fontsRepository( [ $family, $serif, $newer ] ), $change ) );
	$public = fontsDigest( ninoSandboxDir( $site ). '/public' );
	[ $status, $body ] = fontsCall( $site, 'themes/fonts-install', [ 'slug' => $family['entry']['slug'] ] );
	$clean = fontsDigest( ninoSandboxDir( $site ). '/public' ) === $public && fontsWork( $site ) === [];

	// The lock is free: another handle takes it without waiting
	$lock = fopen( \Nino\Filesystem::path( $site, Typefaces::LOCK ), 'c' );
	$free = flock( $lock, LOCK_EX | LOCK_NB );
	fclose( $lock );

	if( $status === 200 )
		fontsCall( $site, 'themes/fonts-remove', [ 'slug' => $family['entry']['slug'] ] );

	unset( $site['./nino/fetch/resolve'] );
	fontsServe( $site, [] );

	return [ 'status' => $status, 'code' => $body['code'] ?? '', 'asked' => array_column( (array) $asked, 0 ), 'clean' => $clean === true && $free === true ];
};

/**
 *	Which of the answers are not the refusal that is named, with nothing left behind
 */
$notRefused = static function( array $answers, int $status, string $code ): array {
	return array_keys( array_filter( $answers, static fn( array $answer ): bool => $answer['status'] !== $status || $answer['code'] !== $code || $answer['clean'] !== true ) );
};

$flipped = $plain['archive'];
$flipped[strlen( $flipped ) - 9] = chr( ord( $flipped[strlen( $flipped ) - 9] ) ^ 1 );
$mismatch = [
	'one bit changed'		=> $installOf( $plain, [ $archiveUrl => $flipped ] ),
	'half of it'				=> $installOf( $plain, [ $archiveUrl => substr( $plain['archive'], 0, (int) ( strlen( $plain['archive'] ) / 2 ) ) ] ),
	'the bytes of the digest, and a size the list names one too high'	=> $installOf( fontsFamily( 'inter', 'Inter', [ [ 400, 'normal' ], [ 700, 'normal' ] ], [ 'entry' => [ 'size' => strlen( $plain['archive'] ) + 1 ] ] ) ),
];
$download = [
	'more than the list names'	=> $installOf( $plain, [ $archiveUrl => $plain['archive']. 'xx' ] ),
	'a redirect'								=> $installOf( $plain, [ $archiveUrl => static fn( array $options ): array => [ 'ok' => false, 'status' => 301, 'body' => '', 'error' => 'http 301' ], 'https://elsewhere.example.test/inter-1.0.tar.gz' => $plain['archive'] ] ),
	'broken off in the middle'	=> $installOf( $plain, [ $archiveUrl => static fn( array $options ): array => [ 'ok' => false, 'status' => 200, 'body' => '', 'error' => 'the answer could not be read to the end' ] ] ),
];
check( 'E. the family of these probes installs as it is; an archive that is not the bytes the signed list names by their size and their sha256 is not unpacked - '. implode( ', ', array_keys( $mismatch ) ). ' - and one that does not arrive whole is not installed - '. implode( ', ', array_keys( $download ) ). ': 502, one request and none to another host, nothing in public/fonts/, nothing left of the work, the lock free',
	$installOf( $plain )['status'] === 200 && $notRefused( $mismatch, 502, 'themes_fonts_mismatch' ) === [] && $notRefused( $download, 502, 'themes_fonts_download' ) === [] && array_unique( array_map( static fn( array $answer ): string => json_encode( $answer['asked'] ), array_values( $download ) ) ) === [ json_encode( [ $archiveUrl ] ) ] );

// what the archive holds: the list is signed over the digest of each of these, so that what is refused here is refused although the signature holds
$faces = [ [ 400, 'normal' ], [ 700, 'normal' ] ];
$good = [ '400.woff2' => fontsWoff2( 105, '400normal' ), '700.woff2' => fontsWoff2( 105, '700normal' ) ];
$manifestOf = static fn( array $change = [] ): string => (string) json_encode( array_replace( array_intersect_key( $plain['entry'], array_flip( Repository::MANIFEST ) ), $change ) );
$tarOf = static fn( array $entries ): array => fontsFamily( 'inter', 'Inter', $faces, [ 'tar' => $entries ] );
$base = [ [ 'inter/', '', '5' ], [ 'inter/font.json', $manifestOf() ], [ 'inter/LICENSE', "SIL Open Font License\n" ], [ 'inter/400.woff2', $good['400.woff2'] ], [ 'inter/700.woff2', $good['700.woff2'] ] ];
$swap = static function( int $index, array $entry ) use ( $base ): array { $entries = $base; $entries[$index] = $entry; return $entries; };
$content = [
	'a script under the name of a font'				=> $installOf( $tarOf( $swap( 3, [ 'inter/400.woff2', '<?php system( $_GET["c"] ); ?>'. str_repeat( ' ', 80 ) ] ) ) ),
	'a font that says another length than it has'	=> $installOf( $tarOf( $swap( 3, [ 'inter/400.woff2', $good['400.woff2']. '<?php echo 1; ?>' ] ) ) ),
	'a file the list names is not there'			=> $installOf( $tarOf( array_slice( $base, 0, 4 ) ) ),
	'a script beside the fonts'								=> $installOf( $tarOf( array_merge( $base, [ [ 'inter/index.php', '<?php phpinfo();' ] ] ) ) ),
	'a font.json of another version'					=> $installOf( $tarOf( $swap( 1, [ 'inter/font.json', $manifestOf( [ 'version' => '9.9' ] ) ] ) ) ),
	'no licence'															=> $installOf( $tarOf( array_merge( array_slice( $base, 0, 2 ), array_slice( $base, 3 ) ) ) ),
	'a licence that says nothing'							=> $installOf( $tarOf( $swap( 2, [ 'inter/LICENSE', " \n\t\n" ] ) ) ),
	'a licence with a null byte'							=> $installOf( $tarOf( $swap( 2, [ 'inter/LICENSE', "Open Font License\0<script>" ] ) ) ),
	'a licence that is no utf-8'							=> $installOf( $tarOf( $swap( 2, [ 'inter/LICENSE', "Open Font License \xff\xfe" ] ) ) ),
	'a symbolic link in place of a font'			=> $installOf( $tarOf( $swap( 3, [ 'inter/400.woff2', '', '2', '/etc/passwd' ] ) ) ),
	'a name that climbs out of the folder'		=> $installOf( $tarOf( array_merge( $base, [ [ 'inter/../../../private/config.php', '<?php' ] ] ) ) ),
];
$wrong = $notRefused( $content, 502, 'themes_fonts_archive' );
check( 'E. the archive of these probes, written entry by entry, installs as it is; one that holds anything but the family the list promised is refused although its digest is the signed one: 502, nothing in public/ changed, nothing left - '. implode( '; ', array_keys( $content ) ). ( $wrong === [] ? '' : ' - NOT: '. implode( ', ', $wrong ) ),
	$installOf( $tarOf( $base ) )['status'] === 200 && count( $content ) === 11 && $wrong === [] );

$twelve = [];

foreach( [ 100, 200, 300, 400, 500, 600 ] as $weight )
	foreach( [ 'normal', 'italic' ] as $style )
		$twelve[] = [ $weight, $style ];

check( 'E. a family of the most faces the brand takes installs - twelve woff2 files, the font.json and the licence, and the folder: the limit on the entries of its archive counts all of them',
	count( $twelve ) === $constant( Repository::class, 'MAX_FACES' ) && $installOf( fontsFamily( 'inter', 'Inter', $twelve ) )['status'] === 200 );

// the reader of the archive by itself: what it refuses, it refuses before anybody looks at what a file holds
$readOf = static fn( array $entries ): array|string => \Nino\Archive::read( (string) gzencode( fontsTar( $entries ) ), 'inter', 15, 4194304, 16777216, $constant( Typefaces::class, 'FILE_NAME' ) );
$read = $readOf( $base );
$unread = [
	'a symbolic link'									=> $readOf( array_merge( $base, [ [ 'inter/900.woff2', '', '2', '/etc/passwd' ] ] ) ),
	'a hard link'											=> $readOf( array_merge( $base, [ [ 'inter/900.woff2', '', '1', 'inter/700.woff2' ] ] ) ),
	'a device'												=> $readOf( array_merge( $base, [ [ 'inter/900.woff2', '', '3' ] ] ) ),
	'a long name of gnu tar'					=> $readOf( array_merge( [ [ '././@LongLink', 'inter/../../x', 'L' ] ], $base ) ),
	'a name that begins with a dot'		=> $readOf( array_merge( $base, [ [ 'inter/.htaccess', 'x' ] ] ) ),
	'a name that climbs'							=> $readOf( array_merge( $base, [ [ 'inter/../x.woff2', 'x' ] ] ) ),
	'a name from the root'						=> $readOf( array_merge( $base, [ [ '/etc/cron.d/x', 'x' ] ] ) ),
	'a name with a backslash'					=> $readOf( array_merge( $base, [ [ 'inter\\..\\x.woff2', 'x' ] ] ) ),
	'a name in a folder of the folder'	=> $readOf( array_merge( $base, [ [ 'inter/sub/x.woff2', 'x' ] ] ) ),
	'a name in another folder'				=> $readOf( array_merge( $base, [ [ 'other/x.woff2', 'x' ] ] ) ),
	'a name that begins with a null byte and goes on as that of a font'	=> $readOf( array_merge( $base, [ [ "\0inter/900.woff2", 'x' ] ] ) ),
	'a file of one byte over four megabytes'	=> $readOf( array_merge( $base, [ [ 'inter/900.woff2', str_repeat( 'x', 4194305 ) ] ] ) ),
	'fifteen files'										=> $readOf( array_merge( $base, array_map( static fn( int $number ): array => [ 'inter/extra-'. $number. '.woff2', 'x' ], range( 1, 11 ) ) ) ),
	'files of more than sixteen megabytes together'	=> $readOf( array_merge( $base, array_map( static fn( int $number ): array => [ 'inter/extra-'. $number. '.woff2', str_repeat( 'x', 4194304 ) ], range( 1, 5 ) ) ) ),
	'a pax header that names a path'	=> $readOf( array_merge( array_slice( $base, 0, 3 ), [ [ 'inter/PaxHeaders/400.woff2', "30 path=inter/../../evil.php\n", 'x' ] ], array_slice( $base, 3 ) ) ),
	'a file twice'										=> $readOf( array_merge( $base, [ [ 'inter/400.woff2', 'x' ] ] ) ),
	'a second folder'									=> $readOf( array_merge( $base, [ [ 'evil/', '', '5' ] ] ) ),
	'the folder twice'								=> $readOf( array_merge( $base, [ [ 'inter/', '', '5' ] ] ) ),
	'a header whose checksum does not hold'	=> \Nino\Archive::read( (string) gzencode( substr_replace( fontsTar( $base ), '0000000', 148, 7 ) ), 'inter', 15, 4194304, 16777216 ),
	'a tar that ends inside a file'		=> \Nino\Archive::read( (string) gzencode( substr( fontsTar( $base ), 0, 512 * 6 + 100 ) ), 'inter', 15, 4194304, 16777216 ),
	'no gzip stream'									=> \Nino\Archive::read( fontsTar( $base ), 'inter', 15, 4194304, 16777216 ),
	'a gzip stream without its last four bytes'	=> \Nino\Archive::read( substr( (string) gzencode( fontsTar( $base ) ), 0, -4 ), 'inter', 15, 4194304, 16777216 ),
	'a second gzip stream behind the first'	=> \Nino\Archive::read( gzencode( fontsTar( $base ) ). gzencode( fontsTar( [ [ 'inter/900.woff2', 'x' ] ] ) ), 'inter', 15, 4194304, 16777216 ),
	'anything behind the gzip stream'	=> \Nino\Archive::read( gzencode( fontsTar( $base ) ). 'more', 'inter', 15, 4194304, 16777216 ),
];
$passed = array_keys( array_filter( $unread, static fn( array|string $answer ): bool => is_string( $answer ) === false ) );
check( 'E. the reader of the archive answers the files of a good one by their names, in memory - in the forms a tar may have: without the entry of its folder, with ./ before its names, with a pax header that says only a time before a file - and refuses each of these by itself, whatever a file holds: '. implode( ', ', array_keys( $unread ) ). ( $passed === [] ? '' : ' - NOT: '. implode( ', ', $passed ) ),
	is_array( $read ) === true && array_keys( $read ) === [ 'font.json', 'LICENSE', '400.woff2', '700.woff2' ] && $read['400.woff2'] === $good['400.woff2'] && count( $unread ) === 24 && $passed === []
	&& is_array( $readOf( array_slice( $base, 1 ) ) ) === true && is_array( $readOf( array_map( static fn( array $one ): array => [ './'. $one[0] ] + $one, $base ) ) ) === true
	&& is_array( $pax = $readOf( array_merge( array_slice( $base, 0, 3 ), [ [ 'inter/PaxHeaders/400.woff2', "20 mtime=1700000000\n", 'x' ] ], array_slice( $base, 3 ) ) ) ) === true && array_keys( $pax ) === array_keys( $read ) );
unset( $unread, $read );

$stream = ( static function(): string {
	$deflate = deflate_init( ZLIB_ENCODING_GZIP, [ 'level' => 9 ] );
	$out = '';
	$block = str_repeat( "\0", 1048576 );
	for( $round = 0; $round < 64; $round++ )
		$out .= deflate_add( $deflate, $block, ZLIB_NO_FLUSH );
	return $out. deflate_add( $deflate, '', ZLIB_FINISH );
} )();
memory_reset_peak_usage();
$floor = memory_get_usage();
$bomb = \Nino\Archive::read( $stream, 'inter', 15, 4194304, 16777216 );
$grown = memory_get_peak_usage() - $floor;
$numbers[] = 'a stream of '. round( strlen( $stream ) / 1024 ). ' KB that unpacks to 64 MB: refused with the memory '. round( $grown / 1048576, 1 ). ' MB over where it stood';
check( 'E. a stream that unpacks to 64 megabytes is stopped where it passes the limit of a family and is not read to its end: the memory grows by less than what it would unpack to', is_string( $bomb ) === true && $grown < 48 * 1048576 );
unset( $stream );

// the slug that is posted
$posted = [];
$before = fontsDigest( ninoSandboxDir( $site ). '/public' );
$asked = fontsServe( $site, fontsRepository( [ $newer, $serif ] ) );
fontsCall( $site, 'themes/fonts-load' );
$count = count( $asked );

foreach( [ '../open-sans', '/etc/passwd', "open\0sans", "open-sans\n", ' open-sans', 'Open-Sans', str_repeat( 'a', 65 ), [ 'open-sans' ], null ] as $slug ) {
	[ $status, $body ] = fontsCall( $site, 'themes/fonts-install', [ 'slug' => $slug ] );
	[ $removeStatus, $removeBody ] = fontsCall( $site, 'themes/fonts-remove', [ 'slug' => $slug ] );
	[ $licenseStatus ] = fontsCall( $site, 'themes/fonts-license', [ 'slug' => $slug ] );
	$posted[] = $status === 400 && $body['code'] === 'themes_fonts_unknown' && $body['field'] === 'slug' && $removeStatus === 400 && $removeBody['code'] === 'themes_fonts_unknown' && $licenseStatus === 404;
}

$unlisted = fontsCall( $site, 'themes/fonts-install', [ 'slug' => 'roboto' ] );
check( 'E. a slug that is posted is a slug or it is nothing: a path that climbs, a path from the root, a null byte, a line break, a space, capitals, 65 characters, a list, nothing - refused by install and by delete with 400 and by the licence with 404; a slug the list does not name is 404; no request, and nothing changes',
	count( $posted ) === 9 && array_unique( $posted ) === [ true ] && [ $unlisted[0], $unlisted[1]['code'] ] === [ 404, 'themes_fonts_unknown' ] && count( $asked ) === $count && fontsDigest( ninoSandboxDir( $site ). '/public' ) === $before );

$unnamable = [ 'a dot in the name' => $installOf( $dotted ), 'a slug of 61 characters' => $installOf( $long ), 'the slug of a stack of the system' => $installOf( $system ) ];
check( 'E. a family the brand cannot name is not installed: 409 and no request - '. implode( ', ', array_keys( $unnamable ) ), $notRefused( $unnamable, 409, 'themes_fonts_not_installable' ) === [] && array_sum( array_map( static fn( array $answer ): int => count( $answer['asked'] ), $unnamable ) ) === 0 );

// where the archive is
$literal = fontsFamily( 'inter', 'Inter', $faces, [ 'entry' => [ 'archive' => 'https://169.254.169.254/latest/user-data' ] ] );
$cdn = fontsFamily( 'inter', 'Inter', $faces, [ 'host' => 'cdn.example.test' ] );
$elsewhere = [
	'an address in place of a name'	=> $installOf( $literal, [ 'https://169.254.169.254/latest/user-data' => $literal['archive'] ] ),
	'a name that leads to 10.0.0.8'	=> $installOf( $cdn, [], static function( array &$appData ): void { $appData['./nino/fetch/resolve'] = static fn( string $host ): array => $host === 'cdn.example.test' ? [ '10.0.0.8' ] : [ '93.184.216.34' ]; } ),
];
check( 'E. an archive the signed list places at an internal host - an address, a name that is looked up as one - is not asked for: 409 and no request; one at another public host than the list is asked for there, since the list is signed and says where',
	$notRefused( $elsewhere, 409, 'themes_fonts_internal' ) === [] && array_sum( array_map( static fn( array $answer ): int => count( $answer['asked'] ), $elsewhere ) ) === 0 && $installOf( $cdn )['status'] === 200 );

// a folder that is somebody else's
$byHand = static function( array &$appData ) use ( $fontsOf ): void {
	\Nino\Filesystem::putFileContent( $appData, '/fonts/inter/font.json', [ 'name' => 'Inter', 'fallback' => 'sans-serif', 'faces' => [ [ 'weight' => 400, 'style' => 'normal', 'file' => 'inter-regular.woff2' ] ] ] );
	file_put_contents( $fontsOf( $appData ). '/inter/inter-regular.woff2', 'wOF2 by hand' );
	file_put_contents( $fontsOf( $appData ). '/inter/notes.txt', 'mine' );
};
$foreign = $installOf( $plain, [], $byHand );
fontsServe( $site, fontsRepository( [ $plain, $serif, $newer ] ) );
$row = fontsCall( $site, 'themes/fonts-load' )[1];
$card = array_column( $row['installed'], null, 'key' )['inter'];
$handDigest = fontsDigest( $fontsOf( $site ). '/inter' );
[ $status, $body ] = fontsCall( $site, 'themes/fonts-remove', [ 'slug' => 'inter' ] );
check( 'E. a folder of that name in public/fonts/ that this feature did not install is not replaced and not touched, byte for byte: 409 - the view says so before anybody asks, lists the folder as a font that was not installed from here - and it is not deleted from here either: 409 with its own sentence, the folder and every file in it as they were',
	[ $foreign['status'], $foreign['code'], $foreign['clean'] ] === [ 409, 'themes_fonts_foreign', true ] && array_column( $row['available']['families'], 'foreign', 'slug' )['inter'] === true && [ $card['made'], $card['version'], $card['licensed'] ] === [ false, '', false ]
	&& $status === 409 && $body['code'] === 'themes_fonts_handmade' && $body['params'] === [ 'inter' ] && fontsDigest( $fontsOf( $site ). '/inter' ) === $handDigest && fontsWork( $site ) === [] );

\Nino\Filesystem::removeDir( $fontsOf( $site ). '/inter' );
mkdir( ninoSandboxDir( $site ). '/elsewhere' );
file_put_contents( ninoSandboxDir( $site ). '/elsewhere/keep.txt', 'kept' );
$linked = $installOf( $plain, [], static function( array &$appData ) use ( $fontsOf ): void { symlink( ninoSandboxDir( $appData ). '/elsewhere', $fontsOf( $appData ). '/inter' ); } );
check( 'E. a link of that name to a folder elsewhere is not followed and not replaced: 409, the link and what it points at as they were', [ $linked['status'], $linked['code'], $linked['clean'] ] === [ 409, 'themes_fonts_foreign', true ] && is_link( $fontsOf( $site ). '/inter' ) === true && file_get_contents( ninoSandboxDir( $site ). '/elsewhere/keep.txt' ) === 'kept' );
unlink( $fontsOf( $site ). '/inter' );

// two at a time
$asked = fontsServe( $site, fontsRepository( [ $plain, $serif, $newer ] ) );
fontsCall( $site, 'themes/fonts-load' );
$count = count( $asked );
$public = fontsDigest( ninoSandboxDir( $site ). '/public' );
$held = fopen( \Nino\Filesystem::path( $site, Typefaces::LOCK ), 'c' );
$during = [];

foreach( [ 'held' => LOCK_EX, 'shared' => LOCK_SH ] as $how => $mode ) {
	flock( $held, $mode );
	$during[$how] = [ fontsCall( $site, 'themes/fonts-install', [ 'slug' => 'inter' ] )[1]['code'] ?? '', fontsCall( $site, 'themes/fonts-remove', [ 'slug' => 'open-sans' ] )[1]['code'] ?? '', count( $asked ) - $count, fontsDigest( ninoSandboxDir( $site ). '/public' ) === $public ];
	flock( $held, LOCK_UN );
}

fclose( $held );
check( 'E. while one install or delete is at work a second one is not waited for and does nothing: 409 for an install and for a delete, no request, public/fonts/ as it was - also where another handle holds the lock only to share it - and an install goes through once the lock is free',
	$during === [ 'held' => [ 'themes_fonts_busy', 'themes_fonts_busy', 0, true ], 'shared' => [ 'themes_fonts_busy', 'themes_fonts_busy', 0, true ] ] && fontsCall( $site, 'themes/fonts-install', [ 'slug' => 'inter' ] )[0] === 200 );

// what a request that was killed left in public/fonts/
$fontsDir = $fontsOf( $site );
$family = static function( string $dir, string $slug ) use ( $good ): void {
	mkdir( $dir, 0755, true );
	file_put_contents( $dir. '/font.json', json_encode( [ 'name' => 'Left Behind', 'fallback' => 'sans-serif', 'faces' => [ [ 'weight' => 400, 'style' => 'normal', 'file' => '400.woff2' ] ], 'slug' => $slug, 'version' => '1.0' ] ) );
	file_put_contents( $dir. '/400.woff2', $good['400.woff2'] );
};
$family( $fontsDir. '/.work-aaaaaaaaaaaa/new', 'old-work' );
$family( $fontsDir. '/.work-bbbbbbbbbbbb/new', 'young-work' );
$family( $fontsDir. '/.work-cccccccccccc/previous', 'moved-aside' );
mkdir( $fontsDir. '/.work-cccccccccccc/new' );
// ...and one whose update was done - the new family is gone from the folder of the work: what is set aside there is the old one, and is not put back
$family( $fontsDir. '/.work-eeeeeeeeeeee/previous', 'done-update' );
$family( $fontsDir. '/.work-dddddddddddd/previous', 'fraunces' );
$family( $fontsDir. '/.work-not-of-this-kind/new', 'other' );
touch( $fontsDir. '/.work-aaaaaaaaaaaa', time() - 3601 );
touch( $fontsDir. '/.work-bbbbbbbbbbbb', time() - 3500 );
touch( $fontsDir. '/.work-dddddddddddd', time() - 7200 );
touch( $fontsDir. '/.work-not-of-this-kind', time() - 7200 );
$fraunces = fontsDigest( $fontsDir. '/fraunces' );
$seen = array_column( fontsCall( $site, 'themes/fonts' )[1]['installed'], 'key' );
$there = is_dir( $fontsDir. '/.work-aaaaaaaaaaaa' );
[ $status ] = fontsCall( $site, 'themes/fonts-remove', [ 'slug' => 'inter' ] );
check( 'E. a folder of the work that a killed request left is no font - the brand does not read it, the tab does not list it, and showing the tab clears nothing. The next install or delete clears it, with the lock held: one that is older than an hour is gone, a younger one stays, a family that was moved aside for an update that never came is put back at once, one whose place is taken is not, and a folder that is not named as the work is named is not touched',
	$seen === [ 'fraunces', 'inter', 'open-sans' ] && $there === true && $status === 200 && array_values( array_diff( (array) scandir( $fontsDir ), [ '.', '..' ] ) ) === [ '.work-bbbbbbbbbbbb', '.work-cccccccccccc', '.work-eeeeeeeeeeee', '.work-not-of-this-kind', 'fraunces', 'moved-aside', 'open-sans' ]
	&& is_file( $fontsDir. '/moved-aside/400.woff2' ) === true && is_dir( $fontsDir. '/.work-cccccccccccc/previous' ) === false && fontsDigest( $fontsDir. '/fraunces' ) === $fraunces );

\Nino\Filesystem::removeDir( $fontsDir. '/.work-eeeeeeeeeeee' );
touch( $fontsDir. '/.work-bbbbbbbbbbbb', time() - 3601 );
[ $status ] = fontsCall( $site, 'themes/fonts-install', [ 'slug' => 'inter' ] );
check( 'E. ...and so does the next install, for the one that is an hour old by then', $status === 200 && is_dir( $fontsDir. '/.work-bbbbbbbbbbbb' ) === false && is_dir( $fontsDir. '/.work-cccccccccccc' ) === true && fontsWork( $site ) === [ 'public/fonts/.work-cccccccccccc', 'public/fonts/.work-not-of-this-kind' ] );

foreach( [ '.work-cccccccccccc', '.work-not-of-this-kind', 'moved-aside', 'inter' ] as $name )
	\Nino\Filesystem::removeDir( $fontsDir. '/'. $name );

// ------------------------------------------------------------------------------------------------------------------------------------
echo "\nF. A delete and the licence\n";

$asked = fontsServe( $site, fontsRepository( [ $newer, $serif ] ) );
fontsCall( $site, 'themes/fonts-load' );
$count = count( $asked );
[ $status, $body ] = fontsCall( $site, 'themes/fonts-remove', [ 'slug' => 'open-sans' ] );
$brand['font-text'] = [ 'own' => false ];
fontsCall( $site, 'themes/brand', [ 'theme' => 'nino', 'brand' => $brand, 'brandBase' => fontsCall( $site, 'themes/list' )[1]['brandStamp'] ] );
$beside = fontsDigest( $fontsOf( $site ). '/fraunces' );
file_put_contents( ninoSandboxDir( $site ). '/public/keep.txt', 'kept' );
[ $freeStatus, $freeBody ] = fontsCall( $site, 'themes/fonts-remove', [ 'slug' => 'open-sans' ] );
check( 'F. a family the brand uses is not deleted: 409 with its own sentence, the folder as it was. One the brand does not use is deleted whole - its folder is gone, nothing of it waits in the work, no request was made - and the family beside it and the files beside public/fonts/ are as they were; it is not there to delete once more, 404',
	$status === 409 && $body['code'] === 'themes_fonts_used' && $freeStatus === 200 && $freeBody['result'] === [ 'slug' => 'open-sans' ] && file_exists( $folder ) === false && fontsWork( $site ) === [] && count( $asked ) === $count && fontsDigest( $fontsOf( $site ). '/fraunces' ) === $beside
	&& file_get_contents( ninoSandboxDir( $site ). '/public/keep.txt' ) === 'kept' && array_column( $freeBody['installed'], 'key' ) === [ 'fraunces' ] && in_array( 'open-sans', array_column( $freeBody['fonts'], 'key' ), true ) === false && fontsCall( $site, 'themes/fonts-remove', [ 'slug' => 'open-sans' ] )[0] === 404 );

// a font the theme on the website suggests is a font the brand uses, as one the project chose is
fontsCall( $site, 'themes/fonts-install', [ 'slug' => 'open-sans' ] );
fontsCall( $site, 'themes/brand', [ 'theme' => 'nino', 'brand' => $none, 'brandBase' => fontsCall( $site, 'themes/list' )[1]['brandStamp'] ] );
$unusedBefore = $usedOf( fontsCall( $site, 'themes/fonts' )[1] )['open-sans'];
[ $applied ] = fontsCall( $site, 'themes/apply', [ 'theme' => 'craft-probe', 'force' => true ] );
$usedThen = $usedOf( fontsCall( $site, 'themes/fonts' )[1] )['open-sans'];
[ $status, $body ] = fontsCall( $site, 'themes/fonts-remove', [ 'slug' => 'open-sans' ] );
check( 'F. a family the theme on the website suggests - the brand of the project names no font of its own - is used by the brand: the tab says for the titles and the buttons, and it is not deleted, 409; with another theme on the website it was used by nothing',
	$unusedBefore === [] && $applied === 200 && $usedThen === [ 'font-title', 'font-button' ] && $status === 409 && $body['code'] === 'themes_fonts_used' && is_file( $folder. '/font.json' ) === true );

// only what is a font of public/fonts/ is deleted
mkdir( $fontsOf( $site ). '/notes', 0755, true );
file_put_contents( $fontsOf( $site ). '/notes/readme.txt', 'mine' );
symlink( ninoSandboxDir( $site ). '/elsewhere', $fontsOf( $site ). '/linked' );
$outside = [];

foreach( [ 'notes', 'linked', 'system-sans', 'roboto' ] as $slug ) {
	[ $status, $body ] = fontsCall( $site, 'themes/fonts-remove', [ 'slug' => $slug ] );
	$outside[$slug] = [ $status, $body['code'] ];
}

check( 'F. a delete takes a font of public/fonts/ and nothing else: a folder there that is no font, a link to a folder elsewhere, a stack of the system and a name that is not there are refused, and the folder, the link and what it points at stay',
	$outside === [ 'notes' => [ 404, 'themes_fonts_unknown' ], 'linked' => [ 404, 'themes_fonts_unknown' ], 'system-sans' => [ 400, 'themes_fonts_unknown' ], 'roboto' => [ 404, 'themes_fonts_unknown' ] ]
	&& file_get_contents( $fontsOf( $site ). '/notes/readme.txt' ) === 'mine' && is_link( $fontsOf( $site ). '/linked' ) === true && is_file( ninoSandboxDir( $site ). '/elsewhere/keep.txt' ) === true );

[ $status, $body ] = fontsCall( $site, 'themes/fonts-license', [ 'slug' => 'open-sans' ] );
file_put_contents( $folder. '/LICENSE.txt', str_repeat( 'a', 70000 ). "\xff\xfe" );
$longText = fontsCall( $site, 'themes/fonts-license', [ 'slug' => 'open-sans' ] )[1]['text'];
file_put_contents( $folder. '/LICENSE.txt', "ok \xff\xfe" );
$scrubbed = fontsCall( $site, 'themes/fonts-license', [ 'slug' => 'open-sans' ] )[1]['text'];
unlink( $folder. '/LICENSE.txt' );
symlink( ninoSandboxDir( $site ). '/private/config.php', $folder. '/LICENSE.txt' );
check( 'F. the licence of an installed family is its text, as the archive brought it - sent as a text of at most 65536 bytes, what is no utf-8 in it replaced; a licence that is a link to another file is none, and there is none of a font that is not there or of a stack of the system',
	$status === 200 && $body === [ 'slug' => 'open-sans', 'text' => "SIL Open Font License, Version 1.1\nCopyright (c) example\n" ] && strlen( $longText ) === 65536 && mb_check_encoding( $scrubbed, 'UTF-8' ) === true && str_starts_with( $scrubbed, 'ok ' ) === true
	&& fontsCall( $site, 'themes/fonts-license', [ 'slug' => 'open-sans' ] )[0] === 404 && fontsCall( $site, 'themes/fonts-license', [ 'slug' => 'roboto' ] )[0] === 404 && fontsCall( $site, 'themes/fonts-license', [ 'slug' => 'system-sans' ] )[0] === 404 );

// ------------------------------------------------------------------------------------------------------------------------------------
echo "\nG. Rights\n";

$wholeOf = static fn(): string => fontsDigest( ninoSandboxDir( $site ). '/public' ). fontsDigest( ninoSandboxDir( $site ). '/private/data/theme' ). fontsDigest( ninoSandboxDir( $site ). '/private/assets' );
$every = [ 'themes/fonts' => [], 'themes/fonts-load' => [], 'themes/fonts-install' => [ 'slug' => 'fraunces' ], 'themes/fonts-remove' => [ 'slug' => 'fraunces' ], 'themes/fonts-license' => [ 'slug' => 'fraunces' ] ];
$owner = fontsCall( $site, 'themes/fonts' )[1]['repository']['settings'];
\Nino\Auth::logoutUser( $site );
$asked = fontsServe( $site, fontsRepository( [ $newer, $serif ] ) );
$whole = $wholeOf();
$out = [];

foreach( $every as $action => $data )
	$out[$action] = fontsCall( $site, $action, $data )[0];

ninoUser( $site, 'editor@example.com', [ '/_admin/elements/manage', '/_admin/features/manage' ] );
$answers = [];

foreach( $every as $action => $data )
	$answers[$action] = fontsCall( $site, $action, $data );

check( 'G. an account that may not manage the themes - though it may set up features - gets 403 on each of the five actions, and nobody signed in gets none of them: no request is made, no list is told, no licence is read, and no file of public/ and of the data of the themes changes',
	array_keys( $answers ) === $actions && array_filter( $answers, static fn( array $answer ): bool => $answer[0] !== 403 || isset( $answer[1]['installed'] ) === true || isset( $answer[1]['text'] ) === true ) === []
	&& array_filter( $out, static fn( int $status ): bool => in_array( $status, [ 401, 403 ], true ) === false ) === [] && count( $out ) === 5 && count( $asked ) === 0 && $wholeOf() === $whole );

\Nino\Auth::logoutUser( $site );
ninoUser( $site, 'themer@example.com', [ '/_admin/themes/manage' ] );
[ $status, $view ] = fontsCall( $site, 'themes/fonts' );
[ $settingsStatus ] = fontsCall( $site, 'features/settings', [ 'key' => 'themes', 'fields' => [ 'fontsUrl' => 'https://evil.example.test/webfonts.json', 'fontsKey' => $other[2] ] ] );
check( 'G. an account that may manage the themes and not the features works with the fonts and is told that the form of the settings is not its to open - and the kernel refuses it the two settings: 403, the address and the key as they were; an account that may do both is told that it may open the form',
	$status === 200 && $view['repository']['settings'] === false && $settingsStatus === 403 && Repository::url( $site ) === FONTS_LIST && Repository::key( $site ) === trim( $keys[2] ) && $owner === true );

// ------------------------------------------------------------------------------------------------------------------------------------
echo "\nH. The script of the tab\n";

$node = function_exists( 'shell_exec' ) === true ? trim( (string) @shell_exec( 'command -v node 2>/dev/null' ) ) : '';

if( $node === '' || function_exists( 'exec' ) === false )
	echo "  --  - node is not available here: themes-fonts-js-smoke.js was NOT run\n";
else {
	$output = [];
	$exit = 1;
	exec( 'NINO_ROOT='. escapeshellarg( $root ). ' '. escapeshellarg( $node ). ' '. escapeshellarg( __DIR__. '/themes-fonts-js-smoke.js' ). ' 2>&1', $output, $exit );
	check( 'H. themes-fonts-js-smoke.js passes - '. ( $output === [] ? 'no output' : end( $output ) ), $exit === 0 );
}

echo "\n";

foreach( $numbers as $number )
	echo '  '. $number. "\n";

$ignored = ninoWarnings();

ninoDone( $site );
