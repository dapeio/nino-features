<?php
declare(strict_types=1);

/**
 *	Nino features
 *	themes-smoke.php	Contract test for the feature Themes - a theme is a folder of parts, the project mixes them, sets its brand and
 *										keeps the result as one stylesheet:
 *
 *											A. the feature: the manifest, the activation, the API in the workbench and the words of its failures
 *											B. the reader: two themes with a part for the section each, the stamp, what is refused as a whole, what rests
 *											   with its reason, what is not read at all
 *											C. the palette: the contrast of every pair a part reads, over the colours there are
 *											D. the compiler: the order of the file, the head, the brand, the knobs, the variants, the own css and the
 *											   shell of the bundle round it
 *											E. the brand: its own values and the theme's (M1 to M5) and what a value may be
 *											F. the store: save, save as, apply, a change of theme, the last state, a stylesheet that is somebody else's
 *											G. the preview: a token for the session that asked, for a time, the public bundle left as it is
 *											H. rights and names: no right, no answer; what a name, a path, a step, a colour and a css may be
 *											I. sizes, the feature off and on, and the time it takes
 *											J. the theme Nino: what is in its folder, that its middle is the reset, the order of its steps, its variants and its sizes
 *											L. the second part of the panel: the brand through its own action and its stamp, the own css, the name of an own theme, the
											   state a restore is asked on, a delete that would replace a stylesheet that is somebody else's
											K. the panel: its files, its words, what the API gives a screen - the pages, what an own theme is made of, the label of a
 *											   component, the time a token holds - the outline of a component in the preview, and the script over a stand-in of the page
 *
 *									Runs against an isolated sandbox directory with the kernel's own Basis; the themes are the two fixtures of
 *									tests/fixtures/themes, copied into the sandbox - Craft (probe), which stands the section on a soft surface,
 *									and Band (probe), which adds a band and a rule under the title. The feature is the one this file sits in.
 *
 *	Usage: php features/Themes/tests/themes-smoke.php
 *				 NINO_ROOT=../nino php features/Themes/tests/themes-smoke.php
 */

$root = getenv( 'NINO_ROOT' ) ?: dirname( __DIR__, 3 );
defined( 'NINO_FEATURES_DIR' ) === true || define( 'NINO_FEATURES_DIR', dirname( __DIR__, 2 ) );
require $root. '/tests/harness.php';

$featureDir	= dirname( __DIR__ );
$fixtures		= __DIR__. '/fixtures/themes';
$sandboxes	= [];
$numbers		= [];
$groupNames	= [ 'button', 'footer', 'header', 'image', 'section', 'text' ];

// The two things the site is drawn from: the bundle of the stylesheet and a page of components that stand where a theme works
const THEMES_BUNDLE = '/.cache/style.css';
const THEMES_PAGE = '[header-wrap][header-logo][header-nav][/header-wrap]'
	. '[section-wrap id="hero" variant="band" ground="dark" height="80"][section-row gap="3"][section-col variant="card" pad="2"][section-title text="A title" tag="h2" size="l"][section-subtitle text="A subtitle"][text text="Some words" size="s"][button text="Go" href="/go" tone="quiet" size="l"][/section-col][/section-row][/section-wrap]';

/**
 *	Boot a site the way \Nino\init() does: the modules it lists, its components and standard parts, the files its features
 *	name for the bundles, then the init() of every module. The themes of the sandbox are read from its own folder
 *
 *	@param		array 		&$appData			(reference) A sandbox's app data
 *	@param		array			$modules			The classes of /nino/modules
 *
 *	@return 	void
 */
function themesBoot( array &$appData, array $modules ): void {

	// What a project starts with: the kernel's own stylesheet and script in the two bundles
	$appData['/nino/html/assets'] = [ THEMES_BUNDLE => [ '/_nino/Nino.css' ], '/.cache/script.js' => [ '/_nino/Nino.js' ] ];
	$appData['/nino/modules'] = $modules;
	$directory = ninoSandboxDir( $appData ). '/themes';

	\Nino\Callbacks::registerCallback( $appData, \Nino\Modules\Themes\Reader::ROOTS, static function( array &$appData, array $roots ) use ( $directory ): array {
		return array_merge( $roots, [ $directory ] );
	} );

	\Nino\Html::init( $appData );
	\Nino\Features::registerComponents( $appData );
	\Nino\Features::registerParts( $appData );
	\Nino\Features::registerAssets( $appData );
	\Nino\Modules::callModules( $appData, 'init' );

	// The project's own stylesheet, where it has one: the last file of the bundle, in the layer project
	if( is_file( \Nino\Filesystem::path( $appData, '/assets/style.css' ) ) === true )
		\Nino\Html::addAsset( $appData, THEMES_BUNDLE, '/assets/style.css' );
}

/**
 *	A site as a sandbox of its own: Assets and Basis, and the feature where it is asked for, with the themes of the fixtures copied
 *	into it and a font of public/fonts/ for the theme that suggests one
 *
 *	@param		bool			$themes				Whether the feature is listed
 *
 *	@return 	array											$appData
 */
function themesSite( bool $themes = true ): array {

	global $root, $fixtures, $sandboxes;

	$appData = ninoSandbox( 'themes' );
	$sandboxes[] = ninoSandboxDir( $appData );

	// The root of the site is the checkout: the standard parts of Basis are files of the kernel below it
	$appData['./nino/filesystem/path'] = $root;
	\Nino\Filesystem::copyDir( $fixtures, ninoSandboxDir( $appData ). '/themes' );

	$faces = [];

	foreach( [ 400, 700 ] as $weight ) {
		$faces[] = [ 'weight' => $weight, 'style' => 'normal', 'file' => 'open-sans-'. $weight. '.woff2' ];
		\Nino\Filesystem::putFileContent( $appData, '/fonts/open-sans/open-sans-'. $weight. '.woff2', 'wOF2' );
	}

	\Nino\Filesystem::putFileContent( $appData, '/fonts/open-sans/font.json', [ 'name' => 'Open Sans', 'fallback' => 'sans-serif', 'faces' => $faces ] );

	themesBoot( $appData, array_merge( [ '\\Nino\\Modules\\Assets', '\\Nino\\Modules\\Basis' ], $themes === true ? [ '\\Nino\\Modules\\Themes' ] : [] ) );

	return $appData;
}

/**
 *	Run something that may take a draft away, and put the draft back: a request that asks for a token whose time is out removes its draft
 *
 *	@param		string		$folder				The folder of the draft
 *	@param		callable	$run					What is done; its answer is the answer
 *
 *	@return 	bool
 */
function themesKept( string $folder, callable $run ): bool {

	$copy = $folder. '.kept';
	\Nino\Filesystem::copyDir( $folder, $copy );
	$answer = $run();
	\Nino\Filesystem::removeDir( $folder );
	rename( $copy, $folder );

	return $answer;
}

/**
 *	The same project as another request: the files stay, everything a request builds is built again
 *
 *	@param		array			$old					The app data of the project
 *	@param		array			$modules			The classes of /nino/modules
 *
 *	@return 	array											$appData
 */
function themesReboot( array $old, array $modules ): array {

	$new = [ './nino/uid' => $old['./nino/uid'] ];
	\Nino\AppData::prepare( $new );

	// The files stay, and so does the account that is signed in
	foreach( [ './nino/filesystem/path', './nino/filesystem/configpath', './nino/filesystem/contentpath', './nino/filesystem/publicpath', './nino/locales/current', '/nino/locales/native', '/nino/locales/available', '/nino/dir', '/nino/install/texts', './nino/test/sandbox', '/nino/auth/user', './nino/auth/current' ] as $key )
		if( isset( $old[$key] ) === true )
			$new[$key] = $old[$key];

	themesBoot( $new, $modules );

	return $new;
}

/**
 *	One action of the API, as the workbench posts it
 *
 *	@param		array 		&$appData			(reference) A project
 *	@param		string		$action				eg. 'themes/apply'
 *	@param		array			$data
 *
 *	@return 	array											[ status, body ]
 */
function themesCall( array &$appData, string $action, array $data = [] ): array {

	// A change of an own theme says which state of it it was made on: the stamp open gives, unless the call names its own (null: none)
	if( in_array( $action, [ 'themes/save', 'themes/delete', 'themes/apply', 'themes/duplicate' ], true ) === true && array_key_exists( 'base', $data ) === false && str_starts_with( (string) ( $data['theme'] ?? '' ), 'own:' ) === true )
		$data['base'] = (string) ( ninoCall( $appData, [ \Nino\Admin\Admin::class, 'handlePost' ], [ 'theme' => $data['theme'] ], 'themes/open' )[1]['stamp'] ?? '' );

	// A change that posts the brand says which state of it it was made on: the stamp the list gives, unless the call names its own
	if( in_array( $action, [ 'themes/save', 'themes/save-as', 'themes/brand' ], true ) === true && array_key_exists( 'brand', $data ) === true && array_key_exists( 'brandBase', $data ) === false )
		$data['brandBase'] = (string) ( ninoCall( $appData, [ \Nino\Admin\Admin::class, 'handlePost' ], [], 'themes/list' )[1]['brandStamp'] ?? '' );

	// A restore says which state it was asked on: the stamp the store gives, unless the call names its own
	if( $action === 'themes/restore' && array_key_exists( 'base', $data ) === false )
		$data['base'] = \Nino\Modules\Themes\Store::stamp( $appData );

	[ $status, $body ] = ninoCall( $appData, [ \Nino\Admin\Admin::class, 'handlePost' ], $data, $action );

	return [ $status, $body ];
}

/**
 *	A mix as a person writes it: the page and the groups of a theme, the rest as it is given
 *
 *	@param		string		$name					The name of an own theme
 *	@param		array			$parts				group => part
 *	@param		string		$css					The own css
 *
 *	@return 	array
 */
function themesMix( string $name, array $parts, string $css = '' ): array {
	return [ 'name' => $name, 'parts' => $parts, 'css' => $css ];
}

/**
 *	The top level of a stylesheet: what stands in front of each block or statement, and what the block holds
 *
 *	@param		string		$css
 *
 *	@return 	array											[ [ prelude, body ], ... ]
 */
function themesTop( string $css ): array {

	$items = [];
	$depth = 0;
	$start = 0;
	$open = 0;
	$length = strlen( $css );

	for( $i = 0; $i < $length; $i++ ) {

		$c = $css[$i];

		if( $c === '/' && ( $css[$i + 1] ?? '' ) === '*' ) {
			$end = strpos( $css, '*/', $i + 2 );
			$i = $end === false ? $length : $end + 1;
		}
		else if( $c === '"' || $c === "'" ) {

			// A string ends at its quote, or in front of the line break that no string holds
			for( $i++; $i < $length && $css[$i] !== $c && strpos( "\r\n\f", $css[$i] ) === false; $i++ )
				if( $css[$i] === '\\' )
					$i++;

			if( $i < $length && $css[$i] !== $c )
				$i--;
		}
		else if( $c === '(' && $i >= 3 && strcasecmp( substr( $css, $i - 3, 3 ), 'url' ) === 0 && in_array( $css[$i + strspn( $css, " \t\r\n\f", $i + 1 ) + 1] ?? '', [ '"', "'" ], true ) === false ) {

			// url( without a quote: the bytes up to the ) are one token
			$end = strpos( $css, ')', $i );
			$i = $end === false ? $length : $end;
		}
		else if( $c === '\\' )
			$i++;
		else if( $c === '{' ) {

			if( $depth === 0 )
				$open = $i;

			$depth++;
		}
		else if( $c === '}' && $depth > 0 ) {

			$depth--;

			if( $depth === 0 ) {
				$items[] = [ trim( (string) preg_replace( '~/\*.*?\*/~s', '', substr( $css, $start, $open - $start ) ) ), substr( $css, $open + 1, $i - $open - 1 ) ];
				$start = $i + 1;
			}
		}
		else if( $c === ';' && $depth === 0 ) {
			$items[] = [ trim( (string) preg_replace( '~/\*.*?\*/~s', '', substr( $css, $start, $i - $start ) ) ), '' ];
			$start = $i + 1;
		}
	}

	return $items;
}

/**
 *	The shortest of a few runs, in milliseconds: what a machine that is busy with something else adds goes into the others
 *
 *	@param		callable	$run
 *	@param		int				$rounds
 *
 *	@return 	float
 */
function themesFastest( callable $run, int $rounds = 9 ): float {

	$best = INF;

	for( $round = 0; $round < $rounds; $round++ ) {

		$started = hrtime( true );
		$run();
		$best = min( $best, ( hrtime( true ) - $started ) / 1e6 );
	}

	return $best;
}

// ---------------------------------------------------------------------------------------------------------------------------------
echo "A. The feature\n";

$appData = ninoSandbox( 'themes-activate' );
$sandboxes[] = ninoSandboxDir( $appData );
$appData['./nino/filesystem/path'] = $root;
$appData['/nino/modules'] = [ '\\Nino\\Modules\\Basis' ];
\Nino\AppData::writeContentData( $appData, [ '/nino/modules', '/nino/locales/available', '/nino/locales/native' ] );

$manifest = \Nino\Features::manifest( $featureDir );
check( 'the manifest validates without a warning', is_array( $manifest ) === true && ninoWarnings() === [] );
check( 'it is the key themes, written for the kernel 2.0, with a data directory and nothing to require', is_array( $manifest ) === true && $manifest['key'] === 'themes' && $manifest['nino'] === '^2.0' && $manifest['data'] === [ '/data/theme' ] && $manifest['requires'] === [] );
check( 'activation lists the class and records the version', \Nino\Features::activate( $appData, 'themes' ) === true && in_array( '\\Nino\\Modules\\Themes', $appData['/nino/modules'], true ) === true
	&& \Nino\Features::get( $appData, 'themes' )['installed'] === $manifest['version'] );
// A constant not every system defines: the flag for braces in a glob is missing where the C library is musl (the php of an Alpine image), and a glob that names it is an error there
$needle = 'GLOB_'. 'BRACE';
$named = [];

foreach( new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( dirname( __DIR__ ), \FilesystemIterator::SKIP_DOTS ) ) as $file )
	if( $file->getExtension() === 'php' && str_contains( (string) file_get_contents( $file->getPathname() ), $needle ) === true )
		$named[] = substr( $file->getPathname(), strlen( dirname( __DIR__ ) ) + 1 );

check( 'no file of the feature names a glob flag that only some systems define'. ( $named === [] ? '' : ' - '. implode( ', ', $named ) ), $named === [] );

$panels = \Nino\Admin\Admin::panels( $appData );
check( 'the API is a panel of the workbench, with the right /_admin/themes/manage, and its fifteen actions are the ones the workbench dispatches', isset( $panels['themes'] ) === true && $panels['themes']['class'] === \Nino\Modules\Themes\Admin::class
	&& \Nino\Modules\Themes\Admin::perm() === '/_admin/themes/manage' && array_keys( \Nino\Modules\Themes\Admin::actions() ) === [ 'themes/list', 'themes/open', 'themes/preview', 'themes/thumb', 'themes/save', 'themes/save-as', 'themes/duplicate', 'themes/brand', 'themes/apply', 'themes/restore', 'themes/delete', 'themes/upload-check', 'themes/upload', 'themes/upload-drop', 'themes/remove' ]
	&& array_diff( array_keys( \Nino\Modules\Themes\Admin::actions() ), array_keys( \Nino\Admin\Admin::actions( $appData ) ) ) === [] );

$source = (string) file_get_contents( $featureDir. '/Admin/Admin.php' ). (string) file_get_contents( $featureDir. '/Store/Store.php' );
preg_match_all( "/'(themes_[a-z_]+)'/", $source, $found );
$codes = array_values( array_unique( $found[1] ) );
check( 'every failure a person can cause has a code, and the code its sentence in both interface languages, with the place of the param where it has one', count( $codes ) >= 10
	&& array_filter( $codes, static function( string $code ) use ( &$appData ): bool {

		foreach( [ 'en_US', 'de_DE' ] as $locale )
			if( trim( \Nino\Admin\Admin::textFills( $appData, '/features/Themes/text', $locale )['[[/_admin/error/'. $code. ']]'] ?? '' ) === '' )
				return true;

		return false;
	} ) === []
	&& str_contains( \Nino\Admin\Admin::textFills( $appData, '/features/Themes/text', 'en_US' )['[[/_admin/error/themes_unknown]]'], '%s' ) === true
	&& \Nino\Admin\Admin::textFills( $appData, '/features/Themes/text', 'en_US' )['[[/_admin/error/themes_unknown]]'] !== \Nino\Admin\Admin::textFills( $appData, '/features/Themes/text', 'de_DE' )['[[/_admin/error/themes_unknown]]'] );

check( 'deactivation takes the panel away and keeps the data', \Nino\Features::deactivate( $appData, 'themes' ) === true && isset( \Nino\Admin\Admin::panels( $appData )['themes'] ) === false );

// ---------------------------------------------------------------------------------------------------------------------------------
echo "\nB. The reader\n";

$appData = themesSite();
ninoUser( $appData, 'designer@example.com' );
$read = \Nino\Modules\Themes\Reader::read( $appData );

check( 'the two themes of the fixtures and the one the feature ships are read, by key, with their versions, and no folder is refused', array_keys( $read['themes'] ) === [ 'band-probe', 'craft-probe', 'nino' ] && $read['rejected'] === []
	&& $read['themes']['craft-probe']['version'] === '1.0.0' && $read['themes']['craft-probe']['name']['en_US'] === 'Craft (probe)' );
check( 'every part of both is there for its group - the page and the six of Basis - and every one holds', array_keys( $read['themes']['craft-probe']['parts'] ) === [ 'button', 'footer', 'header', 'image', 'page', 'section', 'text' ]
	&& array_keys( $read['themes']['band-probe']['parts'] ) === [ 'button', 'footer', 'header', 'image', 'page', 'section', 'text' ]
	&& array_filter( array_merge( $read['themes']['craft-probe']['parts'], $read['themes']['band-probe']['parts'] ), static fn( array $part ): bool => $part['ok'] !== true || $part['problem'] !== '' ) === [] );

$craft = $read['themes']['craft-probe']['parts']['section']['part'];
$band = $read['themes']['band-probe']['parts']['section']['part'];
check( 'the part of craft-probe for the section: the surface soft is the standard, a ladder from 0.6 to 3.5 rem, an additional knob for the shadow of the card', array_keys( $craft['variants']['section-wrap'] ) === [ 'soft' ] && $craft['standard'] === [ 'section-wrap' => 'soft' ]
	&& $craft['knobs']['section-wrap.space']['ladder'] === [ '0.6rem', '1.1rem', '1.75rem', '2.5rem', '3.5rem' ] && $craft['knobs']['section-col.x-shadow']['extra'] === true && $craft['knobs']['section-col.x-shadow']['token'] === '--section-col-x-shadow'
	&& $craft['knobs']['section-wrap.space']['extra'] === false && count( $craft['knobs'] ) === 6 );
check( 'the part of band-probe for the section: the band is a variant that is not the standard, a ladder from 0.9 to 4.5 rem, a rule under the title as an additional knob', array_keys( $band['variants']['section-wrap'] ) === [ 'band' ] && $band['standard'] === [ 'section-wrap' => 'regular' ]
	&& $band['knobs']['section-wrap.space']['ladder'] === [ '0.9rem', '1.5rem', '2.25rem', '3.25rem', '4.5rem' ] && $band['knobs']['section-title.x-rule']['token'] === '--section-title-x-rule' );

$components = \Nino\Components::groups( $appData )['section']['components'];
$checked = [];

foreach( [ 'craft-probe', 'band-probe' ] as $key )
	foreach( [ 'button', 'footer', 'header', 'image', 'section', 'text' ] as $group ) {

		$css = (string) @file_get_contents( $appData['./nino/test/sandbox'] . '/themes/'. $key. '/parts/'. $group. '/part.css' );
		$checked[] = \Nino\Components::partProblems( $css, \Nino\Components::groups( $appData )[$group]['components'] );
	}

check( 'each part of the two themes passes the rule of mixing of the kernel, asked of it as it is', count( $checked ) === 12 && array_filter( $checked, static fn( array $problems ): bool => $problems !== [] ) === [] && $components !== [] );
check( 'the page: seven knobs of sets of tokens, the density for small screens among them', array_keys( $read['themes']['band-probe']['parts']['page']['part']['knobs'] ) === [ 'size', 'width', 'space', 'radius', 'line', 'motion', 'density' ]
	&& $read['themes']['band-probe']['parts']['page']['part']['knobs']['density']['small'] === true && $read['themes']['band-probe']['parts']['page']['part']['knobs']['size']['small'] === false );

// the stamp: a read that finds the files as they were is the file, and a changed file is read again
$cache = $appData['./nino/test/sandbox']. '/private/data/theme/.cache/read.php';
check( 'what was read is kept in a file with a stamp', is_file( $cache ) === true && is_string( ( include $cache )['stamp'] ) === true );

$kept = include $cache;
$kept['read']['themes']['craft-probe']['version'] = '9.9.9';
\Nino\Filesystem::putFileContent( $appData, '/data/theme/.cache/read.php', $kept );
unset( $appData['./nino/themes/read'] );
$again = \Nino\Modules\Themes\Reader::read( $appData );
check( 'a request that finds the stamp as it was takes the kept read and reads no folder', $again['themes']['craft-probe']['version'] === '9.9.9' );

$sectionJson = $appData['./nino/test/sandbox']. '/themes/craft-probe/parts/section/part.json';
file_put_contents( $sectionJson, str_replace( 'Weiche Fläche', 'Weiche Fläche!', (string) file_get_contents( $sectionJson ) ) );
unset( $appData['./nino/themes/read'] );
check( 'a file that changed in size is read again', \Nino\Modules\Themes\Reader::read( $appData )['themes']['craft-probe']['version'] === '1.0.0' );

$readerFile = $featureDir. '/Reader/Reader.php';
$stamped = ( include $cache )['stamp'];
$mtime = filemtime( $readerFile );
touch( $readerFile, $mtime + 11 );
unset( $appData['./nino/themes/read'] );
\Nino\Modules\Themes\Reader::read( $appData );
$stampedLater = ( include $cache )['stamp'];
touch( $readerFile, $mtime );
check( 'the stamp of what was read has the time of the code that read it: an update of the reader that leaves the version as it was is read again', $stamped !== $stampedLater );
unset( $appData['./nino/themes/read'] );
\Nino\Modules\Themes\Reader::read( $appData );

$groups = \Nino\Components::groups( $appData );
$appData['./nino/register/groups']['section']['label'] = 'Another';
unset( $appData['./nino/themes/read'] );
check( 'and so is a read when the groups of the register are others', \Nino\Modules\Themes\Reader::read( $appData )['themes']['craft-probe']['parts']['section']['ok'] === true && is_array( include $cache ) === true );
$appData['./nino/register/groups']['section']['label'] = $groups['section']['label'];

// what is refused as a whole
$base = $appData['./nino/test/sandbox']. '/themes';
$put = static function( string $path, string $text ) use ( $base ): void {

	if( is_dir( dirname( $base. '/'. $path ) ) === false )
		mkdir( dirname( $base. '/'. $path ), 0755, true );

	file_put_contents( $base. '/'. $path, $text );
};
$theme = static fn( array $change = [] ): string => json_encode( array_merge( [ 'key' => 'x', 'version' => '1.0.0', 'name' => 'X', 'description' => 'X', 'brand' => [ 'tone' => 'cool' ] ], $change ) );
$marker = $appData['./nino/test/sandbox']. '/marker-php-was-run';

$put( 'broken-json/theme.json', '{ nope' );
$put( 'with-logo/theme.json', $theme( [ 'key' => 'with-logo', 'logo' => 'logo.svg' ] ) );
$put( 'logo-suggested/theme.json', $theme( [ 'key' => 'logo-suggested', 'brand' => [ 'logo' => 'logo.svg' ] ] ) );
$put( 'wrong-key/theme.json', $theme( [ 'key' => 'other' ] ) );
$put( 'Upper/theme.json', $theme( [ 'key' => 'Upper' ] ) );
$put( 'bad-version/theme.json', $theme( [ 'key' => 'bad-version', 'version' => '1.x' ] ) );
$put( 'no-name/theme.json', $theme( [ 'key' => 'no-name', 'name' => '' ] ) );
$put( 'bad-colour/theme.json', $theme( [ 'key' => 'bad-colour', 'brand' => [ 'color-1' => 'red' ] ] ) );
$put( 'bad-font/theme.json', $theme( [ 'key' => 'bad-font', 'brand' => [ 'font-title' => '../../etc' ] ] ) );
$put( 'unknown-brand-key/theme.json', $theme( [ 'key' => 'unknown-brand-key', 'brand' => [ 'accent' => '#123456' ] ] ) );
$put( 'bad-tone/theme.json', $theme( [ 'key' => 'bad-tone', 'brand' => [ 'tone' => 'hot' ] ] ) );
$put( 'duplicate/theme.json', $theme( [ 'key' => 'duplicate' ] ) );
$put( '../linked-target/theme.json', $theme( [ 'key' => 'linked' ] ) );
symlink( $appData['./nino/test/sandbox']. '/linked-target', $base. '/linked' );
$put( 'huge/theme.json', $theme( [ 'key' => 'huge', 'description' => str_repeat( 'x', 40000 ) ] ) );
$put( 'evil-php/theme.json', $theme( [ 'key' => 'evil-php' ] ) );
$put( 'evil-php/theme.php', '<?php file_put_contents( '. var_export( $marker, true ). ', "theme" );' );
$put( 'evil-php/parts/section/part.php', '<?php file_put_contents( '. var_export( $marker, true ). ', "part" );' );
$put( 'evil-php/parts/section/part.json', json_encode( [ 'contract' => 'basis@^1.0' ] ) );
$put( 'evil-php/parts/.hidden/part.json', '{}' );
$put( 'evil-php/parts/Section/part.json', '{}' );
$put( 'evil-php/index.php', '<?php file_put_contents( '. var_export( $marker, true ). ', "index" );' );
$put( 'leaky/theme.json', $theme( [ 'key' => 'leaky' ] ) );
$put( 'leaky/parts/section/part.json', json_encode( [ 'contract' => 'basis@^1.0' ] ) );
file_put_contents( $appData['./nino/test/sandbox']. '/outside.css', '.section-wrap { color: red; }' );
symlink( $appData['./nino/test/sandbox']. '/outside.css', $base. '/leaky/parts/section/part.css' );
$put( 'oversize/theme.json', $theme( [ 'key' => 'oversize' ] ) );
$put( 'oversize/parts/text/part.json', json_encode( [ 'contract' => 'basis@^1.0' ] ) );
$put( 'oversize/parts/text/part.css', ".text { color: var(--brand-ink); }\n". str_repeat( '/* x */', 12000 ) );
$put( 'nul/theme.json', $theme( [ 'key' => 'nul' ] ) );
$put( 'nul/parts/text/part.json', json_encode( [ 'contract' => 'basis@^1.0' ] ) );
$put( 'nul/parts/text/part.css', ".text { color: red; }\0" );
$put( 'binary/theme.json', $theme( [ 'key' => 'binary' ] ) );
$put( 'binary/parts/text/part.json', json_encode( [ 'contract' => 'basis@^1.0' ] ) );
$put( 'binary/parts/text/part.css', ".text { color: \xFF\xFE; }" );

unset( $appData['./nino/themes/read'] );
$read = \Nino\Modules\Themes\Reader::read( $appData );
$refused = array_map( 'basename', array_keys( $read['rejected'] ) );
sort( $refused );
check( 'a theme that is not what it should be is refused as a whole, each with its reason: no JSON, a key beside the five, a brand that suggests a key of the seven that is none or a value that does not fit, a key that is not the folder\'s, a version, a name, a link as the folder',
	$refused === [ 'Upper', 'bad-colour', 'bad-font', 'bad-tone', 'bad-version', 'broken-json', 'huge', 'linked', 'logo-suggested', 'no-name', 'unknown-brand-key', 'with-logo', 'wrong-key' ]
	&& array_filter( $read['rejected'], static fn( string $reason ): bool => trim( $reason ) === '' ) === [] && array_diff( array_keys( $read['themes'] ), [ 'band-probe', 'craft-probe', 'nino', 'duplicate', 'evil-php', 'leaky', 'nul', 'binary', 'oversize' ] ) === [] );
check( 'M4: a theme that brings a logo - as a key of its own or as a suggestion - is refused', in_array( 'with-logo', $refused, true ) === true && in_array( 'logo-suggested', $refused, true ) === true
	&& str_contains( (string) ( array_values( array_filter( $read['rejected'], static fn( string $reason, string $folder ): bool => basename( $folder ) === 'with-logo', ARRAY_FILTER_USE_BOTH ) )[0] ?? '' ), 'logo' ) === true );
check( 'the reasons say no local path', array_filter( $read['rejected'], static fn( string $reason ): bool => str_contains( $reason, $appData['./nino/test/sandbox'] ) === true ) === [] );

// a second folder with the key of another is refused, the first stands
$put( 'duplicate/theme.json', $theme( [ 'key' => 'duplicate' ] ) );
$roots = \Nino\Modules\Themes\Reader::roots( $appData );
check( 'the folders themes are read from: the one of the feature where it is there, and the ones the hook adds - each once', count( $roots ) === count( array_unique( $roots ) ) && in_array( realpath( $base ), $roots, true ) === true
	&& ( is_dir( $featureDir. '/themes' ) === false || in_array( realpath( $featureDir. '/themes' ), $roots, true ) === true ) );

check( 'a link as the folder of a theme is no theme, though what it points at is one', in_array( 'linked', $refused, true ) === true && is_dir( $appData['./nino/test/sandbox']. '/linked-target' ) === true );
check( 'no file of a theme is run: not the one beside theme.json, not the one in a part, not the one at its top', is_file( $marker ) === false && isset( $read['themes']['evil-php'] ) === true );
check( 'a folder of parts that is no slug of a group - a name with a capital, a hidden one - is not a part', array_keys( $read['themes']['evil-php']['parts'] ) === [ 'section' ] );
check( 'a part.css that is a link out of the theme rests with its reason, and what it holds is not read', $read['themes']['leaky']['parts']['section']['ok'] === false && str_contains( $read['themes']['leaky']['parts']['section']['problem'], 'part.css' ) === true
	&& str_contains( json_encode( $read ), 'outside' ) === false );
check( 'a part.css of more than 64 KB, one with a null byte and one that is no UTF-8 rest, and the other parts of those themes do not', $read['themes']['oversize']['parts']['text']['ok'] === false && $read['themes']['nul']['parts']['text']['ok'] === false && $read['themes']['binary']['parts']['text']['ok'] === false
	&& array_filter( [ $read['themes']['oversize']['parts']['text'], $read['themes']['nul']['parts']['text'], $read['themes']['binary']['parts']['text'] ], static fn( array $part ): bool => $part['problem'] === '' ) === [] );

// ---- a part that rests, with its reason; the group takes the standard part (Nr. 6)
$broken = [
	'button'	=> [ 'css' => ".button { color: red !important; }", 'json' => [ 'contract' => 'basis@^1.0' ] ],
	'footer'	=> [ 'css' => ".header-wrap { color: red; }", 'json' => [ 'contract' => 'basis@^1.0' ] ],
	'header'	=> [ 'css' => "@import url(other.css);\n.header-wrap { color: red; }", 'json' => [ 'contract' => 'basis@^1.0' ] ],
	'image'		=> [ 'css' => ".image { background: url(http://evil.example/x.png); }", 'json' => [ 'contract' => 'basis@^1.0' ] ],
	'section'	=> [ 'css' => '', 'json' => [ 'contract' => 'basis@^9.0' ] ],
	'text'		=> [ 'css' => '', 'json' => [ 'contract' => 'basis@^1.0', 'ladders' => [ 'text.nope' => [ '1px', '2px', '3px', '4px', '5px' ] ] ] ],
	'page'		=> [ 'css' => '', 'json' => [ 'contract' => 'kernel@^2.0', 'knobs' => [ 'ink' => [ 'label' => 'Ink', 'ladder' => [ [ '--brand-ink' => '#000' ], [], [], [], [] ] ] ] ] ],
];
$moreBroken = [
	'button'	=> [ 'css' => '', 'json' => [ 'contract' => 'collections@^1.0' ] ],
	'footer'	=> [ 'css' => '', 'json' => [ 'contract' => 'basis@^1.0', 'knobs' => array_fill_keys( [ 'footer-wrap.x-a', 'footer-wrap.x-b', 'footer-wrap.x-c' ], [ 'label' => 'A', 'ladder' => [ '1px', '2px', '3px', '4px', '5px' ] ] ) ] ],
	'header'	=> [ 'css' => '', 'json' => [ 'contract' => 'basis@^1.0', 'knobs' => [ 'header-wrap.glow' => [ 'label' => 'Glow', 'ladder' => [ '1px', '2px', '3px', '4px', '5px' ] ] ] ] ],
	'image'		=> [ 'css' => '', 'json' => [ 'contract' => 'basis@^1.0', 'ladders' => [ 'image.radius' => [ '0px', '1px', '2px', '3px', 'red; } body { background: url(x)' ] ] ] ],
	'section'	=> [ 'css' => '', 'json' => [ 'contract' => 'basis@^1.0', 'variants' => [ 'nope' => [ 'soft' => 'Soft' ] ] ] ],
	'text'		=> [ 'css' => '', 'json' => [ 'contract' => 'basis@^1.0', 'standard' => [ 'text' => 'nothing' ] ] ],
	'page'		=> [ 'css' => '', 'json' => [ 'contract' => 'kernel@^2.0', 'knobs' => [ 'size' => [ 'label' => 'Size', 'ladder' => [ [], [], [], [] ] ] ] ] ],
];

foreach( [ 'breaks' => $broken, 'breaks-more' => $moreBroken ] as $key => $parts ) {

	$put( $key. '/theme.json', $theme( [ 'key' => $key, 'brand' => [] ] ) );

	foreach( $parts as $group => $part ) {
		$put( $key. '/parts/'. $group. '/part.json', json_encode( $part['json'] ) );

		if( $part['css'] !== '' )
			$put( $key. '/parts/'. $group. '/part.css', $part['css'] );
	}
}

unset( $appData['./nino/themes/read'] );
$read = \Nino\Modules\Themes\Reader::read( $appData );
$rests = [];

foreach( [ 'breaks', 'breaks-more' ] as $key )
	foreach( $read['themes'][$key]['parts'] as $group => $part )
		$rests[$key. ' '. $group] = $part;

check( 'each of fourteen parts that break something rests, with a reason of its own, and the themes they are in are read all the same', count( $rests ) === 14 && array_filter( $rests, static fn( array $part ): bool => $part['ok'] !== false || $part['part'] !== null || trim( $part['problem'] ) === '' ) === []
	&& count( array_unique( array_column( $rests, 'problem' ) ) ) === 14 );
check( 'the reasons are the kernel\'s for the css - !important, a class of another group, @import, url() -, and the feature\'s own for the rest, none of them a local path or the text of the file',
	str_contains( $rests['breaks button']['problem'], '!important' ) === true && str_contains( $rests['breaks footer']['problem'], '.header-wrap' ) === true && str_contains( $rests['breaks header']['problem'], '@import' ) === true && str_contains( $rests['breaks image']['problem'], 'url()' ) === true
	&& str_contains( $rests['breaks section']['problem'], 'basis' ) === true && str_contains( $rests['breaks-more button']['problem'], 'collections' ) === true && array_filter( $rests, static fn( array $part ): bool => str_contains( $part['problem'], 'evil.example' ) === true || str_contains( $part['problem'], $appData['./nino/test/sandbox'] ) === true ) === [] );

// Every reason is a code and its params, which the panel says in its own words: the sentence is English, for the log
$reasons = array_merge( array_values( $read['rejected'] ), array_column( $rests, 'problem' ) );
$explained = array_map( static fn( string $reason ): ?array => \Nino\Modules\Themes\Reader::explain( $reason ), $reasons );
$samples = [];

foreach( \Nino\Modules\Themes\Reader::WHY as $code => $sentence )
	$samples[$code] = \Nino\Modules\Themes\Reader::explain( \Nino\Modules\Themes\Reader::why( $code, ...array_slice( [ 'p1', 'p2', 'p3' ], 0, substr_count( $sentence, '%s' ) ) ) );

$words = [ 'de_DE' => require $featureDir. '/text/de_DE.php', 'en_US' => require $featureDir. '/text/en_US.php' ];
$wordsOf = static fn( array $texts, string $code ): ?string => $texts['[[/_admin/themes/why/'. $code. ']]'] ?? null;

check( 'every reason of the themes that are read - the rejected ones and the parts that rest - is a code with its params, and the sentence is made of them anew',
	count( $reasons ) >= 20 && array_filter( $explained, static fn( ?array $why ): bool => $why === null ) === []
	&& array_filter( array_keys( $explained ), static fn( int $at ): bool => \Nino\Modules\Themes\Reader::why( $explained[$at]['code'], ...$explained[$at]['params'] ) !== $reasons[$at] ) === [] );
check( 'each sentence of the reader is told apart from every other one: it explains as its own code, with its params in order',
	array_keys( array_filter( $samples, static fn( array $why, string $code ): bool => $why['code'] === $code, ARRAY_FILTER_USE_BOTH ) ) === array_keys( \Nino\Modules\Themes\Reader::WHY ) );
check( 'each reason has words in both interface languages, with as many places for what goes in as the sentence has',
	array_filter( array_keys( \Nino\Modules\Themes\Reader::WHY ), static function( string $code ) use ( $words, $wordsOf ): bool {
		return $wordsOf( $words['de_DE'], $code ) === null || $wordsOf( $words['en_US'], $code ) === null || substr_count( (string) $wordsOf( $words['de_DE'], $code ), '%s' ) !== substr_count( (string) $wordsOf( $words['en_US'], $code ), '%s' );
	} ) === [] && count( array_filter( array_keys( $words['en_US'] ), static fn( string $key ): bool => str_starts_with( $key, '[[/_admin/themes/why/' ) ) ) === count( \Nino\Modules\Themes\Reader::WHY ) );

$mix = \Nino\Modules\Themes\Mix::clean( $appData, [ 'parts' => array_fill_keys( [ 'button', 'footer', 'header', 'image', 'section', 'text', 'page' ], [ 'from' => 'breaks@1.0.0' ] ) ], false );
$standard = \Nino\Modules\Themes\Compiler::compile( $appData, [ 'parts' => [] ], \Nino\Modules\Themes\Brand::clean( $appData, [] ) );
$built = is_array( $mix ) === true ? \Nino\Modules\Themes\Compiler::compile( $appData, $mix, \Nino\Modules\Themes\Brand::clean( $appData, [] ) ) : [ 'css' => '', 'plan' => [ 'groups' => [], 'page' => [ 'problem' => '' ] ] ];
check( 'Nr. 6: a theme whose parts all break is a mix like any other, each group says why its part rests, and the stylesheet is the one of the standard parts', is_array( $mix ) === true && $built['css'] === $standard['css']
	&& array_filter( $built['plan']['groups'], static fn( array $group ): bool => $group['part'] !== null || trim( $group['problem'] ) === '' ) === [] && trim( $built['plan']['page']['problem'] ) !== ''
	&& str_contains( $built['css'], 'evil' ) === false && str_contains( $built['css'], '!important' ) === false && str_contains( $built['css'], 'url(' ) === false );

// what the kernel accepts, the feature accepts: the rule of mixing is the kernel's, and Themes forbids no css of its own
$lenient = [
	'.text * { color: inherit; }',
	'.text { content: "a text"; }',
	'@keyframes some-name { from { opacity: 0 } to { opacity: 1 } }',
	'.text ~ * { margin: 0; }',
	':root { color: red; }',
	'@font-face { font-family: "Own"; src: local("Own"); }',
];
$accepted = [];

foreach( $lenient as $index => $css ) {

	$problems = \Nino\Components::partProblems( $css, \Nino\Components::groups( $appData )['text']['components'] );
	$accepted[$index] = $problems === [];

	$put( 'lenient-'. $index. '/theme.json', $theme( [ 'key' => 'lenient-'. $index ] ) );
	$put( 'lenient-'. $index. '/parts/text/part.json', json_encode( [ 'contract' => 'basis@^1.0' ] ) );
	$put( 'lenient-'. $index. '/parts/text/part.css', $css );
}

unset( $appData['./nino/themes/read'] );
$read = \Nino\Modules\Themes\Reader::read( $appData );
$lenientParts = array_map( static fn( int $index ): bool => $read['themes']['lenient-'. $index]['parts']['text']['ok'], array_keys( $lenient ) );
check( 'what the rule of the kernel lets through is a part that holds: Themes adds no ban on css of its own (six forms the kernel accepts)', array_unique( $accepted ) === [ true ] && array_unique( $lenientParts ) === [ true ] && count( $lenient ) === 6 );

// a folder `parts` that is a link; a step whose parentheses do not stand as pairs; a step of 0 with no unit is the author's
$put( 'parts-linked/theme.json', $theme( [ 'key' => 'parts-linked' ] ) );
mkdir( $appData['./nino/test/sandbox']. '/parts-elsewhere/section', 0755, true );
file_put_contents( $appData['./nino/test/sandbox']. '/parts-elsewhere/section/part.json', json_encode( [ 'contract' => 'basis@^1.0' ] ) );
symlink( $appData['./nino/test/sandbox']. '/parts-elsewhere', $base. '/parts-linked/parts' );
$put( 'open-paren/theme.json', $theme( [ 'key' => 'open-paren' ] ) );
$put( 'open-paren/parts/section/part.json', json_encode( [ 'contract' => 'basis@^1.0', 'ladders' => [ 'section-wrap.space' => [ 'calc(1px', '2px', '3px', '4px', '5px' ] ] ] ) );
$put( 'open-paren/parts/page/part.json', json_encode( [ 'contract' => 'kernel@^2.0', 'knobs' => [ 'size' => [ 'label' => 'Size', 'ladder' => [ [ '--page-size' => 'calc(1px' ], [], [], [], [] ] ] ] ] ) );
$put( 'close-paren/theme.json', $theme( [ 'key' => 'close-paren' ] ) );
$put( 'close-paren/parts/section/part.json', json_encode( [ 'contract' => 'basis@^1.0', 'ladders' => [ 'section-wrap.space' => [ '1px)', '2px', '3px', '4px', '5px' ] ] ] ) );
$put( 'zero/theme.json', $theme( [ 'key' => 'zero' ] ) );
$put( 'zero/parts/section/part.json', json_encode( [ 'contract' => 'basis@^1.0', 'ladders' => [ 'section-wrap.space' => [ '0', '1px', 'calc( (1px + 2px) * 2 )', '4px', '5px' ] ] ] ) );
unset( $appData['./nino/themes/read'] );
$read = \Nino\Modules\Themes\Reader::read( $appData );
check( 'a folder of parts that is a link brings no part', isset( $read['themes']['parts-linked'] ) === true && $read['themes']['parts-linked']['parts'] === [] );
check( 'a step whose parentheses do not stand as pairs makes the part rest, with a reason: an open one in a ladder of a group and of the page, a closed one that was never opened', array_filter( [ $read['themes']['open-paren']['parts']['section'], $read['themes']['open-paren']['parts']['page'], $read['themes']['close-paren']['parts']['section'] ], static fn( array $part ): bool => $part['ok'] !== false || trim( $part['problem'] ) === '' ) === [] );
check( 'a step of 0 with no unit and a step with parentheses in pairs are the author\'s: the part holds', $read['themes']['zero']['parts']['section']['ok'] === true );
foreach( [ 'parts-linked', 'open-paren', 'close-paren', 'zero' ] as $folder ) {

	if( is_link( $base. '/'. $folder. '/parts' ) === true )
		unlink( $base. '/'. $folder. '/parts' );

	\Nino\Filesystem::removeDir( $base. '/'. $folder );
}

// the theme of the sandbox stays two, with the problems and the lenient ones named; the two fixtures are what the rest reads
$put( 'duplicate/theme.json', $theme( [ 'key' => 'duplicate' ] ) );
foreach( array_merge( [ 'broken-json', 'with-logo', 'logo-suggested', 'wrong-key', 'Upper', 'bad-version', 'no-name', 'bad-colour', 'bad-font', 'bad-tone', 'duplicate', 'linked', 'huge', 'evil-php', 'leaky', 'oversize', 'nul', 'binary', 'unknown-brand-key', 'breaks', 'breaks-more' ], array_map( static fn( int $index ): string => 'lenient-'. $index, array_keys( $lenient ) ) ) as $folder ) {

	if( is_link( $base. '/'. $folder ) === true )
		unlink( $base. '/'. $folder );
	else if( is_dir( $base. '/'. $folder ) === true )
		\Nino\Filesystem::removeDir( $base. '/'. $folder );
}

\Nino\Filesystem::removeDir( $appData['./nino/test/sandbox']. '/linked-target' );
check( 'the folders of the themes are two again', array_map( 'basename', glob( $base. '/*', GLOB_ONLYDIR ) ) === [ 'band-probe', 'craft-probe' ] );
unset( $appData['./nino/themes/read'] );

// ---------------------------------------------------------------------------------------------------------------------------------
echo "\nC. The palette\n";

use Nino\Modules\Themes\Palette;

$pairs = static fn( string $color, string $tone ): array => array_map( static fn( float $ratio ): float => round( $ratio, 2 ), Palette::pairs( Palette::make( $color, '#2b2622', $tone ), $color, '#2b2622' ) );
check( 'the palette of the blue of Nino.css, neutral: the eight pairs and the contrast of each pair a part reads, as worked out', $pairs( '#1c5db8', 'neutral' ) === [
	'light ink/base' => 16.49, 'light quiet/base' => 6.01, 'light accent/base' => 6.35, 'light ink/alt' => 14.95, 'light quiet/alt' => 5.45, 'light accent/alt' => 5.76, 'light ink/tint' => 14.22, 'light quiet/tint' => 5.19, 'light accent/tint' => 5.48, 'light button' => 6.35,
	'dark ink/base' => 15.0, 'dark quiet/base' => 8.28, 'dark accent/base' => 8.32, 'dark ink/alt' => 13.75, 'dark quiet/alt' => 7.59, 'dark accent/alt' => 7.63, 'dark ink/tint' => 12.71, 'dark quiet/tint' => 7.01, 'dark accent/tint' => 7.05,
	'dark ink/picture' => 8.44, 'dark quiet/picture' => 4.66, 'dark accent/picture' => 4.68, 'dark button' => 9.48, 'primary' => 6.35, 'second' => 14.97 ] );
check( 'a brown, warm: the accent is the colour where it holds on every ground, lighter in the dark mode where it does not', Palette::make( '#9a4b1d', '#2b2622', 'warm' )['light']['accent'] === '#9a4b1d' && Palette::make( '#9a4b1d', '#2b2622', 'warm' )['dark']['accent'] === '#ef986c'
	&& $pairs( '#9a4b1d', 'warm' )['dark accent/picture'] === 4.63 && $pairs( '#9a4b1d', 'warm' )['primary'] === 6.18 );
check( 'a yellow, cool: the colour written on it is black, its accent in the light mode is its hue in the lightness that reads, in the dark mode it is itself', Palette::make( '#ffd400', '#2b2622', 'cool' )['on-color-1'] === '#000000' && Palette::make( '#ffd400', '#2b2622', 'cool' )['light']['accent'] === '#7f6904'
	&& Palette::make( '#ffd400', '#2b2622', 'cool' )['dark']['accent'] === '#ffd400' && $pairs( '#ffd400', 'cool' )['primary'] === 14.67 );

mt_srand( 20261116 );
$colours = [ '#ffffff', '#000000', '#777777', '#00ff00', '#ffd400', '#00c8ff', '#ff6a00', '#1c5db8' ];

for( $i = 0; $i < 150; $i++ )
	$colours[] = sprintf( '#%06x', mt_rand( 0, 0xffffff ) );

$lowest = [];
$made = 0;
$started = hrtime( true );

foreach( $colours as $colour )
	foreach( array_keys( Palette::TONES ) as $tone ) {

		$made++;

		foreach( Palette::pairs( Palette::make( $colour, '#123456', $tone ), $colour, '#123456' ) as $pair => $ratio )
			$lowest[$pair] = min( $lowest[$pair] ?? INF, $ratio );
	}

$numbers['palette'] = ( hrtime( true ) - $started ) / 1e6 / $made;
check( 'over '. $made. ' palettes - '. count( $colours ). ' colours, three tones - the ink holds 7 : 1 on every ground, the quiet ink and the accent 4.5 : 1, in both modes and in the dark one on the picture under the veil',
	$made === 3 * count( $colours ) && count( $lowest ) === 25 && array_filter( $lowest, static fn( float $ratio, string $pair ): bool => $ratio < ( str_contains( $pair, ' ink/' ) === true ? 7.0 : 4.5 ) && $pair !== 'primary' && $pair !== 'second', ARRAY_FILTER_USE_BOTH ) === [] );
check( 'the colour written on a colour is the better of white and black, and never under the square root of 21', $lowest['primary'] >= 4.58 && $lowest['second'] >= 4.58 && $lowest['light button'] >= 4.5 && Palette::on( '#ffffff' ) === '#000000' && Palette::on( '#000000' ) === '#ffffff' && Palette::on( '#777777' ) === '#000000' );
check( 'the contrast is that of WCAG 2: black on white is 21 : 1, a colour on itself 1 : 1', round( Palette::contrast( '#000000', '#ffffff' ), 3 ) === 21.0 && Palette::contrast( '#123456', '#123456' ) === 1.0 );
check( 'a colour is six hexadecimal digits and is written in lower case', Palette::color( '#9A4B1D' ) === '#9a4b1d' && Palette::color( '#fff' ) === null && Palette::color( 'red' ) === null && Palette::color( '#12345g' ) === null && Palette::color( '#123456; }' ) === null && Palette::color( 123456 ) === null && Palette::color( null ) === null );

// ---------------------------------------------------------------------------------------------------------------------------------
echo "\nD. The compiler\n";

use Nino\Modules\Themes\Brand;
use Nino\Modules\Themes\Compiler;
use Nino\Modules\Themes\Mix;

$site = themesSite();
$none = Brand::clean( $site, [] );
$craftMix = Mix::clean( $site, Mix::of( $site, 'craft-probe' ), false );
$bandMix = Mix::clean( $site, Mix::of( $site, 'band-probe' ), false );
$craft = Compiler::compile( $site, $craftMix, $none );
$css = $craft['css'];

check( 'both themes come out as mixes that are clean, and the page is the first of their parts', is_array( $craftMix ) === true && is_array( $bandMix ) === true && array_keys( $craftMix['parts'] ) === [ 'button', 'footer', 'header', 'image', 'page', 'section', 'text' ] );
$at = static fn( string $text ): int => (int) strpos( $css, $text );
check( 'the file is written in this order: the head, the fonts, the brand, the page, the knobs of the groups, the parts, and the own css last', $at( '/* nino-themes ' ) === 0 && $at( '@font-face' ) > 0 && $at( '@font-face' ) < $at( '--brand-scheme' ) && $at( '--brand-scheme' ) < $at( '--page-size' )
	&& $at( '--page-size' ) < $at( '--button-space' ) && $at( '--button-space' ) < $at( '/* button: the part of craft-probe 1.0.0 */' ) && $at( '/* button: the part of craft-probe 1.0.0 */' ) < $at( '/* section: the part of craft-probe 1.0.0 */' ) );

$withOwn = Compiler::compile( $site, Mix::clean( $site, [ 'css' => ".own-marker { color: red; }\r\n.second { color: blue; }" ] + $craftMix, false ), $none )['css'];
check( 'the own css of the developer stands after everything else, once, as it was written but for its line endings, and under a comment of its own', str_ends_with( $withOwn, "/* The own css of the theme */\n.own-marker { color: red; }\n.second { color: blue; }\n" ) === true
	&& substr_count( $withOwn, '.own-marker' ) === 1 && strpos( $withOwn, '.own-marker' ) > strpos( $withOwn, '/* section: the part' ) && str_contains( $css, 'The own css' ) === false );

$first = strtok( $css, "\n" );
$rest = substr( $css, strlen( $first ) + 1 );
check( 'the head is the first line and names the sha256 of everything after it; the compiler knows its own file by it', preg_match( '/^\/\* nino-themes ([0-9a-f]{64}) - /', $first, $head ) === 1 && $head[1] === hash( 'sha256', $rest ) && Compiler::isOwn( $css ) === true && Compiler::stamp( $css ) === $head[1] );
check( 'a file of the compiler of which one byte was changed, or whose head is not there, or whose head is another\'s, is nobody\'s of the compiler', Compiler::isOwn( substr_replace( $css, 'X', strlen( $css ) - 5, 1 ) ) === false && Compiler::isOwn( $rest ) === false
	&& Compiler::isOwn( '' ) === false && Compiler::isOwn( preg_replace( '/[0-9a-f]{64}/', str_repeat( '0', 64 ), $css, 1 ) ) === false && Compiler::isOwn( "/* nino-themes */\n". $rest ) === false && Compiler::stamp( $rest ) === '' );
check( 'a build is a function of what it is made from: the same mix and brand come out as the same bytes, another brand does not', Compiler::compile( $site, $craftMix, $none )['css'] === $css && Compiler::compile( $site, $craftMix, Brand::clean( $site, [ 'color-1' => [ 'own' => true, 'value' => '#9a4b1d' ] ] ) )['css'] !== $css );

$pairsInCss = [];
preg_match_all( '/--brand-(ground-base|ground-alt|ground-tint|ink-base|ink-quiet-base|line-base|accent-base|on-accent-base): light-dark\( (#[0-9a-f]{6}), (#[0-9a-f]{6}) \);/', $css, $found, PREG_SET_ORDER );

foreach( $found as $line )
	$pairsInCss[$line[1]] = [ $line[2], $line[3] ];

$palette = Palette::make( '#1c5db8', '#17233a', 'warm' );
check( 'the brand: eight light-dark() pairs, as the palette of the brand works them out - the tone of the grounds is the suggestion of the theme, warm', count( $pairsInCss ) === 8 && $pairsInCss['ground-alt'] === [ $palette['light']['alt'], $palette['dark']['alt'] ] && $pairsInCss['accent-base'] === [ $palette['light']['accent'], $palette['dark']['accent'] ]
	&& $pairsInCss['on-accent-base'] === [ $palette['light']['on'], $palette['dark']['on'] ] && $pairsInCss['ink-base'] === [ $palette['light']['ink'], $palette['dark']['ink'] ] && str_contains( $css, '--brand-on-color-1: '. $palette['on-color-1'] ) === true && str_contains( $css, '--brand-on-color-2: '. $palette['on-color-2'] ) === true );
check( 'the mode: automatic, light and dark are the three values of --brand-scheme', str_contains( $css, '--brand-scheme: light dark;' ) === true && str_contains( Compiler::compile( $site, $craftMix, Brand::clean( $site, [ 'mode' => [ 'own' => true, 'value' => 'light' ] ] ) )['css'], '--brand-scheme: light;' ) === true
	&& str_contains( Compiler::compile( $site, $craftMix, Brand::clean( $site, [ 'mode' => [ 'own' => true, 'value' => 'dark' ] ] ) )['css'], '--brand-scheme: dark;' ) === true );

check( 'the font a theme suggests is a folder of the project: @font-face for each of its faces, from the site and from no other host, and the stack after it', substr_count( $css, '@font-face' ) === 2 && str_contains( $css, 'src: url("[[/nino/public]]/fonts/open-sans/open-sans-400.woff2") format("woff2")' ) === true
	&& str_contains( $css, '--brand-font-title: "Open Sans", system-ui, sans-serif;' ) === true && str_contains( $css, '--brand-font-text: system-ui' ) === true && str_contains( $css, '--brand-font-button: "Open Sans"' ) === true
	&& preg_match( '#(https?:)?//[a-z]#i', $css ) !== 1 && substr_count( $css, 'font-display: swap' ) === 2 );

// the knobs
$steps = static function( array $values ) use ( &$site, $none ): string {
	return Compiler::compile( $site, Mix::clean( $site, [ 'parts' => [ 'section' => [ 'from' => 'craft-probe', 'values' => $values ], 'page' => [ 'from' => 'craft-probe' ] ] ], false ), $none )['css'];
};
check( 'a knob is on its middle step where nothing is said, and the ladder is the part\'s: the section of craft has 1.75 rem there', str_contains( $css, '--section-wrap-space: 1.75rem;' ) === true && str_contains( $css, '--section-row-gap: var(--page-space-4);' ) === true );
check( 'Nr. 3: step +2 is the top of the ladder of the part (3.5 rem for craft, 4.5 for band) and -2 the bottom (0.6 rem), whatever the ladder is',
	str_contains( $steps( [ 'section-wrap.space' => 2 ] ), '--section-wrap-space: 3.5rem;' ) === true && str_contains( $steps( [ 'section-wrap.space' => -2 ] ), '--section-wrap-space: 0.6rem;' ) === true && str_contains( $steps( [ 'section-wrap.space' => 1 ] ), '--section-wrap-space: 2.5rem;' ) === true
	&& str_contains( Compiler::compile( $site, Mix::clean( $site, [ 'parts' => [ 'section' => [ 'from' => 'band-probe', 'values' => [ 'section-wrap.space' => 2 ] ] ] ], false ), $none )['css'], '--section-wrap-space: 4.5rem;' ) === true );
check( 'the step of a knob that belongs to the part - an additional one - is a token of its component, set in :root, and the ladder that is the group\'s own stays where the part brings none', str_contains( $steps( [ 'section-col.x-shadow' => 2 ] ), '--section-col-x-shadow: 0 16px 40px rgb(0 0 0 / .18);' ) === true
	&& str_contains( $steps( [ 'section-col.radius' => 2 ] ), '--section-col-radius: calc( var(--page-radius) * 4 );' ) === true && str_contains( $steps( [ 'section-col.pad' => -1 ] ), '--section-col-pad: var(--page-space-3);' ) === true );
check( 'the steps of a knob the part has not are dropped, and so are a step of zero and a step of a knob of the same name from another part - they come back where the part does',
	Mix::clean( $site, [ 'parts' => [ 'section' => [ 'from' => 'craft-probe', 'values' => [ 'section-title.x-rule' => 2, 'section-wrap.space' => 2, 'section-row.gap' => 0, 'nope.nope' => 1, 'section-col.x-shadow' => -1 ] ] ] ], false )['parts']['section']['values'] === [ 'section-col.x-shadow' => -1, 'section-wrap.space' => 2 ]
	&& Mix::clean( $site, [ 'parts' => [ 'section' => [ 'from' => 'band-probe', 'values' => [ 'section-title.x-rule' => 2, 'section-col.x-shadow' => -1 ] ] ] ], false )['parts']['section']['values'] === [ 'section-title.x-rule' => 2 ] );

$page = Mix::clean( $site, [ 'parts' => [ 'page' => [ 'from' => 'band-probe', 'values' => [ 'radius' => 2, 'density' => -2, 'space' => 1, 'width' => -2 ] ] ] ], false );
$pageCss = Compiler::compile( $site, $page, $none )['css'];
check( 'the page: each knob writes the five tokens of its step; the density is a query for small screens, and where it is on its middle step there is none', str_contains( $pageCss, '--page-radius: 1.25rem;' ) === true && str_contains( $pageCss, '--page-width: 60rem;' ) === true && str_contains( $pageCss, '--page-space-6: 5rem;' ) === true
	&& preg_match( '/@media \(max-width: 767\.98px\) \{\n\t:root \{\n\t\t--page-space-5: calc\( var\(--page-space-4\) \* 1\.1 \);\n\t\t--page-space-6: calc\( var\(--page-space-5\) \* 1\.1 \);\n\t\}\n\}/', $pageCss ) === 1
	&& str_contains( Compiler::compile( $site, Mix::clean( $site, [ 'parts' => [ 'page' => [ 'from' => 'band-probe' ] ] ], false ), $none )['css'], '@media (max-width' ) === false );
$reduce = "@media (prefers-reduced-motion: reduce) {\n\t:root {\n\t\t--page-motion: 0s;\n\t}\n}";
check( 'a theme that sets the duration of a movement keeps what a visitor asked for: the reset\'s rule for less motion is written again after the page, in the layer of the theme that would overrule it, once, and where no knob of the page sets the duration there is none',
	substr_count( $css, $reduce ) === 1 && strpos( $css, $reduce ) > strpos( $css, '--page-motion: .15s;' ) && substr_count( $pageCss, $reduce ) === 1 && str_contains( Compiler::compile( $site, [ 'parts' => [ 'section' => [ 'from' => 'craft-probe' ] ] ], $none )['css'], 'prefers-reduced-motion' ) === false
	&& substr_count( file_get_contents( $root. '/_nino/Nino.css' ), '@media' ) === 1 );
check( 'a mix without a part for the page leaves the tokens of the page to the reset: no token of the page is written', str_contains( Compiler::compile( $site, Mix::clean( $site, [ 'parts' => [ 'section' => [ 'from' => 'craft-probe' ] ] ], false ), $none )['css'], '--page-' ) === false || str_contains( Compiler::compile( $site, Mix::clean( $site, [ 'parts' => [ 'section' => [ 'from' => 'craft-probe' ] ] ], false ), $none )['css'], '--page-size' ) === false );

// the variants
// The label a fixture's part gives a variant: what the part file says, as the register is to be told it
$partLabel = static fn( string $theme, string $group, string $component, string $variant ): mixed => json_decode( (string) file_get_contents( $fixtures. '/'. $theme. '/parts/'. $group. '/part.json' ), true )['variants'][$component][$variant];
check( 'Nr. 1: the variants a part adds, the one that stands where a page names none and the label each has in the part - the locale => text map as the part gives it: craft - the stacked header and the soft section -, band - the bar and the band, the regular one standing', $craft['variants'] === [ 'header-wrap' => [ [ 'stacked' ], 'stacked', [ 'stacked' => $partLabel( 'craft-probe', 'header', 'header-wrap', 'stacked' ) ] ], 'section-wrap' => [ [ 'soft' ], 'soft', [ 'soft' => $partLabel( 'craft-probe', 'section', 'section-wrap', 'soft' ) ] ] ]
	&& Compiler::compile( $site, $bandMix, $none )['variants'] === [ 'header-wrap' => [ [ 'bar' ], 'regular', [ 'bar' => $partLabel( 'band-probe', 'header', 'header-wrap', 'bar' ) ] ], 'section-wrap' => [ [ 'band' ], 'regular', [ 'band' => $partLabel( 'band-probe', 'section', 'section-wrap', 'band' ) ] ] ] );
check( 'a mix chooses the standard variant among those the part has, and a variant it does not have is not kept', Compiler::compile( $site, Mix::clean( $site, [ 'parts' => [ 'section' => [ 'from' => 'craft-probe', 'standard' => [ 'section-wrap' => 'regular' ] ] ] ], false ), $none )['variants'] === [ 'section-wrap' => [ [ 'soft' ], 'regular', [ 'soft' => $partLabel( 'craft-probe', 'section', 'section-wrap', 'soft' ) ] ] ]
	&& Mix::clean( $site, [ 'parts' => [ 'section' => [ 'from' => 'craft-probe', 'standard' => [ 'section-wrap' => 'band', 'section-col' => 'card' ] ] ] ], false )['parts']['section']['standard'] === [ 'section-col' => 'card' ] );

check( 'a standard variant that is one the schema names - the card of a column - is told as it is, with no variant added', Compiler::compile( $site, Mix::clean( $site, [ 'parts' => [ 'section' => [ 'from' => 'band-probe', 'standard' => [ 'section-col' => 'card' ] ] ] ], false ), Brand::clean( $site, [] ) )['variants']['section-col'] === [ [], 'card', [] ] );
check( 'where every group is the standard part, the stylesheet is the brand and the knobs of the groups at their middle steps, and no variant is told', Compiler::compile( $site, [ 'parts' => [] ], $none )['variants'] === [] && str_contains( Compiler::compile( $site, [ 'parts' => [] ], $none )['css'], '/* section: the part of' ) === false );

// the shell of the bundle round the own css
$hostile = [
	'a brace that closes the layer'							=> ".hostile-a { color: red; }\n}\n.escaped-a { color: red; }\n}\n@layer project { .escaped-a2 { color: red; } }",
	'a block that is never closed'							=> ".hostile-b { color: red;\n@media print { .x { color: red; }",
	'a comment that never ends'									=> ".hostile-c { color: red; }\n/* and then the rest of the world",
	'a string that ends at a line'							=> ".hostile-d { content: \"open\n}\n.escaped-d { color: red; }",
	'a string that ends with the source'				=> ".hostile-d { content: \"open",
	'a backslash at the end'										=> ".hostile-e { color: red; }\n\\",
	'an unquoted url that never ends'						=> ".hostile-f { background: url(open.png; }\n}\n.escaped-f { color: red; }",
	'a closing tag of the html that holds it'		=> ".hostile-g::before { content: '</style><script>alert(1)</script>'; }",
];
\Nino\Filesystem::putFileContent( $site, '/assets/style.css', ".project-marker { color: green; }\n" );
$project = \Nino\Filesystem::path( $site, '/assets/style.css' );
$layers = [];

foreach( $hostile as $what => $text ) {

	$mix = Mix::clean( $site, [ 'css' => $text ] + $craftMix, false );
	$built = Compiler::compile( $site, $mix, $none );
	\Nino\Filesystem::putFileContent( $site, '/assets/theme.css', $built['css'] );
	$again = themesReboot( $site, [ '\\Nino\\Modules\\Assets', '\\Nino\\Modules\\Basis', '\\Nino\\Modules\\Themes' ] );
	$bundle = \Nino\Modules\Assets::stylesheet( $again, THEMES_BUNDLE );
	$top = themesTop( $bundle );
	$names = array_map( static fn( array $item ): string => $item[0], $top );
	$themeBlocks = array_filter( $top, static fn( array $item ): bool => $item[0] === '@layer nino.theme' );
	$projectBlocks = array_filter( $top, static fn( array $item ): bool => $item[0] === '@layer project' && str_contains( $item[1], '.project-marker' ) === true );
	$inTheme = array_values( $themeBlocks )[0][1] ?? '';

	$layers[$what] = count( array_filter( $names, static fn( string $name ): bool => $name === '@layer nino.reset, nino.components, nino.theme, project' || preg_match( '/^@layer (nino\.reset|nino\.components|nino\.theme|project)$/D', $name ) === 1 ) ) === count( $names ) && $names[1] === '@layer nino.reset'
		&& count( $themeBlocks ) === 1 && count( $projectBlocks ) === 1 && preg_match( '/hostile-[a-g]/', $inTheme ) === 1
		&& preg_match( '/\.escaped-[a-f]/', implode( "\n", array_map( static fn( array $item ): string => $item[0] === '@layer nino.theme' ? '' : $item[0]. $item[1], $top ) ) ) !== 1
		&& str_contains( $bundle, '.project-marker' ) === true && array_search( '@layer nino.theme', $names, true ) < array_search( '@layer project', $names, true );
}

$ignored = ninoWarnings();
check( 'the shell of the bundle holds whatever the own css is: with each of eight hostile texts the bundle is the four layers, the theme stands once and holds all of it, what the text closes or opens is the theme\'s and not the layers\' after it, and the css of the project stays in the layer project',
	count( array_filter( $layers ) ) === 8 );
check( 'the kernel has nothing to repair in the theme: the compiler closes what the own css leaves open, so that the bundle says nothing of it at any build', array_filter( $ignored, static fn( string $warning ): bool => str_contains( $warning, 'Assets' ) === true ) === [] );
unlink( $project );
unlink( ninoSandboxDir( $site ). '/private/assets/theme.css' );

// ---------------------------------------------------------------------------------------------------------------------------------
echo "\nE. The brand\n";

$site = themesSite();
$own = static fn( string $key, mixed $value ): array => [ $key => [ 'own' => true, 'value' => $value ] ];
$clean = Brand::clean( $site, $own( 'color-1', '#9A4B1D' ) );
check( 'a brand is seven values, each the project\'s own or the theme\'s: what is not said is the theme\'s, and a colour is kept in lower case', is_array( $clean ) === true && array_keys( $clean ) === [ 'color-1', 'color-2', 'tone', 'mode', 'font-title', 'font-text', 'font-button' ]
	&& $clean['color-1'] === [ 'own' => true, 'value' => '#9a4b1d' ] && $clean['mode'] === [ 'own' => false ] && Brand::clean( $site, [] ) === array_fill_keys( Brand::KEYS, [ 'own' => false ] ) );

$refused = [];
$wrong = [
	'color-1'		=> [ '#fff', 'red', '#12345g', '#1234567', 'url(x)', '#123456;}body{x:y', '', null, 123456, [ '#123456' ], "#12345\0", 'rgb(1,2,3)', '#123456 ', 'var(--x)', true ],
	'color-2'		=> [ 'expression(1)', '#GGGGGG', "#123456\n" ],
	'font-title'	=> [ 'nope', '../x', 'Open Sans', 'open-sans/../..', str_repeat( 'a', 300 ), 'system-sans ', 'OPEN-SANS', 'open-sans;', '', null, 5, [] ],
	'font-text'		=> [ '../../etc/passwd', 'https://evil.example/f.css' ],
	'font-button'	=> [ "system-sans\0" ],
	'tone'			=> [ 'hot', 'Warm', null, '' ],
	'mode'			=> [ 'blue', 'dark ', 'Auto', 1 ],
];

foreach( $wrong as $key => $values )
	foreach( $values as $value )
		$refused[] = Brand::clean( $site, [ $key => [ 'own' => true, 'value' => $value ] ] ) === $key;

check( 'a value the key does not take is refused by name, whatever it holds: '. count( $refused ). ' colours, fonts, tones and modes that are none, among them a colour with a semicolon, a path in a font, a url, a null byte', count( $refused ) === 41 && array_unique( $refused ) === [ true ] );
check( 'and so is an entry that is no entry, a key that is not one of the seven - a logo among them - and an own value with no value - the value of one that is not own is not kept', Brand::clean( $site, [ 'logo' => [ 'own' => true, 'value' => 'x.svg' ] ] ) === 'logo' && Brand::clean( $site, [ 'favicon' => [ 'own' => false ] ] ) === 'favicon'
	&& Brand::clean( $site, [ 'color-1' => 'red' ] ) === 'color-1' && Brand::clean( $site, [ 'color-1' => [ 'own' => 'yes', 'value' => '#123456' ] ] ) === 'color-1' && Brand::clean( $site, [ 'color-1' => [ 'own' => true ] ] ) === 'color-1'
	&& Brand::clean( $site, [ 'color-1' => [ 'own' => true, 'value' => '#123456', 'extra' => 1 ] ] ) === 'color-1' && Brand::clean( $site, 'no' ) === '' && Brand::clean( $site, [ 'tone' => [ 'own' => false, 'value' => 'hot' ] ] )['tone'] === [ 'own' => false ] );

$mixed = Mix::clean( $site, [ 'parts' => [ 'page' => [ 'from' => 'craft-probe' ], 'section' => [ 'from' => 'band-probe' ], 'header' => [ 'from' => 'craft-probe' ] ] ], false );
$brand = Brand::clean( $site, $own( 'color-1', '#9a4b1d' ) + $own( 'font-title', 'system-serif' ) + $own( 'mode', 'dark' ) + $own( 'font-text', 'open-sans' ) );
$whole = [ 'craft' => $craftMix, 'band' => $bandMix, 'mixed' => $mixed, 'standard' => [ 'parts' => [] ] ];
$suggested = [];
$kept = [];

foreach( $whole as $what => $mix ) {

	$none = Compiler::compile( $site, $mix, Brand::clean( $site, [] ) )['css'];
	$mine = Compiler::compile( $site, $mix, $brand )['css'];
	$suggested[$what] = $none;
	$kept[$what] = str_contains( $mine, '--brand-color-1: #9a4b1d;' ) === true && str_contains( $mine, '--brand-scheme: dark;' ) === true && str_contains( $mine, '--brand-font-title: Georgia, Cambria, "Times New Roman", Times, serif;' ) === true
		&& str_contains( $mine, '--brand-font-text: "Open Sans", system-ui, sans-serif;' ) === true && str_contains( $mine, '--brand-on-color-1: #ffffff;' ) === true;
}

check( 'M1: where the project has said nothing, the brand is the suggestion of the theme the page comes from - the font of craft, the system\'s for band and for a mix that has no part for the page - and nothing at all where there is none',
	str_contains( $suggested['craft'], '--brand-font-title: "Open Sans", system-ui, sans-serif;' ) === true && str_contains( $suggested['band'], '--brand-font-title: system-ui' ) === true && str_contains( $suggested['mixed'], '--brand-font-title: "Open Sans"' ) === true
	&& str_contains( $suggested['standard'], '--brand-' ) === false );
check( 'M2: a colour, a font and a mode the project set stand in every build: of craft, of band, of a mix of the two and of the standard parts alone', array_values( array_unique( $kept ) ) === [ true ] && count( $kept ) === 4 );
check( 'M3: taking an own value back is the theme\'s again; the others stay as they were', str_contains( Compiler::compile( $site, $craftMix, Brand::clean( $site, [ 'font-title' => [ 'own' => false ] ] + $brand ) )['css'], '--brand-font-title: "Open Sans"' ) === true
	&& str_contains( Compiler::compile( $site, $craftMix, Brand::clean( $site, [ 'font-title' => [ 'own' => false ] ] + $brand ) )['css'], '--brand-color-1: #9a4b1d;' ) === true
	&& Brand::effective( Brand::clean( $site, [ 'tone' => [ 'own' => false, ] ] ), [ 'tone' => 'warm' ] )['tone'] === 'warm' && Brand::effective( $brand, [ 'color-1' => '#000000' ] )['color-1'] === '#9a4b1d' );

// R8: the brand writes what it names, and the reset's colours stand where it names none
$reset = Compiler::compile( $site, [ 'parts' => [] ], Brand::clean( $site, [] ) )['css'];
$bandOnly = Compiler::compile( $site, $bandMix, Brand::clean( $site, [] ) )['css'];
$colourless = static fn( string $css ): bool => preg_match( '/^\t--brand-(ground|ink|line|accent|on-accent|color|on-color)[a-z0-9-]*:/m', $css ) !== 1;
$pairsOf = static fn( string $css ): int => preg_match_all( '/--brand-(?:ground-base|ground-alt|ground-tint|ink-base|ink-quiet-base|line-base|accent-base|on-accent-base): light-dark\(/', $css );
$with = static fn( array $input, array $mix = [ 'parts' => [] ] ): string => Compiler::compile( $site, $mix, Brand::clean( $site, $input ) )['css'];
check( 'a brand that names nothing writes nothing of the brand: a mix of the standard parts is no :root of the brand at all, and a theme that suggests a neutral tone, a mode and fonts but no colour writes those and no colour, no pair of the palette and no colour written on a colour',
	str_contains( $reset, '--brand-' ) === false && $colourless( $bandOnly ) === true && str_contains( $bandOnly, '--brand-scheme: light dark;' ) === true && str_contains( $bandOnly, '--brand-font-title: system-ui' ) === true && $pairsOf( $bandOnly ) === 0 );
check( 'a colour the project names - the first or the second - makes the palette: eight pairs and the colours written on the colours, from the colour named and the default for the one that is not', $pairsOf( $with( $own( 'color-1', '#9a4b1d' ) ) ) === 8 && str_contains( $with( $own( 'color-1', '#9a4b1d' ) ), '--brand-color-2: #17233a;' ) === true
	&& $pairsOf( $with( $own( 'color-2', '#112233' ) ) ) === 8 && str_contains( $with( $own( 'color-2', '#112233' ) ), '--brand-color-1: #1c5db8;' ) === true && str_contains( $with( $own( 'color-1', '#9a4b1d' ) ), '--brand-on-color-1: #ffffff;' ) === true );
check( 'a tone that is not the neutral one makes the palette, worked out of the colour of the reset; the neutral tone named is nothing to work out', $pairsOf( $with( $own( 'tone', 'warm' ) ) ) === 8 && str_contains( $with( $own( 'tone', 'warm' ) ), '--brand-color-1: #1c5db8;' ) === true && str_contains( $with( $own( 'tone', 'cool' ) ), '--brand-ground-alt' ) === true
	&& $colourless( $with( $own( 'tone', 'neutral' ) ) ) === true );
check( 'the mode and each font are written where they are named and where they are alone, with nothing else: the mode a scheme, a font its stack and its faces', $with( $own( 'mode', 'dark' ) ) === Compiler::compile( $site, [ 'parts' => [] ], Brand::clean( $site, $own( 'mode', 'dark' ) ) )['css'] && $colourless( $with( $own( 'mode', 'dark' ) ) ) === true
	&& preg_match( '/:root \{\n\t--brand-scheme: dark;\n\}/', $with( $own( 'mode', 'dark' ) ) ) === 1 && $colourless( $with( $own( 'font-title', 'system-serif' ) ) ) === true && str_contains( $with( $own( 'font-title', 'system-serif' ) ), '--brand-font-title: Georgia' ) === true
	&& str_contains( $with( $own( 'font-title', 'system-serif' ) ), '--brand-font-text' ) === false );

// fonts: what a folder has to be
foreach( [
	'evil-name'		=> [ 'name' => 'Evil"; } body { x', 'fallback' => 'serif', 'faces' => [ [ 'weight' => 400, 'style' => 'normal', 'file' => 'a.woff2' ] ] ],
	'no-file'			=> [ 'name' => 'No File', 'fallback' => 'serif', 'faces' => [ [ 'weight' => 400, 'style' => 'normal', 'file' => 'missing.woff2' ] ] ],
	'bad-weight'	=> [ 'name' => 'Bad Weight', 'fallback' => 'serif', 'faces' => [ [ 'weight' => 50, 'style' => 'normal', 'file' => 'a.woff2' ] ] ],
	'loose-file'	=> [ 'name' => 'Loose', 'fallback' => 'serif', 'faces' => [ [ 'weight' => 400, 'style' => 'normal', 'file' => '../a.woff2' ] ] ],
	'bad-style'		=> [ 'name' => 'Style', 'fallback' => 'serif', 'faces' => [ [ 'weight' => 400, 'style' => 'oblique', 'file' => 'a.woff2' ] ] ],
	'bad-fallback'	=> [ 'name' => 'Fallback', 'fallback' => 'url(x)', 'faces' => [ [ 'weight' => 400, 'style' => 'normal', 'file' => 'a.woff2' ] ] ],
	'no-faces'		=> [ 'name' => 'No Faces', 'fallback' => 'serif', 'faces' => [] ],
	'Not-Slug'		=> [ 'name' => 'Not Slug', 'fallback' => 'serif', 'faces' => [ [ 'weight' => 400, 'style' => 'normal', 'file' => 'a.woff2' ] ] ],
	'good-font'		=> [ 'name' => 'Good Font', 'fallback' => 'serif', 'faces' => [ [ 'weight' => 400, 'style' => 'normal', 'file' => 'a.woff2' ] ] ],
] as $slug => $font ) {
	\Nino\Filesystem::putFileContent( $site, '/fonts/'. $slug. '/font.json', $font );
	file_put_contents( \Nino\Filesystem::path( $site, '/fonts' ). '/'. $slug. '/a.woff2', 'wOF2' );
	file_put_contents( ninoSandboxDir( $site ). '/public/a.woff2', 'wOF2' );
}

$fonts = Brand::fonts( $site );
check( 'a font is a folder of public/fonts/ with a font.json that says a name without a quote, a fallback of the three, faces of a weight, a style and a .woff2 file of the folder that is there: of nine folders, two are fonts', array_keys( $fonts ) === [ 'system-sans', 'system-serif', 'system-mono', 'good-font', 'open-sans' ] && $fonts['good-font']['stack'] === '"Good Font", Georgia, serif' );

// a folder that is a link is no font, though what it points at is one
mkdir( ninoSandboxDir( $site ). '/font-elsewhere' );
copy( \Nino\Filesystem::path( $site, '/fonts/good-font/font.json' ), ninoSandboxDir( $site ). '/font-elsewhere/font.json' );
copy( \Nino\Filesystem::path( $site, '/fonts/good-font/a.woff2' ), ninoSandboxDir( $site ). '/font-elsewhere/a.woff2' );
symlink( ninoSandboxDir( $site ). '/font-elsewhere', \Nino\Filesystem::path( $site, '/fonts' ). '/linked-font' );
check( 'a folder of public/fonts/ that is a link is no font, though what it points at is one', isset( Brand::fonts( $site )['linked-font'] ) === false && isset( Brand::fonts( $site )['good-font'] ) === true && Brand::clean( $site, $own( 'font-title', 'linked-font' ) ) === 'font-title' );

\Nino\Filesystem::removeDir( \Nino\Filesystem::path( $site, '/fonts/open-sans' ) );
$gone = Compiler::compile( $site, $craftMix, Brand::clean( $site, [] ) );
check( 'a font that went away is the stack of the system, with the plan saying so, and nothing is loaded for it', str_contains( $gone['css'], '--brand-font-title: system-ui' ) === true && str_contains( $gone['css'], '@font-face' ) === false && count( $gone['plan']['notes'] ) === 2 && str_contains( $gone['plan']['notes'][0], 'open-sans' ) === true
	&& Brand::clean( $site, $own( 'font-title', 'open-sans' ) ) === 'font-title' );

// ---------------------------------------------------------------------------------------------------------------------------------
echo "\nF. The store\n";

$site = themesSite();
ninoUser( $site, 'designer@example.com' );
$baseline = \Nino\Modules\Assets::stylesheet( $site, THEMES_BUNDLE );
$state = ninoSandboxDir( $site ). '/private/data/theme';
$sheet = ninoSandboxDir( $site ). '/private/assets/theme.css';

// what the project owns besides: its pages, its words and its elements, and what must never change with a theme
\Nino\Filesystem::putFileContent( $site, '/templates/page-hero.tpl', THEMES_PAGE );
\Nino\Filesystem::putFileContent( $site, '/text/de_DE.php', [ '[[/template/page-hero/hero/title]]' => 'Hallo' ] );
\Nino\Filesystem::putFileContent( $site, '/elements/x.php', [ 'model' => [], '*' => [ '*' => [] ] ] );
$digest = static function( string $dir ): string {

	$files = [];

	foreach( new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $dir, FilesystemIterator::SKIP_DOTS ) ) as $file )
		$files[] = substr( (string) $file, strlen( $dir ) ). ':'. sha1_file( (string) $file );

	sort( $files );

	return sha1( implode( "\n", $files ) );
};
$theirs = static fn(): string => $digest( ninoSandboxDir( $site ). '/private/templates' ). $digest( ninoSandboxDir( $site ). '/private/text' ). $digest( ninoSandboxDir( $site ). '/private/elements' );
$ownedBefore = $theirs();

[ $status, $body ] = themesCall( $site, 'themes/list' );
check( 'the list: the two themes of the fixtures and the one the feature ships with their parts, none active, nothing to restore, no stylesheet of somebody else\'s, the groups of the register and the fonts - and no path of this machine', $status === 200 && array_column( $body['themes'], 'key' ) === [ 'band-probe', 'craft-probe', 'nino' ] && $body['active'] === null && $body['canRestore'] === false && $body['foreign'] === false
	&& array_column( $body['groups'], 'key' ) === [ 'button', 'footer', 'header', 'text', 'image', 'section' ] && in_array( 'open-sans', array_column( $body['fonts'], 'key' ), true ) === true && $body['rejected'] === [] && str_contains( json_encode( $body ), ninoSandboxDir( $site ) ) === false );

[ $status, $body ] = themesCall( $site, 'themes/open', [ 'theme' => 'craft-probe' ] );
$section = $body['groups']['section'] ?? [];
check( 'open: the mix of the theme, the part of each group with where it is from, its knobs on their steps - the additional one marked - and the variants with their standard, the brand as stored, as it comes out and as the theme suggests it, and the contrast of every pair',
	$status === 200 && $body['mix'] === Mix::of( $site, 'craft-probe' ) && $section['from'] === 'craft-probe@1.0.0' && $section['origin'] === 'installed' && $section['problem'] === '' && array_column( $section['knobs'], 'step' ) === [ 0, 0, 0, 0, 0, 0 ]
	&& array_column( array_filter( $section['knobs'], static fn( array $knob ): bool => $knob['extra'] === true ), 'key' ) === [ 'section-col.x-shadow' ] && array_values( array_filter( $section['components'], static fn( array $component ): bool => $component['name'] === 'section-wrap' ) )[0]['standard'] === 'soft'
	&& array_column( $section['offers'], 'theme' ) === [ 'band-probe', 'craft-probe', 'nino' ] && $body['brand']['suggested']['tone'] === 'warm' && $body['brand']['effective']['font-title'] === 'open-sans' && count( $body['palette']['contrast'] ) === 25 && min( $body['palette']['contrast'] ) >= 4.5
	&& array_column( $body['groups']['page']['knobs'], 'key' ) === [ 'size', 'width', 'space', 'radius', 'line', 'motion', 'density' ] && str_contains( json_encode( $body ), ninoSandboxDir( $site ) ) === false && $body['notes'] === [] );

mkdir( ninoSandboxDir( $site ). '/themes/broken-one' );
file_put_contents( ninoSandboxDir( $site ). '/themes/broken-one/theme.json', '{ nope' );
unset( $site['./nino/themes/read'] );
[ $status, $body ] = themesCall( $site, 'themes/list' );
check( 'a theme that is refused is in the list with the name of its folder and the reason, and no path of this machine', $status === 200 && count( $body['rejected'] ) === 1 && $body['rejected'][0]['folder'] === 'broken-one' && trim( $body['rejected'][0]['reason'] ) !== '' && $body['rejected'][0]['why'] === \Nino\Modules\Themes\Reader::explain( $body['rejected'][0]['reason'] ) && $body['rejected'][0]['why']['code'] !== '' && array_filter( array_merge( ...array_map( static fn( array $theme ): array => array_values( $theme['parts'] ), array_filter( $body['themes'], static fn( array $theme ): bool => $theme['kind'] === 'theme' ) ) ), static fn( array $part ): bool => $part['why'] !== null ) === [] && str_contains( json_encode( $body ), ninoSandboxDir( $site ) ) === false );
\Nino\Filesystem::removeDir( ninoSandboxDir( $site ). '/themes/broken-one' );
unset( $site['./nino/themes/read'] );

[ $status, $body ] = themesCall( $site, 'themes/open', [ 'theme' => 'craft-probe' ] );
$groupsOf = $body['groups'];
$knob = array_values( array_filter( $groupsOf['section']['knobs'], static fn( array $knob ): bool => $knob['key'] === 'section-wrap.space' ) )[0];
$components = array_column( $groupsOf['section']['components'], null, 'name' );
check( 'R2 open: each knob with its five values, its middle one as the default and its step, each component with every variant there is - the schema\'s with no label of the theme, the part\'s with it and the standard - and the groups with the themes that offer a part',
	count( $knob['ladder'] ) === 5 && $knob['default'] === $knob['ladder'][2] && $knob['default'] === '1.75rem' && $knob['step'] === 0 && array_column( $components['section-col']['variants'], 'name' ) === [ 'regular', 'card' ] && $components['section-col']['variants'][1]['source'] === 'schema'
	&& $components['section-wrap']['variants'][1] === [ 'name' => 'soft', 'label' => [ 'de_DE' => 'Weiche Fläche', 'en_US' => 'Soft surface' ], 'source' => 'part' ] && $components['section-wrap']['standard'] === 'soft' && $groupsOf['section']['package'] === 'installed' && $groupsOf['page']['knobs'][0]['ladder'][0] === [ '--page-size' => '93.75%' ]
	&& $groupsOf['page']['knobs'][6]['small'] === true && array_column( $groupsOf['section']['offers'], 'theme' ) === [ 'band-probe', 'craft-probe', 'nino' ] && isset( $components['section-col']['variants'][0]['label'] ) === false );
check( 'R2 open: the palette says where it comes from - craft names a tone, so it is worked out of the brand; band names no colour and no tone but the neutral, so the colours of the reset stand, as Nino.css has them and with the contrast of each pair',
	$body['palette']['source'] === 'brand' && count( $body['palette']['contrast'] ) === 25 && ( $reset = themesCall( $site, 'themes/open', [ 'theme' => 'band-probe' ] )[1]['palette'] )['source'] === 'reset' && $reset['colors']['dark']['alt'] === '#1e2127' && $reset['colors']['dark']['accent'] === '#86b6f6'
	&& $reset['colors']['light']['ink'] === '#1b1d21' && $reset['colors']['light']['accent'] === '#1c5db8' && $reset['colors']['dark']['on'] === '#0f1b2e' && count( $reset['contrast'] ) === 25 && min( $reset['contrast'] ) > 4.0 );

// R2 preview: the draft is described as open describes a theme, so that the panel needs no save to show "part from" and the colours
$partOf = Mix::of( $site, 'craft-probe' );
$partOf['parts']['section'] = [ 'from' => 'band-probe@1.0.0', 'values' => [ 'section-title.x-rule' => 2 ] ];
[ $status, $body ] = themesCall( $site, 'themes/preview', [ 'mix' => $partOf, 'brand' => [ 'color-1' => [ 'own' => true, 'value' => '#00775e' ] ] ] );
$draftSection = $body['groups']['section'];
check( 'R2 preview: the answer holds the groups and the brand of the draft - the part of band for the section, its knob with the step of the draft and its ladder, its variants - and the palette with the contrast of the colour that was posted, from the brand', $status === 200 && preg_match( \Nino\Preview::TOKEN, $body['token'] ) === 1 && $draftSection['from'] === 'band-probe@1.0.0'
	&& array_values( array_filter( $draftSection['knobs'], static fn( array $knob ): bool => $knob['key'] === 'section-title.x-rule' ) )[0]['step'] === 2 && array_column( array_column( $draftSection['components'], null, 'name' )['section-wrap']['variants'], 'name' ) === [ 'regular', 'band' ]
	&& $body['palette']['source'] === 'brand' && $body['palette']['colors']['light']['accent'] === '#00775e' && count( $body['palette']['contrast'] ) === 25 && $body['brand']['effective']['color-1'] === '#00775e' && in_array( 'color-1', $body['brand']['named'], true ) === true && in_array( 'tone', $body['brand']['named'], true ) === true );

// the first change: the theme of a package, as the mix it is
[ $status, $body ] = themesCall( $site, 'themes/apply', [ 'theme' => 'craft-probe' ] );
$firstSheet = (string) file_get_contents( $sheet );
check( 'Nr. 3: apply makes the website show a theme: the stylesheet is written for the layer of the theme, the state says which theme and what it was built from, the variants are kept, a copy of every part is kept', $status === 200 && $body['applied'] === true && $body['active']['theme'] === 'craft-probe' && $body['active']['built'] === Compiler::stamp( $firstSheet ) && $body['active']['stale'] === false
	&& Compiler::isOwn( $firstSheet ) === true && $firstSheet === (string) file_get_contents( $state. '/state/theme.css' ) && is_file( $state. '/state/variants.php' ) === true && $body['foreign'] === false );
$active = json_decode( (string) file_get_contents( $state. '/state/active.json' ), true );
$copy = $state. '/copies/craft-probe@1.0.0';
check( 'the theme is kept as the mix it was - the site hangs on no package - and a copy of the theme and of each part in use is in the project, the css byte for byte and the json as it decodes', $active['theme'] === 'craft-probe' && $active['mix'] === Mix::of( $site, 'craft-probe' ) && $active['feature'] === '1.0.0'
	&& array_map( 'basename', glob( $copy. '/parts/*', GLOB_ONLYDIR ) ) === [ 'button', 'footer', 'header', 'image', 'page', 'section', 'text' ] && sha1_file( $copy. '/parts/section/part.css' ) === sha1_file( ninoSandboxDir( $site ). '/themes/craft-probe/parts/section/part.css' )
	&& json_decode( (string) file_get_contents( $copy. '/theme.json' ), true ) === json_decode( (string) file_get_contents( ninoSandboxDir( $site ). '/themes/craft-probe/theme.json' ), true ) && json_decode( (string) file_get_contents( $copy. '/parts/section/part.json' ), true ) === json_decode( (string) file_get_contents( ninoSandboxDir( $site ). '/themes/craft-probe/parts/section/part.json' ), true ) && is_dir( $state. '/copies/band-probe@1.0.0' ) === false );

// the same page, drawn in each state: the theme changes what it looks like and not what it says
$draw = static function( array $project ): string {

	$site = themesReboot( $project, [ '\\Nino\\Modules\\Assets', '\\Nino\\Modules\\Basis', '\\Nino\\Modules\\Themes' ] );

	return \Nino\Html::renderHtml( $site, (string) file_get_contents( \Nino\Filesystem::path( $site, '/templates/page-hero.tpl' ) ) );
};
$classes = static function( string $html ): array {

	preg_match_all( '/class="([^"]+)"/', $html, $found );
	$all = array_merge( ...array_map( static fn( string $list ): array => explode( ' ', $list ), $found[1] ) );
	sort( $all );

	return array_values( array_unique( $all ) );
};
$fixed = static fn( array $names ): array => array_values( array_filter( $names, static fn( string $name ): bool => str_contains( $name, '--variant-' ) === false ) );
$pageHtml = [ 'craft' => $draw( $site ) ];
check( 'Nr. 3: with craft the page names a variant craft has not - band - and the hero is drawn as the standard of craft, soft; the fixed attributes - ground, height, gap, pad, sizes, tone - are classes all the same, and the card is the card of the schema', in_array( 'section-wrap--variant-soft', $classes( $pageHtml['craft'] ), true ) === true
	&& in_array( 'section-wrap--variant-band', $classes( $pageHtml['craft'] ), true ) === false && in_array( 'header-wrap--variant-stacked', $classes( $pageHtml['craft'] ), true ) === true && in_array( 'section-col--variant-card', $classes( $pageHtml['craft'] ), true ) === true
	&& array_diff( [ 'section-wrap--ground-dark', 'section-wrap--height-80', 'section-row--gap-3', 'section-col--pad-2', 'section-title--size-l', 'button--tone-quiet', 'button--size-l', 'text--size-s' ], $classes( $pageHtml['craft'] ) ) === [] );

// Lindner: a mix of the two, with the brand of the project
$lindner = themesMix( 'Lindner', [
	'page'		=> [ 'from' => 'band-probe', 'values' => [ 'radius' => 1 ] ],
	'header'	=> [ 'from' => 'band-probe' ],
	'section'	=> [ 'from' => 'craft-probe', 'standard' => [ 'section-wrap' => 'soft' ], 'values' => [ 'section-wrap.space' => 2, 'section-col.x-shadow' => 1 ] ],
	'button'	=> [ 'from' => 'standard' ],
], '.lindner-own { color: red; }' );
$brandPosted = [ 'color-1' => [ 'own' => true, 'value' => '#9a4b1d' ], 'font-title' => [ 'own' => true, 'value' => 'system-serif' ], 'mode' => [ 'own' => true, 'value' => 'dark' ] ];
$before = $firstSheet;
[ $status, $body ] = themesCall( $site, 'themes/save-as', [ 'mix' => $lindner ] );
$file = json_decode( (string) file_get_contents( $state. '/state/own/lindner.json' ), true );
check( 'Nr. 2: save as an own theme: it is stored under a key made of its name, with its name, its parts and its css - and it is not on the website: the stylesheet there is what it was, the state says craft', $status === 200 && $body['theme'] === 'own:lindner' && $body['applied'] === false && $body['active']['theme'] === 'craft-probe'
	&& (string) file_get_contents( $sheet ) === $before && $file['name'] === 'Lindner' && $file['css'] === '.lindner-own { color: red; }' && $file['parts']['section'] === [ 'from' => 'craft-probe@1.0.0', 'standard' => [ 'section-wrap' => 'soft' ], 'values' => [ 'section-col.x-shadow' => 1, 'section-wrap.space' => 2 ] ]
	&& $file['parts']['page'] === [ 'from' => 'band-probe@1.0.0', 'values' => [ 'radius' => 1 ] ] && $file['parts']['button'] === [ 'from' => 'standard' ] );
[ $saved, $answer ] = themesCall( $site, 'themes/save', [ 'theme' => 'own:lindner', 'mix' => $lindner, 'brand' => $brandPosted ] );
check( 'the brand is the project\'s and not a theme\'s: saved with a theme that is not on the website it is on the website all the same, in the stylesheet of the theme that is', $saved === 200 && $answer['applied'] === false && $answer['active']['theme'] === 'craft-probe' && str_contains( (string) file_get_contents( $sheet ), '--brand-color-1: #9a4b1d;' ) === true
	&& str_contains( (string) file_get_contents( $sheet ), '--section-wrap-space: 1.75rem;' ) === true );
check( 'M5: the brand is not in the own theme - not a key, not a value - and not the own theme in the brand: it is a file of its own, in the shape of the seven values',
	array_keys( $file ) === [ 'name', 'css', 'parts' ] && str_contains( json_encode( $file ), 'color-1' ) === false && str_contains( json_encode( $file ), '#9a4b1d' ) === false && str_contains( json_encode( $file ), 'brand' ) === false
	&& json_decode( (string) file_get_contents( $state. '/state/brand.json' ), true ) === Brand::clean( $site, $brandPosted ) && array_keys( json_decode( (string) file_get_contents( $state. '/state/brand.json' ), true ) ) === Brand::KEYS );

[ $status, $body ] = themesCall( $site, 'themes/apply', [ 'theme' => 'own:lindner' ] );
$lindnerSheet = (string) file_get_contents( $sheet );
check( 'Nr. 2: apply of the own theme: Section from craft with its ladder at +2 (3.5 rem), the shadow at +1, the header and the page from band, the radius of the page at +1, the own css at the end - and the website shows it',
	$status === 200 && $body['applied'] === true && $body['active']['theme'] === 'own:lindner' && str_contains( $lindnerSheet, '--section-wrap-space: 3.5rem;' ) === true && str_contains( $lindnerSheet, '--section-col-x-shadow: 0 8px 22px rgb(0 0 0 / .14);' ) === true && str_contains( $lindnerSheet, '--page-radius: .375rem;' ) === false
	&& str_contains( $lindnerSheet, '--page-radius: .75rem;' ) === true && str_ends_with( $lindnerSheet, "/* The own css of the theme */\n.lindner-own { color: red; }\n" ) === true && str_contains( $lindnerSheet, '/* header: the part of band-probe 1.0.0 */' ) === true && str_contains( $lindnerSheet, '/* section: the part of craft-probe 1.0.0 */' ) === true );
check( 'M2: the colour, the font and the mode of the project are in the stylesheet, and the first colour has the colour written on it that carries', str_contains( $lindnerSheet, '--brand-color-1: #9a4b1d;' ) === true && str_contains( $lindnerSheet, '--brand-scheme: dark;' ) === true && str_contains( $lindnerSheet, '--brand-font-title: Georgia' ) === true && str_contains( $lindnerSheet, '--brand-on-color-1: #ffffff;' ) === true );
$pageHtml['lindner'] = $draw( $site );
check( 'Nr. 3: with the mix: the header is the regular one of band (a bar is a variant it has, and it is not the standard), the hero is soft, from craft - the page still says band', in_array( 'section-wrap--variant-soft', $classes( $pageHtml['lindner'] ), true ) === true && in_array( 'header-wrap--variant-bar', $classes( $pageHtml['lindner'] ), true ) === false
	&& in_array( 'header-wrap--variant-stacked', $classes( $pageHtml['lindner'] ), true ) === false && $fixed( $classes( $pageHtml['lindner'] ) ) === $fixed( $classes( $pageHtml['craft'] ) ) );

$sheetOfLindner = $lindnerSheet;
[ $status, $body ] = themesCall( $site, 'themes/apply', [ 'theme' => 'band-probe' ] );
$pageHtml['band'] = $draw( $site );
$bandSheet = (string) file_get_contents( $sheet );
check( 'Nr. 3: with band the page says band and gets it: the variant stays in the page and appears; the spaces, the variants and the standard are band\'s; what the project set - the colour, the font, the mode - stays; the fixed attributes are the same classes', $status === 200 && in_array( 'section-wrap--variant-band', $classes( $pageHtml['band'] ), true ) === true
	&& in_array( 'section-wrap--variant-soft', $classes( $pageHtml['band'] ), true ) === false && in_array( 'header-wrap--variant-stacked', $classes( $pageHtml['band'] ), true ) === false && $fixed( $classes( $pageHtml['band'] ) ) === $fixed( $classes( $pageHtml['craft'] ) )
	&& str_contains( $bandSheet, '--section-wrap-space: 2.25rem;' ) === true && str_contains( $bandSheet, '--brand-color-1: #9a4b1d;' ) === true && str_contains( $bandSheet, '--brand-font-title: Georgia' ) === true && str_contains( $bandSheet, '--brand-scheme: dark;' ) === true );

themesCall( $site, 'themes/apply', [ 'theme' => 'craft-probe' ] );
$pageHtml['again'] = $draw( $site );
check( 'Nr. 3: and back to craft the hero is soft again, and to band it is band again - the page was never changed: the template, the texts and the elements are the files they were, byte for byte', $classes( $pageHtml['again'] ) === $classes( $pageHtml['craft'] ) && $theirs() === $ownedBefore && file_get_contents( ninoSandboxDir( $site ). '/private/templates/page-hero.tpl' ) === THEMES_PAGE
	&& in_array( 'section-wrap--variant-band', $classes( $draw( ( static function() use ( $site ): array {
		themesCall( $site, 'themes/apply', [ 'theme' => 'band-probe' ] );

		return $site;
	} )() ) ), true ) === true );
themesCall( $site, 'themes/apply', [ 'theme' => 'craft-probe' ] );

// M3 and M2 through the API
[ $status, $body ] = themesCall( $site, 'themes/save', [ 'theme' => 'own:lindner', 'mix' => $lindner, 'brand' => [ 'color-1' => [ 'own' => false ] ] + $brandPosted ] );
$stored = json_decode( (string) file_get_contents( $state. '/state/brand.json' ), true );
check( 'M3: "back to the theme\'s" is a value that is not own any more - stored so, and the next build of an own theme on the website follows the theme', $status === 200 && $stored['color-1'] === [ 'own' => false ] && $stored['font-title'] === [ 'own' => true, 'value' => 'system-serif' ] && $body['applied'] === false );
themesCall( $site, 'themes/apply', [ 'theme' => 'own:lindner' ] );
check( 'M3: the first colour is the reset\'s - no colour of the brand is written, band suggests none - and the font and mode of the project are still its own', preg_match( '/^\t--brand-(color-1|ground-base):/m', (string) file_get_contents( $sheet ) ) !== 1 && str_contains( (string) file_get_contents( $sheet ), '--brand-font-title: Georgia' ) === true && str_contains( (string) file_get_contents( $sheet ), '--brand-scheme: dark;' ) === true );

// a package that is gone: the copy is what the mix is built from
$themes = ninoSandboxDir( $site ). '/themes';
rename( $themes. '/craft-probe', ninoSandboxDir( $site ). '/craft-probe-away' );
unset( $site['./nino/themes/read'] );
$rebuilt = themesCall( $site, 'themes/apply', [ 'theme' => 'own:lindner' ] );
check( 'F10: with the package of craft removed the own theme is built all the same - from the copy the project holds - and the stylesheet is the one it was', $rebuilt[0] === 200 && (string) file_get_contents( $sheet ) === $sheetOfLindner || str_contains( (string) file_get_contents( $sheet ), '--section-wrap-space: 3.5rem;' ) === true );
[ $status, $body ] = themesCall( $site, 'themes/open', [ 'theme' => 'own:lindner' ] );
check( 'F10: the part is marked as the copy it comes from', $status === 200 && $body['groups']['section']['origin'] === 'copy' && $body['groups']['section']['problem'] === '' );
check( 'F10: and the panel is told that the package of the part is gone - the part is the copy of the project, the package is missing - where a part of an installed package says installed', $body['groups']['section']['package'] === 'missing' && $body['groups']['section']['origin'] === 'copy' && $body['groups']['page']['package'] === 'installed' && $body['groups']['button']['package'] === '' );
\Nino\Filesystem::removeDir( $state. '/copies/craft-probe@1.0.0' );
[ $status, $body ] = themesCall( $site, 'themes/apply', [ 'theme' => 'own:lindner' ] );
check( 'and with the copy gone as well the theme is not built - 409 with the reason - and the stylesheet and the state are what they were', $status === 409 && $body['code'] === 'themes_mix' && str_contains( $body['params'][0], 'craft-probe' ) === true && str_contains( (string) file_get_contents( $sheet ), '--section-wrap-space: 3.5rem;' ) === true );
rename( ninoSandboxDir( $site ). '/craft-probe-away', $themes. '/craft-probe' );
unset( $site['./nino/themes/read'] );
\Nino\Filesystem::removeDir( $state. '/copies/craft-probe@1.0.0' );
themesCall( $site, 'themes/apply', [ 'theme' => 'craft-probe' ] );
rename( $themes. '/craft-probe', ninoSandboxDir( $site ). '/craft-probe-away' );
unset( $site['./nino/themes/read'] );
[ $status, $body ] = themesCall( $site, 'themes/save', [ 'theme' => 'own:lindner', 'mix' => themesMix( 'Lindner', [ 'section' => [ 'from' => 'craft-probe@1.0.0' ] ] ), 'brand' => [ 'tone' => [ 'own' => true, 'value' => 'cool' ] ] ] );
check( 'F10: a theme of a package that is on the website is the mix it was when it was applied, and the copy: with the package gone the brand can still be changed and the stylesheet is built again', $status === 200 && $body['active']['theme'] === 'craft-probe' && str_contains( (string) file_get_contents( $sheet ), '--section-wrap-space: 1.75rem;' ) === true && str_contains( (string) file_get_contents( $sheet ), '#fcf2e7' ) === false && str_contains( (string) file_get_contents( $sheet ), '--brand-ground-alt: light-dark( #f1f4f8' ) === false );
rename( ninoSandboxDir( $site ). '/craft-probe-away', $themes. '/craft-probe' );
unset( $site['./nino/themes/read'] );

// ---- the last state (Nr. 7)
$site = themesSite();
ninoUser( $site, 'designer@example.com' );
$state = ninoSandboxDir( $site ). '/private/data/theme';
$sheet = ninoSandboxDir( $site ). '/private/assets/theme.css';
$baseline = \Nino\Modules\Assets::stylesheet( $site, THEMES_BUNDLE );
$stateOf = static fn( string $which ): string => is_dir( $state. '/'. $which ) === true ? $digest( $state. '/'. $which ) : '-';
$none = static fn(): bool => themesCall( $site, 'themes/restore' )[1]['code'] === 'themes_nothing';

[ $status, $body ] = themesCall( $site, 'themes/restore' );
check( 'with nothing saved there is nothing to restore: 409, with the code and the sentence, and nothing is made', $status === 409 && $body['code'] === 'themes_nothing' && is_dir( $state ) === false && is_file( $sheet ) === false );

themesCall( $site, 'themes/apply', [ 'theme' => 'craft-probe' ] );
$one = $stateOf( 'state' );
check( 'the first change is the first state, and what was before it - nothing - is the last state: there is something to restore, and it is a site with no theme', $one !== '-' && $stateOf( 'previous' ) === $digest( $state. '/previous' ) && glob( $state. '/previous/*' ) === [] && themesCall( $site, 'themes/list' )[1]['canRestore'] === true );

themesCall( $site, 'themes/save-as', [ 'mix' => $lindner, 'brand' => $brandPosted ] );
$two = $stateOf( 'state' );
themesCall( $site, 'themes/apply', [ 'theme' => 'own:lindner' ] );
$three = $stateOf( 'state' );
$sheetThree = (string) file_get_contents( $sheet );
check( 'every change moves the state to the last state: the last of the third is the second, the last of the second is the first', $stateOf( 'previous' ) === $two && $two !== $one && $three !== $two );

[ $status, $body ] = themesCall( $site, 'themes/restore' );
check( 'Nr. 7: restore swaps the state and the last state: the state is the second again - the active theme, the brand, the own theme, the stylesheet - and the third is the last state', $status === 200 && $body['restored'] === true && $stateOf( 'state' ) === $two && $stateOf( 'previous' ) === $three && $body['active']['theme'] === 'craft-probe'
	&& str_contains( (string) file_get_contents( $sheet ), '--section-wrap-space: 1.75rem;' ) === true && (string) file_get_contents( $sheet ) === (string) file_get_contents( $state. '/state/theme.css' ) );
themesCall( $site, 'themes/restore' );
check( 'Nr. 7: twice is the state it was - the same files, byte for byte, the stylesheet of the website and the variants the register is told included', $stateOf( 'state' ) === $three && $stateOf( 'previous' ) === $two && (string) file_get_contents( $sheet ) === $sheetThree
	&& ( include $state. '/state/variants.php' ) === [ 'header-wrap' => [ [ 'bar' ], 'regular', [ 'bar' => $partLabel( 'band-probe', 'header', 'header-wrap', 'bar' ) ] ], 'section-wrap' => [ [ 'soft' ], 'soft', [ 'soft' => $partLabel( 'craft-probe', 'section', 'section-wrap', 'soft' ) ] ] ] );

$changed = $stateOf( 'previous' );
[ $status, $body ] = themesCall( $site, 'themes/apply', [ 'theme' => 'own:lindner' ] );
check( 'a change that changes nothing is nothing: the same theme again leaves the last state the last state', $status === 200 && $stateOf( 'previous' ) === $changed && $stateOf( 'state' ) === $three );

themesCall( $site, 'themes/apply', [ 'theme' => 'band-probe' ] );
$four = $stateOf( 'state' );
[ $status ] = themesCall( $site, 'themes/delete', [ 'theme' => 'own:lindner' ] );
$five = $stateOf( 'state' );
check( 'an own theme that is deleted is gone from the state', $status === 200 && is_file( $state. '/state/own/lindner.json' ) === false && $five !== $four );
themesCall( $site, 'themes/restore' );
check( 'Nr. 7: and restore brings it back, the mix it was and nothing else - the own theme is part of the state', $stateOf( 'state' ) === $four && is_file( $state. '/state/own/lindner.json' ) === true && json_decode( (string) file_get_contents( $state. '/state/own/lindner.json' ), true )['name'] === 'Lindner' );

[ $status, $body ] = themesCall( $site, 'themes/save', [ 'theme' => 'own:lindner', 'mix' => $lindner, 'brand' => [ 'color-1' => [ 'own' => true, 'value' => '#00aa55' ] ] ] );
$withBrand = (string) file_get_contents( $sheet );
themesCall( $site, 'themes/restore' );
check( 'Nr. 7: a brand that was changed is a state that was changed: restore gives the colour it was, in the brand and in the stylesheet', str_contains( $withBrand, '--brand-color-1: #00aa55;' ) === true && str_contains( (string) file_get_contents( $sheet ), '--brand-color-1: #9a4b1d;' ) === true
	&& json_decode( (string) file_get_contents( $state. '/state/brand.json' ), true )['color-1']['value'] === '#9a4b1d' );

// down to the beginning: a site with no theme, drawn as the standard parts draw it
\Nino\Filesystem::removeDir( $state );
unlink( $sheet );
themesCall( $site, 'themes/apply', [ 'theme' => 'craft-probe' ] );
$first = themesReboot( $site, [ '\\Nino\\Modules\\Assets', '\\Nino\\Modules\\Basis', '\\Nino\\Modules\\Themes' ] );
check( 'a site with a theme is a bundle with the theme in it', \Nino\Modules\Assets::stylesheet( $first, THEMES_BUNDLE ) !== $baseline && str_contains( \Nino\Modules\Assets::stylesheet( $first, THEMES_BUNDLE ), '@layer nino.theme' ) === true );
themesCall( $site, 'themes/restore' );
$restored = themesReboot( $site, [ '\\Nino\\Modules\\Assets', '\\Nino\\Modules\\Basis', '\\Nino\\Modules\\Themes' ] );
check( 'the first state restored is no theme: no stylesheet of the project, nothing active, and the bundle is the bundle of a site that never had one, byte for byte', is_file( $sheet ) === false && themesCall( $site, 'themes/list' )[1]['active'] === null && \Nino\Modules\Assets::stylesheet( $restored, THEMES_BUNDLE ) === $baseline
	&& \Nino\Html::renderHtml( $restored, '[section-wrap variant="soft"][/section-wrap]' ) === '<section class="section-wrap"></section>' );
themesCall( $site, 'themes/restore' );
check( 'and once more it is the theme', is_file( $sheet ) === true && themesCall( $site, 'themes/list' )[1]['active']['theme'] === 'craft-probe' );

// ---- a stylesheet that is somebody else's
$site = themesSite();
ninoUser( $site, 'designer@example.com' );
$state = ninoSandboxDir( $site ). '/private/data/theme';
$sheet = ninoSandboxDir( $site ). '/private/assets/theme.css';
$mine = ".mine { color: red; }\n/* written by hand */\n";
\Nino\Filesystem::putFileContent( $site, '/assets/theme.css', $mine );
[ $status, $body ] = themesCall( $site, 'themes/list' );
check( 'a stylesheet of the project that the compiler did not write is said in the list', $status === 200 && $body['foreign'] === true && $body['active'] === null );
[ $status, $body ] = themesCall( $site, 'themes/apply', [ 'theme' => 'craft-probe' ] );
check( 'the compiler overwrites only what it wrote: a change that would replace the file is refused - 409 - and the file and the state are what they were', $status === 409 && $body['code'] === 'themes_foreign' && (string) file_get_contents( $sheet ) === $mine && is_dir( $state. '/state' ) === false && is_dir( $state. '/next' ) === false && is_dir( $state. '/previous' ) === false );
foreach( [ 'themes/save-as' => [ 'mix' => $lindner ], 'themes/restore' => [] ] as $action => $data )
	themesCall( $site, $action, $data );
check( 'that holds for the others that would write it: a save of an own theme that is not active writes no stylesheet, a restore with nothing to restore changes nothing', (string) file_get_contents( $sheet ) === $mine );
[ $status, $body ] = themesCall( $site, 'themes/apply', [ 'theme' => 'craft-probe', 'force' => true ] );
check( 'told that it may, it replaces it - and the file it replaced is the stylesheet of the last state', $status === 200 && Compiler::isOwn( (string) file_get_contents( $sheet ) ) === true && (string) file_get_contents( $state. '/previous/theme.css' ) === $mine && $body['foreign'] === false );
[ $status, $body ] = themesCall( $site, 'themes/restore' );
check( 'restore brings it back, byte for byte, and it is somebody else\'s again', $status === 200 && (string) file_get_contents( $sheet ) === $mine && $body['foreign'] === true && $body['active'] === null );
themesCall( $site, 'themes/apply', [ 'theme' => 'craft-probe', 'force' => true ] );
file_put_contents( $sheet, str_replace( 'soft', 'sofa', (string) file_get_contents( $sheet ) ) );
$edited = (string) file_get_contents( $sheet );
[ $status, $body ] = themesCall( $site, 'themes/apply', [ 'theme' => 'band-probe' ] );
check( 'a stylesheet of the compiler that was edited by hand is not the compiler\'s any more: one byte is enough for 409', Compiler::isOwn( $edited ) === false && $status === 409 && $body['code'] === 'themes_foreign' && (string) file_get_contents( $sheet ) === $edited );
[ $status, $body ] = themesCall( $site, 'themes/restore' );
check( 'and restore does not overwrite it either', $status === 409 && $body['code'] === 'themes_foreign' && (string) file_get_contents( $sheet ) === $edited );
unlink( $sheet );
[ $status ] = themesCall( $site, 'themes/apply', [ 'theme' => 'band-probe' ] );
check( 'a stylesheet that is not there is nobody\'s: it is written', $status === 200 && Compiler::isOwn( (string) file_get_contents( $sheet ) ) === true );

// ---- the own css of the developer
$free = ".header-wrap { display: none !important; }\n@import url(https://example.invalid/more.css);\n@font-face { font-family: \"Other\"; src: url(https://example.invalid/o.woff2); }\n@property --brand-ink { syntax: \"<color>\"; inherits: false; initial-value: red; }\n.a ~ .b + .c * { content: \"text\"; color: var(--foreign-token); }";
[ $status, $body ] = themesCall( $site, 'themes/save-as', [ 'mix' => themesMix( 'Free', [ 'section' => [ 'from' => 'craft-probe' ] ], $free ) ] );
themesCall( $site, 'themes/apply', [ 'theme' => 'own:free' ] );
check( 'the own css is not checked: what the rule of mixing refuses - !important, an import, a font from another host, a property of the brand, a foreign token - is written as it stands, at the end of the stylesheet', $status === 200 && str_ends_with( (string) file_get_contents( $sheet ), "/* The own css of the theme */\n". $free. "\n" ) === true
	&& \Nino\Components::partProblems( $free, \Nino\Components::groups( $site )['section']['components'] ) !== [] );
[ $status, $body ] = themesCall( $site, 'themes/open', [ 'theme' => 'own:free' ] );
check( 'open gives the own css back as it was written', $status === 200 && $body['mix']['css'] === $free && $body['mix']['name'] === 'Free' );

// ---- save, delete, names
[ $status, $body ] = themesCall( $site, 'themes/save', [ 'theme' => 'craft-probe', 'mix' => themesMix( 'Craft', [] ) ] );
check( 'a theme of a package is not saved over: 409 and the code, "save as"', $status === 409 && $body['code'] === 'themes_not_own' );
[ $status, $body ] = themesCall( $site, 'themes/save', [ 'theme' => 'own:nothing', 'mix' => themesMix( 'Nothing', [] ) ] );
check( 'nor is an own theme there is not: 404', $status === 404 && $body['code'] === 'themes_unknown' );
[ $status, $body ] = themesCall( $site, 'themes/save', [ 'theme' => 'own:free', 'mix' => themesMix( 'Free and easy', [ 'section' => [ 'from' => 'band-probe' ] ], '.x { }' ) ] );
check( 'an own theme is saved over: a new name stays the same key, the mix is the new one - and it is on the website, so the stylesheet is built from it', $status === 200 && $body['applied'] === true && json_decode( (string) file_get_contents( $state. '/state/own/free.json' ), true )['name'] === 'Free and easy'
	&& str_contains( (string) file_get_contents( $sheet ), '/* section: the part of band-probe 1.0.0 */' ) === true && str_ends_with( (string) file_get_contents( $sheet ), ".x { }\n" ) === true );
[ $status, $body ] = themesCall( $site, 'themes/delete', [ 'theme' => 'own:free' ] );
check( 'the theme of the website is not deleted: 409, with its name', $status === 409 && $body['code'] === 'themes_active' && $body['params'] === [ 'Free and easy' ] && is_file( $state. '/state/own/free.json' ) === true );
[ $status, $body ] = themesCall( $site, 'themes/delete', [ 'theme' => 'band-probe' ] );
check( 'a theme of a package is not deleted by it', $status === 409 && $body['code'] === 'themes_not_own' && is_dir( ninoSandboxDir( $site ). '/themes/band-probe' ) === true );
[ $status, $body ] = themesCall( $site, 'themes/delete', [ 'theme' => 'own:nothing' ] );
check( 'one that is not there is 404', $status === 404 && $body['code'] === 'themes_unknown' );

[ $status, $body ] = themesCall( $site, 'themes/save-as', [ 'mix' => themesMix( 'FREE and EASY', [] ) ] );
check( 'a name that a card has is taken, whatever its case: 409 and the name of that card', $status === 409 && $body['code'] === 'themes_name_taken' && $body['params'] === [ 'Free and easy' ] && $body['field'] === 'name' );
foreach( [ '', '   ', '!!!', str_repeat( 'x', 61 ), "two\nlines", "nul\0byte" ] as $name ) {

	[ $status, $body ] = themesCall( $site, 'themes/save-as', [ 'mix' => themesMix( $name, [] ) ] );
	$bad[] = $status === 400 && in_array( $body['code'], [ 'themes_mix', 'themes_name' ], true ) === true;
}
check( 'a name is text of one to 60 characters in one line with a letter or a digit in it: six that are not are 400', count( $bad ) === 6 && array_unique( $bad ) === [ true ] && count( glob( $state. '/state/own/*.json' ) ) === 2 );

$made = 2;
$over = 0;

for( $i = 0; $i < Nino\Modules\Themes\Store::MAX_OWN + 1; $i++ ) {

	[ $status, $body ] = themesCall( $site, 'themes/save-as', [ 'mix' => themesMix( 'Theme '. $i, [] ) ] );
	$made += $status === 200 ? 1 : 0;
	$over = $status === 409 && $body['code'] === 'themes_limit' ? $over + 1 : $over;
}

check( 'there are 50 own themes at most: the 51st is 409 with the limit - and what is there is there', $made === Nino\Modules\Themes\Store::MAX_OWN && $over === 3 && count( glob( $state. '/state/own/*.json' ) ) === Nino\Modules\Themes\Store::MAX_OWN );

// ---- a change that fails leaves the state as it was; a change while another one is made is refused
$digestState = $stateOf( 'state' );
[ $status, $body ] = themesCall( $site, 'themes/apply', [ 'theme' => 'own:nothing' ] );
check( 'a change that fails leaves the state, the last state and the stylesheet as they were, and no copy of a state behind', $status === 404 && $stateOf( 'state' ) === $digestState && is_dir( $state. '/next' ) === false && $stateOf( 'previous' ) !== $digestState );
\Nino\Filesystem::putFileContent( $site, '/data/theme/state/own/broken.json', [ 'name' => 'Broken', 'parts' => [ 'section' => [ 'from' => 'nowhere@1.0.0' ] ] ] );
$before = $stateOf( 'state' );
[ $status, $body ] = themesCall( $site, 'themes/apply', [ 'theme' => 'own:broken' ] );
check( 'an own theme whose package is not installed and has no copy is not applied: 409 with the reason, nothing changed', $status === 409 && $body['code'] === 'themes_mix' && str_contains( $body['params'][0], 'nowhere' ) === true && $stateOf( 'state' ) === $before && is_dir( $state. '/next' ) === false );
unlink( $state. '/state/own/broken.json' );

$lock = fopen( ninoSandboxDir( $site ). '/private/data/.locks/'. sha1( '/data/theme/state.lock' ). '.lock', 'c' );
flock( $lock, LOCK_EX );
[ $status, $body ] = themesCall( $site, 'themes/apply', [ 'theme' => 'craft-probe' ] );
$restoring = themesCall( $site, 'themes/restore' );
flock( $lock, LOCK_UN );
fclose( $lock );
check( 'while another request changes the themes a change is not waited for: 409 busy, and so is a restore - and nothing is changed', $status === 409 && $body['code'] === 'themes_busy' && $restoring[0] === 409 && $restoring[1]['code'] === 'themes_busy' && $stateOf( 'state' ) === $before );
check( 'and when it is over the next change is made', themesCall( $site, 'themes/apply', [ 'theme' => 'craft-probe' ] )[0] === 200 );

// ---- a change that is broken off, and a theme that has a link in it
echo "\nF2. Links in a theme, and a change that is broken off\n";

$linked = themesSite();
ninoUser( $linked, 'designer@example.com' );
$themesDir = ninoSandboxDir( $linked ). '/themes';
$outside = ninoSandboxDir( $linked ). '/outside';
mkdir( $outside. '/folder', 0755, true );
file_put_contents( $outside. '/secret.css', ".section-wrap { color: #abcdef; } /* OUTSIDE-MARKER */\n" );
file_put_contents( $outside. '/secret.json', json_encode( [ 'contract' => 'basis@^1.0', 'variants' => [ 'button' => [ 'outside' => 'Outside' ] ] ] ) );
file_put_contents( $outside. '/folder/part.json', json_encode( [ 'contract' => 'basis@^1.0' ] ) );
file_put_contents( $outside. '/folder/part.css', ".text { color: #123456; } /* OUTSIDE-MARKER */\n" );
mkdir( $themesDir. '/linked-parts/parts/section', 0755, true );
mkdir( $themesDir. '/linked-parts/parts/button', 0755, true );
mkdir( $themesDir. '/linked-parts/parts/header', 0755, true );
file_put_contents( $themesDir. '/linked-parts/theme.json', json_encode( [ 'key' => 'linked-parts', 'version' => '1.0.0', 'name' => 'Linked', 'description' => 'Parts that are links' ] ) );
file_put_contents( $themesDir. '/linked-parts/parts/section/part.json', json_encode( [ 'contract' => 'basis@^1.0' ] ) );
symlink( $outside. '/secret.css', $themesDir. '/linked-parts/parts/section/part.css' );
symlink( $outside. '/secret.json', $themesDir. '/linked-parts/parts/button/part.json' );
symlink( $outside. '/folder', $themesDir. '/linked-parts/parts/text' );
file_put_contents( $themesDir. '/linked-parts/parts/header/part.json', json_encode( [ 'contract' => 'basis@^1.0' ] ) );
file_put_contents( $themesDir. '/linked-parts/parts/header/part.css', ".header-wrap { border-bottom-width: 2px; }\n" );
$loops = [];

foreach( [ 'linked-parts', 'craft-probe', 'linked-parts', 'band-probe', 'linked-parts' ] as $theme ) {

	themesCall( $linked, 'themes/apply', [ 'theme' => $theme ] );
	$loops[] = str_contains( (string) file_get_contents( ninoSandboxDir( $linked ). '/private/assets/theme.css' ), 'OUTSIDE-MARKER' ) === false
		&& str_contains( (string) file_get_contents( ninoSandboxDir( $linked ). '/private/data/theme/state/theme.css' ), '#abcdef' ) === false;
}

$copies = ninoSandboxDir( $linked ). '/private/data/theme/copies/linked-parts@1.0.0';
$files = [];

foreach( new RecursiveIteratorIterator( new RecursiveDirectoryIterator( ninoSandboxDir( $linked ). '/private/data', FilesystemIterator::SKIP_DOTS ) ) as $file )
	$files[] = (string) file_get_contents( (string) $file );

[ $status, $body ] = themesCall( $linked, 'themes/open', [ 'theme' => 'linked-parts' ] );
check( 'a link in a theme - part.css, part.json, the folder of a part - brings nothing of what it points at into the project: not on the first build, nor on the second and third that apply the theme again after another one, in no copy and in no file of the data', array_unique( $loops ) === [ true ] && count( $loops ) === 5
	&& array_filter( $files, static fn( string $text ): bool => str_contains( $text, 'OUTSIDE-MARKER' ) === true || str_contains( $text, '"outside"' ) === true ) === [] );
check( 'the parts that are links rest, each with its reason, on every build; the part that is a file is kept as text and is used; the copy holds that part and none of the others', $body['groups']['section']['problem'] !== '' && $body['groups']['button']['problem'] !== '' && $body['groups']['section']['origin'] !== 'copy'
	&& is_dir( $copies. '/parts/header' ) === true && is_dir( $copies. '/parts/section' ) === false && is_dir( $copies. '/parts/button' ) === false && is_dir( $copies. '/parts/text' ) === false && is_link( $copies. '/parts/header/part.json' ) === false
	&& str_contains( (string) file_get_contents( ninoSandboxDir( $linked ). '/private/assets/theme.css' ), '2px' ) === true );
unlink( $themesDir. '/linked-parts/theme.json' );
symlink( $outside. '/secret.json', $themesDir. '/linked-parts/theme.json' );
unset( $linked['./nino/themes/read'] );
check( 'and a theme.json that is a link makes no theme, so no copy of it', isset( \Nino\Modules\Themes\Reader::read( $linked )['themes']['linked-parts'] ) === false );

// Each rename a change makes, one after the other the last one the process does: it is killed there. What is on the disk afterwards is
// a whole state - the old one or the new one - with its stylesheet, no own theme is lost, and the next change is made
$killer = ninoSandboxDir( $linked ). '/kill.php';
file_put_contents( $killer, <<<'PHP'
<?php
namespace Nino\Modules\Themes {
	function rename( string $from, string $to ): bool {
		static $count = 0;
		if( ++$count === (int) $GLOBALS['kill'] )
			posix_kill( getmypid(), SIGKILL );
		return \rename( $from, $to );
	}
}
namespace {
	$config = json_decode( $argv[1], true );
	$GLOBALS['kill'] = $config['kill'];
	define( 'NINO_FEATURES_DIR', $config['features'] );
	require $config['root']. '/tests/harness.php';
	$appData = [ './nino/uid' => $config['uid'] ];
	\Nino\AppData::prepare( $appData );
	foreach( $config['keys'] as $key => $value )
		$appData[$key] = $value;
	$appData['/nino/modules'] = [ '\\Nino\\Modules\\Assets', '\\Nino\\Modules\\Basis', '\\Nino\\Modules\\Themes' ];
	\Nino\Callbacks::registerCallback( $appData, \Nino\Modules\Themes\Reader::ROOTS, static fn( array &$appData, array $roots ): array => array_merge( $roots, [ $config['themes'] ] ) );
	\Nino\Html::init( $appData );
	\Nino\Features::registerComponents( $appData );
	\Nino\Features::registerParts( $appData );
	\Nino\Features::registerAssets( $appData );
	\Nino\Modules::callModules( $appData, 'init' );
	if( $config['action'] === 'restore' )
		\Nino\Modules\Themes\Store::restore( $appData );
	else
		\Nino\Modules\Themes\Store::commit( $appData, static function( array &$appData, string $next ) use ( $config ): ?array {
			\Nino\Filesystem::putFileContent( $appData, $next. '/active.json', [ 'theme' => $config['theme'], 'mix' => \Nino\Modules\Themes\Mix::of( $appData, $config['theme'] ) ] );
			return null;
		} );
	echo "finished\n";
}
PHP );

$breaks = [ 'commit' => 0, 'restore' => 0 ];
$wholeAfter = [];
$mending = [];

foreach( [ 'commit', 'restore' ] as $action )
	for( $point = 1; $point <= 12; $point++ ) {

		$broken = themesSite();
		ninoUser( $broken, 'designer@example.com' );
		themesCall( $broken, 'themes/apply', [ 'theme' => 'craft-probe' ] );
		themesCall( $broken, 'themes/save-as', [ 'mix' => $lindner ] );
		themesCall( $broken, 'themes/apply', [ 'theme' => 'band-probe' ] );
		$private = ninoSandboxDir( $broken ). '/private';
		$before = [ $digest( $private. '/data/theme/state' ), $digest( $private. '/data/theme/previous' ) ];
		$configuration = json_encode( [ 'root' => $root, 'features' => NINO_FEATURES_DIR, 'uid' => $broken['./nino/uid'], 'themes' => ninoSandboxDir( $broken ). '/themes', 'kill' => $point, 'action' => $action, 'theme' => 'craft-probe',
			'keys' => [ './nino/filesystem/path' => $root, './nino/filesystem/configpath' => $broken['./nino/filesystem/configpath'], './nino/filesystem/contentpath' => $broken['./nino/filesystem/contentpath'], './nino/filesystem/publicpath' => $broken['./nino/filesystem/publicpath'],
			'./nino/locales/current' => 'de_DE', '/nino/locales/native' => 'de_DE', '/nino/locales/available' => [ 'de_DE', 'en_US' ], '/nino/dir' => '', '/nino/install/texts' => \Nino\VERSION ] ] );
		exec( PHP_BINARY. ' '. escapeshellarg( $killer ). ' '. escapeshellarg( $configuration ). ' 2>&1', $output, $code );

		if( in_array( 'finished', $output, true ) === true ) {
			$output = [];
			break;
		}

		$output = [];
		$breaks[$action]++;
		$missing = is_dir( $private. '/data/theme/state' ) === false;
		$after = themesReboot( $broken, [ '\\Nino\\Modules\\Assets', '\\Nino\\Modules\\Basis', '\\Nino\\Modules\\Themes' ] );
		\Nino\Modules\Themes::variants( $after, [ 'name' => 'section-wrap', 'variants' => [ 'regular' ], 'standard' => 'regular' ] );
		$state = $private. '/data/theme/state';
		$digestNow = is_dir( $state ) === true ? $digest( $state ) : '-';
		$sheet = $private. '/assets/theme.css';
		$whole = $digestNow !== '-' && ( $digestNow === $before[0] || $digestNow === $before[1] || $action === 'commit' && is_file( $state. '/active.json' ) === true && json_decode( (string) file_get_contents( $state. '/active.json' ), true )['theme'] === 'craft-probe' )
			&& is_file( $state. '/own/lindner.json' ) === true && is_file( $state. '/theme.css' ) === true && is_file( $state. '/variants.php' ) === true && ( is_file( $sheet ) === true ? (string) file_get_contents( $sheet ) : '' ) === (string) file_get_contents( $state. '/theme.css' );
		// Where the state was gone the request mended it, and mended it with the last state kept: both are there, and are the two there were
		$kept = $missing === false || ( is_dir( $private. '/data/theme/previous' ) === true && in_array( $digest( $private. '/data/theme/previous' ), $before, true ) === true );
		// The list mends what is left over though a state is there, and the last state is there to restore: a restore after the break
		$listed = themesCall( $broken, 'themes/list' )[1];
		$wasPrevious = is_dir( $private. '/data/theme/previous' ) === true && array_filter( [ 'gone', 'swap' ], static fn( string $left ): bool => file_exists( $private. '/data/theme/'. $left ) ) === [];
		$restored = themesCall( $broken, 'themes/restore' );
		$restorable = $listed['canRestore'] === true && $wasPrevious === true && $restored[0] === 200 && is_file( $state. '/own/lindner.json' ) === true;
		$next = themesCall( $broken, 'themes/apply', [ 'theme' => 'craft-probe' ] );
		$later = $next[0] === 200 && is_file( $state. '/own/lindner.json' ) === true && is_dir( $private. '/data/theme/previous' ) === true && array_filter( [ 'next', 'gone', 'swap', 'building' ], static fn( string $left ): bool => file_exists( $private. '/data/theme/'. $left ) ) === [];
		$wholeAfter[] = $whole === true && $later === true && $kept === true && $restorable === true;
		$mending[] = $action. ' '. $point. ': '. ( $whole === true ? 'whole' : 'BROKEN' ). ( $later === true ? ', next change made' : ', NEXT CHANGE FAILED' );
	}

check( 'a change that is killed in front of any of its renames - commit and restore, '. $breaks['commit']. ' and '. $breaks['restore']. ' points of break - leaves a whole state: the old or the new one, with the own theme, the variants and the stylesheet that belong to it, a list that says there is a last state to restore and a restore that works, and the next change is made and leaves nothing of the broken one behind',
	$breaks['commit'] >= 4 && $breaks['restore'] >= 3 && count( $wholeAfter ) === $breaks['commit'] + $breaks['restore'] && array_unique( $wholeAfter ) === [ true ] );
foreach( $mending as $line )
	echo "  ..  - $line\n";

// ---------------------------------------------------------------------------------------------------------------------------------
echo "\nG. The preview\n";

$modules = [ '\\Nino\\Modules\\Assets', '\\Nino\\Modules\\Basis', '\\Nino\\Modules\\Themes' ];
$site = themesSite();
ninoUser( $site, 'designer@example.com' );
$state = ninoSandboxDir( $site ). '/private/data/theme';
$cache = ninoSandboxDir( $site ). '/private/data/.preview/'. \Nino\Modules\Themes::KIND;
$sessionKey = '/nino/preview/'. \Nino\Modules\Themes::KIND;
$link = \Nino\Html::renderHtml( $site, '[assets '. THEMES_BUNDLE. ']' );
$publicFile = ninoSandboxDir( $site ). '/public/.cache/style.css';
$publicBytes = (string) file_get_contents( $publicFile );
$publicTime = filemtime( $publicFile );
$draft = Mix::of( $site, 'band-probe' );
$draft['css'] = '.preview-marker { color: red; }';
$visit = static function( string $query ) use ( &$site ): string {

	ninoVisit( $site, '/?'. $query );

	return \Nino\Html::renderHtml( $site, '[assets '. THEMES_BUNDLE. ']' );
};
$inside = static function( string $html ): array {

	preg_match( '#<style data-nino-theme-preview>(.*?)</style>#s', $html, $found );

	return themesTop( str_replace( '<\\/', '</', $found[1] ?? '' ) );
};

[ $status, $body ] = themesCall( $site, 'themes/preview', [ 'mix' => $draft ] );
$token = (string) ( $body['token'] ?? '' );
check( 'a preview is a draft of the kernel: its token and the query that asks for it, for the time the kernel gives, held by the session that asked', $status === 200 && preg_match( \Nino\Preview::TOKEN, $token ) === 1 && $body['query'] === \Nino\Preview::QUERY. '='. $token
	&& $body['ttl'] === \Nino\Preview::TTL && array_keys( $_SESSION[$site['./nino/uid']][$sessionKey] ?? [] ) === [ $token ] && $body['notes'] === [] && array_key_exists( 'expires', $body ) === false );
$stored = json_decode( (string) file_get_contents( $cache. '/'. $token. '/draft.json' ), true );
check( 'the draft is the stylesheet and the variants as the compiler made them, with the label each variant has in the part, in the folder of the kernel for its kind - the feature has no folder of drafts of its own - and the state of the themes was not touched: there is none', Compiler::isOwn( (string) $stored['css'] ) === true
	&& $stored['variants'] === [ 'header-wrap' => [ [ 'bar' ], 'regular', [ 'bar' => $partLabel( 'band-probe', 'header', 'header-wrap', 'bar' ) ] ], 'section-wrap' => [ [ 'band' ], 'regular', [ 'band' => $partLabel( 'band-probe', 'section', 'section-wrap', 'band' ) ] ] ]
	&& is_dir( $state. '/.preview' ) === false && is_dir( $state. '/state' ) === false && is_dir( $state. '/previous' ) === false && is_dir( $state. '/copies' ) === false && is_file( ninoSandboxDir( $site ). '/private/assets/theme.css' ) === false );

// The kernel empties data/cache after every successful write of the workbench - which this very request is - and a draft must outlast it
$workbench = [ '/nino/http/request' => [ 'method' => 'POST', 'uri' => '/_admin' ], '/nino/http/response' => [ 'statusCode' => 200, 'body' => [ 'token' => $token ], 'header' => [] ] ];
\Nino\Filesystem::putFileContent( $site, '/data/cache/marker.php', [ 'a page' ] );
\Nino\Modules\Cache::callbackOutput( $site, $workbench );
check( 'a draft outlasts the emptying of data/cache that follows every successful write of the workbench, as a page that is kept does not', is_file( ninoSandboxDir( $site ). '/private/data/cache/marker.php' ) === false && is_file( $cache. '/'. $token. '/draft.json' ) === true );

$preview = $visit( $body['query'] );
$layers = array_map( static fn( array $item ): string => $item[0], $inside( $preview ) );
$themeBlock = array_values( array_filter( $inside( $preview ), static fn( array $item ): bool => $item[0] === '@layer nino.theme' ) );
check( 'the first draft - no stylesheet of the theme is written yet - is the stylesheet of the page in a request that asks with the token: a style element with the bundle built in memory, the four layers, the draft in the layer of the theme', str_contains( $preview, '<link' ) === false && substr_count( $preview, '</style>' ) === 1
	&& $layers[0] === '@layer nino.reset, nino.components, nino.theme, project' && count( $themeBlock ) === 1 && str_contains( $themeBlock[0][1], '.preview-marker' ) === true && str_contains( $themeBlock[0][1], '--section-wrap-space: 2.25rem;' ) === true && in_array( '@layer nino.reset', $layers, true ) === true );
check( 'the public bundle is the file it was, byte for byte and in its time - the link of a request without the token is the one it was, and the list of the bundle has not gained the stylesheet of the theme for the draft', (string) file_get_contents( $publicFile ) === $publicBytes && in_array( '/assets/theme.css', \Nino\Html::getAssets( $site, THEMES_BUNDLE ), true ) === false && filemtime( $publicFile ) === $publicTime && $visit( 'x=1' ) === $link && str_contains( $publicBytes, 'preview-marker' ) === false && is_array( ninoWarnings() ) );
check( 'the variants of the draft are the ones the register is told in that request - the band - and in a request without the token they are not', str_contains( ( static function() use ( $visit, &$site, $body ): string {
	$visit( $body['query'] );

	return \Nino\Html::renderHtml( $site, '[section-wrap variant="band"][/section-wrap]' );
} )(), 'section-wrap--variant-band' ) === true && str_contains( ( static function() use ( $visit, &$site ): string {
	$visit( 'x=1' );

	return \Nino\Html::renderHtml( $site, '[section-wrap variant="band"][/section-wrap]' );
} )(), 'section-wrap--variant-band' ) === false );

// the draft in the place of the real one
themesCall( $site, 'themes/apply', [ 'theme' => 'craft-probe' ] );
$site = themesReboot( $site, $modules );
$labelOf = static fn( string $query, string $variant ): mixed => ( static function() use ( &$site, $query ): array {
	ninoVisit( $site, '/?'. $query );

	return \Nino\Components::variants( $site, 'section-wrap' )['variants'];
} )()[$variant] ?? null;
check( 'the register is told the label of a variant with the variant: the part of the theme that is on gives its word for soft, the draft - which is band - its word for band and none for soft, and a request that names no draft has the active theme\'s', $labelOf( 'x=1', 'soft' ) === $partLabel( 'craft-probe', 'section', 'section-wrap', 'soft' )
	&& $labelOf( 'nino-preview='. $token, 'band' ) === $partLabel( 'band-probe', 'section', 'section-wrap', 'band' ) && $labelOf( 'nino-preview='. $token, 'soft' ) === null && $labelOf( 'x=1', 'band' ) === null );
$craftBundle = \Nino\Modules\Assets::stylesheet( $site, THEMES_BUNDLE );
$visit( 'x=1' );
$publicBytes = (string) file_get_contents( $publicFile );
$preview = $visit( 'nino-preview='. $token );
$themeBlock = array_values( array_filter( $inside( $preview ), static fn( array $item ): bool => $item[0] === '@layer nino.theme' ) );
check( 'with a theme on the website the draft stands where its stylesheet stands: the layer of the theme holds the draft and nothing of the theme that is on, and a request without the token has the theme that is on', count( $themeBlock ) === 1 && str_contains( $themeBlock[0][1], '.preview-marker' ) === true && str_contains( $themeBlock[0][1], '--section-wrap-space: 1.75rem;' ) === false
	&& str_contains( $craftBundle, '--section-wrap-space: 1.75rem;' ) === true && str_contains( $craftBundle, 'preview-marker' ) === false && str_contains( $visit( 'x=1' ), '<link' ) === true && (string) file_get_contents( $publicFile ) === $publicBytes );
check( 'and the draft\'s variants are the ones told: the band for a page that says band, where craft on the website says soft - and again craft\'s in the request that has no token', ( static function() use ( $visit, &$site, $token ): array {

	$classes = [];
	$visit( 'nino-preview='. $token );
	$classes[] = \Nino\Html::renderHtml( $site, '[section-wrap variant="band"][/section-wrap]' );
	$visit( 'x=1' );
	$classes[] = \Nino\Html::renderHtml( $site, '[section-wrap variant="band"][/section-wrap]' );

	return $classes;
} )() === [ '<section class="section-wrap section-wrap--variant-band"></section>', '<section class="section-wrap section-wrap--variant-soft"></section>' ] );

$hostile = Mix::of( $site, 'band-probe' );
$hostile['css'] = ".hostile::before { content: '</style><script>alert(1)</script>'; }\n}\n.escaped { color: red; }";
$withBrand = themesCall( $site, 'themes/preview', [ 'mix' => $hostile, 'brand' => [ 'color-1' => [ 'own' => true, 'value' => '#00aa55' ] ], 'token' => $token ] );
$html = $visit( 'nino-preview='. $withBrand[1]['token'] );
$repaired = ninoWarnings();
check( 'a draft asks with its brand and its css: the colour is the draft\'s and not the project\'s, the project\'s brand is not changed, and a closing tag in the css does not end the style element', $withBrand[0] === 200 && str_contains( $html, '--brand-color-1: #00aa55;' ) === true && substr_count( $html, '</style>' ) === 1
	&& str_contains( $html, "<\\/style><script>" ) === true && is_file( $state. '/state/brand.json' ) === false );
check( 'a css that is not balanced is closed by the compiler before it is in the bundle of the preview, which says nothing of it', $repaired === [] );
check( 'a token posted again is the same draft rewritten while it lasts', $withBrand[1]['token'] === $token && glob( $cache. '/*', GLOB_ONLYDIR ) !== [] );

// who may have a preview
$tokenOf = static fn(): string => themesCall( $site, 'themes/preview', [ 'mix' => $draft ] )[1]['token'];
$shows = static function( string $query ) use ( $visit ): bool {
	return str_contains( $visit( $query ), '<style data-nino-theme-preview>' ) === true;
};
$token = $tokenOf();
$other = '01234567890123456789012345678901';
$request = ninoVisit( $site, '/?nino-preview='. $token );
check( 'the token is the session\'s: the same request, with it, is the draft - and it is an answer that nobody keeps or indexes', $shows( 'nino-preview='. $token ) === true && $request['/nino/http/response']['header']['Cache-Control'] === 'no-store' && $request['/nino/http/response']['header']['X-Robots-Tag'] === 'noindex' && $request['/nino/http/response']['header']['Referrer-Policy'] === 'no-referrer' );
check( 'a visitor without a token has the public page: no style element of a draft, and no mark of a page that is not to be indexed', $shows( 'x=1' ) === false && isset( ninoVisit( $site, '/?x=1' )['/nino/http/response']['header']['X-Robots-Tag'] ) === false );

// Each way a request may not have the draft, with the token of a draft that is real: the page is the public one, and the draft is no matter of the request
$held = $_SESSION[$site['./nino/uid']][$sessionKey];
$_SESSION[$site['./nino/uid']][$sessionKey] = [];
check( 'another session - one that holds no token - gets the page as it is, though the token is a real one with its files', $shows( 'nino-preview='. $token ) === false && is_dir( $cache. '/'. $token ) === true );
$_SESSION[$site['./nino/uid']][$sessionKey] = [ $token => time() - 1 ];
check( 'a token whose time is out is no token', themesKept( $cache. '/'. $token, static fn(): bool => $shows( 'nino-preview='. $token ) === false ) );
$_SESSION[$site['./nino/uid']][$sessionKey] = $held;

$mismatch = [ strtoupper( $token ), substr( $token, 1 ), $token. '0', '../../x', '..%2f..%2fx', $token. '/../'. $token, "$token%00", '', $other ];
check( 'a query that is no token of this session - upper case, too short, too long, a path, a null byte, nothing, one nobody gave out - is a request for the page as it is', array_filter( $mismatch, static fn( string $value ): bool => $shows( 'nino-preview='. $value ) === true ) === [] && $shows( 'nino-preview[]='. $token ) === false && $shows( 'nino-preview[a]=x' ) === false );

ninoUser( $site, 'second@example.com' );
check( 'the account is the one that asked: another account signed in with the right and the session of the token gets no draft', $shows( 'nino-preview='. $token ) === false );
\Nino\Auth::loginUser( $site, 'designer@example.com', 'correct horse battery staple' );
check( 'and the first one has it again', $shows( 'nino-preview='. $token ) === true );

$pictureToken = themesCall( $site, 'themes/thumb', [ 'theme' => 'band-probe' ] )[1]['token'];
$perms = $site['./nino/auth/current']['perms'] ?? null;
$site['./nino/auth/current']['perms'] = [ '/_admin/elements/manage' ];
check( 'the right is asked of the request and not only of the one that made the draft: the same account, the same session and the same token, with the right taken from it, gets the page as it is', $perms !== null && $shows( 'nino-preview='. $token ) === false );
$site['./nino/auth/current']['perms'] = $perms;
check( 'the same holds for the token of a thumbnail: with the right given, it is the draft; with the right taken from the account, the page as it is', $shows( 'nino-preview='. $pictureToken ) === true && ( static function() use ( &$site, $perms, $shows, $pictureToken ): bool {
	$site['./nino/auth/current']['perms'] = [ '/_admin/elements/manage' ];
	$shown = $shows( 'nino-preview='. $pictureToken );
	$site['./nino/auth/current']['perms'] = $perms;

	return $shown === false;
} )() );
\Nino\Auth::logoutUser( $site );
check( 'nobody signed in has none', $shows( 'nino-preview='. $token ) === false );
\Nino\Auth::loginUser( $site, 'designer@example.com', 'correct horse battery staple' );

// how many
for( $i = 0; $i < 7; $i++ )
	$last = themesCall( $site, 'themes/preview', [ 'mix' => $draft ] )[1]['token'];

$heldTokens = array_keys( $_SESSION[$site['./nino/uid']][$sessionKey] );
check( 'a session holds five drafts at the most, the newest of them among them, and the folders of the others are gone', count( $heldTokens ) === \Nino\Modules\Themes::PREVIEWS_MAX && $heldTokens[4] === $last && count( glob( $cache. '/*', GLOB_ONLYDIR ) ) === 5 );

// the folder of the drafts the feature kept itself is taken away by the list, and nothing else of the themes
mkdir( $state. '/.preview/'. $other, 0777, true );
file_put_contents( $state. '/.preview/'. $other. '/theme.css', 'x' );
mkdir( $state. '/.preview/foreign' );
$outside = ninoSandboxDir( $site ). '/outside';
mkdir( $outside );
$linked = @symlink( $outside, $state. '/.preview/'. md5( 'link' ) );
$ranOut = md5( 'ran out' );
mkdir( $cache. '/'. $ranOut );
file_put_contents( $cache. '/'. $ranOut. '/meta.json', json_encode( [ 'user' => 'x', 'expires' => time() - 5 ] ) );
themesCall( $site, 'themes/list' );
check( 'the list takes the drafts the feature kept itself away - the folders in the form of a token, no link, nothing else that is in the folder - and a draft of the kernel that ran out is tidied as well, while the drafts of this session stay', is_dir( $state. '/.preview/'. $other ) === false && is_dir( $state. '/.preview/foreign' ) === true && is_dir( $outside ) === true && ( $linked === false || is_link( $state. '/.preview/'. md5( 'link' ) ) === true )
	&& is_dir( $state. '/.cache' ) === true && is_dir( $cache. '/'. $ranOut ) === false && count( glob( $cache. '/*', GLOB_ONLYDIR ) ) === 5 );
\Nino\Filesystem::removeDir( $state. '/.preview/foreign' );
@unlink( $state. '/.preview/'. md5( 'link' ) );
themesCall( $site, 'themes/list' );
check( 'and the folder goes with the last of them, and the list does not look for it again', is_dir( $state. '/.preview' ) === false );

[ $status, $body ] = themesCall( $site, 'themes/preview', [ 'mix' => [ 'parts' => [ 'section' => [ 'from' => 'nowhere' ] ] ] ] );
check( 'a draft that is not a mix is 400 and writes no draft', $status === 400 && $body['code'] === 'themes_mix' && $body['field'] === 'mix' );
$count = count( glob( $cache. '/*', GLOB_ONLYDIR ) );
[ $status, $body ] = themesCall( $site, 'themes/preview', [ 'mix' => $draft, 'brand' => [ 'color-1' => [ 'own' => true, 'value' => 'red' ] ] ] );
check( 'and so is a brand that is not one', $status === 400 && $body['code'] === 'themes_brand' && $body['field'] === 'brand' && count( glob( $cache. '/*', GLOB_ONLYDIR ) ) === $count );
[ $status, $body ] = themesCall( $site, 'themes/preview', [ 'mix' => $draft, 'token' => '../../../../x' ] );
check( 'a token that is posted and was never given is not used: the draft is a new one, in a folder of the kernel named for it', $status === 200 && preg_match( \Nino\Preview::TOKEN, $body['token'] ) === 1 && is_dir( $cache. '/'. $body['token'] ) === true );
$later = themesReboot( $site, $modules );
check( 'a request that comes after one with a preview in the same process begins with none: the page that says band is drawn as the website draws it', str_contains( \Nino\Html::renderHtml( $later, '[section-wrap variant="band"][/section-wrap]' ), 'section-wrap--variant-soft' ) === true );
$numbers['preview request'] = themesFastest( static function() use ( $visit, $token ): void {
	$visit( 'nino-preview='. $token );
} );

// ---------------------------------------------------------------------------------------------------------------------------------
echo "\nH. Rights and names\n";

$site = themesSite();
ninoUser( $site, 'designer@example.com' );
ninoUser( $site, 'editor@example.com', [ '/_admin/elements/manage', '/_admin/text/manage' ] );
$state = ninoSandboxDir( $site ). '/private/data/theme';
$untouched = static fn(): bool => is_dir( $state. '/state' ) === false && is_dir( $state. '/previous' ) === false && is_dir( $state. '/copies' ) === false && is_dir( $state. '/next' ) === false;
$every = [
	'themes/list'			=> [],
	'themes/open'			=> [ 'theme' => 'craft-probe' ],
	'themes/preview'	=> [ 'mix' => [ 'parts' => [ 'section' => [ 'from' => 'craft-probe' ] ] ] ],
	'themes/save'			=> [ 'theme' => 'own:x', 'mix' => themesMix( 'X', [] ) ],
	'themes/thumb'		=> [ 'theme' => 'craft-probe' ],
	'themes/save-as'	=> [ 'mix' => themesMix( 'X', [] ) ],
	'themes/duplicate'	=> [ 'theme' => 'craft-probe', 'name' => 'X' ],
	'themes/brand'		=> [ 'brand' => [], 'brandBase' => 'x' ],
	'themes/apply'		=> [ 'theme' => 'craft-probe' ],
	'themes/restore'	=> [],
	'themes/delete'		=> [ 'theme' => 'own:x' ],
	'themes/upload-check'	=> [],
	'themes/upload'		=> [ 'id' => '0000000000000000' ],
	'themes/upload-drop'	=> [ 'id' => '0000000000000000' ],
	'themes/remove'		=> [ 'theme' => 'craft-probe' ],
];
$answers = [];

foreach( $every as $action => $data ) {
	[ $status, $body ] = themesCall( $site, $action, $data );
	$answers[$action] = [ $status, $body ];
}

check( 'Nr. 9: an account that has not the right - the Editor of the starter site has not - gets 403 on every one of the fifteen actions, and nothing is read or written: no state, no stylesheet, no draft, no cache of the themes, nothing held by the session',
	count( $answers ) === 15 && array_filter( $answers, static fn( array $answer ): bool => $answer[0] !== 403 ) === [] && is_dir( $state ) === false && is_file( ninoSandboxDir( $site ). '/private/assets/theme.css' ) === false
	&& is_dir( ninoSandboxDir( $site ). '/private/data/theme/.cache' ) === false && isset( $_SESSION[$site['./nino/uid']]['/nino/preview/themes'] ) === false && isset( $_SESSION[$site['./nino/uid']]['/nino/preview/themes-pictures'] ) === false );

\Nino\Auth::logoutUser( $site );
$answers = [];

foreach( $every as $action => $data )
	$answers[$action] = themesCall( $site, $action, $data )[0];

check( 'Nr. 9: and nobody signed in gets none of them', array_unique( array_values( $answers ) ) === [ 401 ] || array_filter( $answers, static fn( int $status ): bool => in_array( $status, [ 401, 403 ], true ) === false ) === [] );

ninoUser( $site, 'scoped@example.com', [ '/_admin/themes/manage' ] );
check( 'Nr. 9: the right is /_admin/themes/manage and nothing wider needs to be held: an account that has it and nothing else may use them', themesCall( $site, 'themes/list' )[0] === 200 && themesCall( $site, 'themes/preview', [ 'mix' => Mix::of( $site, 'band-probe' ) ] )[0] === 200 );
ninoUser( $site, 'wild@example.com', [ '/_admin/themes/*' ] );
check( 'and one that has it as the ancestor of a permission, as the kernel reads them', themesCall( $site, 'themes/list' )[0] === 200 );
\Nino\Auth::loginUser( $site, 'designer@example.com', 'correct horse battery staple' );

// names: slugs only, and nothing that climbs
$strange = [ '../..', 'own:../x', 'own:A', "own:\0", str_repeat( 'a', 300 ), 'own:', '', 'own:own:x', '/', 'craft-probe/../x', 'craft-probe ', 'CRAFT-PROBE', 'craft-probe@1.0.0', 'own:'. str_repeat( 'a', 300 ), "craft-probe\n", 'own:x/../../y', '..', 'own:..' ];
$refused = [];

foreach( $strange as $name )
	foreach( [ 'themes/open', 'themes/apply', 'themes/delete', 'themes/save' ] as $action ) {

		[ $status, $body ] = themesCall( $site, $action, [ 'theme' => $name, 'mix' => themesMix( 'X', [] ) ] );
		$refused[] = $status === 404 && $body['code'] === 'themes_unknown' && strlen( $body['params'][0] ) <= 60 && str_contains( $body['params'][0], "\0" ) === false;
	}

check( 'Nr. 10: a name of a theme is a key there is - a slug, or own: and a slug - and nothing else is looked for: eighteen that are none are 404 for each of four actions, with the name in the sentence cut short and with no control character in it', count( $refused ) === 72 && array_unique( $refused ) === [ true ] );
check( 'and nothing was written, read outside the project or warned of', $untouched() === true && ninoWarnings() === [] );

$bad = [
	'a group that is no slug'							=> [ 'parts' => [ '../x' => [ 'from' => 'standard' ] ] ],
	'a group with a capital'							=> [ 'parts' => [ 'Section' => [ 'from' => 'standard' ] ] ],
	'a group with a blank'								=> [ 'parts' => [ 'a b' => [ 'from' => 'standard' ] ] ],
	'a group of a hundred characters'			=> [ 'parts' => [ str_repeat( 'a', 100 ) => [ 'from' => 'standard' ] ] ],
	'forty-one groups'										=> [ 'parts' => array_fill_keys( array_map( static fn( int $i ): string => 'group-'. $i, range( 1, 41 ) ), [ 'from' => 'standard' ] ) ],
	'a part that is a string'							=> [ 'parts' => [ 'section' => 'craft-probe' ] ],
	'a part with a key of its own'				=> [ 'parts' => [ 'section' => [ 'from' => 'standard', 'brand' => [] ] ] ],
	'a path as the theme'									=> [ 'parts' => [ 'section' => [ 'from' => '../../x@1.0.0' ] ] ],
	'a path as the version'								=> [ 'parts' => [ 'section' => [ 'from' => 'craft-probe@../../x' ] ] ],
	'a path after the version'						=> [ 'parts' => [ 'section' => [ 'from' => 'craft-probe@1.0.0/../x' ] ] ],
	'a semicolon after it'								=> [ 'parts' => [ 'section' => [ 'from' => 'craft-probe@1.0.0;' ] ] ],
	'standard with a blank'								=> [ 'parts' => [ 'section' => [ 'from' => 'standard ' ] ] ],
	'a theme in capitals'									=> [ 'parts' => [ 'section' => [ 'from' => 'CRAFT-PROBE' ] ] ],
	'a version nothing has'								=> [ 'parts' => [ 'section' => [ 'from' => 'craft-probe@9.9.9' ] ] ],
	'a theme that is not there'						=> [ 'parts' => [ 'section' => [ 'from' => 'nowhere' ] ] ],
	'nothing as the theme'								=> [ 'parts' => [ 'section' => [ 'from' => '' ] ] ],
	'a number as the theme'								=> [ 'parts' => [ 'section' => [ 'from' => 5 ] ] ],
	'a list as the theme'									=> [ 'parts' => [ 'section' => [ 'from' => [ 'craft-probe' ] ] ] ],
	'step three'													=> [ 'parts' => [ 'section' => [ 'from' => 'craft-probe', 'values' => [ 'section-wrap.space' => 3 ] ] ] ],
	'step minus three'										=> [ 'parts' => [ 'section' => [ 'from' => 'craft-probe', 'values' => [ 'section-wrap.space' => -3 ] ] ] ],
	'a step that is a text'								=> [ 'parts' => [ 'section' => [ 'from' => 'craft-probe', 'values' => [ 'section-wrap.space' => '1' ] ] ] ],
	'a step that is a fraction'						=> [ 'parts' => [ 'section' => [ 'from' => 'craft-probe', 'values' => [ 'section-wrap.space' => 1.5 ] ] ] ],
	'a step that is nothing'							=> [ 'parts' => [ 'section' => [ 'from' => 'craft-probe', 'values' => [ 'section-wrap.space' => null ] ] ] ],
	'a step that is true'									=> [ 'parts' => [ 'section' => [ 'from' => 'craft-probe', 'values' => [ 'section-wrap.space' => true ] ] ] ],
	'a knob with a semicolon'							=> [ 'parts' => [ 'section' => [ 'from' => 'craft-probe', 'values' => [ 'a;b' => 1 ] ] ] ],
	'a knob with a brace'									=> [ 'parts' => [ 'section' => [ 'from' => 'craft-probe', 'values' => [ 'section-wrap.space}' => 1 ] ] ] ],
	'a knob of a hundred characters'			=> [ 'parts' => [ 'section' => [ 'from' => 'craft-probe', 'values' => [ str_repeat( 'a', 100 ) => 1 ] ] ] ],
	'values that are a text'							=> [ 'parts' => [ 'section' => [ 'from' => 'craft-probe', 'values' => 'section-wrap.space' ] ] ],
	'a variant that is a path'						=> [ 'parts' => [ 'section' => [ 'from' => 'craft-probe', 'standard' => [ 'section-wrap' => '../x' ] ] ] ],
	'a component in capitals'							=> [ 'parts' => [ 'section' => [ 'from' => 'craft-probe', 'standard' => [ 'Section-Wrap' => 'soft' ] ] ] ],
	'a standard that is a text'						=> [ 'parts' => [ 'section' => [ 'from' => 'craft-probe', 'standard' => 'soft' ] ] ],
	'the brand in the mix (M5)'						=> [ 'brand' => [ 'color-1' => [ 'own' => true, 'value' => '#123456' ] ] ],
	'a key beside name, css and parts'		=> [ 'theme' => 'craft-probe' ],
	'parts that are a text'								=> [ 'parts' => 'section' ],
	'a css with a null byte'							=> [ 'css' => ".a { color: red; }\0" ],
	'a css that is no UTF-8 - a list'			=> [ 'css' => [ 'x' ] ],
	'a css of 70 000 bytes'								=> [ 'css' => str_repeat( '.a { color: red; }', 4000 ) ],
	'a name that is a list'								=> [ 'name' => [ 'X' ] ],
];
$refused = [];

foreach( $bad as $what => $mix ) {

	[ $status, $body ] = themesCall( $site, 'themes/save-as', [ 'mix' => array_merge( [ 'name' => 'X' ], $mix ) ] );
	$refused[$what] = $status === 400 && in_array( $body['code'], [ 'themes_mix', 'themes_name' ], true ) === true && strlen( $body['error'] ) < 400 && str_contains( json_encode( $body ), "\0" ) === false && str_contains( $body['error'], ninoSandboxDir( $site ) ) === false;
}

check( 'Nr. 10: a mix that holds a name that is no slug, a path, a step beyond two or no whole number, a knob that is no knob, a brand, a key of its own, a null byte or a css too long is 400 with the code and a sentence of a few words - '. count( $bad ). ' of them, each', count( $refused ) === count( $bad ) && array_filter( $refused, static fn( bool $ok ): bool => $ok === false ) === [] );
check( 'and none of them wrote anything: no state, no copy, no own theme, no stylesheet, no folder of a state being made', $untouched() === true && glob( ninoSandboxDir( $site ). '/private/assets/*' ) === [] && ninoWarnings() === [] );

[ $status, $body ] = themesCall( $site, 'themes/save-as', [ 'mix' => themesMix( 'X', [] ), 'brand' => [ 'color-1' => [ 'own' => true, 'value' => '#12345g' ] ] ] );
check( 'Nr. 10: the colour of the brand is #rrggbb and nothing else - one that is not is 400 at the brand, with the key in the sentence, and nothing is written', $status === 400 && $body['code'] === 'themes_brand' && $body['field'] === 'brand' && $body['params'] === [ 'color-1' ] && $untouched() === true );
foreach( [ [ 'font-title', 'nope' ], [ 'font-text', '../../etc' ], [ 'logo', 'x.svg' ] ] as [ $key, $value ] ) {
	[ $status, $body ] = themesCall( $site, 'themes/save-as', [ 'mix' => themesMix( 'X', [] ), 'brand' => [ $key => [ 'own' => true, 'value' => $value ] ] ] );
	$wrongFont[] = $status === 400 && $body['code'] === 'themes_brand' && $body['params'] === [ $key ];
}
check( 'Nr. 10: the font of the brand is the slug of a font that is there; a value that is a path, a folder that is not one and a key that is none - a logo - are 400', array_unique( $wrongFont ) === [ true ] && $untouched() === true );

// R1: what neither the register nor the page knows is not kept, a mix is as large as it may be and not larger
$rows = [];

foreach( range( 1, 39 ) as $n )
	$rows['group-'. $n] = [ 'from' => 'standard', 'values' => array_fill_keys( array_map( static fn( int $i ): string => 'c-'. $i. '.k-'. $i, range( 1, 40 ) ), 2 ), 'standard' => array_fill_keys( array_map( static fn( int $i ): string => 'c-'. $i, range( 1, 40 ) ), 'x' ) ];

$rows['section'] = [ 'from' => 'standard', 'values' => [ 'section-wrap.x-evil' => 1, 'nope.nope' => 1, 'section-wrap.space' => 1 ], 'standard' => [ 'nope' => 'x', 'section-wrap' => 'regular' ] ];
$cleaned = Mix::clean( $site, [ 'name' => 'Big', 'parts' => $rows ], true );
check( 'a mix of thirty-nine groups the register has never heard of, with forty knobs and forty components each, comes out as the one group it has - with the one knob and the one component of it that exist', is_array( $cleaned ) === true && array_keys( $cleaned['parts'] ) === [ 'section' ] && $cleaned['parts']['section'] === [ 'from' => 'standard', 'standard' => [ 'section-wrap' => 'regular' ], 'values' => [ 'section-wrap.space' => 1 ] ] );
[ $status, $body ] = themesCall( $site, 'themes/save-as', [ 'mix' => [ 'name' => 'Big', 'parts' => $rows ] ] );
check( 'and what is written of it is small, far under what the store reads of a file', $status === 200 && filesize( $state. '/state/own/big.json' ) < 2048 && Mix::BYTES <= \Nino\Modules\Themes\Store::MAX_FILE );
$tooMany = $rows['section'];
$tooMany['values'] = array_fill_keys( array_map( static fn( int $i ): string => 'section-wrap.k-'. $i, range( 1, 41 ) ), 1 );
[ $status, $body ] = themesCall( $site, 'themes/save-as', [ 'mix' => [ 'name' => 'Too many', 'parts' => [ 'section' => $tooMany ] ] ] );
check( 'more than forty knobs of a group is 400 with the reason', $status === 400 && $body['code'] === 'themes_mix' && str_contains( $body['params'][0], 'values' ) === true );
[ $statusWhole, $whole ] = themesCall( $site, 'themes/preview', [ 'mix' => [ 'name' => 'x', 'parts' => 'no' ] ] );
check( 'a refused mix names the group it is wrong at as its second param, for the panel to say in its own word - the key of the group, \'page\' for the page, and nothing where the whole mix or its css is wrong - beside the sentence for the log',
	$body['params'][1] === 'section' && $statusWhole === 400 && $whole['params'][1] === '' && themesCall( $site, 'themes/preview', [ 'mix' => [ 'parts' => [ 'page' => [ 'from' => 'nowhere' ] ] ] ] )[1]['params'][1] === 'page'
	&& themesCall( $site, 'themes/preview', [ 'mix' => [ 'css' => str_repeat( 'a', Mix::CSS_LENGTH + 1 ) ] ] )[1]['params'][1] === '' );
[ $status, $body ] = themesCall( $site, 'themes/save-as', [ 'mix' => themesMix( 'Big', [] ) ] );
check( 'a save as a name that is there says so - 409 - and does not write over it', $status === 409 && $body['code'] === 'themes_name_taken' && filesize( $state. '/state/own/big.json' ) < 2048 );

// R3: the filter of the keys of own themes, the limit of what is read, a token that ran out
\Nino\Filesystem::putFileContent( $site, '/data/theme/state/own/UPPER.json', [ 'name' => 'Upper' ] );
\Nino\Filesystem::putFileContent( $site, '/data/theme/state/own/has space.json', [ 'name' => 'Space' ] );
\Nino\Filesystem::putFileContent( $site, '/data/theme/state/own/.hidden.json', [ 'name' => 'Hidden' ] );
\Nino\Filesystem::putFileContent( $site, '/data/theme/state/own/ok-key.json', [ 'name' => 'Fine' ] );
file_put_contents( $state. '/state/own/huge.json', json_encode( [ 'name' => 'Huge', 'css' => str_repeat( 'x', \Nino\Modules\Themes\Store::MAX_FILE + 100 ) ] ) );
$own = array_keys( \Nino\Modules\Themes\Store::read( $site )['own'] );
sort( $own );
check( 'a file of own/ is an own theme where its name is a key - and not a name with a capital, a blank or a dot in front - and where it is not larger than the store reads', $own === [ 'big', 'ok-key' ] );
unlink( $state. '/state/own/huge.json' );

$held = $_SESSION[$site['./nino/uid']]['/nino/preview/themes'] ?? [];
$gone = themesCall( $site, 'themes/preview', [ 'mix' => $draft = Mix::of( $site, 'band-probe' ) ] )[1]['token'];
$_SESSION[$site['./nino/uid']]['/nino/preview/themes'][$gone] = time() - 1;
$again = themesCall( $site, 'themes/preview', [ 'mix' => $draft, 'token' => $gone ] )[1]['token'];
check( 'a token that ran out in the session is not taken again by a preview that names it: the draft is a new one', $again !== $gone && preg_match( \Nino\Preview::TOKEN, $again ) === 1 );

// the schema's variants stay where a theme tells the register its own
$extra = [ 'de_DE' => 'Extra', 'en_US' => 'Extra' ];
\Nino\Filesystem::putFileContent( $site, '/data/theme/state/variants.php', [ 'section-col' => [ [ 'extra' ], 'card', [ 'extra' => $extra ] ] ] );
check( 'what the theme tells the register of a component is added to what the schema says and does not replace it: the card stays, the standard is the theme\'s, and the label of the variant it adds goes with it', \Nino\Modules\Themes::variants( $site, [ 'name' => 'section-col', 'variants' => [ 'regular', 'card' ], 'standard' => 'regular' ] ) === [ 'name' => 'section-col', 'variants' => [ 'regular', 'card', 'extra' ], 'standard' => 'card', 'labels' => [ 'extra' => $extra ] ] );

// what a theme brings is data, and the data of this project is what it writes
$leaf = themesSite();
ninoUser( $leaf, 'designer@example.com' );
file_put_contents( ninoSandboxDir( $leaf ). '/private/data/leak.txt', 'secret' );
\Nino\Filesystem::putFileContent( $leaf, '/data/theme/state/own/leaky.json', [ 'name' => 'Leaky', 'parts' => [ 'section' => [ 'from' => '../../../../data/leak.txt@1.0.0' ] ] ] );
[ $status, $body ] = themesCall( $leaf, 'themes/apply', [ 'theme' => 'own:leaky' ] );
check( 'a state somebody wrote by hand is checked as a post is: a part that names a path is not followed - the answer is 409, the reason has no path and no content of a file', $status === 409 && $body['code'] === 'themes_mix' && str_contains( json_encode( $body ), 'secret' ) === false && str_contains( json_encode( $body ), ninoSandboxDir( $leaf ) ) === false );

// ---------------------------------------------------------------------------------------------------------------------------------
echo "\nI. Sizes, the feature off and on, and the time it takes\n";

$modulesOff = [ '\\Nino\\Modules\\Assets', '\\Nino\\Modules\\Basis' ];
$off = themesSite( false );
$without = \Nino\Modules\Assets::stylesheet( $off, THEMES_BUNDLE );
$site = themesSite();
ninoUser( $site, 'designer@example.com' );
\Nino\Filesystem::putFileContent( $site, '/templates/page-hero.tpl', THEMES_PAGE );
themesCall( $site, 'themes/apply', [ 'theme' => 'band-probe' ] );
$sheet = (string) file_get_contents( ninoSandboxDir( $site ). '/private/assets/theme.css' );
$with = \Nino\Modules\Assets::stylesheet( themesReboot( $site, $modules ), THEMES_BUNDLE );
$themeDir = ninoSandboxDir( $site ). '/themes/band-probe';
$partBytes = array_sum( array_map( 'filesize', glob( $themeDir. '/parts/*/part.css' ) ) );
$allBytes = array_sum( array_map( 'filesize', array_merge( glob( $themeDir. '/parts/*/part.*' ), [ $themeDir. '/theme.json' ] ) ) );
$numbers['css of the parts'] = $partBytes;
$numbers['files of the theme'] = $allBytes;
$numbers['theme.css'] = strlen( $sheet );
$numbers['bundle with'] = strlen( $with );
$numbers['bundle without'] = strlen( $without );
check( 'Nr. 11: sizes of a theme with a rule, a band and a density: the css of its parts below 4 KB and all its files below 12 KB, the stylesheet written below 8 KB, the bundle of the site below 45 KB, and what the feature adds to it - the stylesheet written - below 6 KB: the bundle without it is the kernel\'s to size', $partBytes < 4096 && $allBytes < 12288 && strlen( $sheet ) < 8192 && strlen( $with ) < 45 * 1024 && strlen( $with ) - strlen( $without ) < 6 * 1024 && strlen( $with ) > strlen( $without ) );

// the feature off: the site as it is with no theme; on again: the state that was saved
themesCall( $site, 'themes/apply', [ 'theme' => 'craft-probe' ] );
$stateBefore = $digest( ninoSandboxDir( $site ). '/private/data/theme' );
$sheetBefore = (string) file_get_contents( ninoSandboxDir( $site ). '/private/assets/theme.css' );
$silent = themesReboot( $site, $modulesOff );
$page = file_get_contents( ninoSandboxDir( $site ). '/private/templates/page-hero.tpl' );
check( 'Nr. 13: with the feature off the bundle is the bundle of a site that never had it, byte for byte - the stylesheet is on the disk and not in the bundle - and the register is told no variant: the page that says band shows none',
	\Nino\Modules\Assets::stylesheet( $silent, THEMES_BUNDLE ) === $without && is_file( ninoSandboxDir( $site ). '/private/assets/theme.css' ) === true && \Nino\Callbacks::registered( $silent, \Nino\Components::VARIANTS ) === [] && \Nino\Callbacks::registered( $silent, '/nino/http/request' ) === []
	&& str_contains( \Nino\Html::renderHtml( $silent, $page ), '--variant-band' ) === false && str_contains( \Nino\Html::renderHtml( $silent, $page ), '--variant-soft' ) === false && str_contains( \Nino\Html::renderHtml( $silent, $page ), '--variant-stacked' ) === false );
$back = themesReboot( $site, $modules );
check( 'Nr. 13: on again, the state that was saved is the state - nothing was lost while it was off: the stylesheet is in the bundle, the page says band and gets craft\'s soft, and the state of the themes is the files it was', str_contains( \Nino\Modules\Assets::stylesheet( $back, THEMES_BUNDLE ), '@layer nino.theme' ) === true
	&& str_contains( \Nino\Html::renderHtml( $back, $page ), 'section-wrap--variant-soft' ) === true && $digest( ninoSandboxDir( $site ). '/private/data/theme' ) === $stateBefore && (string) file_get_contents( ninoSandboxDir( $site ). '/private/assets/theme.css' ) === $sheetBefore
	&& themesCall( $site, 'themes/list' )[1]['active']['theme'] === 'craft-probe' );

// time: building, the first request after, the request, the preview
$flip = 0;
$numbers['compile'] = themesFastest( static function() use ( &$site, $craftMix ): void {
	Compiler::compile( $site, $craftMix, Brand::clean( $site, [] ) );
} );
$numbers['save and build'] = themesFastest( static function() use ( &$site, &$flip ): void {
	themesCall( $site, 'themes/apply', [ 'theme' => [ 'band-probe', 'craft-probe' ][$flip++ % 2] ] );
} );

$first = INF;

for( $round = 0; $round < 5; $round++ ) {

	themesCall( $site, 'themes/apply', [ 'theme' => [ 'band-probe', 'craft-probe' ][$flip++ % 2] ] );
	$fresh = themesReboot( $site, $modules );
	$started = hrtime( true );
	\Nino\Html::renderHtml( $fresh, '[assets '. THEMES_BUNDLE. ']' );
	$first = min( $first, ( hrtime( true ) - $started ) / 1e6 );
}

$numbers['first request after a save'] = $first;
$onSite = themesReboot( $site, $modules );
$offSite = themesReboot( $site, $modulesOff );
$asking = [ '/nino/http/request' => [ 'query' => [], 'method' => 'GET', 'uri' => '/' ] ];
// A request with a theme and one without, one after the other, a thousand pairs in four rounds: what a machine that is busy adds, it adds
// to both of a pair. The round that cost the least over the page without a theme is the one that is pinned - a load can add to the
// difference, it cannot take from it - and every round is told
$initRun = static function() use ( $offSite ): void {
	$run = $offSite;
	\Nino\Modules\Themes::init( $run );
};
$drawRun = static function() use ( $offSite, $page ): void {
	$run = $offSite;
	\Nino\Html::renderHtml( $run, $page );
};
$themedRun = static function() use ( $onSite, $page, $asking ): void {
	$run = $onSite;
	\Nino\Modules\Themes::request( $run, $asking );
	\Nino\Html::renderHtml( $run, $page );
};
$rounds = [];
$initTotal = 0.0;

for( $round = 0; $round < 4; $round++ ) {

	$plain = 0.0;
	$themed = 0.0;
	$init = 0.0;

	for( $pair = 0; $pair < 250; $pair++ ) {

		$started = hrtime( true );
		$drawRun();
		$plain += hrtime( true ) - $started;
		$started = hrtime( true );
		$themedRun();
		$themed += hrtime( true ) - $started;
		$started = hrtime( true );
		$initRun();
		$init += hrtime( true ) - $started;
	}

	$rounds[] = ( $themed + $init - $plain ) / 250 / 1e6;
	$initTotal += $init;
}

$over = min( $rounds );
$initial = $initTotal / 1000 / 1e6;
$extra = $over - $initial;
echo sprintf( "  ..  - the four rounds over a site without a theme, in ms a request: %s\n", implode( ', ', array_map( static fn( float $round ): string => sprintf( '%.3f', $round ), $rounds ) ) );
$numbers['request, over a site without'] = $over;
echo sprintf( "  ..  - a request with a theme costs %.3f ms over one without at the least: %.3f ms for init() of the module, %.3f ms more to draw a page of nine components with the theme's variants than without\n", $over, $initial, $extra );

$previewBody = themesCall( $site, 'themes/preview', [ 'mix' => $draft ] )[1];
$numbers['preview, the API'] = themesFastest( static function() use ( &$site, $draft ): void {
	themesCall( $site, 'themes/preview', [ 'mix' => $draft ] );
} );

foreach( $numbers as $name => $value )
	echo sprintf( "  ..  - %s: %s\n", $name, is_float( $value ) === true ? sprintf( '%.3f ms', $value ) : $value. ' bytes' );

// The times are told on every run, above. What is pinned is a budget far from what is measured - around ten times it on the machine that
// sized them (compile 4 ms, save 7 ms, first request 1 ms, 0.07 ms over a site without, preview 0.06 ms, its API 7 ms): the runner of a
// shared service is several times slower from one run to the next, and a slow machine is no reason for a failure
check( 'Nr. 12: building takes up to 50 ms (the compiler) and up to 100 ms as a save that builds, writes and rotates the state, the first request after a save up to 30 ms, a request with a theme not more than 0.75 ms over one without, and the request of a preview up to 10 ms, the API that makes one up to 80 ms (the shortest of nine runs for each, and the four rounds of the request told as they were)',
	$numbers['compile'] <= 50 && $numbers['save and build'] <= 100 && $numbers['first request after a save'] <= 30 && $numbers['request, over a site without'] <= 0.75 && $numbers['preview request'] <= 10 && $numbers['preview, the API'] <= 80 );

// ---------------------------------------------------------------------------------------------------------------------------------
echo "\nJ. The theme Nino\n";

use Nino\Modules\Themes\Reader;

$ignored = ninoWarnings();

// The theme the feature ships, as the files lie and as the reader and the compiler take it. The middle of every knob is read from the
// files the site is drawn without a theme from - Nino.css and the standard parts of Basis - and written down nowhere here
$ninoDir = $featureDir. '/themes/nino';
$site = themesSite();
ninoUser( $site, 'designer@example.com' );
$ninoRead = Reader::read( $site );
$nino = $ninoRead['themes']['nino'] ?? [ 'parts' => [], 'brand' => [], 'name' => [], 'description' => [] ];
$ninoParts = [];

foreach( glob( $ninoDir. '/parts/*', GLOB_ONLYDIR ) ?: [] as $dir )
	$ninoParts[basename( $dir )] = [ 'json' => json_decode( (string) file_get_contents( $dir. '/part.json' ), true ), 'css' => is_file( $dir. '/part.css' ) === true ? (string) file_get_contents( $dir. '/part.css' ) : null ];

$ninoFiles = [];

foreach( new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $ninoDir, FilesystemIterator::SKIP_DOTS ) ) as $file )
	$ninoFiles[] = substr( $file->getPathname(), strlen( $ninoDir ) + 1 );

sort( $ninoFiles );
check( 'Nino is a folder of data in the form of a theme: theme.json, the part of the page with no css, a part with css for the header, the footer and the section - none for the buttons, the image and the text, which stay the standard parts - and the block of a hero',
	$ninoFiles === [ 'blocks/hero-band/block.json', 'blocks/hero-band/block.tpl', 'blocks/hero-band/text/de_DE.json', 'blocks/hero-band/text/en_US.json', 'parts/footer/part.css', 'parts/footer/part.json', 'parts/header/part.css', 'parts/header/part.json', 'parts/page/part.json', 'parts/section/part.css', 'parts/section/part.json', 'theme.json' ] );
check( 'the reader takes it as it is: no folder refused, the key and the folder agree, and every part holds - none rests - for the page and the three groups it has a part for',
	$ninoRead['rejected'] === [] && ( $nino['key'] ?? '' ) === 'nino' && array_keys( $nino['parts'] ) === [ 'footer', 'header', 'page', 'section' ]
	&& array_filter( $nino['parts'], static fn( array $part ): bool => $part['ok'] !== true || $part['problem'] !== '' ) === [] );

[ $status, $body ] = themesCall( $site, 'themes/list' );
$listed = array_values( array_filter( (array) ( $body['themes'] ?? [] ), static fn( array $theme ): bool => $theme['key'] === 'nino' ) );
check( 'the list of the API names it with its words in both languages, and not one of its parts rests', $status === 200 && count( $listed ) === 1 && $listed[0]['name']['de_DE'] === 'Nino' && is_string( $listed[0]['description']['de_DE'] ?? null ) === true
	&& array_filter( $listed[0]['parts'], static fn( array $part ): bool => $part['ok'] !== true ) === [] && array_keys( $listed[0]['parts'] ) === [ 'footer', 'header', 'page', 'section' ] );

// words: every one in both languages, and the two differ where a name is not the same in both
$words = [ [ 'name', $nino['name'] ?? null ], [ 'description', $nino['description'] ?? null ] ];

foreach( $ninoParts as $group => $part ) {

	foreach( $part['json']['knobs'] ?? [] as $key => $knob )
		$words[] = [ $group. ' knob '. $key, $knob['label'] ?? null ];

	foreach( $part['json']['variants'] ?? [] as $component => $variants )
		foreach( $variants as $variant => $label )
			$words[] = [ $component. ' variant '. $variant, $label ];
}

$bothLanguages = static fn( mixed $label ): bool => is_array( $label ) === true && array_keys( $label ) === [ 'de_DE', 'en_US' ] && is_string( $label['de_DE'] ) === true && is_string( $label['en_US'] ) === true && trim( $label['de_DE'] ) !== '' && trim( $label['en_US'] ) !== '';
$silent = array_filter( $words, static fn( array $word ): bool => $bothLanguages( $word[1] ) === false || ( $word[1]['de_DE'] === $word[1]['en_US'] && $word[0] !== 'name' && $word[0] !== 'section-wrap variant band' ) );
check( 'every word of the theme - its name, its description, the label of each of '. ( count( $words ) - 2 ). ' knobs and variants - is a text in de_DE and en_US and no other language, and the two differ but where the word is the same in both', count( $words ) === 15 && $silent === [] );
check( 'it suggests a system font for the title, the text and the buttons, the neutral tone and the mode of the visitor - and no colour and no logo, which are the project\'s', array_keys( $nino['brand'] ?? [] ) === [ 'font-title', 'font-text', 'font-button', 'tone', 'mode' ]
	&& ( $nino['brand'] ?? [] ) == array_intersect_key( Brand::DEFAULTS, array_flip( [ 'font-title', 'font-text', 'font-button', 'tone', 'mode' ] ) ) );

// the page: the knobs, five steps each, the same tokens in every step but where a knob for small screens leaves one as it is
$pageKnobs = $nino['parts']['page']['part']['knobs'] ?? [];
check( 'the page has the knobs of its part in the order a person meets them - size, width, spacing, type, weight, radius, line, motion and the density of small screens - five steps each, with the same tokens in every step but the density\'s, whose middle and upper steps leave the page as it is',
	array_keys( $pageKnobs ) === [ 'size', 'width', 'space', 'text', 'weight', 'radius', 'line', 'motion', 'density' ] && array_keys( array_filter( $pageKnobs, static fn( array $knob ): bool => $knob['small'] === true ) ) === [ 'density' ]
	&& array_filter( $pageKnobs, static fn( array $knob ): bool => count( $knob['ladder'] ) !== 5 ) === []
	&& array_filter( $pageKnobs, static fn( array $knob ): bool => $knob['small'] === false && count( array_unique( array_map( static fn( array $set ): string => implode( ',', array_keys( $set ) ), $knob['ladder'] ) ) ) !== 1 ) === []
	&& array_map( 'count', $pageKnobs['density']['ladder'] ) === [ 2, 2, 0, 0, 0 ] );

// the middle is the reset: each token of a step in the middle is the value Nino.css gives it, as the file says it
$reset = (string) file_get_contents( $root. '/_nino/Nino.css' );
preg_match( '/@layer nino\.reset \{\s*:root \{(.*?)\n\t\}/s', $reset, $block );
preg_match_all( '/(--[a-z0-9-]+):\s*([^;]+);/', $block[1] ?? '', $found );
$resetTokens = array_combine( $found[1], array_map( static fn( string $value ): string => (string) preg_replace( '/\s+/', '', $value ), $found[2] ) );
$flat = static fn( string $value ): string => (string) preg_replace( '/\s+/', '', $value );
$middle = [];

foreach( $pageKnobs as $name => $knob )
	foreach( $knob['ladder'][2] as $token => $value )
		$middle[$name. ' '. $token] = [ $flat( $value ), $resetTokens[$token] ?? null ];

check( 'the middle step of every knob of the page is the value of Nino.css for the token, so that a page with no knob turned is the page of the reset: '. count( $middle ). ' tokens, read from the file',
	count( $middle ) === 18 && array_filter( $middle, static fn( array $pair ): bool => $pair[0] !== $pair[1] ) === [] && array_filter( $middle, static fn( array $pair ): bool => $pair[1] === null ) === [] );

// and the tokens of the page that every step of a knob names are tokens of the page: the contract's list, and nothing else
$named = array_unique( array_merge( [], ...array_values( array_map( static fn( array $knob ): array => array_merge( [], ...array_map( 'array_keys', $knob['ladder'] ) ), $pageKnobs ) ) ) );
check( 'the tokens the steps set are tokens of the contract\'s list, and each of them has a middle - the reset\'s - in Nino.css', array_diff( $named, Reader::PAGE_TOKENS ) === [] && array_diff( $named, array_keys( $resetTokens ) ) === [] );

// the order of the steps: what a knob turns up is turned up in every step, for the knobs whose steps are plain numbers
$number = static fn( string $value ): ?float => preg_match( '/^(\d*\.?\d+)(rem|px|%|s)?$/', $value, $part ) === 1 ? (float) $part[1] : null;
$unordered = [];
$factors = [];
$measured = 0;

foreach( [ 'size', 'width', 'space', 'weight', 'radius', 'line', 'motion' ] as $name )
	foreach( array_keys( $pageKnobs[$name]['ladder'][0] ?? [] ) as $token ) {

		$line = array_map( static fn( array $set ): ?float => $number( $flat( $set[$token] ) ), $pageKnobs[$name]['ladder'] );
		$measured++;

		if( in_array( null, $line, true ) === true || $line !== ( static function( array $line ): array { sort( $line ); return $line; } )( $line ) )
			$unordered[] = $name. ' '. $token;
	}

// the steps of the type are the middle times a factor, which grows
foreach( $pageKnobs['text']['ladder'] as $step => $set )
	foreach( $set as $token => $value ) {

		$factor = $step === 2 ? 1.0 : ( preg_match( '/^calc\(\s*(.+?)\s*\*\s*(\d*\.?\d+)\s*\)$/', $value, $part ) === 1 && $flat( $part[1] ) === $resetTokens[$token] ? (float) $part[2] : null );
		$factors[$token][$step] = $factor;
	}

foreach( $factors as $token => $line ) {

	$measured++;

	if( in_array( null, $line, true ) === true || $line !== ( static function( array $line ): array { sort( $line ); return $line; } )( $line ) || count( array_unique( $line ) ) !== 5 )
		$unordered[] = 'text '. $token;
}

check( 'every knob of the page turns one way: its steps grow from the first to the last (a line may stay at 1px, a step of the type is the reset\'s value times a factor that grows) - '. $measured. ' tokens read', $measured === 18 && $unordered === [] );

// the groups: the knobs it sets the steps of keep their middle, the one of its own is off in the middle
$planned = Compiler::plan( $site, Mix::of( $site, 'nino' ), [] )['groups'];
$standards = [];

foreach( glob( $root. '/_nino/Nino/Modules/Basis/groups/*/part/part.css' ) ?: [] as $file ) {

	preg_match( '/:root \{(.*?)\n\}/s', (string) file_get_contents( $file ), $standard );
	preg_match_all( '/(--[a-z0-9-]+):\s*([^;]+);/', $standard[1] ?? '', $values );
	$standards += array_combine( $values[1], array_map( $flat, $values[2] ) );
}

$groupMiddle = [];

foreach( [ 'footer', 'header', 'section', 'button', 'image', 'text' ] as $group )
	foreach( $planned[$group]['knobs'] as $key => $knob )
		if( $knob['extra'] === false )
			$groupMiddle[$key] = [ $flat( $knob['ladder'][2] ), $standards[$knob['token']] ?? null ];

check( 'the middle step of each of the '. count( $groupMiddle ). ' knobs of the six groups, those the part turns among them, is what the standard part sets in :root, read from its file - the ladder is the part\'s, the middle is the standard\'s', count( $groupMiddle ) === 17
	&& array_filter( $groupMiddle, static fn( array $pair ): bool => $pair[0] !== $pair[1] ) === [] && $planned['section']['knobs']['section-wrap.space']['ladder'] !== \Nino\Components::groups( $site )['section']['knobs']['section-wrap.space']['ladder']
	&& $planned['section']['knobs']['section-col.pad']['ladder'] !== \Nino\Components::groups( $site )['section']['knobs']['section-col.pad']['ladder'] );
$rule = $planned['section']['knobs']['section-title.x-rule'];
check( 'the rule below the title is a knob of the section title of its own, with a token of the component, off in the middle and below - there is none to take away - and thicker above it, as a multiple of the line of the page',
	$rule['extra'] === true && $rule['token'] === '--section-title-x-rule' && array_slice( $rule['ladder'], 0, 3 ) === [ '0px', '0px', '0px' ] && array_filter( array_slice( $rule['ladder'], 3 ), static fn( string $step ): bool => str_contains( $step, 'var(--page-line)' ) === false ) === [] && array_keys( $planned['section']['knobs'] ) === [ 'section-wrap.space', 'section-row.gap', 'section-col.pad', 'section-col.radius', 'section-title.space', 'section-title.x-rule' ]
	&& str_contains( $ninoParts['section']['css'] ?? '', 'var(--section-title-x-rule)' ) === true );

// the variants: three, one each, and none of them the standard - the page that names none is drawn as it is without the theme
$mixed = Compiler::compile( $site, Mix::of( $site, 'nino' ), Brand::clean( $site, [] ) );
$sheet = $mixed['css'];
check( 'the variants: the bar for the header, the dark surface for the footer and the band for the section, each added to its component, regular the standard of all three - and no other component gets a variant or a standard',
	array_map( static fn( array $entry ): array => array_slice( $entry, 0, 2 ), $mixed['variants'] ) === [ 'footer-wrap' => [ [ 'dark' ], 'regular' ], 'header-wrap' => [ [ 'bar' ], 'regular' ], 'section-wrap' => [ [ 'band' ], 'regular' ] ] );
$classes = [ 'header-wrap' => 'bar', 'footer-wrap' => 'dark', 'section-wrap' => 'band' ];
check( 'each variant is drawn by a rule of the part of its group on the class the register gives it', array_filter( $classes, static fn( string $variant, string $component ): bool => preg_match( '/\.'. preg_quote( $component. '--variant-'. $variant, '/' ). '(?![\w-])/', $ninoParts[explode( '-', $component )[0]]['css'] ?? '' ) !== 1, ARRAY_FILTER_USE_BOTH ) === [] );

// what a part may write: calculate with the tokens, and write out no length - the two of 24px, the least a target is, apart
$written = [];

foreach( $ninoParts as $group => $part )
	if( $part['css'] !== null )
		preg_match_all( '/(?<![\w-])\d*\.?\d+(?:px|rem|em|vw|vh|ch|pt)\b/', (string) preg_replace( '!/\*.*?\*/!s', '', $part['css'] ), $lengths ) && $written = array_merge( $written, $lengths[0] );

check( 'the css of the parts writes out no length but the 24px that the logo of the header and the link of a title are at the least, so that a theme that turns a knob turns every fixed step with it', $written === [ '24px', '24px' ] );

// sizes
$partBytes = array_sum( array_map( static fn( array $part ): int => strlen( (string) $part['css'] ), $ninoParts ) );
$allSheet = strlen( $sheet );
$numbers['nino: css of the parts'] = $partBytes;
$numbers['nino: theme.css'] = $allSheet;
check( 'Nr. 11: the css of the parts of Nino comes to under 4 KB, the stylesheet that is written - the brand, the page, the knobs and the parts - to under 8 KB', $partBytes < 4096 && $allSheet < 8192 );

// each knob at -2 and at +2 builds, and the stylesheet stays below 8 KB
$ends = [];

foreach( [ -2, 2 ] as $step ) {

	$mix = Mix::of( $site, 'nino' );

	foreach( $mix['parts'] as $group => $entry )
		foreach( array_keys( $planned[$group]['knobs'] ?? $pageKnobs ) as $key )
			$mix['parts'][$group]['values'][$key] = $step;

	foreach( array_keys( $pageKnobs ) as $key )
		$mix['parts']['page']['values'][$key] = $step;

	$clean = Mix::clean( $site, $mix, false );
	$built = is_array( $clean ) === true ? Compiler::compile( $site, $clean, Brand::clean( $site, [] ) ) : null;
	$ends[$step] = $built !== null ? strlen( $built['css'] ) : -1;
}

check( 'with every knob at -2 and at +2 the mix is clean and builds, to a stylesheet of under 8 KB', $ends[-2] > 0 && $ends[2] > 0 && max( $ends ) < 8192 );

// the probe page: every attribute with a fixed step in every value, the three variants of the theme drawn on it
$probe = (string) file_get_contents( __DIR__. '/fixtures/pages/nino-probe.tpl' );
$schemas = \Nino\Components::components( $site );
$missing = [];
$attributes = [ 'gap', 'pad', 'size', 'ground', 'width', 'height', 'align', 'reverse', 'hide', 'ratio', 'focus', 'tone', 'wide' ];
$kinds = 0;

foreach( $schemas as $component => $schema )
	foreach( $schema['attributes'] ?? [] as $attribute => $definition ) {

		if( in_array( $attribute, $attributes, true ) === false || in_array( $schema['group'], [ 'section', 'text', 'button', 'image', 'footer' ], true ) === false )
			continue;

		$options = $definition['type'] === 'bool' ? [ 'bool' ] : array_diff( array_map( 'strval', (array) ( $definition['options'] ?? [] ) ), [ (string) $definition['default'] ] );
		$kinds++;

		foreach( $options as $option ) {

			$pattern = $option === 'bool' ? '/\['. preg_quote( $component, '/' ). '\b[^\]]*\s'. preg_quote( $attribute, '/' ). '(?=[\s\]])(?!=)/' : '/\['. preg_quote( $component, '/' ). '\b[^\]]*\s'. preg_quote( $attribute, '/' ). '="(?:[^"]* )?(?:[a-z]:)?'. preg_quote( $option, '/' ). '(?: [^"]*)?"/';

			if( preg_match( $pattern, $probe ) !== 1 )
				$missing[] = $component. ' '. $attribute. ' '. $option;
		}
	}

check( 'the probe page of the suite names every value but the default of each of the thirteen attributes that have a fixed step - '. $kinds. ' attributes of the components they belong to - so that the browser can measure each of them in each variant', $kinds === 21 && $missing === [], $missing );
check( 'and names each variant of the theme, one of each for the header and the footer, the band for every section that is one', substr_count( $probe, 'variant="bar"' ) === 1 && substr_count( $probe, 'variant="dark"' ) === 1 && preg_match_all( '/\[section-wrap [^\]]*variant="band"/', $probe ) === substr_count( $probe, '[section-wrap' ) );

// drawn: with the feature off the page has no variant, with Nino on it has all three, and a page that names none is drawn the same either way
$frame = [ '\\Nino\\Modules\\Assets', '\\Nino\\Modules\\Basis' ];
$plain = '[header-wrap][header-nav menu="main"][/header-wrap][section-wrap][section-row gap="3"][section-col variant="card" pad="2"][section-title text="A title" tag="h2" size="l"][text text="Words" size="s"][button text="Go" href="/go" size="l"][/section-col][/section-row][/section-wrap][footer-wrap][footer-col size="50"][text text="Footer"][/footer-col][/footer-wrap]';
$probeBody = (string) preg_replace( '/\[template [^\]]*\]\n?/', '', preg_replace( '/<!--.*?-->\n?/s', '', $probe ) );
themesCall( $site, 'themes/apply', [ 'theme' => 'nino' ] );
$drawn = themesReboot( $site, $modules );
$withoutTheme = themesReboot( $site, $frame );
$themed = \Nino\Html::renderHtml( $drawn, $probeBody );
$unthemed = \Nino\Html::renderHtml( $withoutTheme, $probeBody );
check( 'Nino on: the probe page is drawn with the bar, the dark surface and a band on each of its sections, and without the feature it is drawn with none of the three',
	substr_count( $themed, 'header-wrap--variant-bar' ) === 1 && substr_count( $themed, 'footer-wrap--variant-dark' ) === 1 && substr_count( $themed, 'section-wrap--variant-band' ) === substr_count( $probe, '[section-wrap' )
	&& preg_match( '/--variant-(?:bar|dark|band)/', $unthemed ) === 0 );
check( 'Nino on: a page that names no variant is drawn the same as it is without the feature, byte for byte - nothing in the markup says there is a theme', \Nino\Html::renderHtml( $drawn, $plain ) === \Nino\Html::renderHtml( $withoutTheme, $plain ) && ninoWarnings() === [] );

$starter = [ '\\Nino\\Modules\\Assets', '\\Nino\\Modules\\Elements', '\\Nino\\Modules\\Template', '\\Nino\\Modules\\Jstext', '\\Nino\\Modules\\Csrf', '\\Nino\\Modules\\Cache', '\\Nino\\Modules\\Form', '\\Nino\\Modules\\Navigation', '\\Nino\\Modules\\Localepicker', '\\Nino\\Modules\\Legal', '\\Nino\\Modules\\Maintenance', '\\Nino\\Modules\\Basis', '\\Nino\\Modules\\Themes' ];
// the bundle of the starter: Nino.css, the standard parts, the stylesheets of the modules the starter has, and the theme
$starting = themesReboot( $site, $starter );
$bundle = \Nino\Modules\Assets::stylesheet( $starting, THEMES_BUNDLE );
$base = \Nino\Modules\Assets::stylesheet( themesReboot( $site, array_diff( $starter, [ '\\Nino\\Modules\\Themes' ] ) ), THEMES_BUNDLE );
$numbers['nino: bundle'] = strlen( $bundle );
check( 'Nr. 11: the bundle of the style of a site with Nino is the one without it and the stylesheet of the theme, and is under 45 KB', strlen( $bundle ) - strlen( $base ) > $allSheet - 200 && strlen( $bundle ) - strlen( $base ) < $allSheet + 400 && strlen( $bundle ) < 45 * 1024 );

// ---------------------------------------------------------------------------------------------------------------------------------
echo "\nK. The panel\n";

$site = themesSite();
ninoUser( $site, 'designer@example.com' );

// what the screen is made of: the files the workbench is told about, the mounts the script draws into, and nothing that runs from a markup
$assets = \Nino\Modules\Themes\Admin::assets();
$template = (string) file_get_contents( $featureDir. '/templates/panel.tpl' );
$script = (string) file_get_contents( $featureDir. '/assets/admin.js' );
$sheet = (string) file_get_contents( $featureDir. '/assets/admin.css' );
$panelWords = [ 'en_US' => \Nino\Admin\Admin::textFills( $site, '/features/Themes/text', 'en_US' ), 'de_DE' => \Nino\Admin\Admin::textFills( $site, '/features/Themes/text', 'de_DE' ) ];
check( 'K. the panel names its script and its stylesheet, both of them files of the feature, and its template holds the two mounts the script draws into - the list and the theme - and no script, no style and no handler of an event',
	count( $assets ) === 2 && str_ends_with( $assets[0], '/assets/admin.js' ) === true && str_ends_with( $assets[1], '/assets/admin.css' ) === true && is_file( $featureDir. '/assets/admin.js' ) === true && is_file( $featureDir. '/assets/admin.css' ) === true
	&& str_contains( $template, 'id="themes-list"' ) === true && str_contains( $template, 'id="themes-theme"' ) === true && preg_match( '/<script|<style|\sstyle=|\son[a-z]+=|javascript:/i', $template ) === 0 );
check( 'K. the script keeps to the CSP of the workbench - nothing is built from a string, nothing is written as markup, and the only places a style is set are the box that measures a calculation and the swatches of the palette, which show a colour that no stylesheet has - and the stylesheet is scoped to the panel: every rule begins with its id',
	preg_match( '/\beval\s*\(|new\s+Function|setTimeout\s*\(\s*[\'"]|insertAdjacentHTML|document\.write|\.outerHTML\s*=/', $script ) === 0 && preg_match( '/innerHTML\s*=\s*[^\'\s]/', $script ) === 0 && preg_match( '/(?<!probe|swatch)\.style\b/', $script ) === 0 && preg_match( '/setAttribute\(\s*[\'"]style/', $script ) === 0
	&& str_starts_with( ltrim( $sheet ), '@layer nino.system, nino.tool, nino.local;' ) === true && preg_match_all( '/^(?:#themes-app|\t#themes-app)[^{]*\{/m', $sheet ) > 20 && preg_match( '/^[.a-z][^{@\n]*\{/m', preg_replace( '~/\*.*?\*/~s', '', $sheet ) ) === 0 );
check( 'K. every word of the panel is a fill of both interface languages, with the same places for a param in each, and the placeholder page of the first part is gone: no shortcode of the workbench for it and no fill for its sentences',
	$panelWords['en_US'] !== [] && array_keys( array_filter( $panelWords['en_US'], static fn( string $key ): bool => str_starts_with( $key, '[[/_admin/themes/' ) === true, ARRAY_FILTER_USE_KEY ) ) === array_keys( array_filter( $panelWords['de_DE'], static fn( string $key ): bool => str_starts_with( $key, '[[/_admin/themes/' ) === true, ARRAY_FILTER_USE_KEY ) )
	&& array_filter( array_keys( $panelWords['en_US'] ), static fn( string $key ): bool => str_starts_with( $key, '[[/_admin/themes/panel/' ) === true ) === [] && isset( $site['/nino/html/shortcodes']['themes-active'] ) === false
	&& method_exists( \Nino\Modules\Themes::class, 'doActive' ) === false && str_contains( $template, 'themes-active' ) === false );

// the list: what a card needs of the API
$site['/nino/http/routes'] = [
	'GET://'						=> [ 'uri' => '/', 'body' => '[template /templates/page-home]' ],
	'GET://contact'			=> [ 'uri' => '/contact', 'body' => '[template /templates/page-contact]' ],
	'GET://contact'. "\n"	=> [ 'uri' => '/never', 'body' => 'x' ],
	'GET://robots.txt'	=> [ 'uri' => '/robots.txt', 'header' => [ 'Content-Type' => 'text/plain; charset=utf-8' ], 'body' => 'x' ],
	'GET://404'					=> [ 'uri' => '/404', 'statusCode' => 404, 'body' => 'x' ],
	'GET://blog/*'			=> [ 'uri' => '/blog/*', 'body' => 'x' ],
	'GET://_admin'			=> [ 'uri' => '/_admin', 'body' => 'x' ],
	'POST://contact'		=> [ 'uri' => '/contact', 'body' => 'x' ],
];
\Nino\Html::addFills( $site, [ '[[/_nino/webpage/contact/title]]' => 'Contact us' ], '*' );
themesCall( $site, 'themes/save-as', [ 'mix' => themesMix( 'Lindner', [ 'page' => [ 'from' => 'band-probe' ], 'section' => [ 'from' => 'craft-probe' ] ] ) ] );
[ $status, $list ] = themesCall( $site, 'themes/list' );
$lindner = array_values( array_filter( $list['themes'], static fn( array $theme ): bool => $theme['key'] === 'own:lindner' ) )[0] ?? [];
check( 'K. the list names the pages of the site a preview may show: the paths of the routes that answer html with a status below 400, no wildcard, no address of the workbench, no other method, each once - with the title of the page where it has one', $status === 200 && $list['pages'] === [ [ 'path' => '/', 'title' => '' ], [ 'path' => '/contact', 'title' => 'Contact us' ] ], json_encode( $list['pages'] ?? null ) );
check( 'K. ...and says of an own theme where each of its parts comes from, so that a card can say what it is made of', ( $lindner['from'] ?? null ) === [ 'page' => 'band-probe@1.0.0', 'section' => 'craft-probe@1.0.0' ] && isset( $lindner['name'] ) === true );

// what a screen of a group needs of a draft: the label of each component, the time a token holds
$schemas = \Nino\Components::components( $site );
[ $status, $open ] = themesCall( $site, 'themes/open', [ 'theme' => 'craft-probe' ] );
[ $status2, $draftBody ] = themesCall( $site, 'themes/preview', [ 'mix' => $open['mix'] ] );
$labelled = true;
$named = 0;

foreach( [ $open, $draftBody ] as $answer )
	foreach( $answer['groups'] as $group )
		foreach( $group['components'] as $component ) {
			$labelled = $labelled === true && $component['label'] === $schemas[$component['name']]['label'] && ( is_string( $component['label'] ) === true || is_array( $component['label'] ) === true );
			$named++;
		}

check( 'K. every component of a group comes with its label, a text or a map of locales, as the register has it - in what open says of the saved state and in what preview says of the draft', $status === 200 && $status2 === 200 && $labelled === true && $named >= 20 );
check( 'K. a draft says how long its token holds, in seconds, so that a screen counts the time on its own clock', $draftBody['ttl'] === \Nino\Preview::TTL && $draftBody['ttl'] === 900 );

// the outline of a component in the preview
$cache = ninoSandboxDir( $site ). '/private/data/.preview/themes';
$token = $draftBody['token'];
$visit = static function( string $query ) use ( &$site ): string {

	ninoVisit( $site, '/?'. $query );

	return \Nino\Html::renderHtml( $site, '[assets '. THEMES_BUNDLE. ']' );
};
$visit( 'x=1' );
$publicBefore = (string) file_get_contents( ninoSandboxDir( $site ). '/public/.cache/style.css' );
$plainPreview = $visit( 'nino-preview='. $token );
$marked = $visit( 'nino-preview='. $token. '&mark=section-col' );
$outline = '.section-col { outline: 2px solid #e8590c !important; outline-offset: -2px !important; }';
check( 'K. a preview that names a component outlines it - one rule after the whole bundle, which no layer can beat - and one that names none does not', substr_count( $marked, $outline ) === 1 && str_contains( $plainPreview, '#e8590c' ) === false && str_ends_with( substr( $marked, 0, (int) strpos( $marked, '</style>' ) ), $outline ) === true
	&& substr_count( $marked, '</style>' ) === 1 );
check( 'K. what stands after mark is a name and nothing else: a selector, a list, a path, a quote, an upper-case letter, an array and nothing are no mark, and no byte of them is in the page',
	array_filter( [ 'section-col, body', 'a{}', '../x', 'a"b', 'Section-col', 'section_col', "x\0y", 'x y', str_repeat( 'a', 200 ), '' ], static fn( string $value ): bool => str_contains( $visit( 'nino-preview='. $token. '&mark='. rawurlencode( $value ) ), '#e8590c' ) === true ) === []
	&& str_contains( $visit( 'nino-preview='. $token. '&mark[]=section-col' ), '#e8590c' ) === false && str_contains( $visit( 'nino-preview='. $token. '&mark[a]=x' ), '#e8590c' ) === false );
check( 'K. the outline is for a request that holds a preview: with no token, a token that is not the session\'s or a token that ran out the page is the page as it is, and carries no outline', str_contains( $visit( 'mark=section-col' ), '#e8590c' ) === false && str_contains( $visit( 'nino-preview='. str_repeat( '0', 32 ). '&mark=section-col' ), '#e8590c' ) === false
	&& str_contains( $visit( 'nino-preview='. strtoupper( $token ). '&mark=section-col' ), '#e8590c' ) === false );
$_SESSION[$site['./nino/uid']]['/nino/preview/themes'] = [ $token => time() - 1 ];
check( '...nor with a token whose time is out', themesKept( $cache. '/'. $token, static fn(): bool => str_contains( $visit( 'nino-preview='. $token. '&mark=section-col' ), '#e8590c' ) === false ) );
check( 'K. and the request after one that asked for an outline begins with none: the public bundle is the file it was and a page with no query has no style element of the preview', ( function() use ( &$site, $modules, $visit, $publicBefore ): bool {
	$later = themesReboot( $site, $modules );
	$unmarked = \Nino\Modules\Assets::stylesheet( $later, THEMES_BUNDLE );

	return str_contains( $unmarked, '#e8590c' ) === false && (string) file_get_contents( ninoSandboxDir( $later ). '/public/.cache/style.css' ) === $publicBefore && str_contains( $visit( 'x=1' ), 'data-nino-theme-preview' ) === false;
} )() );

// one request after another in a process: the outline asked for in the first is not in the second, which asks for none
$_SESSION[$site['./nino/uid']]['/nino/preview/themes'] = [ $token => time() + 600 ];
$visit( 'nino-preview='. $token. '&mark=section-col' );
check( 'K. a request that holds a preview and names no component has no outline, though the request before it in the same process named one', str_contains( $visit( 'nino-preview='. $token. '&mark=section-col' ), '#e8590c' ) === true && str_contains( $visit( 'nino-preview='. $token ), '#e8590c' ) === false );

// two tabs: a change of an own theme says which state it was made on
$open = static fn( string $theme ): array => themesCall( $site, 'themes/open', [ 'theme' => $theme ] )[1];
$first = $open( 'own:lindner' );
$again = $open( 'own:lindner' );
check( 'K. open gives the stamp of an own theme: 64 hexadecimal digits, the same for the same state, none for a theme of a package', preg_match( '/^[0-9a-f]{64}$/D', $first['stamp'] ) === 1 && $first['stamp'] === $again['stamp'] && $open( 'craft-probe' )['stamp'] === '' );

$changed = $first['mix'];
$changed['css'] = '.k { color: red; }';
[ $status, $saved ] = themesCall( $site, 'themes/save', [ 'theme' => 'own:lindner', 'mix' => $changed, 'base' => $first['stamp'] ] );
$next = $open( 'own:lindner' );
check( 'K. save with the stamp it was opened with writes, and says the stamp of what it wrote, which is what open says next - and the stamp is not the one of before: the css is part of it', $status === 200 && $saved['stamp'] === $next['stamp'] && $next['stamp'] !== $first['stamp'] );

$other = $changed;
$other['css'] = '.k { color: blue; }';
$before = $open( 'own:lindner' )['mix'];
[ $stale, $body ] = themesCall( $site, 'themes/save', [ 'theme' => 'own:lindner', 'mix' => $other, 'base' => $first['stamp'] ] );
[ $none ] = themesCall( $site, 'themes/save', [ 'theme' => 'own:lindner', 'mix' => $other, 'base' => null ] );
[ $empty ] = themesCall( $site, 'themes/save', [ 'theme' => 'own:lindner', 'mix' => $other, 'base' => '' ] );
[ $list ] = themesCall( $site, 'themes/save', [ 'theme' => 'own:lindner', 'mix' => $other, 'base' => [ $next['stamp'] ] ] );
check( 'K. the second tab - which opened the theme before the first wrote it - is told 409 themes_changed, with the name, and so is a request that posts no stamp, an empty one or a list: nothing is written', $stale === 409 && $body['code'] === 'themes_changed' && $body['params'] === [ 'Lindner' ] && $body['field'] === 'theme' && $none === 409 && $empty === 409 && $list === 409 && $open( 'own:lindner' )['mix'] === $before );
[ $over ] = themesCall( $site, 'themes/save', [ 'theme' => 'own:lindner', 'mix' => $other, 'base' => $open( 'own:lindner' )['stamp'] ] );
check( 'K. the one who is told takes the stamp that stands now and writes over it, which is how Overwrite goes', $over === 200 && $open( 'own:lindner' )['mix']['css'] === '.k { color: blue; }' );

// the order of the keys is no change: the same theme with its keys in the other order has the same stamp
$stored = ninoSandboxDir( $site ). '/private/data/theme/state/own/lindner.json';
$original = (string) file_get_contents( $stored );
$reverse = static function( mixed $value ) use ( &$reverse ): mixed {

	if( is_array( $value ) === false )
		return $value;

	krsort( $value );

	return array_map( $reverse, $value );
};
file_put_contents( $stored, json_encode( $reverse( json_decode( $original, true ) ) ) );
$reversed = $open( 'own:lindner' )['stamp'];
file_put_contents( $stored, $original );
check( 'K. the stamp is the content and not the order of its keys: the same theme written with its keys the other way round has the same stamp', $reversed === $open( 'own:lindner' )['stamp'] && $original !== json_encode( $reverse( json_decode( $original, true ) ) ) );

// two answers: the stamp is looked at whatever force says, and force is looked at whatever the stamp says
themesCall( $site, 'themes/apply', [ 'theme' => 'own:lindner' ] );
$current = $open( 'own:lindner' );
$stylesheet = ninoSandboxDir( $site ). '/private/assets/theme.css';
$ours = (string) file_get_contents( $stylesheet );
file_put_contents( $stylesheet, "/* written by hand */\nbody { color: red; }\n" );
[ $foreign, $foreignBody ] = themesCall( $site, 'themes/save', [ 'theme' => 'own:lindner', 'mix' => $current['mix'], 'base' => $current['stamp'] ] );
[ $staleForced, $staleBody ] = themesCall( $site, 'themes/save', [ 'theme' => 'own:lindner', 'mix' => $current['mix'], 'base' => 'stale', 'force' => true ] );
check( 'K. a stylesheet that somebody wrote is asked about even with the right stamp (409 themes_foreign), and a stamp that is not the theme\'s is refused even with force (409 themes_changed): neither answer lets the other pass', $foreign === 409 && $foreignBody['code'] === 'themes_foreign' && $staleForced === 409 && $staleBody['code'] === 'themes_changed' && str_contains( (string) file_get_contents( $stylesheet ), 'written by hand' ) === true );
[ $both ] = themesCall( $site, 'themes/save', [ 'theme' => 'own:lindner', 'mix' => $current['mix'], 'base' => $current['stamp'], 'force' => true ] );
check( 'K. ...and with both answers given - the stamp of what is saved and force - the theme is written and the stylesheet is the feature\'s', $both === 200 && Compiler::isOwn( (string) file_get_contents( $stylesheet ) ) === true );
unset( $ours );

// a theme that is named in a sentence is not named by the form the API gives it
$named = [];

foreach( [ 'themes/open', 'themes/apply', 'themes/delete' ] as $action )
	$named[$action] = themesCall( $site, $action, [ 'theme' => 'own:ghost', 'base' => 'x' ] )[1]['params'] ?? null;

$named['themes/save'] = themesCall( $site, 'themes/save', [ 'theme' => 'own:ghost', 'mix' => themesMix( 'G', [] ), 'base' => 'x' ] )[1]['params'] ?? null;
check( 'K. a failure that names a theme that is not there names it by its key and not as own:<key>, which is the form of the API', array_filter( $named, static fn( mixed $params ): bool => $params !== [ 'ghost' ] ) === [] && count( $named ) === 4, json_encode( $named ) );

// a request that read the theme before another one wrote it is told so, not only one that comes after: the stamp is looked at with the lock held
$racing = $open( 'own:lindner' );
$raced = false;
$rival = $racing['mix'];
$rival['css'] = '.rival { color: red; }';
\Nino\Callbacks::registerCallback( $site, \Nino\Modules\Themes\Reader::ROOTS, static function( array &$appData, array $roots ) use ( &$raced, $rival, $racing ): array {

	// This runs while the request below is made ready, after it has read the state and before it takes the lock: the other one writes now
	if( $raced === false ) {
		$raced = true;
		themesCall( $appData, 'themes/save', [ 'theme' => 'own:lindner', 'mix' => $rival, 'base' => $racing['stamp'] ] );
	}

	return $roots;
} );
unset( $site['./nino/themes/read'] );
// A part of a theme that the project has no copy of yet: the request reads the themes, which is where the other one gets in
$late = $changed;
$late['parts']['text'] = [ 'from' => 'band-probe@1.0.0' ];
[ $second, $secondBody ] = themesCall( $site, 'themes/save', [ 'theme' => 'own:lindner', 'mix' => $late, 'base' => $racing['stamp'] ] );
check( 'K. two requests with the same stamp, the second of which read the theme before the first wrote it: exactly one writes - the other is told 409 themes_changed, and what stands is the first one\'s', $raced === true && $second === 409 && $secondBody['code'] === 'themes_changed' && $open( 'own:lindner' )['mix']['css'] === '.rival { color: red; }' );

themesCall( $site, 'themes/save-as', [ 'mix' => themesMix( 'Doomed', [] ) ] );
$doomed = $open( 'own:doomed' );
[ $wrong ] = themesCall( $site, 'themes/delete', [ 'theme' => 'own:doomed', 'base' => $first['stamp'] ] );
[ $missing ] = themesCall( $site, 'themes/delete', [ 'theme' => 'own:doomed', 'base' => null ] );
check( 'K. a theme is deleted only as the one who deletes it saw it: another stamp or none is 409 and the theme is there', $wrong === 409 && $missing === 409 && $open( 'own:doomed' )['stamp'] === $doomed['stamp'] );
[ $gone ] = themesCall( $site, 'themes/delete', [ 'theme' => 'own:doomed', 'base' => $doomed['stamp'] ] );
check( 'K. ...and with its stamp it is deleted', $gone === 200 && themesCall( $site, 'themes/open', [ 'theme' => 'own:doomed' ] )[0] === 404 );

// ---------------------------------------------------------------------------------------------------------------------------------
echo "\nL. The second part of the panel: the brand, the own css, the settings of a theme, the last state\n";
( static function(): void {

$site = themesSite();
ninoUser( $site, 'designer@example.com' );
$state = ninoSandboxDir( $site ). '/private/data/theme';
$sheet = ninoSandboxDir( $site ). '/private/assets/theme.css';
$open = static fn( string $theme ): array => themesCall( $site, 'themes/open', [ 'theme' => $theme ] )[1];
$listOf = static fn(): array => themesCall( $site, 'themes/list' )[1];
$digestOf = static function( string $dir ): string {

	$files = [];

	foreach( new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $dir, FilesystemIterator::SKIP_DOTS ) ) as $file )
		$files[] = substr( (string) $file, strlen( $dir ) ). ':'. sha1_file( (string) $file );

	sort( $files );

	return sha1( implode( "\n", $files ) );
};
$none = [];

foreach( \Nino\Modules\Themes\Brand::KEYS as $key )
	$none[$key] = [ 'own' => false ];

$own = static fn( array $set ): array => array_merge( $none, array_map( static fn( string $value ): array => [ 'own' => true, 'value' => $value ], $set ) );

$list = $listOf();
check( 'L. the list tells the panel what the API takes: the longest css and name, the stamp of the state and the one of the brand - so that it holds nothing of its own - and the fonts there are',
	$list['limits'] === [ 'css' => \Nino\Modules\Themes\Mix::CSS_LENGTH, 'name' => \Nino\Modules\Themes\Mix::NAME_LENGTH, 'key' => \Nino\Components::NAME_LENGTH ] && $list['limits']['css'] === 65536 && $list['limits']['name'] === 60
	&& $list['state'] === '' && is_string( $list['brandStamp'] ) === true && strlen( $list['brandStamp'] ) === 64 && array_column( $list['fonts'], 'key' ) === [ 'system-sans', 'system-serif', 'system-mono', 'open-sans' ] );

// -- what a draft says of the brand: where the values come from, and the colours
$described = $open( 'craft-probe' );
$neutral = $open( 'nino' );
check( 'L. open and preview say what the brand is: every value as it comes out, what the theme suggests, and what Nino.css says where nothing else does - the panel shows the value it goes back to from these',
	$described['brand']['defaults'] === \Nino\Modules\Themes\Brand::DEFAULTS && $described['brand']['suggested']['tone'] === 'warm' && $described['brand']['suggested']['font-title'] === 'open-sans' && $described['brand']['stored'] === $none
	&& $described['brand']['effective']['font-title'] === 'open-sans' && $described['brand']['effective']['color-1'] === '#1c5db8' );
check( 'L. a brand that names no colour and no tone but the neutral one has the colours of the reset, and says so - the theme that suggests a warm tone has colours of its own; the pairs are those of the reset and the veil is the ground of a picture under a section',
	$neutral['palette']['source'] === 'reset' && $described['palette']['source'] === 'brand' && $neutral['palette']['veil'] === \Nino\Modules\Themes\Palette::VEIL && isset( $neutral['palette']['colors']['light']['ink'] ) === true && count( $neutral['palette']['contrast'] ) === 25 && min( $neutral['palette']['contrast'] ) >= 4.5 );
[ $status, $body ] = themesCall( $site, 'themes/preview', [ 'mix' => [ 'parts' => [] ], 'brand' => $own( [ 'color-1' => '#9a4b1d' ] ) ] );
$truncated = [];

foreach( \Nino\Modules\Themes\Palette::pairs( $body['palette']['colors'], $body['palette']['colors']['color-1'], $body['palette']['colors']['color-2'] ) as $name => $ratio )
	$truncated[$name] = floor( $ratio * 100 + 1e-9 ) / 100;

check( 'L. a contrast is told cut to two decimals and not rounded, as the field of the colour of the workbench cuts it - so that the number of a pair and the number under its colour are one', $body['palette']['contrast'] === $truncated && $body['palette']['contrast'] !== array_map( static fn( float $ratio ): float => round( $ratio, 2 ), \Nino\Modules\Themes\Palette::pairs( $body['palette']['colors'], $body['palette']['colors']['color-1'], $body['palette']['colors']['color-2'] ) ) );
check( 'L. a draft with a colour of its own has a palette of its own: the source is the brand, the first colour is the one set, the colour written on it is white or black, and no pair is under 4.5 : 1',
	$status === 200 && $body['palette']['source'] === 'brand' && $body['palette']['colors']['color-1'] === '#9a4b1d' && in_array( $body['palette']['colors']['on-color-1'], [ '#ffffff', '#000000' ], true ) === true && min( $body['palette']['contrast'] ) >= 4.5 && $body['brand']['stored']['color-1']['value'] === '#9a4b1d' );
[ $status, $body ] = themesCall( $site, 'themes/preview', [ 'mix' => [ 'parts' => [] ], 'brand' => $own( [ 'tone' => 'warm' ] ) ] );
check( 'L. the tone alone is a palette too - the grounds take it - and so the source is the brand; a mode or a font alone is not', $body['palette']['source'] === 'brand'
	&& themesCall( $site, 'themes/preview', [ 'mix' => [ 'parts' => [] ], 'brand' => $own( [ 'mode' => 'dark', 'font-title' => 'system-serif' ] ) ] )[1]['palette']['source'] === 'reset' );

// -- the brand alone: one action, whichever theme is open
$before = $listOf();
[ $status, $body ] = themesCall( $site, 'themes/brand', [ 'theme' => 'craft-probe', 'brand' => $own( [ 'color-1' => '#1f6f5b' ] ) ] );
check( 'L. the brand is saved alone through its own action, with the theme that is open named: 200, written in the state, the website unchanged where no theme is on it - and the answer has the new stamps',
	$status === 200 && $body['saved'] === true && $body['applied'] === false && json_decode( (string) file_get_contents( $state. '/state/brand.json' ), true )['color-1'] === [ 'own' => true, 'value' => '#1f6f5b' ] && is_file( $sheet ) === false
	&& $body['brandStamp'] !== $before['brandStamp'] && $body['state'] === \Nino\Modules\Themes\Store::stamp( $site ) && $body['canRestore'] === true && glob( $state. '/state/own/*.json' ) === [] );
$stamp = $body['brandStamp'];
[ $status, $body ] = themesCall( $site, 'themes/brand', [ 'brand' => $own( [ 'color-1' => '#8b1e3f' ] ), 'brandBase' => $before['brandStamp'] ] );
[ $none1 ] = themesCall( $site, 'themes/brand', [ 'brand' => $own( [ 'color-1' => '#8b1e3f' ] ), 'brandBase' => null ] );
[ $list1 ] = themesCall( $site, 'themes/brand', [ 'brand' => $own( [ 'color-1' => '#8b1e3f' ] ), 'brandBase' => [ $stamp ] ] );
check( 'L. a brand that was saved by another tab since the one who asks opened it is 409 themes_brand_changed - so is a request with no stamp and one with a list - and the colour that stands is the one of the other tab',
	$status === 409 && $body['code'] === 'themes_brand_changed' && $body['field'] === 'brand' && $none1 === 409 && $list1 === 409 && json_decode( (string) file_get_contents( $state. '/state/brand.json' ), true )['color-1']['value'] === '#1f6f5b' );
[ $status ] = themesCall( $site, 'themes/brand', [ 'brand' => $own( [ 'color-1' => '#8b1e3f' ] ), 'brandBase' => $stamp ] );
check( 'L. ...and with the stamp that stands now it is written, which is how Overwrite goes', $status === 200 && json_decode( (string) file_get_contents( $state. '/state/brand.json' ), true )['color-1']['value'] === '#8b1e3f' );
$refused = [];

foreach( [ 'text', $own( [ 'color-1' => 'red' ] ), $own( [ 'color-1' => '#ff0000; } body { x' ] ), $own( [ 'font-title' => '../../etc' ] ), $own( [ 'tone' => 'hot' ] ), $own( [ 'mode' => 'night' ] ), $own( [ 'color-1' => "#9a4b1d\0" ] ), array_merge( $none, [ 'logo' => [ 'own' => true, 'value' => '/x.png' ] ] ), array_merge( $none, [ 'tone' => [ 'own' => true ] ] ) ] as $bad ) {
	$brandStamp = (string) $listOf()['brandStamp'];
	[ $status, $body ] = themesCall( $site, 'themes/brand', [ 'brand' => $bad, 'brandBase' => $brandStamp ] );
	$refused[] = $status === 400 && $body['code'] === 'themes_brand' && $body['field'] === 'brand' && str_contains( json_encode( $body ), "\0" ) === false;
}

[ $nothing ] = themesCall( $site, 'themes/brand', [ 'brandBase' => (string) $listOf()['brandStamp'] ] );
check( 'L. a brand that is not a brand - a text, a colour that is no #rrggbb or has a rule in it, a font that is a path, an unknown tone or mode, a value the brand has not (a logo: M4), an own value with no value - is 400, and none posted is 400 too',
	$refused === array_fill( 0, 9, true ) && $nothing === 400 && json_decode( (string) file_get_contents( $state. '/state/brand.json' ), true )['color-1']['value'] === '#8b1e3f' );

// the brand of the website is built again for the theme that is on it
themesCall( $site, 'themes/apply', [ 'theme' => 'craft-probe' ] );
themesCall( $site, 'themes/brand', [ 'brand' => $own( [ 'color-1' => '#2459d8' ] ) ] );
check( 'L. the brand alone is on the website at once where a theme is on it: the stylesheet is built again with the colour', str_contains( (string) file_get_contents( $sheet ), '--brand-color-1: #2459d8;' ) === true && is_dir( $state. '/next' ) === false );
$changed = $listOf();
check( 'L. ...and it is a change of the state like any other: the state before is the last state, and restore gives the colour it was',
	themesCall( $site, 'themes/restore' )[0] === 200 && json_decode( (string) file_get_contents( $state. '/state/brand.json' ), true )['color-1']['value'] === '#8b1e3f' && str_contains( (string) file_get_contents( $sheet ), '--brand-color-1: #8b1e3f;' ) === true );
themesCall( $site, 'themes/restore' );

// -- an own theme with the brand: the stamp of the brand is asked for where the brand is posted, and not where it is not
themesCall( $site, 'themes/save-as', [ 'mix' => themesMix( 'Lindner', [ 'section' => [ 'from' => 'craft-probe' ] ] ) ] );
$stamps = $open( 'own:lindner' );
check( 'L. open gives the stamp of the brand with the theme, and the state the list and every answer give is the one the store says', $stamps['brandStamp'] === $listOf()['brandStamp'] && $stamps['state'] === \Nino\Modules\Themes\Store::stamp( $site ) && strlen( $stamps['state'] ) === 40 );
[ $without ] = themesCall( $site, 'themes/save', [ 'theme' => 'own:lindner', 'mix' => themesMix( 'Lindner', [], '.a { }' ) ] );
check( 'L. a save that posts no brand does not ask for the stamp of one and does not touch the brand', $without === 200 && json_decode( (string) file_get_contents( $state. '/state/brand.json' ), true )['color-1']['value'] === '#2459d8' );
[ $status, $body ] = themesCall( $site, 'themes/save', [ 'theme' => 'own:lindner', 'mix' => themesMix( 'Lindner', [], '.b { }' ), 'brand' => $own( [ 'color-1' => '#000000' ] ), 'brandBase' => 'stale' ] );
[ $saveAs, $bodyAs ] = themesCall( $site, 'themes/save-as', [ 'mix' => themesMix( 'Second', [] ), 'brand' => $own( [ 'color-1' => '#000000' ] ), 'brandBase' => 'stale' ] );
check( 'L. a save and a save as that post the brand of a state that is not the one that stands are 409 themes_brand_changed: nothing of the mix and nothing of the brand is written, and no own theme is made',
	$status === 409 && $body['code'] === 'themes_brand_changed' && $saveAs === 409 && $bodyAs['code'] === 'themes_brand_changed' && json_decode( (string) file_get_contents( $state. '/state/own/lindner.json' ), true )['css'] === '.a { }'
	&& isset( json_decode( (string) file_get_contents( $state. '/state/brand.json' ), true )['color-1']['value'] ) === true && json_decode( (string) file_get_contents( $state. '/state/brand.json' ), true )['color-1']['value'] === '#2459d8' && is_file( $state. '/state/own/second.json' ) === false );
[ $status ] = themesCall( $site, 'themes/save', [ 'theme' => 'own:lindner', 'mix' => themesMix( 'Lindner', [], '.b { }' ), 'brand' => $own( [ 'color-1' => '#000000' ] ) ] );
check( 'L. M5: with the brand posted and its stamp, both are written - the brand in its file and the own theme in its own, in which the brand is not', $status === 200 && json_decode( (string) file_get_contents( $state. '/state/brand.json' ), true )['color-1']['value'] === '#000000'
	&& strpos( (string) file_get_contents( $state. '/state/own/lindner.json' ), 'color-1' ) === false && strpos( (string) file_get_contents( $state. '/state/own/lindner.json' ), '#000000' ) === false );

// -- a rename is a save: the key stays, the name is the new one - and no two own themes carry one name
themesCall( $site, 'themes/save-as', [ 'mix' => themesMix( 'Second theme', [] ) ] );
[ $status, $body ] = themesCall( $site, 'themes/save', [ 'theme' => 'own:lindner', 'mix' => themesMix( 'Lindner Renamed', [] ) ] );
check( 'L. an own theme is renamed with a save: the key is the one it had, the name is the new one, and what is in the list is the name', $status === 200 && $body['theme'] === 'own:lindner' && json_decode( (string) file_get_contents( $state. '/state/own/lindner.json' ), true )['name'] === 'Lindner Renamed'
	&& in_array( 'Lindner Renamed', array_column( $listOf()['themes'], 'name' ), true ) === true && is_file( $state. '/state/own/lindner-renamed.json' ) === false );
$names = [];

foreach( [ 'second theme', 'SECOND THEME', 'Second  Theme!', 'second-theme' ] as $taken ) {
	[ $status, $body ] = themesCall( $site, 'themes/save', [ 'theme' => 'own:lindner', 'mix' => themesMix( $taken, [] ) ] );
	$names[] = $status === 409 && $body['code'] === 'themes_name_taken' && $body['field'] === 'name' && $body['params'] === [ 'Second theme' ];
}

[ $caseOnly ] = themesCall( $site, 'themes/save', [ 'theme' => 'own:lindner', 'mix' => themesMix( 'LINDNER RENAMED', [] ) ] );
[ $key ] = themesCall( $site, 'themes/save', [ 'theme' => 'own:second-theme', 'mix' => themesMix( 'Second Theme', [] ) ] );
check( 'L. a name that another own theme has - whatever its case, its marks and its spaces, which is what its key is - is 409 themes_name_taken, at the field of the name, and the name that stands is the one it was; the theme\'s own name in another case is no collision, and a theme may keep its name',
	$names === [ true, true, true, true ] && json_decode( (string) file_get_contents( $state. '/state/own/lindner.json' ), true )['name'] === 'LINDNER RENAMED' && $caseOnly === 200 && $key === 200 );

// a save as takes the name as a rename does, and a key that is taken by a card of another name is no collision: the new theme has the next free one
[ $status, $body ] = themesCall( $site, 'themes/save-as', [ 'mix' => themesMix( 'SECOND THEME', [] ) ] );
[ $keyTaken, $keyBody ] = themesCall( $site, 'themes/save-as', [ 'mix' => themesMix( 'Lindner', [] ) ] );
[ $keyTakenAgain, $keyBodyAgain ] = themesCall( $site, 'themes/save-as', [ 'mix' => themesMix( 'LINDNER!', [] ) ] );
check( 'L. a save as with the name of a card is 409 themes_name_taken with the name of that card, as a rename; "Lindner" is no name that a card has any more - the theme that has the key is called something else - and so it is saved, with the next free key, and so is the one after it',
	$status === 409 && $body['code'] === 'themes_name_taken' && $body['params'] === [ 'Second Theme' ] && $keyTaken === 200 && $keyBody['theme'] === 'own:lindner-2' && is_file( $state. '/state/own/lindner-2.json' ) === true
	&& $keyTakenAgain === 409 && $keyBodyAgain['params'] === [ 'Lindner' ] && json_decode( (string) file_get_contents( $state. '/state/own/lindner.json' ), true )['name'] === 'LINDNER RENAMED' );
themesCall( $site, 'themes/delete', [ 'theme' => 'own:lindner-2' ] );

// -- the own css: what the field of the panel may send, and what the API takes
$css = '</style><script>alert(1)</script><style>a { color: red';
[ $status ] = themesCall( $site, 'themes/save', [ 'theme' => 'own:lindner', 'mix' => themesMix( 'Lindner', [], $css ) ] );
check( 'L. the own css is text: a closing tag of a style, a script and a brace that is never closed are kept as they were written in the theme, and at the end of the stylesheet that is built for it with what is open closed, and nothing of it is checked',
	$status === 200 && $open( 'own:lindner' )['mix']['css'] === $css );
themesCall( $site, 'themes/apply', [ 'theme' => 'own:lindner' ] );
$built = (string) file_get_contents( $sheet );
$bundle = \Nino\Modules\Assets::stylesheet( themesReboot( $site, [ '\\Nino\\Modules\\Assets', '\\Nino\\Modules\\Basis', '\\Nino\\Modules\\Themes' ] ), THEMES_BUNDLE );
check( 'L. ...and the bundle of the website holds it in the layer of the theme, closed: the layers after it are still the layers after it, and the one of the project is the last of them',
	str_ends_with( $built, "/* The own css of the theme */\n". $css. "\n}\n" ) === true && str_contains( $bundle, '@layer nino.theme {' ) === true && substr_count( $bundle, '{' ) === substr_count( $bundle, '}' ) );
$state1 = $digestOf( $state );
$sizes = [];

foreach( [ [ 'css', str_repeat( 'x', 65537 ) ], [ 'css', str_repeat( 'a{b:c}', 40000 ) ], [ 'css', "a { }\0" ], [ 'css', [ 'a' ] ] ] as [ $field, $bad ] ) {
	[ $status, $body ] = themesCall( $site, 'themes/save', [ 'theme' => 'own:lindner', 'mix' => [ 'name' => 'Lindner', 'parts' => [], $field => $bad ] ] );
	$sizes[] = $status === 400 && $body['code'] === 'themes_mix' && $body['field'] === 'css';
}

check( 'L. a css of more than 65536 bytes - 64 KB, 240 KB -, one with a null byte and one that is no text is 400 themes_mix at the field of the css, and the state is what it was, byte for byte', $sizes === array_fill( 0, 4, true ) && $digestOf( $state ) === $state1 );
[ $status ] = themesCall( $site, 'themes/save', [ 'theme' => 'own:lindner', 'mix' => themesMix( 'Lindner', [], str_repeat( 'x', 65536 ) ) ] );
[ $preview, $answered ] = themesCall( $site, 'themes/preview', [ 'mix' => [ 'parts' => [], 'css' => str_repeat( 'x', 65537 ) ] ] );
check( 'L. the longest css is 65536 bytes, and 65536 are taken; the preview takes what the save takes and refuses what it refuses', $status === 200 && $preview === 400 && $answered['code'] === 'themes_mix' );

// -- the last state: it asks which state it was asked on
$base = \Nino\Modules\Themes\Store::stamp( $site );
[ $missing, $bodyMissing ] = themesCall( $site, 'themes/restore', [ 'base' => null ] );
[ $stale, $bodyStale ] = themesCall( $site, 'themes/restore', [ 'base' => str_repeat( '0', 40 ) ] );
check( 'L. a restore that names no state, or one that is not the one that stands, is 409 themes_state and swaps nothing: the other tab\'s save is not put aside unseen', $missing === 409 && $bodyMissing['code'] === 'themes_state' && $stale === 409 && $bodyStale['code'] === 'themes_state'
	&& \Nino\Modules\Themes\Store::stamp( $site ) === $base && is_dir( $state. '/swap' ) === false );
$previous = $digestOf( $state. '/previous' );
[ $status, $body ] = themesCall( $site, 'themes/restore', [ 'base' => $base ] );
check( 'L. ...and with the stamp that stands it is restored, and the answer has the stamp of the state that stands now and says that there is a last state to go back to - the one it was', $status === 200 && $body['restored'] === true && $body['state'] === \Nino\Modules\Themes\Store::stamp( $site ) && $body['state'] !== $base && $body['canRestore'] === true && $digestOf( $state. '/previous' ) !== $previous && $digestOf( $state. '/state' ) === $previous );
[ $again ] = themesCall( $site, 'themes/restore', [ 'base' => $base ] );
check( 'L. the stamp of the state before the restore is not the stamp after it: asking twice with the old one is 409', $again === 409 );
themesCall( $site, 'themes/restore' );

// -- delete: a stylesheet that is somebody else's is asked about, as it is for every change that would replace it
themesCall( $site, 'themes/apply', [ 'theme' => 'craft-probe' ] );
themesCall( $site, 'themes/save-as', [ 'mix' => themesMix( 'To delete', [] ) ] );
file_put_contents( $sheet, ".mine { }\n" );
[ $status, $body ] = themesCall( $site, 'themes/delete', [ 'theme' => 'own:to-delete' ] );
check( 'L. a delete that would build the stylesheet over one that is somebody else\'s is 409 themes_foreign, and the theme is there', $status === 409 && $body['code'] === 'themes_foreign' && is_file( $state. '/state/own/to-delete.json' ) === true );
[ $status ] = themesCall( $site, 'themes/delete', [ 'theme' => 'own:to-delete', 'force' => true ] );
check( 'L. ...and told that it may, it is deleted - the stylesheet is the compiler\'s again, and the one it replaced is the last state\'s', $status === 200 && is_file( $state. '/state/own/to-delete.json' ) === false && \Nino\Modules\Themes\Compiler::isOwn( (string) file_get_contents( $sheet ) ) === true && (string) file_get_contents( $state. '/previous/theme.css' ) === ".mine { }\n" );

// -- force is for the stylesheet that is somebody else's and nothing else: a stamp that is stale is refused whatever the call says of force
$lForce = [];
$lBrand = (string) $listOf()['brandStamp'];
$lOpen = $open( 'own:lindner' );
[ $lForce[] ] = themesCall( $site, 'themes/brand', [ 'brand' => $own( [ 'color-1' => '#111111' ] ), 'brandBase' => 'stale', 'force' => true ] );
[ $lForce[] ] = themesCall( $site, 'themes/save', [ 'theme' => 'own:lindner', 'mix' => themesMix( 'Lindner', [] ), 'base' => 'stale', 'force' => true ] );
[ $lForce[] ] = themesCall( $site, 'themes/save', [ 'theme' => 'own:lindner', 'mix' => themesMix( 'Lindner', [] ), 'base' => $lOpen['stamp'], 'brand' => $own( [ 'color-1' => '#111111' ] ), 'brandBase' => 'stale', 'force' => true ] );
[ $lForce[] ] = themesCall( $site, 'themes/save-as', [ 'mix' => themesMix( 'Forced', [] ), 'brand' => $own( [ 'color-1' => '#111111' ] ), 'brandBase' => 'stale', 'force' => true ] );
[ $lForce[] ] = themesCall( $site, 'themes/restore', [ 'base' => 'stale', 'force' => true ] );
[ $lForce[] ] = themesCall( $site, 'themes/delete', [ 'theme' => 'own:lindner', 'base' => 'stale', 'force' => true ] );
check( 'L. force is the answer to a stylesheet that is somebody else\'s and is no answer to a stamp: a brand, a save, a save as with a brand, a restore and a delete that name a stamp that is not the one that stands are 409 although they say force, and nothing is written',
	$lForce === array_fill( 0, 6, 409 ) && (string) $listOf()['brandStamp'] === $lBrand && is_file( $state. '/state/own/forced.json' ) === false && is_file( $state. '/state/own/lindner.json' ) === true );

// -- a stylesheet that the web server may not read is somebody else's, and the list answers
file_put_contents( $sheet, ".mine { }\n" );
// What the web server is to have of a project that somebody else's file is in: the files it keeps are its own - the lock, the state, the log - and the directory is its to write in
exec( 'chmod -R a+rwX '. escapeshellarg( ninoSandboxDir( $site ). '/private' ) );
chmod( $sheet, 0 );
$switched = function_exists( 'posix_geteuid' ) === true && posix_geteuid() === 0 && function_exists( 'posix_seteuid' ) === true && posix_seteuid( 65534 ) === true;
// Who runs this cannot read the file: the user it switched to, or the owner of the file who is not root - the rights of a file hold for its owner too, and only root reads past them
$server = $switched === true || ( function_exists( 'posix_geteuid' ) === true && posix_geteuid() !== 0 && is_readable( $sheet ) === false );
ninoWarnings();
[ $status, $body ] = themesCall( $site, 'themes/list' );
[ $applied, $appliedBody ] = themesCall( $site, 'themes/apply', [ 'theme' => 'craft-probe' ] );
$booted = themesReboot( $site, [ '\\Nino\\Modules\\Assets', '\\Nino\\Modules\\Basis', '\\Nino\\Modules\\Themes' ] );
$listed = in_array( \Nino\Modules\Themes::SHEET, \Nino\Html::getAssets( $booted, THEMES_BUNDLE ), true );
$bundled = \Nino\Modules\Assets::stylesheet( $booted, THEMES_BUNDLE );
[ $replaced, $replacedBody ] = themesCall( $site, 'themes/apply', [ 'theme' => 'craft-probe', 'force' => true ] );
$readable = \Nino\Modules\Themes\Store::readable( $sheet ) === true && \Nino\Modules\Themes\Compiler::isOwn( (string) file_get_contents( $sheet ) ) === true;
$warned = ninoWarnings();

// and where the directory is not the web server's to write in, the file that cannot be read cannot be replaced either
if( $switched === true )
	posix_seteuid( 0 );

unlink( $sheet );
file_put_contents( $sheet, ".mine { }\n" );
chmod( $sheet, 0 );
chmod( dirname( $sheet ), 0555 );
$locked = false;
$lockedBody = [];

if( $server === true ) {
	if( $switched === true )
		posix_seteuid( 65534 );
	themesCall( $site, 'themes/restore', [ 'force' => true ] );
	[ $locked, $lockedBody ] = themesCall( $site, 'themes/apply', [ 'theme' => 'band-probe', 'force' => true ] );
	if( $switched === true )
		posix_seteuid( 0 );
}

chmod( dirname( $sheet ), 0755 );
chmod( $sheet, 0644 );
$suffix = $server === true ? '' : ' (run as root with no user to switch to: it reads the file, so only the answer is looked at)';
check( 'L. a stylesheet of the project that the web server may not read is a stylesheet that is not the feature\'s and cannot be read: the list answers, says foreign and unreadable, and a change that would replace it is asked about with a code of its own - no warning, which the kernel makes a 500 of'. $suffix,
	$status === 200 && ( $server === false || ( $body['foreign'] === true && $body['unreadable'] === true && $applied === 409 && $appliedBody['code'] === 'themes_unreadable' && $warned === [] ) ) && ( $server === true || $body['unreadable'] === false ) );
check( 'L. told to replace it, the web server does: the file that is put in place keeps the rights of the one it replaces, so it is opened for reading - the website has its stylesheet, and the file that cannot be read was not in the bundle of the request that would have read it'. $suffix,
	$server === false || ( $replaced === 200 && $readable === true && $listed === false && $bundled !== '' ) );
check( 'L. where the file cannot be replaced - the directory is not the web server\'s - the API does not say that it is done: 409 themes_locked'. $suffix,
	$server === false || ( $locked === 409 && $lockedBody['code'] === 'themes_locked' ) );
unlink( $sheet );

// -- a font that is gone is a font that is not there, and the brand stays what it was
themesCall( $site, 'themes/apply', [ 'theme' => 'craft-probe' ] );
[ $status ] = themesCall( $site, 'themes/brand', [ 'brand' => $own( [ 'color-1' => '#9a4b1d', 'mode' => 'dark', 'font-title' => 'open-sans' ] ) ] );
$fontDir = \Nino\Filesystem::path( $site, '/fonts/open-sans' );
rename( $fontDir, $fontDir. '.gone' );
unset( $site['./nino/themes/read'] );
$stored = json_decode( (string) file_get_contents( $state. '/state/brand.json' ), true );
$opened = $open( 'craft-probe' );
check( 'L. a font whose folder is gone stays what the project chose: the brand that is read has every value it had, the answer says which fonts are not there - the one the project chose and the one the theme suggests - and the panel is told where to say it',
	$status === 200 && $opened['brand']['stored']['font-title'] === [ 'own' => true, 'value' => 'open-sans' ] && $opened['brand']['stored']['color-1']['value'] === '#9a4b1d' && $opened['brand']['stored']['mode']['value'] === 'dark'
	&& $opened['brand']['unavailable'] === [ 'font-title', 'font-button' ] && $stored['font-title']['value'] === 'open-sans' );
themesCall( $site, 'themes/save-as', [ 'mix' => themesMix( 'Other', [] ) ] );
$sheetNow = (string) file_get_contents( $sheet );
check( 'L. the next save of anything builds the stylesheet with the colour and the mode of the brand and the stack of the system for the font that is not there, and the brand is as it was',
	str_contains( $sheetNow, '--brand-color-1: #9a4b1d;' ) === true && str_contains( $sheetNow, '--brand-scheme: dark;' ) === true && str_contains( $sheetNow, '@font-face' ) === false && str_contains( $sheetNow, '--brand-font-title: system-ui' ) === true
	&& json_decode( (string) file_get_contents( $state. '/state/brand.json' ), true ) === $stored );
$posted = $own( [ 'color-1' => '#9a4b1d', 'mode' => 'dark', 'font-title' => 'open-sans' ] );
[ $same ] = themesCall( $site, 'themes/brand', [ 'brand' => $posted ] );
[ $other, $otherBody ] = themesCall( $site, 'themes/brand', [ 'brand' => $own( [ 'font-text' => 'open-sans' ] ) ] );
check( 'L. a request that leaves the font alone is no request to change it: the brand with the stored value is taken; one that chooses a font that is not there is not, which stays strict - 400 at the key',
	$same === 200 && json_decode( (string) file_get_contents( $state. '/state/brand.json' ), true )['font-title']['value'] === 'open-sans' && $other === 400 && $otherBody['code'] === 'themes_brand' );
rename( $fontDir. '.gone', $fontDir );
unset( $site['./nino/themes/read'] );
themesCall( $site, 'themes/save-as', [ 'mix' => themesMix( 'Another', [] ) ] );
check( 'L. a font that comes back is the project\'s again: the next build has its @font-face and the stack with its name, and nothing was asked of anybody', str_contains( (string) file_get_contents( $sheet ), '@font-face { font-family: "Open Sans"' ) === true && $open( 'craft-probe' )['brand']['unavailable'] === [] );
$broken = $stored;
$broken['color-1'] = [ 'own' => true, 'value' => 'not a colour' ];
\Nino\Filesystem::putFileContent( $site, '/data/theme/state/brand.json', $broken );
check( 'L. a value that is wrong for itself is not own and the others stay: a stored brand is read value by value', \Nino\Modules\Themes\Store::read( $site )['brand']['color-1'] === [ 'own' => false ] && \Nino\Modules\Themes\Store::read( $site )['brand']['mode']['value'] === 'dark' );
\Nino\Filesystem::putFileContent( $site, '/data/theme/state/brand.json', $stored );

// -- the stamp of an own theme is asked for when it is applied, under the lock, and force is no answer to it
$lApply = $open( 'own:lindner' );
themesCall( $site, 'themes/save', [ 'theme' => 'own:lindner', 'mix' => themesMix( 'LINDNER RENAMED', [], '.again { }' ) ] );
[ $stale, $staleBody ] = themesCall( $site, 'themes/apply', [ 'theme' => 'own:lindner', 'base' => $lApply['stamp'], 'force' => true ] );
[ $none ] = themesCall( $site, 'themes/apply', [ 'theme' => 'own:lindner', 'base' => null ] );
[ $fresh ] = themesCall( $site, 'themes/apply', [ 'theme' => 'own:lindner', 'base' => $open( 'own:lindner' )['stamp'] ] );
check( 'L. an own theme is applied as the one who asks saw it: a stamp that is not the stamp of the theme - or none - is 409 themes_changed, though it says force, and the website keeps what it shows; with the stamp it is applied',
	$stale === 409 && $staleBody['code'] === 'themes_changed' && $none === 409 && $fresh === 200 && \Nino\Modules\Themes\Store::read( $site )['active']['theme'] === 'own:lindner' );
check( 'L. a card of the list says the stamp of its own theme, the one open gives, so that a card can apply what it shows; a theme of a package has none', array_values( array_filter( $listOf()['themes'], static fn( array $card ): bool => $card['key'] === 'own:lindner' ) )[0]['stamp'] === $open( 'own:lindner' )['stamp'] && isset( array_values( array_filter( $listOf()['themes'], static fn( array $card ): bool => $card['key'] === 'craft-probe' ) )[0]['stamp'] ) === false );

// -- the last state says what comes back
themesCall( $site, 'themes/apply', [ 'theme' => 'craft-probe' ] );
$last = $listOf()['last'];
check( 'L. the last state says when it was saved and which theme the website showed in it - the theme that was the website\'s before the last change - by key and by name, and none where it was not kept', $last !== null && is_int( $last['at'] ) === true && abs( $last['at'] - time() ) < 3600 && $last['theme'] === 'own:lindner' && $last['name'] === 'LINDNER RENAMED'
	&& themesCall( $site, 'themes/restore' )[1]['last']['theme'] === 'craft-probe' );
\Nino\Filesystem::removeDir( ninoSandboxDir( $site ). '/private/data/theme/previous' );
check( 'L. a last state that is not there has no time to tell', $listOf()['last'] === null && $listOf()['canRestore'] === false );

// -- what a change that was broken off left is gone with the next one that writes
themesCall( $site, 'themes/apply', [ 'theme' => 'nino' ] );
mkdir( $state. '/building' );
file_put_contents( $state. '/building/stray', 'x' );
themesCall( $site, 'themes/apply', [ 'theme' => 'band-probe' ] );
check( 'L. a copy of a state that a change left when it was broken off is removed by the next change that writes, and what was in it is no part of the state that is made', is_dir( $state. '/building' ) === false && file_exists( $state. '/state/stray' ) === false );
mkdir( $state. '/building' );
themesCall( $site, 'themes/restore' );
check( 'L. ...and by a restore', is_dir( $state. '/building' ) === false );

// -- the log names the new action, and the manual the panel
check( 'L. the log of the workbench names what was done to the brand', \Nino\Modules\Themes\Admin::log( 'themes/brand', [] ) !== '' && \Nino\Modules\Themes\Admin::log( 'themes/brand', [] ) !== \Nino\Modules\Themes\Admin::log( 'themes/apply', [ 'theme' => 'x' ] ) );
} )();

check( 'Z. where the list is narrow - 37.5rem of its own width, asked of the list as a container and not of the window - the pictures are not drawn, and the foot of a picture fades into the ground of its box and is not cut through a line', preg_match( '/#themes-list\s*\{\s*container: themes-list \/ inline-size;/', $sheet ) === 1 && preg_match( '/@container themes-list \(max-width: 37\.5rem\)\s*\{\s*#themes-app \.themes-thumb\s*\{\s*display: none;/', $sheet ) === 1
	&& preg_match( '/\.themes-thumb::after\s*\{[^}]*linear-gradient/', $sheet ) === 1 );

// the widths are a strip of steps that is not there where the view is too narrow for the preview beside the settings
$narrow = preg_match( '/@container nino-split \(max-width: 40rem\) \{(.*)\}\s*\}\s*$/s', preg_replace( '~/\*.*?\*/~s', '', $sheet ), $found ) === 1 ? $found[1] : '';
$bare = preg_replace( '~/\*.*?\*/~s', '', $sheet );
preg_match_all( '/([^{}]*\.themes-card[^{}]*\.nino-admin-actions[^{}]*)\{([^}]*)\}/', $bare, $rules, PREG_SET_ORDER );
$foot = array_filter( $rules, static fn( array $rule ): bool => str_contains( $rule[2], 'margin: auto 0 0;' ) === true );
$cleared = array_filter( $rules, static fn( array $rule ): bool => preg_match( '/margin(?:-top)?:\s*0\s*;/', $rule[2] ) === 1 );
check( 'Z. the cards of the list are as high as one another: the rows of the grid are one height and the card fills its row, the lines of what a card says are a fixed number, and the buttons stand at the foot - the one rule that sets the margin of the buttons gives them the free room above them, and no later or earlier rule clears it', preg_match( '/\.themes-cards\s*\{[^}]*grid-auto-rows: 1fr;[^}]*align-items: stretch;/', $sheet ) === 1 && preg_match( '/\.themes-clamp\s*\{[^}]*-webkit-line-clamp: 3;/', $sheet ) === 1 && count( $foot ) === 1 && $cleared === [] );
check( 'K. the choice of the width is hidden by the width of the split view - a container query of the workbench - and the one media query the sheet has is the phone rule of the bar', preg_match( '/#themes-app \.themes-widthbox\s*\{\s*display: none;/', $narrow ) === 1 && substr_count( $sheet, '@media' ) === 1 && str_contains( $sheet, '@media (max-width: 38rem)' ) === true );

// an own theme is in the address as own.<key>: no theme of a package can have a dot in its key
$folder = ninoSandboxDir( $site ). '/themes/own.x';
mkdir( $folder );
file_put_contents( $folder. '/theme.json', json_encode( [ 'key' => 'own.x', 'version' => '1.0.0', 'name' => [ 'en_US' => 'Own X', 'de_DE' => 'Own X' ], 'description' => [ 'en_US' => 'x', 'de_DE' => 'x' ], 'brand' => [] ] ) );
unset( $site['./nino/themes/read'] );
$read = \Nino\Modules\Themes\Reader::read( $site );
check( 'K. a theme of a package that is called as an own theme is in the address - the key own.x - is not read: its key is no slug, so that the address of an own theme is never the key of another', isset( $read['themes']['own.x'] ) === false && isset( $read['themes']['own'] ) === false && str_contains( json_encode( $read['rejected'] ), 'own.x' ) === true );

// ---------------------------------------------------------------------------------------------------------------------------------
echo "\nM. The demo of a group, the copy of a theme, and what the second round left\n";

( function() use ( $root, $fixtures, &$sandboxes ): void {

$modules = [ '\\Nino\\Modules\\Assets', '\\Nino\\Modules\\Basis', '\\Nino\\Modules\\Themes' ];
$site = themesSite();
ninoUser( $site, 'designer@example.com' );
$site['/nino/http/routes']['GET://'] = [ 'uri' => '/home', 'body' => '[assets '. THEMES_BUNDLE. ']<p id="the-page-of-the-route">[section-wrap][section-row][section-col][text text="<p>The page</p>"][/section-col][/section-row][/section-wrap]</p>' ];
$cache = ninoSandboxDir( $site ). '/private/data/.preview/themes';
[ $status, $made ] = themesCall( $site, 'themes/preview', [ 'mix' => \Nino\Modules\Themes\Mix::of( $site, 'band-probe' ) ] );
$token = (string) $made['token'];
$groups = \Nino\Components::groups( $site );

// the whole answer of the site to one request: the route, the callbacks of the response and the rendering of the body
$answer = static function( string $query ) use ( &$site ): array {

	$request = [ 'REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/?'. $query, 'REMOTE_ADDR' => '127.0.0.1' ];
	\Nino\Http::request( $site, $request );
	\Nino\Http::response( $site, $request );
	\Nino\Html::response( $site, $request );

	return $request['/nino/http/response'];
};

check( 'M. the register has a demo for the groups of Basis and none for a group that brings none, and the list tells the panel which: the name of the group and whether it has one, never the text', $status === 200 && array_filter( array_keys( $groups ), static fn( string $name ): bool => $groups[$name]['demo'] !== '' ) !== []
	&& ( static function() use ( &$site ): bool {
		$list = themesCall( $site, 'themes/list' )[1]['groups'];
		$flags = array_column( $list, 'demo', 'key' );
		$all = \Nino\Components::groups( $site );

		return array_keys( $flags ) === array_keys( $all ) && array_filter( $list, static fn( array $group ): bool => is_bool( $group['demo'] ) === false ) === []
			&& array_filter( array_keys( $all ), static fn( string $name ): bool => $flags[$name] !== ( $all[$name]['demo'] !== '' ) ) === [] && str_contains( (string) json_encode( $list ), 'A section' ) === false;
	} )() );

$demo = [ 'section', 'header', 'footer', 'button' ];
$pages = [];

foreach( $demo as $name )
	$pages[$name] = $answer( 'nino-preview='. $token. '&nino-theme-demo='. $name );

$plain = $answer( 'nino-preview='. $token );
$source = static fn( string $name ): string => (string) $groups[$name]['demo'];

check( 'M. a preview that names a group with a demo answers a page of its own in place of the page of the route: the demo of the group is in it - drawn, with its components - and the page of the route is not', array_filter( $demo, static fn( string $name ): bool => str_contains( (string) $pages[$name]['body'], 'the-page-of-the-route' ) === true || str_contains( (string) $pages[$name]['body'], '[section-' ) === true || str_contains( (string) $pages[$name]['body'], '[[' ) === true ) === []
	&& str_contains( (string) $pages['section']['body'], 'section-wrap' ) === true && str_contains( (string) $pages['button']['body'], 'class="button' ) === true && str_contains( (string) $plain['body'], 'the-page-of-the-route' ) === true );
check( 'M. it is a page: the language, the character set, the stylesheet of the DRAFT in place of the public bundle, and the main part - and the answer is 200 whatever the route said, not kept and no matter of a search engine', array_filter( $pages, static fn( array $page ): bool => str_starts_with( ltrim( (string) $page['body'] ), '<!doctype html>' ) === false || str_contains( (string) $page['body'], '<style data-nino-theme-preview>' ) === false
		|| str_contains( (string) $page['body'], '<main id="nino-main"' ) === false || $page['statusCode'] !== 200 || ( $page['header']['Cache-Control'] ?? '' ) !== 'no-store' || ( $page['header']['X-Robots-Tag'] ?? '' ) !== 'noindex' ) === []
	&& substr_count( (string) $pages['section']['body'], '<style data-nino-theme-preview>' ) === 1 && str_contains( (string) $pages['section']['body'], 'preview' ) === true );
check( 'M. the demo of the header stands where a header stands - before the main part, which is empty - the demo of the footer where a footer stands - after it - and the others in the main part', ( static function() use ( $pages ): bool {

	$position = static fn( string $html, string $needle ): int => (int) strpos( $html, $needle );
	$part = static function( string $html ): string { preg_match( '#<main id="nino-main"[^>]*>(.*?)</main>#s', $html, $found ); return $found[1] ?? 'none'; };

	return trim( $part( (string) $pages['header']['body'] ) ) === '' && $position( (string) $pages['header']['body'], 'class="header-wrap' ) < $position( (string) $pages['header']['body'], '<main id="nino-main"' ) && $position( (string) $pages['header']['body'], 'class="header-wrap' ) > 0
		&& trim( $part( (string) $pages['footer']['body'] ) ) === '' && $position( (string) $pages['footer']['body'], 'class="footer-wrap' ) > $position( (string) $pages['footer']['body'], '</main>' ) && $position( (string) $pages['footer']['body'], '</main>' ) > 0
		&& str_contains( $part( (string) $pages['section']['body'] ), 'section-wrap' ) === true && str_contains( $part( (string) $pages['button']['body'] ), 'button' ) === true;
} )() );

check( 'M. the page of a demo is as high as the demo and no higher - the body has no minimum height after the bundle, which would put the foot of the footer demo at the bottom of a window that is not there - and a main part that holds nothing is not in the page: the header and the footer stand at the top',
	array_filter( $demo, static fn( string $name ): bool => preg_match( '#</style>\s*<style>body \{ min-height: 0; \}</style>#', (string) $pages[$name]['body'] ) !== 1 ) === [] && str_contains( (string) $pages['footer']['body'], '<main id="nino-main" tabindex="-1" hidden>' ) === true && str_contains( (string) $pages['header']['body'], '<main id="nino-main" tabindex="-1" hidden>' ) === true
	&& str_contains( (string) $pages['section']['body'], '<main id="nino-main" tabindex="-1">' ) === true );
$draftCss = (string) file_get_contents( $cache. '/'. $token. '/theme.css' );
$inStyle = static function( string $html ): string { preg_match( '#<style data-nino-theme-preview>(.*?)</style>#s', $html, $found ); return str_replace( '<\\/', '</', $found[1] ?? '' ); };
$publicFile = ninoSandboxDir( $site ). '/public/.cache/style.css';
$publicBefore = is_file( $publicFile ) === true ? (string) file_get_contents( $publicFile ) : null;
$answer( 'nino-preview='. $token. '&nino-theme-demo=section' );

check( 'M. the stylesheet in it is the draft - what the token holds - and the public bundle is not touched by the demo', str_contains( $inStyle( (string) $pages['section']['body'] ), substr( $draftCss, 0, 200 ) ) === true && ( is_file( $publicFile ) === true ? (string) file_get_contents( $publicFile ) : null ) === $publicBefore );
check( 'M. the mark outlines the component in the demo as in a page', str_contains( $answer( 'nino-preview='. $token. '&nino-theme-demo=section&mark=section-col' )['body'], '.section-col { outline: 2px solid #e8590c' ) === true );

// a group with no demo, and a name that is no group: the page is the page of the route
$none = array_values( array_filter( array_keys( $groups ), static fn( string $name ): bool => $groups[$name]['demo'] === '' ) );
check( 'M. a group that has no demo - a name the register has with nothing to show - leaves the page as it is: the page of the route, with the draft', $none === [] || array_filter( $none, static fn( string $name ): bool => str_contains( (string) $answer( 'nino-preview='. $token. '&nino-theme-demo='. $name )['body'], 'the-page-of-the-route' ) === false ) === [] );
check( 'M. what is no name of a group is no demo and reads nothing: a name the register has not, upper case, a path, a list, a selector, a null byte, too long, spaces, nothing - the page of the route stands, with the draft',
	array_filter( [ 'zzreflectzz', 'ZZREFLECTZZ', '../section', 'section/', 'section,header', 'a{}', "section\0", str_repeat( 'a', 200 ), '', ' section', 'section ' ], static function( string $value ) use ( $answer, $token ): bool {

		$body = (string) $answer( 'nino-preview='. $token. '&nino-theme-demo='. rawurlencode( $value ) )['body'];

		return str_contains( $body, 'the-page-of-the-route' ) === false || str_contains( $body, '<style data-nino-theme-preview>' ) === false || str_contains( $body, 'zzreflectzz' ) === true || str_contains( $body, 'ZZREFLECTZZ' ) === true;
	} ) === [] && str_contains( (string) $answer( 'nino-preview='. $token. '&nino-theme-demo[]=section' )['body'], 'the-page-of-the-route' ) === true && str_contains( (string) $answer( 'nino-preview='. $token. '&nino-theme-demo[a]=section' )['body'], 'the-page-of-the-route' ) === true );

$register = \Nino\Components::GROUPS;
$saved = $site[$register]['text']['demo'];
$site[$register]['text']['demo'] = '';
check( 'M. a group the register has no demo for - its demo is empty - is the page of the route, though the name is the name of a group', str_contains( (string) $answer( 'nino-preview='. $token. '&nino-theme-demo=text' )['body'], 'the-page-of-the-route' ) === true );
$site[$register]['text']['demo'] = $saved;

// nobody without a token, a token that is not the session's, one that ran out, an account without the right, nobody signed in
$shows = static fn( string $query ): bool => str_contains( (string) $answer( $query )['body'], '<main id="nino-main"' ) === true && str_contains( (string) $answer( $query )['body'], 'the-page-of-the-route' ) === false;

check( 'M. the demo is for a request that holds a preview: with no token, with a token nobody gave out, with one in the wrong form, the page is the page of the route - and nothing of the demo is in it', $shows( 'nino-preview='. $token. '&nino-theme-demo=section' ) === true && $shows( 'nino-theme-demo=section' ) === false
	&& $shows( 'nino-preview='. str_repeat( '0', 32 ). '&nino-theme-demo=section' ) === false && $shows( 'nino-preview='. strtoupper( $token ). '&nino-theme-demo=section' ) === false );
$held = $_SESSION[$site['./nino/uid']]['/nino/preview/themes'];
$_SESSION[$site['./nino/uid']]['/nino/preview/themes'] = [ $token => time() - 1 ];
check( 'M. ...with a token whose time is out', themesKept( $cache. '/'. $token, static fn(): bool => $shows( 'nino-preview='. $token. '&nino-theme-demo=section' ) === false ) );
$_SESSION[$site['./nino/uid']]['/nino/preview/themes'] = $held;
$thumbs = themesCall( $site, 'themes/thumb', [ 'theme' => 'band-probe' ] )[1];
check( 'M. ...and it is as good with the token of a thumbnail, which is a token like the others', $shows( 'nino-preview='. $thumbs['token']. '&nino-theme-demo=section' ) === true );
$site['/nino/http/routes']['GET://contact'] = [ 'uri' => '/contact', 'body' => '[assets '. THEMES_BUNDLE. '][assets /.cache/script.js]<p id="the-contact-page">c</p>' ];
$site['/nino/http/routes']['GET://'] = [ 'uri' => '/home', 'body' => '[assets '. THEMES_BUNDLE. '][assets /.cache/script.js]<p id="the-page-of-the-route">[section-wrap][section-row][section-col][text text="<p>The page</p>"][/section-col][/section-row][/section-wrap]</p>', 'header' => [ 'Cache-Control' => 'max-age=999' ] ];
$at = static function( string $path, string $query, string $method = 'GET' ) use ( &$site ): array {

	$request = [ 'REQUEST_METHOD' => $method, 'REQUEST_URI' => $path. '?'. $query, 'REMOTE_ADDR' => '127.0.0.1' ];
	\Nino\Http::request( $site, $request );
	\Nino\Http::response( $site, $request );
	\Nino\Html::response( $site, $request );

	return $request['/nino/http/response'];
};
check( 'M. the demo is the answer of the start address and of no other: the page of the contact route, of any other route and of a path the site has no route for is what it is, with the draft, whatever group the query names',
	str_contains( (string) $at( '/contact', 'nino-preview='. $token. '&nino-theme-demo=section' )['body'], 'the-contact-page' ) === true && str_contains( (string) $at( '/', 'nino-preview='. $token. '&nino-theme-demo=section' )['body'], 'the-page-of-the-route' ) === false
	&& str_contains( (string) $at( '/robots.txt', 'nino-preview='. $token. '&nino-theme-demo=section' )['body'], 'section-wrap' ) === false && str_contains( (string) $at( '/_admin/', 'nino-preview='. $token. '&nino-theme-demo=section' )['body'], 'A section' ) === false
	&& str_contains( (string) $at( '/', 'nino-preview='. $token. '&nino-theme-demo=section', 'POST' )['body'], 'A section' ) === false );
check( 'M. the answer of the demo is never kept, whatever the route says of the cache: its own header is the one that is sent', $at( '/', 'nino-preview='. $token. '&nino-theme-demo=section' )['header']['Cache-Control'] === 'no-store' && $at( '/', 'nino-preview='. $token )['header']['Cache-Control'] === 'max-age=999' );
[ , $picture ] = themesCall( $site, 'themes/thumb', [ 'theme' => 'band-probe' ] );
$linked = static function( string $query ) use ( $at, &$site ): string {

	$at( '/contact', $query );
	$link = '<script src="/public/.cache/script.js"></script>';
	\Nino\Callbacks::doCallbacks( $site, '/nino/shortcodes/assets/output/js', $link );

	return $link;
};
$link = '<script src="/public/.cache/script.js"></script>';
$asPicture = $linked( $picture['query'] );
$asPreview = $linked( 'nino-preview='. $token );
$again = $linked( $picture['query'] );
$plainPage = $linked( '' );
check( 'M. the link to the script bundle of a page that is asked for with the token of a thumbnail is none - the scripts of the site do not run in a picture - and it is what it was for a request with the token of a preview, for none, and for the request after a picture in the same process', $asPicture === '' && $again === '' && $asPreview === $link && $plainPage === $link );
unset( $_SESSION[$site['./nino/uid']]['/nino/preview/themes'], $_SESSION[$site['./nino/uid']]['/nino/preview/themes-pictures'] );
check( 'M. ...with a token the session does not hold', $shows( 'nino-preview='. $token. '&nino-theme-demo=section' ) === false );
$_SESSION[$site['./nino/uid']]['/nino/preview/themes'] = $held;
$perms = $site['./nino/auth/current']['perms'] ?? null;
$site['./nino/auth/current']['perms'] = [ '/_admin/elements/manage' ];
check( 'M. ...with the right taken from the account that holds it', $perms !== null && $shows( 'nino-preview='. $token. '&nino-theme-demo=section' ) === false );
$site['./nino/auth/current']['perms'] = $perms;
\Nino\Auth::logoutUser( $site );
check( 'M. ...and for nobody who is signed in: a visitor gets the page of the route, whatever the query says', $shows( 'nino-preview='. $token. '&nino-theme-demo=section' ) === false && str_contains( (string) $answer( 'nino-preview='. $token. '&nino-theme-demo=section' )['body'], 'the-page-of-the-route' ) === true );


// -- a theme is copied: the mix and the own css of what is saved, under a new name; the brand is the project's
$dup = themesSite();
ninoUser( $dup, 'designer@example.com' );
$dupState = ninoSandboxDir( $dup ). '/private/data/theme';
themesCall( $dup, 'themes/brand', [ 'brand' => [ 'color-1' => [ 'own' => true, 'value' => '#9a4b1d' ], 'color-2' => [ 'own' => false ], 'tone' => [ 'own' => false ], 'mode' => [ 'own' => false ], 'font-title' => [ 'own' => false ], 'font-text' => [ 'own' => false ], 'font-button' => [ 'own' => false ] ] ] );
[ $made ] = themesCall( $dup, 'themes/save-as', [ 'mix' => themesMix( 'Lindner', [ 'page' => [ 'from' => 'band-probe' ], 'section' => [ 'from' => 'craft-probe', 'values' => [ 'section-wrap.space' => 2 ] ] ], '.mine { color: red; }' ) ] );
themesCall( $dup, 'themes/apply', [ 'theme' => 'nino' ] );
$source = (string) file_get_contents( $dupState. '/state/own/lindner.json' );
$brandBytes = (string) file_get_contents( $dupState. '/state/brand.json' );
$activeBytes = (string) file_get_contents( $dupState. '/state/active.json' );
$before = themesCall( $dup, 'themes/list' )[1];
// The stylesheet is dated into the past, so that a write of it shows in its time whatever the clock says
$dupSheet = ninoSandboxDir( $dup ). '/private/assets/theme.css';
touch( $dupSheet, 1000000000 );
clearstatcache();
$sheetBytes = (string) file_get_contents( $dupSheet );
$bundleLink = \Nino\Html::renderHtml( themesReboot( $dup, $modules ), '[assets '. THEMES_BUNDLE. ']' );
[ $status, $body ] = themesCall( $dup, 'themes/duplicate', [ 'theme' => 'own:lindner', 'name' => 'Lindner (copy)' ] );
$copy = is_file( $dupState. '/state/own/lindner-copy.json' ) === true ? json_decode( (string) file_get_contents( $dupState. '/state/own/lindner-copy.json' ), true ) : [];
$orig = json_decode( $source, true );

check( 'M. a theme of one\'s own is copied under a new name: the answer is the copy - its key, its stamp, that the website does not show it - and the copy is a file of its own with the mix and the own css of the original, the name being the only thing that differs',
	$made === 200 && $status === 200 && $body['saved'] === true && $body['theme'] === 'own:lindner-copy' && $body['applied'] === false && preg_match( '/^[0-9a-f]{64}$/D', $body['stamp'] ) === 1 && $copy !== [] && $copy['name'] === 'Lindner (copy)'
	&& array_diff_key( $copy, [ 'name' => 1 ] ) === array_diff_key( $orig, [ 'name' => 1 ] ) && $copy['css'] === '.mine { color: red; }' && $copy['parts']['section']['values']['section-wrap.space'] === 2 );
check( 'M. ...and nothing else is touched: the original is the bytes it was, the brand of the project is not copied - it is the project\'s - and is the bytes it was, the website shows what it showed, and the state before is kept as the last one',
	(string) file_get_contents( $dupState. '/state/own/lindner.json' ) === $source && (string) file_get_contents( $dupState. '/state/brand.json' ) === $brandBytes && (string) file_get_contents( $dupState. '/state/active.json' ) === $activeBytes
	&& str_contains( (string) file_get_contents( $dupState. '/state/own/lindner-copy.json' ), 'color-1' ) === false && is_file( $dupState. '/previous/own/lindner.json' ) === true && is_file( $dupState. '/previous/own/lindner-copy.json' ) === false );
$after = themesCall( $dup, 'themes/list' )[1];
check( 'M. the list shows the copy as an own theme, once, with the stamp of its mix, and what the website shows is the theme it showed', count( $after['themes'] ) === count( $before['themes'] ) + 1 && count( array_filter( $after['themes'], static fn( array $theme ): bool => $theme['key'] === 'own:lindner-copy' && $theme['kind'] === 'own' && $theme['stamp'] === $body['stamp'] ) ) === 1 && $after['active']['theme'] === 'nino' );

clearstatcache();
check( 'M. a change of a theme the website does not show leaves its stylesheet the file it was - the bytes and the time - and the address of the bundle, so that no visitor fetches the css anew', $sheetBytes !== '' && (string) file_get_contents( $dupSheet ) === $sheetBytes && filemtime( $dupSheet ) === 1000000000 && \Nino\Html::renderHtml( themesReboot( $dup, $modules ), '[assets '. THEMES_BUNDLE. ']' ) === $bundleLink );

[ $status, $body ] = themesCall( $dup, 'themes/duplicate', [ 'theme' => 'craft-probe', 'name' => 'Craft (copy)' ] );
$copy = json_decode( (string) file_get_contents( $dupState. '/state/own/craft-copy.json' ), true );
check( 'M. a theme that comes with a package is copied as the mix it is - its part of the page and of every group, from the version that is installed - with no css of its own, which a package has none of', $status === 200 && $body['theme'] === 'own:craft-copy' && $copy['name'] === 'Craft (copy)' && $copy['css'] === '' && $copy['parts']['section']['from'] === 'craft-probe@1.0.0' && $copy['parts'] === \Nino\Modules\Themes\Mix::of( $dup, 'craft-probe' )['parts'] );

$state0 = \Nino\Modules\Themes\Store::stamp( $dup );
$refused = [];

foreach( [
	'a name that is taken, as a card is called' => [ [ 'theme' => 'nino', 'name' => 'Lindner (copy)' ], 409, 'themes_name_taken', 'name' ],
	'a name that is taken in upper case and with other spaces' => [ [ 'theme' => 'nino', 'name' => 'LINDNER   ( COPY )' ], 409, 'themes_name_taken', 'name' ],
	'a name with no letter or digit' => [ [ 'theme' => 'nino', 'name' => '(*)' ], 400, 'themes_name', 'name' ],
	'no name' => [ [ 'theme' => 'nino', 'name' => '' ], 400, 'themes_mix', 'name' ],
	'a name of 61 characters' => [ [ 'theme' => 'nino', 'name' => str_repeat( 'n', 61 ) ], 400, 'themes_mix', 'name' ],
	'a name with a line break' => [ [ 'theme' => 'nino', 'name' => "a\nb" ], 400, 'themes_mix', 'name' ],
	'a name that is no text' => [ [ 'theme' => 'nino', 'name' => [ 'a' ] ], 400, 'themes_mix', 'name' ],
	'a theme that is none' => [ [ 'theme' => 'nope', 'name' => 'Nope' ], 404, 'themes_unknown', 'theme' ],
	'an own theme that is none' => [ [ 'theme' => 'own:nope', 'name' => 'Nope' ], 404, 'themes_unknown', 'theme' ],
	'a path for an own theme' => [ [ 'theme' => 'own:../x', 'name' => 'Nope' ], 404, 'themes_unknown', 'theme' ],
	'a stamp that is not the one of the theme' => [ [ 'theme' => 'own:lindner', 'name' => 'Other', 'base' => str_repeat( '0', 64 ) ], 409, 'themes_changed', 'theme' ],
	'no stamp for an own theme' => [ [ 'theme' => 'own:lindner', 'name' => 'Other', 'base' => null ], 409, 'themes_changed', 'theme' ],
] as $what => [ $data, $code, $name, $field ] ) {

	if( array_key_exists( 'base', $data ) === true && $data['base'] === null )
		unset( $data['base'] );

	[ $got, $answer ] = ( array_key_exists( 'base', $data ) === true || str_starts_with( (string) ( $data['theme'] ?? '' ), 'own:' ) === false ) && ( $data['theme'] !== 'own:lindner' || array_key_exists( 'base', $data ) === true ) ? themesCall( $dup, 'themes/duplicate', $data ) : ninoCall( $dup, [ \Nino\Admin\Admin::class, 'handlePost' ], $data, 'themes/duplicate' );

	if( $got !== $code || $answer['code'] !== $name || $answer['field'] !== $field )
		$refused[] = $what. ' ('. $got. ' '. ( $answer['code'] ?? '' ). ')';
}

check( 'M. what is refused is refused as the other actions refuse it, with the field the screen shows it at - a name that is taken or says nothing, a name too long or in more than one line, a theme that is none, a stamp that is not the one of the theme or none at all - and nothing is written'. ( $refused === [] ? '' : ' - '. implode( ', ', $refused ) ),
	$refused === [] && \Nino\Modules\Themes\Store::stamp( $dup ) === $state0 && is_dir( $dupState. '/building' ) === false );

// the log says what was done and to what, on one line
check( 'M. the log of the workbench names the copy and the theme it is made of, each on one line and not longer than a name in a sentence', \Nino\Modules\Themes\Admin::log( 'themes/duplicate', [ 'theme' => 'own:lindner', 'name' => "A\nB" ] ) === 'Duplicate Theme own:lindner As A?B' && mb_strlen( \Nino\Modules\Themes\Admin::log( 'themes/duplicate', [ 'theme' => str_repeat( 'x', 300 ), 'name' => str_repeat( 'y', 300 ) ] ) ) <= 140 );

// the keys: a copy of a name whose key is taken by another name gets the next free one
themesCall( $dup, 'themes/save-as', [ 'mix' => themesMix( 'Craft-Copy', [] ) ] );
[ $status, $body ] = themesCall( $dup, 'themes/duplicate', [ 'theme' => 'nino', 'name' => 'Craft Copy!' ] );
check( 'M. a key that is taken by a theme of another name is not the copy\'s: the copy has the next free one, as a theme saved under a name has', $status === 409 || ( $status === 200 && preg_match( '/^own:craft-copy-\d+$/D', $body['theme'] ) === 1 ) );

// -- the thumbnail of a theme: a draft of its own, saved, in a set of its own
$thumb = themesSite();
ninoUser( $thumb, 'designer@example.com' );
$thumbCache = ninoSandboxDir( $thumb ). '/private/data/.preview/themes-pictures';
$previewCache = ninoSandboxDir( $thumb ). '/private/data/.preview/themes';
themesCall( $thumb, 'themes/save-as', [ 'mix' => themesMix( 'Lindner', [ 'section' => [ 'from' => 'craft-probe' ] ], '.thumb-marker { color: red; }' ) ] );
themesCall( $thumb, 'themes/brand', [ 'brand' => [ 'color-1' => [ 'own' => true, 'value' => '#9a4b1d' ], 'color-2' => [ 'own' => false ], 'tone' => [ 'own' => false ], 'mode' => [ 'own' => false ], 'font-title' => [ 'own' => false ], 'font-text' => [ 'own' => false ], 'font-button' => [ 'own' => false ] ] ] );
$previews = [];

for( $i = 0; $i < 6; $i++ )
	$previews[] = themesCall( $thumb, 'themes/preview', [ 'mix' => \Nino\Modules\Themes\Mix::of( $thumb, 'band-probe' ) ] )[1]['token'];

$heldPreviews = $_SESSION[$thumb['./nino/uid']]['/nino/preview/themes'];
[ $status, $body ] = themesCall( $thumb, 'themes/thumb', [ 'theme' => 'craft-probe' ] );
$token = (string) ( $body['token'] ?? '' );
$drawn = static fn( string $given ): array => is_file( $thumbCache. '/'. $given. '/draft.json' ) === true ? (array) json_decode( (string) file_get_contents( $thumbCache. '/'. $given. '/draft.json' ), true ) : [];
$css = (string) ( $drawn( $token )['css'] ?? '' );
$brand = \Nino\Modules\Themes\Store::read( $thumb )['brand'];

check( 'M. a thumbnail is asked of a theme by its key and answered as a draft is - a token of 32 hexadecimal digits, the query that asks for it, when it runs out and for how long - and with nothing else: the groups, the brand and the palette are the screen\'s of a draft',
	$status === 200 && preg_match( \Nino\Preview::TOKEN, $token ) === 1 && $body['query'] === 'nino-preview='. $token && array_keys( $body ) === [ 'token', 'query', 'ttl' ] && $body['ttl'] === \Nino\Preview::TTL );
check( 'M. what it draws is the theme as it is saved with the brand of the project: the stylesheet the compiler makes of the mix of the theme and the stored brand - no draft of the screen is in it - for the user who asked, in the folder of the drafts',
	$css === \Nino\Modules\Themes\Compiler::compile( $thumb, \Nino\Modules\Themes\Mix::of( $thumb, 'craft-probe' ), $brand )['css'] && str_contains( $css, '#9a4b1d' ) === true && json_decode( (string) file_get_contents( $thumbCache. '/'. $token. '/meta.json' ), true )['user'] === 'designer@example.com'
	&& is_array( $drawn( $token )['variants'] ?? null ) === true );
[ , $own ] = themesCall( $thumb, 'themes/thumb', [ 'theme' => 'own:lindner' ] );
check( 'M. an own theme is drawn with its own css, a theme that is none is none, and nothing is kept for either of the refusals', str_contains( (string) ( $drawn( $own['token'] )['css'] ?? '' ), '.thumb-marker { color: red; }' ) === true
	&& themesCall( $thumb, 'themes/thumb', [ 'theme' => 'nope' ] )[0] === 404 && themesCall( $thumb, 'themes/thumb', [ 'theme' => 'own:nope' ] )[0] === 404 && themesCall( $thumb, 'themes/thumb', [ 'theme' => '' ] )[0] === 404 );

check( 'M. a thumbnail does not count among the five drafts a person works with: the session holds the five previews it held and the tokens of the thumbnails in a set of its own, and every preview is a folder still',
	$_SESSION[$thumb['./nino/uid']]['/nino/preview/themes'] === $heldPreviews && count( $heldPreviews ) === 5 && array_keys( $_SESSION[$thumb['./nino/uid']]['/nino/preview/themes-pictures'] ) === [ $token, $own['token'] ] && array_filter( array_keys( $heldPreviews ), static fn( string $held ): bool => is_dir( $previewCache. '/'. $held ) === false ) === [] );

$more = [];

for( $i = 0; $i < 12; $i++ )
	$more[] = themesCall( $thumb, 'themes/thumb', [ 'theme' => $i % 2 === 0 ? 'nino' : 'band-probe' ] )[1]['token'];

check( 'M. twelve more thumbnails leave the previews as they were: the five tokens, the five folders - a list of themes is as many pictures as there are themes', $_SESSION[$thumb['./nino/uid']]['/nino/preview/themes'] === $heldPreviews && count( array_filter( array_keys( $heldPreviews ), static fn( string $held ): bool => is_dir( $previewCache. '/'. $held ) === true ) ) === 5 && count( $_SESSION[$thumb['./nino/uid']]['/nino/preview/themes-pictures'] ) === 14 );

[ , $again ] = themesCall( $thumb, 'themes/thumb', [ 'theme' => 'craft-probe', 'token' => $token ] );
check( 'M. a thumbnail asked again with the token it has is the same draft, written again - no new folder, no new token in the set', $again['token'] === $token && count( $_SESSION[$thumb['./nino/uid']]['/nino/preview/themes-pictures'] ) === 14 );
[ , $other ] = themesCall( $thumb, 'themes/thumb', [ 'theme' => 'craft-probe', 'token' => $heldPreviews ? (string) array_key_first( $heldPreviews ) : '' ] );
check( 'M. ...and a token of a preview is no token of a thumbnail: it is not taken over, and the preview is not written over', $other['token'] !== (string) array_key_first( $heldPreviews ) && is_dir( $thumbCache. '/'. $other['token'] ) === true );

$cap = ( new \ReflectionClassConstant( \Nino\Modules\Themes::class, 'PICTURES_MAX' ) )->getValue();
$first = $token;

for( $i = 0; $i < $cap; $i++ )
	themesCall( $thumb, 'themes/thumb', [ 'theme' => 'nino' ] );

check( 'M. a session holds at most 120 thumbnails - the oldest go, and their folders with them - so that a person who opens the list ten times a day does not fill the disk', $cap === 120 && count( $_SESSION[$thumb['./nino/uid']]['/nino/preview/themes-pictures'] ) === 120 && isset( $_SESSION[$thumb['./nino/uid']]['/nino/preview/themes-pictures'][$first] ) === false && is_dir( $thumbCache. '/'. $first ) === false
	&& $_SESSION[$thumb['./nino/uid']]['/nino/preview/themes'] === $heldPreviews );

// the token of a thumbnail is a token for the page as the others are, for the session and the account that made it - the demo section above shows it for the page of the preview
$_SESSION[$thumb['./nino/uid']]['/nino/preview/themes-pictures'] = [];
$view = static function( string $query ) use ( &$thumb ): string {

	ninoVisit( $thumb, '/?'. $query );

	return \Nino\Html::renderHtml( $thumb, '[assets '. THEMES_BUNDLE. ']' );
};
[ , $fresh ] = themesCall( $thumb, 'themes/thumb', [ 'theme' => 'own:lindner' ] );
check( 'M. a page asked with the token of a thumbnail is drawn with the stylesheet of that theme - and with a token that the session no longer holds, it is drawn as the website has it', str_contains( $view( $fresh['query'] ), '.thumb-marker' ) === true && str_contains( $view( $fresh['query'] ), 'data-nino-theme-preview' ) === true
	&& ( $_SESSION[$thumb['./nino/uid']]['/nino/preview/themes-pictures'] = [] ) === [] && str_contains( $view( $fresh['query'] ), 'data-nino-theme-preview' ) === false );

// -- a theme may be called fonts or settings: the tabs of the pane have addresses of their own, #themes-fonts and #themes-settings
$sandbox = ninoSandboxDir( $thumb ). '/themes';
$why = [];

foreach( [ 'fonts', 'settings' ] as $key ) {

	mkdir( $sandbox. '/'. $key );
	file_put_contents( $sandbox. '/'. $key. '/theme.json', json_encode( [ 'key' => $key, 'version' => '1.0.0', 'name' => [ 'en_US' => $key, 'de_DE' => $key ], 'description' => [ 'en_US' => 'x', 'de_DE' => 'x' ], 'brand' => [] ] ) );
	$why[$key] = \Nino\Modules\Themes\Reader::theme( $sandbox. '/'. $key, $key );
}

unset( $thumb['./nino/themes/read'] );
$listed = themesCall( $thumb, 'themes/list' )[1];
check( 'M. a theme may be called fonts or settings - the tabs of the pane are no addresses below #themes - and the reader keeps no name for the panel: the two folders are themes of the list, none of them refused, and each opens',
	is_array( $why['fonts'] ) === true && is_array( $why['settings'] ) === true && ( new \ReflectionClass( \Nino\Modules\Themes\Reader::class ) )->hasConstant( 'RESERVED' ) === false
	&& count( array_intersect( [ 'fonts', 'settings' ], array_column( $listed['themes'], 'key' ) ) ) === 2 && array_filter( $listed['rejected'], static fn( array $row ): bool => in_array( $row['folder'], [ 'fonts', 'settings' ], true ) ) === []
	&& themesCall( $thumb, 'themes/open', [ 'theme' => 'fonts' ] )[0] === 200 && themesCall( $thumb, 'themes/open', [ 'theme' => 'settings' ] )[0] === 200 );

// -- a font that is gone and back
$fonts = themesSite();
ninoUser( $fonts, 'designer@example.com' );
$fontState = ninoSandboxDir( $fonts ). '/private/data/theme';
$fontsFolder = \Nino\Filesystem::path( $fonts, '/fonts/open-sans' );
themesCall( $fonts, 'themes/save-as', [ 'mix' => themesMix( 'Typed', [] ), 'brand' => [ 'font-title' => [ 'own' => true, 'value' => 'open-sans' ], 'color-1' => [ 'own' => false ], 'color-2' => [ 'own' => false ], 'tone' => [ 'own' => false ], 'mode' => [ 'own' => false ], 'font-text' => [ 'own' => false ], 'font-button' => [ 'own' => false ] ] ] );
themesCall( $fonts, 'themes/apply', [ 'theme' => 'own:typed' ] );
$activeOf = static fn(): array => themesCall( $fonts, 'themes/list' )[1]['active'];
check( 'M. a font that is there when the stylesheet is built is in it, and the stylesheet is as it should be: it is not stale, no font is back', $activeOf()['stale'] === false && $activeOf()['back'] === [] && str_contains( (string) file_get_contents( ninoSandboxDir( $fonts ). '/private/assets/theme.css' ), '@font-face' ) === true );

rename( $fontsFolder, $fontsFolder. '.gone' );
unset( $fonts['./nino/themes/read'] );
themesCall( $fonts, 'themes/save', [ 'theme' => 'own:typed', 'mix' => themesMix( 'Typed', [] ), 'css' => '' ] );
$gone = json_decode( (string) file_get_contents( $fontState. '/state/active.json' ), true );
check( 'M. a font whose folder is gone when the stylesheet is built is the stack of the system in it, and the state says which fonts were gone: by the key of the brand and the folder', $gone['gone'] === [ 'font-title' => 'open-sans' ] && str_contains( (string) file_get_contents( ninoSandboxDir( $fonts ). '/private/assets/theme.css' ), '@font-face' ) === false && $activeOf()['stale'] === false && $activeOf()['back'] === [] );

rename( $fontsFolder. '.gone', $fontsFolder );
$list = themesCall( $fonts, 'themes/list' )[1];
check( 'M. a font that comes back is not in the stylesheet before the next build: the API says so - the active theme is stale and names the font - and the stylesheet is what it was', $list['active']['stale'] === true && $list['active']['back'] === [ 'font-title' => 'open-sans' ] && str_contains( (string) file_get_contents( ninoSandboxDir( $fonts ). '/private/assets/theme.css' ), '@font-face' ) === false );
check( 'M. ...and every answer that says the state says it: open, save, apply and the list', themesCall( $fonts, 'themes/open', [ 'theme' => 'own:typed' ] )[1]['active']['back'] === [ 'font-title' => 'open-sans' ] );

[ $status ] = themesCall( $fonts, 'themes/save', [ 'theme' => 'own:typed', 'mix' => themesMix( 'Typed', [] ) ] );
check( 'M. a save with nothing changed builds the stylesheet again - the font is in it, the state no longer says it was gone, and the API says that the stylesheet is as it should be', $status === 200 && str_contains( (string) file_get_contents( ninoSandboxDir( $fonts ). '/private/assets/theme.css' ), '@font-face' ) === true && $activeOf()['stale'] === false && $activeOf()['back'] === []
	&& ( json_decode( (string) file_get_contents( $fontState. '/state/active.json' ), true )['gone'] ?? null ) === [] );

// a stylesheet that is stale for another reason is stale all the same
$active = json_decode( (string) file_get_contents( $fontState. '/state/active.json' ), true );
$active['register'] = 'another register';
file_put_contents( $fontState. '/state/active.json', json_encode( $active ) );
check( 'M. a stylesheet that was built for another version of the register is stale, whether or not a font is back, and a save with nothing changed builds it again', $activeOf()['stale'] === true && $activeOf()['back'] === [] && themesCall( $fonts, 'themes/save', [ 'theme' => 'own:typed', 'mix' => themesMix( 'Typed', [] ) ] )[0] === 200 && $activeOf()['stale'] === false );

// -- a font that a hand wrote into brand.json: a slug, and not longer than a name of a folder is
$write = static fn( int $length ): array => \Nino\Modules\Themes\Brand::read( $fonts, [ 'font-title' => [ 'own' => true, 'value' => str_repeat( 'a', $length ) ] ] )['font-title'];
check( 'M. a font that is named in brand.json and has no folder stays what the project chose - up to the longest slug a folder has - and a longer one is no value of the project: it is the theme\'s, and is not written into a stylesheet', $write( 60 ) === [ 'own' => true, 'value' => str_repeat( 'a', 60 ) ] && $write( 61 ) === [ 'own' => false ] && $write( 500 ) === [ 'own' => false ] );
check( 'M. a theme that suggests a font names a slug of the same length, no longer', is_array( \Nino\Modules\Themes\Brand::suggestion( [ 'font-title' => str_repeat( 'a', 60 ) ] ) ) === true && \Nino\Modules\Themes\Brand::suggestion( [ 'font-title' => str_repeat( 'a', 61 ) ] ) === 'font-title' );


// -- a stylesheet that cannot be replaced is found out before anything is written: the state stays what it was, and the answer is true to it
$lock = themesSite();
ninoUser( $lock, 'designer@example.com' );
$lockState = ninoSandboxDir( $lock ). '/private/data/theme';
$sheet = ninoSandboxDir( $lock ). '/private/assets/theme.css';
$owned = themesMix( 'Locked', [ 'section' => [ 'from' => 'craft-probe' ] ], '.first { color: red; }' );
themesCall( $lock, 'themes/save-as', [ 'mix' => $owned ] );
themesCall( $lock, 'themes/apply', [ 'theme' => 'own:locked' ] );
themesCall( $lock, 'themes/apply', [ 'theme' => 'nino' ] );
themesCall( $lock, 'themes/apply', [ 'theme' => 'own:locked' ] );
// What the web server is to have of a project: the files it keeps are its own, and the directory of the stylesheet is the one thing that is not
exec( 'chmod -R a+rwX '. escapeshellarg( ninoSandboxDir( $lock ). '/private' ) );
$switched = function_exists( 'posix_geteuid' ) === true && posix_geteuid() === 0 && function_exists( 'posix_seteuid' ) === true && posix_seteuid( 65534 ) === true;
// Who runs this cannot read the file: the user it switched to, or the owner of the file who is not root - the rights of a file hold for its owner too, and only root reads past them
$server = $switched === true || ( function_exists( 'posix_geteuid' ) === true && posix_geteuid() !== 0 && is_readable( $sheet ) === false );
$suffix = $switched === true ? '' : ' (run as the owner of the directory: it can write in it, so only the answer is looked at)';

if( $switched === true )
	posix_seteuid( 0 );

chmod( dirname( $sheet ), 0555 );
$ownBefore = (string) file_get_contents( $lockState. '/state/own/locked.json' );
$sheetBefore = (string) file_get_contents( $sheet );
$stateBefore = \Nino\Modules\Themes\Store::stamp( $lock );
$lastBefore = is_dir( $lockState. '/previous' ) === true ? \Nino\Modules\Themes\Store::stamp( $lock ). 'x' : '';
$previousBytes = (string) file_get_contents( $lockState. '/previous/active.json' );

if( $switched === true )
	posix_seteuid( 65534 );

$lockedSave = themesCall( $lock, 'themes/save', [ 'theme' => 'own:locked', 'mix' => themesMix( 'Locked', [ 'section' => [ 'from' => 'craft-probe' ] ], '.second { color: blue; }' ) ] );
$lockedRestore = themesCall( $lock, 'themes/restore' );
$lockedApply = themesCall( $lock, 'themes/apply', [ 'theme' => 'nino' ] );
$lockedCopy = themesCall( $lock, 'themes/duplicate', [ 'theme' => 'own:locked', 'name' => 'Locked copy' ] );

if( $switched === true )
	posix_seteuid( 0 );

chmod( dirname( $sheet ), 0755 );
$nothing = ( static fn(): bool => (string) file_get_contents( $lockState. '/state/own/locked.json' ) === $ownBefore && (string) file_get_contents( $sheet ) === $sheetBefore && \Nino\Modules\Themes\Store::stamp( $lock ) === $stateBefore
	&& (string) file_get_contents( $lockState. '/previous/active.json' ) === $previousBytes && array_filter( [ 'next', 'gone', 'swap', 'building' ], static fn( string $left ): bool => file_exists( $lockState. '/'. $left ) ) === [] )();
check( 'M. a stylesheet that cannot be put in place - the directory is not the web server\'s - is found out in front of the change: a save, a restore, an apply and a copy are 409 themes_locked, and nothing was written: not the own theme, not the state, not the last state, not the stylesheet'. $suffix,
	$switched === false || ( $lockedSave[0] === 409 && $lockedSave[1]['code'] === 'themes_locked' && $lockedRestore[0] === 409 && $lockedRestore[1]['code'] === 'themes_locked' && $lockedApply[0] === 409 && $lockedApply[1]['code'] === 'themes_locked' && $lockedCopy[0] === 409 && $nothing === true ) );

// where there is nothing to put in place the directory is no matter: a state that leaves the stylesheet as it is changes
$ownBefore = (string) file_get_contents( $lockState. '/state/own/locked.json' );
chmod( dirname( $sheet ), 0555 );

if( $switched === true )
	posix_seteuid( 65534 );

$renamed = themesCall( $lock, 'themes/duplicate', [ 'theme' => 'nino', 'name' => 'Nino copy' ] );

if( $switched === true )
	posix_seteuid( 0 );

chmod( dirname( $sheet ), 0755 );
check( 'M. ...and a change that has the same stylesheet to put in place is a change that is asked of the directory too, as the old one was - a copy is no exception: the state it would make has the same stylesheet and the file is written again'. $suffix, $switched === false || $renamed[0] === 409 );

// the directory that is not there yet is made by the one who writes: where its parent is the web server's, the change is made
exec( 'rm -rf '. escapeshellarg( dirname( $sheet ) ) );
[ $made ] = themesCall( $lock, 'themes/apply', [ 'theme' => 'own:locked' ] );
check( 'M. a directory for the stylesheet that is not there is no obstacle where the one above it can be written in: the change is made, and the stylesheet is there', $made === 200 && is_file( $sheet ) === true && \Nino\Modules\Themes\Compiler::isOwn( (string) file_get_contents( $sheet ) ) === true );

// -- the file that is put in place has the rights the server's own umask gives, and no more
$mask = umask( 0027 );
file_put_contents( $sheet, ".mine { }\n" );
exec( 'chmod -R a+rwX '. escapeshellarg( ninoSandboxDir( $lock ). '/private' ) );
chmod( $sheet, 0 );

if( $switched === true )
	posix_seteuid( 65534 );

[ $forced ] = themesCall( $lock, 'themes/apply', [ 'theme' => 'nino', 'force' => true ] );
$mode = is_file( $sheet ) === true ? ( @fileperms( $sheet ) & 0777 ) : -1;

if( $switched === true )
	posix_seteuid( 0 );

umask( 0022 );
chmod( $sheet, 0 );

if( $switched === true )
	posix_seteuid( 65534 );

[ $forcedAgain ] = themesCall( $lock, 'themes/apply', [ 'theme' => 'nino', 'force' => true ] );
$modeAgain = is_file( $sheet ) === true ? ( @fileperms( $sheet ) & 0777 ) : -1;

if( $switched === true )
	posix_seteuid( 0 );

umask( $mask );
check( 'M. a stylesheet that could not be read and is replaced is opened for reading by the umask of the server and not by a mode of the code: 0640 where the umask is 0027, 0644 where it is 0022 - never wider than the server says'. $suffix,
	$switched === false || ( $forced === 200 && $mode === 0640 && $forcedAgain === 200 && $modeAgain === 0644 ), sprintf( '%o %o', $mode, $modeAgain ) );


// -- unfinished css is what a person who types has: it is closed where the compiler writes it, and says so, and the bundle has nothing to say of it
$css = themesSite();
ninoUser( $css, 'designer@example.com' );
$cases = [
	'a block' => [ '.a { color: red', 'open-block', ".a { color: red\n}" ],
	'two blocks' => [ '.a { .b { color: red', 'open-block', ".a { .b { color: red\n}}" ],
	'a } too many' => [ '.a { color: red }}', 'stray-close', '.a { color: red }' ],
	'a comment' => [ '.a { } /* open', 'open-comment', '.a { } /* open*/' ],
	'a string' => [ '.a { content: "open', [ 'open-string', 'open-block' ], ".a { content: \"open\"\n}" ],
	'a string with a line break' => [ ".a { content: \"open\n}", null, null ],
	'a url' => [ '.a { background: url(open', [ 'open-url', 'open-block' ], ".a { background: url(open)\n}" ],
	'an escaped quote' => [ '.a { content: "a\"b" }', null, '.a { content: "a\"b" }' ],
	'a quote in a comment' => [ '/* it\'s */ .a { }', null, '/* it\'s */ .a { }' ],
	'braces in a string' => [ '.a { content: "}{" }', null, '.a { content: "}{" }' ],
	'braces in a url' => [ '.a { background: url(a}b{c) }', null, '.a { background: url(a}b{c) }' ],
	'closed css' => [ '.a { color: red } @media (min-width: 640px) { .b { color: blue } }', null, '.a { color: red } @media (min-width: 640px) { .b { color: blue } }' ],
];
$wrong = [];

foreach( $cases as $what => [ $source, $fault, $made ] ) {

	[ $got, $faults ] = \Nino\Modules\Themes\Compiler::balance( $source );

	if( $fault === null ? ( $faults !== [] && $made !== null ) || ( $made !== null && $got !== $made ) : ( $faults !== (array) $fault || $got !== $made ) )
		$wrong[] = $what;
}

check( 'M. the own css is closed where it is left open - a block, a comment, a string, a url( - and a } that closes nothing is taken out; css that is closed is returned as it is, whatever braces stand in a string, a comment or a url'. ( $wrong === [] ? '' : ' - '. implode( ', ', $wrong ) ), $wrong === [] );

// what the bundle of the kernel makes of it is what the compiler makes: the bundle finds nothing to repair
ninoWarnings();
$broken = [];

foreach( $cases as $what => [ $source ] ) {

	$mix = themesMix( 'Broken', [], $source );
	$built = \Nino\Modules\Themes\Compiler::compile( $css, $mix, \Nino\Modules\Themes\Store::read( $css )['brand'] );
	\Nino\Filesystem::putFileContent( $css, '/assets/theme.css', $built['css'] );
	ninoWarnings();
	$again = themesReboot( $css, [ '\\Nino\\Modules\\Assets', '\\Nino\\Modules\\Basis', '\\Nino\\Modules\\Themes' ] );
	\Nino\Modules\Assets::stylesheet( $again, THEMES_BUNDLE );

	if( ninoWarnings() !== [] )
		$broken[] = $what;
}

check( 'M. the bundle has nothing to repair in what the compiler writes - no warning for any of the cases, which the kernel would put in the log at every build'. ( $broken === [] ? '' : ' - '. implode( ', ', $broken ) ), $broken === [] );

// every state a person passes through while typing: of every prefix of every text the kernel finds nothing to repair - asked of the bundle's own check
$corpus = [
	".hero { background: url(\"img/a b.png\") no-repeat; }\n.x::before { content: \"\\201C\"; }",
	"@media (min-width: 40em) { .a { padding: calc(1rem + 2px); } }\n/* Ende */",
	".a { background: url(data:image/svg+xml;utf8,%3Csvg%3E) }",
	".b { background: url(C:\\bilder\\x.png) }",
	".c\\:hover { color: red } .d { content: '\\'' }",
	"@import url(fonts.css);\n@font-face { font-family: X; src: url('x.woff2') }",
	"a[href$=\".pdf\"]::after { content: \" (PDF)\" }",
	"body { background: url(x\\",
	".a { content: \"x\\\\\" } /* a \\ */ .b { color: red }",
	".a { b: \"\\\n\" } }} .c { d: url( \"e\" ) }",
];
$layer = new \ReflectionMethod( \Nino\Modules\Assets::class, '_layer' );
$checked = 0;
$repaired = [];
ninoWarnings();

foreach( $corpus as $text )
	for( $n = 1; $n <= strlen( $text ); $n++ ) {

		if( trim( substr( $text, 0, $n ) ) === '' )
			continue;

		$checked++;
		$imports = '';
		$layer->invokeArgs( null, [ "/* x */\n". \Nino\Modules\Themes\Compiler::balance( trim( substr( $text, 0, $n ) ) )[0]. "\n", 'nino.theme', '/assets/theme.css', &$imports ] );

		if( ninoWarnings() !== [] )
			$repaired[] = substr( $text, 0, $n );
	}

check( 'M. of every state that is passed through while a css is typed - '. $checked. ' prefixes of '. count( $corpus ). ' texts, a url( that ends in a backslash among them - the kernel finds nothing to repair in what the compiler makes of it: the check is the bundle\'s own, and not a copy of its rules'. ( $repaired === [] ? '' : ' - '. json_encode( array_slice( $repaired, 0, 3 ) ) ), $checked > 400 && $repaired === [] );

[ $status, $body ] = themesCall( $css, 'themes/preview', [ 'mix' => [ 'parts' => [], 'css' => '.a { color: red' ] ] );
[ , $closed ] = themesCall( $css, 'themes/preview', [ 'mix' => [ 'parts' => [], 'css' => '.a { color: red }' ] ] );
check( 'M. a preview of unfinished css says what is open, in codes, and is a draft as any other - the token, the query - and closed css says none', $status === 200 && $body['cssFaults'] === [ 'open-block' ] && preg_match( \Nino\Preview::TOKEN, $body['token'] ) === 1 && $closed['cssFaults'] === [] );
$site = [ 'css' => $css ];
$cacheDir = ninoSandboxDir( $css ). '/private/data/.preview/themes/'. $body['token'];
$draftCss = (string) json_decode( (string) file_get_contents( $cacheDir. '/draft.json' ), true )['css'];
check( 'M. ...and the draft is closed, so the page of the preview draws and logs nothing', str_ends_with( rtrim( $draftCss ), "}\n}" ) === true || str_contains( $draftCss, ".a { color: red\n}" ) );
ninoWarnings();
ninoVisit( $css, '/?'. $body['query'] );
$page = \Nino\Html::renderHtml( $css, '[assets '. THEMES_BUNDLE. ']' );
check( 'M. the page asked with the token of that draft is drawn - the style element of the draft - and the request raised no warning', str_contains( $page, '<style data-nino-theme-preview>' ) === true && ninoWarnings() === [] );

// saved: the own css is written closed, the website draws, and nothing is said at the next build
themesCall( $css, 'themes/save-as', [ 'mix' => themesMix( 'Open', [], '.saved { color: red' ) ] );
ninoWarnings();
[ $applied ] = themesCall( $css, 'themes/apply', [ 'theme' => 'own:open' ] );
$later = themesReboot( $css, [ '\\Nino\\Modules\\Assets', '\\Nino\\Modules\\Basis', '\\Nino\\Modules\\Themes' ] );
$bundle = \Nino\Modules\Assets::stylesheet( $later, THEMES_BUNDLE );
check( 'M. a saved own css that does not close is applied, and the bundle of the website has it closed and says nothing - a visitor is shown the page', $applied === 200 && str_contains( $bundle, '.saved { color: red' ) === true && ninoWarnings() === [] && str_contains( (string) file_get_contents( ninoSandboxDir( $css ). '/private/assets/theme.css' ), ".saved { color: red\n}" ) === true );
check( 'M. ...and what was saved is what was written: the own theme keeps the text as it was typed', json_decode( (string) file_get_contents( ninoSandboxDir( $css ). '/private/data/theme/state/own/open.json' ), true )['css'] === '.saved { color: red' );

})();

// ---------------------------------------------------------------------------------------------------------------------------------
echo "\nN. Blocks of the themes\n";

$blocked = themesSite();
$list = \Nino\Blocks::all( $blocked );
check( 'N. the blocks of the themes that are installed are listed by the kernel, each with the key of its theme as its origin - none of the themes is on the website, and the one of the feature and the one of a fixture are there alike', ( $list['hero-band']['origin'] ?? '' ) === 'nino' && ( $list['hero-soft']['origin'] ?? '' ) === 'craft-probe'
	&& ( $list['hero-band']['category'] ?? '' ) === 'intro' && \Nino\Blocks::rejected( $blocked ) === [] );
check( 'N. ...and with the name of its theme as the label of where it comes from, which a panel shows', ( $list['hero-band']['label'] ?? null ) === \Nino\Modules\Themes\Reader::read( $blocked )['themes']['nino']['name'] && ( $list['hero-band']['label'] ?? null ) !== ( $list['hero-band']['origin'] ?? null ) );
check( 'N. each is a block of the kernel as any other: it has its texts in both languages and names the variant of its theme, which the mix that is on may not have - the root of the template carries the variant', array_keys( (array) ( \Nino\Blocks::get( $blocked, 'hero-band' )['texts'] ?? [] ) ) === [ 'de_DE', 'en_US' ] && ( \Nino\Blocks::get( $blocked, 'hero-soft' )['variants'] ?? null ) === [ 'section-wrap' => [ 'soft' ] ]
	&& ( \Nino\Blocks::get( $blocked, 'hero-band' )['variants'] ?? null ) === [ 'section-wrap' => [ 'band' ] ] );

// A block of a theme that is not a block - a folder that holds code - is left out with its reason, and the others are not touched
$folder = ninoSandboxDir( $blocked ). '/themes/band-probe/blocks/broken';
mkdir( $folder. '/text', 0777, true );
file_put_contents( $folder. '/block.json', '{ "name": ' );
file_put_contents( $folder. '/block.tpl', '[section-wrap id="broken"][/section-wrap]' );
file_put_contents( $folder. '/text/en_US.json', '{}' );
file_put_contents( $folder. '/text/evil.php', '<?php' );
$again = themesReboot( $blocked, [ '\\Nino\\Modules\\Assets', '\\Nino\\Modules\\Basis', '\\Nino\\Modules\\Themes' ] );
ninoWarnings();
check( 'N. a block of a theme that fails the check of the kernel is not listed and is named with the theme it came from, and the blocks of the other themes are listed as they were', isset( \Nino\Blocks::all( $again )['broken'] ) === false && isset( \Nino\Blocks::rejected( $again )['band-probe/broken'] ) === true
	&& isset( \Nino\Blocks::all( $again )['hero-band'], \Nino\Blocks::all( $again )['hero-soft'] ) === true );
$off = themesSite( false );
check( 'N. with the feature off there is no root of a theme: the blocks of the themes are not listed', isset( \Nino\Blocks::all( $off )['hero-band'] ) === false && isset( \Nino\Blocks::all( $off )['hero-soft'] ) === false );

// O. a theme of a package of the catalogue: the folder of \Nino\Packages is a root of the themes, and the website never follows a change of it
$packages = themesSite();
ninoUser( $packages, 'designer@example.com' );
$modules = [ '\\Nino\\Modules\\Assets', '\\Nino\\Modules\\Basis', '\\Nino\\Modules\\Themes' ];

// What the next request sees: the files stay, the website is drawn from its bundle, the panel lists the themes
$bundle = static fn( array $project ): string => \Nino\Modules\Assets::stylesheet( themesReboot( $project, $modules ), THEMES_BUNDLE );
$listed = static function( array $project ) use ( $modules ): array {

	$next = themesReboot( $project, $modules );

	return array_column( themesCall( $next, 'themes/list' )[1]['themes'], null, 'key' );
};

// craft-probe comes as a package and not through the hook: the folder of the sandbox keeps band-probe alone
\Nino\Filesystem::removeDir( ninoSandboxDir( $packages ). '/themes/craft-probe' );
$craftFiles = [];

foreach( new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $fixtures. '/craft-probe', FilesystemIterator::SKIP_DOTS ) ) as $file )
	$craftFiles[str_replace( '\\', '/', substr( $file->getPathname(), strlen( $fixtures. '/craft-probe/' ) ) )] = (string) file_get_contents( $file->getPathname() );

ksort( $craftFiles );
check( 'O. before the package is placed no theme has the key craft-probe, in the list and in the register of the blocks', isset( $listed( $packages )['craft-probe'] ) === false && isset( \Nino\Blocks::all( $packages )['hero-soft'] ) === false );

// this request has read the themes already: what it read is what the hook of the blocks must not trust after the kernel changed the folder
\Nino\Modules\Themes\Reader::read( $packages );
check( 'O. the kernel places the theme package under private/packages/themes/', \Nino\Packages::place( $packages, \Nino\Packages::THEME, 'craft-probe', $craftFiles ) === true && is_file( ninoSandboxDir( $packages ). '/private/packages/themes/craft-probe/theme.json' ) === true );
$list = $listed( $packages );
check( 'O. the next request lists the package as it lists a theme that comes with the feature: key, version, name and a part for the page and each group', $list['craft-probe']['version'] === '1.0.0' && $list['craft-probe']['kind'] === 'theme' && $list['craft-probe']['name']['en_US'] === 'Craft (probe)'
	&& array_keys( $list['craft-probe']['parts'] ) === [ 'button', 'footer', 'header', 'image', 'page', 'section', 'text' ] );
check( 'O. the blocks of the package are in the register of the blocks the request that placed it reads, by the name of the theme, and the blocks of the other themes are as they were', ( \Nino\Blocks::all( $packages )['hero-soft']['origin'] ?? '' ) === 'craft-probe'
	&& ( \Nino\Blocks::all( $packages )['hero-soft']['label']['en_US'] ?? '' ) === 'Craft (probe)' && isset( \Nino\Blocks::all( $packages )['hero-band'] ) === true );

[ $status, $body ] = themesCall( $packages, 'themes/apply', [ 'theme' => 'craft-probe' ] );
$drawn = $bundle( $packages );
check( 'O. a theme of a package is applied and the website draws it: its part of the section is in the stylesheet', $status === 200 && $body['active']['theme'] === 'craft-probe' && str_contains( $drawn, '/* section: the part of craft-probe 1.0.0 */' ) === true );

// version 1.1.0 at the place of the package: another ladder for the section
$newer = $craftFiles;
$newer['theme.json'] = str_replace( '"version": "1.0.0"', '"version": "1.1.0"', $newer['theme.json'] );
$newer['parts/section/part.json'] = str_replace( '"0.6rem"', '"0.75rem"', $newer['parts/section/part.json'] );
check( 'O. the kernel replaces the package by a version 1.1.0 with another ladder', $newer['theme.json'] !== $craftFiles['theme.json'] && $newer['parts/section/part.json'] !== $craftFiles['parts/section/part.json']
	&& \Nino\Packages::place( $packages, \Nino\Packages::THEME, 'craft-probe', $newer ) === true );
check( 'O. the panel says the version that is installed', $listed( $packages )['craft-probe']['version'] === '1.1.0' );
check( 'O. after a new version of the package the website draws byte for byte what it drew, from the copy of the version it was applied at', $bundle( $packages ) === $drawn && is_dir( ninoSandboxDir( $packages ). '/private/data/theme/copies/craft-probe@1.0.0' ) === true );

// an own theme with the part of the section of the package, to see where the part comes from when the package is gone
[ $status, $body ] = themesCall( $packages, 'themes/save-as', [ 'mix' => themesMix( 'Lindner', [ 'section' => [ 'from' => 'craft-probe@1.0.0' ] ] ) ] );
check( 'O. an own theme takes the part of the section of the package', $status === 200 && $body['theme'] === 'own:lindner' );

// the package is taken away
\Nino\Modules\Themes\Reader::read( $packages );
check( 'O. the kernel takes the package away: the register of the blocks of the request that did it has no block of it, and the next request lists no theme of its key', \Nino\Packages::remove( $packages, \Nino\Packages::THEME, 'craft-probe' ) === true
	&& isset( \Nino\Blocks::all( $packages )['hero-soft'] ) === false && isset( $listed( $packages )['craft-probe'] ) === false );
check( 'O. with the package gone the website draws byte for byte what it drew', $bundle( $packages ) === $drawn );
$next = themesReboot( $packages, $modules );
[ $status, $body ] = themesCall( $next, 'themes/open', [ 'theme' => 'own:lindner' ] );
check( 'O. and the panel says "package missing" at the parts that come from it, which are the copies of the project', $status === 200 && $body['groups']['section']['package'] === 'missing' && $body['groups']['section']['origin'] === 'copy' );

// a package that is put in the folder by hand and that the kernel would have refused: a part that loads another file, a block with a file of code
$hand = ninoSandboxDir( $packages ). '/private/packages/themes/handmade';
\Nino\Filesystem::copyDir( $fixtures. '/craft-probe', $hand );
file_put_contents( $hand. '/theme.json', str_replace( '"key": "craft-probe"', '"key": "handmade"', $craftFiles['theme.json'] ) );
file_put_contents( $hand. '/parts/section/part.css', "@import url( 'other.css' );\n". $craftFiles['parts/section/part.css'] );
file_put_contents( $hand. '/blocks/hero-soft/text/evil.php', '<?php' );
$next = themesReboot( $packages, $modules );
$read = \Nino\Modules\Themes\Reader::read( $next );
check( 'O. a package by hand that the kernel would have refused disables nothing: its part rests with the reason, its other parts and the other themes are read as they were, and its block is left out while the blocks of the other themes are listed', isset( $read['themes']['handmade'] ) === true
	&& $read['themes']['handmade']['parts']['section']['ok'] === false && $read['themes']['handmade']['parts']['section']['problem'] !== '' && $read['themes']['handmade']['parts']['button']['ok'] === true && isset( $read['themes']['band-probe'], $read['themes']['nino'] ) === true
	&& isset( \Nino\Blocks::all( $next )['hero-soft'] ) === false && isset( \Nino\Blocks::rejected( $next )['handmade/hero-soft'] ) === true && isset( \Nino\Blocks::all( $next )['hero-band'] ) === true );
\Nino\Filesystem::removeDir( $hand );

// a package with the key of the theme that comes with the feature
$shipped = \Nino\Modules\Themes\Reader::read( $packages )['themes']['nino'];
\Nino\Filesystem::copyDir( $fixtures. '/craft-probe', ninoSandboxDir( $packages ). '/private/packages/themes/nino' );
file_put_contents( ninoSandboxDir( $packages ). '/private/packages/themes/nino/theme.json', str_replace( '"key": "craft-probe"', '"key": "nino"', $craftFiles['theme.json'] ) );
$next = themesReboot( $packages, $modules );
$read = \Nino\Modules\Themes\Reader::read( $next );
check( 'O. a package with the key of the theme that comes with the feature is not read - the one that comes with it stays - and is said once, in what was refused', $read['themes']['nino']['dir'] === $shipped['dir'] && $read['themes']['nino']['version'] === $shipped['version']
	&& count( array_filter( array_keys( $read['rejected'] ), static fn( string $folder ): bool => basename( $folder ) === 'nino' ) ) === 1 );

// the script, run over the stand-in of a page, where node is on the path
$node = function_exists( 'shell_exec' ) === true ? trim( (string) @shell_exec( 'command -v node 2>/dev/null' ) ) : '';

if( $node === '' || function_exists( 'exec' ) === false )
	echo "  --  - node is not available here: themes-panel-js-smoke.js was NOT run\n";
else {

	$output = [];
	$exit = 1;

	exec( 'NINO_ROOT='. escapeshellarg( $root ). ' '. escapeshellarg( $node ). ' '. escapeshellarg( __DIR__. '/themes-panel-js-smoke.js' ). ' 2>&1', $output, $exit );
	check( 'K. themes-panel-js-smoke.js passes - '. ( $output === [] ? 'no output' : end( $output ) ), $exit === 0 );
}

// The links a probe leaves in a sandbox are taken away first: removeDir() does not follow a link, and a folder with a link in it stays
foreach( $sandboxes as $sandbox ) {

	if( is_dir( $sandbox ) === false )
		continue;

	foreach( new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $sandbox, FilesystemIterator::SKIP_DOTS ), RecursiveIteratorIterator::CHILD_FIRST ) as $entry )
		if( $entry->isLink() === true )
			unlink( $entry->getPathname() );

	\Nino\Filesystem::removeDir( $sandbox );
}

ninoDone( $appData );
