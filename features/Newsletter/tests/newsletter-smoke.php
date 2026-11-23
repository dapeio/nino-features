<?php
declare(strict_types=1);

/**
 *	Nino
 *	newsletter-smoke.php	The test of the feature Newsletter: structure and behaviour, one pin each. What it holds is what the
 *												folders say of the feature (the components of the group newsletter, their part, the block, the two pages made
 *												of components), the signup with a script and without one (the redirect of \Nino\Form::answer()), the double
 *												opt-in with its tokens - random, expiring, used once, a link that changes nothing - and the answer that does not
 *												tell whether an address is on the list, the panel with a right behind every action, and the boundaries between
 *												an address a visitor typed and a header, a page, a table and a file. The files of a site of Nino 1 are read as
 *												they are. Where node is on the path the script of the panel is run over a stand-in as well (newsletter-js-smoke.js).
 *
 *	Usage: php features/Newsletter/tests/newsletter-smoke.php
 *	       NINO_ROOT=../nino php features/Newsletter/tests/newsletter-smoke.php
 */
$root = getenv( 'NINO_ROOT' ) ?: dirname( __DIR__, 3 );
defined( 'NINO_FEATURES_DIR' ) === true || define( 'NINO_FEATURES_DIR', dirname( __DIR__, 2 ) );
require $root. '/tests/harness.php';

$dir = dirname( __DIR__ );
$appData = ninoSandbox( 'newsletter' );
$appData['./nino/filesystem/path'] = $root;
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$appData['/nino/dir'] = '';
$list = '/data/newsletter.php';
$removedList = '/data/newsletter-removed.php';

echo "The feature - what the folders say of it\n";

$manifest = \Nino\Features::manifest( $dir );
check( 'the manifest validates, is written for this kernel and keeps the two files of Nino 1 under data',
	is_array( $manifest ) === true && $manifest['key'] === 'newsletter' && $manifest['version'] === '2.0.0' && \Nino\Features::satisfies( (string) $manifest['nino'] ) === true
	&& isset( $manifest['components'], $manifest['stacks'] ) === false && $manifest['data'] === [ $list, $removedList ] );
check( 'Basis and the feature are switched on', \Nino\Features::activate( $appData, 'basis' ) === true && \Nino\Features::activate( $appData, 'newsletter' ) === true );

$appData['/nino/modules'] = (array) ( \Nino\Filesystem::getFileContent( $appData, '/config.php', [] )['/nino/modules'] ?? [] );
\Nino\Html::init( $appData );
\Nino\Features::registerComponents( $appData );
\Nino\Modules\Template::init( $appData );
\Nino\Modules\Csrf::init( $appData );
\Nino\Modules::callModules( $appData, 'init' );
\Nino\Features::registerParts( $appData );

\Nino\Html::addFills( $appData, [
	'[[/project/website/general/url]]'		=> 'example.org/shop',
	'[[/project/mail/address/owner]]'			=> 'owner@example.com',
	'[[/project/mail/address/envelope]]'	=> 'owner@example.com',
	'[[/feature/newsletter/info/success]]'	=> 'Thank you',
	'[[/feature/newsletter/info/error]]'		=> 'Not sent',
	'[[/feature/newsletter/info/email]]'		=> 'Check your address',
], '*' );
$siteUrl = 'https://example.org/shop';

// A page is drawn once in a request: what the register and the component keep for a request is dropped between two drawings
$draw = static function( string $source ) use ( &$appData ): string {
	unset( $appData[ \Nino\Components::OPTIONS ], $appData['./newsletter/forms'], $appData['./newsletter/page'], $appData['./newsletter/unsubscribe-line'] );
	return trim( \Nino\Html::renderSource( $appData, $source, \Nino\Html::context( $appData ) ) );
};
$parse = static function( string $html ): DOMXPath {
	$document = new DOMDocument();
	libxml_use_internal_errors( true );
	$document->loadHTML( '<?xml encoding="UTF-8"><body>'. $html. '</body>' );
	libxml_clear_errors();
	return new DOMXPath( $document );
};
$ignored = ninoWarnings();

check( 'the folders are read without a reason', \Nino\Features::componentProblems( $appData, 'newsletter' ) === [] );
$schemas = \Nino\Components::components( $appData );
$own = [ 'newsletter-signup', 'newsletter-result', 'newsletter-unsubscribe' ];
check( 'the three components are in their group, labelled in both languages, with an icon of the table', array_filter( $own, static fn( string $name ): bool => ( $schemas[$name]['group'] ?? '' ) !== 'newsletter'
	|| isset( $schemas[$name]['label']['de_DE'], $schemas[$name]['label']['en_US'] ) === false || in_array( $schemas[$name]['icon'] ?? '', \Nino\Components::ICONS, true ) === false ) === [] && ( $schemas['newsletter-signup']['icon'] ?? '' ) === 'mail' );
check( 'the signup has the one attribute inline, a bool with a label and a hint in both languages', array_keys( $schemas['newsletter-signup']['attributes'] ?? [] ) === [ 'inline' ]
	&& ( $schemas['newsletter-signup']['attributes']['inline']['type'] ?? '' ) === 'bool' && isset( $schemas['newsletter-signup']['attributes']['inline']['label']['de_DE'], $schemas['newsletter-signup']['attributes']['inline']['label']['en_US'], $schemas['newsletter-signup']['attributes']['inline']['hint']['de_DE'], $schemas['newsletter-signup']['attributes']['inline']['hint']['en_US'] ) === true );
$group = \Nino\Components::groups( $appData )['newsletter'] ?? [];
$part = (string) file_get_contents( $dir. '/groups/newsletter/part/part.css' );
check( 'the group has its knobs and a demo, and the standard part keeps the rule of mixing', array_keys( $group['knobs'] ?? [] ) === [ 'newsletter-signup.gap', 'newsletter-signup.radius', 'newsletter-signup.size' ]
	&& ( $group['demo'] ?? '' ) !== '' && \Nino\Components::partProblems( $part, $own ) === [] );
check( 'the part is in the style bundle, and it keeps off the form group: no token of it is read', str_contains( \Nino\Modules\Assets::stylesheet( $appData, '/.cache/style.css' ), '.newsletter-signup-control' ) === true
	&& preg_match( '/--form-|\.form\b|\.form-/', $part ) === 0 );
check( 'a tool offers the signup in the flow of a column and of a footer column, and the feature depends on Basis alone', in_array( 'newsletter-signup', \Nino\Components::allowed( $appData, 'section-col' ), true ) === true
	&& in_array( 'newsletter-signup', \Nino\Components::allowed( $appData, 'footer-col' ), true ) === true && $manifest['requires'] === [ 'basis' ] );

$block = \Nino\Blocks::get( $appData, 'newsletter-signup' );
check( 'the block is valid, names the feature and misses nothing', is_array( $block ) === true && in_array( 'newsletter', $block['features'] ?? [], true ) === true && ( $block['missing'] ?? [] ) === [] );
check( 'the demo of the group draws the form twice, once in one line', substr_count( $draw( (string) $group['demo'] ), 'class="newsletter-signup-submit"' ) === 2 && substr_count( $draw( (string) $group['demo'] ), 'newsletter-signup--inline' ) === 1 );

$oldWorld = [];
foreach( new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $dir, \FilesystemIterator::SKIP_DOTS ) ) as $file )
	if( str_contains( (string) $file, '/tests/' ) === false && preg_match( '/Feature::shortcode|Modules\\\\Components|\bnino-(?!form\b|admin-)[a-z]|--(?:space|color|radius|fontsize)-|Nino\.ui\.|novalidate/', (string) file_get_contents( (string) $file ) ) === 1 )
		$oldWorld[] = (string) $file;
check( 'no file of the feature names the vocabulary of Nino 1: nino-form is the one class of the kernel, and no form carries novalidate', $oldWorld === [] );

// The words every part of the code reads are in both languages of the site, and the pages are named in both
$site = [ 'de_DE' => (array) include $dir. '/install/text/de_DE.php', 'en_US' => (array) include $dir. '/install/text/en_US.php' ];
$needed = [];
foreach( \Nino\Modules\Newsletter::RESULTS as $result )
	array_push( $needed, '/feature/newsletter/result-'. $result. '/title', '/feature/newsletter/result-'. $result. '/text' );
foreach( [ 'success', 'error', 'email', 'required', 'expired' ] as $word )
	$needed[] = '/feature/newsletter/info/'. $word;
foreach( [ 'confirm', 'unsubscribe' ] as $word )
	array_push( $needed, '/feature/newsletter/action/'. $word, '/feature/newsletter/subject/'. $word );
array_push( $needed, '/feature/newsletter/label/submit', '/feature/newsletter/label/unsubscribe', '/feature/newsletter/signup/label', '/feature/newsletter/signup/placeholder' );
foreach( [ '.newsletter', '.newsletter/unsubscribe' ] as $page )
	foreach( [ 'name', 'title', 'description' ] as $detail )
		$needed[] = '/_nino/webpage/'. $page. '/'. $detail;
check( 'every word the code and the components read is a text of the unit, in both languages, and both say the same keys', array_filter( $needed, static fn( string $key ): bool => isset( $site['de_DE'][ '[['. $key. ']]' ], $site['en_US'][ '[['. $key. ']]' ] ) === false ) === []
	&& array_keys( $site['de_DE'] ) === array_keys( $site['en_US'] ) );

// The two pages are made of components and the frame: a tool reads each as blocks, with nothing it has to keep as free html
$pageProblems = static function( string $name ) use ( &$appData ): array {
	$source = (string) file_get_contents( ninoSandboxDir( $appData ). '/private/templates/'. $name. '.tpl' );
	$found = [ 'old' => preg_match( '/nino-|<section|<div|<form|<h1|<input/', $source ) === 1 ];
	foreach( \Nino\Components::check( $appData, \Nino\Markup::parse( $source, \Nino\Components::containers( $appData ) ) ) as $at => $problems )
		foreach( $problems as $problem )
			if( $problem['detail'] !== 'template' && ( $problem['code'] === 'free' && $at === 0 ) === false )
				$found[] = $problem['code'];
	return $found;
};
check( 'the page of the outcome and the page of the unsubscribe form are made of components: no class of Nino 1, no element of their own, nothing a tool keeps as free html',
	$pageProblems( 'page-newsletter' ) === [ 'old' => false ] && $pageProblems( 'page-newsletter-unsubscribe' ) === [ 'old' => false ] );
check( 'the unit brought the pages, the mails and the frame of the mail, and not the form with the button, which is a component now', array_filter( [ 'page-newsletter', 'page-newsletter-unsubscribe', 'mail-newsletter-confirm', 'mail-newsletter-unsubscribe', 'mail-header', 'mail-footer' ],
	static fn( string $name ): bool => \Nino\Filesystem::fileExists( $appData, '/templates/'. $name. '.tpl' ) === false ) === [] && \Nino\Filesystem::fileExists( $appData, '/templates/newsletter-action.tpl' ) === false );
$privacy = (array) include $dir. '/install/elements/privacy.php';
check( 'the section of the privacy policy is in the unit, in both languages, under the id of the feature', ( (array) include $dir. '/install/manifest.php' )['elements']['privacy'] === 'elements/privacy.php'
	&& isset( $privacy['*']['newsletter'], $privacy['de_DE']['newsletter']['text'], $privacy['en_US']['newsletter']['text'] ) === true );
$handles = array_keys( $manifest['manual']['install'] );
check( 'the manual names the two files of the feature and says that a project with pages of its own copies the two pages of the unit by hand', in_array( '/data/newsletter.php, /data/newsletter-removed.php', $handles, true ) === true && in_array( 'templates/page-newsletter*.tpl', $handles, true ) === true );
check( 'the routes are registered by the module, the five of them, with the callbacks the answers come from', array_keys( array_filter( $appData['/nino/http/routes'] ?? [], static fn( array $route ): bool => str_starts_with( (string) ( $route['uri'] ?? '' ), '/.newsletter' ) ) )
	=== [ 'POST://.newsletter', 'GET://.newsletter', 'POST://.newsletter/unsubscribe', 'GET://.newsletter/unsubscribe', 'POST://.newsletter/action' ] && isset( $appData['./nino/callbacks']['/nino/admin/restore'] ) === true );

echo "\n";
echo "The signup form - for the script of the kernel, and for a browser without it\n";

$signup = $draw( '[newsletter-signup]' );
$x = $parse( $signup );
$form = $x->query( '//form' )->item( 0 );
check( 'the contract of the script of the kernel: nino-form, a prefix of words that the kernel publishes, the message is the first paragraph and the submit the first button, no novalidate',
	$form !== null && in_array( 'nino-form', explode( ' ', $form->getAttribute( 'class' ) ), true ) === true && $form->getAttribute( 'data-form-texts' ) === \Nino\Modules\Newsletter::INFO && $form->hasAttribute( 'novalidate' ) === false
	&& in_array( \Nino\Modules\Newsletter::INFO, ( new ReflectionClassConstant( \Nino\Modules\Form::class, 'WORDS' ) )->getValue(), true ) === true
	&& $x->query( './/p', $form )->item( 0 )->getAttribute( 'class' ) === 'newsletter-signup-message' && $x->query( './/button', $form )->item( 0 )->getAttribute( 'type' ) === 'submit' );
check( 'it posts to the endpoint below the directory of the project, with the token of the session, the empty honeypot and the id of the form on its page',
	$form->getAttribute( 'action' ) === '/.newsletter' && $form->getAttribute( 'method' ) === 'post' && $x->query( './/input[@name="_csrf"]' )->length === 1 && $x->query( './/input[@name="location"][@value=""][@tabindex="-1"]' )->length === 1
	&& $x->query( './/input[@name="id"]' )->item( 0 )->getAttribute( 'value' ) === $form->getAttribute( 'id' ) );
$field = $x->query( './/input[@name="email"]' )->item( 0 );
check( 'the address field is an email field that is required and can be filled by a browser, and its label points at it', $field->getAttribute( 'type' ) === 'email' && $field->hasAttribute( 'required' ) === true && $field->getAttribute( 'autocomplete' ) === 'email'
	&& $x->query( './/label' )->item( 0 )->getAttribute( 'for' ) === $field->getAttribute( 'id' ) );
$two = $parse( $draw( '[newsletter-signup][newsletter-signup inline]' ) );
check( 'two signups on a page have ids and fields of their own, and the one in a line is a class of the part', $two->query( '//form' )->length === 2 && $two->query( '//form' )->item( 0 )->getAttribute( 'id' ) !== $two->query( '//form' )->item( 1 )->getAttribute( 'id' )
	&& $two->query( '//input[@name="email"]' )->item( 0 )->getAttribute( 'id' ) !== $two->query( '//input[@name="email"]' )->item( 1 )->getAttribute( 'id' ) && str_contains( $two->query( '//form' )->item( 1 )->getAttribute( 'class' ), 'newsletter-signup--inline' ) === true );
$all = [];
foreach( $x->query( '//*[@class]' ) as $node )
	$all = array_merge( $all, explode( ' ', $node->getAttribute( 'class' ) ) );
check( 'the one class of Nino 1 the form carries is nino-form, which the script of the kernel is handed it by', array_values( array_filter( $all, static fn( string $c ): bool => str_starts_with( $c, 'nino-' ) === true ) ) === [ 'nino-form' ] );

$onPage = static function( $query ) use ( &$appData, $draw ): string {
	$appData['./nino/http/requests'] = [ [ '/nino/http/request' => [ 'query' => $query === null ? [] : [ 'form' => $query ], 'uri' => '/contact' ] ] ];
	$html = $draw( '[newsletter-signup][newsletter-signup inline]' );
	$appData['./nino/http/requests'] = [];
	return $html;
};
check( 'a page that was sent back with ?form=<outcome> has the words of that outcome in the message of every signup, marked with the outcome',
	substr_count( $onPage( 'success' ), '<p class="newsletter-signup-message" data-form-outcome="success">Thank you</p>' ) === 2 && str_contains( $onPage( 'error' ), 'data-form-outcome="error">Not sent<' ) === true && str_contains( $onPage( 'email' ), '>Check your address<' ) === true );
check( 'an address that names no outcome, a word of its own or an array draws no message', str_contains( $onPage( null ), 'data-form-outcome' ) === false && str_contains( $onPage( '<script>' ), 'data-form-outcome' ) === false
	&& str_contains( $onPage( 'x" onclick="y' ), 'onclick' ) === false && str_contains( $onPage( [ 'success' ] ), 'data-form-outcome' ) === false );
check( 'which signup shows the message is the part\'s: the one the fragment names (:target), and a paragraph the script has written into (tabindex) is its own',
	preg_match( '/\.newsletter-signup:target\s*>\s*\.newsletter-signup-message\[data-form-outcome\]:not\(\[tabindex\]\)\s*\{\s*display:\s*block/', $part ) === 1 && preg_match( '/\.newsletter-signup-message\[data-form-outcome\]:not\(\[tabindex\]\)\s*\{\s*display:\s*none/', $part ) === 1 );
\Nino\Html::addFills( $appData, [ '[[/feature/newsletter/info/success]]' => '<b>"A"</b> & [csrf]' ], '*' );
$said = $onPage( 'success' );
check( 'SAFETY: the message is text: markup, quotes and a call in the words are escaped, and a call is not read', str_contains( $said, '&lt;b&gt;&quot;A&quot;&lt;/b&gt; &amp; [csrf]' ) === true && str_contains( $said, '<b>' ) === false );
\Nino\Html::addFills( $appData, [ '[[/feature/newsletter/info/success]]' => 'Thank you' ], '*' );

echo "\n";
echo "The endpoint - the answer follows the delivery, with a script and without one\n";

$mails = [];
$links = [];
$transport = true;
\Nino\Callbacks::registerCallback( $appData, \Nino\Mail::TRANSPORT, static function( array &$appData, array &$mail ) use ( &$mails, &$links, &$transport ): void {
	$mails[] = $mail;
	$links[] = [ 'confirm' => (string) ( $appData['./nino/html/fills']['*']['[[/feature/newsletter/confirm/url]]'] ?? '' ), 'unsubscribe' => (string) ( $appData['./nino/html/fills']['*']['[[/feature/newsletter/unsubscribe/url]]'] ?? '' ) ];
	$mail['sent'] = $transport;
} );
$link = static function( string $kind ) use ( &$links ): string { return $links === [] ? '' : $links[ array_key_last( $links ) ][$kind]; };
$token = static function( string $url ): string { return rawurldecode( (string) preg_replace( '/^.*=/', '', $url ) ); };
$fresh = static function() use ( &$appData ): void { \Nino\Feature::forget( $appData, 'ratelimit', '127.0.0.1' ); };
$spend = static function() use ( &$appData, $fresh ): void { $fresh(); for( $try = 0; $try < 5; $try++ ) \Nino\Feature::count( $appData, 'ratelimit', '127.0.0.1', 5, 3600 ); };
$read = static fn(): array => \Nino\Filesystem::getFileContent( $appData, '/data/newsletter.php', [] );
$write = static function( array $entries ) use ( &$appData ): void { \Nino\Filesystem::putFileContent( $appData, '/data/newsletter.php', $entries ); };
$entry = static fn( string $email, string $status, string $date, string $token = '' ): array => [ 'email' => $email, 'token' => $token !== '' ? $token : bin2hex( random_bytes( 8 ) ), 'status' => $status, 'date' => $date, 'ip' => '203.0.113.7' ];

/**
 *	One post to /.newsletter through what a request passes: the csrf guard of the kernel (a token that is not the session's, unless the test
 *	says it is wanted), then the callback of the route. A post of the script of the page carries the marker, one of a browser without it a Referer
 */
$post = static function( array $fields, bool $script = true, bool $token = true, string $referer = 'http://localhost/news?x=1' ) use ( &$appData, $fresh ): array {
	$fresh();
	$_POST = array_merge( [ 'location' => '', '_csrf' => $token === true ? \Nino\Csrf::getToken( $appData ) : 'wrong-token' ], $fields );
	$header = $script === true ? [ \Nino\Form::SCRIPT_HEADER => '1' ] : [ 'Referer' => $referer, 'Host' => 'localhost' ];
	$request = [ '/nino/http/request' => [ 'method' => 'POST', 'header' => $header ], '/nino/http/response' => [ 'statusCode' => 200, 'header' => [], 'body' => '' ] ];
	\Nino\Csrf::callbackResponse( $appData, $request );
	\Nino\Callbacks::doCallbacks( $appData, '/nino/http/response/POST://.newsletter', $request );
	$_POST = [];
	return $request;
};
$status = static fn( array $request ): int => (int) $request['/nino/http/response']['statusCode'];
$where = static fn( array $request ): string => (string) ( $request['/nino/http/response']['header']['Location'] ?? '' );

$request = $post( [ 'email' => 'jo@example.com' ], false );
check( 'a signup of a browser without a script is answered with the redirect of the kernel back to the page, ?form=success and the id of the form, and the mail went out',
	$status( $request ) === 303 && $where( $request ) === '/news?x=1&form=success' && count( $mails ) === 1 && $mails[0]['to'] === 'jo@example.com' );
$request = $post( [ 'email' => 'jo@example.com', 'id' => 'newsletter-signup-2' ], false );
check( '...with the fragment where the post says which form it was', $where( $request ) === '/news?x=1&form=success#newsletter-signup-2' );
$mails = [];
$request = $post( [ 'email' => 'anna@example.com' ], true );
check( 'the script of the page gets a 200 and the body, and no redirect', $status( $request ) === 200 && $where( $request ) === '' && ( $request['/nino/http/response']['body']['status'] ?? '' ) === 'ok' );
check( 'the redirect is the kernel\'s: the feature writes no Location, no redirect and no header of its own', preg_match( '/\[\'Location\'\]\s*=[^=>]|\bheader\(|(?<!Http::)\bredirect\(/', (string) file_get_contents( $dir. '/Newsletter.php' ). (string) file_get_contents( $dir. '/Admin/Admin.php' ) ) === 0 );

$write( [] );
$mails = [];
$entries = [];
foreach( [ 'one@example.com', 'two@example.com' ] as $address ) {
	$post( [ 'email' => $address ], true );
	$entries[] = $read()[ count( $entries ) ];
}
$source = (string) file_get_contents( $dir. '/Newsletter.php' );
check( 'SAFETY: a signup is recorded as pending with a token of 128 random bits - 32 hex characters, a different one for each address - and every place that makes a token takes it from random_bytes( 16 ) and from nothing else',
	count( $entries ) === 2 && $entries[0]['status'] === 'pending' && preg_match( '/^[0-9a-f]{32}$/D', $entries[0]['token'] ) === 1 && preg_match( '/^[0-9a-f]{32}$/D', $entries[1]['token'] ) === 1 && $entries[0]['token'] !== $entries[1]['token']
	&& substr_count( $source, '= bin2hex( random_bytes( 16 ) );' ) === 3 && preg_match( '/\b(?:mt_rand|rand|uniqid|mt_srand|srand|lcg_value|microtime|md5|sha1|crc32)\s*\(/', $source ) === 0 );
$write( [] );
$post( [ 'email' => 'one@example.com' ], true );
$first = $read()[0]['token'];
$write( [] );
$post( [ 'email' => 'one@example.com' ], true );
check( 'SAFETY: a token is not made from the address - the same address gets another one when it signs up again', $read()[0]['token'] !== $first );

$source = (string) file_get_contents( $dir. '/Newsletter.php' );
check( 'SAFETY: a token is compared in constant time wherever it is read - the visit, the confirmation and the removal - and never with an operator', substr_count( $source, 'hash_equals( (string) ( $entry[\'token\'] ?? \'\' ), $token )' ) === 3
	&& preg_match( '/\$token\s*[!=]==?\s*(?:\$|\(string\))|[!=]==?\s*\$token\b/', $source ) === 0 );
$write( [] );
$mails = [];
$answers = [];
foreach( [ 'new@example.com', 'again@example.com' ] as $address ) {
	$write( $address === 'again@example.com' ? [ $entry( 'again@example.com', 'subscribed', '2026-01-02 03:04:05' ) ] : [] );
	$mails = [];
	$request = $post( [ 'email' => $address ], false );
	$answers[$address] = [ $status( $request ), $where( $request ), count( $mails ) ];
}
$write( [ $entry( 'wait@example.com', 'pending', date( 'Y-m-d H:i:s', time() - 3600 ) ) ] );
$mails = [];
$request = $post( [ 'email' => 'wait@example.com' ], false );
$answers['wait@example.com'] = [ $status( $request ), $where( $request ), count( $mails ) ];
check( 'SAFETY: the answer does not tell whether an address is on the list: a new, a pending and a subscribed one get the same status, the same redirect and one mail each', count( array_unique( array_map( 'serialize', $answers ) ) ) === 1 && $answers['new@example.com'] === [ 303, '/news?x=1&form=success', 1 ] );

$write( [] );
$mails = [];
$sentBefore = count( $mails );
$bad = [ 'one line' => "a@example.com\r\nBcc: victim@example.net",  'a null byte' => "a\0@example.com", 'markup' => '<b>@example.com', 'empty' => '', 'an array' => [ 'a@example.com' ] ];
$refused = [];
foreach( $bad as $name => $value ) {
	$request = $post( [ 'email' => $value ], true );
	$refused[$name] = $status( $request );
}
check( 'SAFETY: an address with a line break, a null byte, markup, none at all or an array is no address: 400, nothing mailed, nothing recorded, no warning', array_values( array_unique( $refused ) ) === [ 400 ] && count( $mails ) === $sentBefore && $read() === [] && ninoWarnings() === [] );
$request = $post( [ 'email' => "a@example.com\r\nBcc: victim@example.net" ], false );
check( '...and a browser without a script is sent back with ?form=email', $status( $request ) === 303 && str_ends_with( $where( $request ), 'form=email' ) );
$post( [ 'email' => 'jo@example.com' ], true );
check( 'SAFETY: no header line of the mail holds a CR, an LF or a null byte, and the recipient is the address that was posted',
	$mails !== [] && preg_match( '/[\r\n\0]/', $mails[0]['to']. $mails[0]['subject']. $mails[0]['replyTo'] ) === 0 && $mails[0]['to'] === 'jo@example.com' && $mails[0]['replyTo'] === 'owner@example.com' );
\Nino\Html::addFills( $appData, [ '[[/feature/newsletter/subject/confirm]]' => "It&#039;s &amp;\n[[/project/website/general/url]]&#10;Bcc: x@y.z" ], '*' );
$mails = [];
$post( [ 'email' => 'jo@example.com' ], true );
check( 'the subject is plain text: the entities of a word of the Text panel are the characters they mean, and a line break is a space', $mails !== [] && $mails[0]['subject'] === 'It\'s & example.org/shop Bcc: x@y.z' );
$write( [] );
$mails = [];
$request = $post( [ 'email' => 'jo@example.com', 'location' => 'http://spam.example' ], true );
$scripted = $status( $request );
$request = $post( [ 'email' => 'jo@example.com', 'location' => 'http://spam.example' ], false );
check( 'SAFETY: the honeypot refuses with a script (418) and without (back to the page with ?form=error), and nothing is recorded or mailed', $scripted === 418 && $status( $request ) === 303 && str_ends_with( $where( $request ), 'form=error' ) && $read() === [] && $mails === [] );
$request = $post( [ 'email' => 'jo@example.com', 'location' => [ 'x' ] ], true );
check( 'an array in the honeypot is a filled honeypot, and an array in the address is no address - no warning', $status( $request ) === 418 && ninoWarnings() === [] );

$mails = [];
$request = $post( [ 'email' => 'jo@example.com' ], true, false );
$scripted = $status( $request );
$request = $post( [ 'email' => 'jo@example.com' ], false, false );
check( 'SAFETY: a post without the token of the session is a 403, with a script and without - no redirect, nothing recorded, nothing mailed', $scripted === 403 && $status( $request ) === 403 && $where( $request ) === '' && $read() === [] && $mails === [] );

// The mail cap of the kernel is the rate: a signup is counted when its mail is sent, so a browser without a script is counted as the script is
$tries = static fn(): int => (int) ( \Nino\Filesystem::getFileContent( $appData, '/data/ratelimit.php', [] )[ hash( 'sha256', '127.0.0.1' ) ]['tries'] ?? 0 );
$write( [] );
$mails = [];
$fresh();
$_POST = [ 'email' => 'jo@example.com', 'location' => '', '_csrf' => \Nino\Csrf::getToken( $appData ) ];
$request = [ '/nino/http/request' => [ 'method' => 'POST', 'header' => [ 'Referer' => 'http://localhost/news', 'Host' => 'localhost' ] ], '/nino/http/response' => [ 'statusCode' => 200, 'header' => [], 'body' => '' ] ];
\Nino\Callbacks::doCallbacks( $appData, '/nino/http/response/POST://.newsletter', $request );
$_POST = [];
check( 'SAFETY: a signup of a browser without a script is counted against the mail cap of its address, as the script is', $status( $request ) === 303 && $tries() === 1 );
$counted = [];
foreach( [ true, false ] as $script ) {
	$spend();
	$mails = [];
	$_POST = [ 'email' => 'cap'. (int) $script. '@example.com', 'location' => '', '_csrf' => \Nino\Csrf::getToken( $appData ) ];
	$request = [ '/nino/http/request' => [ 'method' => 'POST', 'header' => $script === true ? [ \Nino\Form::SCRIPT_HEADER => '1' ] : [ 'Referer' => 'http://localhost/news', 'Host' => 'localhost' ] ], '/nino/http/response' => [ 'statusCode' => 200, 'header' => [], 'body' => '' ] ];
	\Nino\Callbacks::doCallbacks( $appData, '/nino/http/response/POST://.newsletter', $request );
	$_POST = [];
	$counted[] = [ $status( $request ), str_ends_with( $where( $request ), 'form=error' ), count( $mails ) ];
}
check( 'SAFETY: an address at its mail cap is answered 429 with a script and, without one, with the redirect that says ?form=error - and nothing is mailed', $counted === [ [ 429, false, 0 ], [ 303, true, 0 ] ] );
$transport = false;
$write( [] );
$request = $post( [ 'email' => 'jo@example.com' ], true );
$scripted = $status( $request );
$request = $post( [ 'email' => 'jo@example.com' ], false );
$transport = true;
check( 'a transport that refuses the mail is a 500 and, without a script, ?form=error; the entry is kept for the re-submit', $scripted === 500 && str_ends_with( $where( $request ), 'form=error' ) && count( $read() ) === 1 );

// The ceilings
$write( [] );
$stale = [];
for( $n = 0; $n < \Nino\Modules\Newsletter::PENDING_LIMIT; $n++ )
	$stale[] = $entry( 'p'. $n. '@example.com', 'pending', date( 'Y-m-d H:i:s', time() - 60 - $n ) );
$write( array_merge( [ $entry( 'member@example.com', 'subscribed', '2026-01-02 03:04:05' ) ], $stale ) );
$post( [ 'email' => 'last@example.com' ], true );
$pendingNow = array_values( array_filter( $read(), static fn( array $e ): bool => $e['status'] === 'pending' ) );
check( 'the ceiling holds: with 500 unconfirmed signups the oldest makes room for the newest, and a confirmed subscriber is never touched', count( $pendingNow ) === \Nino\Modules\Newsletter::PENDING_LIMIT
	&& in_array( 'last@example.com', array_column( $pendingNow, 'email' ), true ) === true && in_array( 'p'. ( \Nino\Modules\Newsletter::PENDING_LIMIT - 1 ). '@example.com', array_column( $pendingNow, 'email' ), true ) === false && in_array( 'member@example.com', array_column( $read(), 'email' ), true ) === true );
$write( [ $entry( 'old@example.com', 'pending', date( 'Y-m-d H:i:s', time() - 8 * 86400 ) ) ] );
$post( [ 'email' => 'new@example.com' ], true );
check( 'an unconfirmed signup past its days is swept by the next signup', array_column( $read(), 'email' ) === [ 'new@example.com' ] );
$write( [] );
$post( [ 'email' => str_repeat( 'a', 1100 ). '@example.com' ], true );
$request = $post( [ 'email' => 'cut@example.com'. str_repeat( ' ', 1500 ). 'x' ], true );
check( 'a posted value is cut at 1000 bytes before it is read: a long address is none, and a valid one followed by a thousand spaces and more is the address', $status( $request ) === 200 && array_column( $read(), 'email' ) === [ 'cut@example.com' ] );
$write( [ $entry( 'jo@example.com', 'subscribed', '2026-01-02 03:04:05', 'tokenjocase' ) ] );
$post( [ 'email' => ' Jo@Example.COM ' ], true );
check( 'the same address in another spelling is one entry: lower case, trimmed, as the list holds it', array_column( $read(), 'email' ) === [ 'jo@example.com' ] && count( $read() ) === 1 );
$write( [] );
$quoted = [ '"a,b"@example.com', '"x"@example.com', 'a,b@example.com', '"<script>alert(1)</script>"@example.com' ];
$quotedStatus = array_map( static fn( string $a ): int => $status( $post( [ 'email' => $a ], true ) ), $quoted );
check( 'an address with a quotation mark or a comma is refused, with a script and without: 400, nothing recorded or mailed - the BCC line separates with a comma', $quotedStatus === [ 400, 400, 400, 400 ] && $read() === [] && $status( $post( [ 'email' => '"x"@example.com' ], false ) ) === 303 && $read() === [] );

echo "\n";
echo "The links - the first step changes nothing, the second does what its token says\n";

$write( [] );
$mails = [];
$post( [ 'email' => 'jo@example.com' ], true );
$confirmUrl = $link( 'confirm' );
$confirmToken = $token( $confirmUrl );
check( 'the mail carries the link of this site, with the token that is stored, and the link is no fill any more once the mail is out', str_starts_with( $confirmUrl, $siteUrl. '/.newsletter?confirm=' ) === true && $confirmToken === $read()[0]['token']
	&& isset( $appData['./nino/html/fills']['*']['[[/feature/newsletter/confirm/url]]'] ) === false );

/** A visit of a link, the way the route's callback sees it, and what the page then draws */
$visit = static function( array $query ) use ( &$appData ): array {
	$request = ninoVisit( $appData, '/.newsletter'. ( $query === [] ? '' : '?'. http_build_query( $query ) ) );
	unset( $appData['./newsletter/page'] );
	\Nino\Modules\Newsletter::callbackLink( $appData, $request );
	return $request;
};
/** The button: the post to /.newsletter/action, the csrf guard first */
$press = static function( array $fields, bool $csrf = true ) use ( &$appData ): array {
	$_POST = $fields + ( $csrf === true ? [ '_csrf' => \Nino\Csrf::getToken( $appData ) ] : [ '_csrf' => 'wrong-token' ] );
	$request = ninoVisit( $appData, '/.newsletter/action', 'POST' );
	unset( $appData['./newsletter/page'] );
	\Nino\Csrf::callbackResponse( $appData, $request );
	\Nino\Modules\Newsletter::callbackPerform( $appData, $request );
	$_POST = [];
	return $request;
};
$result = static function() use ( &$appData, $parse ): DOMXPath {
	unset( $appData[ \Nino\Components::OPTIONS ] );
	return $parse( \Nino\Html::renderSource( $appData, '[newsletter-result]', \Nino\Html::context( $appData ) ) );
};

$request = $visit( [ 'confirm' => $confirmToken ] );
$x = $result();
$buttonForm = $x->query( '//form' )->item( 0 );
check( 'SAFETY: a visit of the link answers 200 with a page that has one form - a post to /.newsletter/action with the token in a field named confirm, the token of the session and one button - and changes nothing',
	$status( $request ) === 200 && $buttonForm !== null && $buttonForm->getAttribute( 'action' ) === '/.newsletter/action' && $buttonForm->getAttribute( 'method' ) === 'post' && $x->query( './/input[@name="confirm"]' )->item( 0 )->getAttribute( 'value' ) === $confirmToken
	&& $x->query( './/input[@name="_csrf"]' )->length === 1 && $x->query( './/button[@type="submit"]' )->length === 1 && $read()[0]['status'] === 'pending' );
check( '...as often as a scanner makes it', $status( $visit( [ 'confirm' => $confirmToken ] ) ) === 200 && $read()[0]['status'] === 'pending' );
check( 'SAFETY: a token that is unknown, empty, an array, or in the query beside another link is a 404 with the page of an invalid link and no form', array_unique( array_map( static function( array $query ) use ( $visit, $result ): int { $request = $visit( $query ); return (int) $request['/nino/http/response']['statusCode'] + ( $result()->query( '//form' )->length === 0 ? 0 : 1000 ); },
	[ [ 'confirm' => 'nope' ], [ 'confirm' => '' ], [ 'confirm' => [ 'x' ] ], [ 'confirm' => $confirmToken, 'unsubscribe' => $confirmToken ], [] ] ) ) === [ 404 ] );

$request = $press( [ 'confirm' => $confirmToken ], false );
check( 'SAFETY: the button without the token of the session does nothing: a 403 with the page that says it has expired, and the entry is pending', $status( $request ) === 403 && $read()[0]['status'] === 'pending' && $result()->query( '//form' )->length === 0
	&& $request['/nino/http/response']['body'] === '[template /templates/page-newsletter]' );
check( 'SAFETY: a post with an unknown token, with both fields or with neither is a 404 and changes nothing', array_unique( array_map( static fn( array $fields ): int => (int) $press( $fields )['/nino/http/response']['statusCode'], [ [ 'confirm' => 'nope' ], [ 'confirm' => $confirmToken, 'unsubscribe' => $confirmToken ], [], [ 'confirm' => [ 'x' ] ] ] ) ) === [ 404 ] && $read()[0]['status'] === 'pending' && ninoWarnings() === [] );

$wrong = [ substr( $confirmToken, 0, 16 ), $confirmToken. '0', substr( $confirmToken, 0, 31 ). 'x', strtoupper( $confirmToken ), ' '. $confirmToken, $confirmToken. ' ' ];
check( 'SAFETY: a token that is cut short, longer, one character off, in capitals or with a space is none: 404 on the visit and on the button, and the entry stays pending',
	array_unique( array_map( static fn( string $t ): int => $status( $visit( [ 'confirm' => $t ] ) ) * 1000 + $status( $press( [ 'confirm' => $t ] ) ), $wrong ) ) === [ 404404 ] && $read()[0]['status'] === 'pending' );
$request = $press( [ 'confirm' => $confirmToken ] );
$confirmed = $read()[0];
$_SERVER['REMOTE_ADDR'] = '198.51.100.9';
$write( [ [ 'status' => 'pending', 'ip' => '203.0.113.7', 'date' => date( 'Y-m-d H:i:s', time() - 3 * 86400 ) ] + $confirmed ] );
$press( [ 'confirm' => $confirmToken ] );
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$confirmed = $read()[0];
check( 'the button confirms: a 303 to the page of the outcome, the status subscribed - with the ip and the time of the confirming request, not those of the signup', $status( $request ) === 303 && $where( $request ) === '/.newsletter?done=confirmed' && $confirmed['status'] === 'subscribed'
	&& $confirmed['ip'] === '198.51.100.9' && abs( (int) strtotime( $confirmed['date'] ) - time() ) < 300 && $confirmed['token'] === $confirmToken && $confirmed['email'] === 'jo@example.com' );
$stable = $read();
$again = $visit( [ 'confirm' => $confirmToken ] );
$againForms = $result()->query( '//form' )->length;
$pressed = $press( [ 'confirm' => $confirmToken ] );
check( 'SAFETY: a token is good for the one deed: the link visited again is the page of the confirmation with no button, and pressing it again changes nothing, the date and the ip least', $status( $again ) === 200 && $againForms === 0 && $status( $pressed ) === 303 && $read() === $stable );
$done = $visit( [ 'done' => 'confirmed' ] );
check( 'after the post the browser is on a page of its own whose address holds one word and no token, so that a reload asks for nothing; a word that is none is the invalid link', $status( $done ) === 200 && str_contains( $html = \Nino\Html::renderSource( $appData, '[newsletter-result]', \Nino\Html::context( $appData ) ), (string) \Nino\Html::resolveTextfill( $appData, '/feature/newsletter/result-confirmed/title' ) ) === true && str_contains( $html, '<form' ) === false
	&& $status( $visit( [ 'done' => 'x' ] ) ) === 404 && $status( $visit( [ 'done' => [ 'confirmed' ] ] ) ) === 404 );

$removedHash = hash( 'sha256', 'jo@example.com' );
\Nino\Filesystem::putFileContent( $appData, $removedList, [ $removedHash ] );
$write( [ $entry( 'jo@example.com', 'pending', date( 'Y-m-d H:i:s', time() - 60 ), 'tokenjo' ) ] );
$press( [ 'confirm' => 'tokenjo' ] );
check( 'only a confirmation clears the record of a removal, and in the write that confirms', $read()[0]['status'] === 'subscribed' && in_array( $removedHash, \Nino\Filesystem::getFileContent( $appData, $removedList, [] ), true ) === false );

// The token runs out: an unconfirmed signup lives its days and then is as unknown as a token nobody issued
$write( [ $entry( 'late@example.com', 'pending', date( 'Y-m-d H:i:s', time() - 8 * 86400 ), 'tokenlate' ), $entry( 'timely@example.com', 'pending', date( 'Y-m-d H:i:s', time() - 6 * 86400 ), 'tokentimely' ), [ 'email' => 'nodate@example.com', 'token' => 'tokennodate', 'status' => 'pending' ] ] );
$expired = [ $status( $visit( [ 'confirm' => 'tokenlate' ] ) ), $status( $press( [ 'confirm' => 'tokenlate' ] ) ), $status( $press( [ 'confirm' => 'tokennodate' ] ) ) ];
check( 'SAFETY: the token of a signup past its days, or of one nobody can date, runs out: 404 on the visit and on the button, and the entry is not confirmed', $expired === [ 404, 404, 404 ] && array_column( $read(), 'status' ) === [ 'pending', 'pending', 'pending' ]
	&& $status( $visit( [ 'confirm' => 'tokentimely' ] ) ) === 200 );
$appData[ \Nino\Modules\Newsletter::PENDING_DAYS ] = 10;
check( 'the days are the setting of config.php: the same token is good again with ten', $status( $visit( [ 'confirm' => 'tokenlate' ] ) ) === 200 );
unset( $appData[ \Nino\Modules\Newsletter::PENDING_DAYS ] );

// The way out: a link, a button, an entry that is gone and a link that goes with it
$write( [ $entry( 'out@example.com', 'subscribed', '2026-01-02 03:04:05', 'tokenout' ), $entry( 'stay@example.com', 'subscribed', '2026-01-02 03:04:05', 'tokenstay' ) ] );
\Nino\Filesystem::putFileContent( $appData, $removedList, [] );
check( 'the unsubscribe link is built on the address of the site and is the one the mails carry', \Nino\Modules\Newsletter::getUnsubscribeLink( $appData, 'OUT@example.com ' ) === $siteUrl. '/.newsletter?unsubscribe=tokenout' && \Nino\Modules\Newsletter::getUnsubscribeLink( $appData, 'nobody@example.com' ) === false );
$request = $visit( [ 'unsubscribe' => 'tokenout' ] );
$x = $result();
check( 'SAFETY: a visit of the unsubscribe link offers the button and removes nothing', $status( $request ) === 200 && $x->query( './/input[@name="unsubscribe"]' )->item( 0 )->getAttribute( 'value' ) === 'tokenout' && count( $read() ) === 2 && \Nino\Filesystem::getFileContent( $appData, $removedList, [] ) === [] );
$press( [ 'unsubscribe' => 'tokenout' ], false );
check( 'SAFETY: the button without the token of the session removes nothing', count( $read() ) === 2 );
$request = $press( [ 'unsubscribe' => 'tokenout' ] );
check( 'the button removes the entry, sends the browser on to the page of the outcome and records the removal by the hash of the address, and not the address', $status( $request ) === 303 && $where( $request ) === '/.newsletter?done=unsubscribed' && array_column( $read(), 'email' ) === [ 'stay@example.com' ]
	&& \Nino\Filesystem::getFileContent( $appData, $removedList, [] ) === [ hash( 'sha256', 'out@example.com' ) ] );
check( 'SAFETY: the link of a removed entry is gone with it: a 404, and a token of the other kind opens nothing', $status( $visit( [ 'unsubscribe' => 'tokenout' ] ) ) === 404 && $status( $press( [ 'unsubscribe' => 'tokenout' ] ) ) === 404 && $status( $press( [ 'confirm' => 'tokenstay' ] ) ) === 303 );
$post( [ 'email' => 'out@example.com' ], true );
check( 'a signup does not clear a removal - anyone can type an address, only the confirmation is consent', in_array( hash( 'sha256', 'out@example.com' ), \Nino\Filesystem::getFileContent( $appData, $removedList, [] ), true ) === true );

// A restore merges: an address that was removed stays removed whatever the backup holds
$staging = ninoSandboxDir( $appData ). '/restore';
@mkdir( $staging. '/data', 0775, true );
file_put_contents( $staging. '/data/newsletter.php', '<?php return '. var_export( [ $entry( 'out@example.com', 'subscribed', '2025-01-01 00:00:00' ), $entry( 'back@example.com', 'subscribed', '2025-01-01 00:00:00' ) ], true ). ';' );
file_put_contents( $staging. '/data/newsletter-removed.php', '<?php return [];' );
$args = [ 'dataDir' => ninoSandboxDir( $appData ). '/private/data', 'staging' => $staging ];
\Nino\Modules\Newsletter::callbackRestore( $appData, $args );
check( 'a restore of an older backup leaves the removed address out, and the removal list is the union of both sides', array_column( (array) include $staging. '/data/newsletter.php', 'email' ) === [ 'back@example.com' ]
	&& in_array( hash( 'sha256', 'out@example.com' ), (array) include $staging. '/data/newsletter-removed.php', true ) === true );

// The file of a site of Nino 1 is read as it is: an entry from before the double opt-in has no status, and a token is made for it when it asks for one
$legacyDate = date( 'Y-m-d H:i:s', time() - 900 * 86400 );
$write( [ [ 'email' => 'legacy@example.com', 'date' => $legacyDate ] ] );
$mails = [];
$request = $post( [ 'email' => 'legacy@example.com' ], true );
$legacy = $read()[0];
check( 'a file of Nino 1 is read as it is: an entry with no status counts as subscribed, is mailed a link made for it in the same write, and keeps its date', $status( $request ) === 200 && count( $mails ) === 1 && array_diff_key( $legacy, [ 'token' => 1 ] ) === [ 'email' => 'legacy@example.com', 'date' => $legacyDate ]
	&& preg_match( '/^[0-9a-f]{32}$/D', $legacy['token'] ) === 1 );
$press( [ 'confirm' => $legacy['token'] ] );
check( '...and confirming it sets its status and keeps the date it signed up on', $read()[0]['status'] === 'subscribed' && $read()[0]['date'] === $legacyDate && isset( $read()[0]['ip'] ) === false );

echo "\n";
echo "The page of the outcome and the form that asks for a link\n";

$word = static fn( string $part ): string => (string) \Nino\Html::resolveTextfill( $appData, '/feature/newsletter/'. $part );
$write( [ $entry( 'x@example.com', 'pending', date( 'Y-m-d H:i:s' ), 'tx"><b>' ) ] );
$visit( [ 'confirm' => 'tx"><b>' ] );
$html = \Nino\Html::renderSource( $appData, '[newsletter-result]', \Nino\Html::context( $appData ) );
check( 'SAFETY: the token in the form of the button is an attribute value and stays one: it is escaped, and a token that spells markup opens no element', str_contains( $html, 'value="tx&quot;&gt;&lt;b&gt;"' ) === true && substr_count( $html, '<b>' ) === 0 );
$outcomes = [];
foreach( \Nino\Modules\Newsletter::RESULTS as $name ) {
	$appData['./newsletter/page'] = [ 'result' => $name, 'action' => str_starts_with( $name, 'pending-' ) === true ? substr( $name, 8 ) : '', 'token' => 'abc' ];
	unset( $appData[ \Nino\Components::OPTIONS ] );
	$html = \Nino\Html::renderSource( $appData, '[newsletter-result]', \Nino\Html::context( $appData ) );
	$x = $parse( $html );
	$outcomes[$name] = [ $x->query( '//h1' )->length, $x->query( '//form' )->length, trim( $x->query( '//h1' )->item( 0 )->textContent ) === trim( html_entity_decode( $word( 'result-'. $name. '/title' ), ENT_QUOTES | ENT_HTML5 ) ) ];
}
check( 'each outcome draws its own title and text, and the button only where a link has been read', array_filter( $outcomes, static fn( array $o, string $name ): bool => $o !== [ 1, str_starts_with( $name, 'pending-' ) === true ? 1 : 0, true ], ARRAY_FILTER_USE_BOTH ) === [] && count( array_unique( array_column( $outcomes, 2 ) ) ) === 1 );
unset( $appData['./newsletter/page'] );
check( 'a page drawn where no route has spoken is the page of an invalid link, never a success', str_contains( $draw( '[newsletter-result]' ), $word( 'result-invalid/title' ) ) === true );

// The form that asks for an unsubscribe link: the same answer for an address on the list and one that is not
$write( [ $entry( 'member@example.com', 'subscribed', '2026-01-02 03:04:05', 'tokenmember' ), $entry( 'waiting@example.com', 'pending', date( 'Y-m-d H:i:s', time() - 60 ), 'tokenwaiting' ) ] );
$ask = static function( array $fields, bool $csrf = true ) use ( &$appData, $fresh, &$mails ): array {
	$fresh();
	$mails = [];
	$_POST = $fields + [ 'location' => '', '_csrf' => $csrf === true ? \Nino\Csrf::getToken( $appData ) : 'wrong-token' ];
	$request = ninoVisit( $appData, '/.newsletter/unsubscribe', 'POST' );
	unset( $appData['./newsletter/page'], $appData['./newsletter/unsubscribe-line'] );
	\Nino\Csrf::callbackResponse( $appData, $request );
	\Nino\Modules\Newsletter::callbackUnsubscribeRequest( $appData, $request );
	$_POST = [];
	return $request;
};
$list = $read();
$known = $ask( [ 'email' => 'member@example.com' ] );
$knownMails = $mails;
$unknown = $ask( [ 'email' => 'nobody@example.com' ] );
$unknownMails = $mails;
$knownPage = [ $status( $known ), $known['/nino/http/response']['body'], $appData['./newsletter/page'] ];
check( 'SAFETY: asking for a link answers 200 with the same page for an address on the list and one that is not - one mail to the first, none to the second, and nothing is removed by asking',
	$status( $known ) === 200 && $status( $unknown ) === 200 && $known['/nino/http/response']['body'] === $unknown['/nino/http/response']['body'] && $appData['./newsletter/page'] === $knownPage[2] && count( $knownMails ) === 1 && $unknownMails === [] && $read() === $list
	&& $knownMails[0]['to'] === 'member@example.com' );
$sentLinkOf = $links[ array_key_last( $links ) ]['unsubscribe'];
check( '...and the mail carries the link of the stored token, which is no fill once the mail is out', $sentLinkOf === $siteUrl. '/.newsletter?unsubscribe=tokenmember' && isset( $appData['./nino/html/fills']['*']['[[/feature/newsletter/unsubscribe/url]]'] ) === false );
// The mail cap of the visitor is one counter, and asking for a link charges it for every address alike: the signup that follows answers alike at the limit
$counterAfter = static function( string $email, int $asks ) use ( &$appData, &$mails, $fresh, $tries, $status, $where ): array {
	$fresh();
	for( $n = 0; $n < $asks; $n++ ) {
		$_POST = [ 'email' => $email, 'location' => '', '_csrf' => \Nino\Csrf::getToken( $appData ) ];
		$request = ninoVisit( $appData, '/.newsletter/unsubscribe', 'POST' );
		\Nino\Csrf::callbackResponse( $appData, $request );
		\Nino\Modules\Newsletter::callbackUnsubscribeRequest( $appData, $request );
	}
	$count = $tries();
	$mails = [];
	$_POST = [ 'email' => 'visitor@example.com', 'location' => '', '_csrf' => \Nino\Csrf::getToken( $appData ) ];
	$request = [ '/nino/http/request' => [ 'method' => 'POST', 'header' => [ 'Referer' => 'http://localhost/news', 'Host' => 'localhost' ] ], '/nino/http/response' => [ 'statusCode' => 200, 'header' => [], 'body' => '' ] ];
	\Nino\Callbacks::doCallbacks( $appData, '/nino/http/response/POST://.newsletter', $request );
	$_POST = [];
	return [ $count, $status( $request ), $where( $request ), count( $mails ) ];
};
$write( [ $entry( 'member@example.com', 'subscribed', '2026-01-02 03:04:05', 'tokenmember' ) ] );
$byAsks = [];
foreach( [ 1, 4, 5, 6 ] as $asks )
	$byAsks[$asks] = [ $counterAfter( 'member@example.com', $asks ), $counterAfter( 'stranger@example.org', $asks ) ];
check( 'SAFETY: an address that is not on the list is counted against the mail cap as one that is - the same count after 1, 4, 5 and 6 asks, and at the limit the signup of the visitor is answered the same (success up to the fourth ask, error from the fifth)',
	array_filter( $byAsks, static fn( array $pair ): bool => $pair[0] !== $pair[1] ) === [] && $byAsks[4][0][1] === 303 && str_ends_with( $byAsks[4][0][2], 'form=success' ) && str_ends_with( $byAsks[5][0][2], 'form=error' ) && $byAsks[5][0][3] === 0 && $byAsks[1][0][0] === 1 );
$spend();
$mails = [];
$_POST = [ 'email' => 'member@example.com', 'location' => '', '_csrf' => \Nino\Csrf::getToken( $appData ) ];
$capped = ninoVisit( $appData, '/.newsletter/unsubscribe', 'POST' );
\Nino\Modules\Newsletter::callbackUnsubscribeRequest( $appData, $capped );
$_POST = [];
$transport = false;
$refusedAsk = $ask( [ 'email' => 'member@example.com' ] );
$transport = true;
check( 'SAFETY: whatever the mail did - the cap, a transport that refuses it - the answer is the same 200, never a status that only an address on the list could reach', $status( $capped ) === 200 && $status( $refusedAsk ) === 200 );
$bots = [ $status( $ask( [ 'email' => 'member@example.com', 'location' => 'x' ] ) ), count( $mails ), $status( $ask( [ 'email' => 'not an address' ] ) ), $status( $ask( [ 'email' => [ 'a' ], 'location' => [ 'b' ] ] ) ) ];
check( 'a filled honeypot is a 418 with no mail, an address that is none a 400 with its form, and arrays are refused without a warning', $bots === [ 418, 0, 400, 418 ] && ninoWarnings() === [] );
$blocked = $ask( [ 'email' => 'member@example.com' ], false );
check( 'SAFETY: the form without the token of the session is a 403 that answers with the form and the line that says it has expired, and mails nothing', $status( $blocked ) === 403 && $mails === [] && $blocked['/nino/http/response']['body'] === '[template /templates/page-newsletter-unsubscribe]' && $appData['./newsletter/unsubscribe-line'] === \Nino\Modules\Newsletter::INFO. 'expired' );
$appData['./newsletter/unsubscribe-line'] = \Nino\Modules\Newsletter::INFO. 'email';
\Nino\Html::addFills( $appData, [ '[[/feature/newsletter/info/email]]' => 'Not an <b>address</b>' ], '*' );
$x = $parse( \Nino\Html::renderSource( $appData, '[newsletter-unsubscribe]', \Nino\Html::context( $appData ) ) );
check( 'the form posts to its own route with the token, the honeypot and an address field, and the line over its button is text', $x->query( '//form' )->item( 0 )->getAttribute( 'action' ) === '/.newsletter/unsubscribe' && $x->query( '//input[@name="_csrf"]' )->length === 1 && $x->query( '//input[@name="email"][@required]' )->length === 1
	&& trim( $x->query( '//p' )->item( 0 )->textContent ) === 'Not an <b>address</b>' && $x->query( '//b' )->length === 0 );

// A project swaps a page or a mail for its own - by the name of a template, and nothing else
$appData['/nino/newsletter/page-template'] = '/templates/own-page';
$routes = $appData['/nino/http/routes'];
\Nino\Modules\Newsletter::init( $appData );
$swapped = $appData['/nino/http/routes']['GET://.newsletter']['body'];
$appData['/nino/newsletter/page-template'] = '/templates/x]; [csrf';
\Nino\Modules\Newsletter::init( $appData );
check( 'a template of a project is named by config.php, and a value that is no name of a template is not read', $swapped === '[template /templates/own-page]' && $appData['/nino/http/routes']['GET://.newsletter']['body'] === '[template /templates/page-newsletter]' );
ninoWarnings();
unset( $appData['./newsletter/warned'] );
\Nino\Modules\Newsletter::init( $appData );
\Nino\Modules\Newsletter::init( $appData );
$once = array_filter( ninoWarnings(), static fn( string $w ): bool => str_contains( $w, 'page-template' ) );
check( 'a config value that is no name of a template - a path into a folder - is not read silently: the log says so once for the request', count( $once ) === 1 );
unset( $appData['/nino/newsletter/page-template'] );

echo "\n";
echo "The panel\n";

$panel = \Nino\Modules\Newsletter\Admin::class;
$write( [ $entry( 'a@example.com', 'subscribed', '2026-01-02 03:04:05', 'secret-a' ), [ 'email' => 'old@example.com', 'token' => 'secret-old', 'date' => '2020-01-01 00:00:00' ], $entry( 'p@example.com', 'pending', '2026-02-02 03:04:05', 'secret-p' ) ] );
check( 'the panel is in the registry while the feature is on, with its right and a sign of its own', isset( \Nino\Admin\Admin::panels( $appData )['newsletter'] ) === true && $panel::perm() === '/_admin/newsletter/manage'
	&& preg_match( '/^<svg [^>]*>(<[a-z]+ [^>]*\/>)+<\/svg>$/', $panel::icon() ) === 1 );
$answers = [];
foreach( [ 'apiList' => [], 'apiDelete' => [ 'email' => 'a@example.com' ] ] as $method => $data ) {
	ninoUser( $appData, 'editor@example.org', [] );
	$answers[$method.'-no-right'] = ninoCall( $appData, [ $panel, $method ], $data )[0];
	ninoUser( $appData, 'admin@example.org', [ $panel::perm() ] );
	$answers[$method.'-token-refused'] = ninoCall( $appData, [ $panel, $method ], $data, null, 403 )[0];
}
check( 'SAFETY: every action of the panel needs the right of the panel, and the token of the session: 403 without the right, and the answer of the guard stays where the csrf check refused it - nothing is deleted',
	$answers === [ 'apiList-no-right' => 403, 'apiList-token-refused' => 403, 'apiDelete-no-right' => 403, 'apiDelete-token-refused' => 403 ] && count( $read() ) === 3 );
[ $listed, $body ] = ninoCall( $appData, [ $panel, 'apiList' ] );
check( 'the list answers the newest first with the status of each, an entry from before the double opt-in as subscribed, the counts and the page a subscriber asks for a link at - and no token, which is a credential',
	$listed === 200 && array_column( $body['entries'], 'email' ) === [ 'p@example.com', 'old@example.com', 'a@example.com' ] && $body['counts'] === [ 'subscribed' => 2, 'pending' => 1 ] && $body['entries'][1]['status'] === 'subscribed'
	&& $body['unsubscribeUrl'] === $siteUrl. '/.newsletter/unsubscribe' && str_contains( json_encode( $body ), 'secret' ) === false );
check( 'the date and the ip are the record of a consent and are what is stored', $body['entries'][2]['ip'] === '203.0.113.7' && $body['entries'][2]['date'] === '2026-01-02 03:04:05' );
[ $deleted ] = ninoCall( $appData, [ $panel, 'apiDelete' ], [ 'email' => 'a@example.com' ] );
check( 'a delete removes the address and records the removal by its hash', $deleted === 200 && array_column( $read(), 'email' ) === [ 'old@example.com', 'p@example.com' ] && in_array( hash( 'sha256', 'a@example.com' ), \Nino\Filesystem::getFileContent( $appData, $removedList, [] ), true ) === true );
$missing = [ ninoCall( $appData, [ $panel, 'apiDelete' ], [ 'email' => 'nobody@example.com' ] ), ninoCall( $appData, [ $panel, 'apiDelete' ], [] ) ];
check( 'an address that is not on the list, or none, is a 404 with the code of its sentence', $missing[0][0] === 404 && $missing[1][0] === 404 && ( $missing[0][1]['code'] ?? '' ) === 'newsletter_unknown' && count( $read() ) === 2 );
check( 'the summary of the dashboard counts the confirmed ones, and the log names the address', $panel::count( $appData ) === 1 && $panel::log( 'newsletter/delete', [ 'email' => 'a@example.com' ] ) !== '' );

// What the script of the panel does and does not build, and the words it reads
$script = (string) file_get_contents( $dir. '/assets/admin.js' );
check( 'the panel script takes its table, bar, buttons, dialog and toast from the workbench and builds none itself', preg_match( '/createElement\(\s*.button|createElement\(\s*.textarea|window\.confirm|\balert\(|nino-admin-btn-|type\s*[:=]\s*.file|innerHTML\s*=\s*[^\'"\s]/', $script ) === 0
	&& str_contains( $script, 'Nino.adminUi.table( {' ) === true && str_contains( $script, 'Nino.adminUi.listActions(' ) === true && str_contains( $script, "Nino.adminUi.confirm( 'destructive'" ) === true && str_contains( $script, 'Nino.adminUi.say(' ) === true && str_contains( $script, 'search	: true' ) === true );
check( 'a row is no target of its own where it holds buttons: the table gets no onRowClick', str_contains( $script, 'onRowClick' ) === false );
check( 'SAFETY: the export goes through the workbench\'s own, which neutralizes a cell that starts as a formula (= + - @), and writes the address, status, date and ip of a row - never a token', preg_match( '/Nino\.admin\.exportCsv\(/', $script ) === 1 && preg_match( '/new Blob|\.download\s*=|token/i', $script ) === 0
	&& preg_match( '/^\s*unset\( \$entry\[\'token\'\] \);/m', (string) file_get_contents( $dir. '/Admin/Admin.php' ) ) === 1 );
$panelWords = [ 'de_DE' => include $dir. '/text/de_DE.php', 'en_US' => include $dir. '/text/en_US.php' ];
$kernelWords = [ 'de_DE' => include $root. '/_admin/text/de_DE.php', 'en_US' => include $root. '/_admin/text/en_US.php' ];
preg_match_all( "~'(/_admin/[a-z/_-]*[a-z])'~", $script, $readByScript );
preg_match_all( "~\\bw\\(\\s*'([a-z/_-]+)'~", $script, $readByW );
$wanted = array_merge( [ '/_admin/error/newsletter_copy' ], array_unique( $readByScript[1] ), array_map( static fn( string $name ): string => '/_admin/newsletter/'. $name, array_unique( $readByW[1] ) ) );
$lacking = [];
foreach( $panelWords as $locale => $words )
	foreach( $wanted as $key )
		if( isset( $words[ '[['. $key. ']]' ] ) === false && isset( $kernelWords[$locale][ '[['. $key. ']]' ] ) === false )
			$lacking[] = $locale. ' '. $key;
check( 'every word the script reads - each key of the workbench and each w( name ) - is said in both languages, in the panel\'s own words or in the workbench\'s common ones', $lacking === [] && count( $wanted ) > 20 && array_keys( $panelWords['de_DE'] ) === array_keys( $panelWords['en_US'] ) );
check( 'the panel brings no word of its own for Save, Delete or Cancel: they are the workbench\'s', preg_match( '/newsletter\/label\/(save|delete|cancel)\]\]/', (string) file_get_contents( $dir. '/text/en_US.php' ) ) === 0 );

echo "\n";
echo "The feature off - the data stays\n";

\Nino\Filesystem::putFileContent( $appData, $removedList, [ $removedHash ] );
$count = count( $read() );
check( 'switched off, the two files stay where they are', \Nino\Features::deactivate( $appData, 'newsletter' ) === true && count( $read() ) === $count && \Nino\Filesystem::getFileContent( $appData, $removedList, [] ) === [ $removedHash ] );
$ignored = ninoWarnings();

// The script of the panel runs over a stand-in of the workbench (newsletter-js-smoke.js)
$node = function_exists( 'shell_exec' ) === true ? trim( (string) @shell_exec( 'command -v node 2>/dev/null' ) ) : '';
if( $node === '' || function_exists( 'exec' ) === false )
	echo "  --  - node is not available here: newsletter-js-smoke.js was NOT run\n";
else {
	$output = [];
	$exit = 1;
	exec( 'NINO_ROOT='. escapeshellarg( $root ). ' '. escapeshellarg( $node ). ' '. escapeshellarg( __DIR__. '/newsletter-js-smoke.js' ). ' 2>&1', $output, $exit );
	check( 'newsletter-js-smoke.js passes - '. ( $output === [] ? 'no output' : end( $output ) ), $exit === 0 );
}

ninoDone( $appData );
