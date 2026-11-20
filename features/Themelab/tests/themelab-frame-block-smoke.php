<?php
declare(strict_types=1);

/**
 *	Nino features
 *	themelab-frame-block-smoke.php	Contract test for a frame of the site kept as a block of the project - "Save as a frame block" in the tab of the frames:
 *
 *												A. what is written: the frame as it is saved becomes a block of the category of the frames under the own blocks - its markup, its words in each language, its
 *												   links and menu - and the frame itself is not touched
 *												B. the way round: that block put in the frame again gives the frame it was - the markup and the words, compared as the kernel reads them
 *												C. what a block may not hold is refused in the words of the panel, naming the part - the call of html, a source only the site knows - and nothing is written
 *												E. the block among the blocks of the project: listed, copied, its details changed, taken away, handed out in a package and taken in by a second site
 *												D. who and what: the right of this panel and the token of the session, a template that is no frame, a key that is a path or taken, markup in a name
 *
 *	Usage: php features/Themelab/tests/themelab-frame-block-smoke.php
 *				 NINO_ROOT=../nino php features/Themelab/tests/themelab-frame-block-smoke.php
 */

$root = getenv( 'NINO_ROOT' ) ?: dirname( __DIR__, 3 );
defined( 'NINO_FEATURES_DIR' ) === true || define( 'NINO_FEATURES_DIR', dirname( __DIR__, 2 ) );
require $root. '/tests/harness.php';

function fbCall( array &$appData, string $action, array $data = [] ): array {

	$data += [ 'locale' => \Nino\Locales::getNativeLocale( $appData ) ];

	return ninoCall( $appData, [ \Nino\Admin\Admin::class, 'handlePost' ], $data, $action );
}

function fbFile( string $path ): string {
	return is_file( $path ) === true ? (string) file_get_contents( $path ) : '';
}

/** What the project keeps, by name and hash: for "nothing else is written" */
function fbSnapshot( array &$appData ): array {

	$dir = ninoSandboxDir( $appData );
	$list = [];

	foreach( [ 'private/templates', 'private/text', 'private/blocks', 'private/data/builder' ] as $sub ) {

		if( is_dir( $dir. '/'. $sub ) === false )
			continue;

		foreach( new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $dir. '/'. $sub, FilesystemIterator::SKIP_DOTS ) ) as $file )
			if( $file->isFile() === true )
				$list[substr( (string) $file, strlen( $dir ) )] = hash_file( 'sha256', (string) $file );
	}

	return $list;
}

/** A frame as the kernel reads it, written the one way: the markup and the words under the frame's template, for comparing */
function fbShape( array &$appData, string $template ): array {

	$dir = ninoSandboxDir( $appData );
	$nodes = \Nino\Modules\Builder\Tree::read( $appData, fbFile( $dir. '/private/templates/'. $template. '.tpl' ) );
	$words = [];

	foreach( [ 'de_DE', 'en_US' ] as $locale ) {

		$fills = is_file( $dir. '/private/text/'. $locale. '.php' ) === true ? (array) ( include $dir. '/private/text/'. $locale. '.php' ) : [];

		foreach( $fills as $key => $text )
			if( str_starts_with( (string) $key, '[[/template/'. $template. '/' ) === true )
				$words[$locale][substr( (string) $key, strlen( '[[/template/'. $template ) )] = $text;

		ksort( $words[$locale] );
	}

	return [ \Nino\Markup::write( $nodes, \Nino\Components::containers( $appData ) ), $words ];
}

$site = ninoSandbox( 'themelab-frame-block' );
$sandbox = ninoSandboxDir( $site );
$site['./nino/filesystem/path'] = $root;
$site['/nino/html/assets'] = [];
$site['/nino/html/navs'] = [ 'main' ];
$site['/nino/modules'] = [ '\\Nino\\Modules\\Assets', '\\Nino\\Modules\\Template', '\\Nino\\Modules\\Navigation', '\\Nino\\Modules\\Basis', '\\Nino\\Modules\\Collections', '\\Nino\\Modules\\Builder', '\\Nino\\Modules\\Themelab' ];
\Nino\Html::init( $site );
\Nino\Features::registerComponents( $site );
\Nino\Modules::callModules( $site, 'init' );

$header = "[header-wrap id=\"header\" sticky]\n\t[header-logo]\n\t[header-nav menu=\"main\"]\n\t[header-actions]\n\t\t[button ~button href=\"page:/contact\" size=\"s\"]\n\t[/header-actions]\n[/header-wrap]\n";
\Nino\Filesystem::putFileContent( $site, '/templates/html-header.tpl', "<header>head</header>\n" );
\Nino\Filesystem::putFileContent( $site, '/templates/frame-header.tpl', $header );
\Nino\Filesystem::putFileContent( $site, '/templates/frame-footer.tpl', "[footer-wrap id=\"footer\"]\n[/footer-wrap]\n" );
\Nino\Filesystem::putFileContent( $site, '/templates/page-home.tpl', "<!-- nino:template-name Home -->\n[template /templates/html-header]\n" );
\Nino\Filesystem::putFileContent( $site, '/templates/page-contact.tpl', "<!-- nino:template-name Contact -->\n[template /templates/html-header]\n" );
\Nino\Filesystem::putFileContent( $site, '/text/de_DE.php', [ '[[/template/frame-header/header/button]]' => 'Schreib uns' ] );
\Nino\Filesystem::putFileContent( $site, '/text/en_US.php', [ '[[/template/frame-header/header/button]]' => 'Write to us' ] );
\Nino\Filesystem::putFileContent( $site, '/config.php', [ '/nino/html/navs' => [ 'main' ] ] );
$site['/nino/http/routes'] = [ 'GET://home' => [ 'uri' => '/home', 'body' => '[template /templates/page-home]' ], 'GET://contact' => [ 'uri' => '/contact', 'body' => '[template /templates/page-contact]' ] ];

ninoUser( $site, 'lab@example.com', [ \Nino\Modules\Themelab::PERM ] );
$names = [ 'de_DE' => 'Mein Kopf', 'en_US' => 'My head' ];
$about = [ 'de_DE' => 'Kopf der Website', 'en_US' => 'Head of the site' ];

// ---------------------------------------------------------------------------------------------------------------------------------
echo "\nA. The frame as a block\n";

$frameBefore = [ fbFile( $sandbox. '/private/templates/frame-header.tpl' ), fbShape( $site, 'frame-header' ) ];
$before = fbSnapshot( $site );
[ $status, $made ] = fbCall( $site, 'themelab/frame-adopt', [ 'template' => 'frame-header', 'key' => 'mein-kopf', 'name' => $names, 'description' => $about ] );
$after = fbSnapshot( $site );
unset( $site['./nino/blocks/state'] );
$added = array_keys( array_diff_key( $after, $before ) );
sort( $added );
$meta = json_decode( fbFile( $sandbox. '/private/blocks/mein-kopf/block.json' ), true ) ?: [];
$listed = \Nino\Blocks::all( $site )['mein-kopf'] ?? [];

check( 'a frame is written as a block of the project - the files of an own block, the category of the frames, a name and a description in each language - and the kernel lists it as a block of a header',
	$status === 200 && ( $made['key'] ?? '' ) === 'mein-kopf' && $added === [ '/private/blocks/mein-kopf/block.json', '/private/blocks/mein-kopf/block.tpl', '/private/blocks/mein-kopf/text/de_DE.json', '/private/blocks/mein-kopf/text/en_US.json' ]
	&& ( $meta['category'] ?? '' ) === 'frame' && ( $meta['name']['de_DE'] ?? '' ) === 'Mein Kopf' && ( $meta['name']['en_US'] ?? '' ) === 'My head' && ( $meta['description']['en_US'] ?? '' ) === 'Head of the site'
	&& ( $listed['origin'] ?? '' ) === 'project' && ( $listed['wrap'] ?? '' ) === 'header-wrap' && ( $listed['category'] ?? '' ) === 'frame' );

$tpl = fbFile( $sandbox. '/private/blocks/mein-kopf/block.tpl' );
$texts = [ 'de_DE' => json_decode( fbFile( $sandbox. '/private/blocks/mein-kopf/text/de_DE.json' ), true ), 'en_US' => json_decode( fbFile( $sandbox. '/private/blocks/mein-kopf/text/en_US.json' ), true ) ];

check( 'the words of the frame are the words of the block in each language, the link, the menu and the root stay as the frame has them',
	( $texts['de_DE']['button'] ?? '' ) === 'Schreib uns' && ( $texts['en_US']['button'] ?? '' ) === 'Write to us' && str_contains( $tpl, 'href="page:/contact"' ) === true && str_contains( $tpl, 'menu="main"' ) === true
	&& str_starts_with( $tpl, '[header-wrap id="header"' ) === true );

check( 'the frame of the site is not touched: its file, its words and what stands beside them are those they were - only the folder of the new block is new',
	[ fbFile( $sandbox. '/private/templates/frame-header.tpl' ), fbShape( $site, 'frame-header' ) ] === $frameBefore && array_diff_key( $before, $after ) === [] && array_filter( $added, static fn( string $file ): bool => str_starts_with( $file, '/private/blocks/mein-kopf/' ) === false ) === [] );

// ---------------------------------------------------------------------------------------------------------------------------------
echo "\nB. The way round\n";

ninoUser( $site, 'designer@example.com', [ '/_admin/builder/manage', '/_admin/builder/design/*' ] );
[ $listStatus, $list ] = fbCall( $site, 'builder/list' );
[ $choices, $offer ] = fbCall( $site, 'builder/framechoices', [ 'template' => 'frame-header' ] );
$others = array_values( array_filter( array_column( $offer['blocks'] ?? [], 'key' ), static fn( string $key ): bool => $key !== 'mein-kopf' ) );
$stamp = static function( array &$site ): string {
	[ , $listed ] = fbCall( $site, 'builder/list' );
	return (string) ( array_column( $listed['frames'] ?? [], 'stamp', 'key' )['header'] ?? '' );
};
[ $away ] = fbCall( $site, 'builder/framechoose', [ 'template' => 'frame-header', 'key' => $others[0] ?? 'x', 'base' => $stamp( $site ) ] );
$changed = fbShape( $site, 'frame-header' ) !== $frameBefore[1];
[ $back ] = fbCall( $site, 'builder/framechoose', [ 'template' => 'frame-header', 'key' => 'mein-kopf', 'base' => $stamp( $site ) ] );

$norm = static fn( string $source ): string => (string) preg_replace( '/\s+/', ' ', trim( $source ) );

check( 'the frame replaced by another block, and then by the block that was made of it, is the frame it was - the markup as the kernel reads it and the words in both languages are the same',
	$choices === 200 && in_array( 'mein-kopf', array_column( $offer['blocks'] ?? [], 'key' ), true ) === true && $others !== [] && $away === 200 && $changed === true && $back === 200
	&& $norm( fbShape( $site, 'frame-header' )[0] ) === $norm( $frameBefore[1][0] ) && fbShape( $site, 'frame-header' )[1] === $frameBefore[1][1] );

[ $asPage ] = fbCall( $site, 'builder/tile', [ 'template' => 'page-home', 'key' => 'mein-kopf' ] );

check( 'the block of a frame is offered for a frame and for nothing else: as a block of a page it is not there',
	$asPage === 404 || $asPage === 400 || $asPage === 403 );

// A footer that stands on an id with a number - the id is the frame's, not a copy's: the block has it and the way round gives it back
$footer = "[footer-wrap id=\"footer-2\"]\n\t[footer-col size=\"100 m:50\"]\n\t\t[text ~about format=\"inline\" size=\"s\"]\n\t[/footer-col]\n\t[footer-legal]\n[/footer-wrap]\n";
\Nino\Filesystem::putFileContent( $site, '/templates/frame-footer.tpl', $footer );
\Nino\Filesystem::putFileContent( $site, '/text/de_DE.php', [ '[[/template/frame-header/header/button]]' => 'Schreib uns', '[[/template/frame-footer/footer-2/about]]' => 'Ueber uns' ] );
\Nino\Filesystem::putFileContent( $site, '/text/en_US.php', [ '[[/template/frame-header/header/button]]' => 'Write to us', '[[/template/frame-footer/footer-2/about]]' => 'About us' ] );
$footerBefore = fbShape( $site, 'frame-footer' );
ninoUser( $site, 'lab@example.com', [ \Nino\Modules\Themelab::PERM ] );
[ $footerMade ] = fbCall( $site, 'themelab/frame-adopt', [ 'template' => 'frame-footer', 'key' => 'mein-fuss', 'name' => [ 'de_DE' => 'Mein Fuss', 'en_US' => 'My foot' ], 'description' => [] ] );
unset( $site['./nino/blocks/state'] );
ninoUser( $site, 'designer@example.com', [ '/_admin/builder/manage', '/_admin/builder/design/*' ] );
$footStamp = static function( array &$site ): string {
	[ , $listed ] = fbCall( $site, 'builder/list' );
	return (string) ( array_column( $listed['frames'] ?? [], 'stamp', 'key' )['footer'] ?? '' );
};
[ , $footOffer ] = fbCall( $site, 'builder/framechoices', [ 'template' => 'frame-footer' ] );
$footOthers = array_values( array_filter( array_column( $footOffer['blocks'] ?? [], 'key' ), static fn( string $key ): bool => $key !== 'mein-fuss' ) );
[ $footAway ] = fbCall( $site, 'builder/framechoose', [ 'template' => 'frame-footer', 'key' => $footOthers[0] ?? 'x', 'base' => $footStamp( $site ) ] );
$footChanged = fbShape( $site, 'frame-footer' ) !== $footerBefore;
[ $footBack ] = fbCall( $site, 'builder/framechoose', [ 'template' => 'frame-footer', 'key' => 'mein-fuss', 'base' => $footStamp( $site ) ] );

check( 'a footer is kept and given back the same way, with the id it stands on - footer-2 stays footer-2 - and its words in both languages',
	$footerMade === 200 && str_starts_with( fbFile( $sandbox. '/private/blocks/mein-fuss/block.tpl' ), '[footer-wrap id="footer-2"' ) === true && $footOthers !== [] && $footAway === 200 && $footChanged === true && $footBack === 200
	&& $norm( fbShape( $site, 'frame-footer' )[0] ) === $norm( $footerBefore[0] ) && fbShape( $site, 'frame-footer' )[1] === $footerBefore[1] );

// ---------------------------------------------------------------------------------------------------------------------------------
echo "\nC. What a block may not hold\n";

ninoUser( $site, 'lab@example.com', [ \Nino\Modules\Themelab::PERM ] );
$blocksOf = static function() use ( &$site ): array {
	return array_filter( fbSnapshot( $site ), static fn( string $path ): bool => str_starts_with( $path, '/private/blocks' ), ARRAY_FILTER_USE_KEY );
};
$put = static function( array &$site, string $template, string $source ): void {
	\Nino\Filesystem::putFileContent( $site, '/templates/'. $template. '.tpl', $source );
};
$adopt = static function( array &$site, string $template, string $key, array $more = [] ): array {
	return fbCall( $site, 'themelab/frame-adopt', $more + [ 'template' => $template, 'key' => $key, 'name' => [ 'de_DE' => 'N '. $key, 'en_US' => 'N '. $key ], 'description' => [] ] );
};
/** What a refusal says: the code of each part - [ 'html [html id="x"]', ... ] - from the message, which is the structure; and the sentence for a person */
$partsOf = static function( array $body ): array {
	return str_starts_with( (string) ( $body['error'] ?? '' ), 'the block is not made: ' ) === true ? explode( '; ', substr( (string) $body['error'], strlen( 'the block is not made: ' ) ) ) : [];
};

// More than one part is refused: every part is said at once, each with its kind and the id it has
$put( $site, 'frame-header', str_replace( [ "\t[header-logo]\n", 'href="page:/contact"' ], [ "\t[header-logo]\n\t[html id=\"hi\"]<p>Hi</p>[/html]\n", 'href="/contact"' ], $header ) );
$snapshot = $blocksOf();
[ $htmlStatus, $htmlBody ] = $adopt( $site, 'frame-header', 'mit-html' );
$listed = $partsOf( $htmlBody );

check( 'a frame with more than one thing a block cannot hold is refused at once - 400, one entry for each, the kind of the refusal and the part with the id it has - and nothing is written',
	$htmlStatus === 400 && ( $htmlBody['code'] ?? '' ) === 'builder_frame_parts' && in_array( 'html [html id="hi"]', $listed, true ) === true && in_array( 'address [button]', $listed, true ) === true && count( $listed ) === 2 && $blocksOf() === $snapshot );

$panel = (array) ( include dirname( __DIR__, 2 ). '/Builder/text/'. \Nino\Locales::getNativeLocale( $site ). '.php' );
$sentence = implode( ' ', array_map( 'strval', (array) ( $htmlBody['params'] ?? [] ) ) );

check( 'the sentence of the refusal is in the words of the panel and holds a line for each part: where it is and what is wrong with it - not the first alone',
	str_contains( (string) ( $panel['[[/_admin/error/builder_frame_parts]]'] ?? '' ), '%s' ) === true && str_contains( $sentence, '[html id="hi"]' ) === true && str_contains( $sentence, '[button]' ) === true
	&& str_contains( $sentence, (string) $panel['[[/_admin/error/builder_frame_part_html]]'] ) === true && str_contains( $sentence, (string) $panel['[[/_admin/error/builder_frame_part_address]]'] ) === true );

// A text that a block may not hold is named at its part, and so is a text that is too long and one that is in no language
$put( $site, 'frame-header', $header );
\Nino\Filesystem::putFileContent( $site, '/text/de_DE.php', [ '[[/template/frame-header/header/button]]' => 'Hallo [[/project/company/name]]' ] );
$snapshot = $blocksOf();
[ $textStatus, $textBody ] = $adopt( $site, 'frame-header', 'mit-klammer' );
\Nino\Filesystem::putFileContent( $site, '/text/de_DE.php', [ '[[/template/frame-header/header/button]]' => str_repeat( 'x', \Nino\Blocks::MAX_TEXT + 1 ) ] );
[ $longStatus, $longBody ] = $adopt( $site, 'frame-header', 'zu-lang' );
\Nino\Filesystem::putFileContent( $site, '/text/de_DE.php', [ '[[/template/frame-header/header/button]]' => '' ] );
\Nino\Filesystem::putFileContent( $site, '/text/en_US.php', [ '[[/template/frame-header/header/button]]' => '' ] );
[ $emptyStatus, $emptyBody ] = $adopt( $site, 'frame-header', 'leer' );

check( 'a text with two brackets, a text longer than a block takes and a text in no language are each refused with the part named - 400, and nothing is written',
	$textStatus === 400 && in_array( 'text [button]', $partsOf( $textBody ), true ) === true && $longStatus === 400 && in_array( 'long [button]', $partsOf( $longBody ), true ) === true
	&& $emptyStatus === 400 && in_array( 'empty [button]', $partsOf( $emptyBody ), true ) === true && $blocksOf() === $snapshot );

\Nino\Filesystem::putFileContent( $site, '/text/de_DE.php', [ '[[/template/frame-header/header/button]]' => 'Schreib uns', '[[/template/frame-footer/footer-2/about]]' => 'Ueber uns' ] );
\Nino\Filesystem::putFileContent( $site, '/text/en_US.php', [ '[[/template/frame-header/header/button]]' => 'Write to us', '[[/template/frame-footer/footer-2/about]]' => 'About us' ] );

// What a frame reads of the project is asked of the kernel: the same footer is refused where the kernel refuses a block that reads it, and written where the kernel takes it
$projectFooter = "[footer-wrap id=\"footer\"]\n\t[footer-col size=\"100 m:50\"]\n\t\t[footer-logo /logo]\n\t\t[text /project/company/general/description format=\"inline\" size=\"s\"]\n\t[/footer-col]\n[/footer-wrap]\n";
$probe = static function( array &$site, string $tpl ): bool {
	$parts = [ 'json' => json_encode( [ 'name' => [ 'en_US' => 'P' ], 'description' => [ 'en_US' => 'P' ], 'category' => 'frame', 'rank' => 0 ] ), 'tpl' => $tpl, 'texts' => [ 'en_US' => '{}' ] ];
	return is_string( \Nino\Blocks::validate( $site, 'probe', $parts ) ) === false;
};
$kernelLogo = $probe( $site, "[footer-wrap id=\"p\"]\n\t[footer-col size=\"100\"]\n\t\t[footer-logo /logo]\n\t[/footer-col]\n[/footer-wrap]\n" );
$kernelText = $probe( $site, "[footer-wrap id=\"p\"]\n\t[footer-col size=\"100\"]\n\t\t[text /project/company/general/description format=\"inline\"]\n\t[/footer-col]\n[/footer-wrap]\n" );
$put( $site, 'frame-footer', $projectFooter );
$snapshot = $blocksOf();
[ $footStatus, $footBody ] = $adopt( $site, 'frame-footer', 'fuss-projekt' );
$footParts = $partsOf( $footBody );
$said = [ 'logo' => array_filter( $footParts, static fn( string $part ): bool => str_starts_with( $part, 'source-other [footer-logo' ) ) !== [], 'text' => array_filter( $footParts, static fn( string $part ): bool => str_starts_with( $part, 'source-project [text' ) ) !== [] ];

check( 'what a frame reads of the project - the logo, a text of the company - is the kernel\'s to allow: each is refused, and named, exactly where the kernel refuses a block that reads it; where the kernel takes it the footer is written',
	( $kernelLogo || $kernelText ? $footStatus === 200 || ( $kernelLogo === false || $said['logo'] === false ) : true ) && ( $kernelLogo === true ? $said['logo'] === false : $said['logo'] === true ) && ( $kernelText === true ? $said['text'] === false : $said['text'] === true )
	&& ( $kernelLogo === true && $kernelText === true ? $footStatus === 200 : $footStatus === 400 ) && ( $footStatus === 400 ? $blocksOf() === $snapshot : true ) );

check( 'both parts of the footer are said at once, not one after the other, with the kind of source - the project\'s data for a text of the company - while the kernel refuses them',
	$kernelLogo === true || $kernelText === true || ( count( $footParts ) === 2 && $said['logo'] === true && $said['text'] === true ) );

$put( $site, 'frame-footer', $footer );
\Nino\Filesystem::putFileContent( $site, '/templates/frame-footer.tpl', $footer );

// A picture that the call has by itself is written as the call has it
$logoHeader = str_replace( '[header-logo]', '[header-logo light="/logo"]', $header );
$put( $site, 'frame-header', $logoHeader );
[ $logoStatus ] = $adopt( $site, 'frame-header', 'kopf-logo' );
unset( $site['./nino/blocks/state'] );
$logoTpl = fbFile( $sandbox. '/private/blocks/kopf-logo/block.tpl' );
$put( $site, 'frame-header', $header );
$gone = fbShape( $site, 'frame-header' );
ninoUser( $site, 'designer@example.com', [ '/_admin/builder/manage', '/_admin/builder/design/*' ] );
[ $logoBack ] = fbCall( $site, 'builder/framechoose', [ 'template' => 'frame-header', 'key' => 'kopf-logo', 'base' => $stamp( $site ) ] );
ninoUser( $site, 'lab@example.com', [ \Nino\Modules\Themelab::PERM ] );

check( 'the picture a frame names and that is the one the call has by itself - the logo of the header - is written as the call has it: the block has none, a frame made of it draws the same picture, and the template differs by that one word',
	$logoStatus === 200 && str_contains( $logoTpl, 'light=' ) === false && str_contains( $logoTpl, '[header-logo' ) === true && $logoBack === 200
	&& $norm( fbShape( $site, 'frame-header' )[0] ) === $norm( $gone[0] ) && $norm( str_replace( '[header-logo]', '[header-logo light="/logo"]', fbShape( $site, 'frame-header' )[0] ) ) === $norm( str_replace( '[header-logo light="/logo"]', '[header-logo light="/logo"]', $logoHeader ) ) );

// ---------------------------------------------------------------------------------------------------------------------------------
echo "\nD. Who and what\n";

$snapshot = fbSnapshot( $site );
$asked = static function( array &$site, array $data ): int {
	return fbCall( $site, 'themelab/frame-adopt', $data + [ 'key' => 'ein-kopf', 'name' => [ 'de_DE' => 'Ein Kopf', 'en_US' => 'One head' ], 'description' => [] ] )[0];
};
$refused = [
	'a page for a frame'		=> $asked( $site, [ 'template' => 'page-home' ] ),
	'no template'						=> $asked( $site, [] ),
	'a path for a key'			=> $asked( $site, [ 'template' => 'frame-header', 'key' => '../../x' ] ),
	'a key that is taken'		=> $asked( $site, [ 'template' => 'frame-header', 'key' => 'mein-kopf' ] ),
	'markup in the name'		=> $asked( $site, [ 'template' => 'frame-header', 'name' => [ 'de_DE' => '<b>x</b>', 'en_US' => 'x' ] ] ),
	'a name that is taken'	=> $asked( $site, [ 'template' => 'frame-header', 'name' => [ 'de_DE' => 'Mein Kopf', 'en_US' => 'My head' ] ] ),
];

check( 'what is refused: a template that is no frame - a page, none -, a key that is a path or is taken, markup in a name and a name another block has: 400 or 409 each, and nothing is written',
	array_filter( $refused, static fn( int $status ): bool => in_array( $status, [ 400, 409 ], true ) === false ) === [] && $refused['a key that is taken'] === 409 && fbSnapshot( $site ) === $snapshot );

ninoUser( $site, 'writer@example.com', [ '/_admin/builder/manage', '/_admin/builder/design/*', '/_admin/builder/content/edit' ] );
$noRight = $asked( $site, [ 'template' => 'frame-header' ] );
ninoUser( $site, 'lab@example.com', [ \Nino\Modules\Themelab::PERM ] );

$_POST['data'] = json_encode( [ 'template' => 'frame-header', 'key' => 'ein-kopf', 'name' => [ 'de_DE' => 'Ein Kopf', 'en_US' => 'One head' ], 'locale' => \Nino\Locales::getNativeLocale( $site ) ] );
$_POST['action'] = 'themelab/frame-adopt';
$request = [ '/nino/http/response' => [ 'statusCode' => 403 ] ];
\Nino\Admin\Admin::handlePost( $site, $request );
$untokened = $request['/nino/http/response']['statusCode'];

check( 'an account without the right of this panel is refused, 403, and so is a request without the token of the session: nothing is written, and the Builder has no action of its own for it',
	$noRight === 403 && $untokened === 403 && fbSnapshot( $site ) === $snapshot && isset( \Nino\Modules\Builder\Admin::actions()['builder/frameadopt'] ) === false );

// ---------------------------------------------------------------------------------------------------------------------------------
echo "\nE. The block among the blocks of the project\n";

\Nino\Filesystem::putFileContent( $site, '/templates/frame-header.tpl', $header );
$listAs = static function( array &$site ): array {
	[ $status, $body ] = fbCall( $site, 'themelab/list', [ 'tab' => 'project' ] );
	return [ $status, array_column( $body['blocks'] ?? [], null, 'key' ), $body['counts'] ?? [] ];
};
[ $listStatus, $rows, $counts ] = $listAs( $site );

check( 'the list of the own blocks has the block of a frame - category of the frames, marked as one - and the tab of the theme counts the own blocks of the frames besides the blocks and the frames',
	$listStatus === 200 && ( $rows['mein-kopf']['frame'] ?? false ) === true && ( $rows['mein-kopf']['category'] ?? '' ) === 'frame' && ( $counts['frameBlocks'] ?? 0 ) === count( array_filter( $rows, static fn( array $row ): bool => ( $row['frame'] ?? false ) === true ) ) && ( $counts['frameBlocks'] ?? 0 ) >= 2 && ( $counts['blocks'] ?? -1 ) === 0 );

[ $copied, $copy ] = fbCall( $site, 'themelab/duplicate', [ 'key' => 'mein-kopf', 'name' => [ 'de_DE' => 'Mein Kopf Kopie', 'en_US' => 'My head copy' ] ] );
unset( $site['./nino/blocks/state'] );
$copyKey = (string) ( $copy['key'] ?? '' );
$copyTpl = fbFile( $sandbox. '/private/blocks/'. $copyKey. '/block.tpl' );

check( 'a block of a frame is copied as the files it is - the root keeps the id the frame stands on, the words and the category are the first block\'s - and the copy has the name it was given',
	$copied === 200 && $copyKey !== '' && str_starts_with( $copyTpl, '[header-wrap id="header"' ) === true && fbFile( $sandbox. '/private/blocks/'. $copyKey. '/text/de_DE.json' ) === fbFile( $sandbox. '/private/blocks/mein-kopf/text/de_DE.json' )
	&& ( \Nino\Blocks::all( $site )[$copyKey]['category'] ?? '' ) === 'frame' && ( \Nino\Blocks::all( $site )[$copyKey]['name']['en_US'] ?? '' ) === 'My head copy' );

[ $renamed ] = fbCall( $site, 'themelab/rename', [ 'key' => 'mein-kopf', 'name' => [ 'de_DE' => 'Kopf der Seite', 'en_US' => 'Page head' ], 'description' => [ 'en_US' => 'Page head block' ], 'category' => 'content', 'rank' => 3 ] );
unset( $site['./nino/blocks/state'] );
$after = \Nino\Blocks::all( $site )['mein-kopf'] ?? [];

check( 'the details of the block of a frame are changed in the list - the name, the description, the rank - and its category stays the category of the frames, whatever the request says',
	$renamed === 200 && ( $after['name']['en_US'] ?? '' ) === 'Page head' && ( $after['category'] ?? '' ) === 'frame' && ( $after['rank'] ?? 0 ) === 3 && ( $after['wrap'] ?? '' ) === 'header-wrap' );

// the package of it, taken in by a second site
[ $exported, $bytes ] = fbCall( $site, 'themelab/export', [ 'keys' => [ 'mein-kopf' ], 'key' => 'koepfe', 'version' => '1.0.0', 'name' => 'Koepfe', 'description' => 'Ein Kopf.' ] );
$files = is_string( $bytes ) === true ? \Nino\Archive::read( $bytes, 'koepfe', 400, \Nino\Blocks::MAX_PICTURE, \Nino\Packages::LIMITS[\Nino\Packages::BLOCKS]['bytes'] ) : 'none';

$other = ninoSandbox( 'themelab-frame-block-b' );
$otherDir = ninoSandboxDir( $other );
$other['./nino/filesystem/path'] = $root;
$other['/nino/html/assets'] = [];
$other['/nino/html/navs'] = [ 'main' ];
$other['/nino/modules'] = $site['/nino/modules'];
\Nino\Html::init( $other );
\Nino\Features::registerComponents( $other );
\Nino\Modules::callModules( $other, 'init' );
\Nino\Filesystem::putFileContent( $other, '/templates/frame-header.tpl', "[header-wrap id=\"header\"]\n[/header-wrap]\n" );
\Nino\Filesystem::putFileContent( $other, '/templates/page-contact.tpl', "<!-- nino:template-name Contact -->\n" );
foreach( [ 'de_DE', 'en_US' ] as $language )
	\Nino\Filesystem::putFileContent( $other, '/text/'. $language. '.php', [ '[[/template/page-contact/x]]' => 'x' ] );
$other['/nino/http/routes'] = [ 'GET://contact' => [ 'uri' => '/contact', 'body' => '[template /templates/page-contact]' ] ];
ninoUser( $other, 'lab@example.com', [ \Nino\Modules\Themelab::PERM, '/_admin/builder/manage', '/_admin/builder/design/*' ] );

$tmp = $otherDir. '/upload.tmp';
file_put_contents( $tmp, (string) $bytes );
$_FILES['file'] = [ 'name' => 'koepfe.tar.gz', 'type' => 'application/gzip', 'tmp_name' => $tmp, 'error' => UPLOAD_ERR_OK, 'size' => strlen( (string) $bytes ) ];
[ $checked ] = fbCall( $other, 'themelab/import-check' );
unset( $_FILES['file'] );
$id = (string) ( $_SESSION[\Nino\Modules\Themelab\Package::SESSION]['id'] ?? '' );
[ $imported, $report ] = fbCall( $other, 'themelab/import', [ 'id' => $id, 'items' => [ 'mein-kopf' => [ 'take' => true ] ] ] );
unset( $other['./nino/blocks/state'] );
$taken = \Nino\Blocks::all( $other )['mein-kopf'] ?? [];

check( 'a block of a frame goes out in a package and comes in at a second site as the block it was - in the category of the frames, the root with the id of the frame - and is offered there for the header',
	$exported === 200 && is_array( $files ) === true && isset( $files['mein-kopf/block.tpl'] ) === true && $checked === 200 && $imported === 200 && ( $taken['wrap'] ?? '' ) === 'header-wrap' && ( $taken['category'] ?? '' ) === 'frame'
	&& str_starts_with( fbFile( $otherDir. '/private/blocks/mein-kopf/block.tpl' ), '[header-wrap id="header"' ) === true && in_array( 'mein-kopf', array_column( fbCall( $other, 'builder/framechoices', [ 'template' => 'frame-header' ] )[1]['blocks'] ?? [], 'key' ), true ) === true );

[ $dropped ] = fbCall( $site, 'themelab/remove', [ 'key' => $copyKey ] );
unset( $site['./nino/blocks/state'] );

check( 'the block of a frame is taken away from the list like any block of the project, and the blocks beside it stay',
	$dropped === 200 && isset( \Nino\Blocks::all( $site )[$copyKey] ) === false && isset( \Nino\Blocks::all( $site )['mein-kopf'] ) === true && is_dir( $sandbox. '/private/blocks/'. $copyKey ) === false );


// ---------------------------------------------------------------------------------------------------------------------------------
echo "\nF. The request, the languages and the names\n";

\Nino\Filesystem::putFileContent( $site, '/templates/frame-header.tpl', $header );
\Nino\Filesystem::putFileContent( $site, '/text/de_DE.php', [ '[[/template/frame-header/header/button]]' => 'Schreib uns' ] );
\Nino\Filesystem::putFileContent( $site, '/text/en_US.php', [ '[[/template/frame-header/header/button]]' => 'Write to us' ] );
ninoUser( $site, 'lab@example.com', [ \Nino\Modules\Themelab::PERM ] );
$forged = [ [ 'n' => 'header-wrap', 's' => '', 'a' => [ 'id' => 'header' ], 'c' => [ [ 'n' => 'button', 's' => '~button', 'a' => [ 'href' => 'page:/contact' ], 'c' => null ] ] ] ];
[ $forgedStatus ] = $adopt( $site, 'frame-header', 'erfunden', [ 'tree' => $forged, 'texts' => [ '/template/frame-header/header/button' => [ 'de_DE' => 'Erfunden', 'en_US' => 'Made up' ] ], 'pictures' => [ 'x' => [ 'label' => 'x', 'width' => 10, 'height' => 10 ] ], 'tpl' => '[header-wrap id="x"][/header-wrap]', 'category' => 'content', 'at' => '5' ] );
unset( $site['./nino/blocks/state'] );
$madeUp = fbFile( $sandbox. '/private/blocks/erfunden/block.tpl' );

check( 'the request names nothing of what is in the frame: a tree, words, pictures, a template or a place that it brings are not read - the block is the frame as it is saved, with its words, in the category of the frames',
	$forgedStatus === 200 && $madeUp === fbFile( $sandbox. '/private/blocks/mein-kopf/block.tpl' ) && str_contains( fbFile( $sandbox. '/private/blocks/erfunden/text/de_DE.json' ), 'Schreib uns' ) === true && str_contains( fbFile( $sandbox. '/private/blocks/erfunden/text/en_US.json' ), 'Erfunden' ) === false
	&& ( \Nino\Blocks::all( $site )['erfunden']['category'] ?? '' ) === 'frame' );

// A language that has no text: nothing is made up for it
\Nino\Filesystem::putFileContent( $site, '/text/en_US.php', [ '[[/template/frame-header/header/other]]' => 'x' ] );
$snapshot = $blocksOf();
[ $gapStatus, $gapBody ] = $adopt( $site, 'frame-header', 'luecke' );
[ $askStatus, $ask ] = $adopt( $site, 'frame-header', 'luecke', [ 'check' => true ] );
$untouched = $blocksOf() === $snapshot;
[ $filled, $fill ] = $adopt( $site, 'frame-header', 'luecke', [ 'fill' => 'de_DE' ] );
unset( $site['./nino/blocks/state'] );

check( 'a language without a text for a part is not filled from another by itself: the request is refused, 409, and nothing is written; asked beforehand - check - the answer is which language lacks which part, and nothing is written either',
	$gapStatus === 409 && ( $gapBody['code'] ?? '' ) === 'builder_frame_gaps' && $askStatus === 200 && ( $ask['checked'] ?? false ) === true && ( $ask['gaps'] ?? [] ) === [ [ 'locale' => 'en_US', 'parts' => [ '[button]' ] ] ] && $untouched === true );

check( 'a language is filled from the language that is named - and from no other - when the request says so: the text of the named language is the block\'s for the language that lacked it',
	$filled === 200 && str_contains( fbFile( $sandbox. '/private/blocks/luecke/text/en_US.json' ), 'Schreib uns' ) === true && str_contains( fbFile( $sandbox. '/private/blocks/luecke/text/de_DE.json' ), 'Schreib uns' ) === true );

[ $askedFilled, $askFilled ] = $adopt( $site, 'frame-header', 'ohne-luecke', [ 'check' => true, 'fill' => 'de_DE' ] );

check( 'asked with the language that fills, the answer has no gap left',
	$askedFilled === 200 && ( $askFilled['gaps'] ?? null ) === [] );

// The names: a character that turns a line round or hides in it is no part of a name
\Nino\Filesystem::putFileContent( $site, '/text/en_US.php', [ '[[/template/frame-header/header/button]]' => 'Write to us' ] );
$snapshot = $blocksOf();
$unsafe = [
	'a right-to-left override in a name'		=> $adopt( $site, 'frame-header', 'bidi', [ 'name' => [ 'de_DE' => "Kopf\u{202E}evil", 'en_US' => 'x' ] ] )[0],
	'a zero-width joiner in a name'				=> $adopt( $site, 'frame-header', 'zwj', [ 'name' => [ 'de_DE' => "Ko\u{200D}pf", 'en_US' => 'x' ] ] )[0],
	'a line separator in a description'		=> $adopt( $site, 'frame-header', 'zl', [ 'description' => [ 'de_DE' => "a\u{2028}b" ] ] )[0],
];

check( 'a name or a description with a character that turns the line round, joins or separates lines - the format characters, the line and paragraph separators - is refused, 400, and nothing is written',
	array_filter( $unsafe, static fn( int $status ): bool => $status !== 400 ) === [] && $blocksOf() === $snapshot );

// What the writing refuses of a frame is said as the refusal of a frame
$state = \Nino\Modules\Builder\Store::state( $site, 'frame-header' );
$nodes = \Nino\Modules\Builder\Tree::read( $site, $state['source'] );
$taken = \Nino\Modules\Builder\Custom::save( $site, 'frame-header', $state, $nodes, [ 'at' => '0', 'key' => 'mein-kopf', 'name' => 'Zwei', 'category' => 'frame', 'locale' => 'de_DE' ] );

check( 'the block that is written over a key that is taken says so as a frame does: the code of a frame, 409',
	( $taken['failure'][0] ?? 0 ) === 409 && ( $taken['failure'][2] ?? '' ) === 'builder_frame_taken' );


ninoDone( $site );
