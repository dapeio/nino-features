<?php
declare(strict_types=1);

/**
 *	Dependency-free backend smoke test for the Template Builder module and its panel.
 *	All writes stay inside an isolated temporary project.
 */

define( 'FEATURE', dirname( __DIR__ ) );
define( 'NINO', getenv( 'NINO_ROOT' ) ?: dirname( __DIR__, 3 ) );
define( 'NINO_FEATURES_DIR', dirname( __DIR__, 2 ) );

require NINO. '/_nino/Nino.php';
require NINO. '/_admin/Admin.php';

$failures = 0;
$checks = 0;

function check( string $label, bool $condition ): void {
	global $failures, $checks;
	$checks++;
	if( $condition ) {
		echo "  ok  - $label\n";
		return;
	}
	$failures++;
	echo "FAIL  - $label\n";
}

function response(): array {
	return [ '/nino/http/response' => [ 'statusCode' => 200 ] ];
}

function post( array $data ): void {
	$_POST['data'] = json_encode( $data );
}

function throwsInvalidArgument( callable $callback ): bool {
	try {
		$callback();
	} catch( \InvalidArgumentException ) {
		return true;
	}
	return false;
}

set_error_handler( function() { return true; } );

$sandbox = sys_get_temp_dir(). '/nino-templates-smoke-'. uniqid();
mkdir( $sandbox. '/private/templates', 0777, true );
mkdir( $sandbox. '/private/text', 0777, true );
mkdir( $sandbox. '/private/elements', 0777, true );
mkdir( $sandbox. '/public/.cache', 0777, true );
mkdir( $sandbox. '/private/assets', 0777, true );
mkdir( $sandbox. '/public/fonts/text', 0777, true );
file_put_contents( $sandbox. '/public/.cache/style.css', '/* stale-template-preview-css */' );
file_put_contents( $sandbox. '/public/fonts/text/preview.woff2', 'preview-font' );
file_put_contents( $sandbox. '/private/assets/style.preview.css', '/* template-preview-project-css */ @font-face{font-family:Preview;src:url("[[/nino/public]]/fonts/text/preview.woff2") format("woff2")} @font-face{font-family:Remote;src:url("https://example.invalid/remote.woff2")} .nino-section{display:block}' );

$_SERVER['REMOTE_ADDR'] = '127.0.0.1';

$appData = [ './nino/uid' => $sandbox ];
\Nino\AppData::prepare( $appData );
$appData['./nino/filesystem/path'] = $sandbox;
$appData['./nino/filesystem/configpath'] = $sandbox. '/private';
$appData['./nino/filesystem/contentpath'] = $sandbox. '/private';
$appData['./nino/filesystem/publicpath'] = $sandbox. '/public';
$appData['/nino/dir'] = '';
$appData['/nino/locales/native'] = 'en_US';
$appData['/nino/locales/available'] = [ 'en_US', 'de_DE' ];
$appData['./nino/html/shortcodes'] = [];
$appData['/nino/html/images'] = [];
$appData['/nino/html/assets'] = [ '/.cache/style.css' => [ '/assets/style.preview.css' ] ];

\Nino\Filesystem::putFileContent( $appData, '/config.php', [ '/nino/html/images' => [] ] );
\Nino\Filesystem::putFileContent( $appData, '/text/global.php', [ '[[/_nino/webpage/contact/uri]]' => '/contact' ] );
\Nino\Filesystem::putFileContent( $appData, '/text/blacklist.php', [ '/_nino/webpage/contact/uri' ] );
\Nino\Filesystem::putFileContent( $appData, '/text/en_US.php', [ '[[/template/page-home/hero/title]]' => 'Old title' ] );
\Nino\Filesystem::putFileContent( $appData, '/text/de_DE.php', [ '[[/template/page-home/hero/title]]' => 'Alter Titel' ] );

echo "Sandbox: $sandbox\n\n";


// --- Bootstrap ------------------------------------------------------------
// The Builder is a workbench panel: its actions run through Admin::handlePost()
// and every one of them guards itself, so the anonymous checks come first and
// the developer account the rest of this file works as is created after them

echo "Templates module / panel\n";

$appData['/nino/modules'][] = '\\Nino\\Modules\\Templates';

$registry = \Nino\Admin\Admin::panels( $appData );
check( 'the module contributes the Templates panel to the workbench, as a workspace with its own template', ( $registry['templates']['class'] ?? null ) === \Nino\Modules\Templates\Admin::class
	&& $registry['templates']['layout'] === 'workspace'
	&& $registry['templates']['template'] === '/features/Templates/templates/panel'
	// 'features', not what nav() names: the workbench puts every panel a
	// feature brought in that one group on purpose, so granting it is a
	// bounded grant (see \Nino\Admin\Admin::_isFeaturePanel())
	&& $registry['templates']['group'] === 'features'
	&& str_starts_with( $registry['templates']['icon'], '<svg' ) === true );
check( 'its assets are the four scripts and the stylesheet, project-relative, the namespace seed first', $registry['templates']['assets'][0] === '/features/Templates/assets/script.js'
	&& array_search( '/features/Templates/assets/composer.js', $registry['templates']['assets'], true ) < array_search( '/features/Templates/assets/area-composer.js', $registry['templates']['assets'], true )
	&& in_array( '/features/Templates/assets/style.css', $registry['templates']['assets'], true ) === true );
$actions = \Nino\Admin\Admin::actions( $appData );
check( 'every Documents, Library and Content action reaches the workbench dispatcher under its own name', array_diff( [ 'documents/list', 'documents/create', 'documents/save', 'documents/delete', 'library/list', 'library/compose', 'library/preview', 'content/keys', 'content/save', 'content/type-create', 'content/image-create' ], array_keys( $actions ) ) === []
	&& $actions['documents/list'] === [ \Nino\Modules\Templates\Documents::class, 'apiList' ] );

$templateGet = response();
$templateGet['/nino/http/response']['header']['Content-Security-Policy'] = "default-src 'self'; style-src 'self' 'unsafe-inline'";
\Nino\Modules\Templates::callbackResponse( $appData, $templateGet );
check( 'the workbench page permits the sandboxed data-font previews without widening the global CSP', str_contains( $templateGet['/nino/http/response']['header']['Content-Security-Policy'], "font-src 'self' data:" )
	&& str_contains( $templateGet['/nino/http/response']['header']['Content-Security-Policy'], "default-src 'self'" ) );

$notAuthed = response();
check( 'guard shares the workbench session and rejects unauthenticated requests', \Nino\Modules\Templates\Admin::guard( $appData, $notAuthed ) === false && $notAuthed['/nino/http/response']['statusCode'] === 401 );

$notAuthedList = response();
\Nino\Modules\Templates\Documents::apiList( $appData, $notAuthedList );
check( 'every action guards itself rather than trusting the dispatcher', $notAuthedList['/nino/http/response']['statusCode'] === 401 );

$panelMarkup = (string) file_get_contents( FEATURE. '/templates/panel.tpl' );
check( 'the panel is a fragment the workbench renders into its pane, not a page of its own', str_contains( $panelMarkup, '<html' ) === false
	&& str_contains( $panelMarkup, '[csrf]' ) === false
	&& str_contains( $panelMarkup, 'id="pd-app"' ) === true );

/*	The section source editor is the HTML+ Editor - in the panel, in both
	languages, and in every word written about it: the first name, an escape
	hatch (the Notausgang in German), said to a developer that it was a way
	out of the builder, which it is not. Nothing the feature ships under
	text/, templates/, assets/, docs/ or in its classes may still say it	*/
$editorText = [];
foreach( [ 'en_US', 'de_DE' ] as $editorLocale )
	$editorText[$editorLocale] = include FEATURE. '/text/'. $editorLocale. '.php';
check( 'the dialog\'s eyebrow is the key of the HTML+ Editor, which both languages carry', str_contains( $panelMarkup, '[[/_admin/templates/label/html-editor]]' )
	&& ( $editorText['en_US']['[[/_admin/templates/label/html-editor]]'] ?? '' ) === 'HTML+ Editor'
	&& ( $editorText['de_DE']['[[/_admin/templates/label/html-editor]]'] ?? '' ) === 'HTML+ Editor' );
$oldEditorName = [];
foreach( new RecursiveIteratorIterator( new RecursiveDirectoryIterator( FEATURE, FilesystemIterator::SKIP_DOTS ) ) as $editorFile )
	if( $editorFile->isFile() && in_array( $editorFile->getExtension(), [ 'php', 'js', 'css', 'tpl', 'md' ], true ) && str_contains( $editorFile->getPathname(), '/tests/' ) === false
		&& preg_match( '/escape[- ]hatch|Notausgang/i', (string) file_get_contents( $editorFile->getPathname() ) ) === 1 )
		$oldEditorName[] = substr( $editorFile->getPathname(), strlen( FEATURE ) + 1 );
check( 'nothing the feature ships still calls it an escape hatch or a Notausgang'. ( $oldEditorName === [] ? '' : ' - '. implode( ', ', $oldEditorName ) ), $oldEditorName === [] );

/*	The pane as the workbench renders it (\Nino\Admin\Panels::panesHtml()),
	with the panel's template where its shortcode stands. The head the kernel
	draws over every panel names it; the panel drew a bar of its own under
	that row, with the open document's name on one side and the save state,
	Delete and Save on the other. What it puts over its three columns now is
	those three and nothing else, and its script hands them to the head's
	actions slot at init (templates-js-smoke.js drives that). A kernel from
	before the head renders none, and the pane is read without one	*/
$paneDocument = new DOMDocument();
$previousErrors = libxml_use_internal_errors( true );
$paneDocument->loadHTML( '<?xml encoding="utf-8"?>'. str_replace( '[template '. $registry['templates']['template']. ']', $panelMarkup, \Nino\Admin\Panels::panesHtml( [ $registry['templates'] ] ) ), LIBXML_NOWARNING | LIBXML_NOERROR );
libxml_clear_errors();
libxml_use_internal_errors( $previousErrors );
$paneXpath = new DOMXPath( $paneDocument );
$paneHead = $paneXpath->query( '//div[@id="admin-content-templates"]/div[contains(concat(" ",normalize-space(@class)," ")," admin-panel-head ")]' );
$appChildren = array_map( fn( DOMElement $node ): string => $node->getAttribute('id'), iterator_to_array( $paneXpath->query( '//div[@id="pd-app"]/*' ) ) );
$rowControls = array_map( fn( DOMElement $node ): string => $node->getAttribute('id'), iterator_to_array( $paneXpath->query( '//div[@id="pd-app"]/div[@id="pd-top-actions"]/*' ) ) );
check( 'the rendered pane opens with the head that names the panel, and the panel puts nothing over its three columns but the three controls it hands to that head',
	( $paneHead->length === 0 || $paneXpath->query( './h2[contains(@class,"admin-panel-title")]', $paneHead->item(0) )->length === 1 )
	&& array_slice( $appChildren, 0, 2 ) === [ 'pd-top-actions', 'pd-shell' ]
	&& $rowControls === [ 'pd-save-state', 'pd-delete-template', 'pd-save' ] );

\Nino\Auth::insertUser( $appData, 'dev@example.com', 'correct horse battery staple', [ '/*' ] );
\Nino\Auth::loginUser( $appData, 'dev@example.com', 'correct horse battery staple' );

echo "\n";


// --- Presets and Composer --------------------------------------------------

echo "Library / Composer\n";

/*	The library is its directory: every library/<key>/manifest.php of
	version 3 that normalizes is offered, and the manifest's own weight says
	where - ascending, a manifest without one after every one with, by key.
	Nothing lists the presets a second time any more (the constant that did
	was a second place to keep in step, and a preset dropped into library/
	without a line there was one nobody could reach), so what is held is the
	rule itself, with two presets this test writes into the directory for the
	length of its run - a copy of static-content weighed into the middle, and
	one that names no weight - and takes out again when it ends, whatever
	ends it. Written before the first presets() call: the catalogue is read
	once per process	*/
$smokePresets = [ 'smoke-weighted' => 25, 'smoke-unweighted' => null ];
register_shutdown_function( static function() use ( $smokePresets ): void {
	foreach( array_keys( $smokePresets ) as $smokeKey )
		if( is_dir( FEATURE. '/library/'. $smokeKey ) === true )
			\Nino\Filesystem::removeDir( FEATURE. '/library/'. $smokeKey );
} );
foreach( $smokePresets as $smokeKey => $smokeWeight ) {
	$smokeDirectory = FEATURE. '/library/'. $smokeKey;
	mkdir( $smokeDirectory );
	foreach( glob( FEATURE. '/library/static-content/*.tpl' ) ?: [] as $smokeLayout )
		copy( $smokeLayout, $smokeDirectory. '/'. basename( $smokeLayout ) );
	$smokeManifest = include FEATURE. '/library/static-content/manifest.php';
	unset( $smokeManifest['weight'] );
	if( $smokeWeight !== null )
		$smokeManifest['weight'] = $smokeWeight;
	file_put_contents( $smokeDirectory. '/manifest.php', '<?php return '. var_export( $smokeManifest, true ). ';' );
}

$presets = \Nino\Modules\Templates\Library::presets();

$manifestWeights = [];
foreach( glob( FEATURE. '/library/*/manifest.php' ) ?: [] as $manifestPath )
	$manifestWeights[basename( dirname( $manifestPath ) )] = ( include $manifestPath )['weight'] ?? null;
$expectedOrder = array_keys( $manifestWeights );
usort( $expectedOrder, static fn( string $a, string $b ): int => match( true ) {
	$manifestWeights[$a] === null && $manifestWeights[$b] === null => strcmp( $a, $b ),
	$manifestWeights[$a] === null => 1,
	$manifestWeights[$b] === null => -1,
	default => ( $manifestWeights[$a] <=> $manifestWeights[$b] ) ?: strcmp( $a, $b ),
} );
check( 'offers every directory that carries a version-3 manifest, in the order the manifests weigh themselves, and nothing else', array_keys( $presets ) === $expectedOrder );
check( 'a preset written into the directory is offered without being listed anywhere, its weight placing it among the shipped ones', array_search( 'smoke-weighted', array_keys( $presets ), true ) === 2 );
check( '...and one that names no weight comes after every one that does', array_key_last( $presets ) === 'smoke-unweighted' );
// array_key_exists(), not isset(): the unweighted one is in the list as null
$shippedWeights = array_filter( $manifestWeights, static fn( string $key ): bool => array_key_exists( $key, $smokePresets ) === false, ARRAY_FILTER_USE_KEY );
check( 'every shipped manifest names its weight, and no two share one - the order is meant, not the filesystem\'s', in_array( null, $shippedWeights, true ) === false && count( array_unique( $shippedWeights ) ) === count( $shippedWeights ) );

// Library::presets() swallows a broken manifest so one bad preset cannot take
// the whole catalog down. That is right at runtime and wrong here: the check
// above then just reports a shorter list, and the first test to reach for the
// missing preset dies on a null far from the cause. Re-normalize each shipped
// manifest outside the try/catch so the authoring mistake names itself.
$presetLoadErrors = [];
foreach( glob( FEATURE. '/library/*/manifest.php' ) ?: [] as $manifestPath ) {
	$presetKey = basename( dirname( $manifestPath ) );
	try {
		\Nino\Modules\Templates\AreaComposer::normalizePreset( $presetKey, include $manifestPath, dirname( $manifestPath ) );
	} catch( \Throwable $error ) {
		$presetLoadErrors[] = $presetKey. ': '. $error->getMessage();
	}
}
check( 'every shipped manifest normalizes'. ( $presetLoadErrors === [] ? '' : ' - '. implode( ' | ', $presetLoadErrors ) ), $presetLoadErrors === [] );
check( 'every preset has searchable metadata and a normalized v3 contract', array_filter( $presets, fn( array $preset ): bool => $preset['name'] === ''
	|| $preset['category'] === ''
	|| $preset['tags'] === []
	|| $preset['version'] !== 3
	|| $preset['areas'] === []
	|| $preset['layouts'] === [] ) === [] );
/*	What an area may hold is defined once, in its own render map: every type it
	allows has its entry there, so the panel needs no copy of the whole
	component catalogue beside each preset	*/
$renderGaps = [];
foreach( $presets as $renderPreset => $renderDefinition ) {
	if( array_key_exists( 'componentCatalog', $renderDefinition ) === true )
		$renderGaps[] = $renderPreset. ' carries a component catalogue';
	foreach( $renderDefinition['areas'] as $renderArea => $renderAreaDefinition )
		if( array_diff( $renderAreaDefinition['allowed'], array_keys( $renderAreaDefinition['render'] ) ) !== [] )
			$renderGaps[] = $renderPreset. ':'. $renderArea. ' allows a type it has no render entry for';
}
check( 'every area defines each type it allows, and no preset carries the whole catalogue beside its areas'. ( $renderGaps === [] ? '' : ' - '. implode( ' | ', $renderGaps ) ), $renderGaps === [] );
// The panel shows an area's name in the interface language, the server
// composes stored strings from the English one - so every area carries
// both, and the two have to say the same thing in English
$areaLabelText = [];
foreach( [ 'en_US', 'de_DE' ] as $areaLabelLocale )
	$areaLabelText[$areaLabelLocale] = include __DIR__. '/../text/'. $areaLabelLocale. '.php';
$areaLabelErrors = [];
foreach( $presets as $areaLabelPreset => $areaLabelDefinition )
	foreach( $areaLabelDefinition['areas'] as $areaLabelKey => $areaLabelArea ) {
		$where = $areaLabelPreset. ':'. $areaLabelKey;
		if( $areaLabelArea['labelKey'] === '' ) {
			$areaLabelErrors[] = $where. ' has no labelKey';
			continue;
		}
		foreach( $areaLabelText as $areaLabelLocale => $areaLabelFills )
			if( isset( $areaLabelFills[ '[['. $areaLabelArea['labelKey']. ']]' ] ) === false )
				$areaLabelErrors[] = $where. ' misses '. $areaLabelLocale;
		if( ( $areaLabelText['en_US'][ '[['. $areaLabelArea['labelKey']. ']]' ] ?? null ) !== $areaLabelArea['label'] )
			$areaLabelErrors[] = $where. ' English fill differs from its label';
	}
check( 'every area names itself twice - a fill key for the panel, English for what gets stored'. ( $areaLabelErrors === [] ? '' : ' - '. implode( ' | ', $areaLabelErrors ) ), $areaLabelErrors === [] );
$areaLabelManifest = include __DIR__. '/../library/articles-grid/manifest.php';
$areaLabelManifest['areas']['heading']['labelKey'] = 'Title area';
check( 'an area carries no text the panel never shows', array_filter( $presets, fn( array $preset ): bool => array_filter( $preset['areas'], fn( array $area ): bool => array_key_exists( 'help', $area ) ) !== [] ) === [] );
check( 'an area label key that is not a fill key is dropped rather than shown as text', \Nino\Modules\Templates\AreaComposer::normalizePreset( 'bad-label-key', $areaLabelManifest, __DIR__. '/../library/articles-grid' )['areas']['heading']['labelKey'] === '' );

$libraryRequest = response();
\Nino\Modules\Templates\Library::apiList( $appData, $libraryRequest );
$libraryBody = $libraryRequest['/nino/http/response']['body'];
$publicPresets = $libraryBody['presets'];
check( 'library API supplies editable v3 defaults without leaking Layout source', array_filter( $publicPresets, fn( array $preset ): bool => $preset['version'] === 3
	&& ( !isset( $preset['defaults']['areas'] ) || isset( $preset['_layouts'] ) ) ) === [] );
/*	What a section can be is the manifests' to say and nobody else's: the
	answer carries the presets, the frame choices and fallbacks every preset shares and the
	project stylesheet for the previews - and no second list of section kinds
	beside the presets, which is what Composer::modules() used to add to it	*/
check( 'the library answers its presets and what every preset shares, and no second catalogue of sections beside them', isset( $libraryBody['presets'], $libraryBody['choices'], $libraryBody['fallbacks'], $libraryBody['previewCss'] ) === true
	&& array_key_exists( 'modules', $libraryBody ) === false );
// The panel resolves Auto with the compiler's own table rather than a copy of it
$libraryFallbacks = $libraryBody['fallbacks'] ?? [];
check( 'the frame fallbacks it answers are the compiler\'s, one per axis, each one of that axis\' choices', $libraryFallbacks !== []
	&& count( $libraryFallbacks ) === count( $libraryBody['choices'] ) && array_diff_key( $libraryFallbacks, $libraryBody['choices'] ) === []
	&& array_filter( $libraryFallbacks, fn( string $value, string $axis ): bool => $value === 'auto' || in_array( $value, $libraryBody['choices'][$axis], true ) === false, ARRAY_FILTER_USE_BOTH ) === []
	&& $libraryFallbacks === \Nino\Modules\Templates\AreaComposer::fallbacks() );
check( 'library API refreshes and embeds project CSS for request-free previews', str_contains( $libraryBody['previewCss'], 'template-preview-project-css' )
	&& str_contains( $libraryBody['previewCss'], 'stale-template-preview-css' ) === false );
check( 'sandbox previews inline local fonts and discard unresolved remote font rules', str_contains( $libraryBody['previewCss'], 'data:font/woff2;base64,'. base64_encode('preview-font') )
	&& str_contains( $libraryBody['previewCss'], '/fonts/text/preview.woff2' ) === false
	&& str_contains( $libraryBody['previewCss'], 'example.invalid' ) === false );

$areaPresetDirectory = $sandbox. '/area-preset';
mkdir( $areaPresetDirectory );
file_put_contents( $areaPresetDirectory. '/section.tpl', "[[area:first]]\n[[area:second]]\n" );
$multiAreaManifest = [
	'name' => 'Two collections', 'description' => 'Two independent repeatable areas.',
	'category' => 'Test', 'tags' => [ 'two', 'collections' ], 'version' => 3,
	'layouts' => [ 'default' => [ 'label' => 'Default', 'template' => 'section.tpl' ] ],
	'areas' => [
		'first' => [
			'label' => 'First', 'source' => 'elements', 'allowed' => [ 'title' ],
			'model' => [ 'title' => [ 'type' => 'string', 'locale' => true ] ],
			'recommend' => [ 'components' => [ [ 'id' => 'title', 'type' => 'title', 'bindings' => [ 'text' => 'title' ] ] ] ],
		],
		'second' => [
			'label' => 'Second', 'source' => 'elements', 'allowed' => [ 'title' ],
			'model' => [ 'title' => [ 'type' => 'string', 'locale' => true ] ],
			'recommend' => [ 'components' => [ [ 'id' => 'title', 'type' => 'title', 'bindings' => [ 'text' => 'title' ] ] ] ],
		],
	],
];
$multiAreaPreset = \Nino\Modules\Templates\AreaComposer::normalizePreset( 'two-collections', $multiAreaManifest, $areaPresetDirectory );
$multiAreaResult = \Nino\Modules\Templates\AreaComposer::compose(
	\Nino\Modules\Templates\AreaComposer::defaults( $multiAreaPreset, 'page-home', 'related' ),
	$multiAreaPreset
);
check( 'one preset can compose several independent Elements Areas', count( $multiAreaResult['content']['collections'] ) === 2
	&& $multiAreaResult['content']['collections'][0]['elementType'] === 'page-home-related-first'
	&& $multiAreaResult['content']['collections'][1]['elementType'] === 'page-home-related-second' );
check( 'what the compose answer says about content is the collections it creates - the one part of it the panel reads', array_keys( $multiAreaResult['content'] ) === [ 'collections' ] );
file_put_contents( $areaPresetDirectory. '/unsafe.tpl', "<?php echo 'unsafe'; ?>\n[[area:first]]\n[[area:second]]\n" );
$unsafeManifest = $multiAreaManifest;
$unsafeManifest['layouts']['default']['template'] = 'unsafe.tpl';
check( 'Area manifests reject executable Layout source', throwsInvalidArgument( fn() => \Nino\Modules\Templates\AreaComposer::normalizePreset( 'unsafe-layout', $unsafeManifest, $areaPresetDirectory ) ) );

$dataAttributeManifest = [
	'name' => 'Data attributes', 'description' => 'Frontend behavior configured by declared attributes.',
	'category' => 'Test', 'tags' => [ 'data', 'attributes' ], 'version' => 3,
	'data' => [ 'vpa-delay' => '150ms', 'cover-width' => '80' ],
	'layouts' => [ 'default' => [ 'label' => 'Default', 'template' => 'section.tpl', 'data' => [ 'vpa-delay' => '300ms' ] ] ],
	'areas' => [
		'first' => [
			'label' => 'Cards', 'source' => 'elements', 'allowed' => [ 'title', 'button' ],
			'item' => [ 'tag' => 'article', 'class' => 'nino-article nino-autoheight', 'data' => [ 'autoheight-group' => 'cards-[[section:id]]', 'data-autoheight-mobile' => 'skip' ] ],
			'model' => [ 'title' => [ 'type' => 'string', 'locale' => true ] ],
			'render' => [ 'button' => [ 'class' => 'nino-modal-trigger', 'data' => [ 'Modal-Target' => 'contact "one" & <two>' ] ] ],
			'recommend' => [ 'components' => [
				[ 'id' => 'title', 'type' => 'title', 'bindings' => [ 'text' => 'title' ] ],
				[ 'id' => 'more', 'type' => 'button', 'bindings' => [ 'label' => 'title', 'href' => 'title' ] ],
			] ],
		],
		'second' => [
			'label' => 'Tabs', 'source' => 'single', 'allowed' => [ 'title' ],
			'container' => [ 'class' => 'nino-grid-100 nino-tabs', 'data' => [ 'tabs-target' => 'panel-1' ] ],
			'recommend' => [ 'components' => [ [ 'id' => 'title', 'type' => 'title' ] ] ],
		],
	],
];
$dataAttributePreset = \Nino\Modules\Templates\AreaComposer::normalizePreset( 'data-attributes', $dataAttributeManifest, $areaPresetDirectory );
$dataAttributeInput = \Nino\Modules\Templates\AreaComposer::defaults( $dataAttributePreset, 'page-home', 'stage' );
$dataAttributeSource = \Nino\Modules\Templates\AreaComposer::compose( $dataAttributeInput, $dataAttributePreset )['source'];
$dataAttributeSection = strtok( $dataAttributeSource, "\n" );
check( 'a Layout data attribute overrides the preset default on the section element', str_contains( $dataAttributeSection, 'data-cover-width="80"' )
	&& str_contains( $dataAttributeSection, 'data-vpa-delay="300ms"' )
	&& substr_count( $dataAttributeSource, 'data-vpa-delay' ) === 1 );
check( 'Areas emit declared data attributes on their container and collection item', str_contains( $dataAttributeSource, '<div class="nino-grid-100 nino-tabs" data-tabs-target="panel-1">' )
	&& str_contains( $dataAttributeSource, 'class="nino-article nino-autoheight" data-autoheight-group="cards-stage" data-autoheight-mobile="skip"' ) );
check( 'component render overrides carry escaped data attributes', str_contains( $dataAttributeSource, 'data-modal-target="contact &quot;one&quot; &amp; &lt;two&gt;"' ) );
$injectedInput = $dataAttributeInput;
$injectedInput['data'] = [ 'injected' => 'yes' ];
$injectedInput['areas']['first']['item'] = [ 'tag' => 'article', 'data' => [ 'injected' => 'yes' ] ];
$injectedInput['areas']['second']['container'] = [ 'tag' => 'div', 'data' => [ 'injected' => 'yes' ] ];
$injectedInput['areas']['second']['components'][0]['data'] = [ 'injected' => 'yes' ];
check( 'browser data never adds an attribute the manifest did not declare', str_contains( \Nino\Modules\Templates\AreaComposer::compose( $injectedInput, $dataAttributePreset )['source'], 'injected' ) === false );
$dataAttributeRejects = function( array $data, string $target ) use ( $dataAttributeManifest, $areaPresetDirectory ): bool {
	$manifest = $dataAttributeManifest;
	match( $target ) {
		'preset' => $manifest['data'] = $data,
		'layout' => $manifest['layouts']['default']['data'] = $data,
		'item' => $manifest['areas']['first']['item']['data'] = $data,
		'render' => $manifest['areas']['first']['render']['button']['data'] = $data,
	};
	return throwsInvalidArgument( fn() => \Nino\Modules\Templates\AreaComposer::normalizePreset( 'data-attributes', $manifest, $areaPresetDirectory ) );
};
check( 'declared data attributes cannot break out of the attribute or the data- namespace', $dataAttributeRejects( [ 'x" onclick="alert(1)' => '1' ], 'item' )
	&& $dataAttributeRejects( [ 'group name' => '1' ], 'render' )
	&& $dataAttributeRejects( [ 'group' => [ 'unsupported' ] ], 'item' )
	&& $dataAttributeRejects( [ 'group' => "line\nbreak" ], 'item' )
	&& $dataAttributeRejects( [ 'group' => str_repeat( 'a', 241 ) ], 'item' ) );
check( 'the frame keeps ownership of the data attributes it writes itself', $dataAttributeRejects( [ 'cover-height' => '50' ], 'preset' )
	&& $dataAttributeRejects( [ 'data-cover-height' => '50' ], 'layout' ) );
// A '[[field]]' in a data value is resolved per record by the [elements] pass,
// which escapes an ordinary field for the attribute but runs an 'html' => true
// one through sanitizeHtml() - and that leaves '"' intact, so editor content
// would close the attribute and land a live event handler on the card
$richFieldManifest = $dataAttributeManifest;
$richFieldManifest['areas']['first']['model']['blurb'] = [ 'type' => 'string', 'html' => true ];
$richFieldRejects = function( array $data, string $target ) use ( $richFieldManifest, $areaPresetDirectory ): bool {
	$manifest = $richFieldManifest;
	match( $target ) {
		'item' => $manifest['areas']['first']['item']['data'] = $data,
		'render' => $manifest['areas']['first']['render']['button']['data'] = $data,
	};
	return throwsInvalidArgument( fn() => \Nino\Modules\Templates\AreaComposer::normalizePreset( 'rich-field-data', $manifest, $areaPresetDirectory ) );
};
check( 'a data attribute cannot carry a rich text field, whose value is sanitized for content rather than for an attribute', $richFieldRejects( [ 'filter-item' => '[[blurb]]' ], 'item' )
	&& $richFieldRejects( [ 'filter-item' => 'prefix [[blurb]] suffix' ], 'render' )
	// ...while an ordinary field and the compile token stay available
	&& $richFieldRejects( [ 'filter-item' => '[[title]]' ], 'item' ) === false
	&& $richFieldRejects( [ 'group' => 'cards-[[section:id]]' ], 'item' ) === false );
check( 'repeatable articles recommend a localized CTA label', ( $presets['articles-grid']['areas']['articles']['model']['linkLabel']['locale'] ?? false ) === true );
check( 'every curated preset composes with its defaults', array_filter( array_keys( $presets ), function( string $key ): bool {
	try {
		\Nino\Modules\Templates\Composer::compose( [ 'preset' => $key, 'pageId' => 'test', 'id' => str_replace( '_', '-', $key ) ] );
		return false;
	} catch( \Throwable ) {
		return true;
	}
} ) === [] );

$heroInput = \Nino\Modules\Templates\AreaComposer::defaults( $presets['hero-fullscreen-image'], 'page-home', 'main-hero' );
$heroInput['pageMotion'] = 'on';
$heroInput['areas']['content']['components'][3]['bindings']['href'] = '/_nino/webpage/contact/uri';
$heroInput['areas']['content']['components'][3]['bindingSources']['href'] = 'textfill';
$hero = \Nino\Modules\Templates\Composer::compose( $heroInput );

check( 'composes one ordinary section', str_starts_with( $hero['source'], '<section' ) && str_contains( $hero['source'], '</section>' ) );
check( 'writes stable section metadata inside generated source', str_contains( $hero['source'], '<!-- nino:section {' ) );
check( 'derives textfill keys from page and section ids', in_array( '/template/page-home/main-hero/title', array_column( $hero['fields'], 'key' ), true ) && str_contains( $hero['source'], '[[/template/page-home/main-hero/title]]' ) );
check( 'reports the generated background image slot', ( $hero['images'][0]['slot'] ?? '' ) === 'background' );
$background = \Nino\Modules\Templates\AreaComposer::backgroundDefinition();
check( '...with the label and size of the one background definition', ( $hero['images'][0]['label'] ?? null ) === $background['label']
	&& ( $hero['images'][0]['width'] ?? null ) === $background['width'] && ( $hero['images'][0]['height'] ?? null ) === $background['height'] );
$backgroundSize = $background['width']. '×'. $background['height'];
check( 'the "New image slot" option names the size a background slot is created with, in every language', array_filter( [ 'en_US', 'de_DE' ], fn( string $locale ): bool => str_contains( ( include __DIR__. '/../text/'. $locale. '.php' )['[[/_admin/templates/label/slot-new]]'] ?? '', $backgroundSize ) === false ) === [] );
check( 'inherits page motion into generated nino-vpa markup', preg_match( '/class="(?=[^"]*\bnino-grid-row\b)(?=[^"]*\bnino-vpa\b)[^"]*"/', $hero['source'] ) === 1 );
check( 'applies Area alignment without forcing it onto the section shell', str_contains( $hero['source'], 'nino-text-center' ) && str_contains( strtok( $hero['source'], "\n" ), 'nino-text-center' ) === false );
$contactBinding = array_values( array_filter( $hero['fields'], fn( array $field ): bool => $field['key'] === '/_nino/webpage/contact/uri' ) )[0] ?? null;
check( 'single-Area actions can reuse technical textfills without creating a new field', str_contains( $hero['source'], 'href="[[/_nino/webpage/contact/uri]]"' )
	&& ( $contactBinding['mode'] ?? '' ) === 'existing'
	&& ( $hero['spec']['areas']['content']['components'][3]['bindingSources']['href'] ?? '' ) === 'textfill' );
$missingHeroSources = \Nino\Modules\Templates\AreaComposer::defaults( $presets['hero-fullscreen-image'], 'page-home', 'missing-sources' );
unset( $missingHeroSources['areas']['content']['components'][0]['bindingSources'] );
check( 'section metadata must declare every Single-Area binding source', throwsInvalidArgument( fn() => \Nino\Modules\Templates\Composer::compose( $missingHeroSources ) ) );

$heroPreview = \Nino\Modules\Templates\Composer::preview( [
	'preset' => 'hero-fullscreen-image', 'pageId' => 'preview', 'id' => 'motion-hero', 'pageMotion' => 'on',
] );
check( 'preview strips VPA classes that would stay hidden without client scripts', $heroPreview !== null && str_contains( $heroPreview, 'nino-vpa' ) === false );
check( 'preview-only VPA cleanup never changes composed template source', str_contains( $hero['source'], 'nino-vpa' ) && str_contains( $hero['source'], 'nino-vpa--visible' ) === false );

// The same reasoning as the data attributes above, one layer in: a component
// property that renders inside an attribute (an image's alt, a button's href)
// takes the field value as the [elements] pass leaves it, and a field the
// model released for html comes out of sanitizeHtml() with its '"' intact -
// so it would close the attribute and land whatever follows on the element
$richBindingInput = \Nino\Modules\Templates\AreaComposer::defaults( $presets['articles-grid'], 'page-home', 'rich-binding' );
$richBindingImage = null;
foreach( $richBindingInput['areas']['articles']['components'] as $position => $component )
	if( $component['type'] === 'image' )
		$richBindingImage = $position;
$richAltInput = $richBindingInput;
$richAltInput['areas']['articles']['components'][$richBindingImage]['bindings']['alt'] = 'description';
$richAltInput['areas']['articles']['components'][$richBindingImage]['bindingSources']['alt'] = 'field';
check( 'a component cannot carry a rich text field in an attribute either', throwsInvalidArgument( fn() => \Nino\Modules\Templates\Composer::compose( $richAltInput ) ) );

$plainAltInput = $richBindingInput;
$plainAltInput['areas']['articles']['components'][$richBindingImage]['bindings']['alt'] = 'title';
$plainAltInput['areas']['articles']['components'][$richBindingImage]['bindingSources']['alt'] = 'field';
check( '...while an ordinary field stays available for it', throwsInvalidArgument( fn() => \Nino\Modules\Templates\Composer::compose( $plainAltInput ) ) === false );

$articleInput = \Nino\Modules\Templates\AreaComposer::defaults( $presets['articles-grid'], 'page-home', 'services' );
$articleInput['areas']['articles']['style'] = 'four-columns';
$articleInput['areas']['articles']['source'] = [
	'elementMode' => 'existing',
	'elementType' => 'services',
	'shortcode' => [ 'locale' => '', 'callback' => '', 'limit' => 4, 'query' => '' ],
];
$articleInput['areas']['articles']['components'][0]['bindings'] = [ 'src' => 'photo', 'alt' => 'name' ];
$articleInput['areas']['articles']['components'][1]['bindings'] = [ 'text' => 'name' ];
$articleInput['areas']['articles']['components'][2]['bindings'] = [ 'text' => 'copy' ];
$articleInput['areas']['articles']['components'][3]['bindings'] = [ 'label' => 'buttonLabel', 'href' => 'href' ];
$articles = \Nino\Modules\Templates\Composer::compose( $articleInput );

check( 'binds a repeatable Area to a chosen element type with all shortcode arguments', str_contains( $articles['source'], '[elements /services locale="" callback="" limit="4" query=""]' ) );
check( 'maps ordered components to compatible existing model fields', str_contains( $articles['source'], '[[name]]' )
	&& str_contains( $articles['source'], '[[photo]]' )
	&& str_contains( $articles['source'], '[[buttonLabel]]' ) );
check( 'returns each independently creatable collection and the complete recommended schema', isset( $articles['content']['collections'][0]['model']['image'], $articles['content']['collections'][0]['model']['linkLabel'] ) );
check( 'generated Elements images use Nino image storage under the public content prefix', str_contains( $articles['source'], '[[/nino/public]]/images/[[photo]]' ) && str_contains( $articles['source'], '/uploads/' ) === false );
// An Elements type takes any non-empty field key, so an area bound to one the
// project already has meets whatever that project called its fields. The
// composer only ever took lowerCamel, and refused the rest as "an unknown
// model field" - with an existing collection there is no model to be unknown
// to, so whoever hit it went looking for a field that was there all along
$underscoreInput = $articleInput;
$underscoreInput['id'] = 'underscore-fields';
$underscoreInput['areas']['articles']['components'][0]['bindings'] = [ 'src' => 'header_image', 'alt' => 'page-title' ];
$underscoreInput['areas']['articles']['components'][1]['bindings'] = [ 'text' => 'page-title' ];
$underscore = \Nino\Modules\Templates\Composer::compose( $underscoreInput );
check( 'a chosen collection may call its fields header_image or page-title', str_contains( $underscore['source'], '[[header_image]]' )
	&& str_contains( $underscore['source'], '[[page-title]]' ) );

$spacedInput = $articleInput;
$spacedInput['id'] = 'spaced-field';
$spacedInput['areas']['articles']['components'][1]['bindings'] = [ 'text' => 'no name' ];
$refusal = '';
try { \Nino\Modules\Templates\Composer::compose( $spacedInput ); }
catch( \InvalidArgumentException $exception ) { $refusal = $exception->getMessage(); }
check( 'and a name a fill could not carry is refused for what it is, not as a missing field', str_contains( $refusal, 'cannot render' )
	&& str_contains( $refusal, 'unknown model field' ) === false );

$missingArticleSources = $articleInput;
unset( $missingArticleSources['areas']['articles']['components'][1]['bindingSources'] );
check( 'section metadata must declare every Elements-Area binding source', throwsInvalidArgument( fn() => \Nino\Modules\Templates\Composer::compose( $missingArticleSources ) ) );

$sharedActionInput = $articleInput;
$sharedActionInput['id'] = 'shared-action';
$sharedActionInput['areas']['articles']['components'][3]['bindings']['href'] = '/_nino/webpage/contact/uri';
$sharedActionInput['areas']['articles']['components'][3]['bindingSources']['href'] = 'textfill';
$sharedActionInput['areas']['articles']['components'][3]['bindings']['label'] = 'Contact [now]';
$sharedActionInput['areas']['articles']['components'][3]['bindingSources']['label'] = 'fixed';
$sharedAction = \Nino\Modules\Templates\Composer::compose( $sharedActionInput );
check( 'repeatable components can combine Element fields, shared textfills and escaped fixed values', str_contains( $sharedAction['source'], 'href="[[/_nino/webpage/contact/uri]]"' )
	&& str_contains( $sharedAction['source'], 'Contact &#91;now&#93;' )
	&& str_contains( $sharedAction['source'], '[[name]]' ) );
$unsafeActionInput = $sharedActionInput;
$unsafeActionInput['areas']['articles']['components'][3]['bindings']['href'] = 'javascript:alert(1)';
$unsafeActionInput['areas']['articles']['components'][3]['bindingSources']['href'] = 'fixed';
check( 'fixed URL bindings reject executable schemes', throwsInvalidArgument( fn() => \Nino\Modules\Templates\Composer::compose( $unsafeActionInput ) ) );
$obfuscatedUnsafeActionInput = $unsafeActionInput;
$obfuscatedUnsafeActionInput['areas']['articles']['components'][3]['bindings']['href'] = "java\nscript:alert(1)";
check( 'fixed URL bindings reject control-character scheme obfuscation', throwsInvalidArgument( fn() => \Nino\Modules\Templates\Composer::compose( $obfuscatedUnsafeActionInput ) ) );
$invalidImageSourceInput = $articleInput;
$invalidImageSourceInput['areas']['articles']['components'][0]['bindings']['src'] = '/template/page-home/services/shared-image';
$invalidImageSourceInput['areas']['articles']['components'][0]['bindingSources']['src'] = 'textfill';
check( 'Elements images cannot bypass compatible field mappings', throwsInvalidArgument( fn() => \Nino\Modules\Templates\Composer::compose( $invalidImageSourceInput ) ) );

$textOnlyInput = $articleInput;
$textOnlyInput['id'] = 'plain-services';
$textOnlyInput['areas']['articles']['components'] = array_values( array_filter(
	$textOnlyInput['areas']['articles']['components'],
	fn( array $component ): bool => in_array( $component['type'], [ 'title', 'description' ], true )
) );
$textOnly = \Nino\Modules\Templates\Composer::compose( $textOnlyInput );
check( 'component order changes markup independently from the four-column Area Style', str_contains( $textOnly['source'], 'nino-grid-m-25' )
	&& str_contains( $textOnly['source'], '[[name]]' )
	&& str_contains( $textOnly['source'], '<img' ) === false
	&& str_contains( $textOnly['source'], '<a ' ) === false );
check( 'one v3 metadata comment preserves the complete graphical area model', substr_count( $textOnly['source'], '<!-- nino:section ' ) === 1
	&& $textOnly['spec']['version'] === 3
	&& count( $textOnly['spec']['areas']['articles']['components'] ) === 2 );
check( 'the Articles preset equalizes its card text per section so the calls to action line up', str_contains( $articles['source'], '<h3 class="nino-article-title nino-autoheight" data-autoheight-group="article-title-services" data-autoheight-mobile="skip">' )
	&& str_contains( $articles['source'], '<div class="nino-article-descr nino-autoheight" data-autoheight-group="article-descr-services" data-autoheight-mobile="skip">' )
	&& str_contains( $textOnly['source'], 'data-autoheight-group="article-title-plain-services"' )
	&& str_contains( $textOnly['source'], 'article-title-services"' ) === false );

$articlePreview = \Nino\Modules\Templates\Composer::preview( [
	'preset' => 'articles-grid', 'pageId' => 'preview', 'id' => 'articles',
	'areas' => [ 'articles' => [ 'style' => 'two-columns', 'source' => [ 'shortcode' => [ 'limit' => 2 ] ] ] ],
] );
check( 'renders real preview HTML with deterministic text and image fixtures', $articlePreview !== null && str_contains( $articlePreview, '<section' ) && str_contains( $articlePreview, 'data:image/svg+xml' )
	&& str_contains( $articlePreview, str_replace( '%n', '1', (string) ( $presets['articles-grid']['samples']['title'] ?? 'no sample' ) ) ) );

/*	What a preview shows is the preset's to say, like everything else about
	it: a textfill the section creates shows the value it is created with
	(its field's default), every other fill what the manifest names under
	'samples', and the preview's own mechanics answer the two project paths.
	The Composer used to answer from a table of its own, keyed by field name
	- a table that knew what a price is, what a table's columns are and what
	the contact form's company lines say, and fell back to the fill's name
	for eight fills the shipped presets ask for. Held over every shipped
	preset and layout: nothing a preview shows is left to that fallback	*/
$unanswered = [];
foreach( $presets as $previewKey => $previewPreset ) {
	if( str_starts_with( $previewKey, 'smoke-' ) === true )
		continue;
	foreach( array_keys( $previewPreset['layouts'] ) as $previewLayout ) {
		$composed = \Nino\Modules\Templates\Composer::compose( [ 'preset' => $previewKey, 'layout' => $previewLayout, 'pageId' => 'preview', 'id' => 'preview-'. $previewKey ], true );
		// An item's image is drawn by the preview itself, and a loop's own
		// counters are the loop's
		$previewSource = preg_replace( '#src=(["\'])[^"\']*/images/\[\[image\]\]\1#i', '', $composed['source'] ) ?? '';
		preg_match_all( '#\[\[([^\]]+)\]\]#', $previewSource, $previewFills );
		$previewSamples = \Nino\Modules\Templates\Composer::previewSamples( $previewPreset, $composed );
		foreach( array_unique( $previewFills[1] ) as $previewFill )
			if( in_array( $previewFill, [ '.id', '.value', '.count' ], true ) === false
				&& \Nino\Modules\Templates\Composer::previewSample( $previewFill, 0, $previewSamples ) === null )
				$unanswered[$previewKey. ':'. $previewFill] = true;
	}
}
check( 'every fill a shipped preset\'s preview shows is answered by the section itself or its manifest'. ( $unanswered === [] ? '' : ' - '. implode( ', ', array_slice( array_keys( $unanswered ), 0, 6 ) ). ( count( $unanswered ) > 6 ? ' and '. ( count( $unanswered ) - 6 ). ' more' : '' ) ), $unanswered === [] );

/*	A new text starts empty. What a section used to be created with - a
	title, a subtitle, a button reading 'Learn more' - was written into the
	page as if somebody had typed it, and a developer who did not read every
	field shipped it. The catalogue carries a sample instead, a fill key of
	the workbench's own: it is what the empty field shows as its placeholder
	and what the preview shows for it, said in the language of the workbench
	and stored nowhere. The preview reads the sample through a resolver, the
	way the library does through the workbench's fills - here one over the
	English text file	*/
$sampleFills = $areaLabelText['en_US'];
$sampleText = static fn( string $key ): string => (string) ( $sampleFills[ '[['. $key. ']]' ] ?? '' );
$heroPreview = (string) \Nino\Modules\Templates\Composer::preview( [ 'preset' => 'hero-cta', 'pageId' => 'preview', 'id' => 'hero' ], $sampleText );
$heroFields = \Nino\Modules\Templates\Composer::compose( [ 'preset' => 'hero-cta', 'pageId' => 'preview', 'id' => 'hero' ], true )['fields'];
check( 'a new section starts empty: no text it creates carries a default', $heroFields !== []
	&& array_filter( $heroFields, static fn( array $field ): bool => (string) $field['default'] !== '' ) === [] );
check( '...and the preview shows the sample of every empty field in the workbench\'s words', array_filter( $heroFields, static fn( array $field ): bool => (string) $field['sample'] === ''
	|| $sampleText( (string) $field['sample'] ) === ''
	|| str_contains( $heroPreview, $sampleText( (string) $field['sample'] ) ) === false ) === [] );
$sampleCatalogue = [];
foreach( \Nino\Modules\Templates\AreaComposer::catalog() as $sampleType => $sampleComponent )
	foreach( $sampleComponent['properties'] as $sampleProperty => $sampleDefinition )
		if( in_array( $sampleDefinition['kind'], [ 'text', 'textarea', 'url' ], true ) )
			$sampleCatalogue[$sampleType. '.'. $sampleProperty] = $sampleDefinition;
check( 'no text property of the catalogue has a default, and each names a sample', $sampleCatalogue !== []
	&& array_filter( $sampleCatalogue, static fn( array $definition ): bool => $definition['default'] !== '' || $definition['sample'] === '' ) === [] );
check( 'every sample is a fill of both text files, which carry the same keys', array_filter( $sampleCatalogue, static fn( array $definition ): bool => isset( $areaLabelText['en_US'][ '[['. $definition['sample']. ']]' ], $areaLabelText['de_DE'][ '[['. $definition['sample']. ']]' ] ) === false ) === []
	&& array_diff_key( $areaLabelText['en_US'], $areaLabelText['de_DE'] ) === []
	&& array_diff_key( $areaLabelText['de_DE'], $areaLabelText['en_US'] ) === [] );
check( 'the HTML+ component is the one default that stays: it is source the developer opens', str_contains( (string) \Nino\Modules\Templates\AreaComposer::catalog()['html']['properties']['source']['default'], 'Your own HTML+ here.' ) );
check( 'without a resolver an empty field shows its own name, never a placeholder from the catalogue', str_contains( (string) \Nino\Modules\Templates\Composer::preview( [ 'preset' => 'hero-cta', 'pageId' => 'preview', 'id' => 'hero' ] ), '>Title<' )
	&& str_contains( (string) \Nino\Modules\Templates\Composer::preview( [ 'preset' => 'hero-cta', 'pageId' => 'preview', 'id' => 'hero' ] ), 'Section title' ) === false );

// What the panel types goes with the preview, and goes through the sanitizer
// that saving it would
$typedKey = (string) $heroFields[0]['key'];
$typedInput = static fn( mixed $texts ): array => [ 'preset' => 'hero-cta', 'pageId' => 'preview', 'id' => 'hero', 'texts' => $texts ];
$typedPreview = (string) \Nino\Modules\Templates\Composer::preview( $typedInput( [ $typedKey => 'Typed <b>words</b> & "quotes"', '/template/preview/other/field' => 'Intruder' ] ), $sampleText );
check( 'a typed text replaces the sample of its field in the preview', str_contains( $typedPreview, 'Typed words & &quot;quotes&quot;' ) && str_contains( $typedPreview, $sampleText( (string) $heroFields[0]['sample'] ) ) === false );
check( '...made as safe as saving it makes it: no tag survives, the quote is an entity', str_contains( (string) \Nino\Modules\Templates\Composer::preview( $typedInput( [ $typedKey => '<script>x</script>"' ] ), $sampleText ), 'x&quot;' )
	&& str_contains( (string) \Nino\Modules\Templates\Composer::preview( $typedInput( [ $typedKey => '<script>x</script>"' ] ), $sampleText ), '<script' ) === false );
$hrefFields = array_values( array_filter( $heroFields, static fn( array $field ): bool => $field['control'] === 'url' ) );
check( '...and an address that reads javascript: reaches the preview as none, the pass that strips it running after the fills are in', $hrefFields !== []
	&& str_contains( (string) \Nino\Modules\Templates\Composer::preview( $typedInput( [ $hrefFields[0]['key'] => 'javascript:alert(1)' ] ), $sampleText ), 'javascript:' ) === false
	&& str_contains( (string) \Nino\Modules\Templates\Composer::preview( $typedInput( [ $hrefFields[0]['key'] => '/kontakt' ] ), $sampleText ), 'href="/kontakt"' ) );
check( '...a key the section has no field for is ignored', str_contains( $typedPreview, 'Intruder' ) === false );
check( '...an empty one still shows the sample', str_contains( (string) \Nino\Modules\Templates\Composer::preview( $typedInput( [ $typedKey => '' ] ), $sampleText ), $sampleText( (string) $heroFields[0]['sample'] ) ) );
check( '...a text is cut at 4000 bytes', str_contains( (string) \Nino\Modules\Templates\Composer::preview( $typedInput( [ $typedKey => str_repeat( 'x', 5000 ) ] ), $sampleText ), str_repeat( 'x', 4000 ) )
	&& str_contains( (string) \Nino\Modules\Templates\Composer::preview( $typedInput( [ $typedKey => str_repeat( 'x', 5000 ) ] ), $sampleText ), str_repeat( 'x', 4001 ) ) === false );
$tooMany = [ $typedKey => 'Typed' ];
for( $tooManyIndex = 0; $tooManyIndex < 100; $tooManyIndex++ )
	$tooMany['/template/preview/hero/extra-'. $tooManyIndex] = 'x';
$lateTyped = [];
for( $tooManyIndex = 0; $tooManyIndex < 100; $tooManyIndex++ )
	$lateTyped['/template/preview/hero/extra-'. $tooManyIndex] = 'x';
$lateTyped[$typedKey] = 'Typed';
check( '...another shape or a value that is no string is left out, and the preview still renders', ( \Nino\Modules\Templates\Composer::preview( $typedInput( $tooMany ), $sampleText ) ?? '' ) !== ''
	&& str_contains( (string) \Nino\Modules\Templates\Composer::preview( $typedInput( 'Typed' ), $sampleText ), '<section' )
	&& str_contains( (string) \Nino\Modules\Templates\Composer::preview( $typedInput( [ $typedKey => [ 'Typed' ] ] ), $sampleText ), $sampleText( (string) $heroFields[0]['sample'] ) ) );
// What follows the hundredth entry is dropped, what is before it is kept: the
// answer used to be nothing at all, so a section with a hundred and one texts
// showed every one of them as its sample
check( '...the first 100 entries are kept, the ones after them are left out', str_contains( (string) \Nino\Modules\Templates\Composer::preview( $typedInput( $tooMany ), $sampleText ), 'Typed' )
	&& str_contains( (string) \Nino\Modules\Templates\Composer::preview( $typedInput( $lateTyped ), $sampleText ), 'Typed' ) === false );

// The library resolves the samples through the workbench's own fills, in the
// workbench's language: here a fill that a sandbox would not have by itself
\Nino\Html::addFills( $appData, [ '[[/_admin/templates/sample/title-text]]' => 'Workbench sample title' ], '*' );
post( $typedInput( [] ) );
$samplePreviewRequest = response();
\Nino\Modules\Templates\Library::apiPreview( $appData, $samplePreviewRequest );
check( 'the preview action answers an empty field with the workbench\'s text for its sample', str_contains( (string) ( $samplePreviewRequest['/nino/http/response']['body']['html'] ?? '' ), 'Workbench sample title' ) );
post( $typedInput( [ $typedKey => 'Typed in the panel' ] ) );
$samplePreviewRequest = response();
\Nino\Modules\Templates\Library::apiPreview( $appData, $samplePreviewRequest );
check( '...and a typed one with what was typed', str_contains( (string) ( $samplePreviewRequest['/nino/http/response']['body']['html'] ?? '' ), 'Typed in the panel' ) );

$tablePreview = (string) \Nino\Modules\Templates\Composer::preview( [ 'preset' => 'static-table', 'pageId' => 'preview', 'id' => 'hours', 'layout' => 'default-elements' ] );
$tableSamples = $presets['static-table']['samples'] ?? [];
check( 'a loop field shows its manifest sample, %n numbering the items and a list giving each item its own', isset( $tableSamples['columnA'], $tableSamples['columnB'] ) && is_array( $tableSamples['columnB'] )
	&& str_contains( $tablePreview, str_replace( '%n', '1', $tableSamples['columnA'] ) ) && str_contains( $tablePreview, str_replace( '%n', '2', $tableSamples['columnA'] ) )
	&& str_contains( $tablePreview, $tableSamples['columnB'][0] ) && str_contains( $tablePreview, $tableSamples['columnB'][1] ) );

$samplesManifest = include __DIR__. '/../library/static-content/manifest.php';
$refusesSamples = static function( mixed $samples ) use ( $samplesManifest ): bool {
	$samplesManifest['samples'] = $samples;
	return throwsInvalidArgument( static fn() => \Nino\Modules\Templates\AreaComposer::normalizePreset( 'bad-samples', $samplesManifest, __DIR__. '/../library/static-content' ) );
};
check( 'a manifest\'s samples name fills and hold texts - anything else is refused like every other manifest mistake', $refusesSamples( [ 'not a fill' => 'x' ] )
	&& $refusesSamples( [ 'title' => 3 ] ) && $refusesSamples( [ 'title' => [] ] ) && $refusesSamples( [ 'title' => [ 'a', 2 ] ] ) && $refusesSamples( 'title' )
	&& $refusesSamples( [ 'title' => 'Item %n', '/project/company/contact/email' => 'a@b.c', 'price' => [ '1', '2' ] ] ) === false );
// The panel dims every area but the one being edited, and needs to be told
// where each one begins - a stored section is a file somebody reads and
// edits, and says nothing about a dialog
check( 'a preview marks every area, the single one and each item of a collection', $articlePreview !== null
	&& str_contains( $articlePreview, '<div class="nino-grid-100 nino-mb-3 nino-text-center" data-pd-area="heading">' )
	&& substr_count( $articlePreview, 'data-pd-area="articles"' ) === 2
	&& str_contains( $articlePreview, 'data-pd-area="action"' ) === false );
check( '...and the section that gets stored carries none of them', str_contains( $articles['source'], 'data-pd-area' ) === false
	&& str_contains( \Nino\Modules\Templates\Composer::compose( [ 'preset' => 'articles-grid', 'pageId' => 'preview', 'id' => 'articles' ] )['source'], 'data-pd-area' ) === false );
check( 'preview HTML contains no unresolved project shortcodes', $articlePreview !== null && str_contains( $articlePreview, '[[' ) === false && str_contains( $articlePreview, '[elements' ) === false && str_contains( $articlePreview, '[image' ) === false );
$twoColumnPreview = \Nino\Modules\Templates\Composer::preview( [
	'preset' => 'articles-grid', 'pageId' => 'preview', 'id' => 'two-columns',
	'areas' => [ 'articles' => [ 'style' => 'two-columns' ] ],
] );
$fourColumnPreview = \Nino\Modules\Templates\Composer::preview( [
	'preset' => 'articles-grid', 'pageId' => 'preview', 'id' => 'four-columns',
	'areas' => [ 'articles' => [ 'style' => 'four-columns' ] ],
] );
check( 'named-area preview mirrors the selected column count', substr_count( $twoColumnPreview ?? '', '<article' ) === 2
	&& substr_count( $fourColumnPreview ?? '', '<article' ) === 4 );

// --- articles-filterable-grid: the [elementvalues]-driven category filter --------

$filterInput = \Nino\Modules\Templates\AreaComposer::defaults( $presets['articles-filterable-grid'], 'page-home', 'services' );
$filterInput['areas']['elements']['source'] = [
	'elementMode' => 'existing',
	'elementType' => 'services',
	'shortcode' => [ 'locale' => '', 'callback' => '', 'limit' => -1, 'query' => '' ],
];
$filterSection = \Nino\Modules\Templates\Composer::compose( $filterInput );

check( 'articles-filterable-grid composes one ordinary section with both areas resolved', str_starts_with( $filterSection['source'], '<section' )
	&& str_contains( $filterSection['source'], '</section>' )
	&& str_contains( $filterSection['source'], '[[area:' ) === false );
check( 'the grid Area binds to the chosen collection with no limit, so the filter has the whole set to work with', str_contains( $filterSection['source'], '[elements /services locale="" callback="" limit="-1" query=""]' ) );
check( 'the hand-written filter block survives compilation untouched, [elementvalues] included', str_contains( $filterSection['source'], '[elementvalues /services key="category" sort="value"]' )
	&& str_contains( $filterSection['source'], 'class="nino-filter-nav"' )
	&& str_contains( $filterSection['source'], 'data-filter-value=""' ) );
check( 'each card is stamped with its own category as still-unresolved [[category]] text - the ordinary per-request [elements] render pass fills it in, not the compiler (docs/recipe-section-preset.md, "Complete manifest shape")', str_contains( $filterSection['source'], 'data-filter-item="[[category]]"' ) );
check( 'the card keeps its nino-article family styling, including the image class the catalog default omits', str_contains( $filterSection['source'], 'class="nino-article-img nino-article-img--maxheight"' )
	&& str_contains( $filterSection['source'], 'class="nino-article-descr"' ) );

// The button loop and the card loop have to read one collection, and the
// static block cannot know its name: a new Area mints '<page>-<section>-<area>'
// and Edit Section can rebind it later. [[section:collection:<area>]] resolves
// to whatever the Area is actually bound to, so all three cases agree - the
// plain first insert included, which a hard-coded slug always got wrong.
$collectionOf = function( string $source ): array {
	preg_match( '#\[elements /([a-z0-9_-]+) #', $source, $cards );
	preg_match( '#\[elementvalues /([a-z0-9_-]+) #', $source, $buttons );
	return [ $cards[1] ?? 'cards?', $buttons[1] ?? 'buttons?' ];
};
$defaultInsert = $collectionOf( \Nino\Modules\Templates\Composer::compose( [ 'preset' => 'articles-filterable-grid', 'pageId' => 'page-home', 'id' => 'work' ] )['source'] );
$reboundInput = $filterInput;
$reboundInput['id'] = 'services-rebound';
$reboundInput['areas']['elements']['source']['elementType'] = 'consulting';
$rebound = $collectionOf( \Nino\Modules\Templates\Composer::compose( $reboundInput )['source'] );
check( 'the filter buttons read the same collection as the cards - on a new Area, an existing one, and after a rebind', $defaultInsert === [ 'page-home-work-elements', 'page-home-work-elements' ]
	&& $collectionOf( $filterSection['source'] ) === [ 'services', 'services' ]
	&& $rebound === [ 'consulting', 'consulting' ] );
check( 'a collection token naming something that is not an Elements area of the preset is refused', throwsInvalidArgument( function() use ( $areaPresetDirectory, $multiAreaManifest ): void {
	file_put_contents( $areaPresetDirectory. '/bad-collection.tpl', "[[area:first]]\n[[section:collection:nope]]\n[[area:second]]\n" );
	$manifest = $multiAreaManifest;
	$manifest['layouts']['default']['template'] = 'bad-collection.tpl';
	\Nino\Modules\Templates\AreaComposer::normalizePreset( 'bad-collection', $manifest, $areaPresetDirectory );
} ) );

/*	A collection's fields are named by whoever writes the manifest, and a
	layout fills one of them inside a static [elements] loop over the area's
	collection as [[<field>]]. The composer used to refuse a layout for
	carrying [[content]], [[intro]], [[outro]], [[template]] or
	[[variant-class]] - a guard against the intro/content/outro layouts from
	before named areas, which no library has shipped since manifest version
	3 - so a field called content could be declared but never filled	*/
file_put_contents( $areaPresetDirectory. '/content-field.tpl', "[[area:first]]\n[elements /[[section:collection:first]]]<li>[[content]]</li>[/elements]\n[[area:second]]\n" );
$contentFieldManifest = $multiAreaManifest;
$contentFieldManifest['layouts']['default']['template'] = 'content-field.tpl';
$contentFieldManifest['areas']['first']['model']['content'] = [ 'type' => 'string', 'locale' => true ];
try {
	$contentFieldRefusal = '';
	$contentFieldPreset = \Nino\Modules\Templates\AreaComposer::normalizePreset( 'content-field', $contentFieldManifest, $areaPresetDirectory );
} catch( \InvalidArgumentException $exception ) {
	$contentFieldRefusal = $exception->getMessage();
	$contentFieldPreset = [];
}
check( 'a layout that fills a collection field named content is taken - the field is the manifest\'s to name', $contentFieldRefusal === ''
	&& isset( $contentFieldPreset['areas']['first']['model']['content'] ) === true && isset( $contentFieldPreset['layouts']['default'] ) === true );

$filterPreview = \Nino\Modules\Templates\Composer::preview( [ 'preset' => 'articles-filterable-grid', 'pageId' => 'preview', 'id' => 'grid' ] );
check( 'renders a real preview with sample filter buttons, no raw shortcode text left visible', $filterPreview !== null
	&& str_contains( $filterPreview, '[elementvalues' ) === false
	&& str_contains( $filterPreview, '[[' ) === false
	&& substr_count( $filterPreview, 'nino-filter-btn' ) === 4  // "All" + 3 sample categories
	&& str_contains( $filterPreview, '<article' ) === true );

$fullscreen = \Nino\Modules\Templates\Composer::compose( [
	'preset' => 'hero-fullscreen-image', 'pageId' => 'page-home', 'id' => 'stage', 'layout' => 'parallax',
] );
check( 'Layout changes real markup and can recommend a matching frame', str_contains( $fullscreen['source'], 'nino-parallex' )
	&& $fullscreen['effective']['layout'] === 'parallax'
	&& $fullscreen['effective']['frame']['background'] === 'parallax'
	&& ( $fullscreen['images'][0]['key'] ?? '' ) === '/template/page-home/stage/background' );

$backgroundInput = \Nino\Modules\Templates\AreaComposer::defaults( $presets['hero-fullscreen-image'], 'page-home', 'stage' );
$backgroundInput['frame']['backgroundImageSource'] = 'image';
$backgroundInput['frame']['backgroundImage'] = '/shared/hero-image';
$existingBackground = \Nino\Modules\Templates\Composer::compose( $backgroundInput );
check( 'a chosen existing image slot survives into the composed background', str_contains( $existingBackground['source'], '[image /shared/hero-image alt=""]' )
	&& ( $existingBackground['images'][0]['key'] ?? '' ) === '/shared/hero-image'
	&& ( $existingBackground['images'][0]['mode'] ?? '' ) === 'existing' );
$fixedBackgroundInput = $backgroundInput;
$fixedBackgroundInput['frame']['backgroundImageSource'] = 'fixed';
$fixedBackgroundInput['frame']['backgroundImage'] = '[[/nino/public]]/images/demo-00.jpg';
$fixedBackground = \Nino\Modules\Templates\Composer::compose( $fixedBackgroundInput );
check( 'a fixed background writes a plain image tag and requests no image slot', str_contains( $fixedBackground['source'], '<img src="[[/nino/public]]/images/demo-00.jpg" alt="">' )
	&& $fixedBackground['images'] === []
	&& str_contains( $fixedBackground['source'], '[image ' ) === false );
check( 'the fixed background choice round-trips through the section metadata', ( $fixedBackground['spec']['frame']['backgroundImageSource'] ?? '' ) === 'fixed'
	&& \Nino\Modules\Templates\Composer::compose( $fixedBackground['spec'] )['source'] === $fixedBackground['source'] );
$missingBackgroundSource = $backgroundInput;
unset( $missingBackgroundSource['frame']['backgroundImageSource'] );
check( 'a supplied background value requires its explicit source', throwsInvalidArgument( fn() => \Nino\Modules\Templates\Composer::compose( $missingBackgroundSource ) ) );
$rejectsBackground = function( string $source, string $value ) use ( $backgroundInput ): bool {
	$input = $backgroundInput;
	$input['frame']['backgroundImageSource'] = $source;
	$input['frame']['backgroundImage'] = $value;
	return throwsInvalidArgument( fn() => \Nino\Modules\Templates\Composer::compose( $input ) );
};
check( 'a fixed background rejects executable schemes, markup and shortcode brackets', $rejectsBackground( 'fixed', 'javascript:alert(1)' )
	&& $rejectsBackground( 'fixed', '/hero.jpg" onerror="alert(1)' )
	&& $rejectsBackground( 'fixed', '[elements /x]' )
	&& $rejectsBackground( 'fixed', '/images/../../private/config.php' )
	&& $rejectsBackground( 'fixed', '//example.invalid/hero.jpg' )
	&& $rejectsBackground( 'image', '/shared/../etc' ) );
check( 'a fixed background still allows the public prefix and ordinary project paths', str_contains( $fixedBackground['source'], '[[/nino/public]]' )
	&& $rejectsBackground( 'fixed', '/images/hero.jpg' ) === false
	&& $rejectsBackground( 'fixed', 'https://cdn.example.com/hero.jpg' ) === false );

$templateInput = \Nino\Modules\Templates\AreaComposer::defaults( $presets['articles-grid'], 'page-home', 'with-form' );
$templateInput['areas']['action']['components'][] = [
	'id' => 'form', 'type' => 'template', 'style' => 'auto', 'settings' => [ 'target' => 'same' ],
	'bindings' => [ 'path' => '/templates/form-contact' ],
	'bindingSources' => [ 'path' => 'template' ],
];
$templateSection = \Nino\Modules\Templates\Composer::compose( $templateInput );
check( 'Template is an ordered Area input rather than a gallery pseudo-section', str_contains( $templateSection['source'], '[template /templates/form-contact]' ) );

$includeInput = \Nino\Modules\Templates\AreaComposer::defaults( $presets['template-include'], 'page-home', 'form-contact' );
$includeInput['areas']['include']['components'][0]['bindings']['path'] = '/templates/form-contact';
$includeSection = \Nino\Modules\Templates\Composer::compose( $includeInput );
check( 'the focused reusable-template preset emits one normal managed section', str_contains( $includeSection['source'], '[template /templates/form-contact]' )
	&& substr_count( $includeSection['source'], '<section' ) === 1 );

$splitInput = \Nino\Modules\Templates\AreaComposer::defaults( $presets['image-content-split'], 'page-home', 'story' );
$splitInput['layout'] = 'media-right';
$splitSection = \Nino\Modules\Templates\Composer::compose( $splitInput );
$splitPreview = \Nino\Modules\Templates\Composer::preview( [ 'preset' => 'image-content-split', 'pageId' => 'preview', 'id' => 'story' ] );
check( 'a bound alt text does not leave its shortcode tail standing in the preview', $splitPreview !== null
	&& str_contains( $splitPreview, ']"]' ) === false
	&& str_contains( $splitPreview, '[image' ) === false
	&& substr_count( $splitPreview, '<img ' ) === 1 );
check( 'media split Layouts change semantic Area order without duplicating presets', strpos( $splitSection['source'], 'nino-p-2' ) !== false
	&& strpos( $splitSection['source'], 'nino-img-cover' ) !== false
	&& strpos( $splitSection['source'], 'nino-p-2' ) < strpos( $splitSection['source'], 'nino-img-cover' )
	&& count( $splitSection['images'] ) === 1 );

$everyLayout = [];
foreach( $presets as $presetKey => $preset )
	foreach( array_keys( $preset['layouts'] ) as $layoutKey )
		try {
			$everyLayout[$presetKey. '/'. $layoutKey] = \Nino\Modules\Templates\Composer::compose( [ 'preset' => $presetKey, 'pageId' => 'page-home', 'id' => $presetKey, 'layout' => $layoutKey ] )['source'];
		} catch( \Throwable $exception ) {
			$everyLayout[$presetKey. '/'. $layoutKey] = 'FAILED: '. $exception->getMessage();
		}
check( 'every Layout of every preset composes one section without an unresolved token', array_filter( $everyLayout, fn( string $source ): bool => str_starts_with( $source, '<section' ) === false
	|| substr_count( $source, '<section' ) !== 1
	|| str_contains( $source, '[[area:' )
	|| str_contains( $source, '[[section:id]]' ) ) === [] );
check( 'no Layout nests a second grid row inside the one the compiler writes', array_filter( $everyLayout, fn( string $source ): bool => substr_count( $source, 'class="nino-grid-row' ) > 1 ) === [] );
// One namespace, everywhere: the Builder, the presets and the design system itself
// all speak nino-*. The lookbehind keeps '-ui-'/'-js-' inside identifiers out of it,
// the lookahead the CSS system font keywords that merely look like classes.
/*	An address a Layout writes is the project's, and a site may sit in a
	subdirectory: a form that posts to "/.newsletter" posts beside a site at
	/shop, and the entry never arrives. [[/nino/dir]] is what the kernel's own
	templates put in front of every address (page-contact.tpl's action,
	frame-header.tpl's links) and what Nino.ui.js falls back to when a form
	names no action - so a Layout names the fill or names nothing	*/
$rootAbsolute = [];
foreach( glob( FEATURE. '/library/*/*.tpl' ) ?: [] as $layoutFile )
	if( preg_match( '/\b(?:action|href)="\//', (string) file_get_contents( $layoutFile ) ) === 1 )
		$rootAbsolute[] = basename( dirname( $layoutFile ) ). '/'. basename( $layoutFile );
check( 'no shipped Layout writes an address from the domain root - a site in a subdirectory posts and links within itself'. ( $rootAbsolute === [] ? '' : ' - '. implode( ', ', $rootAbsolute ) ), $rootAbsolute === [] );

$legacyClass = '/(?<![-\\w])(?:ui|js|sc)-(?!monospace|sans-serif|serif|rounded)[a-z0-9]/';
check( 'the Builder, the presets and the design system carry no legacy class prefix', array_filter( $everyLayout, fn( string $source ): bool => preg_match( $legacyClass, $source ) === 1 ) === []
	&& preg_match( $legacyClass, (string) file_get_contents( NINO. '/_nino/Nino.css' ) ) === 0
	&& array_filter( $presets, fn( array $preset ): bool => preg_match( $legacyClass, (string) json_encode( $preset ) ) === 1 ) === [] );

// articles-filterable-grid's wrapper has to carry a layout rule of its own: it sits
// between .nino-grid-row and the cards, so without one the cards stop being
// flex children and their .nino-grid-m-* widths render as stacked blocks. A
// string assertion cannot see that, but it can see the rule is there at all -
// unlike a pure behaviour hook such as .nino-autoheight, which Nino.ui.js
// drives and which has no rule (.nino-filter-item has one only to restate
// [hidden]).
check( 'the filter wrapper carries the layout rule its nested cards depend on', preg_match( '/\.nino-filter\s*\{[^}]*display:\s*flex/', (string) file_get_contents( NINO. '/_nino/Nino.css' ) ) === 1 );
check( 'a component step is a modifier of whichever class the preset gave it', str_contains( \Nino\Modules\Templates\Composer::compose( array_merge(
	\Nino\Modules\Templates\AreaComposer::defaults( $presets['hero-fullscreen-image'], 'page-home', 'loud-hero' ),
	[ 'areas' => [ 'content' => [ 'components' => [ [ 'id' => 'title', 'type' => 'title', 'style' => 'loud', 'bindings' => [ 'text' => 'title' ], 'bindingSources' => [ 'text' => 'new' ] ] ] ] ] ]
) )['source'], '<h2 class="nino-atf-title nino-atf-title--loud"' )
	&& str_contains( \Nino\Modules\Templates\Composer::compose( array_merge(
		\Nino\Modules\Templates\AreaComposer::defaults( $presets['static-content'], 'page-home', 'loud-copy' ),
		[ 'areas' => [ 'heading' => [ 'components' => [ [ 'id' => 'title', 'type' => 'title', 'style' => 'loud', 'bindings' => [ 'text' => 'title' ], 'bindingSources' => [ 'text' => 'new' ] ] ] ] ] ]
	) )['source'], '<h2 class="nino-section-title nino-section-title--loud"' )
	&& str_contains( json_encode( $presets ), 'nino-font-big' ) === false );

/*	The vocabulary is read from the catalogue rather than copied here: what is
	held is that every style it offers a title is such a modifier, named after
	the style, and that 'auto' is the preset's class alone - so a style added
	to the catalogue needs no line in this file, and one that renders as
	nothing does	*/
$titleStyles = \Nino\Modules\Templates\AreaComposer::catalog()['title']['styles'];
$titleWith = fn( string $style ): string => \Nino\Modules\Templates\Composer::compose( array_merge(
	\Nino\Modules\Templates\AreaComposer::defaults( $presets['static-content'], 'page-home', 'styled-copy' ),
	[ 'areas' => [ 'heading' => [ 'components' => [ [ 'id' => 'title', 'type' => 'title', 'style' => $style, 'bindings' => [ 'text' => 'title' ], 'bindingSources' => [ 'text' => 'new' ] ] ] ] ] ]
) )['source'];
check( 'every style the catalogue offers is such a modifier, and auto is the class alone', in_array( 'auto', $titleStyles, true ) === true
	&& str_contains( $titleWith( 'auto' ), '<h2 class="nino-section-title"' ) === true
	&& array_filter( array_diff( $titleStyles, [ 'auto' ] ), fn( string $style ): bool => str_contains( $titleWith( $style ), '<h2 class="nino-section-title nino-section-title--'. $style. '"' ) === false ) === [] );

/*	The same for the scrim: choices() is read, not copied. A scrim is one
	choice per image layer rather than three levels of its own, and that is
	what the composed section says - 'none' paints no scrim, every other
	choice paints exactly one, named after it, and 'auto' resolves to one of
	them or to none. A preset that recommends a value outside the vocabulary
	does not normalize at all, which "every shipped manifest normalizes" above
	already reports	*/
$overlayChoices = \Nino\Modules\Templates\AreaComposer::choices()['overlay'];
$scrimsOf = function( string $overlay ): array {
	$section = strtok( \Nino\Modules\Templates\Composer::compose( [ 'preset' => 'hero-fullscreen-image', 'pageId' => 'page-home', 'id' => 'scrim-'. $overlay, 'frame' => [ 'overlay' => $overlay ] ] )['source'], "\n" );
	preg_match_all( '/\b(?:nino-cover|nino-img-background|nino-parallex)--([a-z0-9-]+)/', $section, $matches );
	return $matches[1];
};
check( 'the scrim is one choice per image layer rather than three levels of its own', in_array( 'none', $overlayChoices, true ) === true
	&& $scrimsOf( 'none' ) === []
	&& count( $scrimsOf( 'auto' ) ) <= 1
	&& array_filter( array_diff( $overlayChoices, [ 'auto', 'none' ] ), fn( string $overlay ): bool => $scrimsOf( $overlay ) !== [ $overlay ] ) === [] );
check( 'overlay values outside the current vocabulary are rejected', throwsInvalidArgument( fn() => \Nino\Modules\Templates\Composer::compose( [
	'preset' => 'hero-fullscreen-image', 'pageId' => 'page-home', 'id' => 'invalid-overlay', 'frame' => [ 'overlay' => 'strong' ],
] ) ) );

$timeline = \Nino\Modules\Templates\Composer::compose( [ 'preset' => 'items-timeline', 'pageId' => 'page-home', 'id' => 'process' ] );
check( 'the timeline numbers its steps from the ordered list instead of storing the ordinal as content', str_contains( $timeline['source'], '<ol class="nino-timeline nino-timeline--counted">' )
	&& str_contains( $timeline['source'], '<li class="nino-timeline-step"><h4>[[title]]</h4>' )
	&& isset( $timeline['content']['collections'][0]['model']['step'] ) === false );
$stacked = \Nino\Modules\Templates\Composer::compose( [ 'preset' => 'items-timeline', 'pageId' => 'page-home', 'id' => 'process', 'layout' => 'stacked' ] );
check( 'its second Layout restacks the same steps instead of restyling the item', str_contains( $stacked['source'], 'nino-timeline--stacked' )
	&& str_contains( $stacked['source'], '<li class="nino-timeline-step">' ) );

$staticTable = \Nino\Modules\Templates\Composer::compose( [ 'preset' => 'static-table', 'pageId' => 'page-home', 'id' => 'hours', 'layout' => 'striped-elements' ] );
check( 'a static block reaches the section exactly as the Layout wrote it, loop and all', str_contains( $staticTable['source'], '<table class="nino-table nino-table--striped">' )
	&& str_contains( $staticTable['source'], '<tr><th>Service</th><th>Duration</th></tr>' )
	&& str_contains( $staticTable['source'], '[elements /example-rows limit="10"]' )
	&& str_contains( $staticTable['source'], '<tr><td>[[columnA]]</td><td>[[columnB]]</td></tr>' )
	&& $staticTable['content']['collections'] === [] );
check( 'its intro stays an ordinary textfill Area while the outro renders nothing at all', str_contains( $staticTable['source'], '[[/template/page-home/hours/title]]' )
	&& str_contains( $staticTable['source'], 'nino-mt-3' ) === false
	&& preg_match( '/\n[\t ]*\n/', $staticTable['source'] ) !== 1 );
$staticOutro = \Nino\Modules\Templates\Composer::compose( [
	'preset' => 'static-table', 'pageId' => 'page-home', 'id' => 'hours',
	'areas' => [ 'outro' => [ 'components' => [ [
		'id' => 'action', 'type' => 'button', 'style' => 'primary',
		'bindings' => [ 'label' => '', 'href' => '' ], 'bindingSources' => [ 'label' => 'new', 'href' => 'new' ],
	] ] ] ],
] );
check( 'and carries a closing action as soon as the outro gets one', str_contains( $staticOutro['source'], 'nino-mt-3' )
	&& str_contains( $staticOutro['source'], '[[/template/page-home/hours/action-label]]' ) );
$accordion = \Nino\Modules\Templates\Composer::compose( [ 'preset' => 'static-accordion', 'pageId' => 'page-home', 'id' => 'faq' ] );
check( 'a static block resolves [[section:id]], so two of them on one page stay independent', str_contains( $accordion['source'], 'name="faq-faq"' )
	&& str_contains( $accordion['source'], '[[section:id]]' ) === false );
$contact = \Nino\Modules\Templates\Composer::compose( [ 'preset' => 'form-contact', 'pageId' => 'page-home', 'id' => 'reach-us', 'layout' => 'split' ] );
check( 'the shipped forms keep their CSRF token, honeypot and per-section field ids', str_contains( $contact['source'], '[csrf]' )
	&& str_contains( $contact['source'], 'name="location"' )
	&& str_contains( $contact['source'], 'id="reach-us-email"' )
	&& str_contains( $contact['source'], 'for="reach-us-email"' )
	&& str_contains( $contact['source'], 'style="' ) === false );

/*	Read from the manifest rather than listed: every Layout pricing offers is
	a composition of its own - a pricing row each, and no two of them the same
	markup - so a Layout added to the manifest needs no line here, and one
	that points at another's template does. Compared without the metadata
	comment, which names the layout and would tell any two apart	*/
$pricingLayouts = array_keys( $presets['items-pricing']['layouts'] );
$pricingSections = array_map( fn( string $layout ): string => (string) preg_replace( '/<!-- nino:section .*? -->/s', '', $everyLayout['items-pricing/'. $layout] ?? '' ), $pricingLayouts );
check( 'every pricing Layout is a real composition of its own, and no two of them are the same', count( $pricingLayouts ) >= 2
	&& array_filter( $pricingSections, fn( string $section ): bool => str_contains( $section, 'nino-pricing-row' ) === false ) === []
	&& count( array_unique( $pricingSections ) ) === count( $pricingSections ) );

$pricing = \Nino\Modules\Templates\Composer::compose( [ 'preset' => 'items-pricing', 'pageId' => 'page-home', 'id' => 'plans', 'layout' => 'feature-middle' ] );
check( 'pricing emphasis is a Layout, not a second collection or a hidden item class', str_contains( $pricing['source'], 'nino-pricing-row nino-pricing-row--feature-middle' )
	&& str_contains( $pricing['source'], '<div class="nino-pricing-item">' )
	&& str_contains( $pricing['source'], '<div class="nino-pricing-price"><strong>[[price]]</strong><span>[[suffix]]</span></div>' ) );

check( 'the banner uses the static background layer rather than the scripted cover', str_contains( $everyLayout['image-banner/plain'], 'nino-img-background' )
	&& str_contains( $everyLayout['image-banner/plain'], 'nino-cover' ) === false
	&& str_contains( $everyLayout['image-banner/plain'], 'data-cover-height' ) === false );
/*	The HTML+ component: one place inside a composed section whose markup is
	the editor's own, with the section staying composed around it. The
	section's own HTML+ editor is the all-or-nothing version of this - it
	detaches the whole section from its preset.

	Its value is source, not a textfill, and it has to be: Text::sanitizeValue()
	turns every '[' and ']' into an entity, so a fill cannot carry a shortcode
	by construction, and strip_tags() or the inline allowlist takes the markup.
	It lives in the spec and travels with the section	*/
$catalog = \Nino\Modules\Templates\AreaComposer::catalog();
check( 'the catalog offers an HTML+ component whose value is source', isset( $catalog['html'] ) === true
	&& ( $catalog['html']['properties']['source']['kind'] ?? '' ) === 'source'
	&& isset( $catalog['html']['properties']['text'] ) === false );

$htmlSpec = \Nino\Modules\Templates\AreaComposer::defaults( $presets['static-content'], 'page-home', 'note' );
$htmlArea = array_key_first( $htmlSpec['areas'] );
$htmlWritten = '<div class="nino-grid-100"><p>Hallo <strong>Welt</strong> [[/project/company/general/name]] [image /demo]</p></div>';
$htmlSpec['areas'][$htmlArea]['components'] = [ [ 'id' => 'note', 'type' => 'html', 'style' => 'auto', 'settings' => [ 'target' => 'same' ], 'bindings' => [ 'source' => $htmlWritten ], 'bindingSources' => [ 'source' => 'source' ] ] ];
/*	Composed through a catch, like resolvedUri() in the kernel suite: every
	check below used to die here instead of failing, and a suite that dies
	reports no failed check at all - it reports nothing	*/
$htmlComposed = [ 'source' => '' ];
try { $htmlComposed = \Nino\Modules\Templates\Composer::compose( $htmlSpec ); } catch( \Throwable $htmlError ) {}

// Verbatim, with no wrapper of its own - the markup is what somebody wrote,
// and a div around it would be one more thing they cannot remove
check( 'an HTML+ component is written into the section exactly as it was typed', str_contains( $htmlComposed['source'], $htmlWritten ) === true );

// A fill and a shortcode survive, which is the whole difference from the
// rich-text a textfill can hold
check( '...with its fills and shortcodes intact, which is what HTML+ means here', str_contains( $htmlComposed['source'], '[[/project/company/general/name]]' ) === true
	&& str_contains( $htmlComposed['source'], '[image /demo]' ) === true );

// It needs no textfill created or filled, so it is no text binding either
$htmlFields = [];
try { $htmlFields = array_column( \Nino\Modules\Templates\AreaComposer::compose( $htmlSpec, $presets['static-content'] )['fields'] ?? [], 'slot' ); } catch( \Throwable $htmlError ) {}
check( '...and asks for no textfill, because its value is not in one', in_array( $htmlArea. '.note.source', $htmlFields, true ) === false );

/*	The spec travels in an html comment and now carries markup. '-->' inside a
	component's source would close the marker early and spill the rest of the
	spec onto the page as visible text, so it is refused - and every '>' in the
	marker is written as \u003e as well, which json_decode() reads back as
	itself. Two answers to one question, because the first depends on a list
	being complete	*/
check( 'the spec marker cannot be closed from inside the source it carries', str_contains( $htmlComposed['source'], '-->' ) === true
	&& substr_count( $htmlComposed['source'], '-->' ) === 1 );

// ...and it reads back, byte for byte, through the document model and a
// second compose - a section nobody can reopen is a section nobody can edit
$htmlRead = [ 'valid' => false, 'segment' => [] ];
$htmlAgain = '';
try {
	$htmlRead = \Nino\Modules\Templates\SectionDocument::inspectSection( $htmlComposed['source'] );
	$htmlAgain = \Nino\Modules\Templates\Composer::compose( $htmlRead['segment']['spec'] )['source'];
} catch( \Throwable $htmlError ) {}
$htmlBack = $htmlRead['segment']['spec']['areas'][$htmlArea]['components'][0]['bindings']['source'] ?? '';
check( 'the source reads back out of the marker unchanged', $htmlRead['valid'] === true && $htmlBack === $htmlWritten );
check( '...and composes to the same section again', $htmlAgain !== '' && $htmlAgain === $htmlComposed['source'] );

/*	What it may not carry, each with its own reason (see HTML_FORBIDDEN):
	a nested section is not what the document model reads back, the loading and
	scripting tags are a promise this component does not make - the HTML+
	editor asks for the whole section and says so - and '-->' is the marker	*/
$htmlRefuses = function( string $source ) use ( $htmlSpec, $htmlArea ): bool {
	$try = $htmlSpec;
	$try['areas'][$htmlArea]['components'][0]['bindings']['source'] = $source;
	return throwsInvalidArgument( fn() => \Nino\Modules\Templates\Composer::compose( $try ) );
};
check( 'a nested section is refused', $htmlRefuses( '<section>x</section>' ) === true );
check( 'so is anything that loads or scripts', $htmlRefuses( '<script>a</script>' ) === true
	&& $htmlRefuses( '<iframe src="x"></iframe>' ) === true && $htmlRefuses( '<form></form>' ) === true
	&& $htmlRefuses( '<style>a{}</style>' ) === true && $htmlRefuses( '<object></object>' ) === true && $htmlRefuses( '<embed>' ) === true );
check( '...whatever case it is written in', $htmlRefuses( '<ScRiPt>a</ScRiPt>' ) === true );
check( 'and so is the sequence that would close the spec marker', $htmlRefuses( 'a --> b' ) === true );
check( 'a source longer than the cap is refused rather than written', $htmlRefuses( str_repeat( 'x', 9000 ) ) === true );

/*	A collection renders its item once per element, and an HTML+ component
	written there is the item's own markup: its [[field]] placeholders are
	resolved per record by the [elements] pass, like the fills the catalogue's
	components write. It used to be refused in a collection area as "the same
	thing about every element"; a row template somebody writes by hand is
	exactly what the preset's fixed components cannot express	*/
// Normalized through a catch, like the HTML+ source above: a preset the
// composer refuses used to end the suite here rather than fail a check
$htmlCollectionPreset = [ 'areas' => [] ];
try {
	$htmlCollectionPreset = \Nino\Modules\Templates\AreaComposer::normalizePreset( 'html-collection',
		array_replace_recursive( $multiAreaManifest, [ 'areas' => [ 'first' => [ 'source' => 'elements', 'allowed' => [ 'title', 'html' ] ] ] ] ), $areaPresetDirectory );
} catch( \Throwable $htmlCollectionRefusal ) {}
check( 'the HTML+ component is offered in a collection area', isset( $htmlCollectionPreset['areas']['first']['render']['html'] ) === true );
$htmlCollection = [ 'source' => '' ];
try {
	$htmlCollectionSpec = \Nino\Modules\Templates\AreaComposer::defaults( $htmlCollectionPreset, 'page-home', 'rows' );
	$htmlCollectionSpec['areas']['first']['components'] = [ [ 'id' => 'row', 'type' => 'html', 'style' => 'auto', 'settings' => [ 'target' => 'same' ], 'bindings' => [ 'source' => '<p class="nino-section-text">[[title]] &middot; [[/project/company/general/name]]</p>' ], 'bindingSources' => [ 'source' => 'fixed' ] ] ];
	$htmlCollection = \Nino\Modules\Templates\AreaComposer::compose( $htmlCollectionSpec, $htmlCollectionPreset );
} catch( \Throwable $htmlCollectionError ) {}
/*	...and it is the item itself. The first version of this check asked only
	whether the source was somewhere inside the block, which '[[<p ...>]]' -
	the source taken for the name of a field and put in fill brackets - also
	satisfies. The item is held whole: the area's own element around the
	source and nothing around the source	*/
check( '...and its source is the item the [elements] pass repeats, [[field]] and all',
	preg_match( '#\[elements /[a-z0-9-]+[^\]]*\](.*?)\[/elements\]#s', $htmlCollection['source'], $htmlItem ) === 1
	&& $htmlItem[1] === '<article class="nino-grid-m-33"><p class="nino-section-text">[[title]] &middot; [[/project/company/general/name]]</p></article>'
	&& str_contains( $htmlCollection['source'], '[[<' ) === false );

// A collection's HTML+ starts as one paragraph with the first text field in it
$htmlLoopDefault = [ 'source' => '' ];
try {
	$htmlLoopDefaultSpec = \Nino\Modules\Templates\AreaComposer::defaults( $htmlCollectionPreset, 'page-home', 'rows' );
	$htmlLoopDefaultSpec['areas']['first']['components'] = [ [ 'id' => 'row', 'type' => 'html', 'style' => 'auto', 'settings' => [ 'target' => 'same' ], 'bindings' => [], 'bindingSources' => [ 'source' => 'source' ] ] ];
	$htmlLoopDefault = \Nino\Modules\Templates\AreaComposer::compose( $htmlLoopDefaultSpec, $htmlCollectionPreset );
} catch( \Throwable $htmlLoopDefaultError ) {}
check( 'a collection\'s HTML+ without a source of its own starts as one paragraph with the first text field in it', str_contains( $htmlLoopDefault['source'], '<article class="nino-grid-m-33"><p class="nino-section-text">[[title]]</p></article>' ) );

// ...while a single area starts it as the catalogue says, whatever its key
$htmlSingleDefault = [ 'source' => '' ];
try {
	$htmlSingleDefaultSpec = \Nino\Modules\Templates\AreaComposer::defaults( $presets['static-content'], 'page-home', 'note' );
	$htmlSingleDefaultSpec['areas'][$htmlArea]['components'] = [ [ 'id' => 'note', 'type' => 'html', 'style' => 'auto', 'settings' => [ 'target' => 'same' ], 'bindings' => [], 'bindingSources' => [ 'source' => 'source' ] ] ];
	$htmlSingleDefault = \Nino\Modules\Templates\AreaComposer::compose( $htmlSingleDefaultSpec, $presets['static-content'] );
} catch( \Throwable $htmlSingleDefaultError ) {}
check( 'a single area\'s HTML+ without a source of its own composes to the catalogue default, not to a text key', str_contains( $htmlSingleDefault['source'], '<p class="nino-section-text">Your own HTML+ here.</p>' )
	&& str_contains( $htmlSingleDefault['source'], 'note-source' ) === false );

// The first text field is a string without html: a number before it, or a rich text field, is not one
$loopSourceOf = static function( array $model ) use ( $multiAreaManifest, $areaPresetDirectory ): ?string {
	try {
		$manifest = $multiAreaManifest;
		$manifest['areas']['first'] = array_replace( $manifest['areas']['first'], [ 'source' => 'elements', 'allowed' => [ 'title', 'html' ], 'model' => $model ] );
		$preset = \Nino\Modules\Templates\AreaComposer::normalizePreset( 'html-loop-first', $manifest, $areaPresetDirectory );
		$spec = \Nino\Modules\Templates\AreaComposer::defaults( $preset, 'page-home', 'rows' );
		$spec['areas']['first']['components'] = [ [ 'id' => 'row', 'type' => 'html', 'style' => 'auto', 'settings' => [ 'target' => 'same' ], 'bindings' => [], 'bindingSources' => [ 'source' => 'source' ] ] ];
		$composed = \Nino\Modules\Templates\AreaComposer::compose( $spec, $preset );
	} catch( \Throwable ) {
		return null;
	}
	return preg_match( '#\[elements /[a-z0-9-]+[^\]]*\](.*?)\[/elements\]#s', $composed['source'], $item ) === 1 ? $item[1] : null;
};
$loopFields = $loopSourceOf( [ 'portrait' => [ 'type' => 'image' ], 'years' => [ 'type' => 'integer' ], 'bio' => [ 'type' => 'string', 'html' => true ], 'title' => [ 'type' => 'string' ] ] );
check( 'the first text field of a collection is the first string without html - not an image, a number or a rich text', $loopFields !== null
	&& str_contains( $loopFields, '<p class="nino-section-text">[[title]]</p>' ) && str_contains( $loopFields, '[[bio]]' ) === false && str_contains( $loopFields, '[[years]]' ) === false );
$loopNoText = $loopSourceOf( [ 'portrait' => [ 'type' => 'image' ], 'bio' => [ 'type' => 'string', 'html' => true ], 'years' => [ 'type' => 'integer' ], 'title' => [ 'type' => 'string', 'html' => true ] ] );
check( '...and a collection with none keeps the component\'s own default', $loopNoText !== null && str_contains( $loopNoText, 'Your own HTML+ here.' ) && str_contains( $loopNoText, '[[bio]]' ) === false && str_contains( $loopNoText, '[[title]]' ) === false );

/*	What it may not hold in a collection: the kernel's pattern ends an
	[elements] block at the first [/elements], so a block inside the item
	closes the outer one and takes the rest of the page with it; and a rich
	field - sanitized for content, with its '"' left alone - inside a tag
	could close an attribute and hand the editor's content an event handler,
	the rule a component's own property is held to as well	*/
$htmlLoopManifest = array_replace_recursive( $multiAreaManifest, [ 'areas' => [ 'first' => [
	'source' => 'elements', 'allowed' => [ 'title', 'html' ],
	'model' => [ 'blurb' => [ 'type' => 'string', 'html' => true ] ],
] ] ] );
$htmlLoopPreset = [ 'areas' => [] ];
try { $htmlLoopPreset = \Nino\Modules\Templates\AreaComposer::normalizePreset( 'html-loop-guards', $htmlLoopManifest, $areaPresetDirectory ); } catch( \Throwable $htmlLoopError ) {}
$htmlLoopRefuses = function( string $source, string $mode = 'new', string $message = '' ) use ( $htmlLoopPreset ): ?bool {
	if( $htmlLoopPreset['areas'] === [] )
		return null;
	$spec = \Nino\Modules\Templates\AreaComposer::defaults( $htmlLoopPreset, 'page-home', 'rows' );
	$spec['areas']['first']['source']['elementMode'] = $mode;
	$spec['areas']['first']['components'] = [ [ 'id' => 'row', 'type' => 'html', 'style' => 'auto', 'settings' => [ 'target' => 'same' ], 'bindings' => [ 'source' => $source ], 'bindingSources' => [ 'source' => 'source' ] ] ];
	try {
		\Nino\Modules\Templates\AreaComposer::compose( $spec, $htmlLoopPreset );
	} catch( \InvalidArgumentException $error ) {
		return str_contains( $error->getMessage(), $message );
	}
	return false;
};
check( 'a collection item refuses a nested [elements] block, whatever its case', $htmlLoopRefuses( '<p>[elements /x][[title]][/elements]</p>' ) === true
	&& $htmlLoopRefuses( '<p>a</p>[/ELEMENTS]' ) === true
	&& $htmlLoopRefuses( '<p>[[title]]</p>' ) === false );
check( '...and a rich text field inside a tag, in an attribute, in a tag or quote left open or as the tag name - while the same field in text content is accepted', $htmlLoopRefuses( '<p title="[[blurb]]">x</p>' ) === true
	&& $htmlLoopRefuses( '<a href=\'/x\' data-b="a > b [[blurb]]">x</a>' ) === true
	&& $htmlLoopRefuses( '<p class="[[blurb]]' ) === true
	&& $htmlLoopRefuses( '<p title="[[blurb]]>x</p>' ) === true
	&& $htmlLoopRefuses( '<[[blurb]]>' ) === true
	&& $htmlLoopRefuses( '<p class="[[blurb]]', 'new', 'inside a tag' ) === true
	&& $htmlLoopRefuses( '<[[blurb]]>', 'new', 'inside a tag' ) === true
	&& $htmlLoopRefuses( '<p title="[[title]]">[[blurb]]</p>' ) === false
	&& $htmlLoopRefuses( '<div>[[blurb]]</div><p title="x">[[title]]</p>' ) === false );
// The components of an area are set one after the other, so a tag one of them leaves open is closed by the next:
// the field is then inside an attribute although neither source shows it
$htmlOpenTag = static function( array $preset, array $sources, string $mode = 'new' ): ?string {
	if( $preset['areas'] === [] )
		return null;
	$spec = \Nino\Modules\Templates\AreaComposer::defaults( $preset, 'page-home', 'rows' );
	$spec['areas']['first']['source']['elementMode'] = $mode;
	$spec['areas']['first']['components'] = [];
	foreach( $sources as $index => $source )
		$spec['areas']['first']['components'][] = [ 'id' => 'row-'. $index, 'type' => 'html', 'style' => 'auto', 'settings' => [ 'target' => 'same' ], 'bindings' => [ 'source' => $source ], 'bindingSources' => [ 'source' => 'source' ] ];
	try {
		\Nino\Modules\Templates\AreaComposer::compose( $spec, $preset );
	} catch( \InvalidArgumentException $error ) {
		return $error->getMessage();
	}
	return '';
};
$openSplit = [ '<a title="', '[[blurb]]">x</a>' ];
check( '...a source whose last tag is left open is refused where the collection has a rich text field, so it cannot be closed by the next component',
	str_contains( (string) $htmlOpenTag( $htmlLoopPreset, $openSplit ), 'leaves a tag open' )
	&& str_contains( (string) $htmlOpenTag( $htmlLoopPreset, [ '<p>[[title]]</p><a href="/x" title="a > b', '[[blurb]]">x</a>' ] ), 'leaves a tag open' )
	&& str_contains( (string) $htmlOpenTag( $htmlLoopPreset, [ '<p>x</p><a class=', '[[blurb]]>x</a>' ] ), 'leaves a tag open' ) );
check( '...while a closed tag, the same field in text and an unknown collection are as they were', $htmlOpenTag( $htmlLoopPreset, [ '<a title="x">', '</a>[[blurb]]' ] ) === ''
	&& $htmlOpenTag( $htmlLoopPreset, [ '<p>[[blurb]]</p>', '<p>[[title]]</p>' ] ) === ''
	&& str_contains( (string) $htmlOpenTag( $htmlLoopPreset, $openSplit, 'existing' ), 'leaves a tag open' ) === false );
$htmlPlainManifest = $multiAreaManifest;
$htmlPlainManifest['areas']['first'] = array_replace( $htmlPlainManifest['areas']['first'], [ 'source' => 'elements', 'allowed' => [ 'title', 'html' ], 'model' => [ 'blurb' => [ 'type' => 'string' ], 'title' => [ 'type' => 'string' ] ] ] );
$htmlPlainPreset = [ 'areas' => [] ];
try { $htmlPlainPreset = \Nino\Modules\Templates\AreaComposer::normalizePreset( 'html-plain-loop', $htmlPlainManifest, $areaPresetDirectory ); } catch( \Throwable $htmlPlainError ) {}
check( '...and a collection with no rich text field has nothing to guard', str_contains( (string) $htmlOpenTag( $htmlPlainPreset, $openSplit ), 'leaves a tag open' ) === false && $htmlPlainPreset['areas'] !== [] );
check( '...a collection the project already has is known by its name alone, so there is no model to hold the field against', $htmlLoopRefuses( '<p title="[[blurb]]">x</p>', 'existing' ) === false );
check( '...while the template component stays a single-area one', throwsInvalidArgument( fn() => \Nino\Modules\Templates\AreaComposer::normalizePreset( 'template-collection',
	array_replace_recursive( $multiAreaManifest, [ 'areas' => [ 'first' => [ 'source' => 'elements', 'allowed' => [ 'title', 'template' ] ] ] ] ), $areaPresetDirectory ) ) );

// Every single area that takes anything but an image offers it, so an editor
// never has to pick a different preset to get one place of their own
$htmlAreas = 0; $openAreas = 0; $htmlLoops = 0;
foreach( $presets as $preset )
	foreach( $preset['areas'] as $area ) {
		if( array_keys( $area['render'] ) === [ 'image' ] ) continue;
		$openAreas++;
		if( isset( $area['render']['html'] ) ) $htmlAreas++;
		if( isset( $area['render']['html'] ) && $area['source'] === 'elements' ) $htmlLoops++;
	}
check( 'every area that is not image-only offers it, the six collections the shipped presets loop over included', $openAreas > 0 && $htmlAreas === $openAreas && $htmlLoops >= 6 );

check( 'list and table tags are available to Areas that need them, scripts and media are not', throwsInvalidArgument( fn() => \Nino\Modules\Templates\AreaComposer::normalizePreset( 'unsafe-tag', array_replace_recursive( $multiAreaManifest, [ 'areas' => [ 'first' => [ 'item' => [ 'tag' => 'iframe' ] ] ] ] ), $areaPresetDirectory ) )
	&& \Nino\Modules\Templates\AreaComposer::normalizePreset( 'list-tag', array_replace_recursive( $multiAreaManifest, [ 'areas' => [ 'first' => [ 'item' => [ 'tag' => 'li' ] ] ] ] ), $areaPresetDirectory )['areas']['first']['item']['tag'] === 'li' );

check( 'rejects invalid page ids', throwsInvalidArgument( fn() => \Nino\Modules\Templates\Composer::compose( [ 'preset' => 'articles-grid', 'pageId' => '../home', 'id' => 'intro' ] ) ) );
// A page's id is its template's category and a section's id a segment of a key: words
// joined by single hyphens, the first one starting with a letter - a page's
// always does, it begins with page-
check( 'ids are words joined by single hyphens, and a section\'s starts with a letter', throwsInvalidArgument( fn() => \Nino\Modules\Templates\Composer::compose( [ 'preset' => 'articles-grid', 'pageId' => 'page-home', 'id' => 'intro--text' ] ) )
	&& throwsInvalidArgument( fn() => \Nino\Modules\Templates\Composer::compose( [ 'preset' => 'articles-grid', 'pageId' => 'page-home', 'id' => 'intro-' ] ) )
	&& throwsInvalidArgument( fn() => \Nino\Modules\Templates\Composer::compose( [ 'preset' => 'articles-grid', 'pageId' => 'page-home', 'id' => '2intro' ] ) )
	&& \Nino\Modules\Templates\Composer::compose( [ 'preset' => 'hero-cta', 'pageId' => 'page-404', 'id' => 'hero' ] )['fields'][0]['key'] === '/template/page-404/hero/title'
	&& \Nino\Modules\Templates\Composer::compose( [ 'preset' => 'hero-cta', 'pageId' => 'page-2026-home', 'id' => 'hero' ] )['fields'][0]['key'] === '/template/page-2026-home/hero/title' );
check( 'a section with the id of its page keeps the category segment of its keys, and the type it proposes is the page, the section and the area', \Nino\Modules\Templates\Composer::compose( [ 'preset' => 'hero-cta', 'pageId' => 'page-home', 'id' => 'page-home' ] )['fields'][0]['key'] === '/template/page-home/page-home/title'
	&& \Nino\Modules\Templates\AreaComposer::defaults( $presets['articles-grid'], 'page-404', 'hero' )['areas']['articles']['source']['elementType'] === 'page-404-hero-articles' );
check( 'rejects invalid Area styles and element type paths', throwsInvalidArgument( fn() => \Nino\Modules\Templates\Composer::compose( [
	'preset' => 'articles-grid', 'pageId' => 'page-home', 'id' => 'intro',
	'areas' => [ 'articles' => [ 'style' => 'unknown' ] ],
] ) ) && throwsInvalidArgument( fn() => \Nino\Modules\Templates\Composer::compose( [
	'preset' => 'articles-grid', 'pageId' => 'page-home', 'id' => 'intro',
	'areas' => [ 'articles' => [ 'source' => [ 'elementType' => '../items' ] ] ],
] ) ) );

echo "\n";


// --- Lossless SectionDocument --------------------------------------------

echo "SectionDocument\n";

$source = "[template /templates/html-header]\n"
	. "<!-- <section id=\"not-real\"> -->\n"
	. "<?php \$sample = '<section>not markup either</section>'; ?>\n"
	. "<script>const sample = '</scripture><section>not markup</section>';</script>\n"
	. "<section id=\"hero\" class=\"nino-section\">\n\t<section class=\"nested\"><p>Nested</p></section>\n</section>\n"
	. "<section id='content'>[[/template/page-home/content/title]] [[/template/page-services/intro/title]] [[/template/common/form/submit]] [[/project/company/general/name]] [[/_nino/webpage/home/title]]</section>\n"
	. "[template /templates/html-footer]\n";

$parsed = \Nino\Modules\Templates\SectionDocument::split( $source, 'page-home' );
$joined = implode( '', array_column( $parsed['segments'], 'source' ) );
$sections = array_values( array_filter( $parsed['segments'], fn( array $segment ): bool => $segment['type'] === 'section' ) );
$templateSections = array_values( array_filter( $parsed['segments'], fn( array $segment ): bool => $segment['type'] === 'template' ) );

check( 'recognizes only top-level sections', $parsed['sectionCount'] === 2 );
check( 'promotes standalone template shortcodes to canvas components', count( $templateSections ) === 2 && $templateSections[0]['template'] === 'html-header' && $templateSections[1]['template'] === 'html-footer' );
check( 'counts HTML and template sections together as canvas components', $parsed['componentCount'] === 4 );
check( 'ignores section-like text in comments, PHP and raw script bodies', count( $sections ) === 2 );
check( 'keeps a nested section inside its top-level parent', str_contains( $sections[0]['source'], 'class="nested"' ) );
check( 'extracts ids, fills and bindings for the UI - the fills being the keys of the template\'s own category, no other key', $sections[1]['htmlId'] === 'content' && $sections[1]['fills'] === [ '/template/page-home/content/title' ] );
$uncategorised = array_values( array_filter( \Nino\Modules\Templates\SectionDocument::split( $source )['segments'], fn( array $segment ): bool => $segment['type'] === 'section' ) );
check( '...and a source read without a category has none', $uncategorised[1]['fills'] === [] );
check( 'rejoining untouched segments is byte-identical', $joined === $source );
check( 'reports an unmatched section instead of guessing', \Nino\Modules\Templates\SectionDocument::split( '<section><p>x</p>' )['error'] !== null );

/*	A '<' somebody wrote in prose is text, not the start of a tag - and until
	the scanner asked that question it looked for the end of a tag there
	anyway. _tagEnd() tracks quotes so an attribute value holding a '>' cannot
	end a tag early, so the apostrophe in "doesn't" opened one that nothing
	closed: the scan ran to the end of the file, answered null, and stopped.
	Every section after that '<' was gone, the page opened with nothing to
	edit, and split() reported no error - as far as it could tell there were
	no sections. One apostrophe after one '<', in a page somebody was writing
	by hand, which is what the HTML+ editor invites	*/
$proseLess = \Nino\Modules\Templates\SectionDocument::split( '<p>5 < 6 and it doesn\'t matter</p><section id="a" class="nino-section"><p>x</p></section>' );
check( 'a bare < in prose does not swallow the sections after it', $proseLess['sectionCount'] === 1
	&& $proseLess['error'] === null
	&& array_column( $proseLess['segments'], 'type' ) === [ 'raw', 'section' ] );

// The same trap with one more space in it, which is why the question is asked
// strictly: a name straight after the '<', the way html reads one
$proseSpaced = \Nino\Modules\Templates\SectionDocument::split( '<p>a < b, that doesn\'t hold</p><section id="a" class="nino-section">1</section><section id="b" class="nino-section">2</section>' );
check( '...nor one with a letter after it, which reads like a tag and is not', $proseSpaced['sectionCount'] === 2
	&& array_column( $proseSpaced['segments'], 'type' ) === [ 'raw', 'section', 'section' ] );

// ...and the quote tracking it is there for still works: a '>' inside an
// attribute value does not end the tag
$quotedAngle = \Nino\Modules\Templates\SectionDocument::split( '<section id="a" data-note="a > b" class="nino-section"><p>x</p></section>' );
check( 'a > inside an attribute value still does not end the tag early', $quotedAngle['sectionCount'] === 1 && $quotedAngle['error'] === null );
$apostropheAttr = \Nino\Modules\Templates\SectionDocument::split( '<section id="a" data-note="it\'s fine" class="nino-section"><p>x</p></section>' );
check( '...and an apostrophe inside a double-quoted one is just a character', $apostropheAttr['sectionCount'] === 1 && $apostropheAttr['error'] === null );

/*	A section's id is the name the panel puts on it and the name a save is
	checked against for duplicates, so it has to be read as the attribute it is:
	the word boundary in \bid also sits between the '-' of 'data-id' and the 'i'
	after it, and any hand-written attribute ending in '-id' was picked up before
	the real one - a page whose sections carry data-id ended up as a list of
	sections all called the same thing, and saving it was refused as a duplicate	*/
$suffixedId = \Nino\Modules\Templates\SectionDocument::split( '<section data-id="decor" aria-labelledby="h" id="hero" class="nino-section"><p>x</p></section>' );
$suffixedSections = array_values( array_filter( $suffixedId['segments'], fn( array $segment ): bool => $segment['type'] === 'section' ) );
check( 'a data-id before the id is not mistaken for the section id', count( $suffixedSections ) === 1 && $suffixedSections[0]['htmlId'] === 'hero' );
$onlySuffixedId = \Nino\Modules\Templates\SectionDocument::split( '<section data-id="decor" class="nino-section"><p>x</p></section>' );
$onlySuffixedSections = array_values( array_filter( $onlySuffixedId['segments'], fn( array $segment ): bool => $segment['type'] === 'section' ) );
check( '...and a section that carries only a data-id has no id at all', count( $onlySuffixedSections ) === 1 && $onlySuffixedSections[0]['htmlId'] === '' );
check( 'rejects a self-closing section because HTML does not close it there', \Nino\Modules\Templates\SectionDocument::split( '<section />' )['error'] !== null );
check( 'code inspection accepts exactly one complete section', \Nino\Modules\Templates\SectionDocument::inspectSection( "<section id=\"x\"></section>\n" )['valid'] === true );
check( 'code inspection rejects source around the section', \Nino\Modules\Templates\SectionDocument::inspectSection( "<div>x</div><section></section>" )['valid'] === false );
check( 'template inspection accepts one standalone shortcode', \Nino\Modules\Templates\SectionDocument::inspectTemplate( "[template /templates/html-header]\n" )['valid'] === true );
check( 'template inspection rejects mixed or nested source', \Nino\Modules\Templates\SectionDocument::inspectTemplate( "<section>[template /templates/inside]</section>\n" )['valid'] === false );
$nestedTemplate = \Nino\Modules\Templates\SectionDocument::split( "<section>[template /templates/inside]</section>\n" );
check( 'a template shortcode inside an HTML section stays inside that section', $nestedTemplate['componentCount'] === 1 && $nestedTemplate['segments'][0]['type'] === 'section' );
$rawNestedTemplate = \Nino\Modules\Templates\SectionDocument::split( "<div>\n[template /templates/inside-div]\n</div>\n" );
check( 'a template shortcode inside an arbitrary raw DOM parent stays locked', $rawNestedTemplate['componentCount'] === 0 && count( $rawNestedTemplate['segments'] ) === 1 );
$commentedTemplate = \Nino\Modules\Templates\SectionDocument::split( "<!--\n[template /templates/commented]\n-->\n" );
check( 'a template-like line inside a comment stays locked', $commentedTemplate['componentCount'] === 0 && count( $commentedTemplate['segments'] ) === 1 );
$wrappedAcrossSection = \Nino\Modules\Templates\SectionDocument::split( "<main>\n<section></section>\n[template /templates/inside-main]\n</main>\n" );
check( 'raw parent depth is retained across visual section segments', $wrappedAcrossSection['componentCount'] === 1 && count( array_filter( $wrappedAcrossSection['segments'], fn( array $segment ): bool => $segment['type'] === 'template' ) ) === 0 );
$emptyFrame = \Nino\Modules\Templates\SectionDocument::split( "[template /templates/html-header]\n[template /templates/html-footer]\n" );
check( 'unmarked header and footer remain ordinary template components outside page normalization', count( $emptyFrame['segments'] ) === 2 && $emptyFrame['segments'][0]['type'] === 'template' && $emptyFrame['segments'][1]['type'] === 'template' );

$markedFrameSource = \Nino\Modules\Templates\SectionDocument::slotSource( 'header', '/templates/html-header' )
	. \Nino\Modules\Templates\SectionDocument::slotSource( 'footer', '' );
$markedFrame = \Nino\Modules\Templates\SectionDocument::split( $markedFrameSource );
$markedSlots = array_values( array_filter( $markedFrame['segments'], fn( array $segment ): bool => $segment['type'] === 'slot' ) );
check( 'recognizes marked header and footer includes as fixed page slots', count( $markedSlots ) === 2 && $markedSlots[0]['slot'] === 'header' && $markedSlots[0]['template'] === 'html-header' && $markedSlots[1]['slot'] === 'footer' && $markedSlots[1]['path'] === '' );
check( 'fixed page slots are excluded from the canvas component count', $markedFrame['componentCount'] === 0 );
check( 'marked slot parsing remains byte-identical', implode( '', array_column( $markedFrame['segments'], 'source' ) ) === $markedFrameSource );
check( 'slot inspection accepts an include or None but rejects the wrong slot', \Nino\Modules\Templates\SectionDocument::inspectSlot( \Nino\Modules\Templates\SectionDocument::slotSource( 'header', '/templates/site-header' ), 'header' )['valid'] === true
	&& \Nino\Modules\Templates\SectionDocument::inspectSlot( \Nino\Modules\Templates\SectionDocument::slotSource( 'footer' ), 'footer' )['valid'] === true
	&& \Nino\Modules\Templates\SectionDocument::inspectSlot( \Nino\Modules\Templates\SectionDocument::slotSource( 'footer' ), 'header' )['valid'] === false );

echo "\n";


// --- Documents ------------------------------------------------------------

echo "Documents\n";

$page = "[template /templates/html-header]\n<!-- locked project source -->\n". $hero['source']. $articles['source']. "[template /templates/html-footer]\n";
file_put_contents( $sandbox. '/private/templates/page-home.tpl', $page );
file_put_contents( $sandbox. '/private/templates/section-card.tpl', '<section></section>' );
file_put_contents( $sandbox. '/private/templates/html-header.tpl', '<!doctype html>' );

$listRequest = response();
\Nino\Modules\Templates\Documents::apiList( $appData, $listRequest );
$listed = $listRequest['/nino/http/response']['body']['documents'];

check( 'lists page-*.tpl files only', array_column( $listed, 'name' ) === [ 'page-home' ] );
check( 'reports filename, display name and page id', $listed[0]['filename'] === 'page-home.tpl' && $listed[0]['displayName'] === 'Home' && $listed[0]['sections'] === 2 && $listed[0]['pageId'] === 'page-home' && $listed[0]['editable'] === true );
check( 'excludes the automatically recognized header/footer shell from the canvas-item count', $listed[0]['components'] === 2 );

$includesRequest = response();
\Nino\Modules\Templates\Documents::apiIncludes( $appData, $includesRequest );
$includes = $includesRequest['/nino/http/response']['body']['includes'];
check( 'include library always starts with html-header and html-footer', array_column( array_slice( $includes, 0, 2 ), 'name' ) === [ 'html-header', 'html-footer' ] );
check( 'include library offers section templates but excludes page templates', in_array( 'section-card', array_column( $includes, 'name' ), true ) && in_array( 'page-home', array_column( $includes, 'name' ), true ) === false );

/*	A project's templates/ holds more than the parts of a page: the mail
	bodies, the plain-text outputs a route answers and the two files the frame
	includes itself. They are classified, not dropped - a page may already
	point at one, and the panel has to find it - and the client leaves the
	kinds that are not for choosing out of its lists	*/
$hiddenTemplates = [ 'mail-user', 'mail-owner', 'mail-newsletter-confirm', 'robots', 'sitemap-xml', 'llms-txt', 'frame-header', 'frame-footer', 'social-links' ];
foreach( $hiddenTemplates as $hiddenTemplate )
	file_put_contents( $sandbox. '/private/templates/'. $hiddenTemplate. '.tpl', 'x' );
$kindsRequest = response();
\Nino\Modules\Templates\Documents::apiIncludes( $appData, $kindsRequest );
$kinds = array_column( $kindsRequest['/nino/http/response']['body']['includes'], 'kind', 'name' );
check( 'mail bodies and the robots, sitemap and llms outputs are listed as output, and the frame\'s own parts as internal', array_map( static fn( string $name ): string => $kinds[$name] ?? 'missing', [ 'mail-user', 'mail-owner', 'mail-newsletter-confirm', 'robots', 'sitemap-xml', 'llms-txt' ] ) === array_fill( 0, 6, 'output' )
	&& ( $kinds['frame-header'] ?? '' ) === 'internal' && ( $kinds['frame-footer'] ?? '' ) === 'internal' );
check( '...while a section, a partial and the frame keep their kinds, and a page still finds the template it points at', ( $kinds['section-card'] ?? '' ) === 'section' && ( $kinds['social-links'] ?? '' ) === 'partial'
	&& ( $kinds['html-header'] ?? '' ) === 'frame' && ( $kinds['html-footer'] ?? '' ) === 'frame' );
foreach( $hiddenTemplates as $hiddenTemplate )
	unlink( $sandbox. '/private/templates/'. $hiddenTemplate. '.tpl' );

/*	The page id is the template's category - its file name without .tpl, the
	prefix and a number included - and a name that is no category is a template
	the builder lists and does not open: it could not give it keys of its own	*/
foreach( [ 'page-2026-home', 'page-2026.home', 'page-Foo', 'page-a.b' ] as $variantName )
	file_put_contents( $sandbox. '/private/templates/'. $variantName. '.tpl', $page );
$variantListRequest = response();
\Nino\Modules\Templates\Documents::apiList( $appData, $variantListRequest );
$variants = array_column( $variantListRequest['/nino/http/response']['body']['documents'], null, 'name' );
check( 'the page id of page-2026-home.tpl is page-2026-home: nothing is cut off, nothing put in front', ( $variants['page-2026-home']['pageId'] ?? '' ) === 'page-2026-home' && ( $variants['page-2026-home']['editable'] ?? false ) === true );
check( 'a name with a dot or a capital gives no category: listed, not editable, with the reason, and with no page id', array_map( static fn( string $name ): array => [ $variants[$name]['pageId'], $variants[$name]['editable'], $variants[$name]['reason'] ], [ 'page-2026.home', 'page-Foo', 'page-a.b' ] ) === array_fill( 0, 3, [ null, false, 'category' ] ) );
post( [ 'name' => 'page-Foo' ] );
$noCategoryLoad = response();
\Nino\Modules\Templates\Documents::apiLoad( $appData, $noCategoryLoad );
check( 'such a template opens read-only when it is asked for, and does not save', is_string( $noCategoryLoad['/nino/http/response']['body']['readonly'] ?? null ) === true && array_key_exists( 'pageId', $noCategoryLoad['/nino/http/response']['body'] ) === true && $noCategoryLoad['/nino/http/response']['body']['pageId'] === null );
post( [ 'name' => 'page-Foo', 'revision' => $noCategoryLoad['/nino/http/response']['body']['revision'], 'segments' => $noCategoryLoad['/nino/http/response']['body']['segments'] ] );
$noCategorySave = response();
\Nino\Modules\Templates\Documents::apiSave( $appData, $noCategorySave );
check( '...and is refused', $noCategorySave['/nino/http/response']['statusCode'] === 400 );
foreach( [ 'page-2026-home', 'page-2026.home', 'page-Foo', 'page-a.b' ] as $variantName )
	unlink( $sandbox. '/private/templates/'. $variantName. '.tpl' );

foreach( [ 'page-2026.home.tpl', 'page-Foo.tpl', 'page-a.b.tpl', 'page-a_b.tpl', 'page-a--b.tpl', 'page-.tpl', 'page-home.TPL' ] as $badFilename ) {
	post( [ 'filename' => $badFilename, 'displayName' => 'Bad' ] );
	$badCreate = response();
	\Nino\Modules\Templates\Documents::apiCreate( $appData, $badCreate );
	check( 'a new page template is a name that is a category: '. $badFilename. ' is refused', $badCreate['/nino/http/response']['statusCode'] === 400 && is_file( $sandbox. '/private/templates/'. $badFilename ) === false );
}
/*	A name that stands for a category of its own: page-common and page-footer
	are the categories page-common and page-footer, which meet no category of
	"common" or of the frame - the prefix keeps them apart	*/
foreach( [ 'page-common', 'page-footer', 'page-404' ] as $goodName ) {
	post( [ 'filename' => $goodName. '.tpl', 'displayName' => 'Good' ] );
	$goodCreate = response();
	\Nino\Modules\Templates\Documents::apiCreate( $appData, $goodCreate );
	check( $goodName. '.tpl is created, its page id is '. $goodName, $goodCreate['/nino/http/response']['statusCode'] === 200 && ( $goodCreate['/nino/http/response']['body']['pageId'] ?? '' ) === $goodName );
	unlink( $sandbox. '/private/templates/'. $goodName. '.tpl' );
}

$bareSource = "<section id=\"bare\"></section>\n";
file_put_contents( $sandbox. '/private/templates/page-bare.tpl', $bareSource );
post( [ 'name' => 'page-bare' ] );
$bareLoadRequest = response();
\Nino\Modules\Templates\Documents::apiLoad( $appData, $bareLoadRequest );
$bare = $bareLoadRequest['/nino/http/response']['body'];
$bareSlots = array_values( array_filter( $bare['segments'], fn( array $segment ): bool => $segment['type'] === 'slot' ) );
check( 'a page without shell includes receives editable None placeholders', count( $bareSlots ) === 2 && $bareSlots[0]['path'] === '' && $bareSlots[1]['path'] === '' );
post( [ 'name' => 'page-bare', 'revision' => $bare['revision'], 'segments' => $bare['segments'] ] );
$bareSaveRequest = response();
\Nino\Modules\Templates\Documents::apiSave( $appData, $bareSaveRequest );
check( 'None placeholders persist as markers without inventing template includes', $bareSaveRequest['/nino/http/response']['statusCode'] === 200
	&& substr_count( (string) file_get_contents( $sandbox. '/private/templates/page-bare.tpl' ), 'nino:template-slot' ) === 2
	&& str_starts_with( (string) file_get_contents( $sandbox. '/private/templates/page-bare.tpl' ), '<!-- nino:template-name Bare -->' )
	&& str_contains( (string) file_get_contents( $sandbox. '/private/templates/page-bare.tpl' ), '[template ' ) === false );
unlink( $sandbox. '/private/templates/page-bare.tpl' );

post( [ 'name' => 'page-home' ] );
$loadRequest = response();
\Nino\Modules\Templates\Documents::apiLoad( $appData, $loadRequest );
$loaded = $loadRequest['/nino/http/response']['body'];

check( 'loads lossless segments with an optimistic revision', count( $loaded['segments'] ) >= 4 && strlen( $loaded['revision'] ) === 64 );
check( 'loads a valid page as editable', $loaded['readonly'] === null );
check( 'an unmarked hand-written template receives a stable display name and inherited VPA', $loaded['displayName'] === 'Home' && $loaded['pageMotion'] === 'on' );
$loadedSlots = array_values( array_filter( $loaded['segments'], fn( array $segment ): bool => $segment['type'] === 'slot' ) );
check( 'recognizes an exact html-header/html-footer frame as fixed settings slots', count( $loadedSlots ) === 2 && $loadedSlots[0]['slot'] === 'header' && $loadedSlots[1]['slot'] === 'footer' );

post( [ 'name' => 'page-home', 'revision' => $loaded['revision'], 'segments' => $loaded['segments'] ] );
$roundTripRequest = response();
\Nino\Modules\Templates\Documents::apiSave( $appData, $roundTripRequest );
check( 'saving untouched segments succeeds', $roundTripRequest['/nino/http/response']['statusCode'] === 200 );
check( 'the first deliberate save adds inert markers around recognized shell includes', str_contains( (string) file_get_contents( $sandbox. '/private/templates/page-home.tpl' ), '<!-- nino:template-slot header -->' )
	&& str_contains( (string) file_get_contents( $sandbox. '/private/templates/page-home.tpl' ), '<!-- nino:template-slot footer -->' )
	&& str_starts_with( (string) file_get_contents( $sandbox. '/private/templates/page-home.tpl' ), "<!-- nino:template-name Home -->\n<!-- nino:template-vpa on -->\n" ) );
$loaded['revision'] = $roundTripRequest['/nino/http/response']['body']['revision'];

$sectionSlots = array_keys( array_filter( $loaded['segments'], fn( array $segment ): bool => $segment['type'] === 'section' ) );
$reordered = $loaded['segments'];
[ $reordered[$sectionSlots[0]], $reordered[$sectionSlots[1]] ] = [ $reordered[$sectionSlots[1]], $reordered[$sectionSlots[0]] ];
post( [ 'name' => 'page-home', 'revision' => $loaded['revision'], 'segments' => $reordered ] );
$reorderRequest = response();
\Nino\Modules\Templates\Documents::apiSave( $appData, $reorderRequest );
$reorderedSource = file_get_contents( $sandbox. '/private/templates/page-home.tpl' );
check( 'reordering complete sections succeeds', $reorderRequest['/nino/http/response']['statusCode'] === 200 );
check( 'reordering leaves metadata and marked header/footer frame source in place', str_starts_with( $reorderedSource, '<!-- nino:template-name Home -->' )
	&& strpos( $reorderedSource, '<!-- nino:template-name Home -->' ) < strpos( $reorderedSource, '<!-- nino:template-slot header -->' )
	&& str_contains( $reorderedSource, "[template /templates/html-header]\n" )
	&& str_ends_with( $reorderedSource, "[template /templates/html-footer]\n" ) );
check( 'the selected section order reaches the file', strpos( $reorderedSource, 'id="services"' ) < strpos( $reorderedSource, 'id="main-hero"' ) );

post( [ 'name' => 'page-home' ] );
$freshLoadRequest = response();
\Nino\Modules\Templates\Documents::apiLoad( $appData, $freshLoadRequest );
$fresh = $freshLoadRequest['/nino/http/response']['body'];
$missingSlot = array_values( array_filter( $fresh['segments'], fn( array $segment ): bool => !( $segment['type'] === 'slot' && $segment['slot'] === 'footer' ) ) );
post( [ 'name' => 'page-home', 'revision' => $fresh['revision'], 'segments' => $missingSlot ] );
$missingSlotRequest = response();
\Nino\Modules\Templates\Documents::apiSave( $appData, $missingSlotRequest );
check( 'refuses to save a payload without both fixed shell slots', $missingSlotRequest['/nino/http/response']['statusCode'] === 400 );

$duplicateSlot = $fresh['segments'];
$headerSlot = array_values( array_filter( $fresh['segments'], fn( array $segment ): bool => $segment['type'] === 'slot' && $segment['slot'] === 'header' ) )[0];
$duplicateSlot[] = $headerSlot;
post( [ 'name' => 'page-home', 'revision' => $fresh['revision'], 'segments' => $duplicateSlot ] );
$duplicateSlotRequest = response();
\Nino\Modules\Templates\Documents::apiSave( $appData, $duplicateSlotRequest );
check( 'refuses duplicate shell slots', $duplicateSlotRequest['/nino/http/response']['statusCode'] === 400 );

$tampered = $fresh['segments'];
foreach( $tampered as &$segment )
	if( $segment['type'] === 'raw' ) {
		$segment['source'] .= 'tampered';
		break;
	}
unset( $segment );
post( [ 'name' => 'page-home', 'revision' => $fresh['revision'], 'segments' => $tampered ] );
$tamperRequest = response();
\Nino\Modules\Templates\Documents::apiSave( $appData, $tamperRequest );
check( 'rejects changes to locked page-frame source', $tamperRequest['/nino/http/response']['statusCode'] === 400 );

post( [ 'name' => 'page-home', 'revision' => $fresh['revision'], 'segments' => [ 'not-an-array' ] ] );
$shapeRequest = response();
\Nino\Modules\Templates\Documents::apiSave( $appData, $shapeRequest );
check( 'rejects malformed segment shapes without a type error', $shapeRequest['/nino/http/response']['statusCode'] === 400 );

$duplicates = $fresh['segments'];
$duplicateSlots = array_keys( array_filter( $duplicates, fn( array $segment ): bool => $segment['type'] === 'section' ) );
$duplicates[$duplicateSlots[1]]['source'] = preg_replace( '/\bid=("|\')[^"\']+\1/', 'id="services"', $duplicates[$duplicateSlots[1]]['source'], 1 );
post( [ 'name' => 'page-home', 'revision' => $fresh['revision'], 'segments' => $duplicates ] );
$duplicateRequest = response();
\Nino\Modules\Templates\Documents::apiSave( $appData, $duplicateRequest );
check( 'rejects duplicate non-empty section ids', $duplicateRequest['/nino/http/response']['statusCode'] === 409 );

file_put_contents( $sandbox. '/private/templates/page-home.tpl', $reorderedSource. "\n<!-- external edit -->\n" );
post( [ 'name' => 'page-home', 'revision' => $fresh['revision'], 'segments' => $fresh['segments'] ] );
$staleRequest = response();
\Nino\Modules\Templates\Documents::apiSave( $appData, $staleRequest );
check( 'rejects stale saves after an external edit', $staleRequest['/nino/http/response']['statusCode'] === 409 );

post( [ 'name' => '../../config' ] );
$traversalRequest = response();
\Nino\Modules\Templates\Documents::apiLoad( $appData, $traversalRequest );
check( 'rejects template path traversal', $traversalRequest['/nino/http/response']['statusCode'] === 404 );

post( [
	'filename' => 'page-services.tpl',
	'displayName' => 'Services Overview',
	'header' => '/templates/section-card',
	'footer' => '',
	'pageMotion' => 'on',
] );
$createRequest = response();
\Nino\Modules\Templates\Documents::apiCreate( $appData, $createRequest );
$createdPage = file_get_contents( $sandbox. '/private/templates/page-services.tpl' );
check( 'creates a new page template on demand', $createRequest['/nino/http/response']['statusCode'] === 200 && $createdPage !== false );
$createdSegments = array_values( array_filter( \Nino\Modules\Templates\SectionDocument::split( (string) $createdPage )['segments'], fn( array $segment ): bool => $segment['type'] === 'slot' ) );
check( 'new templates start with name/VPA metadata and chosen header/footer slots', str_starts_with( (string) $createdPage, "<!-- nino:template-name Services Overview -->\n<!-- nino:template-vpa on -->\n" )
	&& count( $createdSegments ) === 2 && $createdSegments[0]['template'] === 'section-card' && $createdSegments[1]['path'] === '' );
post( [ 'name' => 'page-services' ] );
$createdLoadRequest = response();
\Nino\Modules\Templates\Documents::apiLoad( $appData, $createdLoadRequest );
$createdLoad = $createdLoadRequest['/nino/http/response']['body'];
check( 'loads persisted template name and VPA outside the canvas', $createdLoad['displayName'] === 'Services Overview' && $createdLoad['pageMotion'] === 'on' && count( $createdLoad['segments'] ) === 2 );
post( [ 'filename' => 'page-services.tpl', 'displayName' => 'Duplicate' ] );
$duplicateCreateRequest = response();
\Nino\Modules\Templates\Documents::apiCreate( $appData, $duplicateCreateRequest );
check( 'refuses to overwrite an existing page template', $duplicateCreateRequest['/nino/http/response']['statusCode'] === 409 );
post( [ 'id' => 'page-old-field', 'displayName' => 'Missing filename' ] );
$missingFilenameRequest = response();
\Nino\Modules\Templates\Documents::apiCreate( $appData, $missingFilenameRequest );
check( 'requires the current filename field when creating a template', $missingFilenameRequest['/nino/http/response']['statusCode'] === 400 );
post( [ 'filename' => '../unsafe.tpl', 'displayName' => 'Unsafe' ] );
$invalidCreateRequest = response();
\Nino\Modules\Templates\Documents::apiCreate( $appData, $invalidCreateRequest );
check( 'rejects an unsafe new-template filename', $invalidCreateRequest['/nino/http/response']['statusCode'] === 400 );
post( [ 'name' => 'page-services', 'confirmName' => 'page-other', 'revision' => $createdLoad['revision'] ] );
$unconfirmedDeleteRequest = response();
\Nino\Modules\Templates\Documents::apiDelete( $appData, $unconfirmedDeleteRequest );
check( 'requires an exact template name before deleting a file', $unconfirmedDeleteRequest['/nino/http/response']['statusCode'] === 400 && is_file( $sandbox. '/private/templates/page-services.tpl' ) );
post( [ 'name' => 'page-services', 'confirmName' => 'page-services', 'revision' => $createdLoad['revision'] ] );
$deleteRequest = response();
\Nino\Modules\Templates\Documents::apiDelete( $appData, $deleteRequest );
check( 'deletes exactly the revision the user confirmed', $deleteRequest['/nino/http/response']['statusCode'] === 200 && is_file( $sandbox. '/private/templates/page-services.tpl' ) === false );

echo "\n";


// --- Native content -------------------------------------------------------

echo "Content\n";

post( [] );
$keysRequest = response();
\Nino\Modules\Templates\Content::apiKeys( $appData, $keysRequest );
$listedTextfills = $keysRequest['/nino/http/response']['body']['entries'];
$technicalTextfill = array_values( array_filter( $listedTextfills, fn( array $entry ): bool => $entry['key'] === '/_nino/webpage/contact/uri' ) )[0] ?? null;
check( 'lists content and blacklisted technical textfills for reusable Area bindings', in_array( '/template/page-home/hero/title', array_column( $listedTextfills, 'key' ), true )
	&& ( $technicalTextfill['blacklisted'] ?? false ) === true );
check( 'a page uri written by /_install or /_admin is offered as a global technical value', ( $technicalTextfill['global'] ?? false ) === true
	&& ( $technicalTextfill['value'] ?? '' ) === '/contact' );

post( [ 'name' => 'page-home', 'keys' => [ '/template/page-home/hero/title', '/template/page-home/hero/subtitle', '/_nino/webpage/contact/uri', '/template/common/form/submit', '/project/mail/address/owner' ] ] );
$fieldsRequest = response();
\Nino\Modules\Templates\Content::apiFields( $appData, $fieldsRequest );
$fields = $fieldsRequest['/nino/http/response']['body'];
check( 'reads existing, missing and technical native textfill values together', $fields['nativeLocale'] === 'en_US'
	&& $fields['fields'][0]['value'] === 'Old title'
	&& $fields['fields'][1]['exists'] === false
	&& $fields['fields'][2]['value'] === '/contact' );
/*	The builder writes one template's own keys: /template/<its category>/...
	Every other one it is bound to - a word of another template, a common one,
	the project's, a page's details - it reads, and says it is not its to write	*/
check( 'says which of them are this template\'s to write: its own keys, and no other', array_column( $fields['fields'], 'writable' ) === [ true, true, false, false, false ] );

post( [ 'keys' => [ '/template/page-home/hero/title' ] ] );
$noNameFields = response();
\Nino\Modules\Templates\Content::apiFields( $appData, $noNameFields );
post( [ 'name' => 'page-nothing', 'keys' => [ '/template/page-nothing/hero/title' ] ] );
$unknownNameFields = response();
\Nino\Modules\Templates\Content::apiFields( $appData, $unknownNameFields );
check( 'a read of values needs the page template it is for, and one that exists', $noNameFields['/nino/http/response']['statusCode'] === 400 && $unknownNameFields['/nino/http/response']['statusCode'] === 400 );

post( [ 'name' => 'page-home', 'items' => [
	[ 'key' => '/template/page-home/hero/title', 'value' => 'New title' ],
	[ 'key' => '/template/page-home/hero/subtitle', 'value' => 'New subtitle', 'create' => true ],
] ] );
$contentSaveRequest = response();
\Nino\Modules\Templates\Content::apiSave( $appData, $contentSaveRequest );
$nativeText = \Nino\Filesystem::getFileContent( $appData, '/text/en_US.php', [] );
$germanText = \Nino\Filesystem::getFileContent( $appData, '/text/de_DE.php', [] );
check( 'quick fill updates the native value and creates missing keys', $nativeText['[[/template/page-home/hero/title]]'] === 'New title' && $nativeText['[[/template/page-home/hero/subtitle]]'] === 'New subtitle' );
check( 'quick fill leaves translations untouched', $germanText['[[/template/page-home/hero/title]]'] === 'Alter Titel' && isset( $germanText['[[/template/page-home/hero/subtitle]]'] ) === false );

post( [ 'name' => 'page-home', 'items' => [ [ 'key' => '../../config', 'value' => 'x' ] ] ] );
$invalidContentRequest = response();
\Nino\Modules\Templates\Content::apiSave( $appData, $invalidContentRequest );
check( 'rejects content keys outside /template/<category>/<section>/<name>', $invalidContentRequest['/nino/http/response']['statusCode'] === 400 );

/*	What one page template's quick fill may write is its own keys. A binding
	to the contact form's address, to a word every template shares, to a page's
	details, to another template's text or to a key of the project is read, and
	a request that carries one is refused whole: nothing of it is written	*/
\Nino\Filesystem::putFileContent( $appData, '/text/global.php', [ '[[/project/mail/address/owner]]' => 'sales@example.com', '[[/template/common/form/submit]]' => 'Send', '[[/_nino/webpage/home/uri]]' => '/' ] );
$globalBefore = \Nino\Filesystem::getFileContent( $appData, '/text/global.php', [] );
$nativeBefore = \Nino\Filesystem::getFileContent( $appData, '/text/en_US.php', [] );
foreach( [ '/project/mail/address/owner', '/template/common/form/submit', '/_nino/webpage/home/uri', '/template/page-services/intro/title', '/project/company/general/name', '/feature/posts/pager/next', '/_admin/common/word/title', '/template/page-home/hero' ] as $foreignKey ) {
	post( [ 'name' => 'page-home', 'items' => [ [ 'key' => '/template/page-home/hero/title', 'value' => 'Kept out' ], [ 'key' => $foreignKey, 'value' => 'Changed', 'create' => true ] ] ] );
	$foreignRequest = response();
	\Nino\Modules\Templates\Content::apiSave( $appData, $foreignRequest );
	check( 'content/save refuses '. $foreignKey. ' and writes nothing of the batch', $foreignRequest['/nino/http/response']['statusCode'] === 400
		&& \Nino\Filesystem::getFileContent( $appData, '/text/global.php', [] ) === $globalBefore && \Nino\Filesystem::getFileContent( $appData, '/text/en_US.php', [] ) === $nativeBefore );
}
post( [ 'items' => [ [ 'key' => '/template/page-home/hero/title', 'value' => 'No name' ] ] ] );
$noNameSave = response();
\Nino\Modules\Templates\Content::apiSave( $appData, $noNameSave );
check( 'content/save needs the page template, and the category is its, not the request\'s', $noNameSave['/nino/http/response']['statusCode'] === 400 && \Nino\Filesystem::getFileContent( $appData, '/text/en_US.php', [] ) === $nativeBefore );
// The category is the document's: the keys of another page template are another's
post( [ 'name' => 'page-home', 'items' => [ [ 'key' => '/template/page-2026-home/hero/title', 'value' => 'Not mine', 'create' => true ] ] ] );
$otherCategorySave = response();
\Nino\Modules\Templates\Content::apiSave( $appData, $otherCategorySave );
check( 'the keys of another page template are refused, even where that template exists', $otherCategorySave['/nino/http/response']['statusCode'] === 400 );

// A key a unit keeps up to date - on a blacklist - is no value to save from here, even under the template's own category
\Nino\Filesystem::putFileContent( $appData, '/text/blacklist.php', [ '/template/page-home/hero/technical' ] );
\Nino\Filesystem::putFileContent( $appData, '/text/en_US.php', array_merge( $nativeBefore, [ '[[/template/page-home/hero/technical]]' => 'kept' ] ) );
post( [ 'name' => 'page-home', 'items' => [ [ 'key' => '/template/page-home/hero/technical', 'value' => 'changed' ] ] ] );
$blacklistedSave = response();
\Nino\Modules\Templates\Content::apiSave( $appData, $blacklistedSave );
check( 'a blacklisted key is never written, even one of the template\'s own', $blacklistedSave['/nino/http/response']['statusCode'] !== 200
	&& \Nino\Filesystem::getFileContent( $appData, '/text/en_US.php', [] )['[[/template/page-home/hero/technical]]'] === 'kept' );
\Nino\Filesystem::putFileContent( $appData, '/text/blacklist.php', [] );
\Nino\Filesystem::putFileContent( $appData, '/text/en_US.php', $nativeBefore );

// A key that ends in a line break is no key of the template's: $ alone would let it pass and create a junk key
post( [ 'name' => 'page-home', 'items' => [ [ 'key' => "/template/page-home/hero/junk\n", 'value' => 'junk', 'create' => true ] ] ] );
$newlineSave = response();
\Nino\Modules\Templates\Content::apiSave( $appData, $newlineSave );
check( 'a key ending in a line break is refused and creates nothing', $newlineSave['/nino/http/response']['statusCode'] !== 200
	&& array_filter( array_keys( \Nino\Filesystem::getFileContent( $appData, '/text/en_US.php', [] ) ), fn( string $key ): bool => str_contains( $key, '/hero/junk' ) ) === [] );

/*	A collection's model is the one its preset's Elements area declares, and
	nothing else: a request that names a section kind instead of a preset and
	an area - what the composer before named areas posted - is refused rather
	than answered from a catalogue beside the manifests, and writes nothing	*/
post( [ 'module' => 'articles', 'uri' => 'home-services', 'title' => 'Home Services' ] );
$createTypeRequest = response();
\Nino\Modules\Templates\Content::apiCreateType( $appData, $createTypeRequest );
check( 'a collection comes from a preset\'s Elements area only - a request naming no preset is refused and writes nothing', $createTypeRequest['/nino/http/response']['statusCode'] === 400
	&& \Nino\Filesystem::getFileContent( $appData, '/elements/home-services.php', [] ) === [] );

post( [ 'preset' => 'articles-grid', 'area' => 'articles', 'uri' => 'home-area-services', 'title' => 'Area Services' ] );
$createAreaTypeRequest = response();
\Nino\Modules\Templates\Content::apiCreateType( $appData, $createAreaTypeRequest );
$createdAreaType = \Nino\Filesystem::getFileContent( $appData, '/elements/home-area-services.php', [] );
check( 'creates only the Elements model declared by the requested Area', $createAreaTypeRequest['/nino/http/response']['statusCode'] === 200
	&& isset( $createdAreaType['model']['title'], $createdAreaType['model']['linkLabel'], $createdAreaType['model']['image'] ) );

// ...and the type the composer proposes for a page named by its category - page-404, a word of its own - is a type it may create
$suggestedType = \Nino\Modules\Templates\AreaComposer::defaults( $presets['articles-grid'], 'page-404', 'hero' )['areas']['articles']['source']['elementType'];
post( [ 'preset' => 'articles-grid', 'area' => 'articles', 'uri' => $suggestedType, 'title' => 'Hero Articles' ] );
$suggestedTypeRequest = response();
\Nino\Modules\Templates\Content::apiCreateType( $appData, $suggestedTypeRequest );
check( 'content/type-create accepts the type suggested for a page-404 section', $suggestedType === 'page-404-hero-articles' && $suggestedTypeRequest['/nino/http/response']['statusCode'] === 200
	&& isset( \Nino\Filesystem::getFileContent( $appData, '/elements/page-404-hero-articles.php', [] )['model']['title'] ) );

/*	A slot is always named by the preset it belongs to: every preset in the
	library is an Area preset, so the caller says which one and which slot.
	The dimensions never come from the shape of the uri - a component's from
	the manifest, the background's from AreaComposer::backgroundDefinition(),
	whose size a stored slot keeps.	*/
post( [
	'name' => 'page-home', 'preset' => 'hero-fullscreen-image', 'slot' => 'background',
	'uri' => '/template/page-home/area-stage/background', 'label' => 'Area Stage Background',
] );
$createAreaImageRequest = response();
\Nino\Modules\Templates\Content::apiCreateImage( $appData, $createAreaImageRequest );
check( 'creates a background image slot with safe recommended dimensions', $createAreaImageRequest['/nino/http/response']['statusCode'] === 200
	&& $appData['/nino/html/images']['/template/page-home/area-stage/background']['width'] === 1920
	&& $appData['/nino/html/images']['/template/page-home/area-stage/background']['height'] === 1080 );

// ...and a request that names no preset cannot invent one from the uri
post( [ 'name' => 'page-home', 'uri' => '/template/page-home/main-hero/background', 'label' => 'Main Hero Background' ] );
$presetlessImageRequest = response();
\Nino\Modules\Templates\Content::apiCreateImage( $appData, $presetlessImageRequest );
check( 'refuses a slot whose preset it was never told', $presetlessImageRequest['/nino/http/response']['statusCode'] === 400 );

post( [ 'name' => 'page-home', 'uri' => '/arbitrary/slot', 'label' => 'Unsafe' ] );
$invalidImageRequest = response();
\Nino\Modules\Templates\Content::apiCreateImage( $appData, $invalidImageRequest );
check( 'refuses to create image slots outside a generated page section', $invalidImageRequest['/nino/http/response']['statusCode'] === 400 );

// ...and outside the page template's own category
post( [ 'name' => 'page-home', 'preset' => 'hero-fullscreen-image', 'slot' => 'background', 'uri' => '/template/page-2026-home/area-stage/background', 'label' => 'Not mine' ] );
$foreignImageRequest = response();
\Nino\Modules\Templates\Content::apiCreateImage( $appData, $foreignImageRequest );
check( 'refuses an image slot of another page template', $foreignImageRequest['/nino/http/response']['statusCode'] === 400 && isset( $appData['/nino/html/images']['/template/page-2026-home/area-stage/background'] ) === false );

/*	A section writes /template/<category>/<its id>/...: a hand-written page
	that reads /template/page-services/intro/title must not be edited by a
	section that merely has the id "intro". The id is refused, with one that is free	*/
file_put_contents( $sandbox. '/private/templates/page-services.tpl', "[template /templates/html-header]\n<section id=\"intro\"><h1>[[/template/page-services/intro/title]]</h1><p>[[/template/page-services/intro/text]]</p></section>\n[template /templates/html-footer]\n" );
\Nino\Filesystem::putFileContent( $appData, '/text/en_US.php', array_merge( \Nino\Filesystem::getFileContent( $appData, '/text/en_US.php', [] ), [ '[[/template/page-services/intro/title]]' => 'Services', '[[/template/page-services/intro/text]]' => 'What we do.' ] ) );
post( [ 'name' => 'page-services', 'preset' => 'hero-cta', 'pageId' => 'page-services', 'id' => 'intro' ] );
$takenCompose = response();
\Nino\Modules\Templates\Library::apiCompose( $appData, $takenCompose );
check( 'library/compose refuses a section id under which the template already has keys, names the key and offers a free id', $takenCompose['/nino/http/response']['statusCode'] === 409
	&& str_contains( (string) ( $takenCompose['/nino/http/response']['body']['error'] ?? '' ), '/template/page-services/intro/' )
	&& str_contains( (string) ( $takenCompose['/nino/http/response']['body']['error'] ?? '' ), '"intro-2"' ) );
post( [ 'name' => 'page-services', 'preset' => 'hero-cta', 'pageId' => 'page-services', 'id' => 'offer' ] );
$freeCompose = response();
\Nino\Modules\Templates\Library::apiCompose( $appData, $freeCompose );
check( '...while a free id composes, under the template\'s category', $freeCompose['/nino/http/response']['statusCode'] === 200
	&& ( $freeCompose['/nino/http/response']['body']['fields'][0]['key'] ?? '' ) === '/template/page-services/offer/title' );
post( [ 'name' => 'page-services', 'preset' => 'hero-cta', 'pageId' => 'page-home', 'id' => 'offer' ] );
$wrongPageCompose = response();
\Nino\Modules\Templates\Library::apiCompose( $appData, $wrongPageCompose );
post( [ 'preset' => 'hero-cta', 'pageId' => 'page-services', 'id' => 'offer' ] );
$noNameCompose = response();
\Nino\Modules\Templates\Library::apiCompose( $appData, $noNameCompose );
check( 'a section\'s page id is the template\'s category, and compose needs the template', $wrongPageCompose['/nino/http/response']['statusCode'] === 400 && $noNameCompose['/nino/http/response']['statusCode'] === 400 );

// A section the document holds owns its keys: updating it finds them its own, not taken
$ownSection = \Nino\Modules\Templates\Composer::compose( [ 'preset' => 'hero-cta', 'pageId' => 'page-services', 'id' => 'intro' ] )['source'];
file_put_contents( $sandbox. '/private/templates/page-services.tpl', "[template /templates/html-header]\n". $ownSection. "[template /templates/html-footer]\n" );
post( [ 'name' => 'page-services', 'preset' => 'hero-cta', 'pageId' => 'page-services', 'id' => 'intro' ] );
$updateCompose = response();
\Nino\Modules\Templates\Library::apiCompose( $appData, $updateCompose );
check( 'a section that is in the document may be composed again under its own id', $updateCompose['/nino/http/response']['statusCode'] === 200 );

/*	A section that was inserted a moment ago has written its keys already (content/save)
	while the page is still unsaved, so the file does not hold it yet. The panel says which
	sections its open draft holds, and those are the document's own: updating one is not a
	clash with itself - while a key no section of the draft holds stays refused	*/
\Nino\Filesystem::putFileContent( $appData, '/text/en_US.php', array_merge( \Nino\Filesystem::getFileContent( $appData, '/text/en_US.php', [] ), [ '[[/template/page-services/offer/title]]' => 'Our offer' ] ) );
post( [ 'name' => 'page-services', 'preset' => 'hero-cta', 'pageId' => 'page-services', 'id' => 'offer' ] );
$unsavedRefused = response();
\Nino\Modules\Templates\Library::apiCompose( $appData, $unsavedRefused );
post( [ 'name' => 'page-services', 'preset' => 'hero-cta', 'pageId' => 'page-services', 'id' => 'offer', 'sectionIds' => [ 'intro', 'offer' ] ] );
$unsavedOwn = response();
\Nino\Modules\Templates\Library::apiCompose( $appData, $unsavedOwn );
post( [ 'name' => 'page-services', 'preset' => 'hero-cta', 'pageId' => 'page-services', 'id' => 'offer', 'sectionIds' => [ 'intro', 7, [ 'offer' ] ] ] );
$unsavedOther = response();
\Nino\Modules\Templates\Library::apiCompose( $appData, $unsavedOther );
check( 'a section the open draft holds finds its own keys, saved or not, and one no section of the draft holds is still refused', $unsavedRefused['/nino/http/response']['statusCode'] === 409
	&& $unsavedOwn['/nino/http/response']['statusCode'] === 200 && $unsavedOther['/nino/http/response']['statusCode'] === 409 );
check( 'the refusal carries a code and the values for the panel\'s own sentence: the id, the key and a free id', ( $unsavedRefused['/nino/http/response']['body']['code'] ?? '' ) === 'section-id-taken'
	&& ( $unsavedRefused['/nino/http/response']['body']['params'] ?? [] ) === [ 'offer', '/template/page-services/offer/title', 'offer-2' ] );

/*	What a composed section reads is what the panel shows as its fills: the segment that
	compose returns - the one the client keeps when it inserts or updates - lists the
	generated keys, and so does inspecting the source of a section	*/
$composedFills = $freeCompose['/nino/http/response']['body']['segment']['fills'] ?? [];
$composedKeys = array_column( $freeCompose['/nino/http/response']['body']['fields'], 'key' );
sort( $composedFills );
sort( $composedKeys );
check( 'the segment library/compose returns lists the keys the section generates as its fills', $composedFills !== [] && $composedFills === $composedKeys
	&& array_filter( $composedFills, fn( string $key ): bool => str_starts_with( $key, '/template/page-services/offer/' ) === false ) === [] );
post( [ 'name' => 'page-services', 'source' => $freeCompose['/nino/http/response']['body']['source'] ] );
$inspected = response();
\Nino\Modules\Templates\Documents::apiInspect( $appData, $inspected );
$inspectedFills = $inspected['/nino/http/response']['body']['segment']['fills'] ?? [];
sort( $inspectedFills );
check( 'documents/inspect reads the fills of the template\'s own category from a section\'s source', $inspected['/nino/http/response']['statusCode'] === 200 && $inspectedFills === $composedKeys );
post( [ 'name' => 'page-home', 'source' => $freeCompose['/nino/http/response']['body']['source'] ] );
$inspectedElsewhere = response();
\Nino\Modules\Templates\Documents::apiInspect( $appData, $inspectedElsewhere );
post( [ 'source' => $freeCompose['/nino/http/response']['body']['source'] ] );
$inspectedNoName = response();
\Nino\Modules\Templates\Documents::apiInspect( $appData, $inspectedNoName );
check( '...none from another category, and it needs the page template', ( $inspectedElsewhere['/nino/http/response']['body']['segment']['fills'] ?? null ) === [] && $inspectedNoName['/nino/http/response']['statusCode'] === 400 );

/*	What a section may bind: the keys the system writes, /_nino/webpage<uri>/..., and no key of the workbench's own	*/
$bindingInput = \Nino\Modules\Templates\AreaComposer::defaults( $presets['hero-fullscreen-image'], 'page-services', 'offer-hero' );
$bindingInput['areas']['content']['components'][3]['bindings']['href'] = '/_nino/webpage/contact/uri';
$bindingInput['areas']['content']['components'][3]['bindingSources']['href'] = 'textfill';
post( array_merge( [ 'name' => 'page-services' ], $bindingInput ) );
$systemBinding = response();
\Nino\Modules\Templates\Library::apiCompose( $appData, $systemBinding );
$bindingInput['areas']['content']['components'][3]['bindings']['href'] = '/_admin/templates/label/category-all';
post( array_merge( [ 'name' => 'page-services' ], $bindingInput ) );
$workbenchBinding = response();
\Nino\Modules\Templates\Library::apiCompose( $appData, $workbenchBinding );
check( 'library/compose binds a key the system writes and refuses one of the workbench\'s own', $systemBinding['/nino/http/response']['statusCode'] === 200
	&& str_contains( (string) $systemBinding['/nino/http/response']['body']['source'], '[[/_nino/webpage/contact/uri]]' )
	&& $workbenchBinding['/nino/http/response']['statusCode'] === 400 );

unlink( $sandbox. '/private/templates/page-services.tpl' );

\Nino\Auth::logoutUser( $appData );

echo "\n";


// --- The browser half ----------------------------------------------------------

$node = trim( (string) @shell_exec( 'command -v node 2>/dev/null' ) );

if( $node === '' )
	echo "  --  node is not on the path, so templates-js-smoke.js is not run here\n\n";
else {
	echo "templates-js-smoke.js\n";
	$out = (string) @shell_exec( escapeshellarg( $node ). ' '. escapeshellarg( __DIR__. '/templates-js-smoke.js' ). ' 2>&1' );
	echo $out;
	check( 'the browser half passes too', preg_match( '/^\d+ checks, 0 failed$/m', $out ) === 1 );
	echo "\n";
}


\Nino\Filesystem::removeDir( $sandbox );

echo "$checks checks, $failures failed\n";
exit( $failures > 0 ? 1 : 0 );
