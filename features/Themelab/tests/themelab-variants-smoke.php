<?php
declare(strict_types=1);

/**
 *	Nino features
 *	themelab-variants-smoke.php	Contract test for what the editor of the blocks defines of a block: its details - the names and the descriptions in each language, the category, the
 *															rank, the places of the pictures - and its variants:
 *
 *															A. the details: read with the sheet, written with the block, held to the rule of each, in each language
 *															B. the variants: made by the server from the draft, in the form of the kernel; the kernel takes them, tells each variant of a block put in, and sets it
 *															C. what a variant may not hold: a source, a text, a link target, an id, the class, a foreign attribute, a foreign place - refused with a sentence, nothing written
 *															D. from the block to the page: the choice of the variant in the Builder and the setting of it, end to end
 *
 *	Usage: php features/Themelab/tests/themelab-variants-smoke.php
 *				 NINO_ROOT=../nino php features/Themelab/tests/themelab-variants-smoke.php
 */

$root = getenv( 'NINO_ROOT' ) ?: dirname( __DIR__, 3 );
defined( 'NINO_FEATURES_DIR' ) === true || define( 'NINO_FEATURES_DIR', dirname( __DIR__, 2 ) );
require $root. '/tests/harness.php';

use Nino\Modules\Builder\Library;
use Nino\Modules\Builder\Tree;

function variantsCall( array &$appData, string $action, array $data = [] ): array {

	$data += [ 'locale' => \Nino\Locales::getNativeLocale( $appData ) ];

	[ $status, $body ] = ninoCall( $appData, [ \Nino\Admin\Admin::class, 'handlePost' ], $data, $action );

	// The answer as the browser reads it: objects are maps
	return [ $status, json_decode( (string) json_encode( $body ), true ) ];
}

$site = ninoSandbox( 'blocks-variants' );
$sandbox = ninoSandboxDir( $site );
$site['./nino/filesystem/path'] = $root;
$site['/nino/html/assets'] = [];
$site['/nino/html/navs'] = [ 'main' ];
$site['/nino/modules'] = [ '\\Nino\\Modules\\Assets', '\\Nino\\Modules\\Template', '\\Nino\\Modules\\Navigation', '\\Nino\\Modules\\Basis', '\\Nino\\Modules\\Collections', '\\Nino\\Modules\\Builder', '\\Nino\\Modules\\Themelab' ];
\Nino\Html::init( $site );
\Nino\Features::registerComponents( $site );
\Nino\Modules::callModules( $site, 'init' );

\Nino\Filesystem::putFileContent( $site, '/templates/html-header.tpl', "<header>head</header>\n" );
\Nino\Filesystem::putFileContent( $site, '/templates/html-footer.tpl', "<footer>foot</footer>\n" );
\Nino\Filesystem::putFileContent( $site, '/templates/page-home.tpl', "<!-- nino:template-name Home -->\n[template /templates/html-header]\n[template /templates/html-footer]\n" );
foreach( [ 'de_DE', 'en_US' ] as $locale )
	\Nino\Filesystem::putFileContent( $site, '/text/'. $locale. '.php', [ '[[/template/page-home/hero/title]]' => 'Hallo' ] );

$site['/nino/http/routes'] = [ 'GET://home' => [ 'uri' => '/home', 'body' => '[template /templates/page-home]' ] ];

ninoUser( $site, 'blocks@example.com', [ \Nino\Modules\Themelab::PERM ] );

$folder = static fn( string $key ): array => array_map( static fn( string $file ): string => hash_file( 'sha256', $file ), array_merge( (array) glob( $sandbox. '/private/blocks/'. $key. '/block.json' ), (array) glob( $sandbox. '/private/blocks/'. $key. '/block.tpl' ), (array) glob( $sandbox. '/private/blocks/'. $key. '/text/*.json' ) ) );
$meta = static fn( string $key ): array => (array) json_decode( (string) file_get_contents( $sandbox. '/private/blocks/'. $key. '/block.json' ), true );

[ , $created ] = variantsCall( $site, 'themelab/create', [ 'name' => [ 'de_DE' => 'Mein Split', 'en_US' => 'My split' ], 'category' => 'content', 'from' => 'split-image-text' ] );
$key = (string) ( $created['key'] ?? '' );
[ , $draft ] = variantsCall( $site, 'themelab/open', [ 'key' => $key ] );
$save = static function( array $extra ) use ( &$site, $key, &$draft ): array {

	$answer = variantsCall( $site, 'themelab/save', [ 'key' => $key, 'tree' => $draft['tree'], 'texts' => $draft['texts'], 'base' => $draft['stamp'] ] + $extra );

	return $answer;
};

echo "\nA. The details\n";

check( 'the sheet brings the details of the block with it: its name and description in each language, the category, the rank, and each place of a picture with its words, placeholder, size and whether a call still reads it - and the variants it has, the first being the template',
	( $draft['details']['names']['de_DE'] ?? '' ) === 'Mein Split' && ( $draft['details']['descriptions']['en_US'] ?? '' ) !== '' && $draft['details']['category'] === 'content' && is_int( $draft['details']['rank'] )
	&& ( $draft['details']['images']['picture']['used'] ?? false ) === true && ( $draft['details']['images']['picture']['width'] ?? 0 ) === 1200 && count( $draft['alternatives'] ) === 2 && $draft['alternatives'][0]['name'] === 'image-left' && (array) $draft['alternatives'][0]['set'] === [] );

$before = $folder( $key );
$details = [ 'names' => [ 'de_DE' => 'Neuer Name', 'en_US' => 'New name' ], 'descriptions' => [ 'de_DE' => 'Eine Beschreibung', 'en_US' => 'A description' ], 'category' => 'trust', 'rank' => 42, 'images' => [ 'picture' => [ 'placeholder' => '/nino/placeholder/mark', 'width' => 800, 'height' => 600, 'label' => [ 'de_DE' => 'Das Bild', 'en_US' => 'The picture' ] ] ] ];
[ $saved ] = $save( [ 'details' => $details ] );
$now = $meta( $key );

check( 'the details are written with the block, in each language, as they were given - the names and the descriptions, the category, the rank, the label, placeholder and size of a picture place - and the kernel lists the block so',
	$saved === 200 && $now['name'] == [ 'de_DE' => 'Neuer Name', 'en_US' => 'New name' ] && $now['description']['en_US'] === 'A description' && $now['category'] === 'trust' && $now['rank'] === 42 && $now['images']['picture']['width'] === 800 && $now['images']['picture']['placeholder'] === '/nino/placeholder/mark' && $now['images']['picture']['label']['de_DE'] === 'Das Bild'
	&& \Nino\Blocks::all( $site )[$key]['category'] === 'trust' && \Nino\Blocks::all( $site )[$key]['rank'] === 42 );

// a description that is not brought stays: the details of one language never write another language's, and a description that is emptied has the name only where the kernel asks for one
[ $renamedOne ] = variantsCall( $site, 'themelab/rename', [ 'key' => $key, 'name' => $now['name'], 'description' => [ 'en_US' => 'Only English' ] ] );
$one = $meta( $key );
[ $renamedEmpty ] = variantsCall( $site, 'themelab/rename', [ 'key' => $key, 'name' => $now['name'], 'description' => [ 'en_US' => '' ] ] );
$empty = $meta( $key );

check( 'a description that the request does not bring stays as it is - another language\'s is never written by it, and never made the name - and one that is emptied is the name of that language alone',
	$renamedOne === 200 && $one['description']['de_DE'] === 'Eine Beschreibung' && $one['description']['en_US'] === 'Only English'
	&& $renamedEmpty === 200 && $empty['description']['de_DE'] === 'Eine Beschreibung' && $empty['description']['en_US'] === $now['name']['en_US'] );

[ , $draft ] = variantsCall( $site, 'themelab/open', [ 'key' => $key ] );
$refusals = [];
$before = $folder( $key );

foreach( [
	'a rank over 999' => [ [ 'rank' => 1000 ], 'themelab_rank' ],
	'a rank that is no number' => [ [ 'rank' => 'zehn' ], 'themelab_rank' ],
	'the category dynamic content, which a block gets by itself' => [ [ 'category' => 'dynamic' ], 'themelab_category' ],
	'the category of a header, which is no category of a page' => [ [ 'category' => 'header' ], 'themelab_category' ],
	'a description with markup' => [ [ 'descriptions' => [ 'de_DE' => 'a <b>' ] ], 'themelab_description' ],
	'a placeholder the kernel has not' => [ [ 'images' => [ 'picture' => [ 'placeholder' => '/x', 'width' => 100, 'height' => 100, 'label' => [ 'de_DE' => 'x' ] ] ] ], 'themelab_picture_place' ],
	'a picture of no size' => [ [ 'images' => [ 'picture' => [ 'placeholder' => '/nino/placeholder/mark', 'width' => 5, 'height' => 100, 'label' => [ 'de_DE' => 'x' ] ] ] ], 'themelab_picture_place' ],
	'a label with markup' => [ [ 'images' => [ 'picture' => [ 'placeholder' => '/nino/placeholder/mark', 'width' => 100, 'height' => 100, 'label' => [ 'de_DE' => '<i>' ] ] ] ], 'themelab_picture_label' ],
] as $name => [ $wrong, $code ] ) {

	[ $status, $body ] = $save( [ 'details' => $wrong ] );
	$refusals[$name] = $status === 400 && ( $body['code'] ?? '' ) === $code;
}

check( 'what a detail may not be is refused with a sentence of its own, and nothing is written: a rank beyond 999 or no number, the category of dynamic content or of the frames, a description or a label with markup, a place of a picture with no placeholder of the kernel or no size',
	array_filter( $refusals, static fn( bool $ok ): bool => $ok === false ) === [] && $folder( $key ) === $before );

[ $stale ] = variantsCall( $site, 'themelab/save', [ 'key' => $key, 'tree' => $draft['tree'], 'texts' => $draft['texts'], 'base' => 'old', 'details' => $details ] );

check( 'the details are saved with the block under one stamp: a second session that opened the block earlier is told, 409, and nothing is written', $stale === 409 && $folder( $key ) === $before );

echo "\nB. The variants\n";

[ , $draft ] = variantsCall( $site, 'themelab/open', [ 'key' => $key ] );
$label = static fn( string $de, string $en ): array => [ 'de_DE' => $de, 'en_US' => $en ];
$standard = [ 'name' => 'image-left', 'label' => $label( 'Bild links', 'Picture left' ), 'set' => [] ];
$right = [ 'name' => 'image-right', 'label' => $label( 'Bild rechts', 'Picture right' ), 'set' => [ '1' => [ 'reverse' => 'on' ] ] ];
$tinted = [ 'name' => 'right-tinted', 'label' => $label( 'Bild rechts, getönt', 'Picture right, tinted' ), 'set' => [ 'root' => [ 'ground' => 'tint' ], '1' => [ 'reverse' => 'on' ] ] ];
[ $saved ] = $save( [ 'variants' => [ $standard, $right, $tinted ] ] );
$written = $meta( $key )['variants'] ?? [];
$block = \Nino\Blocks::get( $site, $key );
$root = Library::block( $site, $key )['tree'][0];
$told = [];

foreach( [ 'image-left', 'image-right', 'right-tinted' ] as $name )
	$told[$name] = \Nino\Blocks::variantOf( $block, [ \Nino\Blocks::setVariant( $block, [ $root ], $name )[0] ] );

check( 'the variants are written in the form of the kernel - a label in each language and a set of places - made from the draft; the kernel takes the block, lists each variant in the order of the file, and tells every one of them at a block that is put in with its set',
	$saved === 200 && array_keys( $written ) === [ 'image-left', 'image-right', 'right-tinted' ] && (array) $written['image-left']['set'] === [] && $written['right-tinted']['set']['root']['ground'] === 'tint' && $written['right-tinted']['label']['en_US'] === 'Picture right, tinted'
	&& array_keys( \Nino\Blocks::all( $site )[$key]['alternatives'] ) === [ 'image-left', 'image-right', 'right-tinted' ] && $told === [ 'image-left' => 'image-left', 'image-right' => 'image-right', 'right-tinted' => 'right-tinted' ] );

[ , $draft ] = variantsCall( $site, 'themelab/open', [ 'key' => $key ] );
[ $reordered ] = $save( [ 'variants' => [ $standard, $tinted, array_replace( $right, [ 'label' => $label( 'Rechts', 'Right' ) ] ) ] ] );
$after = $meta( $key )['variants'] ?? [];

check( 'a variant is renamed, moved and taken away by the draft: the order is the order of the list, the first stays the template, and what is not in the list is not in the file',
	$reordered === 200 && array_keys( $after ) === [ 'image-left', 'right-tinted', 'image-right' ] && $after['image-right']['label']['de_DE'] === 'Rechts' );

[ , $draft ] = variantsCall( $site, 'themelab/open', [ 'key' => $key ] );
[ $dropped ] = $save( [ 'variants' => [ $standard, $right ] ] );
$remaining = array_keys( $meta( $key )['variants'] ?? [] );
[ , $draft ] = variantsCall( $site, 'themelab/open', [ 'key' => $key ] );
[ $alone ] = $save( [ 'variants' => [ $standard ] ] );

check( 'a variant that is taken out is gone from the file, and the standard alone is no choice: the block has no variants then, and the page that had one keeps its look',
	$dropped === 200 && $remaining === [ 'image-left', 'image-right' ] && $alone === 200 && array_key_exists( 'variants', $meta( $key ) ) === false );

[ , $draft ] = variantsCall( $site, 'themelab/open', [ 'key' => $key ] );
$save( [ 'variants' => [ $standard, $right ] ] );
$had = $meta( $key )['variants'] ?? [];
[ , $draft ] = variantsCall( $site, 'themelab/open', [ 'key' => $key ] );
[ $kept ] = $save( [ 'details' => [ 'rank' => 7 ] ] );

check( 'a save that brings no variants leaves the ones the block has, as it did - a draft of the details alone is no draft of the variants',
	$kept === 200 && $meta( $key )['rank'] === 7 && array_keys( $had ) === [ 'image-left', 'image-right' ] && ( $meta( $key )['variants'] ?? [] ) == $had );

// the first variant is the template, and a template nobody named is the standard: a block with no variants of its own is listed with a word for it in each language, and a
// save whose first variant has none - the person gave a name to the second only, and in one language - is no refusal but has the word of the standard
$blank = $key. '-blank';
[ , $made ] = variantsCall( $site, 'themelab/create', [ 'name' => $label( 'Ohne Namen', 'Nameless' ), 'category' => 'content', 'from' => $key ] );
$blank = $made['key'] ?? $blank;
[ , $draft ] = variantsCall( $site, 'themelab/open', [ 'key' => $blank ] );
$listed = \Nino\Modules\Themelab\Variants::listed( [ 'alternatives' => [] ], [ 'de_DE', 'en_US' ], 'de_DE' );
[ $nameless ] = variantsCall( $site, 'themelab/save', [ 'key' => $blank, 'tree' => $draft['tree'], 'texts' => $draft['texts'], 'base' => $draft['stamp'], 'variants' => [ [ 'name' => 'standard', 'label' => $label( '', '' ), 'set' => [] ], [ 'name' => 'compact', 'label' => [ 'de_DE' => 'Kompakt', 'en_US' => '' ], 'set' => [] ] ] ] );
$named = $meta( $blank )['variants'] ?? [];

check( 'a block that has no variants is listed with the word of the standard in each language, and a save whose first variant has no name - the second was named in one language - is taken: the standard has its word in each language, the second its word where it has none',
	$listed[0]['label'] === [ 'de_DE' => 'Standard', 'en_US' => 'Default' ] && $nameless === 200 && ( $named['standard']['label'] ?? [] ) == [ 'de_DE' => 'Standard', 'en_US' => 'Default' ] && ( $named['compact']['label'] ?? [] ) == [ 'de_DE' => 'Kompakt', 'en_US' => 'Kompakt' ] );

$strong = [ 'name' => 'strong', 'label' => $label( 'Kräftig', 'Strong' ), 'set' => [ '1.3.5' => [ 'tone' => 'primary' ] ] ];
[ , $draft ] = variantsCall( $site, 'themelab/open', [ 'key' => $key ] );
[ $again ] = $save( [ 'variants' => [ $standard, $right, $tinted, $strong ] ] );
[ , $draft ] = variantsCall( $site, 'themelab/open', [ 'key' => $key ] );

// the structure moves the places: a call put in before the title of the second column moves the button by two, and the draft brings the set at the place the button has now
$moved = $draft['tree'];
$rootAt = array_key_first( array_filter( $moved, static fn( array $node ): bool => ( $node['n'] ?? '' ) === 'section-wrap' ) );
array_splice( $moved[$rootAt]['c'][1]['c'][3]['c'], 1, 0, [ [ 'n' => 'text', 's' => '~intro', 'a' => [], 'c' => null ], [ 't' => "\n\t\t\t" ] ] );
$texts = $draft['texts'];
$texts['/template/'. $draft['template']. '/'. $moved[$rootAt]['a']['id']. '/intro'] = [ 'de_DE' => 'Neu', 'en_US' => 'New' ];
$shifted = [ $standard, $right, $tinted, array_replace( $strong, [ 'set' => [ '1.3.7' => [ 'tone' => 'primary' ] ] ] ) ];
[ $follows, $follow ] = variantsCall( $site, 'themelab/save', [ 'key' => $key, 'tree' => $moved, 'texts' => $texts, 'base' => $draft['stamp'], 'variants' => $shifted ] );
$moves = $meta( $key )['variants'] ?? [];

check( 'a draft that built the block anew brings the sets at the places they have now - they move with the calls they are about - and the kernel takes the block with them at those places',
	$again === 200 && $follows === 200 && isset( $moves['strong']['set']['1.3.7']['tone'] ) === true && isset( $moves['strong']['set']['1.3.5'] ) === false );

[ , $draft ] = variantsCall( $site, 'themelab/open', [ 'key' => $key ] );
$before = $folder( $key );
[ $stalePlace, $why ] = variantsCall( $site, 'themelab/save', [ 'key' => $key, 'tree' => $draft['tree'], 'texts' => $draft['texts'], 'base' => $draft['stamp'], 'variants' => [ $standard, $right, $tinted, array_replace( $strong, [ 'set' => [ '1.3.6' => [ 'tone' => 'primary' ] ] ] ) ] ] );

check( 'a set at a place where the draft has no call - a call that was taken out, a blank - is refused with a sentence that names the variant and the place, and nothing is written',
	$stalePlace === 400 && ( $why['code'] ?? '' ) === 'themelab_variant_place' && $why['params'] === [ 'Kräftig' ] && $folder( $key ) === $before );

echo "\nC. What a variant may not hold\n";

[ , $draft ] = variantsCall( $site, 'themelab/open', [ 'key' => $key ] );
$before = $folder( $key );
$set = static fn( array $set, string $name = 'bad' ): array => [ $standard, [ 'name' => $name, 'label' => $label( 'Schlecht', 'Bad' ), 'set' => $set ] ];
$refused = [];

foreach( [
	'a text as a value of the title'								=> [ $set( [ '1.3.1' => [ 'text' => 'Hallo' ] ] ), 'themelab_variant_attribute' ],
	'a source in the place of a look'								=> [ $set( [ '1.3.1' => [ 'size' => '~title' ] ] ), 'themelab_variant_value' ],
	'a link target'																	=> [ $set( [ '1.3.5' => [ 'href' => 'https://example.org' ] ] ), 'themelab_variant_attribute' ],
	'the id'																				=> [ $set( [ 'root' => [ 'id' => 'other' ] ] ), 'themelab_variant_attribute' ],
	'the class itself'															=> [ $set( [ 'root' => [ 'class' => 'x' ] ] ), 'themelab_variant_attribute' ],
	'an attribute the component has not'						=> [ $set( [ 'root' => [ 'nothing' => 'x' ] ] ), 'themelab_variant_attribute' ],
	'an attribute of a picture that is its source'	=> [ $set( [ '1.1.1' => [ 'image' => 'x' ] ] ), 'themelab_variant_attribute' ],
	'a place that is none'													=> [ $set( [ 'zz' => [ 'ground' => 'tint' ] ] ), 'themelab_variant_place' ],
	'a place of another block'											=> [ $set( [ '99.99' => [ 'ground' => 'tint' ] ] ), 'themelab_variant_place' ],
	'a value with markup'														=> [ $set( [ 'root' => [ 'ground' => '<b>' ] ] ), 'themelab_variant_value' ],
	'a value that is a fill'												=> [ $set( [ 'root' => [ 'ground' => '[[x]]' ] ] ), 'themelab_variant_value' ],
	'an empty value'																=> [ $set( [ 'root' => [ 'ground' => '' ] ] ), 'themelab_variant_value' ],
	'a value the attribute does not take'						=> [ $set( [ 'root' => [ 'ground' => 'purple' ] ] ), 'themelab_variant_value' ],
	'a set on the first variant, the template itself' => [ [ [ 'name' => 'image-left', 'label' => $label( 'Standard', 'Standard' ), 'set' => [ 'root' => [ 'ground' => 'tint' ] ] ], $right ], 'themelab_variant_first' ],
	'a name that is no name'												=> [ $set( [], 'Bad Name' ), 'themelab_variant_name' ],
	'a name twice'																	=> [ [ $standard, $right, $right ], 'themelab_variant_name' ],
	'a label with markup'														=> [ [ $standard, [ 'name' => 'x', 'label' => $label( '<i>', 'x' ), 'set' => [] ] ], 'themelab_variant_label' ],
	'the form of the kernel, a map'									=> [ [ 'x' => [ 'label' => $label( 'x', 'x' ), 'set' => [] ] ], 'themelab_variant_shape' ],
	'no list at all'																=> [ 'bad', 'themelab_variant_shape' ],
	'more than the kernel takes'										=> [ array_map( static fn( int $at ): array => [ 'name' => 'v'. $at, 'label' => $label( 'v', 'v' ), 'set' => [] ], range( 1, 9 ) ), 'themelab_variant_shape' ],
] as $name => [ $variants, $code ] ) {

	[ $status, $body ] = variantsCall( $site, 'themelab/save', [ 'key' => $key, 'tree' => $draft['tree'], 'texts' => $draft['texts'], 'base' => $draft['stamp'], 'variants' => $variants ] );
	$refused[$name] = $status === 400 && ( $body['code'] ?? '' ) === $code && $folder( $key ) === $before;
}

check( 'nothing comes into a block by the variants that the kernel does not allow in a set - a text, a source, a link target, the id, the class, an attribute the component has not or one that is no look, a place that is none, a value that is no plain word, markup in a name or a label, a map of the kernel instead of the draft\'s list, more than the kernel takes - each is refused with a sentence of its own, and nothing is written',
	array_filter( $refused, static fn( bool $ok ): bool => $ok === false ) === [] );

// the refusal says it in the words of the page: the component and where it stands, the setting by its label - no place of numbers, no name of the register
$find = static function( array $node, string $name, string $at = '' ) use ( &$find ): ?string {

	foreach( (array) ( $node['c'] ?? [] ) as $index => $child ) {

		$here = $at === '' ? (string) $index : $at. '.'. $index;

		if( ( $child['n'] ?? '' ) === $name )
			return $here;

		if( ( $found = $find( $child, $name, $here ) ) !== null )
			return $found;
	}

	return null;
};
$wrapAt = array_key_first( array_filter( $draft['tree'], static fn( array $one ): bool => ( $one['n'] ?? '' ) === 'section-wrap' ) );
$buttonAt = (string) $find( $draft['tree'][$wrapAt], 'button' );
[ , $worded ] = variantsCall( $site, 'themelab/save', [ 'key' => $key, 'tree' => $draft['tree'], 'texts' => $draft['texts'], 'base' => $draft['stamp'], 'variants' => $set( [ $buttonAt => [ 'href' => 'https://example.org' ] ] ) ] );
[ , $valued ] = variantsCall( $site, 'themelab/save', [ 'key' => $key, 'tree' => $draft['tree'], 'texts' => $draft['texts'], 'base' => $draft['stamp'], 'variants' => $set( [ 'root' => [ 'ground' => 'purple' ] ] ) ] );
$spoken = static fn( array $answer ): string => (string) ( $answer['message'] ?? '' ). ' '. implode( ' ', (array) ( $answer['params'] ?? [] ) );

check( 'a refusal of a variant names the component and where it stands - "Button in Column 2" - and the setting by its label: no place of numbers, no name of the register',
	( $worded['params'][1] ?? '' ) === 'Button in Spalte 2' && ( $worded['params'][2] ?? 'href' ) !== 'href' && preg_match( '/\d\.\d|href/', $spoken( $worded ) ) === 0
	&& ( $valued['params'][1] ?? '' ) === 'Sektion' && ( $valued['params'][2] ?? '' ) !== 'ground' );

ninoUser( $site, 'builder-only@example.com', [ '/_admin/builder/manage', '/_admin/builder/design/*' ] );
[ $foreign ] = variantsCall( $site, 'themelab/save', [ 'key' => $key, 'tree' => $draft['tree'], 'texts' => $draft['texts'], 'base' => $draft['stamp'], 'variants' => [ $standard, $right ] ] );
ninoUser( $site, 'blocks@example.com', [ \Nino\Modules\Themelab::PERM ] );

check( 'the variants come in by the action of the block and by its right alone: an account of the Builder without the right of Themelab is refused, 403, and nothing is written', $foreign === 403 && $folder( $key ) === $before );

$apart = \Nino\Blocks::get( $site, 'hero-full' );
[ $theme ] = variantsCall( $site, 'themelab/save', [ 'key' => 'hero-full', 'tree' => $draft['tree'], 'texts' => $draft['texts'], 'base' => 'x', 'variants' => [ $standard, $right ] ] );

check( 'a block of the kernel or of a theme takes no variants from a request: it is not the project\'s, and the save is refused', $theme === 404 && $apart !== null );

echo "\nD. From the block to the page\n";

[ , $draft ] = variantsCall( $site, 'themelab/open', [ 'key' => $key ] );
[ $final ] = $save( [ 'variants' => [ $standard, $right, $tinted ] ] );
$registry = \Nino\Components::components( $site );
$entry = array_values( array_filter( Library::all( $site )['blocks'], static fn( array $one ): bool => $one['key'] === $key ) )[0] ?? [];
$node = Library::block( $site, $key )['tree'][0];
$origin = Library::origin( $site, $node, $registry );

check( 'the block that was saved is offered with its variants: the dialog counts them, and a block that is put in is known by its block and its variant - the standard, as it stands',
	$final === 200 && ( $entry['variants'] ?? 0 ) === 3 && $origin !== null && $origin['key'] === $key && array_column( $origin['items'], 0 ) === [ 'image-left', 'image-right', 'right-tinted' ] && $origin['current'] === 'image-left' );

// A page that has the block as the dialog puts it in: its template, its root laid out as the kernel writes a template
$laid = '';

foreach( Tree::read( $site, (string) file_get_contents( $sandbox. '/private/blocks/'. $key. '/block.tpl' ) ) as $one )
	if( isset( $one['n'] ) === true )
		$laid = rtrim( \Nino\Markup::write( \Nino\Markup::layout( [ $one ], 0 ), \Nino\Components::containers( $site ) ) );

\Nino\Filesystem::putFileContent( $site, '/templates/page-mine.tpl', "<!-- nino:template-name Mine -->\n[template /templates/html-header]\n". $laid. "\n[template /templates/html-footer]\n" );
$site['/nino/html/images']['/template/page-mine/'. Library::block( $site, $key )['id']. '/picture'] = [ 'label' => 'Picture', 'width' => 800, 'height' => 600, 'filename' => null ];
$site['/nino/http/routes']['GET://mine'] = [ 'uri' => '/mine', 'body' => '[template /templates/page-mine]' ];
ninoUser( $site, 'designer@example.com', [ '/_admin/builder/manage', '/_admin/builder/design/*' ] );
[ , $page ] = variantsCall( $site, 'builder/open', [ 'template' => 'page-mine' ] );
$at = (string) array_key_first( array_filter( $page['tree'], static fn( array $one ): bool => ( $one['n'] ?? '' ) === 'section-wrap' ) );
$set = [];

foreach( [ 'image-right', 'right-tinted', 'image-left' ] as $name ) {

	[ $status, $answer ] = variantsCall( $site, 'builder/variant', [ 'template' => 'page-mine', 'tree' => $page['tree'], 'at' => $at, 'name' => $name ] );
	$set[$name] = $status === 200 && ( $answer['variant'] ?? '' ) === $name;
}

check( 'end to end: a block with variants that was saved in the editor is put into a page, and the Builder sets each of its variants - the set of the editor is the look of the page, and the variant is told again from the page',
	array_filter( $set, static fn( bool $ok ): bool => $ok === false ) === [] && array_keys( $set ) === [ 'image-right', 'right-tinted', 'image-left' ] );

echo "\nE. Blocks of one form: the block that is put in is the block whose variants are offered\n";

ninoUser( $site, 'blocks@example.com', [ \Nino\Modules\Themelab::PERM ] );
$kernelKey = 'split-image-text';
$kernelNode = Library::block( $site, $kernelKey )['tree'][0];
$ownNode = Library::block( $site, $key )['tree'][0];
$rank = static function( string $block, int $rank ) use ( &$site, $save, $key, $folder ): int {

	[ , $open ] = variantsCall( $site, 'themelab/open', [ 'key' => $block ] );
	[ $status ] = variantsCall( $site, 'themelab/save', [ 'key' => $block, 'tree' => $open['tree'], 'texts' => $open['texts'], 'base' => $open['stamp'], 'details' => [ 'rank' => $rank ] ] );

	return $status;
};
$ids = static fn( ?array $origin ): array => $origin === null ? [] : [ $origin['key'], array_column( $origin['items'], 0 ) ];

// a block that is made as a copy is its own block: its root has an id of its own, made from its key, and a block put in has that id
check( 'a block that is made as a copy has an id of its own at the root - made from its key, not the id of its template - so that two blocks of one form are told apart',
	$ownNode['a']['id'] === $key && $kernelNode['a']['id'] !== $key );

$orders = [];

foreach( [ 1, 999 ] as $place ) {

	$rank( $key, $place );
	$own = Library::origin( $site, $ownNode, $registry );
	$base = Library::origin( $site, $kernelNode, $registry );
	$orders[$place] = $own !== null && $own['key'] === $key && count( $own['items'] ) === 3 && $base !== null && $base['key'] === $kernelKey && count( $base['items'] ) === 2;
}

check( 'the block that is put in is the block whose variants are offered, whichever of two blocks of one form comes first: the copy with a variant of its own and the block it is a copy of, the rank of the copy first and last',
	$orders === [ 1 => true, 999 => true ] );

// a copy that kept the id of its template - made before the root had an id of its own - is told from the template by the block that was put in this session, else by its variant
[ , $second ] = variantsCall( $site, 'themelab/create', [ 'name' => [ 'de_DE' => 'Alter Klon', 'en_US' => 'Old clone' ], 'category' => 'content', 'from' => $kernelKey ] );
$old = (string) ( $second['key'] ?? '' );
[ , $open ] = variantsCall( $site, 'themelab/open', [ 'key' => $old ] );
variantsCall( $site, 'themelab/save', [ 'key' => $old, 'tree' => $open['tree'], 'texts' => $open['texts'], 'base' => $open['stamp'], 'variants' => [ $standard, $right, [ 'name' => 'quiet', 'label' => $label( 'Leise', 'Quiet' ), 'set' => [ 'root' => [ 'ground' => 'tint' ] ] ] ] ] );
$tpl = $sandbox. '/private/blocks/'. $old. '/block.tpl';
file_put_contents( $tpl, str_replace( $old, 'split', (string) file_get_contents( $tpl ) ) );
\Nino\Blocks::forget( $site );

$cloneNode = Library::block( $site, $old )['tree'][0];
$twin = $cloneNode['a']['id'] === 'split';
$hinted = Library::origin( $site, $kernelNode, $registry, [ 'split' => $old ] );
$bare = Library::origin( $site, $kernelNode, $registry );
$counted = Library::origin( $site, $kernelNode, $registry, [ 'split' => 'nothing-like-it' ] );
$numbered = $kernelNode;
$numbered['a']['id'] = 'split-2';

check( 'two blocks of one form with the same id and different variants give no choice rather than the variants of the other block - and the one that was put in this very session is the one that is chosen; a key the request names that is none of them is none',
	$twin === true && $bare === null && $counted === null && $hinted !== null && $hinted['key'] === $old && Library::origin( $site, $numbered, $registry ) === null );

ninoUser( $site, 'designer@example.com', [ '/_admin/builder/manage', '/_admin/builder/design/*' ] );
[ , $mine ] = variantsCall( $site, 'builder/open', [ 'template' => 'page-mine' ] );
$mineAt = (string) array_key_first( array_filter( $mine['tree'], static fn( array $one ): bool => ( $one['n'] ?? '' ) === 'section-wrap' ) );
[ $refused ] = variantsCall( $site, 'builder/variant', [ 'template' => 'page-mine', 'tree' => $mine['tree'], 'at' => $mineAt, 'name' => 'quiet' ] );
[ $allowed ] = variantsCall( $site, 'builder/variant', [ 'template' => 'page-mine', 'tree' => $mine['tree'], 'at' => $mineAt, 'name' => 'right-tinted' ] );

check( 'the Builder sets a variant of the block the page has and no variant of another block: a name of a block of the same form that the page does not have is refused, 403',
	$refused === 403 && $allowed === 200 );

ninoDone( $site );
