<?php
declare(strict_types=1);
/**
 *	Nino							A compact filesystembased php framework
 *	preview.php				A design harness for the part sets - dev only, never deployed.
 *
 *										It boots a real Nino against a throwaway project, applies the
 *										base unit into it, swaps in the header and footer named below,
 *										and renders one specimen page that uses every class a set can
 *										reach. What you see is what a real page renders, not an
 *										approximation: the stylesheet comes out of the Design
 *										feature's own \Nino\Modules\Design\Compiler, against a setup
 *										its own Setup::normalize() checked, and the frames go through
 *										\Nino\Html::renderHtml(), so their textfills, their
 *										[template] includes and the [navigation] shortcode all resolve
 *										the way they do in a project.
 *
 *										Nothing is written into a checkout. The throwaway project lives
 *										in the system temp directory and is rebuilt on every request, so
 *										editing a set or a frame is a reload away.
 *
 *	Usage							php -S 127.0.0.1:8080 design-library/preview.php
 *										then open http://127.0.0.1:8080/
 *
 *										The Nino checkout is ../nino, or wherever NINO_ROOT points:
 *										NINO_ROOT=/path/to/nino php -S 127.0.0.1:8080 design-library/preview.php
 *
 *	@package					Dape/Nino
 *	@author						David Perchermeier <mail@dape.io>
 *	@link							https://github.com/dapeio/nino
 */


/*	========================================================================
	What to look at.

	Everything is named out of the Design feature's library: header/footer
	name a directory in features/Design/library/<part>/, every other key a
	file features/Design/library/sets/<part>/<value>.css. A part may carry
	its step as well - 'article' => [ 'v2', 'less' ] - which picks that step
	out of every --name--less / --name--default / --name--more triple the set
	declares. A set that does not exist is reported in the bar along the
	bottom rather than passed over, so a typo does not read as "the design
	does nothing".
	======================================================================== */

const PARTS = [
	'header' 	=> 'v1',
	'footer' 	=> 'v1',
	'atf' 		=> 'v1',
	'section' => 'v1',
	'article' => 'v1',
	'buttons' => 'v1',
	'forms' 	=> 'v1',
	'lists' 	=> 'v1',
	'blocks' 	=> 'v1',
];

// The root size the compiler writes: s, m or l (see Setup::SIZES)
const PREVIEW_SIZE = 'm';

// The interface language of the specimen - the frames' own labels come from
// the base unit's text files, so this is a real locale, not a label
const LOCALE = 'de_DE';

/*	======================================================================== */


// Dev only, and it means it: this boots a kernel, writes into the temp
// directory and renders unauthenticated. It answers the loopback and nothing
// else, so a stray copy on a server is inert rather than interesting
$remote = (string) ( $_SERVER['REMOTE_ADDR'] ?? '' );
if( in_array( $remote, [ '127.0.0.1', '::1', '' ], true ) === false ) {
	http_response_code( 403 );
	exit( "preview.php answers the loopback interface only.\n" );
}

$here 		= __DIR__;
$root 		= rtrim( (string) ( getenv( 'NINO_ROOT' ) ?: dirname( $here, 2 ). '/nino' ), '/' );
// The sets and frames live with the feature that ships them; this file is only
// the harness that looks at them
$library 	= dirname( $here ). '/features/Design/library';

if( is_file( $root. '/_nino/Nino.php' ) === false ) {
	http_response_code( 500 );
	exit( "No Nino checkout at $root - clone it beside this repository or set NINO_ROOT.\n" );
}

/*	The autoloader resolves Nino\Modules\* over the checkout's own features/,
	and the feature this previews lives in *this* repository - the same define
	every feature's test makes, and for the same reason */
defined( 'NINO_FEATURES_DIR' ) === true || define( 'NINO_FEATURES_DIR', dirname( $here ). '/features' );

require $root. '/_nino/Nino.php';


/**
 *	The part and the step it is shown at
 *
 *	@param		string		$part					A key of PARTS
 *
 *	@return 	array									[ name, step ]
 */
function previewPart( string $part ): array {

	$value = PARTS[$part] ?? '';

	if( is_array( $value ) === true )
		return [ (string) ( $value[0] ?? $value['set'] ?? '' ), (string) ( $value[1] ?? $value['step'] ?? 'default' ) ];

	return [ (string) $value, 'default' ];
}


/**
 *	A throwaway project with the base unit applied, the three always-on module
 *	units beside it, and the chosen frames written over the delivered ones.
 *	Rebuilt per request - it is thirty-odd small files, and always-correct is
 *	worth more here than fast
 *
 *	@param		string		$root					The Nino checkout
 *	@param		string		$library			The feature's library
 *	@param		array 		&$notes				(reference) Anything worth saying in the bar
 *
 *	@return 	array									App data for the render
 */
function previewProject( string $root, string $library, array &$notes ): array {

	$dir = sys_get_temp_dir(). '/nino-design-preview';
	\Nino\Filesystem::removeDir( $dir );
	mkdir( $dir, 0755, true );

	$appData = [ './nino/uid' => $dir ];
	\Nino\AppData::prepare( $appData );

	// prepare() carries the runtime skeleton; the persisted defaults are what
	// init() would merge from config.php, and there is no config.php here
	$appData += \Nino\AppData::DEFAULTS;

	$appData['./nino/filesystem/path'] 				= $dir;
	$appData['./nino/filesystem/configpath'] 	= $dir. '/private';
	$appData['./nino/filesystem/contentpath'] = $dir. '/private';
	$appData['./nino/filesystem/privatepath'] = $dir. '/private';
	$appData['./nino/filesystem/publicpath'] 	= $dir. '/public';
	$appData['./nino/locales/current'] 				= LOCALE;
	$appData['/nino/locales/native'] 					= LOCALE;
	$appData['/nino/locales/available'] 			= [ LOCALE ];

	$routes = [];
	$blacklist = [];

	$units = [ $root. '/_admin/install/library/base' ];
	foreach( [ 'Navigation', 'Localepicker', 'Form' ] as $module )
		if( is_dir( $root. '/_nino/Nino/Modules/'. $module. '/install' ) === true )
			$units[] = $root. '/_nino/Nino/Modules/'. $module. '/install';

	foreach( $units as $unit )
		\Nino\Features::applyUnit( $appData, $unit, [ LOCALE ], $routes, $blacklist );

	$appData['/nino/modules'] = [
		'\\Nino\\Modules\\Assets', '\\Nino\\Modules\\Elements', '\\Nino\\Modules\\Template',
		'\\Nino\\Modules\\Jstext', '\\Nino\\Modules\\Csrf', '\\Nino\\Modules\\Images',
		'\\Nino\\Modules\\Navigation', '\\Nino\\Modules\\Localepicker',
	];
	/*	A header set cannot be judged against an empty menu, and the base unit's
		own routes (robots, sitemap, llms) are in no navigation. These five are
		the specimen's menu, in the shape the page units use: the route key is
		the public path ('GET://' is the root, 'GET://kontakt' the rest), the
		route's own `uri` is the element path the texts hang off, and
		Modules\Navigation reads each label from /webpage<uri>/name - a route
		nobody named stays out of the menu entirely */
	$menu = [
		'' 						=> [ '/home', 'Start' ],
		'leistungen' 	=> [ '/leistungen', 'Leistungen' ],
		'referenzen' 	=> [ '/referenzen', 'Referenzen' ],
		'ueber-uns' 	=> [ '/ueber-uns', 'Über uns' ],
		'kontakt' 		=> [ '/kontakt', 'Kontakt' ],
	];
	$weight = 0;
	$labels = [];

	foreach( $menu as $path => [ $element, $label ] ) {
		$weight += 5;
		$routes['GET://'. $path] = [ 'uri' => $element, 'body' => '', 'statusCode' => 200, 'navs' => [ 'main' => $weight, 'footer' => $weight ] ];
		$labels['/webpage'. $element. '/name'] = $label;
	}

	$appData['/nino/http/routes'] = $routes;

	// The frames the bar names, over the two the base unit just wrote
	foreach( [ 'header', 'footer' ] as $kind ) {

		[ $name ] = previewPart( $kind );
		$template = \Nino\Modules\Design\Setup::file( $library, $kind, $name, 'template' );

		if( $template === '' ) {
			$notes[] = $kind. '/'. $name. ' has no template.tpl - showing the one the base unit delivers';
			continue;
		}

		\Nino\Filesystem::putFileContent( $appData, '/templates/theme.'. $kind. '.tpl', (string) file_get_contents( $template ) );
	}

	/*	The fills a request would have brought: the two path prefixes (empty,
		because this harness serves from its own root) and one page, so the
		frames' own title fill resolves instead of standing there in brackets */
	$appData['/nino/http/response'] = [ 'uri' => '/preview', 'statusCode' => 200, 'header' => [], 'body' => '' ];

	\Nino\Html::addFills( $appData, [
		'/nino/dir' 									=> '',
		'/nino/public' 								=> '',
		// The frames resolve their title through a nested fill,
		// [[/webpage[[/nino/http/response/uri]]/title]] - the inner one is a
		// request's own, and there is no request here
		'/nino/http/response/uri' 		=> '/preview',
		'/date/year' 									=> date( 'Y' ),
		'/webpage/preview/title' 			=> 'Design preview',
		'/webpage/preview/name' 			=> 'Preview',
		'/webpage/preview/description'=> 'Every class a part set can reach, on one page.',
	] + $labels, '*' );

	\Nino\Modules::callModules( $appData, 'init' );

	return $appData;
}


/**
 *	The stylesheet the specimen is shown under - the feature's own compiler, not
 *	a second assembly beside it. What is previewed here is byte for byte what a
 *	project would get, and the compiler is exercised every time somebody looks
 *	at a design rather than only when its test runs
 *
 *	@param		string		$library			The feature's library
 *	@param		array 		&$notes				(reference) Anything worth saying in the bar
 *
 *	@return 	string								The compiled css
 */
function previewCss( string $library, array &$notes ): string {

	$setup = \Nino\Modules\Design\Setup::normalize( previewSetup(), $library, $notes );
	$css 	 = \Nino\Modules\Design\Compiler::compile( $setup, $library, $notes );

	// The harness serves from its own root, so the public prefix is nothing -
	// base.css's @font-face urls become /fonts/… and land on the route below
	return str_replace( '[[/nino/public]]', '', $css );
}


/**
 *	PARTS, in the shape Setup::normalize() reads. The array at the head of this
 *	file is the short form a person types; this is the long one the feature
 *	stores
 *
 *	@return 	array
 */
function previewSetup(): array {

	$parts = [];

	foreach( PARTS as $part => $value ) {
		[ $name, $step ] = previewPart( $part );
		$parts[$part] = [ 'set' => $name, 'step' => $step === 'default' ? null : $step ];
	}

	return [ 'format' => 1, 'parts' => $parts, 'step' => 'default', 'size' => PREVIEW_SIZE ];
}


/**
 *	The specimen. Every class a part set can reach, once, in the markup a real
 *	page produces - the article grid is the shape the articles-grid preset
 *	emits, the form is the contact page's. Section ids are the part names, so
 *	the bar at the top can jump to one
 *
 *	@return 	string								Html+, rendered through the kernel by the caller
 */
function previewSpecimen(): string {

	$lorem 	= 'Die Entscheidung, die ein Set trifft, sieht man erst an echtem Text: wo der Titel steht, wie weit er vom Untertitel absteht, und ob die Zeile noch ruhig bleibt, wenn sie lang wird.';
	$short 	= 'Kurz genug, um die Ausrichtung zu zeigen.';
	$out 		= [];

	$out[] = '[template /templates/theme.header]';

	// --- ATF: the hero, its three loudnesses, the arrow ---
	$out[] = '<section id="atf" class="nino-section nino-section--dark nino-cover nino-atf" data-cover-height="70" aria-labelledby="atf-title">
		<div class="nino-cover-content">
			<div class="nino-grid-row nino-grid-middle">
				<div class="nino-grid-100 nino-text-center">
					<h2 class="nino-atf-title" id="atf-title">ATF</h2>
					<p class="nino-atf-subtitle">'. $short. '</p>
					<p><a class="nino-btn nino-btn--primary" href="#section">Weiter</a> <a class="nino-btn nino-btn--outline" href="#article">Artikel</a></p>
				</div>
			</div>
		</div>
		<button class="nino-atf-arrowdown" data-arrow-target="#section" aria-label="Weiter"></button>
	</section>';

	$out[] = '<section class="nino-section" aria-label="ATF-Lautstärken">
		<div class="nino-grid-row">
			<div class="nino-grid-100 nino-grid-m-33"><h3 class="nino-atf-title nino-atf-title--quiet">quiet</h3><p class="nino-atf-subtitle nino-atf-subtitle--quiet">'. $short. '</p></div>
			<div class="nino-grid-100 nino-grid-m-33"><h3 class="nino-atf-title">default</h3><p class="nino-atf-subtitle">'. $short. '</p></div>
			<div class="nino-grid-100 nino-grid-m-33"><h3 class="nino-atf-title nino-atf-title--loud">loud</h3><p class="nino-atf-subtitle nino-atf-subtitle--loud">'. $short. '</p></div>
		</div>
	</section>';

	// --- Section: every surface, every loudness, the border steps ---
	$surfaces = [ '' => 'default', '--alt' => 'alt', '--tint' => 'tint', '--primary' => 'primary', '--brand-alt' => 'brand-alt', '--dark' => 'dark', '--black' => 'black' ];
	$first = true;

	foreach( $surfaces as $modifier => $label ) {
		$out[] = '<section'. ( $first ? ' id="section"' : '' ). ' class="nino-section'. ( $modifier !== '' ? ' nino-section'. $modifier : '' ). '" aria-label="Section '. $label. '">
			<div class="nino-grid-row">
				<div class="nino-grid-100">
					<h2 class="nino-section-title">Section &mdash; '. $label. '</h2>
					<p class="nino-section-subtitle">'. $short. '</p>
					<p class="nino-section-text">'. $lorem. '</p>
					<p><a class="nino-btn nino-btn--primary" href="#">Primär</a> <a class="nino-btn nino-btn--outline" href="#">Outline</a></p>
				</div>
			</div>
		</section>';
		$first = false;
	}

	$out[] = '<section class="nino-section nino-section--border-1" aria-label="Section-Lautstärken und Rahmen">
		<div class="nino-grid-row">
			<div class="nino-grid-100 nino-grid-m-33"><h3 class="nino-section-title nino-section-title--quiet">quiet</h3><p class="nino-section-subtitle nino-section-subtitle--quiet">'. $short. '</p><p class="nino-section-text nino-section-text--quiet">'. $short. '</p></div>
			<div class="nino-grid-100 nino-grid-m-33"><h3 class="nino-section-title">default</h3><p class="nino-section-subtitle">'. $short. '</p><p class="nino-section-text">'. $short. '</p></div>
			<div class="nino-grid-100 nino-grid-m-33"><h3 class="nino-section-title nino-section-title--loud">loud</h3><p class="nino-section-subtitle nino-section-subtitle--loud">'. $short. '</p><p class="nino-section-text nino-section-text--loud">'. $short. '</p></div>
		</div>
	</section>';

	$out[] = '<section class="nino-section nino-section--border-2" aria-label="Rahmenstufe 2"><div class="nino-grid-row"><div class="nino-grid-100"><p class="nino-section-text">border-2</p></div></div></section>';
	$out[] = '<section class="nino-section nino-section--border-3" aria-label="Rahmenstufe 3"><div class="nino-grid-row"><div class="nino-grid-100"><p class="nino-section-text">border-3</p></div></div></section>';
	$out[] = '<section class="nino-section nino-section--border-primary" aria-label="Rahmen in der Markenfarbe"><div class="nino-grid-row"><div class="nino-grid-100"><p class="nino-section-text">border-primary</p></div></div></section>';

	return implode( "\n", $out ). previewSpecimenRest( $lorem, $short );
}


/**
 *	The second half of the specimen - articles, buttons, forms, lists and
 *	tables, the building blocks. Split off so neither function is a wall
 *
 *	@param		string		$lorem				A paragraph long enough to wrap
 *	@param		string		$short				A line short enough to read at a glance
 *
 *	@return 	string								Html+
 */
function previewSpecimenRest( string $lorem, string $short ): string {

	$out = [];

	// --- Article: the grid an articles-grid section emits, then the variants ---
	$card = static function( string $title, string $extra = '', string $inner = '' ): string {
		return '<div class="nino-grid-100 nino-grid-m-33">
			<article class="nino-article'. ( $extra !== '' ? ' '. $extra : '' ). '">
				<img class="nino-article-img" src="/images/specimen.svg" alt="" width="640" height="420">
				<div class="nino-article-content">
					<h3 class="nino-article-title">'. $title. '</h3>
					<p class="nino-article-subtitle">Untertitel</p>
					<p class="nino-article-descr">Eine Beschreibung, lang genug, dass sie in die zweite Zeile läuft und der Abstand darunter sichtbar wird.</p>
					'. $inner. '
				</div>
			</article>
		</div>';
	};

	$out[] = '<section id="article" class="nino-section" aria-labelledby="article-title">
		<div class="nino-grid-row"><div class="nino-grid-100"><h2 class="nino-section-title" id="article-title">Article</h2></div></div>
		<div class="nino-grid-row">
			'. $card( 'Mit Preis', '', '<p class="nino-article-price">49 €</p>' ). '
			'. $card( 'Mit Badge', '', '<p><span class="nino-badge nino-badge--primary">Neu</span> <span class="nino-badge nino-badge--success">Auf Lager</span></p>' ). '
			'. $card( 'Mit Aktion', '', '<p><a class="nino-btn nino-btn--small nino-btn--outline" href="#">Mehr</a></p>' ). '
		</div>
		<div class="nino-grid-row">
			'. $card( 'alt', 'nino-article--alt' ). '
			'. $card( 'borderless', 'nino-article--borderless' ). '
			'. $card( 'grid', 'nino-article--grid' ). '
		</div>
		<div class="nino-grid-row"><div class="nino-grid-100">
			<article class="nino-article nino-article--fullwidth nino-article-cols">
				<img class="nino-article-img nino-article-img--maxheight" src="/images/specimen.svg" alt="" width="640" height="420">
				<div class="nino-article-content">
					<h3 class="nino-article-title nino-article-title--loud">fullwidth, cols, loud</h3>
					<p class="nino-article-descr nino-article-descr--quiet">'. $lorem. '</p>
				</div>
			</article>
		</div></div>
	</section>';

	// --- Buttons: all eight, on a plain and on a dark surface ---
	$buttons = static function(): string {
		$html = '';
		foreach( [ '' => 'btn', '--primary' => 'primary', '--outline' => 'outline', '--brand-alt' => 'brand-alt', '--light' => 'light', '--dark' => 'dark' ] as $modifier => $label )
			$html .= '<a class="nino-btn'. ( $modifier !== '' ? ' nino-btn'. $modifier : '' ). '" href="#">'. $label. '</a> ';
		return $html. '<a class="nino-btn nino-btn--primary nino-btn--big" href="#">big</a> <a class="nino-btn nino-btn--primary nino-btn--small" href="#">small</a>';
	};

	$out[] = '<section id="buttons" class="nino-section" aria-labelledby="buttons-title">
		<div class="nino-grid-row"><div class="nino-grid-100">
			<h2 class="nino-section-title" id="buttons-title">Buttons</h2>
			<p>'. $buttons(). '</p>
		</div></div>
	</section>';
	$out[] = '<section class="nino-section nino-section--dark" aria-label="Buttons auf dunkler Fläche">
		<div class="nino-grid-row"><div class="nino-grid-100"><p>'. $buttons(). '</p></div></div>
	</section>';

	// --- Forms: the contact page's shape, plus the states ---
	$out[] = '<section id="forms" class="nino-section nino-section--alt" aria-labelledby="forms-title">
		<div class="nino-grid-row">
			<div class="nino-grid-100 nino-grid-m-50">
				<h2 class="nino-section-title" id="forms-title">Forms</h2>
				<form class="nino-form" action="#" method="post" onsubmit="return false">
					<label for="p-name">Name *</label>
					<input type="text" id="p-name" name="name" class="nino-form-input" value="Ada Lovelace" required>
					<label for="p-mail">E-Mail *</label>
					<input type="email" id="p-mail" name="email" class="nino-form-input" placeholder="ada@example.org" required>
					<label for="p-topic">Thema</label>
					<select id="p-topic" name="topic" class="nino-form-select"><option>Anfrage</option><option>Angebot</option></select>
					<label for="p-message">Nachricht *</label>
					<textarea id="p-message" name="message" class="nino-form-textarea" required>Zwei Zeilen, damit die Höhe und der Innenabstand sichtbar sind.</textarea>
					<p class="nino-form-message" aria-live="polite"></p>
					<p><small>* Pflichtfeld</small></p>
					<button type="submit" class="nino-btn nino-btn--primary nino-form-submit">Senden</button>
				</form>
			</div>
			<div class="nino-grid-100 nino-grid-m-50">
				<h3 class="nino-section-subtitle">Zustände</h3>
				<form class="nino-form nino-is-error" action="#" onsubmit="return false"><p class="nino-form-message">Da fehlt noch etwas.</p></form>
				<form class="nino-form nino-is-success" action="#" onsubmit="return false"><p class="nino-form-message">Danke, ist angekommen.</p></form>
				<form class="nino-form nino-form--inline" action="#" onsubmit="return false">
					<input type="email" class="nino-form-input" placeholder="E-Mail" aria-label="E-Mail">
					<button type="submit" class="nino-btn nino-btn--primary nino-form-submit">Anmelden</button>
				</form>
			</div>
		</div>
	</section>';

	return implode( "\n", $out ). previewSpecimenTail( $lorem, $short );
}


/**
 *	The last third - lists, tables, badges, and the two building blocks that
 *	carry their own vocabulary
 *
 *	@param		string		$lorem				A paragraph long enough to wrap
 *	@param		string		$short				A line short enough to read at a glance
 *
 *	@return 	string								Html+
 */
function previewSpecimenTail( string $lorem, string $short ): string {

	$items = '<li>Erster Punkt</li><li>Ein zweiter, der lang genug ist, um umzubrechen und den Zeilenabstand zu zeigen</li><li>Dritter</li>';
	$out 		= [];

	$out[] = '<section id="lists" class="nino-section" aria-labelledby="lists-title">
		<div class="nino-grid-row"><div class="nino-grid-100"><h2 class="nino-section-title" id="lists-title">Listen &amp; Tabellen</h2></div></div>
		<div class="nino-grid-row">
			<div class="nino-grid-100 nino-grid-m-33"><p class="nino-section-subtitle">list</p><ul class="nino-list">'. $items. '</ul></div>
			<div class="nino-grid-100 nino-grid-m-33"><p class="nino-section-subtitle">check</p><ul class="nino-list nino-list--check">'. $items. '</ul></div>
			<div class="nino-grid-100 nino-grid-m-33"><p class="nino-section-subtitle">numbered</p><ol class="nino-list nino-list--numbered">'. $items. '</ol></div>
		</div>
		<div class="nino-grid-row">
			<div class="nino-grid-100 nino-grid-m-50"><p class="nino-section-subtitle">columns</p><ul class="nino-list nino-list--columns">'. $items. $items. '</ul></div>
			<div class="nino-grid-100 nino-grid-m-50"><p class="nino-section-subtitle">content</p><ul class="nino-list nino-list--content">'. $items. '</ul></div>
		</div>
		<div class="nino-grid-row"><div class="nino-grid-100">
			<p class="nino-section-subtitle">striped, bordered</p>
			<div class="nino-table-wrap"><table class="nino-table nino-table--striped nino-table--bordered">
				<thead><tr><th>Bauteil</th><th>Set</th><th>Stufe</th></tr></thead>
				<tbody><tr><td>Section</td><td>v1</td><td>default</td></tr><tr><td>Article</td><td>v1</td><td>less</td></tr><tr><td>Buttons</td><td>v2</td><td>more</td></tr></tbody>
			</table></div>
		</div></div>
		<div class="nino-grid-row"><div class="nino-grid-100">
			<p class="nino-section-subtitle">badges</p>
			<p class="nino-badge-cloud"><span class="nino-badge">plain</span> <span class="nino-badge nino-badge--primary">primary</span> <span class="nino-badge nino-badge--success">success</span> <span class="nino-badge nino-badge--error">error</span> <span class="nino-badge nino-badge--pill">pill</span></p>
		</div></div>
	</section>';

	// --- Building blocks: pricing and timeline, the two with their own words ---
	// A plan is a direct child of .nino-pricing-row, which is the flex container
	// itself - no grid column around it, or the row lays out its wrappers
	$plan = static function( string $title, string $price, bool $featured = false ): string {
		return '<div class="nino-pricing-item'. ( $featured === true ? ' nino-pricing-item--featured' : '' ). '">
			<h3 class="nino-pricing-title">'. $title. '</h3>
			<p class="nino-pricing-price">'. $price. '</p>
			<ul class="nino-list nino-list--check nino-pricing-features"><li>Ein Merkmal</li><li>Noch eines</li><li>Und ein drittes</li></ul>
			<p><a class="nino-btn nino-btn--primary" href="#">Wählen</a></p>
		</div>';
	};

	$out[] = '<section id="blocks" class="nino-section nino-section--tint" aria-labelledby="blocks-title">
		<div class="nino-grid-row"><div class="nino-grid-100"><h2 class="nino-section-title" id="blocks-title">Bausteine</h2><p class="nino-section-subtitle">Preispläne und Abläufe &mdash; eigenes Vokabular, eigenes Set</p></div></div>
		<div class="nino-grid-row"><div class="nino-grid-100">
			<div class="nino-pricing-row">
				'. $plan( 'Klein', '9 €' ). '
				'. $plan( 'Mittel', '29 €', true ). '
				'. $plan( 'Groß', '79 €' ). '
			</div>
		</div></div>
		<div class="nino-grid-row"><div class="nino-grid-100">
			<p class="nino-section-subtitle">timeline &mdash; counted (die Nummer erzeugt ein CSS-Zähler)</p>
			<ol class="nino-timeline nino-timeline--counted">
				<li class="nino-timeline-step"><h3 class="nino-article-title">Auswählen</h3><p class="nino-article-descr">'. $short. '</p></li>
				<li class="nino-timeline-step"><h3 class="nino-article-title">Anpassen</h3><p class="nino-article-descr">'. $lorem. '</p></li>
				<li class="nino-timeline-step"><h3 class="nino-article-title">Kompilieren</h3><p class="nino-article-descr">'. $short. '</p></li>
			</ol>
		</div></div>
		<div class="nino-grid-row"><div class="nino-grid-100 nino-grid-m-66">
			<p class="nino-section-subtitle">timeline &mdash; stacked, mit eigener Nummer</p>
			<ol class="nino-timeline nino-timeline--stacked">
				<li class="nino-timeline-step"><span class="nino-timeline-number">01</span><div><h3 class="nino-article-title">Auswählen</h3><p class="nino-article-descr">'. $short. '</p></div></li>
				<li class="nino-timeline-step"><span class="nino-timeline-number">02</span><div><h3 class="nino-article-title">Anpassen</h3><p class="nino-article-descr">'. $short. '</p></div></li>
			</ol>
		</div></div>
	</section>';

	$out[] = '[template /templates/theme.footer]';

	return "\n". implode( "\n", $out );
}


/**
 *	The bar across the top: what is on screen, and what could not be found.
 *	It is outside .nino-* on purpose - a set must not be able to restyle the
 *	thing that tells you which set you are looking at
 *
 *	@param		array 		$notes				What previewProject()/previewCss() had to say
 *
 *	@return 	string								Plain html, no fills
 */
function previewBar( array $notes ): string {

	$chips = '';
	foreach( PARTS as $part => $value ) {
		[ $name, $step ] = previewPart( $part );
		$chips .= '<a href="#'. $part. '"><b>'. $part. '</b> '. htmlspecialchars( $name, ENT_QUOTES )
			. ( $step !== 'default' ? ' <i>'. htmlspecialchars( $step, ENT_QUOTES ). '</i>' : '' ). '</a>';
	}

	$warn = '';
	foreach( $notes as $note )
		$warn .= '<p>'. htmlspecialchars( $note, ENT_QUOTES ). '</p>';

	return '<div id="preview-bar">
		<style>
			/*	Along the bottom, not the top. The header a frame brings is fixed
			    at top: 0 - that is its design - so a bar up there would sit over
			    the one thing half this page exists to judge. Down here it only
			    ever meets the footer, and only at the very end of the scroll. */
			body { padding-bottom: 2.6rem; }
			#preview-bar { position: fixed; left: 0; right: 0; bottom: 0; z-index: 9999; background: #14181d; color: #e7ebf0; font: 12px/1.5 ui-monospace, SFMono-Regular, Menlo, monospace; padding: .5rem .75rem; }
			#preview-bar a { display: inline-block; color: #e7ebf0; text-decoration: none; background: #232a32; border-radius: 3px; padding: .15rem .45rem; margin: .1rem .25rem .1rem 0; }
			#preview-bar a:hover { background: #323c47; }
			#preview-bar b { color: #7fc4ff; font-weight: 600; }
			#preview-bar i { color: #ffcc7f; font-style: normal; }
			#preview-bar p { margin: .35rem 0 0; color: #ffb4a2; }
		</style>
		'. $chips. $warn. '
	</div>';
}


// ---- the routes this harness answers ------------------------------------

$path = (string) parse_url( (string) ( $_SERVER['REQUEST_URI'] ?? '/' ), PHP_URL_PATH );
$notes = [];

// The three webfaces theme.css declares, straight out of the base unit
if( str_starts_with( $path, '/fonts/' ) === true ) {

	$font = $root. '/_admin/install/library/base/fonts/'. basename( $path );

	if( is_file( $font ) === false ) {
		http_response_code( 404 );
		exit;
	}

	header( 'Content-Type: font/woff2' );
	header( 'Cache-Control: max-age=3600' );
	readfile( $font );
	exit;
}

/*	Every image the specimen and the frames ask for, as one generated
	placeholder. Shipping binaries for a design harness would mean choosing
	pictures, and a picture is a design decision this page must not make for
	whoever is looking at it */
if( str_starts_with( $path, '/images/' ) === true ) {

	header( 'Content-Type: image/svg+xml' );
	header( 'Cache-Control: max-age=3600' );
	exit( '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 420" role="img" aria-label="Platzhalter">
		<rect width="640" height="420" fill="#d8dee6"/>
		<path d="M0 300l170-130 130 100 110-80 230 170z" fill="#b9c3cf"/>
		<circle cx="480" cy="110" r="46" fill="#c9d2dc"/>
	</svg>' );
}

/*	The behaviour the specimen depends on: the scroll-away header, the burger,
	the cover heights and the arrow that scrolls on. Served from the checkout so
	the harness stays one file with no build step */
if( $path === '/nino.js' || $path === '/nino.ui.js' ) {

	$script = $root. '/_nino/'. ( $path === '/nino.js' ? 'Nino.js' : 'Nino.ui.js' );

	if( is_file( $script ) === false ) {
		http_response_code( 404 );
		exit;
	}

	header( 'Content-Type: text/javascript; charset=utf-8' );
	header( 'Cache-Control: no-store' );
	readfile( $script );
	exit;
}

// The browser asks for this on its own; answering it keeps the console clean,
// and a clean console is worth something in a tool you stare at
if( $path === '/favicon.ico' ) {
	http_response_code( 204 );
	exit;
}

/*	The framework, as its own bundle entry - the compiler does not emit it and
	must not: /_nino/Nino.css is the first entry of the css bundle and
	assets/theme.css the second, and the preview links them in that order for
	the same reason a project loads them in it */
if( $path === '/nino.css' ) {
	header( 'Content-Type: text/css; charset=utf-8' );
	header( 'Cache-Control: no-store' );
	readfile( $root. '/_nino/Nino.css' );
	exit;
}

if( $path === '/preview.css' ) {
	header( 'Content-Type: text/css; charset=utf-8' );
	header( 'Cache-Control: no-store' );
	exit( previewCss( $library, $notes ) );
}

if( $path !== '/' && $path !== '/index.php' ) {
	http_response_code( 404 );
	exit( "preview.php serves /, /preview.css, /fonts/* and /images/*.\n" );
}


// ---- the page -----------------------------------------------------------

$appData = previewProject( $root, $library, $notes );
$body 	= \Nino\Html::renderHtml( $appData, previewSpecimen() );

// previewCss() finds the rest of the notes, and the bar is printed after it
previewCss( $library, $notes );

$unresolved = [];
if( preg_match_all( '/\[\[[^\]]{1,60}\]\]/', $body, $left ) > 0 )
	$unresolved = array_slice( array_unique( $left[0] ), 0, 6 );

if( $unresolved !== [] )
	$notes[] = 'unresolved fills in the render: '. implode( ' ', $unresolved );

header( 'Content-Type: text/html; charset=utf-8' );
header( 'Cache-Control: no-store' );

echo '<!doctype html>
<html lang="', substr( LOCALE, 0, 2 ), '">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Design preview</title>
<link rel="stylesheet" href="/nino.css">
<link rel="stylesheet" href="/preview.css">
</head>
<body>
', previewBar( $notes ), '
<main>', $body, '</main>
<script src="/nino.js"></script>
<script src="/nino.ui.js"></script>
</body>
</html>';
