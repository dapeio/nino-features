<?php
declare(strict_types=1);

// The public interface of the Pdf feature to a package that brings a screen of its own: the writers of Store, the four views, the preview, the permission,
// the three callbacks (/pdf/template/uses, /pdf/vocabulary, /pdf/editor) and the day a template was changed. Every pin is a function of its own at the end of
// this file, so that each can be broken on a copy
$root = getenv( 'NINO_ROOT' ) ?: dirname( __DIR__, 3 );
defined( 'NINO_FEATURES_DIR' ) === true || define( 'NINO_FEATURES_DIR', dirname( __DIR__, 2 ) );
require $root. '/tests/harness.php';

use Nino\Modules\Pdf as Pdf;
use Nino\Modules\Pdf\Layout\Tokens;
use Nino\Modules\Pdf\Store;
use Nino\Modules\Pdf\View;

/**
 *	A listener of the callbacks of the feature, as a package would register one: the class is the owner the warnings name, what it answers is set by the test
 */
class PdfProbeOne {

	public static mixed $reply = null;
	public static array $heard = [];

	public static function ask( array &$appData, array &$asked ): mixed {

		self::$heard[] = $asked;

		return is_callable( self::$reply ) === true ? ( self::$reply )( $asked ) : self::$reply;
	}
}

class PdfProbeTwo {

	public static mixed $reply = null;

	public static function ask( array &$appData, array &$asked ): mixed {
		return is_callable( self::$reply ) === true ? ( self::$reply )( $asked ) : self::$reply;
	}
}

// A listener that answers by changing its argument, and returns nothing
class PdfProbeByReference {

	public static array $set = [];

	public static function ask( array &$appData, array &$asked ): void {
		$asked = self::$set + $asked;
	}
}

$appData = ninoSandbox( 'pdf-packages' );
\Nino\AppData::writeContentData( $appData, [ '/nino/modules', '/nino/locales/available', '/nino/locales/native' ] );
\Nino\Features::activate( $appData, 'pdf' );
\Nino\Html::init( $appData );
\Nino\Modules::callModules( $appData, 'init' );
ninoUser( $appData, 'dev@example.com' );
\Nino\Features::saveSettings( $appData, 'pdf', [ 'tags' => "offer\ninvoice" ] );

$post = static function( string $action, array $data = [] ) use ( &$appData ): array {
	return ninoCall( $appData, [ \Nino\Admin\Admin::class, 'handlePost' ], $data, $action );
};
$sentence = static function( string $code, string $locale ) use ( &$appData ): string {
	return (string) ( \Nino\Admin\Admin::textFills( $appData, '/features/Pdf/text', $locale )['[[/_admin/error/'. $code. ']]'] ?? '' );
};

// A pin that throws is a FAIL line, and the next pin runs
foreach( [
	'signatures'	=> static function() { pinSignatures(); },
	'permission'	=> static function() use ( &$appData ) { pinPermission( $appData ); },
	'answers'			=> static function() use ( &$appData ) { pinPanelAnswers( $appData ); },
	'changed'			=> static function() use ( &$appData, $post ) { pinChanged( $appData, $post ); },
	'preview'			=> static function() use ( &$appData, $post ) { pinPreview( $appData, $post ); },
	'uses'				=> static function() use ( &$appData, $post, $sentence ) { pinUses( $appData, $post, $sentence ); },
	'vocabulary'	=> static function() use ( &$appData ) { pinVocabulary( $appData ); },
	'editor'			=> static function() use ( &$appData ) { pinEditor( $appData ); },
	'weight'			=> static function() use ( &$appData ) { pinWeight( $appData ); },
	'ignored'			=> static function() use ( &$appData ) { pinIgnored( $appData ); },
	'grammar'			=> static function() use ( &$appData ) { pinGrammar( $appData ); },
	'refusal'			=> static function() { pinRefusal(); },
] as $name => $pin ) {

	$result = pdfTry( static function() use ( $pin ): mixed { $pin(); return null; } );
	check( 'the pins of "'. $name. '" ran to their end'. ( is_string( $result ) === true ? ' - '. $result : '' ), $result === null );
}

check( 'no warning is left unread: every one a pin provoked, a pin read', ninoWarnings() === [] );

ninoDone( $appData );

// --- pins ---------------------------------------------------------------------------------------------------------------

// What a call answers, or that it threw: a pin that a regression turns to an exception is a FAIL line and not a crash
function pdfTry( callable $call ): mixed {

	try {
		return $call();
	}
	catch( \Throwable $thrown ) {
		return 'threw '. $thrown::class. ': '. $thrown->getMessage();
	}
}

// A method as a package reads it: name( type $name, ... ): type, by reference marked
function pdfSignature( string $class, string $method ): string {

	$reflection = new \ReflectionMethod( $class, $method );
	$parameters = [];

	foreach( $reflection->getParameters() as $parameter )
		$parameters[] = $parameter->getType(). ' '. ( $parameter->isPassedByReference() === true ? '&' : '' ). '$'. $parameter->getName(). ( $parameter->isDefaultValueAvailable() === true ? ' = '. ( $parameter->getDefaultValue() === [] ? '[]' : var_export( $parameter->getDefaultValue(), true ) ) : '' );

	return ( $reflection->isPublic() === true && $reflection->isStatic() === true ? 'public static ' : 'not public static ' ). $method. '( '. implode( ', ', $parameters ). ' ): '. $reflection->getReturnType();
}

/**
 *	The eight writers of Store, the four views and the calls of Pdf a package builds on: their signatures, word for word
 */
function pinSignatures(): void {

	$expected = [
		Store::class => [
			'saveLayout'				=> 'public static saveLayout( array &$appData, array $data ): array',
			'saveImage'					=> 'public static saveImage( array &$appData, string $kind, string $bytes ): array',
			'deleteImage'				=> 'public static deleteImage( array &$appData, string $kind ): array',
			'saveBlock'					=> 'public static saveBlock( array &$appData, array $data ): array',
			'deleteBlock'				=> 'public static deleteBlock( array &$appData, string $key ): array',
			'saveTemplate'			=> 'public static saveTemplate( array &$appData, array $data ): array',
			'duplicateTemplate'	=> 'public static duplicateTemplate( array &$appData, string $key, string $name ): array',
			'deleteTemplate'		=> 'public static deleteTemplate( array &$appData, string $key ): array',
		],
		View::class => [
			'layout'		=> 'public static layout( array &$appData ): array',
			'blocks'		=> 'public static blocks( array &$appData ): array',
			'templates'	=> 'public static templates( array &$appData ): array',
			'offer'			=> 'public static offer( array &$appData ): array',
		],
		Pdf::class => [
			'preview'				=> 'public static preview( array &$appData, array $posted, array $data = [] ): array',
			'previewLayout'	=> 'public static previewLayout( array &$appData, array $data = [] ): array',
			'uses'					=> 'public static uses( array &$appData ): array',
			'vocabulary'		=> 'public static vocabulary( array &$appData ): array',
			'editorAddress'	=> 'public static editorAddress( array &$appData, array $about ): array',
		],
	];
	$differ = [];
	$count = 0;

	foreach( $expected as $class => $methods )
		foreach( $methods as $method => $signature ) {
			$count++;

			if( pdfSignature( $class, $method ) !== $signature )
				$differ[] = $class. '::'. $method;
		}

	check( 'the '. $count. ' calls a package builds on - the eight writers of Store, the four views, preview, previewLayout, uses, vocabulary and editorAddress of Pdf - keep their signatures word for word'. ( $differ === [] ? '' : ' - differ: '. implode( ', ', $differ ) ), $differ === [] && $count === 17 );
	check( 'the writers are documented as the public interface: the docblock of Store names all eight and the class docblock of Pdf names the views, the callbacks and the permission', array_reduce( array_keys( $expected[Store::class] ), static fn( bool $all, string $method ): bool => $all && str_contains( (string) ( new \ReflectionClass( Store::class ) )->getDocComment(), $method. '(' ), true ) === true
		&& array_reduce( [ 'Store', 'View', 'preview(', 'previewLayout(', 'PERM', 'uses(', 'vocabulary(', 'editorAddress(', '/pdf/template/uses', '/pdf/vocabulary', '/pdf/editor' ], static fn( bool $all, string $word ): bool => $all && str_contains( (string) ( new \ReflectionClass( Pdf::class ) )->getDocComment(), $word ), true ) === true );
}

/**
 *	One place for the permission: Pdf::PERM is the panel's, the tabs' and the gate of every action, and no other file of the feature spells it
 */
function pinPermission( array &$appData ): void {

	$tabs = array_map( static fn( string $class ): string => $class::perm(), \Nino\Modules\Pdf\Admin::tabs() );
	$spelled = [];

	foreach( new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( dirname( __DIR__ ), \FilesystemIterator::SKIP_DOTS ) ) as $file )
		if( str_ends_with( (string) $file, '.php' ) === true && str_contains( (string) $file, '/tests/' ) === false && str_contains( (string) file_get_contents( (string) $file ), '/_admin/pdf/manage' ) === true )
			$spelled[] = substr( (string) $file, strlen( dirname( __DIR__ ) ) + 1 );

	check( 'Pdf::PERM is the permission of the panel and of its three tabs, and the one file that spells it is Pdf.php', Pdf::PERM === '/_admin/pdf/manage' && \Nino\Modules\Pdf\Admin::perm() === Pdf::PERM && $tabs === [ Pdf::PERM, Pdf::PERM, Pdf::PERM ] && $spelled === [ 'Pdf.php' ] );

	ninoUser( $appData, 'viewer@example.com', [] );
	$denied = [];

	foreach( array_keys( \Nino\Modules\Pdf\Admin::actions() ) as $action )
		$denied[] = ninoCall( $appData, [ \Nino\Admin\Admin::class, 'handlePost' ], [ 'key' => 'x' ], $action )[0];

	check( 'an account without the permission is refused by each of the '. count( $denied ). ' actions, which all stand on that one constant', count( $denied ) === 14 && array_unique( $denied ) === [ 403 ] );
	ninoUser( $appData, 'dev@example.com' );
}

/**
 *	The panel's answers are what they were before the four assemblers moved to View: the SHA-256 of every body, recorded on the stock before the move, and the views are
 *	the same data
 */
function pinPanelAnswers( array &$appData ): void {

	$recorded = json_decode( (string) file_get_contents( __DIR__. '/fixtures/panel-answers.json' ), true );
	$copy = static function( string $from, string $to ) use ( &$copy ): void {

		mkdir( $to, 0775, true );

		foreach( array_diff( (array) scandir( $from ), [ '.', '..' ] ) as $name )
			is_dir( $from. '/'. $name ) === true ? $copy( $from. '/'. $name, $to. '/'. $name ) : copy( $from. '/'. $name, $to. '/'. $name );
	};
	$answers = [];
	$same = true;

	foreach( [ 'empty', 'stock' ] as $kind ) {

		$stock = ninoSandbox( 'pdf-answers-'. $kind );
		\Nino\AppData::writeContentData( $stock, [ '/nino/modules', '/nino/locales/available', '/nino/locales/native' ] );
		\Nino\Features::activate( $stock, 'pdf' );
		\Nino\Html::init( $stock );
		\Nino\Modules::callModules( $stock, 'init' );
		ninoUser( $stock, 'dev@example.com' );
		\Nino\Features::saveSettings( $stock, 'pdf', [ 'tags' => "offer\ninvoice" ] );

		if( $kind === 'stock' ) {
			$copy( __DIR__. '/fixtures/data/pdf', \Nino\Filesystem::path( $stock, '/data/pdf' ) );
			$copy( __DIR__. '/fixtures/data/pdf-fonts', \Nino\Filesystem::path( $stock, '/data/pdf-fonts' ) );
		}

		$calls = [ [ 'pdf/layout/get' ], [ 'pdf/blocks/list' ], [ 'pdf/templates/list' ], [ 'pdf/templates/get', [ 'key' => 'fx-one' ] ], [ 'pdf/templates/get', [ 'key' => 'seals' ] ], [ 'pdf/templates/get', [ 'key' => 'nope' ] ], [ 'pdf/templates/get', [ 'key' => '123' ] ], [ 'pdf/layout/preview' ] ];

		if( $kind === 'stock' ) {
			$template = Pdf::template( $stock, 'fx-one' );
			$calls[] = [ 'pdf/preview', [ 'name' => $template['name'], 'tag' => '', 'blocks' => $template['blocks'] ] ];
			$calls[] = [ 'pdf/preview', [ 'name' => '', 'tag' => 'zzz', 'blocks' => [] ] ];
			$calls[] = [ 'pdf/preview', [ 'name' => 'Empty', 'tag' => '', 'blocks' => [] ] ];
		}

		foreach( $calls as $call ) {

			$answer = ninoCall( $stock, [ \Nino\Admin\Admin::class, 'handlePost' ], $call[1] ?? [], $call[0] );
			$answers[ $kind. ' '. $call[0]. ' '. json_encode( $call[1] ?? [] ) ] = [ 'status' => $answer[0], 'sha256' => hash( 'sha256', (string) json_encode( $answer[1] ) ), 'length' => strlen( (string) json_encode( $answer[1] ) ) ];

			// The view is the data the panel answered with, key for key
			$view = [ 'pdf/layout/get' => View::layout( $stock ), 'pdf/blocks/list' => View::blocks( $stock ), 'pdf/templates/list' => View::templates( $stock ) ][ $call[0] ] ?? null;
			$same = $same && ( $view === null || json_encode( $view ) === json_encode( $answer[1] ) );
		}

		\Nino\Filesystem::removeDir( ninoSandboxDir( $stock ) );
	}

	$differ = array_keys( array_filter( $answers + $recorded, static fn( mixed $one, string $key ): bool => ( $answers[$key] ?? null ) !== ( $recorded[$key] ?? null ), ARRAY_FILTER_USE_BOTH ) );

	check( 'the answers of the panel are what they were before the four assemblers moved to View: '. count( $recorded ). ' bodies - the layout, the blocks, the templates, the editor of two templates and of two that are none, both previews - have the SHA-256 and the length recorded on the stock, on an empty project and on the stock'
		. ( $differ === [] ? '' : ' - differ: '. implode( ' | ', array_slice( $differ, 0, 4 ) ) ), $differ === [] && count( $recorded ) === 19 && count( $answers ) === 19 );
	check( '...and View::layout(), blocks() and templates() are the very data of those answers', $same === true );
	check( '...View::offer() is what the editor is offered: the tags, the library, the paper and the ranges - and templates() carries it', array_keys( View::offer( $appData ) ) === [ 'tags', 'library', 'paper', 'ranges' ] && array_slice( View::templates( $appData ), 1 ) === View::offer( $appData ) );
}

/**
 *	The day a template was written is in its row
 */
function pinChanged( array &$appData, callable $post ): void {

	$post( 'pdf/templates/save', [ 'key' => 'dated', 'isNew' => true, 'name' => 'Dated', 'tag' => 'offer', 'blocks' => [] ] );
	$path = \Nino\Filesystem::path( $appData, Pdf::TEMPLATES. '/dated.php' );
	touch( $path, 1700000000 );
	clearstatcache();

	$day = ( new \DateTimeImmutable( '@1700000000' ) )->setTimezone( new \DateTimeZone( date_default_timezone_get() ) )->format( 'Y-m-d' );
	$row = Pdf::templates( $appData )['dated'];

	check( 'a row of templates() says the day (Y-m-d) the file of the template was written, and the name and the tag as before', $row === [ 'name' => 'Dated', 'tag' => 'offer', 'changed' => $day ] );

	touch( $path, 1800000000 );
	clearstatcache();

	check( '...it follows the file: a template written on another day has that day', Pdf::templates( $appData, 'offer' )['dated']['changed'] === ( new \DateTimeImmutable( '@1800000000' ) )->setTimezone( new \DateTimeZone( date_default_timezone_get() ) )->format( 'Y-m-d' ) );
	check( '...and the rows the panel lists are the same as before: key, name, tag, blocks and known, no day', array_keys( $post( 'pdf/templates/list' )[1]['templates'][0] ) === [ 'key', 'name', 'tag', 'blocks', 'known' ] );

	// The day is the day in UTC, whatever zone php runs in: 23:30 UTC of the 14th is already the 15th in Auckland
	$zone = date_default_timezone_get();
	date_default_timezone_set( 'Pacific/Auckland' );
	touch( $path, 1700004600 );
	clearstatcache();
	$utc = Pdf::templates( $appData )['dated']['changed'];
	date_default_timezone_set( 'America/Los_Angeles' );
	$west = Pdf::templates( $appData )['dated']['changed'];
	date_default_timezone_set( $zone );
	check( 'the day of a template is the day in UTC: a file written at 23:30 UTC of 2023-11-14 is that day in Auckland and in Los Angeles alike', $utc === '2023-11-14' && $west === '2023-11-14' );

	$day = new \ReflectionMethod( Pdf::class, '_day' );
	check( '...and a file whose time cannot be read has no day, not 1970-01-01', $day->invoke( null, false ) === '' && $day->invoke( null, 0 ) === '1970-01-01' && $day->invoke( null, 1700000000 ) === '2023-11-14' );

	$post( 'pdf/templates/delete', [ 'key' => 'dated' ] );
}

/**
 *	preview() and previewLayout(): the bytes of what is not saved, with a caller's tokens, and nothing written
 */
function pinPreview( array &$appData, callable $post ): void {

	$tree = static function() use ( &$appData ): array {

		$files = [];

		foreach( new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( \Nino\Filesystem::path( $appData, Pdf::DIR ), \FilesystemIterator::SKIP_DOTS ) ) as $file )
			$files[ (string) $file ] = hash( 'sha256', (string) file_get_contents( (string) $file ) );

		ksort( $files );

		return $files;
	};
	$text = static function( string $pdf ): string {

		preg_match_all( '/stream\n(.*?)\nendstream/s', $pdf, $streams );

		foreach( $streams[1] as $stream ) {

			$inflated = @gzuncompress( $stream );

			if( $inflated !== false && str_contains( $inflated, 'Tj' ) === true )
				return $inflated;
		}

		return '';
	};
	$posted = [ 'name' => 'Unsaved', 'tag' => '', 'blocks' => [ [ 'type' => 'html', 'html' => '<p>Hello [[customer.name]] and [[/site/portal]] and [[other]]</p>' ] ] ];
	$before = $tree();
	$answer = Pdf::preview( $appData, $posted, [ 'tokens' => [ 'customer' => [ 'name' => 'Ada <b>' ], '/site/portal' => 'Door [[x]]' ] ] );

	check( 'preview() answers the bytes of a PDF for what an editor posts, with the tokens a caller hands in laid in, a token it has no value for as [name], and the text of a value as text', array_keys( $answer ) === [ 'bytes' ] && str_starts_with( $answer['bytes'], '%PDF-' )
		&& str_contains( $text( $answer['bytes'] ), '(Hello Ada <b> and Door [[x]] and [other])' ) );
	check( '...it is the answer of the panel\'s preview: the same bytes as pdf/preview base64 encoded', base64_decode( (string) $post( 'pdf/preview', $posted )[1]['pdf'], true ) === Pdf::preview( $appData, $posted )['bytes'] );
	check( '...and it writes nothing: the data of the feature is byte for byte what it was', $tree() === $before );

	$refused = Pdf::preview( $appData, [ 'name' => '', 'tag' => 'zzz', 'blocks' => [] ] );
	$block = Pdf::preview( $appData, [ 'name' => 'A', 'tag' => '', 'blocks' => [ [ 'type' => 'html', 'block' => 'nowhere' ] ] ] );
	$oversized = Pdf::preview( $appData, [ 'name' => 'A', 'tag' => '', 'blocks' => array_fill( 0, Pdf::MAX_BLOCKS + 1, [ 'type' => 'pagebreak' ] ) ] );
	$injection = Pdf::preview( $appData, [ 'name' => "A\0", 'tag' => '', 'blocks' => [ [ 'type' => 'html', 'html' => '<script>alert(1)</script><p>x</p>' ] ] ] );

	check( 'a posted template that a save would refuse is [ fail ] with the arguments of Http::fail() and no bytes - a name that is none, a block of the library that is not there, more blocks than allowed', isset( $refused['bytes'] ) === false && $refused['fail'][0] === 400 && is_string( $refused['fail'][2] ?? null ) === true
		&& isset( $block['bytes'] ) === false && $block['fail'][0] === 400 && $block['fail'][2] === 'pdf_block_ref' && isset( $oversized['bytes'] ) === false && $oversized['fail'][0] === 400 );
	$unlisted = Pdf::preview( $appData, [ 'name' => 'A', 'tag' => 'retired', 'blocks' => [] ] );

	check( '...a tag that is a slug but is not on the list of the project does not stop a preview - it is not saved - while a save of the same tag is refused', isset( $unlisted['bytes'] ) === true && Store::saveTemplate( $appData, [ 'key' => 'unlisted-tag', 'isNew' => true, 'name' => 'A', 'tag' => 'retired', 'blocks' => [] ] )['fail'][0] === 400 );
	check( '...a name with a null byte and a script in a block are no way to break it: the bytes are a PDF with the text of the script and not its tag', isset( $injection['fail'] ) === true || ( str_starts_with( $injection['bytes'], '%PDF-' ) === true && str_contains( $text( $injection['bytes'] ), '<script>' ) === false ) );
	check( '...and a data that is not what render() takes - tokens that are no map, a string for slots - is no fault: the template is laid out with none', str_starts_with( Pdf::preview( $appData, $posted, [ 'tokens' => 'x', 'slots' => 5 ] )['bytes'], '%PDF-' ) === true );

	Store::saveLayout( $appData, [ 'format' => 'a4', 'margins' => [ 20, 20, 20, 20 ], 'font' => 'helvetica', 'size' => 10, 'header' => '<p>Head [[who]]</p>', 'footer' => '', 'design' => '' ] );
	$layout = Pdf::previewLayout( $appData, [ 'tokens' => [ 'who' => 'Ada' ] ] );

	check( 'previewLayout() is the bytes of the sample text with the layout as it is saved, a caller\'s tokens in the header, and the panel\'s layout preview is the same bytes', array_keys( $layout ) === [ 'bytes' ] && str_contains( $text( $layout['bytes'] ), '(Head Ada)' )
		&& base64_decode( (string) $post( 'pdf/layout/preview' )[1]['pdf'], true ) === Pdf::previewLayout( $appData )['bytes'] && $tree() !== $before );
	Store::saveLayout( $appData, [ 'format' => 'a4', 'margins' => [ 20, 20, 20, 20 ], 'font' => 'helvetica', 'size' => 10, 'header' => '', 'footer' => '', 'design' => '' ] );
}

/**
 *	The weight of the data of a preview, and what the docblocks of the calls that show the layout say of rights
 */
function pinWeight( array &$appData ): void {

	$posted = [ 'name' => 'Weighed', 'tag' => '', 'blocks' => [ [ 'type' => 'html', 'html' => '<p>[[who]]</p>' ] ] ];
	$over = str_repeat( 'x', Pdf::MAX_DATA );
		$refused = Pdf::preview( $appData, $posted, [ 'tokens' => [ 'who' => $over ] ] );
	$layout = Pdf::previewLayout( $appData, [ 'tokens' => [ 'who' => $over ] ] );

	check( 'the data of a preview is held to '. Pdf::MAX_DATA. ': a text of that length is refused with 413, the code pdf_data_large and the limit in the params, by preview() and by previewLayout()', isset( $refused['bytes'] ) === false && $refused['fail'][0] === 413 && $refused['fail'][2] === 'pdf_data_large' && $refused['fail'][3] === [ Pdf::MAX_DATA ]
		&& isset( $layout['bytes'] ) === false && $layout['fail'][2] === 'pdf_data_large' );
	check( '...many small entries weigh too: 12000 entries of a key and a text are over, and a list with a few is under', isset( Pdf::preview( $appData, $posted, [ 'tokens' => array_fill_keys( array_map( static fn( int $at ): string => 'key-number-'. $at, range( 1, 12000 ) ), 'text of some length' ) ] )['fail'] ) === true
		&& isset( Pdf::preview( $appData, $posted, [ 'tokens' => [ 'who' => 'Ada', 'other' => 'Bob' ] ] )['bytes'] ) === true );
	check( '...the weight counts each entry and each key: 11000 entries of an empty text under short keys are over - 32 for an entry - and so are 6000 keys of 40 characters with nothing in them', isset( Pdf::preview( $appData, $posted, [ 'tokens' => array_fill_keys( array_map( static fn( int $at ): string => 'k'. $at, range( 1, 11000 ) ), '' ) ] )['fail'] ) === true
		&& isset( Pdf::preview( $appData, $posted, [ 'tokens' => array_fill_keys( array_map( static fn( int $at ): string => str_pad( (string) $at, 40, 'k' ), range( 1, 6000 ) ), null ) ] )['fail'] ) === true );
	check( '...a tree deeper than 8 levels is refused, one of 8 is read, and the weight is counted as the tree is read - a refusal needs no copy of it', isset( Pdf::preview( $appData, $posted, [ 'tokens' => array_reduce( range( 1, 9 ), static fn( array $inner ): array => [ 'n' => $inner ], [ 'who' => 'x' ] ) ] )['fail'] ) === true
		&& isset( Pdf::preview( $appData, $posted, [ 'tokens' => array_reduce( range( 1, 6 ), static fn( array $inner ): array => [ 'n' => $inner ], [ 'who' => 'x' ] ) ] )['bytes'] ) === true );
	check( '...and a text a little under the limit is laid out: the limit is the limit', isset( Pdf::preview( $appData, $posted, [ 'tokens' => [ 'who' => str_repeat( 'x', 1000 ) ] ] )['bytes'] ) === true );

	$words = static fn( string $locale ): array => \Nino\Admin\Admin::textFills( $appData, '/features/Pdf/text', $locale );
	check( '...the refusal has a sentence in both languages, and so has the word for a listener that is no class', ( $words( 'en_US' )['[[/_admin/error/pdf_data_large]]'] ?? '' ) !== '' && ( $words( 'de_DE' )['[[/_admin/error/pdf_data_large]]'] ?? '' ) !== '' && $words( 'en_US' )['[[/_admin/error/pdf_data_large]]'] !== $words( 'de_DE' )['[[/_admin/error/pdf_data_large]]']
		&& ( $words( 'en_US' )['[[/_admin/pdf/label/function]]'] ?? '' ) !== ( $words( 'de_DE' )['[[/_admin/pdf/label/function]]'] ?? '' ) );

	// The largest sample a vocabulary may hold goes through a preview as its tokens: the limit is made of the vocabulary's own
	$locale = \Nino\Locales::getAvailableLocales( $appData )[0];
	\Nino\Callbacks::removeCallbacks( $appData, Pdf::VOCABULARY );
	PdfProbeOne::$reply = [ 'sample' => array_combine( array_map( static fn( int $at ): string => 'k'. str_pad( (string) $at, 199, 'k', STR_PAD_LEFT ), range( 1, Pdf::MAX_SAMPLES ) ), array_fill( 0, Pdf::MAX_SAMPLES, str_repeat( 'v', Pdf::MAX_SAMPLE ) ) ) ];
	\Nino\Callbacks::registerCallback( $appData, Pdf::VOCABULARY, [ PdfProbeOne::class, 'ask' ] );
	$biggest = Pdf::vocabulary( $appData )['sample'];
	$through = Pdf::preview( $appData, $posted, [ 'tokens' => $biggest ] );
	$slots = Pdf::preview( $appData, $posted, [ 'tokens' => [ 'who' => 'Ada' ], 'slots' => [ 'rows' => str_repeat( 'x', 3 * Pdf::MAX_DATA ) ], 'inputs' => [ 'note' => str_repeat( 'x', 3 * Pdf::MAX_DATA ) ] ] );
	\Nino\Callbacks::removeCallbacks( $appData, Pdf::VOCABULARY );
	PdfProbeOne::$reply = null;
	check( 'a vocabulary at its own limit - '. Pdf::MAX_SAMPLES. ' samples of a key of 200 characters and a text of '. Pdf::MAX_SAMPLE. ' - is a preview\'s tokens: preview() answers bytes for it, and MAX_DATA is made of those constants', count( $biggest ) === Pdf::MAX_SAMPLES && isset( $through['bytes'] ) === true && Pdf::MAX_DATA === Pdf::MAX_SAMPLES * ( 32 + 200 + Pdf::MAX_SAMPLE ) + 1000 );
	check( '...slots and inputs are not read by a preview and do not weigh: three times the limit in each is no reason to refuse', isset( $slots['bytes'] ) === true && $locale !== '' );

	$said = static fn( string $class, string $method ): string => (string) ( new \ReflectionMethod( $class, $method ) )->getDocComment();
	$all = true;

	foreach( [ $said( Pdf::class, 'preview' ), $said( Pdf::class, 'previewLayout' ), (string) ( new \ReflectionClass( View::class ) )->getDocComment() ] as $doc )
		$all = $all && str_contains( $doc, 'Pdf::PERM' ) === true && preg_match( '/checks? no right|check(s)? a right/', $doc ) === 1;

	check( 'the docblocks of preview(), previewLayout() and View say that none of them checks a right, that the caller checks Pdf::PERM, and what they show of the layout; preview() says $data is held to MAX_DATA',
		$all === true && str_contains( $said( Pdf::class, 'preview' ), 'signature' ) === true && str_contains( $said( Pdf::class, 'previewLayout' ), 'signature' ) === true && str_contains( (string) ( new \ReflectionClass( View::class ) )->getDocComment(), 'signature' ) === true
		&& str_contains( $said( Pdf::class, 'preview' ), 'MAX_DATA' ) === true );
}

/**
 *	The files of the owner's fonts that the layout leaves out are named, at most twenty
 */
function pinIgnored( array &$appData ): void {

	$dir = \Nino\Filesystem::path( $appData, \Nino\Modules\Pdf\Fonts\Metrics::DIR );
	is_dir( $dir ) === true || mkdir( $dir, 0775, true );

	foreach( range( 1, 25 ) as $at )
		file_put_contents( $dir. '/Junk'. sprintf( '%02d', $at ). '-Regular.ttf', 'not a font at all' );

	$layout = View::layout( $appData );

	check( 'of 25 font files that do not read the layout names the first twenty, by file name', count( \Nino\Modules\Pdf\Fonts\Metrics::ignored() ) === 25 && ( $layout['ignored'] ?? [] ) === array_slice( array_keys( \Nino\Modules\Pdf\Fonts\Metrics::ignored() ), 0, 20 ) && count( $layout['ignored'] ) === 20 );

	foreach( glob( $dir. '/Junk*-Regular.ttf' ) ?: [] as $file )
		unlink( $file );

	View::layout( $appData );
	check( '...and with them gone it names none: the key is not in the answer', isset( View::layout( $appData )['ignored'] ) === false );
}

/**
 *	The sentence of a refusal that names a listener by its class wraps anywhere, so that a long class never widens the page
 */
function pinRefusal(): void {

	$css = (string) file_get_contents( dirname( __DIR__ ). '/assets/admin.css' );

	check( 'the stylesheet of the panel lets the sentence of a refusal wrap anywhere: a class of a listener, a word of 80 letters with no place to break, stays inside the page at 390 px', preg_match( '/\.pdf-refusal \.nino-admin-error\s*\{[^}]*overflow-wrap:\s*anywhere/', $css ) === 1 );
}

/**
 *	One grammar of a token: the engine fills what the vocabulary takes, and the pattern stands in one place
 */
function pinGrammar( array &$appData ): void {

	$spelled = [];

	foreach( new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( dirname( __DIR__ ), \FilesystemIterator::SKIP_DOTS ) ) as $file )
		if( str_ends_with( (string) $file, '.php' ) === true && str_contains( (string) $file, '/tests/' ) === false && str_contains( (string) file_get_contents( (string) $file ), 'A-Za-z0-9_.' ) === true )
			$spelled[] = substr( (string) $file, strlen( dirname( __DIR__ ) ) + 1 );

	check( 'the grammar of a token is spelled in one file, Pdf.php, and the engine builds its pattern from Pdf::TOKEN_PART', $spelled === [ 'Pdf.php' ] && str_contains( (string) file_get_contents( dirname( __DIR__ ). '/Layout/Tokens/Tokens.php' ), 'Pdf::TOKEN_PART' ) === true );

	$odd = 'a-b_c.d';
	check( '...and a token of that grammar is filled by the engine as the vocabulary takes it', \Nino\Modules\Pdf\Layout\Tokens::fill( $appData, '[['. $odd. ']]', [ 'a-b_c' => [ 'd' => 'filled' ] ], false ) === 'filled' );
}

/**
 *	uses() and the guard of deleteTemplate()
 */
function pinUses( array &$appData, callable $post, callable $sentence ): void {

	$hook = Pdf::USES;
	$reset = static function() use ( &$appData, $hook ): void {

		\Nino\Callbacks::removeCallbacks( $appData, $hook );
		PdfProbeOne::$reply = null;
		PdfProbeOne::$heard = [];
		PdfProbeTwo::$reply = null;
		PdfProbeByReference::$set = [];
		ninoWarnings();
	};
	$listen = static function( string ...$classes ) use ( &$appData, $hook ): void {

		foreach( $classes as $class )
			\Nino\Callbacks::registerCallback( $appData, $hook, [ $class, 'ask' ] );
	};
	$make = static function( string $key ) use ( $post ): void {
		$post( 'pdf/templates/save', [ 'key' => $key, 'isNew' => true, 'name' => ucfirst( $key ), 'tag' => '', 'blocks' => [] ] );
	};
	$there = static function( string $key ) use ( &$appData ): bool {
		return Pdf::template( $appData, $key ) !== null;
	};

	$reset();
	check( 'with no listener uses() says nothing is used and nothing failed, and a template is deleted as it always was', Pdf::uses( $appData ) === [ 'uses' => [], 'unknown' => [], 'failed' => [] ] && $make( 'lone' ) === null && $post( 'pdf/templates/delete', [ 'key' => 'lone' ] )[0] === 200 && $there( 'lone' ) === false );

	// A listener that counts
	$make( 'used-one' );
	$make( 'free-one' );
	$listen( PdfProbeOne::class );
	PdfProbeOne::$reply = static fn( array $asked ): array => [ 'uses' => [ 'used-one' => [ 'crm-documents' => 3, 'crm-dates' => 1, 'crm-idle' => 0 ] ] ];
	$uses = Pdf::uses( $appData );
	$heard = PdfProbeOne::$heard;
	$panel = $post( 'pdf/templates/delete', [ 'key' => 'used-one' ] );
	$direct = Store::deleteTemplate( $appData, 'used-one' );

	check( 'uses() lists what a listener counts - template, package, number - sorted, without the package that counts nought, and the listener was handed [ uses => [] ]', $uses === [ 'uses' => [ 'used-one' => [ 'crm-dates' => 1, 'crm-documents' => 3 ] ], 'unknown' => [], 'failed' => [] ] && $heard === [ [ 'uses' => [] ] ] );
	check( '...a template with a count above nought is not deleted, by the panel or by Store: 409, the code pdf_template_used, the packages with their numbers in the params, and the file stays', $panel[0] === 409 && $panel[1]['code'] === 'pdf_template_used' && $panel[1]['params'] === [ 'crm-dates (1), crm-documents (3)' ]
		&& $direct['fail'][0] === 409 && $direct['fail'][2] === 'pdf_template_used' && $there( 'used-one' ) === true );
	check( '...the sentence of the code is in both languages and takes the packages: '. $sentence( 'pdf_template_used', 'en_US' ), str_contains( $sentence( 'pdf_template_used', 'en_US' ), '%s' ) === true && str_contains( $sentence( 'pdf_template_used', 'de_DE' ), '%s' ) === true
		&& $sentence( 'pdf_template_used', 'en_US' ) !== $sentence( 'pdf_template_used', 'de_DE' ) && $sentence( 'pdf_template_unknown', 'en_US' ) !== '' && $sentence( 'pdf_template_unknown', 'de_DE' ) !== $sentence( 'pdf_template_unknown', 'en_US' ) );
	check( '...a template nobody counts goes on being deleted, and one that is not there is 404 - the guard does not hide it', $post( 'pdf/templates/delete', [ 'key' => 'free-one' ] )[0] === 200 && $there( 'free-one' ) === false && $post( 'pdf/templates/delete', [ 'key' => 'ghost-one' ] )[0] === 404 );

	// A count that is nought stands for no use
	PdfProbeOne::$reply = [ 'uses' => [ 'used-one' => [ 'crm-documents' => 0 ] ] ];
	check( '...a count of nought is no use: the template is deleted', Pdf::uses( $appData ) === [ 'uses' => [], 'unknown' => [], 'failed' => [] ] && $post( 'pdf/templates/delete', [ 'key' => 'used-one' ] )[0] === 200 );

	// Two listeners
	$make( 'shared' );
	$listen( PdfProbeTwo::class );
	PdfProbeOne::$reply = [ 'uses' => [ 'shared' => [ 'crm-documents' => 3, 'crm-dates' => 2 ], 'other-one' => [ 'crm-dates' => 1 ] ] ];
	PdfProbeTwo::$reply = [ 'uses' => [ 'shared' => [ 'crm-documents' => 4, 'crm-portal' => 5 ] ] ];
	check( 'two listeners that count the same package for the same template are summed per package: 3 and 4 are 7, the packages of each stand, and a template nobody has is listed too', Pdf::uses( $appData ) === [ 'uses' => [ 'other-one' => [ 'crm-dates' => 1 ], 'shared' => [ 'crm-dates' => 2, 'crm-documents' => 7, 'crm-portal' => 5 ] ], 'unknown' => [], 'failed' => [] ] );
	PdfProbeOne::$reply = [ 'uses' => [ 'shared' => [ 'crm-documents' => PHP_INT_MAX ] ] ];
	PdfProbeTwo::$reply = [ 'uses' => [ 'shared' => [ 'crm-documents' => PHP_INT_MAX ] ] ];
	check( '...and a sum that would overflow stays a whole number: the largest', Pdf::uses( $appData )['uses']['shared']['crm-documents'] === PHP_INT_MAX );

	// Each listener has its own argument
	PdfProbeOne::$reply = static function( array &$asked ): array {
		$asked['uses']['shared']['sneaky'] = 9;
		return [ 'uses' => [] ];
	};
	PdfProbeTwo::$reply = static fn( array &$asked ): array => [ 'uses' => $asked['uses'] ];
	check( 'a listener gets an argument of its own: what the first one writes into it is not what the second one is handed', Pdf::uses( $appData ) === [ 'uses' => [], 'unknown' => [], 'failed' => [] ] );

	// By reference, with nothing returned
	$reset();
	$listen( PdfProbeByReference::class );
	PdfProbeByReference::$set = [ 'uses' => [ 'shared' => [ 'crm-dates' => 2 ] ] ];
	check( 'a listener may answer by filling its argument and return nothing', Pdf::uses( $appData ) === [ 'uses' => [ 'shared' => [ 'crm-dates' => 2 ] ], 'unknown' => [], 'failed' => [] ] && $post( 'pdf/templates/delete', [ 'key' => 'shared' ] )[0] === 409 && $there( 'shared' ) === true );

	// Answers that say nothing readable: nothing can be said for the listener, and no template goes
	$structural = [
		'a text for the uses'		=> [ 'uses' => 'shared' ],
		'no uses at all'				=> [ 'other' => [] ],
		'an empty list'					=> [],
		'a text'								=> 'busy',
		'true'									=> true,
		'a number'							=> 5,
		'a list instead of a map'	=> [ 'uses' => [ [ 'crm-documents' => 1 ] ] ],
		'a key in capitals'			=> [ 'uses' => [ 'Shared' => [ 'crm-documents' => 1 ] ] ],
		'a key with a space'		=> [ 'uses' => [ 'shared ' => [ 'crm-documents' => 1 ] ] ],
		'a key of digits'				=> [ 'uses' => [ '123' => [ 'crm-documents' => 2 ] ] ],
		'a key with a path jump'	=> [ 'uses' => [ '../up' => [ 'crm-documents' => 1 ] ] ],
		'a key with a null byte'	=> [ 'uses' => [ "nul\0" => [ 'crm-documents' => 1 ] ] ],
		'a key of 41 letters'		=> [ 'uses' => [ str_repeat( 'a', 41 ) => [ 'crm-documents' => 1 ] ] ],
	];

	foreach( $structural as $what => $reply ) {

		$reset();
		$listen( PdfProbeOne::class );
		PdfProbeOne::$reply = $reply;
		$uses = Pdf::uses( $appData );
		$refused = $post( 'pdf/templates/delete', [ 'key' => 'shared' ] );

		check( 'a listener that answers '. $what. ' is named in a warning and in failed, and no template is deleted: 409 pdf_template_unknown with the listener in the params',
			$uses['failed'] === [ PdfProbeOne::class ] && $uses['unknown'] === [] && $uses['uses'] === [] && $refused[0] === 409 && $refused[1]['code'] === 'pdf_template_unknown' && $refused[1]['params'] === [ PdfProbeOne::class ] && $there( 'shared' ) === true && count( ninoWarnings() ) >= 1 );
	}

	// An entry that is wrong is wrong for its own template: the key is readable
	$make( 'other-one' );
	$entries = [
		'a negative count'			=> [ 'crm-documents' => -1 ],
		'a list as count'				=> [ 'crm-documents' => [ 1 ] ],
		'a float as count'			=> [ 'crm-documents' => 3.0 ],
		'true as count'					=> [ 'crm-documents' => true ],
		'null as count'					=> [ 'crm-documents' => null ],
		'a count with a sign'		=> [ 'crm-documents' => '+3' ],
		'a count with a space'	=> [ 'crm-documents' => ' 3' ],
		'a count with a fraction'	=> [ 'crm-documents' => '3.0' ],
		'a count in exponent form'	=> [ 'crm-documents' => '1e3' ],
		'an empty count'				=> [ 'crm-documents' => '' ],
		'a count of 20 digits'	=> [ 'crm-documents' => '12345678901234567890' ],
		'a package that is digits'	=> [ '2024' => 2 ],
		'a package with a space'	=> [ 'Crm Documents' => 2 ],
		'a list of packages'		=> [ 2, 3 ],
		'a text for packages'		=> 'crm',
	];

	foreach( $entries as $what => $packages ) {

		$reset();
		$listen( PdfProbeOne::class );
		PdfProbeOne::$reply = [ 'uses' => [ 'shared' => $packages, 'other-one' => [ 'crm-dates' => 2 ] ] ];
		$uses = Pdf::uses( $appData );
		$refused = $post( 'pdf/templates/delete', [ 'key' => 'shared' ] );
		$usedOther = $post( 'pdf/templates/delete', [ 'key' => 'other-one' ] );

		check( 'a listener whose entry holds '. $what. ' is unknown for that template alone: named in a warning, under its key in unknown and not in failed - "shared" is 409 pdf_template_unknown, and "other-one", which it counts, stays for the count',
			$uses['unknown'] === [ 'shared' => [ PdfProbeOne::class ] ] && $uses['failed'] === [] && $uses['uses'] === [ 'other-one' => [ 'crm-dates' => 2 ] ] && $refused[0] === 409 && $refused[1]['code'] === 'pdf_template_unknown'
			&& $usedOther[0] === 409 && $usedOther[1]['code'] === 'pdf_template_used' && $there( 'shared' ) === true && count( ninoWarnings() ) >= 1 );
	}

	$reset();
	$listen( PdfProbeOne::class );
	PdfProbeOne::$reply = [ 'uses' => [ 'shared' => [ 'crm-documents' => '3', 'crm-dates' => '007' ], 'other-one' => [ 'crm-dates' => 'x' ] ] ];
	$mixed = Pdf::uses( $appData );
	check( 'a count that is a text of digits alone - what a database gives for COUNT - is a number: "3" is 3 and "007" is 7, and the bad entry of the other template is its own', $mixed['uses'] === [ 'shared' => [ 'crm-dates' => 7, 'crm-documents' => 3 ] ] && $mixed['unknown'] === [ 'other-one' => [ PdfProbeOne::class ] ] && $mixed['failed'] === [] );
	check( '...a template that is free of the bad entry is deleted: a listener that fails for one template does not hold the others', ( function() use ( $post, $there, $make ): bool { $make( 'free-two' ); return $post( 'pdf/templates/delete', [ 'key' => 'free-two' ] )[0] === 200 && $there( 'free-two' ) === false; } )() === true );

	// The refusal is in the log of the panel, with the listener
	$reset();
	$listen( PdfProbeOne::class );
	PdfProbeOne::$reply = 'busy';
	$before = count( \Nino\Modules\Logs\Admin::recentLines( $appData, 1000 ) );
	$post( 'pdf/templates/delete', [ 'key' => 'shared' ] );
	$lines = array_slice( \Nino\Modules\Logs\Admin::recentLines( $appData, 1000 ), 0, 1 );
	check( 'a refusal for what a listener got wrong writes a line to the activity log of the panel that names the template and the class of the listener', count( \Nino\Modules\Logs\Admin::recentLines( $appData, 1000 ) ) === $before + 1 && str_contains( json_encode( $lines ), 'shared' ) === true && str_contains( json_encode( $lines ), 'PdfProbeOne' ) === true );
	PdfProbeOne::$reply = [ 'uses' => [] ];
	$post( 'pdf/templates/delete', [ 'key' => 'shared-none' ] );
	check( '...and a refusal for a count or a template that is not there writes none of its own', count( \Nino\Modules\Logs\Admin::recentLines( $appData, 1000 ) ) === $before + 1 );

	// A listener that is no class is named by the word of the feature in the language of the session
	$reset();
	\Nino\Callbacks::registerCallback( $appData, $hook, static function( array &$appData, array &$asked ): mixed { return 'busy'; } );
	$words = [];

	foreach( [ 'en_US', 'de_DE' ] as $locale ) {

		\Nino\Runtime::setSessionValue( $appData, './admin/locale', $locale );
		$words[$locale] = Pdf::uses( $appData )['failed'];
		$refusal = $post( 'pdf/templates/delete', [ 'key' => 'shared' ] );
		$words[$locale. ' refused'] = $refusal[1]['params'] ?? null;
	}

	\Nino\Runtime::setSessionValue( $appData, './admin/locale', '' );
	ninoWarnings();
	check( 'a listener that is a closure is named by a word of the texts of the feature in the language of the session - "a function", "eine Funktion" - in the failed list and in the params of the refusal, and never by an English word of the code',
		$words['en_US'] === [ 'a function' ] && $words['de_DE'] === [ 'eine Funktion' ] && $words['en_US refused'] === [ 'a function' ] && $words['de_DE refused'] === [ 'eine Funktion' ] );

	// A listener that throws
	foreach( [ 'a RuntimeException' => static fn() => throw new \RuntimeException( 'database away' ), 'a TypeError' => static fn() => ( static fn( int $x ): int => $x )( 'x' ), 'an Error' => static fn() => throw new \Error( 'boom' ) ] as $what => $reply ) {

		$reset();
		$listen( PdfProbeOne::class, PdfProbeTwo::class );
		PdfProbeOne::$reply = $reply;
		PdfProbeTwo::$reply = [ 'uses' => [ 'other-one' => [ 'crm-dates' => 1 ] ] ];
		$uses = pdfTry( static function() use ( &$appData ): mixed { return Pdf::uses( $appData ); } );
		$refused = pdfTry( static function() use ( $post ): mixed { return $post( 'pdf/templates/delete', [ 'key' => 'shared' ] ); } );
		$warnings = ninoWarnings();

		check( 'a listener that throws '. $what. ' is named in a warning, what the other listener counted stands, and nothing is deleted - 409 pdf_template_unknown',
			$uses === [ 'uses' => [ 'other-one' => [ 'crm-dates' => 1 ] ], 'unknown' => [], 'failed' => [ PdfProbeOne::class ] ] && is_array( $refused ) === true && $refused[0] === 409 && $refused[1]['code'] === 'pdf_template_unknown' && $there( 'shared' ) === true
			&& count( array_filter( $warnings, static fn( string $warning ): bool => str_contains( $warning, PdfProbeOne::class ) === true && str_contains( $warning, Pdf::USES ) === true ) ) >= 1 );
	}

	// A count above nought wins over an unknown: the sentence says what is known
	$reset();
	$listen( PdfProbeOne::class, PdfProbeTwo::class );
	PdfProbeOne::$reply = 'busy';
	PdfProbeTwo::$reply = [ 'uses' => [ 'shared' => [ 'crm-dates' => 1 ] ] ];
	check( 'where one listener counts a use and another fails, the use is what is said: 409 pdf_template_used', $post( 'pdf/templates/delete', [ 'key' => 'shared' ] )[1]['code'] === 'pdf_template_used' );

	// A key a template really has, in a map the listener built with a digits-only package
	$make( 'digit-pack' );
	PdfProbeOne::$reply = [ 'uses' => [ 'digit-pack' => [ '2024' => 2 ] ] ];
	check( 'a use of a real template under a package named by digits is no valid entry: that template is unknown and stays', Pdf::uses( $appData )['unknown'] === [ 'digit-pack' => [ PdfProbeOne::class ] ] && $post( 'pdf/templates/delete', [ 'key' => 'digit-pack' ] )[0] === 409 && $there( 'digit-pack' ) === true );

	// A use in the middle of a bulk delete of the panel: every call is its own
	$make( 'bulk-a' );
	$make( 'bulk-b' );
	PdfProbeOne::$reply = [ 'uses' => [ 'bulk-b' => [ 'crm-documents' => 1 ] ] ];
	check( 'each deletion asks on its own: of two templates one that is used stays and the other goes', $post( 'pdf/templates/delete', [ 'key' => 'bulk-a' ] )[0] === 200 && $post( 'pdf/templates/delete', [ 'key' => 'bulk-b' ] )[0] === 409 && $there( 'bulk-b' ) === true );

	$reset();
	foreach( [ 'digit-pack', 'bulk-b', 'other-one', 'shared' ] as $key )
		$post( 'pdf/templates/delete', [ 'key' => $key ] );
}

/**
 *	vocabulary(): what the callers' templates may use, checked, merged
 */
function pinVocabulary( array &$appData ): void {

	$hook = Pdf::VOCABULARY;
	$reset = static function() use ( &$appData, $hook ): void {

		\Nino\Callbacks::removeCallbacks( $appData, $hook );
		PdfProbeOne::$reply = null;
		PdfProbeTwo::$reply = null;
		ninoWarnings();
		\Nino\Callbacks::registerCallback( $appData, $hook, [ PdfProbeOne::class, 'ask' ] );
		\Nino\Callbacks::registerCallback( $appData, $hook, [ PdfProbeTwo::class, 'ask' ] );
	};
	$label = static fn( string $text ): array => [ 'de_DE' => $text. ' (de)', 'en_US' => $text ];

	\Nino\Callbacks::removeCallbacks( $appData, $hook );
	check( 'with no listener vocabulary() says nothing: no group, no slot, no sample', Pdf::vocabulary( $appData ) === [ 'groups' => [], 'slots' => [], 'sample' => [] ] );

	$reset();
	PdfProbeOne::$reply = [
		'groups' => [
			[ 'key' => 'customer', 'label' => $label( 'Customer' ), 'tokens' => [ [ 'token' => 'customer.name', 'label' => $label( 'Name' ) ], [ 'token' => '/site/portal', 'label' => [ 'de_DE' => 'Portal' ] ] ] ],
			[ 'key' => 'invoice', 'label' => $label( 'Invoice' ), 'tokens' => [ [ 'token' => 'invoice-number', 'label' => $label( 'Number' ) ] ] ],
		],
		'slots' => [ [ 'name' => 'lines', 'label' => $label( 'Lines' ) ], [ 'name' => 'totals', 'label' => $label( 'Totals' ) ] ],
		'sample' => [ 'customer.name' => 'Ada Lovelace', '/site/portal' => 'https://example.org/portal', 'invoice-number' => 42, 'rate' => 1.5 ],
	];
	$vocabulary = Pdf::vocabulary( $appData );

	check( 'vocabulary() lists the groups with their tokens, the slots and the sample of a listener, in its order, with the labels as maps of locale to text and the numbers of a sample as text', array_keys( $vocabulary ) === [ 'groups', 'slots', 'sample' ]
		&& array_column( $vocabulary['groups'], 'key' ) === [ 'customer', 'invoice' ] && $vocabulary['groups'][0]['label'] === $label( 'Customer' ) && array_column( $vocabulary['groups'][0]['tokens'], 'token' ) === [ 'customer.name', '/site/portal' ] && $vocabulary['groups'][0]['tokens'][1]['label'] === [ 'de_DE' => 'Portal' ]
		&& $vocabulary['slots'] === [ [ 'name' => 'lines', 'label' => $label( 'Lines' ) ], [ 'name' => 'totals', 'label' => $label( 'Totals' ) ] ]
		&& $vocabulary['sample'] === [ 'customer.name' => 'Ada Lovelace', '/site/portal' => 'https://example.org/portal', 'invoice-number' => '42', 'rate' => '1.5' ] && ninoWarnings() === [] );

	// Markup and brackets in a label are text, kept as they are
	$wild = '<b onclick="x()">Name</b> [[customer.name]] &amp; "\'';
	PdfProbeOne::$reply = [ 'groups' => [ [ 'key' => 'wild', 'label' => $label( $wild ), 'tokens' => [ [ 'token' => 'name', 'label' => $label( $wild ) ] ] ] ], 'slots' => [ [ 'name' => 'rows', 'label' => $label( $wild ) ] ], 'sample' => [ 'name' => $wild ] ];
	$vocabulary = Pdf::vocabulary( $appData );
	$json = json_decode( (string) json_encode( $vocabulary ), true );

	check( 'HTML and [[...]] in a label, a slot name\'s label and a sample are kept as the text they are - not cut, not escaped, not filled - and survive JSON byte for byte, for whoever draws them to write as text', $vocabulary['groups'][0]['label']['en_US'] === $wild && $vocabulary['groups'][0]['tokens'][0]['label']['de_DE'] === $wild. ' (de)'
		&& $vocabulary['slots'][0]['label']['en_US'] === $wild && $vocabulary['sample']['name'] === $wild && $json === $vocabulary );

	// What is invalid falls away, the rest stays
	$long = str_repeat( 'x', Pdf::MAX_LABEL + 1 );
	PdfProbeOne::$reply = [
		'groups' => [
			[ 'key' => 'Bad Group', 'label' => $label( 'Bad' ), 'tokens' => [ [ 'token' => 'ok', 'label' => $label( 'Ok' ) ] ] ],
			[ 'key' => 'nolabel', 'tokens' => [ [ 'token' => 'ok', 'label' => $label( 'Ok' ) ] ] ],
			[ 'key' => 'textlabel', 'label' => 'plain', 'tokens' => [ [ 'token' => 'ok', 'label' => $label( 'Ok' ) ] ] ],
			'not a group',
			[ 'key' => 12, 'label' => $label( 'Twelve' ), 'tokens' => [] ],
			[ 'key' => 'mixed', 'label' => [ 'en_US' => 'Mixed', 'xx' => 'no locale', 'de_DE' => $long, 'fr_FR' => "tab\there", 'it_IT' => "nul\0", 'es_ES' => "bad\xC3\x28", 'pt_PT' => '', 'nl_NL' => [ 'x' ] ], 'tokens' => [
				[ 'token' => 'good', 'label' => $label( 'Good' ) ],
				[ 'token' => 'with space', 'label' => $label( 'No' ) ],
				[ 'token' => '[[wrapped]]', 'label' => $label( 'No' ) ],
				[ 'token' => '1digit', 'label' => $label( 'No' ) ],
				[ 'token' => '/slash key', 'label' => $label( 'No' ) ],
				[ 'token' => '', 'label' => $label( 'No' ) ],
				[ 'token' => 'nolabel' ],
				[ 'token' => 'longlabel', 'label' => [ 'en_US' => $long ] ],
				[ 'token' => 'texty', 'label' => 'Text' ],
				[ 'token' => 7, 'label' => $label( 'No' ) ],
				'plain',
				[ 'token' => 'a.b.c', 'label' => $label( 'Dotted' ) ],
				[ 'token' => '/a/b-c_d.e', 'label' => $label( 'Slashed' ) ],
				[ 'token' => 'good', 'label' => $label( 'Twice' ) ],
			] ],
			[ 'key' => 'empty', 'label' => $label( 'Empty' ), 'tokens' => [ [ 'token' => 'bad token', 'label' => $label( 'No' ) ] ] ],
		],
		'slots' => [ [ 'name' => 'Rows', 'label' => $label( 'No' ) ], [ 'name' => 'ok-slot', 'label' => $label( 'Ok' ) ], [ 'name' => 'nolabel' ], [ 'name' => 5, 'label' => $label( 'No' ) ], 'x', [ 'name' => 'ok-slot', 'label' => $label( 'Again' ) ] ],
		'sample' => [ 'good' => 'fine', 'bad key' => 'no', 7 => 'no', 'listy' => [ 'a' ], 'objecty' => null, 'boolish' => true, 'longy' => $long. str_repeat( 'x', 400 ), 'ctrl' => "a\x01b", 'broken' => "bad\xC3\x28", 'empty' => '', '/ok/slash' => 'yes' ],
	];
	$vocabulary = Pdf::vocabulary( $appData );

	check( 'what is invalid in an answer falls away with what hangs on it and the rest stays: a group with a key that is no slug, with no label, with a label that is a text, a group that is no list, a key that is a number, a group whose tokens are all invalid - no empty group is left',
		array_column( $vocabulary['groups'], 'key' ) === [ 'mixed' ] );
	check( '...a label keeps the locales that are valid - a locale of the shape xx_XX, a text of 1 to '. Pdf::MAX_LABEL. ' characters, UTF-8, no control character - and drops the others', $vocabulary['groups'][0]['label'] === [ 'en_US' => 'Mixed' ] );
	check( '...a token is one that [[...]] reads - a name with dots, a key with slashes - with a label of the same kind, and each only once: "good" is listed once and keeps the first label', array_column( $vocabulary['groups'][0]['tokens'], 'token' ) === [ 'good', 'a.b.c', '/a/b-c_d.e' ] && $vocabulary['groups'][0]['tokens'][0]['label'] === $label( 'Good' ) );
	check( '...a slot needs a slug and a label, and the first of a name stands', $vocabulary['slots'] === [ [ 'name' => 'ok-slot', 'label' => $label( 'Ok' ) ] ] );
	check( '...a sample is a token with a text or a number of at most '. Pdf::MAX_SAMPLE. ' characters, valid, with no control character - a list, null, a boolean, a text that is too long, with a control character or not UTF-8 are left out, an empty text is a text', $vocabulary['sample'] === [ 'good' => 'fine', 'empty' => '', '/ok/slash' => 'yes' ] );

	// The grammar is the one the engine fills
	$accepted = array_merge( ...array_map( static fn( array $group ): array => array_column( $group['tokens'], 'token' ), $vocabulary['groups'] ) );
	$grammar = true;

	foreach( array_merge( $accepted, [ 'name', 'customer.name', '/site/portal', 'a-b_c.d' ] ) as $token )
		$grammar = $grammar && preg_match( Pdf::TOKEN, $token ) === 1 && Tokens::fill( $appData, '[['. $token. ']]', [], true ) !== '[['. $token. ']]';

	foreach( [ 'with space', '1digit', '/slash key', '', 'a..b', '.a', 'a.', '/' ] as $token )
		$grammar = $grammar && ( preg_match( Pdf::TOKEN, $token ) === 0 ) && Tokens::fill( $appData, '[['. $token. ']]', [], true ) === '[['. $token. ']]';

	check( 'a token the vocabulary takes is one the engine fills ([[...]] becomes the sample [name]), and one it refuses is one the engine leaves standing', $grammar === true );

	// Two listeners add up
	$reset();
	PdfProbeOne::$reply = [ 'groups' => [ [ 'key' => 'site', 'label' => $label( 'Site' ), 'tokens' => [ [ 'token' => '/site/login', 'label' => $label( 'Login' ) ] ] ] ], 'slots' => [ [ 'name' => 'lines', 'label' => $label( 'Lines' ) ] ], 'sample' => [ '/site/login' => 'one' ] ];
	PdfProbeTwo::$reply = [ 'groups' => [ [ 'key' => 'site', 'label' => $label( 'Other site' ), 'tokens' => [ [ 'token' => '/site/portal', 'label' => $label( 'Portal' ) ], [ 'token' => '/site/login', 'label' => $label( 'Again' ) ] ] ], [ 'key' => 'extra', 'label' => $label( 'Extra' ), 'tokens' => [ [ 'token' => 'x1', 'label' => $label( 'X' ) ] ] ] ], 'slots' => [ [ 'name' => 'lines', 'label' => $label( 'Other lines' ) ], [ 'name' => 'totals', 'label' => $label( 'Totals' ) ] ], 'sample' => [ '/site/login' => 'two', 'x1' => 'three' ] ];
	$vocabulary = Pdf::vocabulary( $appData );

	check( 'two listeners add up: a group of the same key is one - the label of the first, the tokens of both, a token that is taken is left out -, slots and samples of a name that is taken stay as the first said them',
		array_column( $vocabulary['groups'], 'key' ) === [ 'site', 'extra' ] && $vocabulary['groups'][0]['label'] === $label( 'Site' ) && $vocabulary['groups'][0]['tokens'] === [ [ 'token' => '/site/login', 'label' => $label( 'Login' ) ], [ 'token' => '/site/portal', 'label' => $label( 'Portal' ) ] ]
		&& $vocabulary['slots'] === [ [ 'name' => 'lines', 'label' => $label( 'Lines' ) ], [ 'name' => 'totals', 'label' => $label( 'Totals' ) ] ] && $vocabulary['sample'] === [ '/site/login' => 'one', 'x1' => 'three' ] );

	// A listener that fails does not take the others with it
	PdfProbeOne::$reply = static fn() => throw new \RuntimeException( 'gone' );
	$vocabulary = pdfTry( static function() use ( &$appData ): mixed { return Pdf::vocabulary( $appData ); } );
	$warnings = ninoWarnings();

	check( 'a listener that throws is named in a warning and its answer is left out, the other one stands', is_array( $vocabulary ) === true && array_column( $vocabulary['groups'], 'key' ) === [ 'site', 'extra' ] && count( array_filter( $warnings, static fn( string $warning ): bool => str_contains( $warning, PdfProbeOne::class ) === true ) ) === 1 );

	foreach( [ 'a text' => 'x', 'a number' => 5, 'true' => true, 'groups as a text' => [ 'groups' => 'x', 'slots' => 5, 'sample' => 'y' ] ] as $what => $reply ) {

		PdfProbeOne::$reply = $reply;
		PdfProbeTwo::$reply = null;
		check( 'an answer that is '. $what. ' leaves nothing and breaks nothing', Pdf::vocabulary( $appData ) === [ 'groups' => [], 'slots' => [], 'sample' => [] ] );
	}

	// The numbers
	$reset();
	PdfProbeOne::$reply = [
		'groups' => array_map( static fn( int $at ): array => [ 'key' => 'group-'. $at, 'label' => [ 'en_US' => 'G' ], 'tokens' => [ [ 'token' => 'tok'. $at, 'label' => [ 'en_US' => 'T' ] ] ] ], range( 1, Pdf::MAX_GROUPS + 5 ) ),
		'slots' => array_map( static fn( int $at ): array => [ 'name' => 'slot-'. $at, 'label' => [ 'en_US' => 'S' ] ], range( 1, Pdf::MAX_SLOTS + 5 ) ),
		'sample' => array_combine( array_map( static fn( int $at ): string => 'smp'. $at, range( 1, Pdf::MAX_SAMPLES + 5 ) ), array_fill( 0, Pdf::MAX_SAMPLES + 5, 'v' ) ),
	];
	$vocabulary = Pdf::vocabulary( $appData );
	PdfProbeOne::$reply = [ 'groups' => [ [ 'key' => 'big', 'label' => [ 'en_US' => 'B' ], 'tokens' => array_map( static fn( int $at ): array => [ 'token' => 'big'. $at, 'label' => [ 'en_US' => 'T' ] ], range( 1, Pdf::MAX_GROUP_TOKENS + 7 ) ) ] ] ];

	check( 'an answer is held to its numbers: at most '. Pdf::MAX_GROUPS. ' groups, '. Pdf::MAX_SLOTS. ' slots, '. Pdf::MAX_SAMPLES. ' samples and '. Pdf::MAX_GROUP_TOKENS. ' tokens to a group', count( $vocabulary['groups'] ) === Pdf::MAX_GROUPS && count( $vocabulary['slots'] ) === Pdf::MAX_SLOTS && count( $vocabulary['sample'] ) === Pdf::MAX_SAMPLES
		&& count( Pdf::vocabulary( $appData )['groups'][0]['tokens'] ) === Pdf::MAX_GROUP_TOKENS );

	// The locales of a label are the locales of the site
	$reset();
	$sitelocales = \Nino\Locales::getAvailableLocales( $appData );
	PdfProbeOne::$reply = [ 'groups' => [ [ 'key' => 'lang', 'label' => [ 'fr_FR' => 'Français', 'en_US' => 'English', 'xx_XX' => 'Nowhere' ], 'tokens' => [ [ 'token' => 'only-fr', 'label' => [ 'fr_FR' => 'Seulement' ] ], [ 'token' => 'both', 'label' => [ 'de_DE' => 'Beide', 'fr_FR' => 'Les deux' ] ] ] ] ] ];
	$lang = Pdf::vocabulary( $appData );
	check( 'a label keeps the locales the site has ('. implode( ', ', $sitelocales ). ') and drops the others, however well formed: fr_FR and xx_XX go, and a token whose label has no locale of the site is no token', $lang['groups'][0]['label'] === [ 'en_US' => 'English' ] && $lang['groups'][0]['tokens'] === [ [ 'token' => 'both', 'label' => [ 'de_DE' => 'Beide' ] ] ] && in_array( 'fr_FR', $sitelocales, true ) === false );

	// The length of a token and of a key of the sample
	$name200 = str_repeat( 'a', 200 );
	$name201 = str_repeat( 'a', 201 );
	$slash200 = '/'. str_repeat( 'b', 199 );
	$slash201 = '/'. str_repeat( 'b', 200 );
	PdfProbeOne::$reply = [ 'groups' => [ [ 'key' => 'long', 'label' => $label( 'Long' ), 'tokens' => [ [ 'token' => $name201, 'label' => $label( 'x' ) ], [ 'token' => $name200, 'label' => $label( 'x' ) ], [ 'token' => $slash201, 'label' => $label( 'x' ) ], [ 'token' => $slash200, 'label' => $label( 'x' ) ] ] ] ], 'sample' => [ $name201 => 'no', $name200 => 'yes', $slash201 => 'no', $slash200 => 'yes' ] ];
	$long = Pdf::vocabulary( $appData );
	check( 'a token and a key of the sample are of at most 200 characters, as a name and as a key with slashes: 201 go, 200 stay', array_column( $long['groups'][0]['tokens'], 'token' ) === [ $name200, $slash200 ] && array_keys( $long['sample'] ) === [ $name200, $slash200 ] );

	// The numbers hold where an answer is read: what lies beyond them is not looked at
	$bad = static fn( int $count, array $one ): array => array_merge( array_fill( 0, $count, 'junk' ), [ $one ] );
	PdfProbeOne::$reply = [ 'groups' => $bad( Pdf::MAX_GROUPS, [ 'key' => 'late', 'label' => $label( 'L' ), 'tokens' => [ [ 'token' => 'x1', 'label' => $label( 'x' ) ] ] ] ), 'slots' => $bad( Pdf::MAX_SLOTS, [ 'name' => 'late', 'label' => $label( 'L' ) ] ), 'sample' => array_merge( array_fill_keys( array_map( static fn( int $at ): string => 'bad key '. $at, range( 1, Pdf::MAX_SAMPLES ) ), 'v' ), [ 'late' => 'v' ] ) ];
	check( 'an answer is read up to the number of each list and no further: valid entries behind that many entries - junk, here - are not looked at, in the groups, the slots and the sample', Pdf::vocabulary( $appData ) === [ 'groups' => [], 'slots' => [], 'sample' => [] ] );
	PdfProbeOne::$reply = [ 'groups' => [ [ 'key' => 'tokens', 'label' => $label( 'T' ), 'tokens' => $bad( Pdf::MAX_GROUP_TOKENS, [ 'token' => 'late', 'label' => $label( 'L' ) ] ) ] ] ];
	check( '...and so are the tokens of a group', Pdf::vocabulary( $appData ) === [ 'groups' => [], 'slots' => [], 'sample' => [] ] );
	// A label is looked up by the languages of the site, wherever it lists them
	$full = static fn( string $text ): array => [ 'fr_FR' => $text. ' fr', 'en_US' => $text. ' en', 'de_DE' => $text. ' de' ];
	$ofSite = static function( array $site ) use ( &$appData ): array {
		$appData['/nino/locales/available'] = $site;
		return Pdf::vocabulary( $appData );
	};
	$home = $appData['/nino/locales/available'];
	PdfProbeOne::$reply = [ 'groups' => [ [ 'key' => 'mapped', 'label' => $full( 'Group' ), 'tokens' => [ [ 'token' => 'ok', 'label' => $full( 'Ok' ) ], [ 'token' => 'only-fr', 'label' => [ 'fr_FR' => 'Seulement' ] ], [ 'token' => 'also-ok', 'label' => $full( 'Also' ) ] ] ] ], 'slots' => [ [ 'name' => 'rows', 'label' => $full( 'Rows' ) ] ] ];
	$german = $ofSite( [ 'de_DE' ] );
	$english = $ofSite( [ 'en_US' ] );
	$both = $ofSite( [ 'de_DE', 'en_US' ] );
	$appData['/nino/locales/available'] = $home;
	check( 'a site with one language reads a label that lists en_US first and de_DE after it: the language of the site is found wherever the label has it - de_DE only on a German site, en_US only on an English one',
		$german['groups'][0]['label'] === [ 'de_DE' => 'Group de' ] && $english['groups'][0]['label'] === [ 'en_US' => 'Group en' ] && $german['slots'][0]['label'] === [ 'de_DE' => 'Rows de' ] );
	check( '...a site of two languages gets both of a label that lists fr_FR, en_US and de_DE, in the order of the site', array_keys( $both['groups'][0]['label'] ) === [ 'de_DE', 'en_US' ] && $both['groups'][0]['label']['de_DE'] === 'Group de' );
	check( '...a token whose label has no language of the site is the one entry that falls, and the group stands with the others', array_column( $german['groups'][0]['tokens'], 'token' ) === [ 'ok', 'also-ok' ] && array_column( $both['groups'][0]['tokens'], 'token' ) === [ 'ok', 'also-ok' ] );
	check( '...and a group whose own label has no language of the site is dropped, tokens and all', $ofSite( [ 'it_IT' ] ) === [ 'groups' => [], 'slots' => [], 'sample' => [] ] && ( $appData['/nino/locales/available'] = $home ) === $home );
	$wide = array_merge( array_fill_keys( array_map( static fn( int $at ): string => 'a'. chr( 97 + $at % 26 ). '_'. chr( 65 + $at % 26 ). 'Q', range( 0, 300 ) ), 'x' ), [ 'de_DE' => 'Late' ] );
	PdfProbeOne::$reply = [ 'groups' => [ [ 'key' => 'wide', 'label' => $wide, 'tokens' => [ [ 'token' => 'ok', 'label' => $wide ] ] ] ] ];
	check( '...however many languages a label lists, the one the site has is found behind them', Pdf::vocabulary( $appData )['groups'][0]['label'] === [ 'de_DE' => 'Late' ] );

	// A group is held to its number of tokens whoever gives them
	$tokens = static fn( string $prefix, int $count ): array => array_map( static fn( int $at ): array => [ 'token' => $prefix. $at, 'label' => [ 'en_US' => 'T' ] ], range( 1, $count ) );
	PdfProbeOne::$reply = [ 'groups' => [ [ 'key' => 'shared', 'label' => $label( 'S' ), 'tokens' => $tokens( 'one', 60 ) ] ] ];
	PdfProbeTwo::$reply = [ 'groups' => [ [ 'key' => 'shared', 'label' => $label( 'S' ), 'tokens' => $tokens( 'two', 60 ) ] ] ];
	$shared = Pdf::vocabulary( $appData )['groups'][0]['tokens'];
	check( 'a group has at most '. Pdf::MAX_GROUP_TOKENS. ' tokens over two listeners: 60 and 60 are 100, the first listener\'s 60 and the first 40 of the second\'s', count( $shared ) === Pdf::MAX_GROUP_TOKENS && $shared[59]['token'] === 'one60' && $shared[60]['token'] === 'two1' && $shared[99]['token'] === 'two40' );

	\Nino\Callbacks::removeCallbacks( $appData, $hook );
	ninoWarnings();
}

/**
 *	editorAddress(): the address of the panel, and what a listener may put in its place
 */
function pinEditor( array &$appData ): void {

	$hook = Pdf::EDITOR;
	$reset = static function() use ( &$appData, $hook ): void {

		\Nino\Callbacks::removeCallbacks( $appData, $hook );
		PdfProbeOne::$reply = null;
		PdfProbeOne::$heard = [];
		PdfProbeTwo::$reply = null;
		ninoWarnings();
	};
	$listen = static function( string ...$classes ) use ( &$appData, $hook ): void {

		foreach( $classes as $class )
			\Nino\Callbacks::registerCallback( $appData, $hook, [ $class, 'ask' ] );
	};
	$of = static function( array $about ) use ( &$appData ): array {
		return Pdf::editorAddress( $appData, $about );
	};

	$reset();
	$base = \Nino\Filesystem::getDir( $appData ). '/_admin';
	check( 'with no listener the address is the one of the panel of the workbench, written by AdminAddress - a template: its record, a block: its record, the list of templates, the layout and the design: the panel\'s start, the pictures: their tab - and it is not inside',
		$of( [ 'surface' => 'crm', 'part' => 'templates', 'key' => 'offer-one' ] ) === [ 'href' => $base. '#pdf/templates/offer-one', 'inside' => false ]
		&& $of( [ 'surface' => 'crm', 'part' => 'blocks', 'key' => 'intro' ] ) === [ 'href' => $base. '#pdf/blocks/intro', 'inside' => false ] && $of( [ 'surface' => 'crm', 'part' => 'templates' ] )['href'] === $base. '#pdf/templates'
		&& $of( [ 'part' => 'layout' ] )['href'] === $base. '#pdf' && $of( [ 'part' => 'design' ] )['href'] === $base. '#pdf' && $of( [ 'part' => 'pictures' ] )['href'] === $base. '#pdf/pictures' && $of( [] ) === [ 'href' => $base. '#pdf', 'inside' => false ] );
	check( '...the key is a key and a part of the address: a key that is no slug and a part of the layout are left out, a tag only goes to the listeners, and what is no text is none',
		$of( [ 'part' => 'templates', 'key' => '../up' ] )['href'] === $base. '#pdf/templates' && $of( [ 'part' => 'layout', 'key' => 'offer-one' ] )['href'] === $base. '#pdf' && $of( [ 'part' => 'templates', 'key' => 'offer-one', 'tag' => 'offer' ] )['href'] === $base. '#pdf/templates/offer-one'
		&& $of( [ 'part' => [ 'x' ], 'key' => 5, 'surface' => null ] )['href'] === $base. '#pdf' && $of( [ 'part' => 'templates', 'key' => "offer-one\0" ] )['href'] === $base. '#pdf/templates' );

	// What a listener is told, and what it may say
	$listen( PdfProbeOne::class );
	PdfProbeOne::$reply = [ 'href' => '/crm#pdf-template-offer-one', 'inside' => true ];
	$answer = $of( [ 'surface' => 'crm', 'part' => 'templates', 'key' => 'offer-one', 'tag' => 'offer', 'extra' => 'x' ] );

	check( 'a listener is told surface, part, key, tag and the address of the panel with inside false, and nothing else; it may answer a path and inside', $answer === [ 'href' => '/crm#pdf-template-offer-one', 'inside' => true ]
		&& PdfProbeOne::$heard === [ [ 'surface' => 'crm', 'part' => 'templates', 'key' => 'offer-one', 'tag' => 'offer', 'href' => $base. '#pdf/templates/offer-one', 'inside' => false ] ] && ninoWarnings() === [] );

	foreach( [ '#pdf-template-x', '/crm', '/a/b?c=d#e', '#', '/crm#pdf-new-template-offer', '/p%20q', '/a-b_c.d~e!f$g&h(i)j*k+l,m;n=o:p@q/r?s=t#u%20' ] as $good ) {

		PdfProbeOne::$reply = [ 'href' => $good ];
		check( 'an address of this site is taken: '. $good. ' - and inside is false where the listener does not say', $of( [ 'part' => 'templates' ] ) === [ 'href' => $good, 'inside' => false ] && ninoWarnings() === [] );
	}

	$bad = [
		'a script'							=> 'javascript:alert(1)',
		'a script in capitals'	=> 'JaVaScRiPt:alert(1)',
		'a script after a space'	=> ' javascript:alert(1)',
		'another host'					=> 'https://evil.example/pdf',
		'another host, plain'		=> 'http://evil.example/',
		'a host without a scheme'	=> '//evil.example/pdf',
		'a host with a backslash'	=> '/\\evil.example',
		'a backslash path'			=> '\\\\evil.example\\x',
		'a data address'				=> 'data:text/html,<script>alert(1)</script>',
		'a path with a space'		=> '/crm #pdf',
		'a path with a line feed'	=> "/crm\n#pdf",
		'a path with a null byte'	=> "/crm\0",
		'a path with a tab'			=> "/crm\t",
		'a backslash in a path'	=> '/crm\\evil.example',
		'a backslash in a hash'	=> '#pdf\\x',
		'a path with a delete'	=> "/crm\x7F",
		'a relative path'				=> 'crm#pdf',
		'an empty address'			=> '',
		'a path too long'				=> '/'. str_repeat( 'a', Pdf::MAX_ADDRESS ),
		'a list'								=> [ '/crm' ],
		'a number'							=> 5,
		'a double quote'				=> '/crm"onload="x',
		'a single quote'				=> "/crm'onload='x",
		'a less-than sign'			=> '/crm<script>',
		'a greater-than sign'		=> '/crm>',
		'a backtick'						=> '/crm`x`',
		'a bracket'							=> '/crm[0]',
		'a brace'								=> '/crm{0}',
		'a bar'									=> '/crm|x',
		'a caret'								=> '/crm^x',
		'a letter beyond ASCII'	=> '/crmä',
	];

	foreach( $bad as $what => $href ) {

		$reset();
		$listen( PdfProbeOne::class );
		PdfProbeOne::$reply = [ 'href' => $href, 'inside' => true ];
		$answer = $of( [ 'surface' => 'crm', 'part' => 'templates', 'key' => 'offer-one' ] );

		check( 'a listener that answers '. $what. ' is named in a warning and the address of the panel stands', $answer === [ 'href' => $base. '#pdf/templates/offer-one', 'inside' => false ] && count( ninoWarnings() ) === 1 );
	}

	// A listener that says nothing, or only inside, hides nobody
	$reset();
	$listen( PdfProbeOne::class, PdfProbeTwo::class );
	PdfProbeOne::$reply = null;
	PdfProbeTwo::$reply = [ 'href' => '#active', 'inside' => true ];
	$passive = $of( [ 'part' => 'templates' ] );
	PdfProbeOne::$reply = static function( array &$asked ): void {};
	$unchanged = $of( [ 'part' => 'templates' ] );
	PdfProbeOne::$reply = static function( array &$asked ): void { $asked['inside'] = true; };
	$insideAlone = $of( [ 'part' => 'templates' ] );
	PdfProbeOne::$reply = [ 'inside' => true ];
	$insideReturned = $of( [ 'part' => 'templates' ] );
	PdfProbeOne::$reply = [ 'href' => null ];
	$nullHref = $of( [ 'part' => 'templates' ] );
	PdfProbeOne::$reply = static function( array &$asked ): void { $asked['href'] = '#changed'; };
	$changed = $of( [ 'part' => 'templates' ] );
	check( 'a listener that answers nothing - null, with its argument unchanged - has no answer and hides no later one: the active listener after it decides, and no warning is written', $passive === [ 'href' => '#active', 'inside' => true ] && $unchanged === $passive && ninoWarnings() === [] );
	check( '...nor does one that sets inside alone, by the argument or in an array, or an href that is null: no address of its own is no answer - while an href that it changed in the argument is one, and the first answer decides', $insideAlone === $passive && $insideReturned === $passive && $nullHref === $passive && ninoWarnings() === [] && $changed === [ 'href' => '#changed', 'inside' => false ] );

	// The default address below a directory the project is served from
	$appData['/nino/dir'] = '/shop';
	\Nino\Callbacks::removeCallbacks( $appData, $hook );
	check( 'the address of the panel stands below the directory the project is served from: /shop/_admin#pdf/templates/offer-one', $of( [ 'part' => 'templates', 'key' => 'offer-one' ] )['href'] === '/shop/_admin#pdf/templates/offer-one' );
	$appData['/nino/dir'] = '/shop/';
	check( '...with one slash where the directory ends in one', $of( [ 'part' => 'blocks', 'key' => 'intro' ] )['href'] === '/shop/_admin#pdf/blocks/intro' );
	$appData['/nino/dir'] = '';

	// The texts of $about are held to 200 characters
	$long = str_repeat( 'a', 201 );
	$edge = str_repeat( 'a', 200 );
	PdfProbeOne::$heard = [];
	$reset();
	$listen( PdfProbeOne::class );
	PdfProbeOne::$reply = null;
	$of( [ 'surface' => $long, 'part' => 'templates', 'key' => 'offer-one', 'tag' => $long ] );
	$of( [ 'surface' => $edge, 'tag' => $edge ] );
	check( 'a text of $about of 201 characters is none, as an empty text, and one of 200 stays', PdfProbeOne::$heard[0]['surface'] === '' && PdfProbeOne::$heard[0]['tag'] === '' && PdfProbeOne::$heard[0]['key'] === 'offer-one' && PdfProbeOne::$heard[1]['surface'] === $edge && PdfProbeOne::$heard[1]['tag'] === $edge );

	$reset();
	$listen( PdfProbeOne::class );
	PdfProbeOne::$reply = [ 'href' => '/crm#x', 'inside' => 'yes' ];
	check( 'an inside that is no boolean makes the answer none', $of( [ 'part' => 'templates' ] )['href'] === $base. '#pdf/templates' && count( ninoWarnings() ) === 1 );

	$reset();
	$listen( PdfProbeOne::class, PdfProbeTwo::class );
	PdfProbeOne::$reply = [ 'href' => 'https://evil.example/' ];
	PdfProbeTwo::$reply = [ 'href' => '#pdf-template-two', 'inside' => true ];
	check( 'an answer that is none does not stop the next listener, and the first valid one decides: of two the second is taken, and with two valid ones the first', $of( [ 'part' => 'templates' ] ) === [ 'href' => '#pdf-template-two', 'inside' => true ] && count( ninoWarnings() ) === 1
		&& ( static function() use ( $of ): bool { PdfProbeOne::$reply = [ 'href' => '#one' ]; return $of( [ 'part' => 'templates' ] )['href'] === '#one'; } )() === true );

	$reset();
	$listen( PdfProbeOne::class );
	PdfProbeOne::$reply = static fn() => throw new \RuntimeException( 'gone' );
	check( 'a listener that throws is named in a warning and the address of the panel stands', pdfTry( static function() use ( $of ): mixed { return $of( [ 'part' => 'blocks', 'key' => 'intro' ] ); } ) === [ 'href' => $base. '#pdf/blocks/intro', 'inside' => false ] && count( ninoWarnings() ) === 1 );

	$reset();
}
