<?php
declare(strict_types=1);

/**
 *	Nino
 *	hello-smoke.php		The example feature's own test - and, like the rest of
 *										features/Hello/, written to be copied.
 *
 *										A feature's test runs against a real Nino checkout with
 *										a throwaway project in it, not against mocks: the
 *										harness three levels up builds the sandbox, and
 *										everything below drives the actual kernel. That is the
 *										point - what a feature has to get right is its contract
 *										with Nino, and only Nino can say whether it did.
 *
 *										It is laid out as the request is: the manifest, then
 *										activation, then what init() registers, then what the
 *										shortcode renders, then the panel, then the data, then
 *										deactivation. Copy the shape and delete the sections
 *										your feature has no equivalent of.
 *
 *	Usage: php features/Hello/tests/hello-smoke.php
 *	       NINO_ROOT=../nino php features/Hello/tests/hello-smoke.php
 */

/*	The checkout this runs against: three levels up when the feature sits in a
	project's features/, else the one NINO_ROOT names - a checkout beside the
	catalogue, say. The features root is this feature's own parent either way,
	so the kernel's autoloader serves the class from here rather than from a
	copy somewhere else	*/
$root = getenv( 'NINO_ROOT' ) ?: dirname( __DIR__, 3 );
defined( 'NINO_FEATURES_DIR' ) === true || define( 'NINO_FEATURES_DIR', dirname( __DIR__, 2 ) );
require $root. '/tests/harness.php';

/*	A throwaway project in a temp directory: config, private half, locales,
	the lot. check(), ninoWarnings(), ninoSandboxDir() and ninoDone() all come
	from that harness	*/
$appData = ninoSandbox( 'hello' );
$appData['/nino/dir'] = '';

/*	\Nino\AppData::prepare() (what ninoSandbox() calls) seeds only the keys
	needed before config.php loads. Textfiles' own directory arrives through
	the real ::init(), which a sandboxed test never runs - so rendering a fill
	needs this one set by hand	*/
$appData['/nino/locales/textfiles'] = '/text';
// ...and English, so the assertions below can compare against
// install/text/en_US.php rather than against a translation
$appData['./nino/locales/current'] = 'en_US';

/*	\Nino\Filesystem::path()'s fallback resolves a virtual path outside
	PRIVATE_DIRS/PUBLIC_DIRS against the project root - which is how
	'/_nino/Nino.css' reaches the kernel's own file. The sandbox's root is a
	fresh temp directory, not this feature's real parent, so the file the
	asset bundler actually has to read is mirrored into it here, the way a
	real project's features/ directory holds it	*/
$assetsDir = ninoSandboxDir( $appData ). '/features/Hello/assets';
mkdir( $assetsDir, 0755, true );
copy( dirname( __DIR__ ). '/assets/hello.css', $assetsDir. '/hello.css' );


// --- 1. The manifest ---------------------------------------------------------

echo "The manifest\n";

$dir = dirname( __DIR__ );
$manifest = \Nino\Features::manifest( $dir );

check( 'it validates, key "hello"', is_array( $manifest ) === true && $manifest['key'] === 'hello' && ninoWarnings() === [] );
check( 'it is written for this kernel', is_array( $manifest ) === true && \Nino\Features::satisfies( $manifest['nino'] ) === true );
check( 'it names and describes itself in both interface languages', is_array( $manifest ) === true
	&& \Nino\Features::localized( $manifest['description'], 'de_DE' ) !== \Nino\Features::localized( $manifest['description'], 'en_US' ) );

/*	Read from the file rather than from $manifest: this repository's CI runs
	every feature's test against Nino's main *and* against its latest tag, and
	a kernel released before categories existed drops the field on the way
	through	*/
$raw = include $dir. '/feature.php';
check( 'it is filed under system - an example belongs with the tooling', ( $raw['category'] ?? '' ) === 'system' );

check( 'the setting is declared with a type the panel can draw', ( $manifest['settings']['greeting']['type'] ?? '' ) === 'string'
	&& in_array( $manifest['settings']['greeting']['type'], \Nino\Features::SETTING_TYPES, true ) === true );
check( '...and with a default, so it is worth something before anybody saves', ( $manifest['settings']['greeting']['default'] ?? null ) === 'Hello' );
check( 'what it stores is declared, so a backup carries it', in_array( \Nino\Modules\Hello::PATH, (array) ( $manifest['data'] ?? [] ), true ) === true );

echo "\n";


// --- 2. Activation -----------------------------------------------------------

echo "Activation - what the install unit puts in the project\n";

// A project's config.php, written the way the wizard leaves it, so the
// activation has something to add its class to
\Nino\AppData::writeContentData( $appData, [ '/nino/modules', '/nino/locales/available', '/nino/locales/native' ] );
ninoWarnings();

check( 'the registry lists it inactive, with nothing in the way', ( static function() use ( &$appData ): bool {
	$feature = \Nino\Features::get( $appData, 'hello' );
	return $feature !== null && $feature['active'] === false && $feature['installed'] === null && $feature['problems'] === [];
} )() );

check( 'activation succeeds', \Nino\Features::activate( $appData, 'hello' ) === true );
check( 'the class is listed and the version recorded', in_array( '\\Nino\\Modules\\Hello', \Nino\Filesystem::getFileContent( $appData, '/config.php', [] )['/nino/modules'], true ) === true
	&& \Nino\Features::get( $appData, 'hello' )['installed'] === '1.0.0' );

check( 'the page template is in the project now', \Nino\Filesystem::fileExists( $appData, '/templates/page-hello.tpl' ) === true );

// Add-only, and for every locale the project has
foreach( [ 'en_US', 'de_DE' ] as $locale ) {
	$text = \Nino\Filesystem::getFileContent( $appData, '/text/'. $locale. '.php', [] );
	check( 'the site\'s words are in text/'. $locale. '.php', isset( $text['[[/hello/note]]'] ) === true
		&& isset( $text['[[/hello/page/title]]'] ) === true );
}

// The panel's own words are not: they are read out of features/Hello/text/
// while the panel is drawn, and belong to the workbench rather than the site
check( 'the panel\'s words stayed out of the project', isset(
	\Nino\Filesystem::getFileContent( $appData, '/text/en_US.php', [] )['[[/_admin/hello/title]]'] ) === false );

echo "\n";


// --- 3. init() ---------------------------------------------------------------

echo "Modules\\Hello::init() - what one request gets\n";

// Modules\Assets is what turns [assets ...] into a tag at all, and
// Modules\Template what turns [template ...] into a file - the sandbox starts
// with no modules, so both are added here to drive the real thing end to end
$appData['/nino/modules'][] = '\\Nino\\Modules\\Assets';
$appData['/nino/modules'][] = '\\Nino\\Modules\\Template';
\Nino\Modules::callModules( $appData, 'init' );

check( 'the shortcode is registered', ( $appData['./nino/html/shortcodes']['hello'] ?? null ) !== null );
check( 'the route is registered at runtime, not written into config.php',
	( $appData['/nino/http/routes']['GET://hello']['uri'] ?? '' ) === '/hello'
	&& isset( \Nino\Filesystem::getFileContent( $appData, '/config.php', [] )['/nino/http/routes']['GET://hello'] ) === false );
check( 'hello.css joined the project\'s own bundle', in_array( '/features/Hello/assets/hello.css', \Nino\Html::getAssets( $appData, '/.cache/style.css' ), true ) === true );
check( 'the generated cache file really carries it', ( static function() use ( &$appData ): bool {
	\Nino\Html::renderHtml( $appData, '[assets /.cache/style.css]' );
	return str_contains( (string) \Nino\Filesystem::getFileContent( $appData, '/.cache/style.css', '' ), '.nino-hello' );
} )() );

echo "\n";


// --- 4. [hello] --------------------------------------------------------------

echo "[hello] - what a page renders\n";

$html = \Nino\Html::renderHtml( $appData, '[hello]' );

check( 'it greets, with the setting\'s default and the stored fallback', str_contains( $html, 'Hello, World!' ) === true );
check( '...and its fill is resolved rather than printed', str_contains( $html, 'Rendered by the Hello World feature.' ) === true
	&& str_contains( $html, '[[/hello/note]]' ) === false );
check( 'an argument names somebody else', str_contains( \Nino\Html::renderHtml( $appData, '[hello name="Ada"]' ), 'Hello, Ada!' ) === true );

/*	The one rule that is never optional: a shortcode argument comes straight
	out of a template and a panel's stored value straight out of a form, so
	both are escaped. A fill is not - that goes through the fill engine, which
	is what an editor's own text is written in	*/
check( 'what came from outside is escaped', str_contains(
	\Nino\Html::renderHtml( $appData, '[hello name="<script>alert(1)</script>"]' ), '<script>' ) === false );

// The setting, changed the way the Features panel changes it
\Nino\Features::saveSettings( $appData, 'hello', [ 'greeting' => 'Guten Tag' ] );
check( 'the setting is what the greeting reads', str_contains( \Nino\Html::renderHtml( $appData, '[hello]' ), 'Guten Tag, World!' ) === true );
check( '...and an empty one falls back rather than greeting with nothing', ( static function() use ( &$appData ): bool {
	\Nino\Features::saveSettings( $appData, 'hello', [ 'greeting' => '   ' ] );
	return str_contains( \Nino\Html::renderHtml( $appData, '[hello]' ), 'Hello, World!' );
} )() );

// The page the install unit brought, rendered through the route's own body
check( 'the installed page renders the greeting inside it', str_contains(
	\Nino\Html::renderHtml( $appData, '[template /templates/page-hello]' ), 'Hello, World!' ) === true );

echo "\n";


// --- 5. The panel ------------------------------------------------------------

echo "Modules\\Hello\\Admin - one field, one button\n";

check( 'the feature brings its panel along', \Nino\Modules\Hello::adminPanels( $appData ) === [ \Nino\Modules\Hello\Admin::class ] );
check( 'it names every action it answers', array_keys( \Nino\Modules\Hello\Admin::actions() ) === [ 'hello/list', 'hello/save' ] );
check( 'it brings a pane, a script and a stylesheet', \Nino\Modules\Hello\Admin::panes() === [ 'hello-form' ]
	&& count( \Nino\Modules\Hello\Admin::assets() ) === 2 );
check( 'a save is written to the activity log and a read is not', \Nino\Modules\Hello\Admin::log( 'hello/save', [] ) !== ''
	&& \Nino\Modules\Hello\Admin::log( 'hello/list', [] ) === '' );

/**
 *	Call one panel action the way the workbench does: a posted request with
 *	the action's json in it, answered into $request. Every feature test that
 *	has a panel needs this or something like it
 *
 *	@param		array 		&$appData			(reference) The sandbox's app data
 *	@param		string		$method				The Admin method to call
 *	@param		array			$data					What the screen posted
 *
 *	@return 	array										[ status, decoded body ]
 */
function callHelloAction( array &$appData, string $method, array $data = [] ): array {

	$_POST['data'] = json_encode( $data );

	// The shape an action writes into - a request that has already been
	// answered 200 until something says otherwise, which is what the kernel
	// hands a panel
	$request = [ '/nino/http/response' => [ 'statusCode' => 200 ] ];

	\Nino\Modules\Hello\Admin::$method( $appData, $request );

	$_POST = [];

	return [
		(int) ( $request['/nino/http/response']['statusCode'] ?? 0 ),
		is_array( $request['/nino/http/response']['body'] ?? null )
			? $request['/nino/http/response']['body']
			: json_decode( (string) ( $request['/nino/http/response']['body'] ?? '' ), true ),
	];
}

/*	Guarded before anything else, because the routing does not guard: a panel
	that is not drawn is not a panel that cannot be posted to	*/
check( 'every action refuses a request with no session',
	callHelloAction( $appData, 'apiList' )[0] === 401
	&& callHelloAction( $appData, 'apiSave', [ 'name' => 'Ada' ] )[0] === 401 );

\Nino\Auth::insertUser( $appData, 'editor@example.com', 'correct horse battery staple', [ '/_admin/elements/manage' ] );
\Nino\Auth::loginUser( $appData, 'editor@example.com', 'correct horse battery staple' );
check( '...and an account without the permission with a 403', callHelloAction( $appData, 'apiList' )[0] === 403 );

\Nino\Auth::insertUser( $appData, 'dev@example.com', 'correct horse battery staple', [ '/*' ] );
\Nino\Auth::loginUser( $appData, 'dev@example.com', 'correct horse battery staple' );

[ $status, $listed ] = callHelloAction( $appData, 'apiList' );
check( 'the list hands the screen what it draws', $status === 200
	&& ( $listed['name'] ?? '' ) === 'World'
	&& ( $listed['fallback'] ?? '' ) === 'World'
	&& isset( $listed['greeting'] ) === true );

[ $status, $saved ] = callHelloAction( $appData, 'apiSave', [ 'name' => 'Ada' ] );
check( 'a save stores it and answers with what to show now', $status === 200 && ( $saved['name'] ?? '' ) === 'Ada' );
check( '...and the shortcode greets her from then on', str_contains( \Nino\Html::renderHtml( $appData, '[hello]' ), ', Ada!' ) === true );

check( 'an empty name is the fallback rather than an empty greeting', ( static function() use ( &$appData ): bool {
	callHelloAction( $appData, 'apiSave', [ 'name' => '   ' ] );
	return \Nino\Modules\Hello::name( $appData ) === 'World';
} )() );

check( 'a name longer than a name is refused, and nothing is written',
	callHelloAction( $appData, 'apiSave', [ 'name' => str_repeat( 'a', 61 ) ] )[0] === 400
	&& \Nino\Modules\Hello::name( $appData ) === 'World' );

echo "\n";


// --- 6. The data, and the upgrade hook ---------------------------------------

echo "What it stores\n";

callHelloAction( $appData, 'apiSave', [ 'name' => 'Ada' ] );

check( 'it writes exactly the file the manifest declared', \Nino\Filesystem::fileExists( $appData, \Nino\Modules\Hello::PATH ) === true );
/*	...and nothing else. \Nino\Filesystem::path() is how a virtual path
	becomes a real one - never build it out of the project root by hand, or a
	project that moved its private half with NINO_PRIVATE_DIR is a project
	your test looks in the wrong place for	*/
check( '...and nothing else of its own under data/', array_values( array_filter(
	(array) scandir( \Nino\Filesystem::path( $appData, '/data' ) ),
	// The dot entries are the kernel's - /data/.locks is where
	// Filesystem::mutate() keeps its lock files, and it belongs to whoever
	// wrote last rather than to any one feature
	static fn( string $entry ): bool => str_starts_with( $entry, '.' ) === false
) ) === [ 'hello.php' ] );

/*	The only hook there is, and at 1.0.0 it has nothing to do. Called here all
	the same: an upgrade() that throws on a project with no stored data yet is
	the classic way a feature breaks the release it was meant to fix	*/
\Nino\Modules\Hello::upgrade( $appData, '0.9.0', '1.0.0' );
check( 'upgrade() survives being called with nothing to migrate', \Nino\Modules\Hello::name( $appData ) === 'Ada' );

echo "\n";


// --- 7. Deactivation ---------------------------------------------------------

echo "Deactivation\n";

check( 'deactivation succeeds', \Nino\Features::deactivate( $appData, 'hello' ) === true );
check( 'the class is gone from /nino/modules', in_array( '\\Nino\\Modules\\Hello', \Nino\Filesystem::getFileContent( $appData, '/config.php', [] )['/nino/modules'], true ) === false );

/*	What a deactivation leaves behind is the project's, and that is the
	contract: the template and the words were merged in and stay, the stored
	data stays for a reactivation, and only the running code goes	*/
check( 'the page template stays - it is the project\'s file now', \Nino\Filesystem::fileExists( $appData, '/templates/page-hello.tpl' ) === true );
check( 'the words stay too', isset( \Nino\Filesystem::getFileContent( $appData, '/text/en_US.php', [] )['[[/hello/note]]'] ) === true );
check( 'and what was stored is still there for a reactivation', \Nino\Filesystem::fileExists( $appData, \Nino\Modules\Hello::PATH ) === true );
check( 'the feature is still on disk, listed and inactive', ( \Nino\Features::get( $appData, 'hello' )['active'] ?? true ) === false );

ninoDone( $appData );
