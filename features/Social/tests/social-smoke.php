<?php
declare(strict_types=1);

/**
 *	Nino
 *	social-smoke.php	Social links: the manifest and the files it ships, the
 *										install unit and what it leaves alone, what [social],
 *										[social-link] and [social-icon] draw, what an editor
 *										can put into an element and what of it reaches a page,
 *										and what deactivation leaves behind.
 *
 *	Usage: php features/Social/tests/social-smoke.php
 *	       NINO_ROOT=../nino php features/Social/tests/social-smoke.php
 */

/*	The checkout this runs against: three levels up when the feature sits in a
	project's features/, else the one NINO_ROOT names. The features root is this
	feature's own parent either way, so the kernel's autoloader serves the class
	- and Filesystem's /features/ its icons and templates - from here	*/
$root = getenv( 'NINO_ROOT' ) ?: dirname( __DIR__, 3 );
defined( 'NINO_FEATURES_DIR' ) === true || define( 'NINO_FEATURES_DIR', dirname( __DIR__, 2 ) );
require $root. '/tests/harness.php';

/**
 *	A throwaway project with the feature switched on, the Elements and
 *	Template modules beside it, ready to render
 *
 *	@param		array					$locales			The project's languages, the first its native one
 *	@param		callable|null	$before				Called with &$appData before the activation
 *
 *	@return 	array
 */
function socialSandbox( array $locales = [ 'de_DE', 'en_US' ], ?callable $before = null ): array {

	$appData = ninoSandbox( 'social' );
	$appData['/nino/dir'] = '';
	$appData['/nino/locales/textfiles'] = '/text';
	$appData['/nino/locales/available'] = $locales;
	$appData['/nino/locales/native'] = $locales[0];
	$appData['./nino/locales/current'] = in_array( 'en_US', $locales, true ) === true ? 'en_US' : $locales[0];
	\Nino\AppData::writeContentData( $appData, [ '/nino/modules', '/nino/locales/available', '/nino/locales/native' ] );

	if( $before !== null )
		$before( $appData );

	check( 'activation in '. implode( '+', $locales ). ' succeeds', \Nino\Features::activate( $appData, 'social' ) === true );

	$appData['/nino/modules'][] = '\\Nino\\Modules\\Elements';
	$appData['/nino/modules'][] = '\\Nino\\Modules\\Template';
	\Nino\Modules::callModules( $appData, 'init' );

	return $appData;
}

/**
 *	@param		array 		&$appData			(reference) Array with current app data
 *	@param		string		$html
 *
 *	@return 	string
 */
function render( array &$appData, string $html ): string {
	return \Nino\Html::renderHtml( $appData, $html );
}

$dir = dirname( __DIR__ );


// --- 1. The manifest and the files ------------------------------------------

echo "The manifest and the files\n";

$manifest = \Nino\Features::manifest( $dir );

check( 'it validates, key "social"', is_array( $manifest ) === true && $manifest['key'] === 'social' && ninoWarnings() === [] );
check( 'it is written for this kernel', is_array( $manifest ) === true && \Nino\Features::satisfies( $manifest['nino'] ) === true );
check( 'it names and describes itself in both interface languages', is_array( $manifest ) === true
	&& \Nino\Features::localized( $manifest['name'], 'de_DE' ) !== \Nino\Features::localized( $manifest['name'], 'en_US' )
	&& \Nino\Features::localized( $manifest['description'], 'de_DE' ) !== \Nino\Features::localized( $manifest['description'], 'en_US' ) );

check( 'it is filed under content, and has no setting, no data and no requirement', $manifest['category'] === 'content'
	&& $manifest['settings'] === [] && $manifest['data'] === [] && $manifest['requires'] === [] );

$type			= include $dir. '/install/social.php';
$options	= $type['model']['icon']['options'];
$icons		= array_map( static fn( string $file ): string => basename( $file, '.svg' ), glob( $dir. '/icons/*.svg' ) ?: [] );
sort( $icons );
$offered = $options;
sort( $offered );

check( 'every icon the select offers is a file, and every file is offered', $offered === $icons && count( $icons ) === 23 );
check( 'the icon a link falls back to is one of them', in_array( \Nino\Modules\Social::FALLBACK, $icons, true ) === true );
check( 'the type has the five fields, every one of them global', array_keys( $type['model'] ) === [ 'title', 'icon', 'link', 'order', 'hidden' ]
	&& array_filter( $type['model'], static fn( array $field ): bool => ( $field['locale'] ?? false ) === true ) === [] );
check( 'a link is visible unless it says otherwise', ( $type['*']['*']['hidden'] ?? null ) === false );

$samples = array_diff_key( $type['*'], [ '*' => true ] );
check( 'four links to start from, each named, with an offered icon and a front page, not an account', array_keys( $samples ) === [ 'instagram', 'facebook', 'youtube', 'telegram' ]
	&& array_filter( $samples, static fn( array $sample ): bool => ( $sample['title'] ?? '' ) === ''
		|| in_array( $sample['icon'] ?? '', $options, true ) === false
		|| preg_match( '#^https://[^/]+/$#', $sample['link'] ?? '' ) !== 1 ) === [] );

/*	An icon is inlined into every page that draws a link, as often as the page
	draws it, so it may carry nothing that is only allowed once or that does
	anything: no id, no style, no class, no reference, no script, no handler -
	drawing elements in one line, in currentColor, hidden from screen readers	*/
$clean = [];
foreach( glob( $dir. '/icons/*.svg' ) ?: [] as $file ) {

	$svg = (string) file_get_contents( $file );
	$document = new DOMDocument();

	if( substr_count( $svg, "\n" ) !== 1 || str_ends_with( $svg, "</svg>\n" ) === false || str_contains( $svg, '[' ) === true
		|| @$document->loadXML( $svg ) === false || $document->documentElement?->localName !== 'svg' ) {
		$clean[] = basename( $file );
		continue;
	}

	$svgRoot = $document->documentElement;
	if( $svgRoot->getAttribute( 'fill' ) !== 'none' || $svgRoot->getAttribute( 'stroke' ) !== 'currentColor'
		|| $svgRoot->getAttribute( 'aria-hidden' ) !== 'true' || $svgRoot->getAttribute( 'focusable' ) !== 'false' )
		$clean[] = basename( $file );

	foreach( $document->getElementsByTagName( '*' ) as $node ) {
		if( in_array( $node->localName, [ 'svg', 'path', 'circle', 'rect', 'line', 'polyline', 'polygon', 'ellipse' ], true ) === false )
			$clean[] = basename( $file );
		foreach( $node->attributes ?? [] as $attribute )
			if( in_array( $attribute->name, [ 'id', 'style', 'class', 'href' ], true ) === true || str_starts_with( $attribute->name, 'on' ) === true )
				$clean[] = basename( $file );
	}
}
check( 'every icon is one line of drawing elements in currentColor, aria-hidden, with nothing that runs or must be unique'. ( $clean === [] ? '' : ' - '. implode( ', ', array_unique( $clean ) ) ), $clean === [] );

check( 'the icons carry Lucide\'s license: ISC, and MIT for what came from Feather',
	str_contains( (string) @file_get_contents( $dir. '/icons/LICENSE' ), 'ISC License' ) === true
	&& str_contains( (string) @file_get_contents( $dir. '/icons/LICENSE' ), 'Cole Bemis' ) === true
	&& str_contains( (string) @file_get_contents( $dir. '/icons/LICENSE-0.577.0' ), 'ISC License' ) === true );

$en				= include $dir. '/install/text/en_US.php';
$de				= include $dir. '/install/text/de_DE.php';
$unit			= include $dir. '/install/manifest.php';
$labels		= array_map( static fn( string $field ): string => '[[/_admin/elements/field/social/'. $field. ']]', array_keys( $type['model'] ) );

check( 'a label per field, in both languages, and nothing else', array_keys( $en ) === $labels && array_keys( $de ) === $labels );
check( 'the unit blacklists every label - workbench words, not the site\'s', $unit['blacklist'] === array_map( static fn( string $key ): string => trim( $key, '[]' ), $labels ) );

$css = (string) preg_replace( '#/\*.*?\*/#s', '', (string) file_get_contents( $dir. '/assets/social.css' ) );
preg_match_all( '/(?<![\w-])fill\s*:\s*([^;]+);/', $css, $fills );
check( 'the stylesheet fills nothing: a fill set from css beats the svg\'s and blots a line icon', $fills[1] === [ 'none' ] );


// --- 2. The activation -------------------------------------------------------

echo "\nThe activation\n";

$appData = socialSandbox();

$typeFile = \Nino\Filesystem::getFileContent( $appData, '/elements/social.php', false );
check( 'the type lands with its four links', is_array( $typeFile ) === true && count( array_diff_key( $typeFile['*'], [ '*' => true ] ) ) === 4 );
check( 'the template the frames include lands, holding [social]', trim( (string) \Nino\Filesystem::getFileContent( $appData, '/templates/social-links.tpl', '' ) ) === '[social]' );
check( 'the labels land in both languages', ( \Nino\Filesystem::getFileContent( $appData, '/text/de_DE.php', [] )['[[/_admin/elements/field/social/hidden]]'] ?? '' ) === 'Auf der Website ausblenden'
	&& ( \Nino\Filesystem::getFileContent( $appData, '/text/en_US.php', [] )['[[/_admin/elements/field/social/hidden]]'] ?? '' ) === 'Hide on the website' );
check( '...and on the blacklist', in_array( '/_admin/elements/field/social/link', (array) \Nino\Filesystem::getFileContent( $appData, '/text/blacklist.php', [] ), true ) === true );
check( 'the stylesheet joins the bundle', in_array( '/features/Social/assets/social.css', \Nino\Html::getAssets( $appData, '/.cache/style.css' ), true ) === true );


// --- 3. What the shortcodes draw ---------------------------------------------

echo "\nWhat the shortcodes draw\n";

$html = render( $appData, '[social]' );
check( '[social]: one list, four links, by position', substr_count( $html, '<ul class="nino-social">' ) === 1 && substr_count( $html, '<li class="nino-social-item">' ) === 4
	&& strpos( $html, 'instagram.com' ) < strpos( $html, 'facebook.com' ) && strpos( $html, 'facebook.com' ) < strpos( $html, 'youtube.com' ) && strpos( $html, 'youtube.com' ) < strpos( $html, 't.me' ) );
check( 'every link is named by text for a screen reader, its icon hidden', substr_count( $html, '<span class="nino-social-label nino-sr-only">' ) === 4
	&& substr_count( $html, '<span class="nino-social-icon" aria-hidden="true"><svg' ) === 4 && str_contains( $html, 'aria-label' ) === false );
check( 'a profile on the web is rel="me", and nothing opens a new tab', substr_count( $html, 'rel="me"' ) === 4 && str_contains( $html, 'target=' ) === false );
$telegram = trim( (string) file_get_contents( $dir. '/icons/telegram.svg' ) );
check( 'the icon is the one the element names', $telegram !== '' && str_contains( $html, $telegram ) === true );

$html = render( $appData, '[social only="telegram, instagram, nope"]' );
check( 'only=: these, an unknown id ignored, in the order of their position', substr_count( $html, '<li ' ) === 2 && strpos( $html, 'instagram.com' ) < strpos( $html, 't.me' ) );
$html = render( $appData, '[social exclude="facebook,telegram"]' );
check( 'exclude= leaves them out', substr_count( $html, '<li ' ) === 2 && str_contains( $html, 'facebook.com' ) === false && str_contains( $html, 't.me' ) === false );
$html = render( $appData, '[social show="both" size="large"]' );
check( 'show="both" shows the names, size="large" sizes the list', str_contains( $html, '<ul class="nino-social nino-social--large">' ) === true
	&& substr_count( $html, '<span class="nino-social-label">' ) === 4 && substr_count( $html, '<svg' ) === 4 );
$html = render( $appData, '[social show="label" size="small"]' );
check( 'show="label": the names alone', str_contains( $html, '<ul class="nino-social nino-social--small">' ) === true && str_contains( $html, '<svg' ) === false && str_contains( $html, '>Instagram</span>' ) === true );
$html = render( $appData, '[social size="huge" show="all" class="x" onclick="y"]' );
check( 'an unknown size or show is the default, and nothing else is echoed', str_contains( $html, '<ul class="nino-social">' ) === true
	&& substr_count( $html, 'nino-sr-only' ) === 4 && str_contains( $html, 'onclick' ) === false && str_contains( $html, '"x"' ) === false );

$html = render( $appData, 'Follow us on [social-link youtube].' );
check( '[social-link]: one anchor in the text, icon and name by default, no list', substr_count( $html, '<a ' ) === 1 && str_contains( $html, '<ul' ) === false
	&& str_contains( $html, '<span class="nino-social-label">YouTube</span>' ) === true && str_contains( $html, '<svg' ) === true && str_starts_with( $html, 'Follow us on <a class="nino-social-link"' ) === true );
check( '[social-link id="…" show="label"]: the name alone', str_contains( render( $appData, '[social-link id="youtube" show="label"]' ), '<svg' ) === false
	&& str_contains( render( $appData, '[social-link id="youtube" show="label"]' ), 'youtube.com' ) === true );
check( '[social-link] of an unknown id draws nothing', render( $appData, '[social-link nope]' ) === '' && render( $appData, '[social-link]' ) === '' );
check( '[social-icon telegram]: the icon alone', render( $appData, '[social-icon telegram]' ) === '<span class="nino-social-icon" aria-hidden="true">'. $telegram. '</span>' );
check( '[social-icon name="…"] inside [elements /social] draws each element\'s', substr_count( render( $appData, '[elements /social][social-icon name="[[icon]]"][/elements]' ), '<svg' ) === 4 );
check( 'the template the frames include draws the list', substr_count( render( $appData, '[template /templates/social-links]' ), '<li class="nino-social-item">' ) === 4 );

ninoWarnings();
check( 'drawing raised no warning', ninoWarnings() === [] );


// --- 4. Positions ------------------------------------------------------------

echo "\nPositions\n";

\Nino\Elements::insertElement( $appData, '/social/mastodon', [ 'title' => 'Mastodon', 'icon' => 'mastodon', 'link' => 'https://mastodon.social/@example', 'order' => 15 ], 'en_US' );
\Nino\Elements::insertElement( $appData, '/social/website', [ 'title' => 'Website', 'icon' => 'website', 'link' => 'https://first.example/', 'order' => 0 ], 'en_US' );
\Nino\Elements::insertElement( $appData, '/social/mail', [ 'title' => 'Mail', 'icon' => 'mail', 'link' => 'mailto:hello@example.com' ], 'en_US' );

$html = render( $appData, '[social]' );
check( 'a position between two puts a link between them', strpos( $html, 'instagram.com' ) < strpos( $html, 'mastodon.social' ) && strpos( $html, 'mastodon.social' ) < strpos( $html, 'facebook.com' ) );
check( '0 - what the panel saves for an empty field - and none go last, in the order of the type file', strpos( $html, 't.me' ) < strpos( $html, 'first.example' ) && strpos( $html, 'first.example' ) < strpos( $html, 'mailto:' ) );
check( 'mail is a link, not a profile: no rel="me"', str_contains( $html, 'href="mailto:hello@example.com">' ) === true );

foreach( [ 'mastodon', 'website', 'mail' ] as $id )
	\Nino\Elements::deleteElement( $appData, '/social/'. $id, '*' );


// --- 5. What an editor can put in --------------------------------------------

echo "\nWhat an editor can put in\n";

$hostile = [
	'js'			=> 'javascript:alert(1)',
	'jsuc'		=> ' JavaScript:alert(1)',
	'jstab'		=> "java\tscript:alert(1)",
	'data'		=> 'data:text/html,<script>x</script>',
	'vbs'			=> 'vbscript:x',
	'proto'		=> '//evil.example/',
	'slash'		=> "/\t/evil.example/",
	'back'		=> '/\\evil.example/',
	'user'		=> 'https://instagram.com@evil.example/',
	'nohost'	=> 'https:///path',
];
$order = 1;
foreach( $hostile as $id => $url )
	\Nino\Elements::insertElement( $appData, '/social/'. $id, [ 'title' => 'X'. $id, 'icon' => 'mail', 'link' => $url, 'order' => $order++ ], 'en_US' );
\Nino\Elements::insertElement( $appData, '/social/name', [ 'title' => '"><b>x</b>[social]', 'icon' => '../../config', 'link' => 'https://ok.example/"onmouseover="x', 'order' => 1 ], 'en_US' );
\Nino\Elements::insertElement( $appData, '/social/noname', [ 'title' => '  ', 'icon' => 'website', 'link' => 'https://www.example.org/', 'order' => 2 ], 'en_US' );
\Nino\Elements::insertElement( $appData, '/social/here', [ 'title' => 'Here', 'icon' => 'link', 'link' => '/contact', 'order' => 3 ], 'en_US' );
\Nino\Elements::insertElement( $appData, '/social/Upper', [ 'title' => 'Upper', 'icon' => 'NotASlug', 'link' => 'https://upper.example/', 'order' => 5 ], 'en_US' );
\Nino\Elements::insertElement( $appData, '/social/off', [ 'title' => 'Off', 'icon' => 'link', 'link' => 'https://off.example/', 'order' => 4, 'hidden' => true ], 'en_US' );

$html = render( $appData, '[social]' );
check( 'an address that is not http(s), mailto:, tel: or a path here is dropped', preg_match( '/>X(?:js|jsuc|jstab|data|vbs|proto|slash|back|user|nohost)</', $html ) !== 1
	&& stripos( $html, 'script:' ) === false && str_contains( $html, 'evil.example' ) === false );
check( 'a name is escaped, its bracket neutralised, and nothing renders twice', str_contains( $html, '<b>' ) === false && str_contains( $html, '&#91;social]' ) === true && substr_count( $html, '<ul' ) === 1 );
check( 'a quote in an address stays inside href', str_contains( $html, 'href="https://ok.example/&quot;onmouseover=&quot;x"' ) === true );
check( 'an icon that is no slug of this feature\'s draws the link icon', substr_count( $html, trim( (string) file_get_contents( $dir. '/icons/link.svg' ) ) ) === 3 );
check( 'a link without a name is named by its host', str_contains( $html, '<span class="nino-social-label nino-sr-only">example.org</span>' ) === true );
check( 'a path on this site is kept, without rel="me"', str_contains( $html, 'href="/contact">' ) === true );
check( 'a hidden link is left out, wherever it is asked for', str_contains( $html, 'off.example' ) === false
	&& render( $appData, '[social-link off]' ) === '' && render( $appData, '[social only="off"]' ) === '' );
check( 'an id with capitals, which the Elements panel accepts, is drawn and can be asked for', str_contains( $html, 'upper.example' ) === true
	&& str_contains( render( $appData, '[social-link Upper]' ), 'upper.example' ) === true && str_contains( render( $appData, '[social only="Upper"]' ), 'upper.example' ) === true );
check( 'an icon slug cannot leave the icons', render( $appData, '[social-icon ../../config]' ) === \Nino\Modules\Social::icon( $appData, 'link' )
	&& render( $appData, '[social-icon name="../LICENSE"]' ) === \Nino\Modules\Social::icon( $appData, 'link' ) );

// A type file edited by hand: '1' for hidden is hidden too. The element
// cache is dropped as the next request would start without it
$typeFile = (array) \Nino\Filesystem::getFileContent( $appData, '/elements/social.php', [] );
$typeFile['*']['facebook']['hidden'] = '1';
\Nino\Filesystem::putFileContent( $appData, '/elements/social.php', $typeFile );
unset( $appData['./nino/elements/cache'] );
check( 'a hidden written by hand as "1" hides the link as well', str_contains( render( $appData, '[social]' ), 'facebook.com' ) === false );
$typeFile['*']['facebook']['hidden'] = false;
\Nino\Filesystem::putFileContent( $appData, '/elements/social.php', $typeFile );
unset( $appData['./nino/elements/cache'] );

foreach( array_merge( array_keys( $hostile ), [ 'name', 'noname', 'here', 'Upper', 'off' ] ) as $id )
	\Nino\Elements::deleteElement( $appData, '/social/'. $id, '*' );
check( 'with those gone, the four are what is left', substr_count( render( $appData, '[social]' ), '<li ' ) === 4 );


// --- 6. Add-only, an update, other languages ---------------------------------

echo "\nAdd-only, an update, other languages\n";

\Nino\Elements::deleteElement( $appData, '/social/youtube', '*' );
$appData['/nino/features']['social']['version'] = '0.9.0';
\Nino\AppData::writeContentData( $appData, [ '/nino/features' ] );
unset( $appData['./nino/features/all'] );
check( 'an update activates', \Nino\Features::activate( $appData, 'social' ) === true );
check( 'a link the editors deleted stays deleted', \Nino\Elements::getElement( $appData, '/social/youtube', 'en_US' ) === false );

// A project with a type /social and a template of the same name of its own:
// both are kept as they were, and [social] draws nothing it does not understand
$foreign	= "<?php\nreturn [ 'title' => 'Mine', 'model' => [ 'name' => [ 'type' => 'string' ], 'url' => [ 'type' => 'string' ] ], '*' => [ '*' => [], 'a' => [ 'name' => 'A', 'url' => 'https://a.example/' ] ] ];\n";
$ownTpl		= "<p>Our links: [[/project/company/general/name]]</p>\n";
$own = socialSandbox( [ 'de_DE', 'en_US' ], static function( array &$appData ) use ( $foreign, $ownTpl ): void {
	\Nino\Filesystem::forceDir( $appData, '/elements' );
	\Nino\Filesystem::forceDir( $appData, '/templates' );
	file_put_contents( \Nino\Filesystem::path( $appData, '/elements/social.php' ), $foreign );
	file_put_contents( \Nino\Filesystem::path( $appData, '/templates/social-links.tpl' ), $ownTpl );
} );
check( 'a type /social of the project\'s own is kept byte for byte', file_get_contents( \Nino\Filesystem::path( $own, '/elements/social.php' ) ) === $foreign );
check( 'so is a template social-links of its own', file_get_contents( \Nino\Filesystem::path( $own, '/templates/social-links.tpl' ) ) === $ownTpl );
ninoWarnings();
check( '[social] over a model of another shape draws nothing, and warns about nothing', render( $own, '[social]' ) === '' && ninoWarnings() === [] );
\Nino\Filesystem::removeDir( ninoSandboxDir( $own ) );

// A project in French alone: the type file is not tied to a language, so the
// links are named; the unit has no French labels, so none is copied
$fr = socialSandbox( [ 'fr_FR' ] );
$html = render( $fr, '[social show="both"]' );
check( 'fr_FR alone: four named links', substr_count( $html, '<li ' ) === 4 && str_contains( $html, '>Instagram</span>' ) === true );
check( 'fr_FR alone: no [[key]] reaches the page', str_contains( $html, '[[' ) === false && str_contains( $html, '&#91;&#91;' ) === false );
\Nino\Filesystem::removeDir( ninoSandboxDir( $fr ) );


// --- 7. Deactivation -----------------------------------------------------------

echo "\nDeactivation\n";

check( 'deactivation succeeds', \Nino\Features::deactivate( $appData, 'social' ) === true );
$persisted = (array) ( \Nino\Filesystem::getFileContent( $appData, '/config.php', [] )['/nino/modules'] ?? [] );
check( 'the class leaves /nino/modules', in_array( '\\Nino\\Modules\\Social', $persisted, true ) === false );
check( 'the type, the template and the labels stay', is_file( \Nino\Filesystem::path( $appData, '/elements/social.php' ) ) === true
	&& is_file( \Nino\Filesystem::path( $appData, '/templates/social-links.tpl' ) ) === true
	&& isset( \Nino\Filesystem::getFileContent( $appData, '/text/en_US.php', [] )['[[/_admin/elements/field/social/link]]'] ) === true );

// The next request, which no longer loads the class
$next = $appData;
unset( $next['./nino/html/shortcodes'], $next['./nino/callbacks'] );
$next['./nino/html/cache'] = false;
$next['/nino/modules'] = [ '\\Nino\\Modules\\Elements', '\\Nino\\Modules\\Template' ];
\Nino\Modules::callModules( $next, 'init' );
check( 'afterwards a [social] left in a template is text - the contract every feature has', render( $next, 'A[social]B' ) === 'A[social]B' );
check( '...the template the frames include among them, which is why the README says to empty it first', trim( render( $next, '[template /templates/social-links]' ) ) === '[social]' );

ninoDone( $appData );
