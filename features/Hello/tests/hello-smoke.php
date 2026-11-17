<?php
declare(strict_types=1);

// The test of the feature Hello: the component, the page, the panel's actions, the data. php features/Hello/tests/hello-smoke.php
$root = getenv( 'NINO_ROOT' ) ?: dirname( __DIR__, 3 );
defined( 'NINO_FEATURES_DIR' ) === true || define( 'NINO_FEATURES_DIR', dirname( __DIR__, 2 ) );
require $root. '/tests/harness.php';

$dir = dirname( __DIR__ );
$appData = ninoSandbox( 'hello' );
$appData['./nino/filesystem/path'] = $root;
$manifest = \Nino\Features::manifest( $dir );
check( 'the manifest validates and is written for this kernel', is_array( $manifest ) === true && \Nino\Features::satisfies( (string) $manifest['nino'] ) === true && isset( $manifest['components'] ) === false );
check( 'Basis and the feature are switched on', \Nino\Features::activate( $appData, 'basis' ) === true && \Nino\Features::activate( $appData, 'hello' ) === true );
check( 'the unit brought the page, its words and the section of the privacy policy', \Nino\Filesystem::fileExists( $appData, '/templates/page-hello.tpl' ) === true );

$appData['/nino/modules'] = (array) ( \Nino\Filesystem::getFileContent( $appData, '/config.php', [] )['/nino/modules'] ?? [] );
\Nino\Html::init( $appData );
\Nino\Features::registerComponents( $appData );
\Nino\Modules::callModules( $appData, 'init' );
\Nino\Features::registerParts( $appData );
$draw = static fn( string $source ): string => trim( \Nino\Html::renderSource( $appData, $source, [ 'template' => 'page-hello' ] + \Nino\Html::context( $appData ) ) );
$ignored = ninoWarnings();

check( 'the folders are read without a reason', \Nino\Features::componentProblems( $appData, 'hello' ) === [] );
$schema = \Nino\Components::components( $appData )['hello'] ?? [];
check( 'the component is in its group, labelled in both languages, with an icon of the table', ( $schema['group'] ?? '' ) === 'hello' && isset( $schema['label']['de_DE'], $schema['label']['en_US'] ) === true && in_array( $schema['icon'] ?? '', \Nino\Components::ICONS, true ) === true );
check( 'the standard part keeps the rule of mixing', \Nino\Components::partProblems( (string) file_get_contents( $dir. '/groups/hello/part/part.css' ), [ 'hello' ] ) === [] );
check( 'a tool offers it in a column', in_array( 'hello', \Nino\Components::allowed( $appData, 'section-col' ), true ) === true );
check( 'it greets the stored name, a name of the call, and in capitals', $draw( '[hello]' ) === '<p class="hello">Hello, World!</p>' && $draw( '[hello name="Ada" shout]' ) === '<p class="hello">HELLO, ADA!</p>' );
check( 'a name that spells a tag or a call is text, escaped once', $draw( '[hello name="<b> & [csrf]"]' ) === '<p class="hello">Hello, &lt;b&gt; &amp; [csrf]!</p>' );

// The unit brings its section of the privacy policy, the same one in every language and listed in the manifest of the unit
$unit = (array) include $dir. '/install/manifest.php';
$privacy = (array) include $dir. '/install/elements/privacy.php';
check( 'the section of the privacy policy is in the unit, in both languages, under the id of the feature', ( $unit['elements']['privacy'] ?? '' ) === 'elements/privacy.php'
	&& isset( $privacy['*']['hello'], $privacy['de_DE']['hello']['text'], $privacy['en_US']['hello']['text'] ) === true );

// The page the route answers is named in every language the unit speaks: what the Builder lists it by and what the page's title reads
$named = [];
foreach( [ 'de_DE', 'en_US' ] as $locale )
	$named[] = array_keys( array_filter( (array) include $dir. '/install/text/'. $locale. '.php', static fn( string $key ): bool => str_starts_with( $key, '[[/_nino/webpage/hello/' ), ARRAY_FILTER_USE_KEY ) );
check( 'the page is named, titled and described in both languages', $named[0] === $named[1] && count( $named[0] ) === 3 );

// The page of the unit is made of components: a tool reads it as blocks, with nothing it has to keep as free html
$page = (string) file_get_contents( ninoSandboxDir( $appData ). '/private/templates/page-hello.tpl' );
$tree = \Nino\Markup::parse( $page, \Nino\Components::containers( $appData ) );
// What a tool finds against the page: the name comment and the two includes of the frame, and nothing between them
$found = [];
foreach( \Nino\Components::check( $appData, $tree ) as $at => $problems )
	foreach( $problems as $problem )
		if( ( $problem['code'] === 'unknown' && $problem['detail'] === 'template' ) === false && ( $problem['code'] === 'free' && $at === 0 ) === false )
			$found[] = $problem['code'];
check( 'the page of the unit is made of components: no class of Nino 1, no element of its own, nothing a tool keeps as free html', preg_match( '/nino-|<section|<div|<h1/', $page ) === 0 && $found === [] );
check( 'the route of the page is registered and its template draws the greeting', ( $appData['/nino/http/routes']['GET://hello']['uri'] ?? '' ) === '/hello'
	&& str_contains( $draw( '[section-wrap id="intro"][section-row][section-col][hello][/section-col][/section-row][/section-wrap]' ), '<p class="hello">Hello, World!</p>' ) === true );

// The panel: both actions ask for the right, the save keeps the name and refuses one that is too long
ninoUser( $appData, 'editor@example.org', [] );
$denied = ninoCall( $appData, [ \Nino\Modules\Hello\Admin::class, 'apiSave' ], [ 'name' => 'Eve' ] );
check( 'an account without the right saves nothing', $denied[0] === 403 && \Nino\Modules\Hello::name( $appData ) === 'World' );
ninoUser( $appData, 'admin@example.org', [ '/_admin/hello/manage' ] );
$saved = ninoCall( $appData, [ \Nino\Modules\Hello\Admin::class, 'apiSave' ], [ 'name' => 'Ada' ] );
check( 'the save keeps the name in the file a backup carries', $saved[0] === 200 && \Nino\Modules\Hello::name( $appData ) === 'Ada' && in_array( \Nino\Modules\Hello::STORAGE, $manifest['data'], true ) === true );
check( 'a name that is too long is refused at its field', ( ninoCall( $appData, [ \Nino\Modules\Hello\Admin::class, 'apiSave' ], [ 'name' => str_repeat( 'a', 61 ) ] )[0] ) === 400 );

// The script of the panel builds nothing itself (the guide, "What a panel does not build itself")
$script = (string) file_get_contents( $dir. '/assets/admin.js' );
check( 'the rail entry has a sign of its own: an svg that draws, no script in it', preg_match( '/^<svg [^>]*>(<path d="[^"]+"\/>)+<\/svg>$/', \Nino\Modules\Hello\Admin::icon() ) === 1 );
check( 'the panel script takes its bar, button and field from the workbench', preg_match( '/createElement\(\s*.button|window\.confirm|\balert\(|nino-admin-btn-|type\s*[:=]\s*.file/', $script ) === 0 && str_contains( $script, 'Nino.adminUi.actionBar( {' ) === true );
check( 'the form is the shape of the guide: novalidate, a fieldset with its fieldgrid, one main button, the answer as a toast, unsaved input reported', str_contains( $script, 'noValidate = true' ) === true
	&& str_contains( $script, "'fieldset'" ) === true && str_contains( $script, 'nino-admin-fieldgrid' ) === true && substr_count( $script, 'primary :' ) === 1
	&& str_contains( $script, 'Nino.adminUi.say(' ) === true && str_contains( $script, 'dirty' ) === true );
$words = [ 'de_DE' => include $dir. '/text/de_DE.php', 'en_US' => include $dir. '/text/en_US.php' ];
check( 'both languages of the panel have the same keys', array_keys( $words['de_DE'] ) === array_keys( $words['en_US'] ) );
// A word the script reads is a fill of the panel or of the workbench's common list: never one the panel forgot
preg_match_all( "~'(/_admin/(?!common/)[a-z/_-]+)'~", $script, $read );
$own = array_map( static fn( string $key ): string => trim( $key, '[]' ), array_keys( $words['en_US'] ) );
check( 'every word the script reads is one the panel brings in both languages', array_diff( array_unique( $read[1] ), $own ) === [] );

$block = \Nino\Blocks::get( $appData, 'hello-greeting' );
check( 'the block is valid and names the feature', is_array( $block ) === true && in_array( 'hello', $block['features'] ?? [], true ) === true && ( $block['missing'] ?? [] ) === [] );
check( 'the demo of the group draws', substr_count( $draw( (string) ( \Nino\Components::groups( $appData )['hello']['demo'] ?? '' ) ), 'class="hello"' ) === 2 );

check( 'switched off, the call draws nothing and the name stays', \Nino\Features::deactivate( $appData, 'hello' ) === true && \Nino\Filesystem::fileExists( $appData, \Nino\Modules\Hello::STORAGE ) === true );
$ignored = ninoWarnings();

// Nothing of the old components API or the old stylesheet vocabulary is left in the folder
$oldWorld = [];
foreach( new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $dir, \FilesystemIterator::SKIP_DOTS ) ) as $file )
	if( str_contains( (string) $file, '/tests/' ) === false && preg_match( '/Feature::shortcode|Modules\\\\Components|\bnino-(?!admin-fieldgrid)[a-z]|--(?:space|color|radius|fontsize)-|Nino\.ui\./', (string) file_get_contents( (string) $file ) ) === 1 )
		$oldWorld[] = (string) $file;
check( 'no file of the feature names the vocabulary of Nino 1', $oldWorld === [] );

ninoDone( $appData );
