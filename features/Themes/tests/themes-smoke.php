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
 *											K. the panel: its files, its words, what the API gives a screen - the pages, what an own theme is made of, the label of a
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
	if( in_array( $action, [ 'themes/save', 'themes/delete' ], true ) === true && array_key_exists( 'base', $data ) === false && str_starts_with( (string) ( $data['theme'] ?? '' ), 'own:' ) === true )
		$data['base'] = (string) ( ninoCall( $appData, [ \Nino\Admin\Admin::class, 'handlePost' ], [ 'theme' => $data['theme'] ], 'themes/open' )[1]['stamp'] ?? '' );

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
check( 'the API is a panel of the workbench, with the right /_admin/themes/manage, and its eight actions are the ones the workbench dispatches', isset( $panels['themes'] ) === true && $panels['themes']['class'] === \Nino\Modules\Themes\Admin::class
	&& \Nino\Modules\Themes\Admin::perm() === '/_admin/themes/manage' && array_keys( \Nino\Modules\Themes\Admin::actions() ) === [ 'themes/list', 'themes/open', 'themes/preview', 'themes/save', 'themes/save-as', 'themes/apply', 'themes/restore', 'themes/delete' ]
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

$pairs = static fn( string $color, string $tone ): array => array_map( static fn( float $ratio ): float => round( $ratio, 2 ), Palette::pairs( Palette::make( $color, '#2b2622', $tone ), $color ) );
check( 'the palette of the blue of Nino.css, neutral: the eight pairs and the contrast of each pair a part reads, as worked out', $pairs( '#1c5db8', 'neutral' ) === [
	'light ink/base' => 16.49, 'light quiet/base' => 6.01, 'light accent/base' => 6.35, 'light ink/alt' => 14.95, 'light quiet/alt' => 5.45, 'light accent/alt' => 5.76, 'light ink/tint' => 14.22, 'light quiet/tint' => 5.19, 'light accent/tint' => 5.48, 'light button' => 6.35,
	'dark ink/base' => 15.0, 'dark quiet/base' => 8.28, 'dark accent/base' => 8.32, 'dark ink/alt' => 13.75, 'dark quiet/alt' => 7.59, 'dark accent/alt' => 7.63, 'dark ink/tint' => 12.71, 'dark quiet/tint' => 7.01, 'dark accent/tint' => 7.05,
	'dark ink/picture' => 8.44, 'dark quiet/picture' => 4.66, 'dark accent/picture' => 4.68, 'dark button' => 9.48, 'primary' => 6.35 ] );
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

		foreach( Palette::pairs( Palette::make( $colour, '#123456', $tone ), $colour ) as $pair => $ratio )
			$lowest[$pair] = min( $lowest[$pair] ?? INF, $ratio );
	}

$numbers['palette'] = ( hrtime( true ) - $started ) / 1e6 / $made;
check( 'over '. $made. ' palettes - '. count( $colours ). ' colours, three tones - the ink holds 7 : 1 on every ground, the quiet ink and the accent 4.5 : 1, in both modes and in the dark one on the picture under the veil',
	$made === 3 * count( $colours ) && count( $lowest ) === 24 && array_filter( $lowest, static fn( float $ratio, string $pair ): bool => $ratio < ( str_contains( $pair, ' ink/' ) === true ? 7.0 : 4.5 ) && $pair !== 'primary', ARRAY_FILTER_USE_BOTH ) === [] );
check( 'the colour written on a colour is the better of white and black, and never under the square root of 21', $lowest['primary'] >= 4.58 && $lowest['light button'] >= 4.5 && Palette::on( '#ffffff' ) === '#000000' && Palette::on( '#000000' ) === '#ffffff' && Palette::on( '#777777' ) === '#000000' );
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
check( 'Nr. 1: the variants a part adds and the one that stands where a page names none: craft - the stacked header and the soft section -, band - the bar and the band, the regular one standing', $craft['variants'] === [ 'header-wrap' => [ [ 'stacked' ], 'stacked' ], 'section-wrap' => [ [ 'soft' ], 'soft' ] ]
	&& Compiler::compile( $site, $bandMix, $none )['variants'] === [ 'header-wrap' => [ [ 'bar' ], 'regular' ], 'section-wrap' => [ [ 'band' ], 'regular' ] ] );
check( 'a mix chooses the standard variant among those the part has, and a variant it does not have is not kept', Compiler::compile( $site, Mix::clean( $site, [ 'parts' => [ 'section' => [ 'from' => 'craft-probe', 'standard' => [ 'section-wrap' => 'regular' ] ] ] ], false ), $none )['variants'] === [ 'section-wrap' => [ [ 'soft' ], 'regular' ] ]
	&& Mix::clean( $site, [ 'parts' => [ 'section' => [ 'from' => 'craft-probe', 'standard' => [ 'section-wrap' => 'band', 'section-col' => 'card' ] ] ] ], false )['parts']['section']['standard'] === [ 'section-col' => 'card' ] );

check( 'a standard variant that is one the schema names - the card of a column - is told as it is, with no variant added', Compiler::compile( $site, Mix::clean( $site, [ 'parts' => [ 'section' => [ 'from' => 'band-probe', 'standard' => [ 'section-col' => 'card' ] ] ] ], false ), Brand::clean( $site, [] ) )['variants']['section-col'] === [ [], 'card' ] );
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
check( 'the kernel says a source it repaired in a warning, and says it of the theme and of nothing else', count( array_filter( $ignored, static fn( string $warning ): bool => str_contains( $warning, 'theme.css' ) === true ) ) >= 5 && array_filter( $ignored, static fn( string $warning ): bool => str_contains( $warning, 'theme.css' ) === false && str_contains( $warning, 'Assets' ) === true ) === [] );
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
	&& array_column( $section['offers'], 'theme' ) === [ 'band-probe', 'craft-probe', 'nino' ] && $body['brand']['suggested']['tone'] === 'warm' && $body['brand']['effective']['font-title'] === 'open-sans' && count( $body['palette']['contrast'] ) === 24 && min( $body['palette']['contrast'] ) >= 4.5
	&& array_column( $body['groups']['page']['knobs'], 'key' ) === [ 'size', 'width', 'space', 'radius', 'line', 'motion', 'density' ] && str_contains( json_encode( $body ), ninoSandboxDir( $site ) ) === false && $body['notes'] === [] );

mkdir( ninoSandboxDir( $site ). '/themes/broken-one' );
file_put_contents( ninoSandboxDir( $site ). '/themes/broken-one/theme.json', '{ nope' );
unset( $site['./nino/themes/read'] );
[ $status, $body ] = themesCall( $site, 'themes/list' );
check( 'a theme that is refused is in the list with the name of its folder and the reason, and no path of this machine', $status === 200 && count( $body['rejected'] ) === 1 && $body['rejected'][0]['folder'] === 'broken-one' && trim( $body['rejected'][0]['reason'] ) !== '' && str_contains( json_encode( $body ), ninoSandboxDir( $site ) ) === false );
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
	$body['palette']['source'] === 'brand' && count( $body['palette']['contrast'] ) === 24 && ( $reset = themesCall( $site, 'themes/open', [ 'theme' => 'band-probe' ] )[1]['palette'] )['source'] === 'reset' && $reset['colors']['dark']['alt'] === '#1e2127' && $reset['colors']['dark']['accent'] === '#86b6f6'
	&& $reset['colors']['light']['ink'] === '#1b1d21' && $reset['colors']['light']['accent'] === '#1c5db8' && $reset['colors']['dark']['on'] === '#0f1b2e' && count( $reset['contrast'] ) === 24 && min( $reset['contrast'] ) > 4.0 );

// R2 preview: the draft is described as open describes a theme, so that the panel needs no save to show "part from" and the colours
$partOf = Mix::of( $site, 'craft-probe' );
$partOf['parts']['section'] = [ 'from' => 'band-probe@1.0.0', 'values' => [ 'section-title.x-rule' => 2 ] ];
[ $status, $body ] = themesCall( $site, 'themes/preview', [ 'mix' => $partOf, 'brand' => [ 'color-1' => [ 'own' => true, 'value' => '#00775e' ] ] ] );
$draftSection = $body['groups']['section'];
check( 'R2 preview: the answer holds the groups and the brand of the draft - the part of band for the section, its knob with the step of the draft and its ladder, its variants - and the palette with the contrast of the colour that was posted, from the brand', $status === 200 && preg_match( \Nino\Modules\Themes::TOKEN, $body['token'] ) === 1 && $draftSection['from'] === 'band-probe@1.0.0'
	&& array_values( array_filter( $draftSection['knobs'], static fn( array $knob ): bool => $knob['key'] === 'section-title.x-rule' ) )[0]['step'] === 2 && array_column( array_column( $draftSection['components'], null, 'name' )['section-wrap']['variants'], 'name' ) === [ 'regular', 'band' ]
	&& $body['palette']['source'] === 'brand' && $body['palette']['colors']['light']['accent'] === '#00775e' && count( $body['palette']['contrast'] ) === 24 && $body['brand']['effective']['color-1'] === '#00775e' && in_array( 'color-1', $body['brand']['named'], true ) === true && in_array( 'tone', $body['brand']['named'], true ) === true );

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
	&& ( include $state. '/state/variants.php' ) === [ 'header-wrap' => [ [ 'bar' ], 'regular' ], 'section-wrap' => [ [ 'soft' ], 'soft' ] ] );

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

[ $status, $body ] = themesCall( $site, 'themes/save-as', [ 'mix' => themesMix( 'FREE', [] ) ] );
check( 'a name that makes the key of one that is there is taken, whatever its case: 409 and the name', $status === 409 && $body['code'] === 'themes_name_taken' && $body['params'] === [ 'FREE' ] && $body['field'] === 'name' );
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
$cache = ninoSandboxDir( $site ). '/private/data/theme/.preview';
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
check( 'a preview is a token of 32 hexadecimal digits and the query that asks for it, for a quarter of an hour, and the session that asked holds it', $status === 200 && preg_match( \Nino\Modules\Themes::TOKEN, $token ) === 1 && $body['query'] === 'nino-theme-preview='. $token
	&& $body['expires'] > time() + 800 && $body['expires'] <= time() + \Nino\Modules\Themes::TTL && ( $_SESSION[$site['./nino/uid']]['./themes/preview'] ?? [] ) === [ $token => $body['expires'] ] && $body['notes'] === [] );
check( 'the draft is a stylesheet, the variants and who it is for, in a folder whose name begins with a dot - which a backup does not carry - and the state of the themes was not touched: there is none', is_file( $cache. '/'. $token. '/theme.css' ) === true && Compiler::isOwn( (string) file_get_contents( $cache. '/'. $token. '/theme.css' ) ) === true
	&& ( include $cache. '/'. $token. '/variants.php' ) === [ 'header-wrap' => [ [ 'bar' ], 'regular' ], 'section-wrap' => [ [ 'band' ], 'regular' ] ] && json_decode( (string) file_get_contents( $cache. '/'. $token. '/meta.json' ), true )['user'] === 'designer@example.com'
	&& is_dir( $state. '/state' ) === false && is_dir( $state. '/previous' ) === false && is_dir( $state. '/copies' ) === false && is_file( ninoSandboxDir( $site ). '/private/assets/theme.css' ) === false );

// The kernel empties data/cache after every successful write of the workbench - which this very request is - and a draft must outlast it
$workbench = [ '/nino/http/request' => [ 'method' => 'POST', 'uri' => '/_admin' ], '/nino/http/response' => [ 'statusCode' => 200, 'body' => [ 'token' => $token ], 'header' => [] ] ];
\Nino\Filesystem::putFileContent( $site, '/data/cache/marker.php', [ 'a page' ] );
\Nino\Modules\Cache::callbackOutput( $site, $workbench );
check( 'a draft outlasts the emptying of data/cache that follows every successful write of the workbench, as a page that is kept does not', is_file( ninoSandboxDir( $site ). '/private/data/cache/marker.php' ) === false && is_file( $cache. '/'. $token. '/theme.css' ) === true );

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
$craftBundle = \Nino\Modules\Assets::stylesheet( $site, THEMES_BUNDLE );
$visit( 'x=1' );
$publicBytes = (string) file_get_contents( $publicFile );
$preview = $visit( 'nino-theme-preview='. $token );
$themeBlock = array_values( array_filter( $inside( $preview ), static fn( array $item ): bool => $item[0] === '@layer nino.theme' ) );
check( 'with a theme on the website the draft stands where its stylesheet stands: the layer of the theme holds the draft and nothing of the theme that is on, and a request without the token has the theme that is on', count( $themeBlock ) === 1 && str_contains( $themeBlock[0][1], '.preview-marker' ) === true && str_contains( $themeBlock[0][1], '--section-wrap-space: 1.75rem;' ) === false
	&& str_contains( $craftBundle, '--section-wrap-space: 1.75rem;' ) === true && str_contains( $craftBundle, 'preview-marker' ) === false && str_contains( $visit( 'x=1' ), '<link' ) === true && (string) file_get_contents( $publicFile ) === $publicBytes );
check( 'and the draft\'s variants are the ones told: the band for a page that says band, where craft on the website says soft - and again craft\'s in the request that has no token', ( static function() use ( $visit, &$site, $token ): array {

	$classes = [];
	$visit( 'nino-theme-preview='. $token );
	$classes[] = \Nino\Html::renderHtml( $site, '[section-wrap variant="band"][/section-wrap]' );
	$visit( 'x=1' );
	$classes[] = \Nino\Html::renderHtml( $site, '[section-wrap variant="band"][/section-wrap]' );

	return $classes;
} )() === [ '<section class="section-wrap section-wrap--variant-band"></section>', '<section class="section-wrap section-wrap--variant-soft"></section>' ] );

$hostile = Mix::of( $site, 'band-probe' );
$hostile['css'] = ".hostile::before { content: '</style><script>alert(1)</script>'; }\n}\n.escaped { color: red; }";
$withBrand = themesCall( $site, 'themes/preview', [ 'mix' => $hostile, 'brand' => [ 'color-1' => [ 'own' => true, 'value' => '#00aa55' ] ], 'token' => $token ] );
$html = $visit( 'nino-theme-preview='. $withBrand[1]['token'] );
$repaired = ninoWarnings();
check( 'a draft asks with its brand and its css: the colour is the draft\'s and not the project\'s, the project\'s brand is not changed, and a closing tag in the css does not end the style element', $withBrand[0] === 200 && str_contains( $html, '--brand-color-1: #00aa55;' ) === true && substr_count( $html, '</style>' ) === 1
	&& str_contains( $html, "<\\/style><script>" ) === true && is_file( $state. '/state/brand.json' ) === false );
check( 'a css that is not balanced is repaired in the bundle of the preview as in the public one, and the kernel says so', count( $repaired ) === 1 && str_contains( $repaired[0], 'theme.css' ) === true && str_contains( $repaired[0], 'closes no block' ) === true );
check( 'a token posted again is the same draft rewritten while it lasts', $withBrand[1]['token'] === $token && glob( $cache. '/*', GLOB_ONLYDIR ) !== [] );

// who may have a preview
$tokenOf = static fn(): string => themesCall( $site, 'themes/preview', [ 'mix' => $draft ] )[1]['token'];
$shows = static function( string $query ) use ( $visit ): bool {
	return str_contains( $visit( $query ), '<style data-nino-theme-preview>' ) === true;
};
$token = $tokenOf();
$other = '01234567890123456789012345678901';
check( 'the token is the session\'s: the same request, with it, is the draft', $shows( 'nino-theme-preview='. $token ) === true );

$held = $_SESSION[$site['./nino/uid']]['./themes/preview'];
$_SESSION[$site['./nino/uid']]['./themes/preview'] = [];
check( 'another session - one that holds no token - gets the page as it is, though the token is a real one with its files', $shows( 'nino-theme-preview='. $token ) === false && is_dir( $cache. '/'. $token ) === true );
$_SESSION[$site['./nino/uid']]['./themes/preview'] = [ $token => time() - 1 ];
check( 'a token whose time is out is no token', $shows( 'nino-theme-preview='. $token ) === false );
$_SESSION[$site['./nino/uid']]['./themes/preview'] = $held;
$meta = json_decode( (string) file_get_contents( $cache. '/'. $token. '/meta.json' ), true );
file_put_contents( $cache. '/'. $token. '/meta.json', json_encode( [ 'expires' => time() - 1 ] + $meta ) );
touch( $cache. '/'. $token. '/meta.json', time() - 100 );
check( 'nor is one whose draft says it is out, whatever the session says', $shows( 'nino-theme-preview='. $token ) === false );
file_put_contents( $cache. '/'. $token. '/meta.json', json_encode( $meta ) );
touch( $cache. '/'. $token. '/meta.json', time() - 50 );
check( 'and it is the draft again when the files say it holds', $shows( 'nino-theme-preview='. $token ) === true );

check( 'a token nobody gave out, in the form of one, is no token', $shows( 'nino-theme-preview='. $other ) === false );
check( 'a query that is no token - upper case, too short, too long, a path, a list, a null byte, nothing - is a request for the page as it is, and reads no file', array_filter( [ strtoupper( $token ), substr( $token, 1 ), $token. '0', '../../x', '..%2f..%2fx', $token. '/../'. $token, "$token%00", '' ], static fn( string $value ): bool => $shows( 'nino-theme-preview='. $value ) === true ) === [] && $shows( 'nino-theme-preview[]='. $token ) === false && $shows( 'nino-theme-preview[a]=x' ) === false );

ninoUser( $site, 'second@example.com' );
check( 'the account is the one that asked: another account signed in with the right and the session of the token gets no draft', $shows( 'nino-theme-preview='. $token ) === false );
\Nino\Auth::loginUser( $site, 'designer@example.com', 'correct horse battery staple' );
check( 'and the first one has it again', $shows( 'nino-theme-preview='. $token ) === true );

$perms = $site['./nino/auth/current']['perms'] ?? null;
$site['./nino/auth/current']['perms'] = [ '/_admin/elements/manage' ];
check( 'the right is asked of the request and not only of the one that made the draft: the same account, the same session and the same token, with the right taken from it, gets the page as it is', $perms !== null && $shows( 'nino-theme-preview='. $token ) === false );
$site['./nino/auth/current']['perms'] = $perms;
ninoUser( $site, 'editor@example.com', [ '/_admin/elements/manage' ] );
check( 'an account without the right has none, with the token and the session of one who had it', $shows( 'nino-theme-preview='. $token ) === false );
\Nino\Auth::logoutUser( $site );
check( 'nobody signed in has none', $shows( 'nino-theme-preview='. $token ) === false );
\Nino\Auth::loginUser( $site, 'designer@example.com', 'correct horse battery staple' );

// how many, and for how long
for( $i = 0; $i < 7; $i++ )
	$last = themesCall( $site, 'themes/preview', [ 'mix' => $draft ] )[1]['token'];

check( 'a session holds five drafts at the most, the newest of them - the one that was just made, though all seven were made in the same second - among them', count( $_SESSION[$site['./nino/uid']]['./themes/preview'] ) === 5 && array_key_last( $_SESSION[$site['./nino/uid']]['./themes/preview'] ) === $last );
// The draft of a session that is gone: one that ran out and one that has time left
$orphans = [ md5( 'ran out' ), md5( 'in time' ) ];
mkdir( $cache. '/'. $orphans[0] );
mkdir( $cache. '/'. $orphans[1] );
file_put_contents( $cache. '/'. $orphans[0]. '/meta.json', json_encode( [ 'user' => 'x', 'expires' => time() - 5 ] ) );
file_put_contents( $cache. '/'. $orphans[1]. '/meta.json', json_encode( [ 'user' => 'x', 'expires' => time() + 500 ] ) );
mkdir( $cache. '/not-a-token' );
mkdir( $cache. '/'. $other );
$tokensBefore = array_keys( $_SESSION[$site['./nino/uid']]['./themes/preview'] );
themesCall( $site, 'themes/list' );
check( 'a list tidies as a preview does: the draft that ran out is removed, the one of another session with time left stays, the drafts of this session stay, and the sixth draft of a session is deleted and not only forgotten', is_dir( $cache. '/'. $orphans[0] ) === false && is_dir( $cache. '/'. $orphans[1] ) === true
	&& count( array_filter( $tokensBefore, static fn( string $kept ): bool => is_dir( $cache. '/'. $kept ) === true ) ) === 5 && count( array_filter( glob( $cache. '/*', GLOB_ONLYDIR ), static fn( string $folder ): bool => preg_match( \Nino\Modules\Themes::TOKEN, basename( $folder ) ) === 1 && in_array( basename( $folder ), array_merge( $tokensBefore, $orphans ), true ) === false ) ) === 0 );
file_put_contents( $cache. '/'. $orphans[1]. '/meta.json', json_encode( [ 'user' => 'x', 'expires' => time() - 5, 'by' => 'a change of the size' ] ) );
mkdir( $cache. '/'. $orphans[0] );
themesCall( $site, 'themes/preview', [ 'mix' => $draft ] );
check( 'a draft that is out of time is removed when the next one is made, and so is a folder in the form of a token that holds no draft; and a folder that is no token\'s is left alone', is_dir( $cache. '/'. $orphans[1] ) === false && is_dir( $cache. '/'. $orphans[0] ) === false && is_dir( $cache. '/not-a-token' ) === true && is_dir( $cache. '/'. $other ) === false );

[ $status, $body ] = themesCall( $site, 'themes/preview', [ 'mix' => [ 'parts' => [ 'section' => [ 'from' => 'nowhere' ] ] ] ] );
check( 'a draft that is not a mix is 400 and writes no draft', $status === 400 && $body['code'] === 'themes_mix' && $body['field'] === 'mix' );
$count = count( glob( $cache. '/*', GLOB_ONLYDIR ) );
[ $status, $body ] = themesCall( $site, 'themes/preview', [ 'mix' => $draft, 'brand' => [ 'color-1' => [ 'own' => true, 'value' => 'red' ] ] ] );
check( 'and so is a brand that is not one', $status === 400 && $body['code'] === 'themes_brand' && $body['field'] === 'brand' && count( glob( $cache. '/*', GLOB_ONLYDIR ) ) === $count );
[ $status, $body ] = themesCall( $site, 'themes/preview', [ 'mix' => $draft, 'token' => '../../../../x' ] );
check( 'a token that is posted and was never given is not used: the draft is a new one, in a folder of the cache named for it', $status === 200 && preg_match( \Nino\Modules\Themes::TOKEN, $body['token'] ) === 1 && is_dir( $cache. '/'. $body['token'] ) === true && is_dir( ninoSandboxDir( $site ). '/private/data/theme/.preview/../../../../x' ) === false );
$visit( 'nino-theme-preview='. $body['token'] );
$later = themesReboot( $site, $modules );
check( 'a request that comes after one with a preview in the same process begins with none: the page that says band is drawn as the website draws it', str_contains( \Nino\Html::renderHtml( $later, '[section-wrap variant="band"][/section-wrap]' ), 'section-wrap--variant-soft' ) === true );
$numbers['preview request'] = themesFastest( static function() use ( $visit, $token ): void {
	$visit( 'nino-theme-preview='. $token );
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
	'themes/save-as'	=> [ 'mix' => themesMix( 'X', [] ) ],
	'themes/apply'		=> [ 'theme' => 'craft-probe' ],
	'themes/restore'	=> [],
	'themes/delete'		=> [ 'theme' => 'own:x' ],
];
$answers = [];

foreach( $every as $action => $data ) {
	[ $status, $body ] = themesCall( $site, $action, $data );
	$answers[$action] = [ $status, $body ];
}

check( 'Nr. 9: an account that has not the right - the Editor of the starter site has not - gets 403 on every one of the eight actions, and nothing is read or written: no state, no stylesheet, no draft, no cache of the themes, nothing held by the session',
	count( $answers ) === 8 && array_filter( $answers, static fn( array $answer ): bool => $answer[0] !== 403 ) === [] && is_dir( $state ) === false && is_file( ninoSandboxDir( $site ). '/private/assets/theme.css' ) === false
	&& is_dir( ninoSandboxDir( $site ). '/private/data/theme/.cache' ) === false && isset( $_SESSION[$site['./nino/uid']]['./themes/preview'] ) === false );

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

$held = $_SESSION[$site['./nino/uid']]['./themes/preview'] ?? [];
$gone = themesCall( $site, 'themes/preview', [ 'mix' => $draft = Mix::of( $site, 'band-probe' ) ] )[1]['token'];
$_SESSION[$site['./nino/uid']]['./themes/preview'][$gone] = time() - 1;
$again = themesCall( $site, 'themes/preview', [ 'mix' => $draft, 'token' => $gone ] )[1]['token'];
check( 'a token that ran out in the session is not taken again by a preview that names it: the draft is a new one', $again !== $gone && preg_match( \Nino\Modules\Themes::TOKEN, $again ) === 1 );

// the schema's variants stay where a theme tells the register its own
\Nino\Filesystem::putFileContent( $site, '/data/theme/state/variants.php', [ 'section-col' => [ [ 'extra' ], 'card' ] ] );
check( 'what the theme tells the register of a component is added to what the schema says and does not replace it: the card stays, the standard is the theme\'s', \Nino\Modules\Themes::variants( $site, [ 'name' => 'section-col', 'variants' => [ 'regular', 'card' ], 'standard' => 'regular' ] ) === [ 'name' => 'section-col', 'variants' => [ 'regular', 'card', 'extra' ], 'standard' => 'card' ] );

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

check( 'Nr. 12: building takes up to 50 ms (the compiler, and a save that builds, writes and rotates the state), the first request after a save up to 30 ms, a request with a theme not more than 0.15 ms over one without, and the request of a preview up to 10 ms, the API that makes one up to 20 ms (the shortest of nine runs for each, and the four rounds of the request told as they were)',
	$numbers['compile'] <= 50 && $numbers['save and build'] <= 50 && $numbers['first request after a save'] <= 30 && $numbers['request, over a site without'] <= 0.15 && $numbers['preview request'] <= 10 && $numbers['preview, the API'] <= 20 );

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
check( 'Nino is a folder of data in the form of a theme: theme.json, the part of the page with no css, and a part with css for the header, the footer and the section - none for the buttons, the image and the text, which stay the standard parts',
	$ninoFiles === [ 'parts/footer/part.css', 'parts/footer/part.json', 'parts/header/part.css', 'parts/header/part.json', 'parts/page/part.json', 'parts/section/part.css', 'parts/section/part.json', 'theme.json' ] );
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

check( 'the middle step of each of the '. count( $groupMiddle ). ' knobs of the six groups, those the part turns among them, is what the standard part sets in :root, read from its file - the ladder is the part\'s, the middle is the standard\'s', count( $groupMiddle ) === 16
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
	$mixed['variants'] === [ 'footer-wrap' => [ [ 'dark' ], 'regular' ], 'header-wrap' => [ [ 'bar' ], 'regular' ], 'section-wrap' => [ [ 'band' ], 'regular' ] ] );
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

check( 'the probe page of the suite names every value but the default of each of the thirteen attributes that have a fixed step - '. $kinds. ' attributes of the components they belong to - so that the browser can measure each of them in each variant', $kinds === 20 && $missing === [], $missing );
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
check( 'K. the script keeps to the CSP of the workbench - nothing is built from a string, nothing is written as markup, and the only place a style is set is the box that measures a calculation - and the stylesheet is scoped to the panel: every rule begins with its id, but those for the dialog of the name, which stands in the root of the workbench',
	preg_match( '/\beval\s*\(|new\s+Function|setTimeout\s*\(\s*[\'"]|insertAdjacentHTML|document\.write|\.outerHTML\s*=/', $script ) === 0 && preg_match( '/innerHTML\s*=\s*[^\'\s]/', $script ) === 0 && preg_match( '/(?<!probe)\.style\b/', $script ) === 0
	&& str_starts_with( ltrim( $sheet ), '@layer nino.system, nino.tool, nino.local;' ) === true && preg_match_all( '/^(?:#themes-app|\t#themes-app)[^{]*\{/m', $sheet ) > 20 && preg_match( '/^(?!\.themes-dialog)[.a-z][^{@\n]*\{/m', preg_replace( '~/\*.*?\*/~s', '', $sheet ) ) === 0 );
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
check( 'K. a draft says how long its token holds, in seconds, so that a screen counts the time on its own clock', $draftBody['ttl'] === \Nino\Modules\Themes::TTL && $draftBody['ttl'] === 900 );

// the outline of a component in the preview
$cache = ninoSandboxDir( $site ). '/private/data/theme/.preview';
$token = $draftBody['token'];
$visit = static function( string $query ) use ( &$site ): string {

	ninoVisit( $site, '/?'. $query );

	return \Nino\Html::renderHtml( $site, '[assets '. THEMES_BUNDLE. ']' );
};
$visit( 'x=1' );
$publicBefore = (string) file_get_contents( ninoSandboxDir( $site ). '/public/.cache/style.css' );
$plainPreview = $visit( 'nino-theme-preview='. $token );
$marked = $visit( 'nino-theme-preview='. $token. '&mark=section-col' );
$outline = '.section-col { outline: 2px solid #e8590c !important; outline-offset: -2px !important; }';
check( 'K. a preview that names a component outlines it - one rule after the whole bundle, which no layer can beat - and one that names none does not', substr_count( $marked, $outline ) === 1 && str_contains( $plainPreview, '#e8590c' ) === false && str_ends_with( substr( $marked, 0, (int) strpos( $marked, '</style>' ) ), $outline ) === true
	&& substr_count( $marked, '</style>' ) === 1 );
check( 'K. what stands after mark is a name and nothing else: a selector, a list, a path, a quote, an upper-case letter, an array and nothing are no mark, and no byte of them is in the page',
	array_filter( [ 'section-col, body', 'a{}', '../x', 'a"b', 'Section-col', 'section_col', "x\0y", 'x y', str_repeat( 'a', 200 ), '' ], static fn( string $value ): bool => str_contains( $visit( 'nino-theme-preview='. $token. '&mark='. rawurlencode( $value ) ), '#e8590c' ) === true ) === []
	&& str_contains( $visit( 'nino-theme-preview='. $token. '&mark[]=section-col' ), '#e8590c' ) === false && str_contains( $visit( 'nino-theme-preview='. $token. '&mark[a]=x' ), '#e8590c' ) === false );
check( 'K. the outline is for a request that holds a preview: with no token, a token that is not the session\'s or a token that ran out the page is the page as it is, and carries no outline', str_contains( $visit( 'mark=section-col' ), '#e8590c' ) === false && str_contains( $visit( 'nino-theme-preview='. str_repeat( '0', 32 ). '&mark=section-col' ), '#e8590c' ) === false
	&& str_contains( $visit( 'nino-theme-preview='. strtoupper( $token ). '&mark=section-col' ), '#e8590c' ) === false );
$_SESSION[$site['./nino/uid']]['./themes/preview'] = [ $token => time() - 1 ];
check( '...nor with a token whose time is out', str_contains( $visit( 'nino-theme-preview='. $token. '&mark=section-col' ), '#e8590c' ) === false );
check( 'K. and the request after one that asked for an outline begins with none: the public bundle is the file it was and a page with no query has no style element of the preview', ( function() use ( &$site, $modules, $visit, $publicBefore ): bool {
	$later = themesReboot( $site, $modules );
	$unmarked = \Nino\Modules\Assets::stylesheet( $later, THEMES_BUNDLE );

	return str_contains( $unmarked, '#e8590c' ) === false && (string) file_get_contents( ninoSandboxDir( $later ). '/public/.cache/style.css' ) === $publicBefore && str_contains( $visit( 'x=1' ), 'data-nino-theme-preview' ) === false;
} )() );

// one request after another in a process: the outline asked for in the first is not in the second, which asks for none
$visit( 'nino-theme-preview='. $token. '&mark=section-col' );
$_SESSION[$site['./nino/uid']]['./themes/preview'] = [ $token => time() + 600 ];
check( 'K. a request that holds a preview and names no component has no outline, though the request before it in the same process named one', str_contains( $visit( 'nino-theme-preview='. $token. '&mark=section-col' ), '#e8590c' ) === true && str_contains( $visit( 'nino-theme-preview='. $token ), '#e8590c' ) === false );

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

// the widths are a strip of steps that is not there where the view is too narrow for the preview beside the settings, and the dialog that asks a name has the floor of the text too
$narrow = preg_match( '/@container nino-split \(max-width: 40rem\) \{(.*)\}\s*\}\s*$/s', preg_replace( '~/\*.*?\*/~s', '', $sheet ), $found ) === 1 ? $found[1] : '';
check( 'K. the choice of the width is hidden by the width of the split view - a container query of the workbench - and the one media query the sheet has is the phone rule of the bar', preg_match( '/#themes-app \.themes-widthbox\s*\{\s*display: none;/', $narrow ) === 1 && substr_count( $sheet, '@media' ) === 1 && str_contains( $sheet, '@media (max-width: 38rem)' ) === true );
check( 'K. the dialog of the name holds its field and its sentences to 12 pixels like the screen, outside of the id of the panel', preg_match( '/\.themes-dialog :is\([^)]*nino-admin-field > span[^)]*\)\s*\{\s*font-size: max\(\.75rem, 12px\);/', $sheet ) === 1 && str_contains( $script, "'themes-dialog'" ) === true );

// an own theme is in the address as own.<key>: no theme of a package can have a dot in its key
$folder = ninoSandboxDir( $site ). '/themes/own.x';
mkdir( $folder );
file_put_contents( $folder. '/theme.json', json_encode( [ 'key' => 'own.x', 'version' => '1.0.0', 'name' => [ 'en_US' => 'Own X', 'de_DE' => 'Own X' ], 'description' => [ 'en_US' => 'x', 'de_DE' => 'x' ], 'brand' => [] ] ) );
unset( $site['./nino/themes/read'] );
$read = \Nino\Modules\Themes\Reader::read( $site );
check( 'K. a theme of a package that is called as an own theme is in the address - the key own.x - is not read: its key is no slug, so that the address of an own theme is never the key of another', isset( $read['themes']['own.x'] ) === false && isset( $read['themes']['own'] ) === false && str_contains( json_encode( $read['rejected'] ), 'own.x' ) === true );

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

foreach( $sandboxes as $sandbox )
	if( is_dir( $sandbox ) === true )
		\Nino\Filesystem::removeDir( $sandbox );

ninoDone( $appData );
