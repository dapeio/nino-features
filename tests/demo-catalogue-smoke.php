<?php
declare(strict_types=1);

/**
 *	Nino features
 *	demo-catalogue-smoke.php	The "Demo: Catalogue" page unit of the kernel
 *														(_admin/install/library/pages/.demo-catalogue) is
 *														the one page that draws every building block of
 *														Nino.css, and a catalogue of features that bring
 *														classes of their own has to know the page still
 *														does. That is what this measures: the unit's own
 *														manifest and files, every nino-* class Nino.css
 *														defines on the page (or on the short list of what
 *														a frame, a module, a feature (the Builder writes
 *														nino-wrap) or Nino.ui.js draws instead), and a
 *														page that renders from what every installation of
 *														it has. Not the wording, which is free to change,
 *														and not the markup.
 *
 *														Runs against the checkout beside this repository
 *														(../nino) or the one NINO_ROOT names.
 *
 *	Usage: php tests/demo-catalogue-smoke.php
 *	       NINO_ROOT=/path/to/nino php tests/demo-catalogue-smoke.php
 */

// The Nino checkout is the one NINO_ROOT names or the one beside this repository
define( 'NINO', realpath( getenv( 'NINO_ROOT' ) ?: dirname( __DIR__, 2 ). '/nino' ) ?: '' );

if( is_file( NINO. '/_nino/Nino.php' ) === false ) {
	fwrite( STDERR, 'No Nino checkout at '. ( getenv( 'NINO_ROOT' ) ?: dirname( __DIR__, 2 ). '/nino' ). ' - clone https://github.com/dapeio/nino beside this repository or set NINO_ROOT'. "\n" );
	exit( 2 );
}

require NINO. '/_nino/Nino.php';
require NINO. '/_admin/Admin.php';
require NINO. '/_admin/install/Install.php';

$failures = 0;
$checks		= 0;

/**
 *	Assert a condition and print the result
 *
 *	@param		string		$label				Description of the check
 *	@param		bool			$condition		Result to assert
 *
 *	@return		void
 */
function check( string $label, bool $condition ): void {
	global $failures, $checks;
	$checks++;
	if( $condition === true ) {
		echo "  ok  - $label\n";
		return;
	}
	$failures++;
	echo "FAIL  - $label\n";
}

$unit 		= NINO. '/_admin/install/library/pages/.demo-catalogue';
$template = $unit. '/templates/.demo-catalogue.tpl';


// --- The unit itself -------------------------------------------------------

echo "Unit\n";

$manifest = is_file( $unit. '/manifest.php' ) === true ? include $unit. '/manifest.php' : null;

check( 'the unit ships a manifest', is_array( $manifest ) === true );
check( 'it mounts itself on a dot-uri, out of the way of a project\'s own pages', array_key_first( (array) ( $manifest['routes'] ?? [] ) ) === 'GET://.demo-catalogue' );
check( 'the route renders the page template', ( array_values( (array) ( $manifest['routes'] ?? [] ) )[0]['body'] ?? '' ) === '[template /templates/.demo-catalogue]' );

foreach( (array) ( $manifest['templates'] ?? [] ) as $file )
	check( 'declared template exists: '. $file, is_file( $unit. '/templates/'. $file ) === true );

foreach( (array) ( $manifest['files'] ?? [] ) as $file )
	check( 'declared file exists: '. $file, file_exists( $unit. '/'. $file ) === true );

// Every image the page paints with has to be one the unit brings itself -
// pointing at something another unit installs is exactly how the previous
// demo page ended up rendering empty frames
$source = (string) file_get_contents( $template );
preg_match_all( '#\[\[/nino/public\]\](/images/[^"\']+)#', $source, $imageMatches );
$images = array_values( array_unique( $imageMatches[1] ) );

check( 'the page paints with images, and every one of them ships with the unit'. ( $images === [] ? ' - none referenced' : '' ),
	$images !== [] && array_filter( $images, static fn( string $image ): bool => is_file( $unit. $image ) === false ) === [] );

echo "\n";


// --- Nino.css classes ------------------------------------------------------

echo "Nino.css classes\n";

$css = (string) file_get_contents( NINO. '/_nino/Nino.css' );

// Comments carry the per-chapter class inventory, which would otherwise count
// as a definition - and .nino-grid, .nino-text and .nino-opacity exist only
// there, as the shorthand for their own families
$css = (string) preg_replace( '#/\*.*?\*/#s', '', $css );

preg_match_all( '/\.(nino-[a-z0-9-]+)/', $css, $cssMatches );
$defined = array_values( array_unique( $cssMatches[1] ) );

preg_match_all( '/class="([^"]*)"/', $source, $classMatches );
$used = [];
foreach( $classMatches[1] as $attribute )
	foreach( preg_split( '/\s+/', trim( $attribute ) ) ?: [] as $class )
		if( str_starts_with( $class, 'nino-' ) === true )
			$used[$class] = true;

/*	Classes no page template can carry, with the reason each one is here.
	Nothing else belongs in this list: if a class is missing from the catalogue
	and it is not owned by a frame or written by Nino.ui.js, the catalogue is
	incomplete and this test is the place that says so.	*/
$elsewhere = [
	// The header and footer units own these - every page renders them, none
	// declares them (see _admin/install/library/header|footer/<key>/template.tpl)
	'nino-logo', 'nino-headernav-logo', 'nino-nav-burger', 'nino-nav-content', 'nino-nav-bg',
	'nino-footer-main', 'nino-footer-legal', 'nino-footer-title', 'nino-footer-logo',
	'nino-footer-getintouch', 'nino-footer-localepicker', 'nino-localepicker-wrap', 'nino-localepicker-bg',
	'nino-scroll-header',
	// Nino.ui.js writes these onto the page while it runs: page chrome it
	// creates itself, and the state classes it toggles
	'nino-preloader', 'nino-back-to-top',
	'nino-toast', 'nino-toast--success', 'nino-toast--error', 'nino-toast--visible', 'nino-toast-container',
	'nino-slider-controls', 'nino-slider-button', 'nino-slider-points', 'nino-slider-point',
	'nino-scroll-atf', 'nino-scroll-btf', 'nino-scroll-down',
	'nino-is-touch', 'nino-vpa--visible', 'nino-vpa--visible-once',
];

/*	Nino up to 1.3.2 wrote a cookie banner of its own, and Nino.css carries the
	three classes of it; a later Nino ships none and Nino.css drops them (the
	Consent feature is the banner now). The names count as "rendered elsewhere"
	only while the checkout's Nino.css still defines them, so this test passes
	on both - and the list can go once the latest Nino tag no longer has them.	*/
$retired = [ 'nino-cookie-banner', 'nino-cookie-banner--visible', 'nino-cookie-banner-actions' ];
$elsewhere = array_merge( $elsewhere, array_values( array_intersect( $retired, $defined ) ) );

/*	Nino up to 1.4.0 styles a form state .nino-is-existing that nothing has
	set since 1.0.0-beta, and Nino.ui.js asks for it; a later Nino drops both.
	The same rule as above: it counts as "rendered elsewhere" only while the
	checkout's Nino.css still defines it - the line can go once the latest
	Nino tag no longer has it.	*/
$elsewhere = array_merge( $elsewhere, array_values( array_intersect( [ 'nino-is-existing' ], $defined ) ) );

/*	Nino 1.4's Legal module draws every section of the imprint and the privacy
	policy in a .nino-legal-section, and Nino.css styles it. No page template
	of a unit can carry it - the module's shortcodes write it - so it counts as
	"rendered elsewhere" only while the checkout's Nino.css defines it, and this
	test passes on a Nino without the module as well	*/
$drawnByModules = [ 'nino-legal-section',
	// Nino 1.6's Components module: the section background, the columns a
	// viewport hides, the component stack and its gaps and the image frame -
	// written by the components, never by a unit's template. nino-wrap, the
	// wrapper around a page's sections, is written by the Builder (a feature)
	'nino-wrap', 'nino-section-bg', 'nino-hide-s', 'nino-hide-m', 'nino-hide-l',
	'nino-stack', 'nino-stack-start', 'nino-stack-center', 'nino-stack-end',
	'nino-stack-gap-0', 'nino-stack-gap-1', 'nino-stack-gap-2', 'nino-stack-gap-3', 'nino-stack-gap-4', 'nino-stack-gap-5', 'nino-stack-gap-6',
	'nino-image', 'nino-image--1-1', 'nino-image--4-3', 'nino-image--3-2', 'nino-image--16-9', 'nino-image--21-9',
];
$elsewhere = array_merge( $elsewhere, array_values( array_intersect( $drawnByModules, $defined ) ) );

$uncovered = [];
foreach( $defined as $class )
	if( isset( $used[$class] ) === false && in_array( $class, $elsewhere, true ) === false )
		$uncovered[] = $class;

check( 'every class Nino.css defines is on the page'. ( $uncovered === [] ? '' : ' - missing '. implode( ', ', $uncovered ) ), $uncovered === [] );

$stale = [];
foreach( $elsewhere as $class )
	if( in_array( $class, $defined, true ) === false )
		$stale[] = $class;

check( 'the "rendered elsewhere" list carries no class Nino.css dropped'. ( $stale === [] ? '' : ' - '. implode( ', ', $stale ) ), $stale === [] );

echo "\n";


// --- What the page must not contain ----------------------------------------

echo "Rendering\n";

/*	Every fill left on the page has to come from somewhere every installation
	of it has: the base unit, a module the manifest actually requires, or the
	unit's own text/ fragments. A fill from anywhere else renders as its own
	key on a project that did not happen to pick that module - the newsletter
	specimen's labels are the case in point: Newsletter is a feature, not a
	wizard unit, so the page carries those two labels itself.	*/
// Two fills no unit writes: \Nino\request() registers /nino/dir and
// /nino/public from config.php on every request, so a page may leave them
// and every installation resolves them
preg_match_all( '/\[\[([^\]]+)\]\]/', $source, $fillMatches );
$fills = array_values( array_unique( array_filter( $fillMatches[1], static fn( string $fill ): bool => in_array( $fill, [ '/nino/dir', '/nino/public' ], true ) === false ) ) );

$library 	= NINO. '/_admin/install/library';
// A module's unit sits beside the module itself - Setup::units() knows where
$units 		= \Nino\Install\Setup::units();
$available = [];
$fragments = array_merge( glob( $library. '/base/text/*.php' ) ?: [], glob( $unit. '/text/*.php' ) ?: [] );
foreach( (array) ( $manifest['requiresModules'] ?? [] ) as $module )
	$fragments = array_merge( $fragments, isset( $units[$module] ) === true ? ( glob( $units[$module]. '/text/*.php' ) ?: [] ) : [] );
foreach( $fragments as $fragment )
	foreach( array_keys( (array) ( include $fragment ) ) as $key )
		$available[trim( (string) $key, '[]' )] = true;

$unprovided = array_values( array_filter( $fills, static fn( string $fill ): bool => isset( $available[$fill] ) === false ) );

check( 'every textfill the page leaves comes from the base unit or a required module'. ( $unprovided === [] ? '' : ' - '. implode( ', ', $unprovided ) ), $unprovided === [] );
check( 'and the modules those fills need are declared', $fills === [] || ( $manifest['requiresModules'] ?? [] ) !== [] );

preg_match_all( '/\[(elements|elementvalues|image|nav|localepicker)\b/', $source, $shortcodeMatches );
check( 'no shortcode is left that needs content this unit does not ship'. ( $shortcodeMatches[0] === [] ? '' : ' - '. implode( ', ', array_unique( $shortcodeMatches[0] ) ) ), $shortcodeMatches[0] === [] );

// [template] is allowed exactly three times: the two page slots and the one
// reusable include the page demonstrates
preg_match_all( '/\[template\s+([^\]]+)\]/', $source, $templateMatches );
check( 'the page includes the shell and the one demonstrated template, nothing else',
	$templateMatches[1] === [ '/templates/html-header', '/templates/demo-catalogue-include', '/templates/html-footer' ] );

// Inline styles are how a demo page hides a missing class. If a specimen
// needs one, the framework is what should change
check( 'no specimen falls back to an inline style', preg_match( '/\sstyle="/i', $source ) !== 1 );

// Every <section> closes after it opens and none is left over
$depth = 0;
$balanced = true;
preg_match_all( '#<(/?)section\b#', $source, $sectionTags );
foreach( $sectionTags[1] as $closing ) {
	$depth += $closing === '/' ? -1 : 1;
	$balanced = $balanced && $depth >= 0;
}
check( 'it is built from sections, not from one big block, and every section closes after it opens', $balanced && $depth === 0 && count( $sectionTags[1] ) > 100 );

echo "\n";

echo $checks. ' checks, '. $failures. " failed\n";
exit( $failures === 0 ? 0 : 1 );
