<?php
declare(strict_types=1);

/**
 *	Nino features
 *	collections-smoke.php	Contract test for the feature Collections - a loop, a list, a table with its columns and a filter, as
 *												components of the register that draw over the elements of a type:
 *
 *													A. the folders: five schemas read and accepted, each as clean as validate() makes it, the places they offer,
 *													   the group, its standard part, the renderers, the manifest; where a loop stands decides what it holds
 *													B. the loop: three elements as cards in a row, as a list and as a table; the loop in a page, a flow and a
 *													   row; sort, limit, offset and query against [elements] - the same elements in the same order -; loops in
 *													   loops, where the inner element wins; a list with nothing in it and the text for it
 *													C. what every component draws: the classes of each variant and option, computed from the schema; the
 *													   table - heading, scope, alignment, caption, the box that scrolls -; the filter, what it marks and the
 *													   order of its buttons
 *													D. the contract of group, part, ladder and token, and the rule of mixing (T3) for the part
 *													E. what must never be read: a field, a text, a title and a value that spell a call, a fill, a quote, a null
 *													   byte, a megabyte; a query, a sort and a type of elements that are none; the lang of each field; the
 *													   fields of an element in the attributes of a button and the picture of an image field
 *													F. the script of the filter, run over the kernel's stand-in of a page, in collections-js-smoke.js
 *													G. words, activation, sizes, and the cost of a loop of a thousand elements - what the register costs it
 *
 *												Runs against an isolated sandbox directory. The kernel is the checkout's own; the feature is the one this
 *												file sits in, which the kernel reads from the directory it is told (NINO_FEATURES_DIR) and registers as it
 *												does for a project that has it switched on.
 *
 *	Usage: php features/Collections/tests/collections-smoke.php
 *				 NINO_ROOT=../nino php features/Collections/tests/collections-smoke.php
 */

$root = getenv( 'NINO_ROOT' ) ?: dirname( __DIR__, 3 );
defined( 'NINO_FEATURES_DIR' ) === true || define( 'NINO_FEATURES_DIR', dirname( __DIR__, 2 ) );
require $root. '/tests/harness.php';

$featureDir	= dirname( __DIR__ );
$sandboxes	= [];

// What the feature is made of, in the order of its manifest
const COLLECTIONS_COMPONENTS = [ 'collection', 'collection-list', 'collection-table', 'collection-col', 'collection-filter' ];

// A hostile text: a call, a template and a fill that mean something to the site, a tag that would run, a quote, a null byte
const COLLECTIONS_HOSTILE = "[template /templates/evil] [[/project/company/general/name]] [[title]] \"><script>alert(1)</script> '\" onload=\"x \0 [csrf] [/collection]";

/**
 *	A type of elements, written the way the kernel keeps it: its model, the fields every language shares and the ones of each language
 *
 *	@param		array 		&$appData			(reference) A project
 *	@param		string		$type					The name of the type, services
 *	@param		array			$model
 *	@param		array			$global				name => fields of the bucket '*'
 *	@param		array			$locales			locale => name => fields
 *
 *	@return 	void
 */
function collectionsType( array &$appData, string $type, array $model, array $global, array $locales ): void {

	$data = [ 'model' => $model, '*' => [ '*' => [] ] + $global ] + $locales;

	\Nino\Filesystem::putFileContent( $appData, '/elements/'. $type. '.php', $data );
}

/**
 *	A project with Basis and Collections switched on, a type of seven elements - three of them in a category of their own - a second
 *	type for loops in loops, and the words of the feature: as \Nino\init() registers it, the components are read from the folders
 *
 *	@param		string		$locale
 *	@param		array 		$extra				More of the elements of services, name => [ 'global' => fields, locale => fields ]
 *
 *	@return 	array											$appData
 */
function collectionsSite( string $locale = 'de_DE', array $extra = [] ): array {

	global $root, $featureDir, $sandboxes;

	$appData = ninoSandbox( 'collections' );
	$sandboxes[] = ninoSandboxDir( $appData );
	$appData['./nino/locales/current'] = $locale;

	// The root of the site is the checkout: the standard parts of Basis are files of the kernel below it
	$appData['./nino/filesystem/path'] = $root;
	$appData['/nino/modules'] = [ '\\Nino\\Modules\\Basis', '\\Nino\\Modules\\Collections', '\\Nino\\Modules\\Elements' ];

	$services = [
		's1' => [ 'Holz', 'Bau', 30, '2026-03-01', true ],
		's2' => [ 'Stein', 'Bau', 10, '2026-01-01', true ],
		's3' => [ 'Glas', 'Fenster', 20, '2026-02-01', false ],
		's4' => [ 'Ziegel', 'Bau', 20, '2026-04-01', true ],
		's5' => [ 'Farbe', 'Maler', 5, '2026-05-01', false ],
		's6' => [ 'Stahl', 'Metall', 50, '2026-06-01', true ],
		's7' => [ 'Zink', 'Metall', 20, '2026-07-01', false ],
	];

	$global = [];
	$german = [];
	$english = [];

	foreach( $services as $name => [ $title, $category, $price, $date, $featured ] ) {

		$global[$name] = [ 'category' => $category, 'price' => $price, 'date' => $date, 'featured' => $featured ];
		$german[$name] = [ 'title' => $title, 'summary' => 'Text von '. $title ];
		$english[$name] = [ 'title' => $title. ' (en)', 'summary' => 'Text of '. $title ];
	}

	// An element that exists in German only: the page in English shows it all the same, as [elements] does
	$global['s8'] = [ 'category' => 'Bau', 'price' => 15, 'date' => '2026-08-01', 'featured' => false ];
	$german['s8'] = [ 'title' => 'Nur deutsch', 'summary' => 'Kein englischer Text' ];

	foreach( $extra as $name => $fields ) {

		$global[$name] = $fields['global'] ?? [];
		$german[$name] = $fields['de_DE'] ?? [];
		$english[$name] = $fields['en_US'] ?? [];
	}

	collectionsType( $appData, 'services', [
		'title'			=> [ 'type' => 'string', 'locale' => true ],
		'summary'		=> [ 'type' => 'string', 'locale' => true ],
		'category'	=> [ 'type' => 'string' ],
		'price'			=> [ 'type' => 'double' ],
		'date'			=> [ 'type' => 'string' ],
		'featured'	=> [ 'type' => 'boolean' ],
	], $global, [ 'de_DE' => $german, 'en_US' => $english ] );

	collectionsType( $appData, 'tags', [ 'title' => [ 'type' => 'string', 'locale' => true ] ], [ 't1' => [], 't2' => [] ], [
		'de_DE' => [ 't1' => [ 'title' => 'Neu' ], 't2' => [ 'title' => 'Beliebt' ] ],
		'en_US' => [ 't1' => [ 'title' => 'New' ], 't2' => [ 'title' => 'Popular' ] ],
	] );

	// What the site says: the words of the feature, as an activation puts them into the Text panel, and two of the page
	foreach( [ 'de_DE', 'en_US' ] as $language ) {

		$fills = [ '/template/page-x/hero/price' => $language === 'de_DE' ? 'Preis' : 'Price', '/template/page-x/hero/name' => $language === 'de_DE' ? 'Name' : 'Name', '/template/page-x/hero/all' => $language === 'de_DE' ? 'Alles zeigen' : 'Show everything' ];

		foreach( (array) include $featureDir. '/install/text/'. $language. '.php' as $key => $text )
			$fills[trim( (string) $key, '[]' )] = $text;

		\Nino\Html::addFills( $appData, $fills, $language );
	}

	\Nino\Html::addFills( $appData, [ '/project/company/general/name' => 'COMPANYNAME' ], '*' );
	\Nino\Filesystem::putFileContent( $appData, '/templates/evil.tpl', 'TEMPLATE-WAS-READ' );

	// As \Nino\init() does it: the components first, then the standard parts, then the modules
	\Nino\Html::init( $appData );
	\Nino\Features::registerComponents( $appData );
	\Nino\Features::registerParts( $appData );
	\Nino\Features::registerAssets( $appData );
	\Nino\Modules\Elements::init( $appData );

	return $appData;
}

/**
 *	A source drawn the way a page is, in the template page-x
 *
 *	@param		array 		&$appData			(reference) A project of collectionsSite()
 *	@param		string		$source				html+
 *	@param		array			$context			More of the context the nodes are drawn in
 *
 *	@return 	string
 */
function collectionsDraw( array &$appData, string $source, array $context = [] ): string {

	$notes = [];

	return \Nino\Components::render( $appData, \Nino\Markup::parse( $source, \Nino\Components::containers( $appData ), $notes ), $context + [ 'template' => 'page-x' ] );
}

/**
 *	The names of the elements a drawing shows, in the order it shows them: the text of what draws .slug
 *
 *	@param		string		$html
 *
 *	@return 	array
 */
function collectionsSlugs( string $html ): array {

	preg_match_all( '/(?:<i>|class="text">)([^<]*)</', $html, $found );

	return $found[1];
}

/**
 *	The elements [elements] and a collection draw for the same arguments, as the names they have
 *
 *	@param		array 		&$appData			(reference) A project of collectionsSite()
 *	@param		string		$arguments		What stands after the type: sort="title" limit="2"
 *
 *	@return 	array											[ [ the slugs [elements] drew ], [ the slugs the collection drew ] ]
 */
function collectionsBoth( array &$appData, string $arguments ): array {

	return [
		collectionsSlugs( \Nino\Html::renderHtml( $appData, '[elements /services '. $arguments. ']<i>[[.slug]]</i>[/elements]' ) ),
		collectionsSlugs( collectionsDraw( $appData, '[collection /services '. $arguments. '][text .slug][/collection]' ) ),
	];
}

/**
 *	The classes a call writes, worked out of the schema: the name, then for each choice that is a class and is not its default
 *	name--attribute-value, for each bool that is on name--attribute, and the variant as name--variant-word
 *
 *	@param		string		$name
 *	@param		array			$schema
 *	@param		array			$given				attribute => the word written, for a bool true; 'variant' => the variant
 *
 *	@return 	string
 */
function collectionsClasses( string $name, array $schema, array $given ): string {

	$class = $name;

	foreach( $schema['attributes'] as $attribute => $declared ) {

		if( $declared['class'] !== true || isset( $given[$attribute] ) === false )
			continue;

		if( $declared['type'] === 'bool' )
			$class .= ' '. $name. '--'. $attribute;
		else if( $given[$attribute] !== $declared['default'] )
			$class .= ' '. $name. '--'. $attribute. '-'. $given[$attribute];
	}

	if( isset( $given['variant'] ) === true && $given['variant'] !== 'regular' && in_array( $given['variant'], $schema['variants'], true ) === true )
		$class .= ' '. $name. '--variant-'. $given['variant'];

	return $class;
}

/**	The classes of the first element a drawing writes	*/
function collectionsFirstClass( string $html ): string {
	return preg_match( '/class="([^"]*)"/', $html, $found ) === 1 ? $found[1] : '';
}

/**	The text of a tag's own attribute in a drawing, '' where it is not there	*/
function collectionsAttribute( string $html, string $name ): string {
	return preg_match( '/\s'. preg_quote( $name, '/' ). '="([^"]*)"/', $html, $found ) === 1 ? $found[1] : '';
}


/**
 *	The order of the buttons a drawing says, as the script reads it: the list in data-order of the first box
 *
 *	@param		string		$html
 *
 *	@return 	array											The values; [] where the box says none
 */
function collectionsOrder( string $html ): array {
	return preg_match( '/data-order="([^"]*)"/', $html, $found ) === 1 ? (array) json_decode( html_entity_decode( $found[1], ENT_QUOTES | ENT_HTML5, 'UTF-8' ), true ) : [];
}

/**
 *	What a browser makes of a drawing: the names of the elements and of the attributes it would find in it, so that a quote that
 *	stays in an attribute value is no attribute and a tag that is escaped is no element
 *
 *	@param		string		$html
 *
 *	@return 	array											[ [ element names ], [ attribute names ] ]
 */
function collectionsDom( string $html ): array {

	$document = new \DOMDocument();
	$used = libxml_use_internal_errors( true );
	$document->loadHTML( '<?xml encoding="utf-8"?><body>'. $html. '</body>', LIBXML_NOERROR | LIBXML_NOWARNING | LIBXML_PARSEHUGE );
	libxml_clear_errors();
	libxml_use_internal_errors( $used );

	$elements = [];
	$attributes = [];

	foreach( $document->getElementsByTagName( '*' ) as $element ) {

		$elements[$element->nodeName] = true;

		foreach( $element->attributes ?? [] as $attribute )
			$attributes[$attribute->nodeName] = true;
	}

	return [ array_keys( $elements ), array_keys( $attributes ) ];
}

// --- A. The folders -----------------------------------------------------------------------------------

echo "A. The folders of the feature: schemas, group, part, manifest\n";

$read = \Nino\Features::readComponents( $featureDir, 'collections' );
check( 'the folders are read and nothing is refused: no component, no group and no standard part', is_array( $read ) === true && $read['rejected'] === [] && array_keys( $read['groups'] ) === [ 'collection' ] && $read['groups']['collection']['part'] === true );

$have = array_keys( $read['components'] );
sort( $have );
$names = COLLECTIONS_COMPONENTS;
sort( $names );
check( 'five components in one group, collection, and every one names it', $have === $names && array_unique( array_column( $read['components'], 'group' ) ) === [ 'collection' ] );

$unstable = [];
foreach( $read['components'] as $name => $clean )
	if( \Nino\Components::validate( $name, $clean ) !== $clean )
		$unstable[] = $name;
check( 'validate() accepts every clean schema as it is, unchanged'. ( $unstable === [] ? '' : ' - '. implode( ', ', $unstable ) ), $unstable === [] );

$appData = collectionsSite();
$schemas = \Nino\Components::components( $appData );
check( 'switched on, the register holds the five - and the twenty of Basis beside them -, and the group', count( $schemas ) === 25 && array_diff( COLLECTIONS_COMPONENTS, array_keys( $schemas ) ) === []
	&& array_diff( array_keys( \Nino\Components::groups( $appData ) ), [ 'button', 'footer', 'header', 'image', 'section', 'text', 'collection' ] ) === [] && count( \Nino\Components::groups( $appData ) ) === 7 );

$allowed = static function( string $parent ) use ( &$appData ): array {

	$list = \Nino\Components::allowed( $appData, $parent );
	sort( $list );

	return $list;
};
check( 'allowed(): the table holds its columns and nothing else, a column holds nothing, the filter holds the three loops', $allowed( 'collection-table' ) === [ 'collection-col' ] && $allowed( 'collection-col' ) === []
	&& $allowed( 'collection-filter' ) === [ 'collection', 'collection-list', 'collection-table' ] );

// Where a loop may stand, and what it offers its children: where it stands decides what they are - columns in a row, a flow in a column, sections in a page
check( 'collection stands in the page, a flow, a row and a filter; the list and the table in a flow and a filter; the filter in the page, a flow and a row',
	$schemas['collection']['in'] === [ 'page', 'flow', 'section-row', 'collection-filter' ] && $schemas['collection-list']['in'] === [ 'flow', 'collection-filter' ]
	&& $schemas['collection-table']['in'] === [ 'flow', 'collection-filter' ] && $schemas['collection-filter']['in'] === [ 'page', 'flow', 'section-row' ]
	&& $schemas['collection-col']['in'] === [ 'collection-table' ] );
check( 'asked with no place to stand in, a collection offers what its places say: columns, the components of a flow and sections', in_array( 'section-col', \Nino\Components::allowed( $appData, 'collection' ), true ) === true
	&& in_array( 'text', \Nino\Components::allowed( $appData, 'collection' ), true ) === true && in_array( 'section-wrap', \Nino\Components::allowed( $appData, 'collection' ), true ) === true );

// Where a loop stands decides what it may hold: it draws no element of its own, so what is in it is what the place holds
$through = static function( string $parent, string $container ) use ( &$appData ): array {

	$list = \Nino\Components::allowed( $appData, $parent, $container );
	sort( $list );

	return $list;
};
check( 'only the loop that draws no element round what it holds is through: collection says so, and the list, the table, the column and the filter - each draws a box, a ul, a table, a cell - do not',
	( $schemas['collection']['through'] ?? false ) === true && array_filter( [ 'collection-list', 'collection-table', 'collection-col', 'collection-filter' ], static fn( string $name ): bool => ( $schemas[$name]['through'] ?? false ) !== false ) === [] );
check( 'a collection in a row holds what a row holds, in a flow what a flow holds and in the page what the page holds - and not what its places say together',
	$through( 'collection', 'section-row' ) === $allowed( 'section-row' ) && $through( 'collection', 'flow' ) === $allowed( 'flow' ) && $through( 'collection', 'page' ) === $allowed( 'page' ) && $through( 'collection', '' ) === $allowed( 'collection' )
	&& in_array( 'section-col', $through( 'collection', 'section-row' ), true ) === true && in_array( 'text', $through( 'collection', 'section-row' ), true ) === false && in_array( 'section-wrap', $through( 'collection', 'section-row' ), true ) === false
	&& in_array( 'text', $through( 'collection', 'flow' ), true ) === true && in_array( 'section-col', $through( 'collection', 'flow' ), true ) === false
	&& in_array( 'section-wrap', $through( 'collection', 'page' ), true ) === true && in_array( 'text', $through( 'collection', 'page' ), true ) === false );
check( 'the list, the table and the filter take no notice of where they stand: what they hold is their own', $through( 'collection-list', 'section-row' ) === $allowed( 'collection-list' ) && $through( 'collection-table', 'flow' ) === [ 'collection-col' ]
	&& $through( 'collection-filter', 'section-row' ) === $allowed( 'collection-filter' ) && $through( 'collection-col', 'flow' ) === [] );

$containers = array_values( array_intersect( array_keys( array_filter( \Nino\Components::containers( $appData ) ) ), COLLECTIONS_COMPONENTS ) );
sort( $containers );
check( 'the containers of the feature are the four loops, and the column is a leaf', $containers === [ 'collection', 'collection-filter', 'collection-list', 'collection-table' ] );

$renderers = array_keys( (array) $appData[\Nino\Components::RENDERERS] );
check( 'every one of the five has a renderer, and each of them is a method of the class', array_values( array_intersect( $renderers, COLLECTIONS_COMPONENTS ) ) !== [] && count( array_intersect( $renderers, COLLECTIONS_COMPONENTS ) ) === 5
	&& array_filter( COLLECTIONS_COMPONENTS, static fn( string $name ): bool => is_callable( [ '\\Nino\\Modules\\Collections', 'component'. str_replace( ' ', '', ucwords( str_replace( '-', ' ', $name ) ) ) ] ) === false ) === [] );
check( 'a loop has its children as nodes: the register draws nothing of them', array_values( array_filter( COLLECTIONS_COMPONENTS, static fn( string $name ): bool => ( $schemas[$name]['children'] ?? '' ) === 'nodes' ) ) === [ 'collection', 'collection-list', 'collection-table', 'collection-filter' ] && ( $schemas['collection-col']['children'] ?? '' ) === '' );

$manifestWarnings = ninoWarnings();
$manifest = \Nino\Features::manifest( $featureDir );
check( 'the manifest validates with no warning: key collections, no requirement, the kernel with the register, the script of the filter as its one asset',
	is_array( $manifest ) === true && ninoWarnings() === [] && $manifestWarnings === [] && $manifest['key'] === 'collections' && $manifest['requires'] === [] && $manifest['nino'] === '>=1.13'
	&& $manifest['assets'] === [ '/features/Collections/assets/collection-filter.js' ] && $manifest['settings'] === [] && \Nino\Features::satisfies( $manifest['nino'] ) === true );
check( 'the manifest names no component and no stack: those are the folders\'', is_array( $manifest ) === true && ( $manifest['components'] ?? [] ) === [] && ( $manifest['stacks'] ?? [] ) === [] );
$all = \Nino\Features::all( $appData );
check( 'the feature is listed, off by default, with its class the module of its directory and no problem', isset( $all['collections'] ) === true && $all['collections']['module'] === '\\Nino\\Modules\\Collections'
	&& $all['collections']['problems'] === [] && $all['collections']['shipped'] === false && is_dir( $featureDir. '/components' ) === true );

// --- B. The loop -----------------------------------------------------------------------------------------

echo "\nB. The loop: cards, list, table, and what [elements] draws\n";

$ignored = ninoWarnings();

$cards = '[section-wrap][section-row][collection /services sort="title" limit="3"]'
	. '[section-col variant="card" size="100 m:33"][section-title .title tag="h3"][text .summary][/section-col][/collection][/section-row][/section-wrap]';
$html = collectionsDraw( $appData, $cards );
preg_match_all( '/<div class="(section-col[^"]*)"><h3 class="section-title">([^<]*)<\/h3><div class="text">([^<]*)<\/div><\/div>/', $html, $found );
check( 'three elements as cards in one row: three section-col with variant card, each with the title and the summary of its element, in the order of the sort',
	count( $found[0] ) === 3 && $found[2] === [ 'Farbe', 'Glas', 'Holz' ] && $found[3] === [ 'Text von Farbe', 'Text von Glas', 'Text von Holz' ]
	&& $found[1] === array_fill( 0, 3, 'section-col section-col--m-size-33 section-col--variant-card' ) );
check( 'the cards stand directly in the row - nothing of the collection is drawn around them', str_contains( $html, '<div class="section-row"><div class="section-col' ) === true && substr_count( $html, 'collection' ) === 0 );

[ $viaElements, $viaCollection ] = collectionsBoth( $appData, '' );
check( 'the same elements in the same order as [elements] with no argument, the one that exists in German only among them', $viaElements === $viaCollection && count( $viaCollection ) === 8 && in_array( 's8', $viaCollection, true ) === true );

$arguments = [ 'sort="title"', 'sort="-price"', 'sort="price,-date"', 'sort="category,title" limit="4"', 'sort="date" limit="3" offset="2"', 'sort="-date" offset="5"', 'query="category=Bau" sort="price,title"', 'query="category=Metall&featured=1"',
	'query="featured=1" sort="-price" limit="2" offset="1"', 'query="category=%au"', 'query="title=%e%" sort="title"', 'query="category=Gibtsnicht"', 'limit="2"', 'offset="20"' ];
$same = [];
foreach( $arguments as $one ) {

	[ $viaElements, $viaCollection ] = collectionsBoth( $appData, $one );

	if( $viaElements !== $viaCollection )
		$same[] = $one. ' [elements]: '. implode( ',', $viaElements ). ' collection: '. implode( ',', $viaCollection );
}
check( 'sort, limit, offset and query give the elements [elements] gives, in the order it gives them - '. count( $arguments ). ' calls'. ( $same === [] ? '' : ': '. implode( ' | ', $same ) ), $same === [] );
check( 'and they are not all the same list: a sort, a limit, an offset and a query each change it', count( array_unique( array_map( static fn( string $one ): string => implode( ',', collectionsBoth( $appData, $one )[1] ), $arguments ) ) ) >= 12 );

$appData = collectionsSite( 'en_US' );
$arguments = [ 'sort="title"', 'sort="-title" limit="3"', 'query="category=Bau" sort="title"' ];
$same = [];
foreach( $arguments as $one )
	if( collectionsBoth( $appData, $one )[0] !== collectionsBoth( $appData, $one )[1] )
		$same[] = $one;
check( 'in English too, where a title is taken from German for the one element that has none, the sort follows what is drawn as [elements] has it', $same === [] && collectionsBoth( $appData, 'sort="title"' )[1] !== [] );
check( 'a field of the element in the language of the page: the English title, and the German one where there is no other', str_contains( collectionsDraw( $appData, '[collection /services query="category=Metall" sort="title"][text .title][/collection]' ), '>Stahl (en)<' ) === true
	&& str_contains( collectionsDraw( $appData, '[collection /services query="category=Bau" sort="title"][text .title][/collection]' ), '>Nur deutsch<' ) === true );
$appData = collectionsSite();

// where a loop stands: the page, a flow and a row
$page = collectionsDraw( $appData, '[collection /services limit="2" sort="title"][section-wrap][section-row][section-col][section-title .title][/section-col][/section-row][/section-wrap][/collection]' );
check( 'in the page it draws a whole section for each element', substr_count( $page, '<section class="section-wrap">' ) === 2 && $page === collectionsDraw( $appData, '[collection /services limit="2" sort="title"][section-wrap][section-row][section-col][section-title .title][/section-col][/section-row][/section-wrap][/collection]' )
	&& preg_match_all( '/<h2 class="section-title">([^<]*)</', $page, $found ) === 2 && $found[1] === [ 'Farbe', 'Glas' ] );
$flow = collectionsDraw( $appData, '[section-wrap][section-row][section-col][collection /services limit="2" sort="title"][text .title][button .title href="/x"][/collection][/section-col][/section-row][/section-wrap]' );
check( 'in a flow - in a column - it draws the components of a flow, one after the other, with nothing round them', substr_count( $flow, '<div class="text">' ) === 2 && substr_count( $flow, 'class="button"' ) === 2
	&& str_contains( $flow, '<div class="section-col"><div class="text">Farbe</div><a class="button" href="/x">Farbe</a><div class="text">Glas</div>' ) === true );

// the same elements, drawn as a list and as a table
$list = collectionsDraw( $appData, '[collection-list /services sort="title" limit="3"][text .title][/collection-list]' );
check( 'three elements as a list: a ul, one li for each, the children of the call in it, in the order of the sort', $list === '<ul class="collection-list"><li class="collection-list-row"><div class="text">Farbe</div></li><li class="collection-list-row"><div class="text">Glas</div></li><li class="collection-list-row"><div class="text">Holz</div></li></ul>' );
$table = collectionsDraw( $appData, '[collection-table /services sort="title" limit="3" caption="Dienste"][collection-col .title title="Name" rowhead][collection-col .price title="Preis" align="end"][/collection-table]' );
check( 'three elements as a table: a caption, one heading row, a row for each, the first cell of each a row heading and the price cell at the end', $table
	=== '<div class="collection-table-wrap" role="region" aria-labelledby="collection-table-1" tabindex="0"><table class="collection-table"><caption id="collection-table-1">Dienste</caption>'
	. '<thead><tr><th class="collection-col" scope="col">Name</th><th class="collection-col collection-col--align-end" scope="col">Preis</th></tr></thead><tbody>'
	. '<tr class="collection-table-row"><th class="collection-col" scope="row">Farbe</th><td class="collection-col collection-col--align-end">5</td></tr>'
	. '<tr class="collection-table-row"><th class="collection-col" scope="row">Glas</th><td class="collection-col collection-col--align-end">20</td></tr>'
	. '<tr class="collection-table-row"><th class="collection-col" scope="row">Holz</th><td class="collection-col collection-col--align-end">30</td></tr></tbody></table></div>' );
foreach( [ 'collection-list', 'collection-table' ] as $loop ) {

	$body = $loop === 'collection-list' ? '[text .slug]' : '[collection-col .slug]';
	$same = [];

	foreach( [ 'sort="-price,title" limit="3"', 'query="category=Bau" sort="date"', 'sort="title" offset="3" limit="2"' ] as $one )
		if( preg_match_all( '/(?:class="text">|<td class="collection-col">)([^<]*)</', collectionsDraw( $appData, '['. $loop. ' /services '. $one. ']'. $body. '[/'. $loop. ']' ), $drawn ) === 0 || $drawn[1] !== collectionsBoth( $appData, $one )[0] )
			$same[] = $one;

	check( $loop. ': the same elements in the same order as [elements] for sort, limit, offset and query'. ( $same === [] ? '' : ' - '. implode( ' | ', $same ) ), $same === [] );
}

// loops in loops: the inner element is the one of the inner loop, and the outer one is back when the inner one is done
$nested = collectionsDraw( $appData, '[collection-list /services sort="title" limit="2"][text .title][collection-list /tags sort="title"][text .title][text .slug][/collection-list][text .slug][/collection-list]' );
preg_match_all( '/class="text">([^<]*)</', $nested, $found );
check( 'a loop in a loop: the inner element wins inside it - the title and the name of the tag - and the outer element is back after it', $found[1] === [ 'Farbe', 'Beliebt', 't2', 'Neu', 't1', 's5', 'Glas', 'Beliebt', 't2', 'Neu', 't1', 's3' ]
	&& substr_count( $nested, '<ul class="collection-list">' ) === 3 );
$nested = collectionsDraw( $appData, '[collection /tags sort="title"][text .title][collection /services limit="1" sort="title"][text .title][/collection][text .title][/collection]' );
preg_match_all( '/class="text">([^<]*)</', $nested, $found );
check( 'the same with the loops the other way round: the inner title wins, the outer one is the one drawn after it, and nothing is drawn around the loops', $found[1] === [ 'Beliebt', 'Farbe', 'Beliebt', 'Neu', 'Farbe', 'Neu' ] && str_contains( $nested, 'collection' ) === false );

// a loop with no element: nothing, or the text of the call
check( 'a list with no element draws nothing - no ul, no table, no cell - and the text of the call where it has one, as a paragraph, in a list and in a table too',
	collectionsDraw( $appData, '[collection /services query="category=Gibtsnicht"][text .title][/collection]' ) === ''
	&& collectionsDraw( $appData, '[collection-list /services query="category=Gibtsnicht"][text .title][/collection-list]' ) === ''
	&& collectionsDraw( $appData, '[collection-table /services query="category=Gibtsnicht"][collection-col .title][/collection-table]' ) === ''
	&& collectionsDraw( $appData, '[collection /services query="category=Gibtsnicht" empty="Nichts da"][text .title][/collection]' ) === '<p class="collection-empty">Nichts da</p>'
	&& collectionsDraw( $appData, '[collection-list /services query="category=Gibtsnicht" empty="Nichts da"][text .title][/collection-list]' ) === '<p class="collection-empty">Nichts da</p>'
	&& collectionsDraw( $appData, '[collection-table /services query="category=Gibtsnicht" empty="Nichts da"][collection-col .title][/collection-table]' ) === '<p class="collection-empty">Nichts da</p>'
	&& str_contains( collectionsDraw( $appData, '[collection /services query="category=Bau" empty="Nichts da"][text .title][/collection]' ), 'Nichts da' ) === false );
\Nino\Html::addFills( $appData, [ '/template/page-x/hero/none' => 'Keine Dienste' ], 'de_DE' );
check( 'the text of an empty list is a text like any other: a key, a ~name of the section and a plain text', collectionsDraw( $appData, '[collection /nothing empty="/template/page-x/hero/none"][text .title][/collection]' ) === '<p class="collection-empty">Keine Dienste</p>'
	&& collectionsDraw( $appData, '[collection /nothing empty="~none"][text .title][/collection]', [ 'scope' => 'hero' ] ) === '<p class="collection-empty">Keine Dienste</p>' );
$ignored = ninoWarnings();

// a collection that holds nothing, and one that has no type
check( 'a collection with nothing in it draws nothing, and so does one with no type', collectionsDraw( $appData, '[collection /services][/collection]' ) === '' && collectionsDraw( $appData, '[collection][text .title][/collection]' ) === '' );
check( 'a column outside a table is nothing', collectionsDraw( $appData, '[collection-col .title title="Name"]' ) === '' && collectionsDraw( $appData, '[collection-list /services][collection-col .title][/collection-list]' ) === '<ul class="collection-list">'. str_repeat( '<li class="collection-list-row"></li>', 8 ). '</ul>' );

// --- C. What every component draws ---------------------------------------------------------------------------

echo "\nC. What every component draws\n";

$wrong = [];
foreach( $schemas['collection-list']['variants'] as $variant ) {

	$drawn = collectionsFirstClass( collectionsDraw( $appData, '[collection-list /services limit="1" variant="'. $variant. '"][text .title][/collection-list]' ) );

	if( $drawn !== collectionsClasses( 'collection-list', $schemas['collection-list'], [ 'variant' => $variant ] ) )
		$wrong[] = $variant;
}
check( 'the list writes the class of each of its variants, worked out of the schema, and the regular one has none'. ( $wrong === [] ? '' : ' - '. implode( ', ', $wrong ) ), $wrong === [] && $schemas['collection-list']['variants'] === [ 'regular', 'check', 'numbered', 'plain' ]
	&& collectionsFirstClass( collectionsDraw( $appData, '[collection-list /services limit="1" variant="nonsense"][text .title][/collection-list]' ) ) === 'collection-list' );
check( 'a numbered list is an ol and every other one a ul', str_starts_with( collectionsDraw( $appData, '[collection-list /services limit="1" variant="numbered"][text .title][/collection-list]' ), '<ol class="collection-list collection-list--variant-numbered">' ) === true
	&& str_ends_with( collectionsDraw( $appData, '[collection-list /services limit="1" variant="numbered"][text .title][/collection-list]' ), '</ol>' ) === true
	&& str_starts_with( collectionsDraw( $appData, '[collection-list /services limit="1" variant="check"][text .title][/collection-list]' ), '<ul ' ) === true );

$wrong = [];
foreach( $schemas['collection-col']['attributes']['align']['options'] as $align ) {

	$drawn = collectionsDraw( $appData, '[collection-table /services limit="1"][collection-col .title align="'. $align. '"][/collection-table]' );

	if( preg_match( '/<td class="([^"]*)"/', $drawn, $found ) !== 1 || $found[1] !== collectionsClasses( 'collection-col', $schemas['collection-col'], [ 'align' => $align ] ) )
		$wrong[] = $align;
}
check( 'a column writes the class of each alignment, worked out of the schema, and the start has none'. ( $wrong === [] ? '' : ' - '. implode( ', ', $wrong ) ), $wrong === [] && $schemas['collection-col']['attributes']['align']['options'] === [ 'start', 'center', 'end' ] );

// the table
$notitles = collectionsDraw( $appData, '[collection-table /services limit="1"][collection-col .title][collection-col .price][/collection-table]' );
check( 'a table with no title in its columns has no heading row and no caption, and its box is a region a keyboard can reach, named by the word of the feature', str_contains( $notitles, '<thead' ) === false && str_contains( $notitles, '<caption' ) === false
	&& str_starts_with( $notitles, '<div class="collection-table-wrap" role="region" aria-label="Tabelle" tabindex="0"><table class="collection-table"><tbody>' ) === true );
$cells = collectionsDraw( $appData, '[collection-table /services limit="2" sort="title"][collection-col .title title="Name"][collection-col .nofield title="Leer"][collection-col .category title="Kategorie"][/collection-table]' );
check( 'a column of a field the type has none of is an empty cell, so that every row has as many cells as the table has columns', substr_count( $cells, '<tr class="collection-table-row">' ) === 2
	&& substr_count( $cells, '<td class="collection-col"></td>' ) === 2 && preg_match_all( '/<tr class="collection-table-row">((?:<t[dh][^>]*>[^<]*<\/t[dh]>){3})<\/tr>/', $cells ) === 2 );
$in = collectionsDraw( $appData, '[collection-table /services limit="1" sort="title"]text between[collection-col .id title="Nr"] [text .title][collection-col .slug title="Name"][collection-col .uri][/collection-table]' );
check( 'the columns are the calls of collection-col and nothing else: what stands between them is not drawn, and .id, .slug and .uri are cells too', str_contains( $in, 'between' ) === false && str_contains( $in, 'class="text"' ) === false
	&& str_contains( $in, '<td class="collection-col">0</td><td class="collection-col">s5</td><td class="collection-col">/services/s5</td>' ) === true );
$heads = collectionsDraw( $appData, '[collection-table /services limit="1"][collection-col .title title="~name"][collection-col .price title="/template/page-x/hero/price"][collection-col .date title="Datum"][/collection-table]', [ 'scope' => 'hero' ] );
check( 'the heading of a column is a ~name of the section, a key or a fixed text', str_contains( $heads, '<thead><tr><th class="collection-col" scope="col">Name</th><th class="collection-col" scope="col">Preis</th><th class="collection-col" scope="col">Datum</th></tr></thead>' ) === true );
$cells = collectionsDraw( $appData, '[collection-table /services limit="1" sort="title"][collection-col .featured][collection-col .price][collection-col .title][/collection-table]' );
check( 'a cell is the field as [elements] writes it by its type: a boolean, a number and a text', str_contains( $cells, '<td class="collection-col"></td><td class="collection-col">5</td><td class="collection-col">Farbe</td>' ) === true );

// the loop is a column of the cell too: a text with a token of the element
$tokens = collectionsDraw( $appData, '[collection-list /services limit="1" sort="title"][html]<b>[[title]]</b> [[category]] [[.id]][/html][/collection-list]' );
check( 'the tokens of the element - [[title]], [[category]], [[.id]] - are read by what the loop draws, as in [elements]', str_contains( $tokens, 'Farbe Maler 0' ) === true );

// the filter
$filtered = collectionsDraw( $appData, '[collection-filter by=".category"][collection-list /services sort="title"][text .title][/collection-list][/collection-filter]' );
preg_match_all( '/data-collection-value="([^"]*)"><div class="text">([^<]*)</', $filtered, $found );
check( 'a filter holds what it filters in a box with its words - the name of the buttons, what the status says, the name of the row of buttons - and marks each row with the value of the field, beside its title',
	str_starts_with( $filtered, '<div class="collection-filter" data-collection-filter data-all="Alle" data-shown="{shown} von {total} angezeigt" data-label="Filter" data-order="&#91;&quot;Bau&quot;,&quot;Fenster&quot;,&quot;Maler&quot;,&quot;Metall&quot;]"><ul class="collection-list">' ) === true && str_ends_with( $filtered, '</ul></div>' ) === true
	&& substr_count( $filtered, '<li class="collection-list-row" data-collection-value=' ) === 8
	&& array_combine( $found[2], $found[1] ) === [ 'Farbe' => 'Maler', 'Glas' => 'Fenster', 'Holz' => 'Bau', 'Nur deutsch' => 'Bau', 'Stahl' => 'Metall', 'Stein' => 'Bau', 'Ziegel' => 'Bau', 'Zink' => 'Metall' ] );
check( 'the box has an attribute the script finds it by and the words it needs, in the language of the page', collectionsAttribute( $filtered, 'data-all' ) === 'Alle' && collectionsAttribute( $filtered, 'data-label' ) === 'Filter'
	&& str_contains( collectionsDraw( collectionsSite( 'en_US' ), '[collection-filter by="category"][collection-list /services][text .slug][/collection-list][/collection-filter]' ), 'data-all="All" data-shown="Showing {shown} of {total}" data-label="Filter"' ) === true );
check( 'the label of the first button is the call\'s own where it says one', str_contains( collectionsDraw( $appData, '[collection-filter by=".category" all="Alles"][collection-list /services][text .slug][/collection-list][/collection-filter]' ), 'data-all="Alles"' ) === true );
check( 'the label of the first button is a text: a ~name of the section reads its key in the language of the page, and a key written out too',
	collectionsAttribute( collectionsDraw( $appData, '[collection-filter by=".category" all="~all"][collection-list /services][text .slug][/collection-list][/collection-filter]', [ 'scope' => 'hero' ] ), 'data-all' ) === 'Alles zeigen'
	&& collectionsAttribute( collectionsDraw( collectionsSite( 'en_US' ), '[collection-filter by=".category" all="~all"][collection-list /services][text .slug][/collection-list][/collection-filter]', [ 'scope' => 'hero' ] ), 'data-all' ) === 'Show everything'
	&& collectionsAttribute( collectionsDraw( $appData, '[collection-filter by=".category" all="/template/page-x/hero/all"][collection-list /services][text .slug][/collection-list][/collection-filter]' ), 'data-all' ) === 'Alles zeigen' );
check( 'a ~name that no section has is no label: the box is drawn bare, as for a filter with no label at all', str_contains( collectionsDraw( $appData, '[collection-filter by=".category" all="~none"][collection-list /services][text .slug][/collection-list][/collection-filter]', [ 'scope' => 'hero' ] ), 'data-all="~none"' ) === false );

$cardsFiltered = collectionsDraw( $appData, '[section-wrap][section-row][collection-filter by=".category"][collection /services sort="title" limit="2"][section-col variant="card"][section-title .title][/section-col][/collection][/collection-filter][/section-row][/section-wrap]' );
check( 'cards in a row are filtered too: each card is in a box that carries its value, and the box of the filter is the only other thing in the row',
	preg_match_all( '/<div class="collection-item" data-collection-value="([^"]*)"><div class="section-col section-col--variant-card">/', $cardsFiltered, $found ) === 2 && $found[1] === [ 'Maler', 'Fenster' ]
	&& str_starts_with( $cardsFiltered, '<section class="section-wrap"><div class="section-row"><div class="collection-filter" data-collection-filter ' ) === true );
check( 'a collection no filter asks for is drawn bare, and a table is filtered by its rows', str_contains( collectionsDraw( $appData, '[collection /services limit="1"][text .title][/collection]' ), 'collection-item' ) === false
	&& substr_count( collectionsDraw( $appData, '[collection-filter by=".category"][collection-table /services limit="3"][collection-col .title][/collection-table][/collection-filter]' ), '<tr class="collection-table-row" data-collection-value="' ) === 3 );

$nested = collectionsDraw( $appData, '[collection-filter by=".category"][collection-list /services limit="1" sort="title"][text .title][collection-list /tags][text .title][/collection-list][/collection-list][/collection-filter]' );
check( 'only the loops a filter holds directly are marked: a loop in one of their rows is the rows of its own type, and has no category', substr_count( $nested, 'data-collection-value=' ) === 1 );
$inner = collectionsDraw( $appData, '[collection-filter by=".category"][collection-filter by=".title"][collection-list /tags][text .slug][/collection-list][/collection-filter][/collection-filter]' );
check( 'filters in filters: the nearest one asks, the one above it asks nothing of the loop below the other', preg_match_all( '/data-collection-value="([^"]*)"/', $inner, $found ) === 2 && $found[1] === [ 'Neu', 'Beliebt' ] && substr_count( $inner, 'data-collection-filter' ) === 2 );

// the order of the buttons: the sort of the kernel in the language of the page, whatever order the rows stand in
$ordered = collectionsSite( 'de_DE', [
	'z1' => [ 'global' => [ 'category' => 'Zebra', 'price' => 12, 'date' => '2026-10-01', 'featured' => false ], 'de_DE' => [ 'title' => 'Aaa' ] ],
	'z2' => [ 'global' => [ 'category' => 'Ärzte', 'price' => 9, 'date' => '2026-10-02', 'featured' => false ], 'de_DE' => [ 'title' => 'Bbb' ] ],
] );
$drawn = collectionsDraw( $ordered, '[collection-filter by=".category"][collection-list /services sort="-title"][text .title][/collection-list][/collection-filter]' );
preg_match_all( '/data-collection-value="([^"]*)"/', $drawn, $found );
$standing = array_values( array_unique( array_map( static fn( string $value ): string => html_entity_decode( $value, ENT_QUOTES | ENT_HTML5, 'UTF-8' ), $found[1] ) ) );
$rows = array_map( static fn( string $value ): array => [ 'value' => $value ], $standing );
$kernel = array_column( \Nino\Elements::sortElements( $rows, 'value', 'de_DE' ), 'value' );
check( 'the buttons of a filter stand in the order the kernel sorts the values in, whatever order the rows stand in: what sortElements() answers for the language of the page', collectionsOrder( $drawn ) === $kernel && $standing !== $kernel && count( $kernel ) === 6 );

if( extension_loaded( 'intl' ) === true )
	check( 'with intl a letter with an umlaut stands with its letter, Ärzte before Bau and Zebra last', collectionsOrder( $drawn ) === [ 'Ärzte', 'Bau', 'Fenster', 'Maler', 'Metall', 'Zebra' ] );
else {

	echo "  --  - intl is not available here: the order of Ärzte before Zebra was NOT checked\n";
	check( 'without intl the values are sorted by their bytes, Ärzte after Zebra', collectionsOrder( $drawn ) === [ 'Bau', 'Fenster', 'Maler', 'Metall', 'Zebra', 'Ärzte' ] );
}

check( 'two numbers are compared as numbers: 9 before 10, whatever the rows say', collectionsOrder( collectionsDraw( $ordered, '[collection-filter by=".price"][collection-list /services sort="-title"][text .title][/collection-list][/collection-filter]' ) ) === [ '5', '9', '10', '12', '15', '20', '30', '50' ] );
check( 'a filter that holds a table and a collection of cards says one order for both', collectionsOrder( collectionsDraw( $ordered, '[section-wrap][section-row][collection-filter by=".category"][collection /services sort="-title" limit="3"][section-col][text .title][/section-col][/collection][collection-table /services sort="title"][collection-col .title][/collection-table][/collection-filter][/section-row][/section-wrap]' ) ) === $kernel );
$inner = collectionsDraw( $ordered, '[collection-filter by=".category"][collection-list /services sort="title"][text .slug][/collection-list][collection-filter by=".price"][collection-list /services sort="-title"][text .slug][/collection-list][/collection-filter][collection-list /tags][text .slug][/collection-list][/collection-filter]' );
preg_match_all( '/data-order="([^"]*)"/', $inner, $found );
$orders = array_map( static fn( string $order ): array => (array) json_decode( html_entity_decode( $order, ENT_QUOTES | ENT_HTML5, 'UTF-8' ), true ), $found[1] );
check( 'filters in filters each say their own order: the outer one the values of its own loops, the inner one those of its own - whichever of them is drawn first', count( $orders ) === 2 && $orders[0] === $kernel && $orders[1] === [ '5', '9', '10', '12', '15', '20', '30', '50' ] );

$ignored = ninoWarnings();
$noWords = collectionsSite();
unset( $noWords['./nino/html/fills']['de_DE']['[[/feature/collections/filter/shown]]'] );
$bare = collectionsDraw( $noWords, '[collection-filter by=".category"][collection-list /services limit="1" sort="title"][text .title][/collection-list][/collection-filter]' );
check( 'a filter with a word missing draws what it holds and no box: a button with no name is worse than none', str_contains( $bare, 'data-collection-filter' ) === false && str_contains( $bare, '<ul class="collection-list">' ) === true );
$none = [];
foreach( [ 'by=""', 'by="../x"', 'by="a b"', 'by=".1x"', 'by="ca\\"x"', 'by="[[title]]"', '' ] as $by )
	if( str_contains( collectionsDraw( $appData, '[collection-filter '. $by. '][collection-list /services limit="1"][text .title][/collection-list][/collection-filter]' ), 'data-collection-filter' ) === true )
		$none[] = $by;
check( 'a filter that names no field - none, a path, a space, a leading digit, a quote, a fill - is no filter, and its children are drawn'. ( $none === [] ? '' : ' - '. implode( ' | ', $none ) ), $none === []
	&& str_contains( collectionsDraw( $appData, '[collection-filter by="../x"][collection-list /services limit="1"][text .title][/collection-list][/collection-filter]' ), '<ul class="collection-list">' ) === true );
check( 'a filter with nothing in it is nothing, and a filter with a loop that has no element too', collectionsDraw( $appData, '[collection-filter by=".category"][/collection-filter]' ) === ''
	&& collectionsDraw( $appData, '[collection-filter by=".category"][collection-list /services query="category=Gibtsnicht"][text .title][/collection-list][/collection-filter]' ) === '' );

// --- D. group, part, ladder and token ----------------------------------------------------------------------

echo "\nD. The contract of group, part, ladder and token\n";

$ninoCss = (string) file_get_contents( $root. '/_nino/Nino.css' );
preg_match_all( '/^\s*(--(?:brand|page)-[a-z0-9-]+)\s*:/m', $ninoCss, $tokens );
$declaredTokens = array_unique( $tokens[1] );
$declared = $read['groups']['collection'];
$css = (string) file_get_contents( $featureDir. '/groups/collection/part/part.css' );
$problems = [];

if( $declared['part'] !== true || \Nino\Components::partProblems( $css, COLLECTIONS_COMPONENTS ) !== [] )
	$problems[] = 'the part breaks the rule of mixing: '. implode( '; ', \Nino\Components::partProblems( $css, COLLECTIONS_COMPONENTS ) );

if( is_array( $declared['label'] ) === false || isset( $declared['label']['de_DE'], $declared['label']['en_US'] ) === false )
	$problems[] = 'no name in both languages';

$per = [];
$plain = (string) preg_replace( '#/\*.*?\*/#s', '', $css );
$firstRoot = preg_match( '/^\s*:root\s*\{([^}]*)\}/', $plain, $found ) === 1 ? $found[1] : '';

if( str_starts_with( ltrim( $plain ), ':root' ) === false )
	$problems[] = 'the part does not begin with :root';

foreach( $declared['knobs'] as $key => $knob ) {

	[ $component, $knobName ] = explode( '.', $key );
	$per[$component] = ( $per[$component] ?? 0 ) + 1;

	if( in_array( $component, COLLECTIONS_COMPONENTS, true ) === false || $knob['token'] !== '--'. $component. '-'. $knobName || count( $knob['ladder'] ) !== 5 )
		$problems[] = $key. ': not a knob of a component of the group, not its token or not five steps';

	// the middle step is the default, and it stands in the part, in the first :root
	if( preg_match( '/(?:^|[\s;])'. preg_quote( $knob['token'], '/' ). ':\s*'. preg_quote( $knob['ladder'][2], '/' ). '\s*;/', $firstRoot ) !== 1 )
		$problems[] = $key. ': the middle step is not what the part sets';

	if( isset( $knob['label']['de_DE'], $knob['label']['en_US'] ) === false )
		$problems[] = $key. ': no label in both languages';

	foreach( $knob['ladder'] as $step )
		if( preg_match( '/var\(\s*(--[a-z0-9-]+)/', $step, $used ) === 1 && in_array( $used[1], $declaredTokens, true ) === false )
			$problems[] = $key. ': a step reads '. $used[1]. ', which Nino.css does not set';
}

foreach( $per as $component => $count )
	if( $count > 6 )
		$problems[] = $component. ': more than six knobs';

// what the part reads of the brand and of the page is set in Nino.css
preg_match_all( '/var\(\s*(--(?:brand|page)-[a-z0-9-]+)/', $css, $reads );

foreach( array_unique( $reads[1] ) as $token )
	if( in_array( $token, $declaredTokens, true ) === false )
		$problems[] = 'the part reads '. $token. ', which Nino.css does not set';

// every token the part sets is a token of its own components, and every knob has a token the part sets
preg_match_all( '/(?:^|[\s;{])(--[a-z0-9-]+)\s*:/m', $firstRoot, $sets );
$knobTokens = array_column( $declared['knobs'], 'token' );

if( array_diff( $sets[1], $knobTokens ) !== [] || array_diff( $knobTokens, $sets[1] ) !== [] )
	$problems[] = 'the tokens of :root and the knobs are not the same list';

check( 'the group: a name in both languages, knobs of its components with their token, five steps and a label in both, the middle step set in the part, at most six for a component, a part that keeps the rule of mixing and reads only what Nino.css sets'
	. ( $problems === [] ? '' : ' - '. implode( '; ', array_slice( $problems, 0, 4 ) ) ), $problems === [] && count( $declared['knobs'] ) === 4 );

// Mutations of the rule, in the suite: the check has to see a foreign class, a foreign token and an important - and a sixth step, a seventh knob
check( 'T3 sees a class that is no component of the group\'s, one of another feature\'s, a token that is none of the brand, the page or the group\'s - read or set -, an important, an @import and a url()',
	\Nino\Components::partProblems( $css. "\n.card { color: red; }", COLLECTIONS_COMPONENTS ) !== [] && \Nino\Components::partProblems( $css. "\n.section-col { color: red; }", COLLECTIONS_COMPONENTS ) !== []
	&& \Nino\Components::partProblems( $css. "\n.collection-list { color: var(--foreign-ink); }", COLLECTIONS_COMPONENTS ) !== [] && \Nino\Components::partProblems( $css. "\n.collection-list { --section-wrap-space: 1px; }", COLLECTIONS_COMPONENTS ) !== []
	&& \Nino\Components::partProblems( $css. "\n.collection-list { color: red !important; }", COLLECTIONS_COMPONENTS ) !== [] && \Nino\Components::partProblems( "@import 'x.css';\n". $css, COLLECTIONS_COMPONENTS ) !== []
	&& \Nino\Components::partProblems( $css. "\n.collection-list { background: url(x.png); }", COLLECTIONS_COMPONENTS ) !== [] && \Nino\Components::partProblems( $css. "\nbutton { color: red; }", COLLECTIONS_COMPONENTS ) !== [] );
$group = include $featureDir. '/groups/collection/group.php';
$six = $group;
$six['knobs']['collection-list.gap']['ladder'][] = '1rem';
$seven = $group;
for( $more = 0; $more < 7; $more++ )
	$seven['knobs']['collection-list.more'. $more] = [ 'token' => '--collection-list-more'. $more, 'label' => 'x', 'ladder' => [ '1rem', '2rem', '3rem', '4rem', '5rem' ] ];
check( 'a sixth step is refused, and so is a component with more than six knobs, and a knob of a component that is in no such group', is_string( \Nino\Components::validateGroup( 'collection', $six, COLLECTIONS_COMPONENTS ) ) === true
	&& is_string( \Nino\Components::validateGroup( 'collection', $seven, COLLECTIONS_COMPONENTS ) ) === true && is_string( \Nino\Components::validateGroup( 'collection', $group, [ 'collection' ] ) ) === true
	&& is_array( \Nino\Components::validateGroup( 'collection', $group, COLLECTIONS_COMPONENTS ) ) === true );

// the part in the bundle
$bundle = $appData['/nino/html/assets']['/.cache/style.css'] ?? [];
check( 'the standard part is in the style bundle, once, as a file of the feature that is there', count( array_keys( $bundle, '/features/Collections/groups/collection/part/part.css', true ) ) === 1
	&& is_file( \Nino\Filesystem::path( $appData, '/features/Collections/groups/collection/part/part.css' ) ) === true );
$scripts = $appData['/nino/html/assets']['/.cache/script.js'] ?? [];
check( 'and the one script of the feature is in the script bundle, once', count( array_keys( $scripts, '/features/Collections/assets/collection-filter.js', true ) ) === 1 );

$asked = new \ArrayObject();
$themed = ninoSandbox( 'collections-theme' );
$sandboxes[] = ninoSandboxDir( $themed );
$themed['/nino/modules'] = [ '\\Nino\\Modules\\Collections' ];
\Nino\Callbacks::registerCallback( $themed, \Nino\Components::PARTS, static function( array &$appData, array $answer ) use ( $asked ): array {
	$asked[] = $answer;
	return [ 'css' => '' ] + $answer;
} );
\Nino\Features::registerComponents( $themed );
\Nino\Features::registerParts( $themed );
check( 'a theme is asked for the part - the group, the feature and the file - and may take it away', $asked->getArrayCopy() === [ [ 'group' => 'collection', 'feature' => 'collections', 'css' => '/features/Collections/groups/collection/part/part.css' ] ]
	&& ( $themed['/nino/html/assets']['/.cache/style.css'] ?? [] ) === [] );

// the stylesheet's own rules: widths the catalogue answers to, and what a hidden row stays
check( 'the part asks for no width of the page but the print: no media query on a width, so that no threshold of the catalogue is missed', preg_match_all( '/@media\s*([^{]*)\{/', $css, $queries ) === 1 && $queries[1] === [ 'print ' ] );
check( 'a row the filter hides stays hidden: the box that is out of the layout and the hidden row both say none', preg_match( '/\.collection-item\[hidden\]\s*\{\s*display:\s*none;/', $css ) === 1 && preg_match( '/\.collection-item\s*\{\s*display:\s*contents;/', $css ) === 1
	&& preg_match( '/\.collection-list-row\s*\{[^}]*display\s*:/', $css ) === 0 && preg_match( '/\.collection-table-row\s*\{[^}]*display\s*:/', $css ) === 0 );
check( 'the table scrolls in its own box and a keyboard reaches it; the buttons have a ring for the keyboard and a size above 24 px', preg_match( '/\.collection-table-wrap\s*\{[^}]*overflow-x:\s*auto/', $css ) === 1
	&& preg_match( '/\.collection-table-wrap:focus-visible\s*\{[^}]*outline/', $css ) === 1 && preg_match( '/\.collection-filter-button:focus-visible\s*\{[^}]*outline/', $css ) === 1
	&& preg_match( '/\.collection-filter-button\s*\{[^}]*min-height:\s*2\.25rem/', $css ) === 1 );

// --- E. What must never be read ------------------------------------------------------------------------------

echo "\nE. What must never be read\n";

$ignored = ninoWarnings();
$big = str_repeat( 'Lorem ipsum [x] ', 65536 );
$hostile = collectionsSite( 'de_DE', [ 'h1' => [
	'global'	=> [ 'category' => COLLECTIONS_HOSTILE, 'price' => 1, 'date' => '2026-12-01', 'featured' => true ],
	'de_DE'		=> [ 'title' => COLLECTIONS_HOSTILE, 'summary' => $big ],
] ] );
$one = 'query="date=2026-12-01"';

$least = [ 'cards' => 1000000, 'list' => 2000000, 'table' => 1000000, 'filter' => 1000 ];
foreach( [
	'cards'	=> '[collection /services '. $one. '][section-col variant="card"][section-title .title][text .summary][/section-col][/collection]',
	'list'	=> '[collection-list /services '. $one. '][text .title][text .summary][html][[title]] [[category]] [[summary]][/html][/collection-list]',
	'table'	=> '[collection-table /services '. $one. '][collection-col .title title="Titel"][collection-col .summary][collection-col .category][/collection-table]',
	'filter'	=> '[collection-filter by=".category"][collection-list /services][text .title][/collection-list][/collection-filter]',
] as $label => $source ) {

	$drawn = collectionsDraw( $hostile, $source );
	$text = str_replace( [ '&#91;', '&lt;', '&gt;', '&quot;', '&#039;' ], [ '[', '<', '>', '"', "'" ], $drawn );

	check( $label. ': a field that spells a template, a fill, a token, a tag, a quote, a call, a null byte and a megabyte is text - nothing is read, nothing runs, the megabyte is there',
		str_contains( $drawn, 'TEMPLATE-WAS-READ' ) === false && str_contains( $drawn, 'COMPANYNAME' ) === false && str_contains( $drawn, '<script' ) === false && str_contains( $drawn, "\0" ) === false
		&& array_intersect( collectionsDom( $drawn )[0], [ 'script', 'input', 'img' ] ) === [] && in_array( 'onload', collectionsDom( $drawn )[1], true ) === false && substr_count( $text, 'alert(1)' ) >= 1 && substr_count( $text, '[template /templates/evil]' ) >= 1 && substr_count( $text, '[[/project/company/general/name]]' ) >= 1
		&& strlen( $drawn ) > $least[$label] );
}

$drawn = collectionsDraw( $hostile, '[collection-filter by=".category"][collection-list /services '. $one. '][text .title][/collection-list][/collection-filter]' );
check( 'the value that marks a row is an attribute that nothing leaves: the quote, the angle brackets and the brackets of a call are entities, and the null byte is gone', preg_match( '/data-collection-value="([^"]*)"/', $drawn, $found ) === 1
	&& str_contains( $found[1], '&quot;&gt;&lt;script&gt;' ) === true && str_contains( $found[1], '[' ) === false && str_contains( $found[1], ']' ) === true && str_contains( $drawn, '"><script' ) === false && str_contains( $drawn, "\0" ) === false );
preg_match( '/data-collection-value="([^"]*)"/', $drawn, $first );
check( 'the order of the buttons is as safe as the values: the same text in a list that the script reads, no quote and no bracket of it left in the attribute, the null byte gone', collectionsOrder( $drawn ) === [ html_entity_decode( $first[1], ENT_QUOTES | ENT_HTML5, 'UTF-8' ) ]
	&& preg_match( '/data-order="([^"]*)"/', $drawn, $order ) === 1 && str_contains( $order[1], '[' ) === false && str_contains( $order[1], "\0" ) === false && in_array( 'onload', collectionsDom( $drawn )[1], true ) === false );
$longValue = collectionsSite( 'de_DE', [ 'h2' => [ 'global' => [ 'category' => str_repeat( 'ä', 500 ). '<b>x</b>', 'price' => 1, 'date' => '2026-12-02', 'featured' => true ], 'de_DE' => [ 'title' => 'Lang' ] ] ] );
preg_match( '/data-collection-value="([^"]*)"/', collectionsDraw( $longValue, '[collection-filter by=".category"][collection-list /services query="date=2026-12-02"][text .title][/collection-list][/collection-filter]' ), $found );
check( 'a value of the field that is long is cut at 120 characters', mb_strlen( html_entity_decode( $found[1], ENT_QUOTES | ENT_HTML5, 'UTF-8' ) ) === 120 && str_contains( $found[1], '<' ) === false );

// what the call itself says: the text of an empty list, the caption of a table, the title of a column and the label of a filter
$source = '[collection-table /nothing empty="'. addcslashes( COLLECTIONS_HOSTILE, '"\\' ). '"][collection-col .title][/collection-table]'
	. '[collection-table /services '. $one. ' caption="'. addcslashes( COLLECTIONS_HOSTILE, '"\\' ). '"][collection-col .title title="'. addcslashes( COLLECTIONS_HOSTILE, '"\\' ). '"][/collection-table]'
	. '[collection-filter by=".category" all="'. addcslashes( COLLECTIONS_HOSTILE, '"\\' ). '"][collection-list /services][text .title][/collection-list][/collection-filter]';
$drawn = collectionsDraw( $hostile, $source );
check( 'the text of an empty list, a caption, the title of a column and the label of a filter are text: no call of them is read, no tag or quote of them is one - the fill of the site in them is the site\'s own word, put in as in any attribute',
	str_contains( $drawn, 'TEMPLATE-WAS-READ' ) === false && str_contains( $drawn, '<script' ) === false && str_contains( $drawn, "\0" ) === false && str_contains( $drawn, '<input' ) === false
	&& array_intersect( collectionsDom( $drawn )[0], [ 'script', 'input', 'img' ] ) === [] && in_array( 'onload', collectionsDom( $drawn )[1], true ) === false && substr_count( $drawn, '&lt;script&gt;alert(1)&lt;/script&gt;' ) >= 4 && substr_count( $drawn, 'COMPANYNAME' ) >= 4 );
check( 'and the text of a key that holds them is text as well', str_contains( collectionsDraw( $hostile, '[collection /nothing empty="/template/page-x/hero/evil"][text .title][/collection]' ), '<script' ) === false );

// a query, a sort and a type of elements that are none
$ignored = ninoWarnings();
$unsorted = collectionsBoth( $appData, '' )[1];
check( 'a query whose field is none that is a field - or that is read as something else - selects nothing: a field the type has none of, a quote, a slash, an empty key, no =, a tab, a tag',
	collectionsSlugs( collectionsDraw( $appData, '[collection /services query="nofield=1"][text .slug][/collection]' ) ) === []
	&& collectionsDraw( $appData, '[collection /services query="category=Bau&x\\"y=1"][text .slug][/collection]' ) === '' && collectionsDraw( $appData, '[collection /services query="category=Bau&../x=1"][text .slug][/collection]' ) === ''
	&& collectionsDraw( $appData, '[collection /services query="=1"][text .slug][/collection]' ) === '' && collectionsDraw( $appData, '[collection /services query="category"][text .slug][/collection]' ) === ''
	&& collectionsDraw( $appData, '[collection /services query="category=Bau&"][text .slug][/collection]' ) === '' && collectionsDraw( $appData, "[collection /services query=\"category=Bau\t\"][text .slug][/collection]" ) === ''
	&& collectionsDraw( $appData, '[collection /services query="<script>=1"][text .slug][/collection]' ) === '' && collectionsDraw( $appData, '[collection /services query="category=Bau&[[/x/y/z/w]]=1"][text .slug][/collection]' ) === '' );
check( '...and a query that cannot be read never selects more than it was written to: the field a pair is dropped for is never one that narrows', collectionsDraw( $appData, '[collection /services query="featured=1&nofield"][text .slug][/collection]' ) === ''
	&& collectionsSlugs( collectionsDraw( $appData, '[collection /services query="category=Bau" sort="title"][text .slug][/collection]' ) ) !== [] );
check( 'a query of a thousand characters or more is none, and one below it is read', collectionsDraw( $appData, '[collection /services query="'. str_repeat( 'category=Bau&', 80 ). 'category=Bau"][text .slug][/collection]' ) === ''
	&& collectionsSlugs( collectionsDraw( $appData, '[collection /services query="'. str_repeat( 'category=Bau&', 5 ). 'category=Bau"][text .slug][/collection]' ) ) !== [] );
check( 'a sort that holds a character a field has none of is no sort - not a failure: the elements come as they are -, and a field that does not exist sorts nothing',
	collectionsSlugs( collectionsDraw( $appData, '[collection /services sort="title; DROP"][text .slug][/collection]' ) ) === $unsorted && collectionsSlugs( collectionsDraw( $appData, '[collection /services sort="../../x"][text .slug][/collection]' ) ) === $unsorted
	&& collectionsSlugs( collectionsDraw( $appData, '[collection /services sort="<script>"][text .slug][/collection]' ) ) === $unsorted
	&& collectionsSlugs( collectionsDraw( $appData, "[collection /services sort=\"title\t\"][text .slug][/collection]" ) ) === $unsorted && collectionsSlugs( collectionsDraw( $appData, '[collection /services sort="title"][text .slug][/collection]' ) ) !== $unsorted && collectionsSlugs( collectionsDraw( $appData, '[collection /services sort="__proto__,constructor"][text .slug][/collection]' ) ) === $unsorted
	&& collectionsSlugs( collectionsDraw( $appData, '[collection /services sort="nofield"][text .slug][/collection]' ) ) === $unsorted && collectionsSlugs( collectionsDraw( $appData, '[collection /services sort="'. str_repeat( 'a', 300 ). '"][text .slug][/collection]' ) ) === $unsorted
	&& collectionsSlugs( collectionsDraw( $appData, '[collection /services sort="-price,title"][text .slug][/collection]' ) ) === collectionsBoth( $appData, 'sort="-price,title"' )[0] );

$ignored = ninoWarnings();
$none = [];
foreach( [ '/../../private/config', '/services/../tags', '/services/..', '/..', '/services/', 'services', '/Services', '/services/s1', '//services', '/services%2f..', '/[[/x/y/z/w]]', '/'. str_repeat( 'a', 100 ), '/1services', '/.hidden', '\\services', "/services\0" ] as $uri )
	foreach( [ 'collection' => '[text .slug]', 'collection-list' => '[text .slug]', 'collection-table' => '[collection-col .slug]' ] as $loop => $body )
		if( preg_match( '/<(?:li|tr|td|p|div)\b/', collectionsDraw( $appData, '['. $loop. ' '. $uri. ']'. $body. '[/'. $loop. ']' ) ) === 1 )
			$none[] = $loop. ' '. $uri;
check( 'a type of elements that is none - a way up, a way in, an element, no slash, a capital, a fill, a null byte - draws no element: where the call is no call, the page shows what is written'. ( $none === [] ? '' : ' - '. implode( ' | ', $none ) ), $none === [] );
$late = ninoWarnings();
check( '...and the type file is never asked for - the kernel has no notice to say - while the loop says, once for each of the calls, that the type is none', count( array_filter( $late, static fn( string $note ): bool => str_contains( $note, 'Invalid element php file' ) ) ) === 0
	&& count( array_filter( $late, static fn( string $note ): bool => str_starts_with( $note, 'Collections: the type of elements' ) ) ) >= 1 );
check( 'a limit and an offset that are no numbers, or are out of bounds, are the default or the bound', count( collectionsSlugs( collectionsDraw( $appData, '[collection /services limit="abc"][text .slug][/collection]' ) ) ) === 8
	&& count( collectionsSlugs( collectionsDraw( $appData, '[collection /services limit="-3"][text .slug][/collection]' ) ) ) === 8 && count( collectionsSlugs( collectionsDraw( $appData, '[collection /services limit="99999999999"][text .slug][/collection]' ) ) ) === 8
	&& count( collectionsSlugs( collectionsDraw( $appData, '[collection /services offset="-3"][text .slug][/collection]' ) ) ) === 8 && count( collectionsSlugs( collectionsDraw( $appData, '[collection /services offset="2" limit="1"][text .slug][/collection]' ) ) ) === 1 );

// the pieces of a loop drawn where there is no loop: no element, no field
check( 'the fields of a loop are read where there is a loop and nowhere else: a .field outside draws nothing', collectionsDraw( $appData, '[text .title]' ) === '' && collectionsDraw( $appData, '[collection-list /services limit="1"][text .nofield][/collection-list]' ) === '<ul class="collection-list"><li class="collection-list-row"></li></ul>' );

// a table in a loop: its headings are drawn with no element, and a column reads the field of its own type and not the one of the loop round it
$inLoop = collectionsDraw( $appData, '[collection-list /services limit="1" sort="title"][collection-table /tags sort="title"][collection-col .title title="Name"][collection-col .category title="Kategorie"][/collection-table][/collection-list]' );
check( 'a table inside a loop has its headings as headings, and a column of a field the inner type has none of is empty, whatever the outer element holds', str_contains( $inLoop, '<thead><tr><th class="collection-col" scope="col">Name</th><th class="collection-col" scope="col">Kategorie</th></tr></thead>' ) === true
	&& str_contains( $inLoop, '<td class="collection-col">Beliebt</td><td class="collection-col"></td>' ) === true && str_contains( $inLoop, 'Maler' ) === false );
$outer = collectionsDraw( $appData, '[collection-filter by=".category"][collection-filter by="../x"][collection-list /tags][text .slug][/collection-list][/collection-filter][/collection-filter]' );
check( 'a filter that names no field takes the loops below it from the filter above it: they are marked by no one', str_contains( $outer, 'data-collection-value' ) === false && substr_count( $outer, 'data-collection-filter' ) === 1 );
$limits = [ 'limit' => 10000, 'offset' => 1000000 ];
$bounded = [];
foreach( [ 'collection', 'collection-list', 'collection-table' ] as $name )
	foreach( $limits as $attribute => $max )
		if( $schemas[$name]['attributes'][$attribute]['min'] !== 0 || $schemas[$name]['attributes'][$attribute]['max'] !== $max )
			$bounded[] = $name. ' '. $attribute;
check( 'limit and offset are whole numbers from 0, a limit up to 10000 and an offset up to a million, on each of the three loops'. ( $bounded === [] ? '' : ' - '. implode( ', ', $bounded ) ), $bounded === [] );

// an id in a loop: no component of a loop carries one - it would stand once for each element - and a ~name there reads the section round the loop
\Nino\Html::addFills( $appData, [ '/template/page-x/card/title' => 'TITLE-OF-THE-CARD', '/template/page-x/outer/title' => 'TITLE-OUTSIDE', '/template/page-x/top/title' => 'TITLE-OUTSIDE' ], 'de_DE' );
$ids = collectionsDraw( $appData, '[section-row][collection /services limit="3"][section-col id="card" variant="card"][section-title ~title][/section-col][/collection][/section-row]', [ 'scope' => 'outer' ] );
check( 'a component with an id in a loop is drawn with none, for each element, and its ~name reads the section outside the loop', substr_count( $ids, ' id=' ) === 0 && substr_count( $ids, 'TITLE-OUTSIDE' ) === 3 && str_contains( $ids, 'TITLE-OF-THE-CARD' ) === false );
$ids = collectionsDraw( $appData, '[section-wrap id="top"][section-row][collection-list /services limit="2"][collection /tags][section-col id="deep" size="50"][section-title ~title id="deeper"][/section-col][/collection][/collection-list][/section-row][/section-wrap]', [ 'scope' => 'outer' ] );
check( 'in loops in loops no id is left, at any depth - and the id of what stands outside every loop is still its own', substr_count( $ids, ' id="' ) === 1 && str_contains( $ids, '<section id="top"' ) === true && substr_count( $ids, 'TITLE-OUTSIDE' ) === 4 );
check( 'a loop that holds a section-wrap with an id draws no id either, and nor does a column three levels down', substr_count( collectionsDraw( $appData, '[collection /services limit="2"][section-wrap id="s"][section-row][section-col][text .title][/section-col][/section-row][/section-wrap][/collection]' ), ' id=' ) === 0
	&& substr_count( collectionsDraw( $appData, '[collection /services limit="2"][section-wrap][section-row][section-col id="c"][text .title][/section-col][/section-row][/section-wrap][/collection]' ), ' id=' ) === 0 );

// the token that tells a loop which field a filter asks for is no textfill: no text of a page reads it
$leak = collectionsDraw( $appData, '[collection-filter by=".category"][html]X[[.filter]]Y[/html][collection-list /services limit="1"][text .title][/collection-list][/collection-filter]' );
check( 'what a filter tells the loops below it is read by no textfill: a page that writes the token or its key gets text', str_contains( $leak, 'category' ) === false && str_contains( $leak, 'X[[.filter]]Y' ) === true );

// lang: only a field that is per language can be a text of another language, and only where the element took one
$lang = collectionsSite( 'en_US', [ 'm1' => [ 'global' => [ 'category' => 'Mix', 'price' => 5, 'date' => '2026-09-01', 'featured' => false ], 'de_DE' => [ 'title' => 'Mix de', 'summary' => 'Zusammenfassung' ], 'en_US' => [ 'title' => 'Mix en' ] ] ] );
$cells = collectionsDraw( $lang, '[collection-table /services query="category=Mix"][collection-col .category][collection-col .price][collection-col .summary][/collection-table]' );
check( 'a cell has a lang where its field is per language and the element took text from another one - and the number and the category of the element have none', str_contains( $cells, '<td class="collection-col">Mix</td><td class="collection-col">5</td><td class="collection-col" lang="de-DE">Zusammenfassung</td>' ) === true );
check( 'an element with all its text in the language of the page has no lang on any cell', str_contains( collectionsDraw( $lang, '[collection-table /services query="category=Metall"][collection-col .title][collection-col .price][/collection-table]' ), 'lang=' ) === false );

// lang for each field: the title is English and the summary German on the page in English - the title has no lang, the summary has
$mixed = collectionsDraw( $lang, '[collection-table /services query="category=Mix"][collection-col .title][collection-col .summary][collection-col .category][/collection-table]' );
check( 'an English title and a German summary on the page in English: the title has no lang, the summary lang="de-DE", the category none', str_contains( $mixed, '<td class="collection-col">Mix en</td><td class="collection-col" lang="de-DE">Zusammenfassung</td><td class="collection-col">Mix</td>' ) === true
	&& substr_count( $mixed, 'lang=' ) === 1 );
$only = collectionsDraw( $lang, '[collection-table /services query="category=Bau" sort="title"][collection-col .title][collection-col .summary][/collection-table]' );
check( 'an element that exists in German only has the lang on both of its cells, and the elements beside it on none', str_contains( $only, '<td class="collection-col" lang="de-DE">Nur deutsch</td><td class="collection-col" lang="de-DE">Kein englischer Text</td>' ) === true && substr_count( $only, 'lang=' ) === 2 );
// lang of a row: the element as a whole - what is not the page's language in every text it has
$rows = collectionsDraw( $lang, '[collection-list /services query="category=Bau" sort="title"][text .title][/collection-list]' );
check( 'a row of a list has the lang of the element where all its text is from another language: the element in German only has it, the elements in the language of the page have none',
	str_contains( $rows, '<li class="collection-list-row" lang="de-DE"><div class="text">Nur deutsch</div></li>' ) === true && substr_count( $rows, 'lang=' ) === 1 && substr_count( $rows, '<li class="collection-list-row"><div class="text">' ) === 3 );
check( 'an element with one field of its own language and another from the other has no lang on its row - the cells name the language of each field -, and the page in German and a loop that draws no row have none',
	str_contains( collectionsDraw( $lang, '[collection-list /services query="category=Mix"][text .title][/collection-list]' ), 'lang=' ) === false
	&& str_contains( collectionsDraw( $appData, '[collection-list /services][text .title][/collection-list]' ), 'lang=' ) === false
	&& str_contains( collectionsDraw( $lang, '[collection /services query="category=Bau"][text .title][/collection]' ), 'lang=' ) === false
	&& str_contains( collectionsDraw( $lang, '[collection-filter by=".category"][collection /services query="category=Bau"][text .title][/collection][/collection-filter]' ), 'lang=' ) === false
	&& str_contains( collectionsDraw( $lang, '[collection-filter by=".category"][collection-list /services query="category=Bau" sort="title"][text .title][/collection-list][/collection-filter]' ), '<li class="collection-list-row" lang="de-DE" data-collection-value="Bau">' ) === true );
$langOf = new ReflectionMethod( \Nino\Modules\Collections::class, '_lang' );
$model = [ 'title' => [ 'locale' => true ], 'summary' => [ 'locale' => true ], 'price' => [] ];
check( 'a row has a lang only where one language gave all the text: two languages, a field with no text of its own and no field taken give none',
	$langOf->invoke( null, [ 'title' => 'a', 'summary' => 'b', '.langs' => [ 'title' => 'de-DE', 'summary' => 'fr-FR' ] ], $model ) === ''
	&& $langOf->invoke( null, [ 'title' => 'a', 'summary' => '', '.langs' => [ 'title' => 'de-DE' ] ], $model ) === ' lang="de-DE"'
	&& $langOf->invoke( null, [ 'title' => 'a', 'summary' => 'b', '.langs' => [] ], $model ) === '' );
check( 'on the page in German the same elements have no lang at all', str_contains( collectionsDraw( $appData, '[collection-table /services query="category=Mix"][collection-col .title][collection-col .summary][/collection-table]' ), 'lang=' ) === false
	&& str_contains( collectionsDraw( $appData, '[collection-table /services][collection-col .title][collection-col .summary][/collection-table]' ), 'lang=' ) === false );

// what a user of the feature writes: the fields of the element in the attributes of what it holds, and the picture of an image field
$buttons = collectionsDraw( $appData, '[collection /services sort="title" limit="2"][button href="/leistungen/[[.slug]]" text="Mehr zu [[title]]"][/collection]' );
check( 'a button in a collection has the slug of its element in its link and the title in its text, one for each element', $buttons === '<a class="button" href="/leistungen/s5">Mehr zu Farbe</a><a class="button" href="/leistungen/s3">Mehr zu Glas</a>' );
$spelled = collectionsDraw( $hostile, '[collection /services '. $one. '][button href="/x/[[title]]" text="Mehr zu [[title]]"][button href="[[category]]" text="[[category]]"][/collection]' );
[ $spelledElements, $spelledAttributes ] = collectionsDom( $spelled );
$spelledText = str_replace( [ '&#91;', '&lt;', '&gt;', '&quot;', '&#039;' ], [ '[', '<', '>', '"', "'" ], $spelled );
check( 'a field that spells a call, a fill, a tag, a quote and a null byte is text in the link and the text of a button: nothing is read, no element and no attribute comes of it',
	array_diff( $spelledElements, [ 'html', 'body', 'a', 'span' ] ) === [] && array_diff( $spelledAttributes, [ 'class', 'href' ] ) === [] && str_contains( $spelled, 'TEMPLATE-WAS-READ' ) === false && str_contains( $spelled, 'COMPANYNAME' ) === false
	&& str_contains( $spelled, "\0" ) === false && substr_count( $spelledText, '[template /templates/evil]' ) >= 2 && substr_count( $spelledText, 'alert(1)' ) >= 3 );
$scheme = collectionsSite( 'de_DE', [ 'j1' => [ 'global' => [ 'category' => 'javascript:alert(1)', 'price' => 1, 'date' => '2026-12-03', 'featured' => true ], 'de_DE' => [ 'title' => 'J' ] ] ] );
check( 'a field that is a script address is no link: the button is drawn without one', collectionsDraw( $scheme, '[collection /services query="date=2026-12-03"][button href="[[category]]" text="Go"][/collection]' ) === '<span class="button">Go</span>' );

$pictures = collectionsSite();
collectionsType( $pictures, 'team', [ 'title' => [ 'type' => 'string', 'locale' => true ], 'photo' => [ 'type' => 'image', 'alt' => 'photoAlt' ], 'photoAlt' => [ 'type' => 'string', 'locale' => true ] ],
	[ 't1' => [ 'photo' => 'elements/team/anna.jpg' ], 't2' => [ 'photo' => '' ], 't3' => [ 'photo' => '../private/key.jpg' ], 't4' => [ 'photo' => '/etc/passwd' ] ],
	[ 'de_DE' => [ 't1' => [ 'title' => 'Anna', 'photoAlt' => 'Anna "A" <b>' ], 't2' => [ 'title' => 'Ben' ], 't3' => [ 'title' => 'Cem' ], 't4' => [ 'title' => 'Dora' ] ] ] );
$team = collectionsDraw( $pictures, '[collection /team sort="title"][image .photo][/collection]' );
check( 'an image of the field of each element in a collection: the file the field holds, the alt text of the field the model names - escaped - and nothing for an element with no file, one that climbs and one that is absolute',
	$team === '<figure class="image"><img src="/public/images/elements/team/anna.jpg" alt="Anna &quot;A&quot; &lt;b&gt;" loading="lazy" decoding="async"></figure>' );
check( 'the same in a list, and with the title of the element as the alt text of the call', collectionsDraw( $pictures, '[collection-list /team sort="title" limit="1"][image .photo alt="[[title]]"][/collection-list]' )
	=== '<ul class="collection-list"><li class="collection-list-row"><figure class="image"><img src="/public/images/elements/team/anna.jpg" alt="Anna" loading="lazy" decoding="async"></figure></li></ul>' );

// a column writes a value as the formatters of the kernel do, in the language of the page
$schemaAs = $schemas['collection-col']['attributes']['as'];
check( 'the formatters of the kernel are the options of as: the empty word and each of them by name', $schemaAs['class'] === false && $schemaAs['default'] === '' && array_diff( [ '', 'number', 'currency', 'percent', 'date', 'date-long', 'datetime', 'time' ], $schemaAs['options'] ) === [] );
$formats = [];
foreach( [ 'de_DE', 'en_US' ] as $language ) {

	$site = collectionsSite( $language );
	$cell = static fn( string $source ): string => collectionsDraw( $site, $source );
	$formats[$language] = [ $cell( '[collection-table /services query="category=Maler"][collection-col .price as="currency" align="end"][/collection-table]' ), \Nino\Format::currency( $site, 5 ), $cell( '[collection-table /services query="category=Maler"][collection-col .date as="date"][/collection-table]' ), \Nino\Format::date( $site, '2026-05-01' ) ];
}
check( 'a column with as writes the price and the date as the language of the page does - German and English differ - and the heading and the alignment are the column\'s', $formats['de_DE'][1] !== $formats['en_US'][1] && $formats['de_DE'][3] !== $formats['en_US'][3]
	&& str_contains( $formats['de_DE'][0], '<td class="collection-col collection-col--align-end">'. htmlspecialchars( (string) $formats['de_DE'][1], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' ). '</td>' ) === true
	&& str_contains( $formats['en_US'][0], '>'. htmlspecialchars( (string) $formats['en_US'][1], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' ). '</td>' ) === true
	&& str_contains( $formats['de_DE'][2], '>'. $formats['de_DE'][3]. '</td>' ) === true && str_contains( $formats['en_US'][2], '>'. $formats['en_US'][3]. '</td>' ) === true );
check( 'a value the formatter cannot read, and a formatter nobody has, show the value as it is', str_contains( collectionsDraw( $appData, '[collection-table /services query="category=Maler"][collection-col .title as="number"][collection-col .price as="nonsense"][/collection-table]' ), '<td class="collection-col">Farbe</td><td class="collection-col">5</td>' ) === true );

// the budget of a request: the rounds of all the loops together
$reflection = new \ReflectionClassConstant( '\\Nino\\Modules\\Collections', 'ROUNDS' );
$many = collectionsSite();
$rows = [];
for( $i = 0; $i < 30; $i++ )
	$rows['r'. $i] = [ 'title' => 'R'. $i ];
collectionsType( $many, 'many', [ 'title' => [ 'type' => 'string', 'locale' => true ] ], [ 'r0' => [] ] + array_map( static fn( array $row ): array => [], $rows ), [ 'de_DE' => $rows ] );
$ignored = ninoWarnings();
collectionsDraw( $many, '[collection /services][text .slug][/collection]' );
check( 'a request below the budget draws every round and says nothing', ( $many['./nino/collections/rounds'] ?? 0 ) === 8 && ninoWarnings() === [] && $reflection->getValue() === 20000 );
$deep = collectionsDraw( $many, '[collection-list /many][collection-list /many][collection-list /many][text .slug][/collection-list][/collection-list][/collection-list]' );
$said = ninoWarnings();
check( 'three loops of 30 inside each other would be 27930 rounds: they draw 20000 rounds, no more, and say so once as a warning', $many['./nino/collections/rounds'] === 20000 && count( $said ) === 1 && str_contains( $said[0], '20000' ) === true );
check( '...the budget is that of the outermost loop: a loop beside it draws its rounds, and the warning is not said twice', substr_count( collectionsDraw( $many, '[collection /many][text .slug][/collection]' ), '<div class="text">' ) === 30 && ninoWarnings() === [] && strlen( $deep ) > 1000 );
$noisy = collectionsSite();
collectionsType( $noisy, 'many', [ 'title' => [ 'type' => 'string', 'locale' => true ] ], [ 'r0' => [] ] + array_map( static fn( array $row ): array => [], $rows ), [ 'de_DE' => $rows ] );
for( $i = 0; $i < 25; $i++ )
	collectionsDraw( $noisy, '[collection /many sort="t'. $i. '!" limit="1"][text .slug][/collection]' );
$ignored = ninoWarnings();
collectionsDraw( $noisy, '[collection-list /many][collection-list /many][collection-list /many][text .slug][/collection-list][/collection-list][/collection-list]' );
$said = ninoWarnings();
check( '...and a request that has said its twenty things still says that a loop ran out of rounds', count( $said ) === 1 && str_contains( $said[0], '20000' ) === true );

// what a loop that draws less than it was written to says: once for each place, and at most twenty things
$notes = collectionsSite();
$ignored = ninoWarnings();
foreach( [ '[collection /services/x][text .slug][/collection]', '[collection /services/x][text .slug][/collection]', '[collection /services query="a"][text .slug][/collection]', '[collection /services sort="a;b"][text .slug][/collection]', '[collection /services sort="a;b"][text .slug][/collection]' ] as $source )
	collectionsDraw( $notes, $source );
$said = ninoWarnings();
check( 'a type that is none, a query that cannot be read and a sort that is not one are said in the log - once for each place, not for each call', count( $said ) === 3 && str_contains( $said[0], 'the type of elements "/services/x"' ) === true && str_contains( $said[1], 'query "a"' ) === true && str_contains( $said[2], 'sort "a;b"' ) === true );
for( $i = 0; $i < 30; $i++ )
	collectionsDraw( $notes, '[collection /services sort="a;'. $i. '"][text .slug][/collection]' );
check( 'and at most twenty things in a request', count( ninoWarnings() ) === 17 );
$notes = collectionsSite();
$ignored = ninoWarnings();
collectionsDraw( $notes, '[collection /services sort="ä;'. str_repeat( 'x', 200 ). '"][text .slug][/collection]' );
$said = ninoWarnings();
check( 'what a note shows of a value is printable and short', count( $said ) === 1 && str_contains( $said[0], 'sort "??;x' ) === true && strlen( $said[0] ) < 260 && preg_match( '/[^\x20-\x7E]/', $said[0] ) === 0 );

// a table without a caption is drawn and says nothing; with one the box is named by it
$tables = collectionsSite();
$ignored = ninoWarnings();
$one = collectionsDraw( $tables, '[collection-table /services limit="1"][collection-col .title][/collection-table]' );
collectionsDraw( $tables, '[collection-table /services limit="1"][collection-col .title][/collection-table]' );
$said = ninoWarnings();
$two = collectionsDraw( $tables, '[collection-table /services limit="1" caption="A"][collection-col .title][/collection-table][collection-table /services limit="1" caption="B"][collection-col .title][/collection-table]' );
preg_match_all( '/aria-labelledby="([^"]*)"[^>]*><table class="collection-table"><caption id="([^"]*)">/', $two, $found );
check( 'a table with no caption has the word of the feature for a name and nothing is said; with a caption the box is named by it, and two tables have two ids', str_contains( $one, 'aria-label="Tabelle"' ) === true && $said === []
	&& count( $found[1] ) === 2 && $found[1] === $found[2] && $found[1][0] !== $found[1][1] );

// the text of an empty list and the caption are what the register hands over: no bracket of them is changed
check( 'a text with brackets in it is shown as it is written', collectionsDraw( $appData, '[collection /nothing empty="a [b] c"][text .slug][/collection]' ) === '<p class="collection-empty">a [b] c</p>'
	&& str_contains( collectionsDraw( $appData, '[collection-table /services limit="1" caption="a [b] c"][collection-col .title][/collection-table]' ), '<caption id="collection-table-' ) === true );
$ignored = ninoWarnings();

// the part: a long value is a button that wraps and never wider than its row, a row keeps its marker where a reset takes it, and paper shows every row
check( 'a button wraps a long word and is never wider than its row; a row of a list takes the marker of its list', preg_match( '/\.collection-filter-button\s*\{[^}]*max-width:\s*100%;[^}]*overflow-wrap:\s*anywhere;[^}]*text-align:\s*start/', $css ) === 1
	&& preg_match( '/\.collection-list-row\s*\{[^}]*list-style:\s*inherit/', $css ) === 1 );
preg_match( '/@media print\s*\{(.*)\}\s*$/s', $css, $print );
check( 'on paper every row is shown - the box, the row of a list and the row of a table that a filter hides too - and the buttons and the status are not', isset( $print[1] ) === true && preg_match( '/\.collection-item\[hidden\]\s*\{\s*display:\s*contents/', $print[1] ) === 1
	&& preg_match( '/\.collection-list-row\[hidden\]\s*\{\s*display:\s*list-item/', $print[1] ) === 1 && preg_match( '/\.collection-table-row\[hidden\]\s*\{\s*display:\s*table-row/', $print[1] ) === 1
	&& preg_match( '/\.collection-filter-nav,\s*\.collection-filter-status\s*\{\s*display:\s*none/', $print[1] ) === 1 );

// --- F. The script of the filter ---------------------------------------------------------------------------------

echo "\nF. The script of the filter\n";

$script = (string) file_get_contents( $featureDir. '/assets/collection-filter.js' );
check( 'the script is small and asks nothing of the network or the page\'s markup: no request, no eval, no markup written, no storage, no dependency', strlen( $script ) < 4096
	&& preg_match( '/\b(?:fetch|XMLHttpRequest|eval|localStorage|sessionStorage|document\.write|importScripts|WebSocket|sendBeacon)\b|innerHTML|outerHTML|insertAdjacentHTML|new Function|require\(|import\s/', $script ) === 0 );
check( 'the script uses let and const and no var, and its row of buttons is a group and no landmark', preg_match( '/\bvar\b/', $script ) === 0 && preg_match( '/createElement\( \'nav\' \)/', $script ) === 0 && str_contains( $script, "'role', 'group'" ) === true );
check( 'it makes a button of a name that is text, says the state with aria-pressed and the count in a status, and hides rows with the attribute hidden', str_contains( $script, 'textContent' ) === true && str_contains( $script, 'aria-pressed' ) === true
	&& str_contains( $script, "'role', 'status'" ) === true && str_contains( $script, '.hidden' ) === true && str_contains( $script, 'data-collection-value' ) === true );

$node = function_exists( 'shell_exec' ) === true ? trim( (string) @shell_exec( 'command -v node 2>/dev/null' ) ) : '';

if( $node === '' || function_exists( 'exec' ) === false )
	echo "  --  - node is not available here: collections-js-smoke.js was NOT run\n";
else {

	$output = [];
	$exit = 1;
	exec( 'NINO_ROOT='. escapeshellarg( $root ). ' '. escapeshellarg( $node ). ' '. escapeshellarg( __DIR__. '/collections-js-smoke.js' ). ' 2>&1', $output, $exit );
	check( 'collections-js-smoke.js passes - '. ( $output === [] ? 'no output' : end( $output ) ), $exit === 0 );
}

// --- G. Words, activation, sizes, cost -----------------------------------------------------------------------

echo "\nG. Words, activation, sizes and the cost of a loop\n";

$wordsDe = (array) include $featureDir. '/install/text/de_DE.php';
$wordsEn = (array) include $featureDir. '/install/text/en_US.php';
check( 'the words of the feature are the same four keys in both languages, each of the form /feature/collections/<part>/<name>, and no key is empty', array_keys( $wordsDe ) === array_keys( $wordsEn ) && count( $wordsDe ) === 4
	&& array_filter( array_keys( $wordsDe ), static fn( string $key ): bool => preg_match( '#^\[\[/feature/collections/[a-z]+/[a-z]+\]\]$#D', $key ) !== 1 ) === [] && array_filter( array_merge( $wordsDe, $wordsEn ), static fn( string $text ): bool => trim( $text ) === '' ) === []
	&& $wordsDe !== $wordsEn && str_contains( $wordsDe['[[/feature/collections/filter/shown]]'], '{shown}' ) === true && str_contains( $wordsDe['[[/feature/collections/filter/shown]]'], '{total}' ) === true
	&& str_contains( $wordsEn['[[/feature/collections/filter/shown]]'], '{shown}' ) === true && str_contains( $wordsEn['[[/feature/collections/filter/shown]]'], '{total}' ) === true );

$active = ninoSandbox( 'collections-activate' );
$sandboxes[] = ninoSandboxDir( $active );
$active['/nino/modules'] = [];
\Nino\AppData::writeContentData( $active, [ '/nino/modules', '/nino/locales/available', '/nino/locales/native' ] );
$activated = \Nino\Features::activate( $active, 'collections' );
$de = \Nino\Filesystem::getFileContent( $active, '/text/de_DE.php', [] );
check( 'activation lists the class, records the version, and puts the four words into the text of both languages', $activated === true && in_array( '\\Nino\\Modules\\Collections', $active['/nino/modules'], true ) === true
	&& \Nino\Features::get( $active, 'collections' )['installed'] === '1.0.0' && ( $de['[[/feature/collections/filter/all]]'] ?? '' ) === 'Alle'
	&& count( array_filter( array_keys( (array) $de ), static fn( mixed $key ): bool => str_starts_with( (string) $key, '[[/feature/collections/' ) ) ) === 4
	&& ( \Nino\Filesystem::getFileContent( $active, '/text/en_US.php', [] )['[[/feature/collections/filter/all]]'] ?? '' ) === 'All' );
$ignored = ninoWarnings();

// sizes
$partBytes = strlen( $css );
check( 'sizes: the part below 6 KB, the script below 4 KB', $partBytes < 6144 && strlen( $script ) < 4096 );

// the cost of a loop
$thousand = 1000;
$data = [ 'model' => [ 'title' => [ 'type' => 'string', 'locale' => true ], 'summary' => [ 'type' => 'string', 'locale' => true ], 'category' => [ 'type' => 'string' ] ], '*' => [ '*' => [] ], 'de_DE' => [] ];
for( $i = 0; $i < $thousand; $i++ ) {
	$data['*']['e'. $i] = [ 'category' => 'c'. ( $i % 7 ) ];
	$data['de_DE']['e'. $i] = [ 'title' => 'Title '. $i, 'summary' => 'Summary of the element '. $i ];
}
$busy = collectionsSite();
\Nino\Filesystem::putFileContent( $busy, '/elements/many.php', $data );

$median = static function( callable $run, int $rounds = 5 ): float {

	$times = [];

	for( $round = 0; $round < $rounds; $round++ ) {

		$started = hrtime( true );
		$run();
		$times[] = ( hrtime( true ) - $started ) / 1e6;
	}

	sort( $times );

	return $times[intdiv( $rounds, 2 )];
};

$kids = '[section-col variant="card" size="100 m:33"][section-title .title tag="h3"][text .summary][/section-col]';
$notes = [];
$children = \Nino\Markup::parse( $kids, \Nino\Components::containers( $busy ), $notes );
$elements = \Nino\Modules\Elements::queryLoop( $busy, [ '/many', 'sort' => 'title' ], true );
$model = \Nino\Elements::getElementModel( $busy, '/many' );
$loop = '';
$whole = $median( static function() use ( &$busy, &$loop ): void {
	$loop = collectionsDraw( $busy, '[collection /many sort="title"]'. '[section-col variant="card" size="100 m:33"][section-title .title tag="h3"][text .summary][/section-col]'. '[/collection]' );
} );
$alone = $median( static function() use ( &$busy, $children, $elements, $model ): void {

	foreach( $elements as $index => $element )
		\Nino\Components::render( $busy, $children, [ 'template' => 'page-x', 'element' => [ 'uri' => '/many', 'id' => $index, 'element' => $element, 'model' => $model ], 'fills' => array_map( 'strval', \Nino\Modules\Elements::fills( $element, $model, $index ) ) ] );
} );
$asking = $median( static function() use ( &$busy ): void {
	\Nino\Modules\Elements::queryLoop( $busy, [ '/many', 'sort' => 'title' ], true );
} );
$viaElements = $median( static function() use ( &$busy ): void {
	\Nino\Html::renderHtml( $busy, '[elements /many sort="title"]<div class="card"><h3>[[title]]</h3><p>[[summary]]</p></div>[/elements]' );
} );

echo sprintf( "  ..  - 1000 elements as cards: %.1f ms for the loop, %.1f ms for the children alone, %.1f ms for the query alone, %.1f ms for [elements] with markup of its own\n", $whole, $alone, $asking, $viaElements );

check( 'a thousand elements as cards: all of them drawn, in a time a page can bear - and each one costs what drawing its children costs, with the query and the fields on top, not more than twice', substr_count( $loop, 'section-col--variant-card' ) === $thousand
	&& $whole < 1500 && $whole <= 2 * $alone + $asking + 25 );

// what the register costs a loop: the table of the fills, read again by every drawing that reads a key
$fills = [];

for( $i = 0; $i < 5000; $i++ )
	$fills['/template/x/k'. $i] = 'text '. $i;

\Nino\Html::addFills( $busy, $fills, 'de_DE' );
$plainRound = $median( static function() use ( &$busy ): void {
	collectionsDraw( $busy, '[collection /many sort="title"][section-col variant="card"][section-title .title][text .summary][/section-col][/collection]' );
}, 3 );
$keyedRound = $median( static function() use ( &$busy ): void {
	collectionsDraw( $busy, '[collection /many sort="title"][section-col variant="card"][section-title .title][text .summary][text /template/x/k5][/section-col][/collection]' );
}, 3 );
$reads = $median( static function() use ( &$busy ): void {

	for( $i = 0; $i < 1000; $i++ )
		\Nino\Html::getFills( $busy );
}, 3 );
echo sprintf( "  ..  - a table of 5000 fills: %.1f ms for a thousand rounds that read no key, %.1f ms where each reads one (%.0f us a round), %.1f ms for a thousand reads of the table\n", $plainRound, $keyedRound, ( $keyedRound - $plainRound ) / $thousand * 1000, $reads );
// A measurement and no assertion: the table of the fills is the register's to read, and the day it keeps it between two drawings this line says so

foreach( $sandboxes as $sandbox )
	if( is_dir( $sandbox ) === true )
		\Nino\Filesystem::removeDir( $sandbox );

ninoDone( $appData );
