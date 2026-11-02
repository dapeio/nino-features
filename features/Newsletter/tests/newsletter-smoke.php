<?php
declare(strict_types=1);

/**
 *	Nino
 *	newsletter-smoke.php	Contract test for the Newsletter feature (Modules\Newsletter):
 *											the manifest and the activation through \Nino\Features,
 *											the double opt-in signup with confirm and unsubscribe
 *											links, the unsubscribe-request form whose session has
 *											lapsed answering with its page and a line instead of the
 *											kernel's bare false, the signup form as the component and the shortcode
 *											[newsletter-signup], the workbench panel and its permission,
 *											and the restore merge that keeps an unsubscribe unsubscribed.
 *											Travels with the feature and runs against the checkout
 *											three levels up, or the one NINO_ROOT names (see
 *											tests/harness.php there).
 *
 *	Usage: php features/Newsletter/tests/newsletter-smoke.php
 *	       NINO_ROOT=../nino php features/Newsletter/tests/newsletter-smoke.php
 */

// The checkout this test runs against: three levels up when the feature sits
// in a project's features/, else the one NINO_ROOT names (a checkout beside
// the catalogue, say). The features root is this feature's own parent either
// way, so the kernel's autoloader serves the class from here
$root = getenv( 'NINO_ROOT' ) ?: dirname( __DIR__, 3 );
defined( 'NINO_FEATURES_DIR' ) === true || define( 'NINO_FEATURES_DIR', dirname( __DIR__, 2 ) );
require $root. '/tests/harness.php';

$appData = ninoSandbox( 'newsletter' );
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$appData['/nino/dir'] = '';

function submitNewsletter( array &$appData, array $post ): array {
	$_POST = array_merge( [ 'email' => '', 'location' => '' ], $post );
	$request = [ '/nino/http/response' => [ 'statusCode' => 200 ] ];
	\Nino\Modules\Newsletter::callbackResponse( $appData, $request );
	return $request;
}

function submitUnsubscribe( array &$appData, array $post ): array {
	$_POST = array_merge( [ 'email' => '', 'location' => '' ], $post );
	$request = [ '/nino/http/response' => [ 'statusCode' => 200 ] ];
	\Nino\Modules\Newsletter::callbackUnsubscribeRequest( $appData, $request );
	return $request;
}

// What it does beyond ninoVisit(): the page's own callback runs on the request, which is what the GET route's response round does
function visitNewsletterLink( array &$appData, array $query ): array {
	$request = ninoVisit( $appData, '/.newsletter'. ( $query === [] ? '' : '?'. http_build_query( $query ) ) );
	\Nino\Modules\Newsletter::callbackLink( $appData, $request );
	return $request;
}

/*	What the button of the page does: the post to /.newsletter/action, its csrf
	check first (the global callback at its place) and the route's own callback
	after it - the round Http::response() makes. $csrf false posts without the
	token the form carries	*/
function performNewsletterLink( array &$appData, array $post, bool $csrf = true ): array {
	$_POST = $post + ( $csrf === true ? [ '_csrf' => \Nino\Csrf::getToken( $appData ) ] : [] );
	$request = ninoVisit( $appData, '/.newsletter/action', 'POST' );
	\Nino\Csrf::callbackResponse( $appData, $request );
	\Nino\Modules\Newsletter::callbackPerform( $appData, $request );
	$_POST = [];
	return $request;
}

/*	A request the way the site serves it: the route resolved, the global and the route's callbacks, then
	the body rendered into html - the page a visitor is shown, to be read as a document. The sandbox has
	no modules, so the two shortcodes the pages draw with and the kernel's csrf guard are put in on a copy
	of the app data, which leaves the mails of the suite as they are (their template calls stay unrendered);
	what the page filled in is handed back, for the checks that read the fills. Posts carry the csrf token
	unless $csrf is false. The head of the page is the kernel's in its one line that matters here - the title and the
	description read from the details of the response's uri - written for the length of the request, and the
	fills of the request (the response's uri among them) are put in the way \Nino\request() does after the routing	*/
function serveNewsletter( array &$appData, string $uri, ?array $post = null, bool $csrf = true ): array {
	$headFile = \Nino\Filesystem::path( $appData, '/templates/html-header.tpl' );
	file_put_contents( $headFile, '<title>[[/_nino/webpage[[/nino/http/response/uri]]/title]]</title><meta name="description" content="[[/_nino/webpage[[/nino/http/response/uri]]/description]]">' );
	$site = $appData;
	\Nino\Csrf::init( $site );
	\Nino\Html::init( $site );
	\Nino\Modules\Template::init( $site );
	\Nino\Modules\Csrf::init( $site );
	$_POST = $post === null ? [] : $post + ( $csrf === true ? [ '_csrf' => \Nino\Csrf::getToken( $site ) ] : [] );
	$request = ninoVisit( $site, $uri, $post === null ? 'GET' : 'POST' );
	\Nino\Http::response( $site, $request );
	\Nino\Html::addFills( $site, \Nino\Html::requestFills( $request, '' ), '*' );
	\Nino\Html::response( $site, $request );
	$_POST = [];
	unlink( $headFile );
	$appData['./nino/html/fills'] = $site['./nino/html/fills'] ?? [];
	return [ 'status' => $request['/nino/http/response']['statusCode'], 'html' => (string) $request['/nino/http/response']['body'], 'uri' => (string) $request['/nino/http/response']['uri'] ];
}

// The head of a served page: [ title, description ] as the document reads them
function newsletterHead( string $html ): array {
	$document = new DOMDocument();
	libxml_use_internal_errors( true );
	$document->loadHTML( '<?xml encoding="UTF-8">'. $html );
	libxml_clear_errors();
	$title = $document->getElementsByTagName( 'title' )->item( 0 );
	$description = '';
	foreach( $document->getElementsByTagName( 'meta' ) as $meta )
		if( $meta->getAttribute( 'name' ) === 'description' )
			$description = $meta->getAttribute( 'content' );
	return [ $title === null ? null : $title->textContent, $description ];
}

// The forms of a served page, as a document: [ [ 'method', 'action', 'fields' => name => value, 'buttons' ], ... ]
function newsletterForms( string $html ): array {
	$document = new DOMDocument();
	libxml_use_internal_errors( true );
	$document->loadHTML( '<?xml encoding="UTF-8">'. $html );
	libxml_clear_errors();
	$forms = [];
	foreach( $document->getElementsByTagName( 'form' ) as $form ) {
		$entry = [ 'method' => strtolower( $form->getAttribute( 'method' ) ), 'action' => $form->getAttribute( 'action' ), 'fields' => [], 'buttons' => 0 ];
		foreach( $form->getElementsByTagName( 'input' ) as $input )
			if( $input->getAttribute( 'type' ) === 'hidden' )
				$entry['fields'][ $input->getAttribute( 'name' ) ] = $input->getAttribute( 'value' );
		foreach( $form->getElementsByTagName( 'button' ) as $button )
			if( $button->getAttribute( 'type' ) === 'submit' )
				$entry['buttons']++;
		$forms[] = $entry;
	}
	return $forms;
}

/*	What the signup forms of a rendered piece of html are, as a document reads them - the parts a visitor,
	Nino.ui.js and the endpoint hold on to: [ [ 'classes', 'method', 'action', 'hidden' => name => value,
	'traps', 'emails', 'labels', 'buttons', 'messages', 'novalidate' ], ... ]. A trap is the field called
	location, an email is the field called email; a label is [ for, classes, text ]; a message is a paragraph
	of the class nino-form-message and whether it is the first paragraph of the form, which Nino.ui.js writes
	into	*/
function newsletterSignupForms( string $html ): array {
	$document = new DOMDocument();
	libxml_use_internal_errors( true );
	$document->loadHTML( '<?xml encoding="UTF-8">'. $html );
	libxml_clear_errors();
	$xpath = new DOMXPath( $document );
	$classes = static fn( DOMElement $element ): array => array_values( array_filter( preg_split( '/\s+/', $element->getAttribute( 'class' ) ) ?: [] ) );
	$forms = [];
	foreach( $document->getElementsByTagName( 'form' ) as $form ) {
		$entry = [ 'classes' => $classes( $form ), 'method' => strtolower( $form->getAttribute( 'method' ) ), 'action' => $form->getAttribute( 'action' ), 'hidden' => [],
			'traps' => [], 'emails' => [], 'labels' => [], 'buttons' => [], 'messages' => [], 'novalidate' => $form->hasAttribute( 'novalidate' ) ];
		foreach( $xpath->query( './/input', $form ) as $input ) {
			if( $input->getAttribute( 'type' ) === 'hidden' )
				$entry['hidden'][ $input->getAttribute( 'name' ) ] = $input->getAttribute( 'value' );
			elseif( $input->getAttribute( 'name' ) === 'location' )
				$entry['traps'][] = [ 'type' => $input->getAttribute( 'type' ), 'value' => $input->getAttribute( 'value' ), 'tabindex' => $input->getAttribute( 'tabindex' ), 'aria-hidden' => $input->getAttribute( 'aria-hidden' ), 'classes' => $classes( $input ) ];
			elseif( $input->getAttribute( 'name' ) === 'email' )
				$entry['emails'][] = [ 'type' => $input->getAttribute( 'type' ), 'id' => $input->getAttribute( 'id' ), 'required' => $input->hasAttribute( 'required' ), 'placeholder' => $input->getAttribute( 'placeholder' ), 'classes' => $classes( $input ) ];
		}
		foreach( $xpath->query( './/label', $form ) as $label )
			$entry['labels'][] = [ 'for' => $label->getAttribute( 'for' ), 'classes' => $classes( $label ), 'text' => trim( $label->textContent ) ];
		foreach( $xpath->query( './/button', $form ) as $button )
			$entry['buttons'][] = [ 'type' => $button->getAttribute( 'type' ), 'classes' => $classes( $button ), 'text' => trim( $button->textContent ) ];
		$first = $xpath->query( './/p', $form )->item( 0 );
		foreach( $xpath->query( './/p', $form ) as $paragraph )
			if( in_array( 'nino-form-message', $classes( $paragraph ), true ) === true )
				$entry['messages'][] = [ 'live' => $paragraph->getAttribute( 'aria-live' ), 'first' => $paragraph->isSameNode( $first ), 'text' => trim( $paragraph->textContent ) ];
		$forms[] = $entry;
	}
	return $forms;
}

// The workbench's entry point, which ninoCall() hands a panel action
$panel = [ \Nino\Admin\Admin::class, 'handlePost' ];


// --- The feature -------------------------------------------------------------

echo "The feature - manifest and activation\n";

$dir = dirname( __DIR__ );
$manifest = \Nino\Features::manifest( $dir );
check( 'the manifest validates, key "newsletter"', is_array( $manifest ) && $manifest['key'] === 'newsletter' && ninoWarnings() === [] );
check( 'the feature is written for this kernel', is_array( $manifest ) && \Nino\Features::satisfies( $manifest['nino'] ) === true );
check( 'it names itself in both interface languages', is_array( $manifest ) && \Nino\Features::localized( $manifest['description'], 'de_DE' ) !== \Nino\Features::localized( $manifest['description'], 'en_US' ) );
check( 'it declares the two data files its restore callback merges', is_array( $manifest ) && $manifest['data'] === [ '/data/newsletter.php', '/data/newsletter-removed.php' ] );

// A project's config.php, written the way the wizard leaves it, so the
// activation has something to add its class and routes to
\Nino\AppData::writeContentData( $appData, [ '/nino/modules', '/nino/locales/available', '/nino/locales/native' ] );
ninoWarnings();

check( 'the Features registry lists it, inactive', ( \Nino\Features::get( $appData, 'newsletter' )['active'] ?? null ) === false );
check( 'activation succeeds', \Nino\Features::activate( $appData, 'newsletter' ) === true );
check( 'the class is listed in /nino/modules and the version recorded', in_array( '\\Nino\\Modules\\Newsletter', \Nino\Filesystem::getFileContent( $appData, '/config.php', [] )['/nino/modules'], true ) === true
	&& \Nino\Features::get( $appData, 'newsletter' )['installed'] === $manifest['version'] );
check( 'the unit copied the pages and the mail templates, the unsubscribe form and mail and the form with the button among them', array_filter( [ 'page-newsletter.tpl', 'newsletter-action.tpl', 'page-newsletter-unsubscribe.tpl', 'mail-newsletter-confirm.tpl', 'mail-newsletter-unsubscribe.tpl', 'mail-header.tpl', 'mail-footer.tpl' ],
	static fn( string $tpl ): bool => \Nino\Filesystem::fileExists( $appData, '/templates/'. $tpl ) === false ) === [] );
check( 'the unit merged the fills for the available locales', \Nino\Filesystem::getFileContent( $appData, '/text/de_DE.php', [] )['[[/template/mail-newsletter-confirm/intro/title]]'] === 'Fast geschafft!'
	&& \Nino\Filesystem::getFileContent( $appData, '/text/en_US.php', [] )['[[/feature/newsletter/label/submit]]'] === 'Subscribe' );
// The email word is the base unit's (/template/common/form/email) and the look
// of the mails too (/project/mail/...): the unit writes neither, and has no
// global text of its own
check( 'it leaves the word for an email address and the look of the mails to the base unit', isset( \Nino\Filesystem::getFileContent( $appData, '/text/en_US.php', [] )['[[/template/common/form/email]]'] ) === false
	&& \Nino\Filesystem::fileExists( $appData, '/text/global.php' ) === false );

\Nino\Modules::callModules( $appData, 'init' );
check( 'init registers the POST route under /.newsletter', isset( $appData['/nino/http/routes']['POST://.newsletter'] ) === true );
check( 'init registers the GET route (confirm/unsubscribe page) under /.newsletter', isset( $appData['/nino/http/routes']['GET://.newsletter'] ) === true );
check( 'init registers the unsubscribe form (GET) and its post under /.newsletter/unsubscribe', isset( $appData['/nino/http/routes']['GET://.newsletter/unsubscribe'] ) === true
	&& isset( $appData['/nino/http/routes']['POST://.newsletter/unsubscribe'] ) === true
	&& isset( $appData['./nino/callbacks']['/nino/http/response/POST://.newsletter/unsubscribe'] ) === true );
check( 'init registers the second step of a link as a post under /.newsletter/action, with its own callback, and no page to get there', isset( $appData['/nino/http/routes']['POST://.newsletter/action'] ) === true
	&& isset( $appData['./nino/callbacks']['/nino/http/response/POST://.newsletter/action'] ) === true
	&& \Nino\Http::requestRoute( $appData, '/.newsletter/action', 'GET' ) === null );
check( '...the form with its own template as the body, not the page the links land on', ( $appData['/nino/http/routes']['GET://.newsletter/unsubscribe']['body'] ?? '' ) === '[template /templates/page-newsletter-unsubscribe]' );
check( 'init registers the restore merge on /nino/admin/restore', isset( $appData['./nino/callbacks']['/nino/admin/restore'] ) === true );

/*	A transport takes the mail so nothing is actually sent - the same
	\Nino\Mail::TRANSPORT callback the Mailer feature registers. Without one the
	signup would call the real mail(), and the answer follows the delivery now:
	a host with no sendmail answers 500. $transportResult is what the stub says
	about the mail, so a refusal is as easy to make as a delivery.

	\Nino\Mail::send() caps a client ip at five mails an hour, and the answer
	follows that too - 429 - so every check on a status starts from a fresh
	window	*/
$mails = [];
$sentLinks = [];
$transportResult = true;
\Nino\Callbacks::registerCallback( $appData, \Nino\Mail::TRANSPORT, static function( array &$appData, array &$mail ) use ( &$mails, &$sentLinks, &$transportResult ): void {
	$mails[] = $mail;
	// The sandbox has no template shortcode, so the link a mail carries is what the
	// class had set as its fill at the moment it was sent - and the fill does not
	// stay (see below), so it is read here
	$sentLinks[] = [
		'confirm'			=> (string) ( $appData['./nino/html/fills']['*']['[[/feature/newsletter/confirm/url]]'] ?? '' ),
		'unsubscribe'	=> (string) ( $appData['./nino/html/fills']['*']['[[/feature/newsletter/unsubscribe/url]]'] ?? '' ),
	];
	$mail['sent'] = $transportResult;
} );
$sentLink = static function( string $kind ) use ( &$sentLinks ): string {
	return $sentLinks === [] ? '' : $sentLinks[ array_key_last( $sentLinks ) ][$kind];
};

/*	The website's address, which every link of a mail and of the panel is built
	on (\Nino\Html::absoluteUrl()). A site served from a directory, so that the
	directory is seen to be kept in them	*/
$siteAddress = 'example.org/shop';
$siteUrl = 'https://'. $siteAddress;
\Nino\Html::addFills( $appData, [ '[[/project/website/general/url]]' => $siteAddress ], '*' );

/**
 *	A fresh window of the mail cap for this client, and the same with the cap
 *	spent: five tries counted the way \Nino\Mail counts one, so the next send is
 *	the sixth
 */
$freshMailWindow = static function( array &$appData ): void {
	\Nino\Feature::forget( $appData, 'ratelimit', '127.0.0.1' );
};
$spentMailWindow = static function( array &$appData ) use ( $freshMailWindow ): void {
	$freshMailWindow( $appData );
	for( $try = 0; $try < 5; $try++ )
		\Nino\Feature::count( $appData, 'ratelimit', '127.0.0.1', 5, 3600 );
};

echo "\n";


// --- Signup, confirm, unsubscribe ----------------------------------------------

echo "Modules\\Newsletter - double opt-in signup, confirm, unsubscribe\n";

$missingEmailRequest = submitNewsletter( $appData, [] );
check( 'a missing email is rejected (400)', $missingEmailRequest['/nino/http/response']['statusCode'] === 400 );

$invalidEmailRequest = submitNewsletter( $appData, [ 'email' => 'not-an-email' ] );
check( 'an invalid email is rejected (400)', $invalidEmailRequest['/nino/http/response']['statusCode'] === 400 );

$honeypotRequest = submitNewsletter( $appData, [ 'email' => 'jo@example.com', 'location' => 'filled-by-a-bot' ] );
check( 'a filled honeypot is rejected (418)', $honeypotRequest['/nino/http/response']['statusCode'] === 418 );

check( 'none of the rejected signups created the newsletter file', is_file( \Nino\Filesystem::path( $appData, '/data/newsletter.php' ) ) === false );

$_POST['_csrf'] = 'wrong-token';
$blockedNewsletterRequest = [ '/nino/http/request' => [ 'method' => 'POST' ], '/nino/http/response' => [ 'statusCode' => 200 ] ];
\Nino\Csrf::callbackResponse( $appData, $blockedNewsletterRequest ); // sets 403 + the blocked flag, same as the real POST pipeline
$_POST = array_merge( $_POST, [ 'email' => 'jo@example.com', 'location' => '' ] );
\Nino\Modules\Newsletter::callbackResponse( $appData, $blockedNewsletterRequest );
check( 'a csrf-blocked signup is rejected too', $blockedNewsletterRequest['/nino/http/response']['statusCode'] === 403 );
check( 'a csrf-blocked signup does not create the newsletter file', is_file( \Nino\Filesystem::path( $appData, '/data/newsletter.php' ) ) === false );
unset( $_POST['_csrf'] );

$freshMailWindow( $appData );
$okNewsletterRequest = submitNewsletter( $appData, [ 'email' => 'jo@example.com' ] );
check( 'a valid signup succeeds (200) - the mail went out', $okNewsletterRequest['/nino/http/response']['statusCode'] === 200 && count( $mails ) === 1 );
check( 'a valid new signup reports the generic status', $okNewsletterRequest['/nino/http/response']['body']['status'] === 'ok' );
check( 'a valid signup bootstraps the newsletter file on the private root', is_file( \Nino\Filesystem::path( $appData, '/data/newsletter.php' ) ) === true );

$subscribersPath 	= '/data/newsletter.php';
$subscribersFile 	= \Nino\Filesystem::path( $appData, $subscribersPath );

$subscribers = \Nino\Filesystem::getFileContent( $appData, $subscribersPath, [] );
check( 'exactly one entry was recorded', count( $subscribers ) === 1 );
check( 'the entry is pending, not subscribed - the form submit alone must not subscribe', ( $subscribers[0]['status'] ?? '' ) === 'pending' );
check( 'the entry has the submitted email, a confirm token, a date and ip', $subscribers[0]['email'] === 'jo@example.com' && empty( $subscribers[0]['token'] ) === false && isset( $subscribers[0]['date'] ) === true && $subscribers[0]['ip'] === '127.0.0.1' );

check( 'the file is a plain, human-readable php array file - not an encoded stub', str_starts_with( (string) file_get_contents( $subscribersFile ), "<?php return array (" ) === true );

$pendingToken = $subscribers[0]['token'];

$freshMailWindow( $appData );
$dupeRequest = submitNewsletter( $appData, [ 'email' => 'jo@example.com' ] );
check( 'a repeated signup while still pending succeeds (200)', $dupeRequest['/nino/http/response']['statusCode'] === 200 );
check( 'and reports the same generic status - the confirm mail is simply re-sent', $dupeRequest['/nino/http/response']['body']['status'] === 'ok' );
$subscribers = \Nino\Filesystem::getFileContent( $appData, $subscribersPath, [] );
check( 'without creating a duplicate entry', count( $subscribers ) === 1 );
check( 'and without rotating the pending token', $subscribers[0]['token'] === $pendingToken );

// Nothing says a post or a query carries strings. 'email[]=x' and
// '?confirm[]=x' used to reach a (string) cast, and the warning that raises
// is fatal to the request (see \Nino\Runtime::NON_FATAL_LEVELS) - an
// unauthenticated 500 from an address anybody can visit
ninoWarnings();
$arraySignupRequest = submitNewsletter( $appData, [ 'email' => [ 'jo@example.com' ] ] );
check( 'a signup whose email is an array is refused, not raised at', ninoWarnings() === [] && $arraySignupRequest['/nino/http/response']['statusCode'] === 400 );

$arrayHoneypotRequest = submitNewsletter( $appData, [ 'email' => 'jo@example.com', 'location' => [ 'x' ] ] );
check( 'and an array in the honeypot is a refusal like any other value in it', ninoWarnings() === [] && $arrayHoneypotRequest['/nino/http/response']['statusCode'] === 418 );

$arrayConfirmRequest = visitNewsletterLink( $appData, [ 'confirm' => [ 'x' ] ] );
check( 'a confirm link whose token is an array answers 404, not a 500', ninoWarnings() === [] && $arrayConfirmRequest['/nino/http/response']['statusCode'] === 404 );

$arrayUnsubscribeRequest = visitNewsletterLink( $appData, [ 'unsubscribe' => [ 'x' ] ] );
check( '...and so does an unsubscribe link', ninoWarnings() === [] && $arrayUnsubscribeRequest['/nino/http/response']['statusCode'] === 404 );

$arrayPerformRequest = performNewsletterLink( $appData, [ 'confirm' => [ 'x' ] ] );
check( '...and the post of the button, for either field', ninoWarnings() === [] && $arrayPerformRequest['/nino/http/response']['statusCode'] === 404
	&& performNewsletterLink( $appData, [ 'unsubscribe' => [ 'x' ] ] )['/nino/http/response']['statusCode'] === 404 && ninoWarnings() === [] );

$wrongTokenRequest = visitNewsletterLink( $appData, [ 'confirm' => 'not-the-token' ] );
check( 'a confirm link with an unknown token answers 404', $wrongTokenRequest['/nino/http/response']['statusCode'] === 404 );
check( 'and leaves the entry pending', \Nino\Filesystem::getFileContent( $appData, $subscribersPath, [] )[0]['status'] === 'pending' );
check( 'and prepares the "invalid" page fills, with no form on it', ( $appData['./nino/html/fills']['*']['[[/feature/newsletter/page/title]]'] ?? '' ) === '[[/feature/newsletter/result-invalid/title]]'
	&& ( $appData['./nino/html/fills']['*']['[[/feature/newsletter/page/form]]'] ?? 'unset' ) === '' );

/*	The link is the first step and it changes nothing. A mail scanner opens every link of a mail
	ahead of the reader - a GET that confirmed or removed made it the one who decides. What the visit
	answers instead is a page with a form: it posts to /.newsletter/action, carries the csrf token
	and the token of the link in hidden fields and has the button that is the second step	*/
$linkPage = serveNewsletter( $appData, '/.newsletter?confirm='. $pendingToken );
$linkForms = newsletterForms( $linkPage['html'] );
check( 'a confirm link with the mailed token answers 200 and shows a page with a form: a post to /.newsletter/action, one submit button', $linkPage['status'] === 200
	&& count( $linkForms ) === 1 && $linkForms[0]['method'] === 'post' && str_ends_with( $linkForms[0]['action'], '/.newsletter/action' ) === true && $linkForms[0]['buttons'] === 1 );
check( '...the token of the link in a hidden field named confirm, and the csrf token in _csrf - nothing else is posted', $linkForms[0]['fields'] === [ '_csrf' => \Nino\Csrf::getToken( $appData ), 'confirm' => $pendingToken ] );
check( '...and the visit confirmed nothing: the entry is still pending', \Nino\Filesystem::getFileContent( $appData, $subscribersPath, [] )[0]['status'] === 'pending' );
check( '...the page asks for the click: the "pending confirm" title and text, not the outcome', ( $appData['./nino/html/fills']['*']['[[/feature/newsletter/page/title]]'] ?? '' ) === '[[/feature/newsletter/result-pending-confirm/title]]'
	&& ( $appData['./nino/html/fills']['*']['[[/feature/newsletter/page/text]]'] ?? '' ) === '[[/feature/newsletter/result-pending-confirm/text]]'
	&& ( $appData['./nino/html/fills']['*']['[[/feature/newsletter/action/button]]'] ?? '' ) === '[[/feature/newsletter/action/confirm]]' );
check( '...the same page for the visit that scanners make, as often as they make it', serveNewsletter( $appData, '/.newsletter?confirm='. $pendingToken )['status'] === 200
	&& \Nino\Filesystem::getFileContent( $appData, $subscribersPath, [] )[0]['status'] === 'pending' );

$withoutToken = performNewsletterLink( $appData, [ 'confirm' => $pendingToken ], false );
check( 'the post without the csrf token does nothing (403)', $withoutToken['/nino/http/response']['statusCode'] === 403 && \Nino\Filesystem::getFileContent( $appData, $subscribersPath, [] )[0]['status'] === 'pending' );
$wrongCsrf = performNewsletterLink( $appData, [ 'confirm' => $pendingToken, '_csrf' => 'wrong-token' ], false );
check( '...nor with a wrong one', $wrongCsrf['/nino/http/response']['statusCode'] === 403 && \Nino\Filesystem::getFileContent( $appData, $subscribersPath, [] )[0]['status'] === 'pending' );

/*	The kernel's guard answers a post without its token 403 with the body false, which is json: a
	visitor whose session cookie is gone would read a bare "false". The status stays, the body is the page -
	Http only encodes a body that is no string, whatever the status is - and the page says what is true:
	this one has expired, and the link of the mail opens it again	*/
$expiredPage = serveNewsletter( $appData, '/.newsletter/action', [ 'confirm' => $pendingToken ], false );
check( 'a post without the csrf token keeps its 403 and answers with a page, not the bare false - the page that says it has expired, with no form', $expiredPage['status'] === 403 && $expiredPage['html'] !== ''
	&& newsletterForms( $expiredPage['html'] ) === [] && ( $appData['./nino/html/fills']['*']['[[/feature/newsletter/page/title]]'] ?? '' ) === '[[/feature/newsletter/result-expired/title]]'
	&& ( $appData['./nino/html/fills']['*']['[[/feature/newsletter/page/text]]'] ?? '' ) === '[[/feature/newsletter/result-expired/text]]' );
check( '...and changes nothing: the entry is still pending', \Nino\Filesystem::getFileContent( $appData, $subscribersPath, [] )[0]['status'] === 'pending' );
$expiredWrong = serveNewsletter( $appData, '/.newsletter/action', [ 'unsubscribe' => $pendingToken, '_csrf' => 'wrong-token' ], false );
check( '...the same for a wrong token, and for the unsubscribe field: the page, 403, the entry still on the list', $expiredWrong['status'] === 403 && $expiredWrong['html'] !== '' && newsletterForms( $expiredWrong['html'] ) === []
	&& count( \Nino\Filesystem::getFileContent( $appData, $subscribersPath, [] ) ) === 1 );

$unknownPost = performNewsletterLink( $appData, [ 'confirm' => 'not-the-token' ] );
check( 'a post with an unknown token answers 404 and leaves the entry pending', $unknownPost['/nino/http/response']['statusCode'] === 404 && \Nino\Filesystem::getFileContent( $appData, $subscribersPath, [] )[0]['status'] === 'pending'
	&& ( $appData['./nino/html/fills']['*']['[[/feature/newsletter/page/title]]'] ?? '' ) === '[[/feature/newsletter/result-invalid/title]]' );
check( 'a post with both fields is the invalid page - the entry stays pending and stays listed', performNewsletterLink( $appData, [ 'confirm' => $pendingToken, 'unsubscribe' => $pendingToken ] )['/nino/http/response']['statusCode'] === 404
	&& \Nino\Filesystem::getFileContent( $appData, $subscribersPath, [] )[0]['status'] === 'pending' );
check( '...and so is one with neither', performNewsletterLink( $appData, [] )['/nino/http/response']['statusCode'] === 404 && \Nino\Filesystem::getFileContent( $appData, $subscribersPath, [] )[0]['status'] === 'pending' );
check( '...and a visit with both of them in the query', visitNewsletterLink( $appData, [ 'confirm' => $pendingToken, 'unsubscribe' => $pendingToken ] )['/nino/http/response']['statusCode'] === 404 );
check( 'a GET of /.newsletter/action is the site\'s 404: there is no page there', serveNewsletter( $appData, '/.newsletter/action?confirm='. $pendingToken )['status'] === 404 && \Nino\Filesystem::getFileContent( $appData, $subscribersPath, [] )[0]['status'] === 'pending' );

$confirmPage = serveNewsletter( $appData, '/.newsletter/action', [ 'confirm' => $pendingToken ] );
check( 'the post of the button with the mailed token answers 200', $confirmPage['status'] === 200 );
check( 'and flips the entry to subscribed', \Nino\Filesystem::getFileContent( $appData, $subscribersPath, [] )[0]['status'] === 'subscribed' );
check( 'and prepares the "confirmed" page fills, with no form left on it', ( $appData['./nino/html/fills']['*']['[[/feature/newsletter/page/title]]'] ?? '' ) === '[[/feature/newsletter/result-confirmed/title]]'
	&& newsletterForms( $confirmPage['html'] ) === [] );

/*	The head of the page reads the title and the description of the response's uri - the page template
	starts with the frame that does. The route of the post has no details of a page of its own, and the
	answer is a page of /.newsletter, so the response says that uri and the head has the words of that page
	in every answer, not the key as it stands	*/
$detailWords = static function( string $detail ) use ( $appData ): array {
	return array_map( static fn( string $textLocale ): string => (string) ( \Nino\Filesystem::getFileContent( $appData, '/text/'. $textLocale. '.php', [] )['[[/_nino/webpage/.newsletter/'. $detail. ']]'] ?? '' ), [ 'en_US', 'de_DE' ] );
};
$answers = [ 'the outcome' => $confirmPage, 'the invalid page' => serveNewsletter( $appData, '/.newsletter/action', [ 'confirm' => 'not-the-token' ] ), 'the expired page' => $expiredPage ];
foreach( $answers as $answerName => $answer ) {
	[ $headTitle, $headDescription ] = newsletterHead( $answer['html'] );
	check( "$answerName of the post is a page of /.newsletter: that response uri, and the title and the description of its head are the words of that page", $answer['uri'] === '/.newsletter'
		&& in_array( $headTitle, $detailWords( 'title' ), true ) === true && in_array( $headDescription, $detailWords( 'description' ), true ) === true );
}

$reconfirmRequest = performNewsletterLink( $appData, [ 'confirm' => $pendingToken ] );
check( 'confirming twice stays a friendly 200, not an error', $reconfirmRequest['/nino/http/response']['statusCode'] === 200 );

/*	A confirm token is not consumed - the entry keeps it, and the mail of a signup that is made again
	carries it - so the link opened a second time still answers. What it shows is the page of the
	confirmation, with no button: there is nothing left to press, and the visit changes nothing	*/
$secondVisit = serveNewsletter( $appData, '/.newsletter?confirm='. $pendingToken );
check( 'the link opened again after the confirmation answers 200 with the page of the confirmation and no button', $secondVisit['status'] === 200 && newsletterForms( $secondVisit['html'] ) === []
	&& ( $appData['./nino/html/fills']['*']['[[/feature/newsletter/page/title]]'] ?? '' ) === '[[/feature/newsletter/result-confirmed/title]]'
	&& \Nino\Filesystem::getFileContent( $appData, $subscribersPath, [] )[0]['status'] === 'subscribed' );
$listWithJo = \Nino\Filesystem::getFileContent( $appData, $subscribersPath, [] );
\Nino\Filesystem::putFileContent( $appData, $subscribersPath, [ [ 'email' => 'old@example.com', 'token' => 'old0token', 'date' => '2025-05-06 07:08:09' ] ] );
$legacyVisit = serveNewsletter( $appData, '/.newsletter?confirm=old0token' );
check( '...an entry from before the double opt-in flow - a token and no status - is not confirmed yet: it gets the button, and the visit gives it no status', $legacyVisit['status'] === 200 && count( newsletterForms( $legacyVisit['html'] ) ) === 1
	&& isset( \Nino\Filesystem::getFileContent( $appData, $subscribersPath, [] )[0]['status'] ) === false );
\Nino\Filesystem::putFileContent( $appData, $subscribersPath, $listWithJo );

$freshMailWindow( $appData );
$mails = [];
$subscribedRequest = submitNewsletter( $appData, [ 'email' => 'jo@example.com' ] );
// The response must not distinguish a known address from a new one - that
// would let anyone test whether a given address is subscribed
check( 'signing up a confirmed subscriber again reports the same generic status', $subscribedRequest['/nino/http/response']['statusCode'] === 200 && $subscribedRequest['/nino/http/response']['body']['status'] === 'ok' );
check( 'and does not create a duplicate entry', count( \Nino\Filesystem::getFileContent( $appData, $subscribersPath, [] ) ) === 1 );
// The sandbox has no template shortcode, so the mail's body is the template
// call as it stands - the link it carries is the fill the class sets for it
check( 'it is mailed exactly like a new address is - once, with the token it already has', count( $mails ) === 1
	&& $sentLink( 'confirm' ) === $siteUrl. '/.newsletter?confirm='. $pendingToken );
check( '...and stays subscribed, the mail being no signup', \Nino\Filesystem::getFileContent( $appData, $subscribersPath, [] )[0]['status'] === 'subscribed' );

$unsubscribeLink = \Nino\Modules\Newsletter::getUnsubscribeLink( $appData, 'jo@example.com' );
check( 'getUnsubscribeLink builds the /.newsletter unsubscribe url for a subscriber', is_string( $unsubscribeLink ) === true && str_contains( $unsubscribeLink, '/.newsletter?unsubscribe='. $pendingToken ) === true );
check( 'getUnsubscribeLink is case-insensitive about the email', \Nino\Modules\Newsletter::getUnsubscribeLink( $appData, 'JO@example.com' ) === $unsubscribeLink );
check( 'getUnsubscribeLink returns false for an unknown email', \Nino\Modules\Newsletter::getUnsubscribeLink( $appData, 'nobody@example.com' ) === false );

$badUnsubscribeRequest = visitNewsletterLink( $appData, [ 'unsubscribe' => 'not-the-token' ] );
check( 'an unsubscribe link with an unknown token answers 404 and removes nothing', $badUnsubscribeRequest['/nino/http/response']['statusCode'] === 404 && count( \Nino\Filesystem::getFileContent( $appData, $subscribersPath, [] ) ) === 1 );
check( '...and so does the post of the button', performNewsletterLink( $appData, [ 'unsubscribe' => 'not-the-token' ] )['/nino/http/response']['statusCode'] === 404 && count( \Nino\Filesystem::getFileContent( $appData, $subscribersPath, [] ) ) === 1 );

$unsubscribeLinkPage = serveNewsletter( $appData, '/.newsletter?unsubscribe='. $pendingToken );
$unsubscribeForms = newsletterForms( $unsubscribeLinkPage['html'] );
check( 'an unsubscribe link with the subscriber\'s token answers 200 and shows a page with a form posting it to /.newsletter/action, in a hidden field named unsubscribe', $unsubscribeLinkPage['status'] === 200
	&& count( $unsubscribeForms ) === 1 && $unsubscribeForms[0]['method'] === 'post' && str_ends_with( $unsubscribeForms[0]['action'], '/.newsletter/action' ) === true && $unsubscribeForms[0]['buttons'] === 1
	&& $unsubscribeForms[0]['fields'] === [ '_csrf' => \Nino\Csrf::getToken( $appData ), 'unsubscribe' => $pendingToken ] );
check( '...with the "pending unsubscribe" words and the unsubscribe button', ( $appData['./nino/html/fills']['*']['[[/feature/newsletter/page/title]]'] ?? '' ) === '[[/feature/newsletter/result-pending-unsubscribe/title]]'
	&& ( $appData['./nino/html/fills']['*']['[[/feature/newsletter/action/button]]'] ?? '' ) === '[[/feature/newsletter/action/unsubscribe]]' );
check( '...and the visit removed nothing: the entry is on the list, no removal is recorded', count( \Nino\Filesystem::getFileContent( $appData, $subscribersPath, [] ) ) === 1
	&& \Nino\Filesystem::getFileContent( $appData, '/data/newsletter-removed.php', [] ) === [] );

$unsubscribeWithoutCsrf = performNewsletterLink( $appData, [ 'unsubscribe' => $pendingToken ], false );
check( 'the post without the csrf token removes nothing (403)', $unsubscribeWithoutCsrf['/nino/http/response']['statusCode'] === 403 && count( \Nino\Filesystem::getFileContent( $appData, $subscribersPath, [] ) ) === 1
	&& \Nino\Filesystem::getFileContent( $appData, '/data/newsletter-removed.php', [] ) === [] );

$unsubscribePage = serveNewsletter( $appData, '/.newsletter/action', [ 'unsubscribe' => $pendingToken ] );
check( 'the post of the button with the subscriber\'s token answers 200', $unsubscribePage['status'] === 200 );
check( 'and removes the entry from the list', count( \Nino\Filesystem::getFileContent( $appData, $subscribersPath, [] ) ) === 0 );
check( 'and prepares the "unsubscribed" page fills, with no form left on it', ( $appData['./nino/html/fills']['*']['[[/feature/newsletter/page/title]]'] ?? '' ) === '[[/feature/newsletter/result-unsubscribed/title]]'
	&& newsletterForms( $unsubscribePage['html'] ) === [] );

// An unsubscribe token goes with its entry: the link opened a second time is a link like any unknown one
$secondUnsubscribeVisit = serveNewsletter( $appData, '/.newsletter?unsubscribe='. $pendingToken );
check( 'the link opened again after the unsubscribe is the invalid page (404), without a form', $secondUnsubscribeVisit['status'] === 404 && newsletterForms( $secondUnsubscribeVisit['html'] ) === [] );

// callbackRestore() relies on this record surviving every unsubscribe - see
// the restore section below. A sha256 of the address, not the address itself
$joRemovalHash = hash( 'sha256', 'jo@example.com' );
check( 'the unsubscribe is recorded, for a later restore not to undo it', in_array( $joRemovalHash, \Nino\Filesystem::getFileContent( $appData, '/data/newsletter-removed.php', [] ), true ) === true );

$freshMailWindow( $appData );
$resubscribeRequest = submitNewsletter( $appData, [ 'email' => 'jo@example.com' ] );
check( 'resubscribing after an unsubscribe succeeds', $resubscribeRequest['/nino/http/response']['statusCode'] === 200 );
check( 'and creates a fresh pending entry', count( \Nino\Filesystem::getFileContent( $appData, $subscribersPath, [] ) ) === 1 );
check( 'and clears the earlier removal record - a fresh signup is a fresh consent', in_array( $joRemovalHash, \Nino\Filesystem::getFileContent( $appData, '/data/newsletter-removed.php', [] ), true ) === false );

$bareVisitRequest = visitNewsletterLink( $appData, [] );
check( 'a bare GET /.newsletter without confirm/unsubscribe answers 404', $bareVisitRequest['/nino/http/response']['statusCode'] === 404 );

echo "\n";


// --- The answer follows the delivery ------------------------------------------

echo "Modules\\Newsletter - the answer follows the delivery and nothing about the list\n";

$writeList = static function( array &$appData, array $entries ): void {
	\Nino\Filesystem::putFileContent( $appData, '/data/newsletter.php', $entries );
};
$readList = static function( array &$appData ): array {
	return \Nino\Filesystem::getFileContent( $appData, '/data/newsletter.php', [] );
};
$entryAt = static fn( string $email, string $status, string $date ): array => [
	'email' => $email, 'token' => bin2hex( random_bytes( 8 ) ), 'status' => $status, 'date' => $date, 'ip' => '203.0.113.7',
];


$listBefore 		= $readList( $appData );
$removedBefore	= \Nino\Filesystem::getFileContent( $appData, '/data/newsletter-removed.php', [] );

/*	An address on the list and one that is not take the same way through the
	mail cap and the transport, so what the visitor is answered says what
	happened to the mail and nothing about who is subscribed: 429 when the cap
	refused it, 500 when it could not be sent or the entry could not be stored,
	200 when it went out. An answer that only some addresses could reach would
	be a free test of whether an address is on the list	*/
$memberSince = '2026-01-02 03:04:05';
$memberEntry = $entryAt( 'member@example.com', 'subscribed', $memberSince );
$resetList = static function( array &$appData ) use ( $writeList, $memberEntry, $entryAt ): void {
	$writeList( $appData, [
		$memberEntry,
		$entryAt( 'waiting@example.com', 'pending', date( 'Y-m-d H:i:s', time() - 3600 ) ),
	] );
};
$answer = static function( array &$appData, string $email ) use ( $freshMailWindow, &$mails ): array {
	$freshMailWindow( $appData );
	$mails = [];
	$request = submitNewsletter( $appData, [ 'email' => $email ] );
	return [ $request['/nino/http/response']['statusCode'], $request['/nino/http/response']['body'] ?? null, count( $mails ) ];
};
$addresses = [ 'new' => 'brand-new@example.com', 'pending' => 'waiting@example.com', 'subscribed' => 'member@example.com' ];

$resetList( $appData );
$transportResult = false;
$refused = array_map( static fn( string $email ): array => $answer( $appData, $email ), $addresses );
$transportResult = true;
check( 'a transport that refuses the mail answers 500, with no body, for a new, a pending and a subscribed address alike',
	array_unique( array_column( $refused, 0 ) ) === [ 500 ] && array_unique( array_column( $refused, 1 ), SORT_REGULAR ) === [ null ] );
check( '...every one of them having been handed to the transport once', array_unique( array_column( $refused, 2 ) ) === [ 1 ] );
check( '...the pending entry is kept and the new one recorded, a re-submit re-sends it',
	array_column( array_filter( $readList( $appData ), static fn( array $e ): bool => ( $e['status'] ?? '' ) === 'pending' ), 'email' ) === [ 'waiting@example.com', 'brand-new@example.com' ] );

$resetList( $appData );
$capped = [];
foreach( $addresses as $kind => $email ) {
	$spentMailWindow( $appData );
	$mails = [];
	$request = submitNewsletter( $appData, [ 'email' => $email ] );
	$capped[$kind] = [ $request['/nino/http/response']['statusCode'], $request['/nino/http/response']['body'] ?? null, count( $mails ) ];
}
check( 'an ip at its mail cap is answered 429 - no body, nothing sent - for a new, a pending and a subscribed address alike',
	array_unique( array_column( $capped, 0 ) ) === [ 429 ] && array_unique( array_column( $capped, 1 ), SORT_REGULAR ) === [ null ] && array_unique( array_column( $capped, 2 ) ) === [ 0 ] );

/*	What the cap said belongs to that call: the kernel puts the flag down at the
	start of every send, so the signup after a capped one, refused by the
	transport this time, is not answered 429 - and nothing in this class unsets
	the flag for it	*/
$transportResult = false;
[ $afterCapStatus ] = $answer( $appData, 'brand-new@example.com' );
$transportResult = true;
check( 'a signup that the transport refuses right after a capped one is answered 500, not 429 again', $afterCapStatus === 500 );

$resetList( $appData );
$delivered = array_map( static fn( string $email ): array => $answer( $appData, $email ), $addresses );
check( 'a delivered mail is a 200 {status: ok} for all three, each of them mailed once',
	array_unique( array_column( $delivered, 0 ) ) === [ 200 ] && array_unique( array_column( $delivered, 1 ), SORT_REGULAR ) === [ [ 'status' => 'ok' ] ] && array_unique( array_column( $delivered, 2 ) ) === [ 1 ] );

/*	Its stored token, and nothing else of it touched: confirming that mail
	changes nothing, and the signup is no fresh consent to clear a removal for	*/
$memberToken = $memberEntry['token'];
$resetList( $appData );
$memberHash = hash( 'sha256', 'member@example.com' );
\Nino\Filesystem::putFileContent( $appData, '/data/newsletter-removed.php', [ $memberHash ] );
$answer( $appData, 'member@example.com' );
$memberNow = array_values( array_filter( $readList( $appData ), static fn( array $e ): bool => $e['email'] === 'member@example.com' ) );
check( 'a subscribed address that signs up again is mailed the confirm link with the token it already has',
	$sentLink( 'confirm' ) === $siteUrl. '/.newsletter?confirm='. rawurlencode( $memberToken ) && count( $mails ) === 1 );
check( '...and stays what it was: one entry, subscribed, the same token and date', count( $memberNow ) === 1 && $memberNow[0] === $memberEntry );
check( '...and pressing the button of that link changes nothing', performNewsletterLink( $appData, [ 'confirm' => $memberToken ] )['/nino/http/response']['statusCode'] === 200
	&& array_values( array_filter( $readList( $appData ), static fn( array $e ): bool => $e['email'] === 'member@example.com' ) ) === [ $memberEntry ] );
check( '...and a removal recorded for the address is not cleared by it', in_array( $memberHash, \Nino\Filesystem::getFileContent( $appData, '/data/newsletter-removed.php', [] ), true ) === true );
\Nino\Filesystem::putFileContent( $appData, '/data/newsletter-removed.php', [] );

// An entry from before the double opt-in flow has no token to put in a mail
$legacyDate = date( 'Y-m-d H:i:s', time() - 900 * 86400 );
$writeList( $appData, [ [ 'email' => 'legacy@example.com', 'date' => $legacyDate ] ] );
[ $legacyStatus ] = $answer( $appData, 'legacy@example.com' );
$legacyNow = $readList( $appData );
check( 'a legacy entry without a token is mailed one, stored in the same write', $legacyStatus === 200 && count( $mails ) === 1 && count( $legacyNow ) === 1
	&& empty( $legacyNow[0]['token'] ) === false
	&& $sentLink( 'confirm' ) === $siteUrl. '/.newsletter?confirm='. rawurlencode( $legacyNow[0]['token'] ) );
check( '...its status and date left as they were - it is still subscribed', array_diff_key( $legacyNow[0], [ 'token' => 1 ] ) === [ 'email' => 'legacy@example.com', 'date' => $legacyDate ] );

// Confirming that token is the first confirmation the entry has: the status is
// recorded, and the date is still the day it signed up on, not the day it clicked
$legacyConfirm = performNewsletterLink( $appData, [ 'confirm' => (string) $legacyNow[0]['token'] ] );
$legacyConfirmed = $readList( $appData );
check( 'confirming a legacy entry\'s token records it as subscribed', $legacyConfirm['/nino/http/response']['statusCode'] === 200 && ( $legacyConfirmed[0]['status'] ?? '' ) === 'subscribed' );
check( '...and keeps the date it signed up on', ( $legacyConfirmed[0]['date'] ?? '' ) === $legacyDate && $legacyConfirmed[0]['token'] === $legacyNow[0]['token'] );
$pendingDate = date( 'Y-m-d H:i:s', time() - 3 * 86400 );
$writeList( $appData, [ [ 'email' => 'waiting@example.com', 'token' => 'f00dfeed', 'status' => 'pending', 'date' => $pendingDate, 'ip' => '127.0.0.1' ] ] );
performNewsletterLink( $appData, [ 'confirm' => 'f00dfeed' ] );
check( '...while a pending entry gets the date of its confirmation', ( $readList( $appData )[0]['date'] ?? $pendingDate ) !== $pendingDate && ( $readList( $appData )[0]['status'] ?? '' ) === 'subscribed' );
$writeList( $appData, [ [ 'email' => 'legacy@example.com', 'date' => $legacyDate ] ] );
$answer( $appData, 'legacy@example.com' );

// The link is a credential and the fill is set for every language: it must not be there
// after the mail, for the response of the same request to find
check( 'the confirmation link is no fill any more once the mail is out', isset( $appData['./nino/html/fills']['*']['[[/feature/newsletter/confirm/url]]'] ) === false
	&& isset( $appData['./nino/html/fills']['en_US']['[[/feature/newsletter/confirm/url]]'] ) === false );

/*	A list that cannot be locked or written is no mail either: nothing was
	recorded, so there is no token to put in one - and the visitor is told so
	(500), not given the success text for a mail that never left	*/
$resetList( $appData );
$locks = \Nino\Filesystem::path( $appData, '/data/.locks' );
$locksAside = $locks. '-aside';
rename( $locks, $locksAside );
file_put_contents( $locks, 'not a directory' );
ninoWarnings();
[ $unlockableStatus, $unlockableBody, $unlockableMails ] = $answer( $appData, 'brand-new@example.com' );
ninoWarnings();
unlink( $locks );
rename( $locksAside, $locks );
check( 'a list that cannot be locked answers 500 and sends nothing', $unlockableStatus === 500 && $unlockableBody === null && $unlockableMails === 0
	&& in_array( 'brand-new@example.com', array_column( $readList( $appData ), 'email' ), true ) === false );

// What the sections that follow start from
$writeList( $appData, $listBefore );
\Nino\Filesystem::putFileContent( $appData, '/data/newsletter-removed.php', $removedBefore );
$freshMailWindow( $appData );

echo "\n";


// --- The way out without a link --------------------------------------------------

echo "Modules\\Newsletter - /.newsletter/unsubscribe: an address in, a mail with the link out\n";

$listBefore 		= $readList( $appData );
$removedBefore	= \Nino\Filesystem::getFileContent( $appData, '/data/newsletter-removed.php', [] );

$memberEntry = $entryAt( 'member@example.com', 'subscribed', '2026-01-02 03:04:05' );
$waitingEntry = $entryAt( 'waiting@example.com', 'pending', date( 'Y-m-d H:i:s', time() - 3600 ) );
$legacyEntry = [ 'email' => 'legacy@example.com', 'date' => '2025-05-06 07:08:09' ];
$writeList( $appData, [ $memberEntry, $waitingEntry, $legacyEntry ] );
$listBeforeAsks = $readList( $appData );

$pageFills = static fn( array $appData ): array => array_intersect_key( $appData['./nino/html/fills']['*'] ?? [], [ '[[/feature/newsletter/page/title]]' => 1, '[[/feature/newsletter/page/text]]' => 1 ] );
$errorFill = static fn( array $appData ): string => (string) ( $appData['./nino/html/fills']['*']['[[/feature/newsletter/unsubscribe/error]]'] ?? '' );
$asked = static function( array &$appData, array $post ) use ( $freshMailWindow, &$mails, $pageFills ): array {
	$freshMailWindow( $appData );
	$mails = [];
	$request = submitUnsubscribe( $appData, $post );
	return [ 'status' => $request['/nino/http/response']['statusCode'], 'body' => $request['/nino/http/response']['body'] ?? null, 'fills' => $pageFills( $appData ), 'mails' => count( $mails ) ];
};

$formRequest = [ '/nino/http/response' => [ 'statusCode' => 200 ] ];
\Nino\Modules\Newsletter::callbackUnsubscribeForm( $appData, $formRequest );
check( 'the form page starts without an error line', ( $appData['./nino/html/fills']['*']['[[/feature/newsletter/unsubscribe/error]]'] ?? 'unset' ) === '' && $formRequest['/nino/http/response']['statusCode'] === 200 );

$known = $asked( $appData, [ 'email' => 'Member@Example.com' ] );
$knownToken = $memberEntry['token'];
check( 'a known address is answered 200 with the page that says a link is on its way, if the address is on the list',
	$known['status'] === 200 && $known['body'] === '[template /templates/page-newsletter]'
	&& $known['fills']['[[/feature/newsletter/page/title]]'] === '[[/feature/newsletter/result-unsubscribe-requested/title]]'
	&& $known['fills']['[[/feature/newsletter/page/text]]'] === '[[/feature/newsletter/result-unsubscribe-requested/text]]' );
check( '...and gets exactly one mail, to that address, carrying the link with its stored token', $known['mails'] === 1 && $mails[0]['to'] === 'member@example.com'
	&& $sentLink( 'unsubscribe' ) === $siteUrl. '/.newsletter?unsubscribe='. rawurlencode( $knownToken )
	&& str_contains( (string) $mails[0]['body'], 'mail-newsletter-unsubscribe' ) === true );
check( '...nothing is removed by asking - the list is as it was until the link is visited', $readList( $appData ) === $listBeforeAsks );
check( '...and the unsubscribe link is no fill any more once the mail is out - nothing later in the request can carry it', isset( $appData['./nino/html/fills']['*']['[[/feature/newsletter/unsubscribe/url]]'] ) === false );

$unknown = $asked( $appData, [ 'email' => 'nobody@example.com' ] );
check( 'an unknown address gets the identical answer - status, body and fills - and no mail', $unknown['mails'] === 0 && $unknown['status'] === $known['status'] && $unknown['body'] === $known['body'] && $unknown['fills'] === $known['fills'] );
check( '...and the list is not touched by it', $readList( $appData ) === $listBeforeAsks );

/*	An unknown address sends nothing and so charges nothing: the per-ip mail
	cap stays as it was. That is a difference to a listed address (which
	charges it with its mail), accepted - the
	check only keeps it from growing into something else, such as a send to an
	empty recipient, which a kernel that records failed deliveries would count
	as one	*/
$triesOf = static function( array &$appData ): int {
	$state = \Nino\Filesystem::getFileContent( $appData, '/data/ratelimit.php', [] );
	return (int) ( $state[ hash( 'sha256', '127.0.0.1' ) ]['tries'] ?? 0 );
};
$triesBefore = $triesOf( $appData );
$asked( $appData, [ 'email' => 'nobody@example.com' ] );
check( 'an unknown address leaves the mail cap untouched', $triesOf( $appData ) === $triesBefore );
$writeList( $appData, $listBeforeAsks );
$freshMailWindow( $appData );
$mails = [];

$waiting = $asked( $appData, [ 'email' => 'waiting@example.com' ] );
check( 'a pending address gets the mail as well, with its token', $waiting['mails'] === 1 && $waiting['status'] === 200
	&& $sentLink( 'unsubscribe' ) === $siteUrl. '/.newsletter?unsubscribe='. rawurlencode( $waitingEntry['token'] ) );

$legacy = $asked( $appData, [ 'email' => 'legacy@example.com' ] );
$legacyStored = array_values( array_filter( $readList( $appData ), static fn( array $e ): bool => $e['email'] === 'legacy@example.com' ) );
check( 'an entry without a token gets one, in the same write, and the mail goes out with it', $legacy['mails'] === 1 && count( $legacyStored ) === 1 && empty( $legacyStored[0]['token'] ) === false
	&& $sentLink( 'unsubscribe' ) === $siteUrl. '/.newsletter?unsubscribe='. rawurlencode( $legacyStored[0]['token'] )
	&& array_diff_key( $legacyStored[0], [ 'token' => 1 ] ) === $legacyEntry );

// The mail's template is the project's to swap
$appData['/nino/newsletter/unsubscribe-mail-template'] = '/templates/own-unsubscribe-mail';
$swapped = $asked( $appData, [ 'email' => 'member@example.com' ] );
unset( $appData['/nino/newsletter/unsubscribe-mail-template'] );
check( 'the template the config key names is the one the mail is made of', $swapped['mails'] === 1 && str_contains( (string) $mails[0]['body'], 'own-unsubscribe-mail' ) === true );

$invalid = $asked( $appData, [ 'email' => 'not-an-email' ] );
check( 'an invalid address is a 400 with the form and its error line, and no mail', $invalid['status'] === 400 && $invalid['mails'] === 0
	&& $invalid['body'] === '[template /templates/page-newsletter-unsubscribe]' && $errorFill( $appData ) === '[[/feature/newsletter/info/email]]' );
$empty = $asked( $appData, [] );
check( '...and so is none at all', $empty['status'] === 400 && $empty['mails'] === 0 );

$bot = $asked( $appData, [ 'email' => 'member@example.com', 'location' => 'filled-by-a-bot' ] );
check( 'a filled honeypot is a 418 with the answer page, and no mail', $bot['status'] === 418 && $bot['mails'] === 0 && $bot['body'] === '[template /templates/page-newsletter]' );
ninoWarnings();
$botArray = $asked( $appData, [ 'email' => [ 'member@example.com' ], 'location' => [ 'x' ] ] );
check( 'a post of arrays is refused like any other, not raised at', ninoWarnings() === [] && $botArray['status'] === 418 && $botArray['mails'] === 0 );

// Whatever the mail does, the answer does not say: no 429 for the ones
// that are on the list and a 200 for the ones that are not
$transportResult = false;
$refusedAsk = $asked( $appData, [ 'email' => 'member@example.com' ] );
$transportResult = true;
check( 'a transport that refuses the mail is still answered 200 - never a 500 that only a known address could reach', $refusedAsk['mails'] === 1 && $refusedAsk['status'] === $known['status'] && $refusedAsk['body'] === $known['body'] && $refusedAsk['fills'] === $known['fills'] );

$spentMailWindow( $appData );
$mails = [];
$cappedAsk = submitUnsubscribe( $appData, [ 'email' => 'member@example.com' ] );
check( 'an ip at its mail cap is still answered 200, with nothing sent - never a 429 that only a known address could reach', $cappedAsk['/nino/http/response']['statusCode'] === 200
	&& $mails === [] && $pageFills( $appData ) === $known['fills'] );

// A post the csrf check refused writes and sends nothing
$freshMailWindow( $appData );
$mails = [];
$_POST['_csrf'] = 'wrong-token';
$blockedAsk = [ '/nino/http/request' => [ 'method' => 'POST' ], '/nino/http/response' => [ 'statusCode' => 200 ] ];
\Nino\Csrf::callbackResponse( $appData, $blockedAsk );
$_POST = array_merge( $_POST, [ 'email' => 'legacy@example.com', 'location' => '' ] );
$before = $readList( $appData );
\Nino\Modules\Newsletter::callbackUnsubscribeRequest( $appData, $blockedAsk );
unset( $_POST['_csrf'] );
check( 'a csrf-blocked post is refused (403), sends nothing and writes nothing', $blockedAsk['/nino/http/response']['statusCode'] === 403 && $mails === [] && $readList( $appData ) === $before );

/*	The kernel's guard answers that post 403 with the body false, which is json: a visitor of the
	plain form whose session is gone would read a bare "false". The status stays, the body is the form's
	page - the form to send again - with the line that says the form has expired	*/
check( '...with the form\'s page as its body and the line that says the form has expired, not the bare false', ( $blockedAsk['/nino/http/response']['body'] ?? null ) === '[template /templates/page-newsletter-unsubscribe]'
	&& $errorFill( $appData ) === '[[/feature/newsletter/info/expired]]' );
$expiredAsk = serveNewsletter( $appData, '/.newsletter/unsubscribe', [ 'email' => 'legacy@example.com', 'location' => '' ], false );
$expiredAskForms = newsletterForms( $expiredAsk['html'] );
check( 'served, the post without the csrf token keeps its 403 and answers with the page of the form - the form with a fresh token to send again, never the json false', $expiredAsk['status'] === 403
	&& $expiredAsk['html'] !== '' && $expiredAsk['html'] !== 'false' && json_decode( $expiredAsk['html'] ) === null
	&& count( $expiredAskForms ) === 1 && $expiredAskForms[0]['method'] === 'post' && str_ends_with( $expiredAskForms[0]['action'], '/.newsletter/unsubscribe' ) === true
	&& empty( $expiredAskForms[0]['fields']['_csrf'] ) === false && $expiredAskForms[0]['buttons'] === 1 );
$expiredLine = [];
$expiredDocument = new DOMDocument();
libxml_use_internal_errors( true );
$expiredDocument->loadHTML( '<?xml encoding="UTF-8">'. $expiredAsk['html'] );
libxml_clear_errors();
foreach( $expiredDocument->getElementsByTagName( 'p' ) as $paragraph )
	if( str_contains( ' '. $paragraph->getAttribute( 'class' ). ' ', ' nino-form-message ' ) === true )
		$expiredLine[] = $paragraph->textContent;
check( '...and the page shows that line in the paragraph of the form\'s messages, as the words of its key', count( $expiredLine ) === 1 && $expiredLine[0] !== ''
	&& in_array( $expiredLine[0], [ ( require dirname( __DIR__ ). '/install/text/en_US.php' )['[[/feature/newsletter/info/expired]]'], ( require dirname( __DIR__ ). '/install/text/de_DE.php' )['[[/feature/newsletter/info/expired]]'] ], true ) === true );
check( '...the line it carries is the expired-form text, which is neither the one for an invalid address nor the one of the page whose link has expired, and nothing was mailed or written', $errorFill( $appData ) === '[[/feature/newsletter/info/expired]]'
	&& $errorFill( $appData ) !== '[[/feature/newsletter/info/email]]' && $mails === [] && $readList( $appData ) === $before );

// Only the button of the link takes anybody off the list
check( 'pressing the button of the link the mail carried removes the entry and records the removal', performNewsletterLink( $appData, [ 'unsubscribe' => $knownToken ] )['/nino/http/response']['statusCode'] === 200
	&& in_array( 'member@example.com', array_column( $readList( $appData ), 'email' ), true ) === false
	&& in_array( hash( 'sha256', 'member@example.com' ), \Nino\Filesystem::getFileContent( $appData, '/data/newsletter-removed.php', [] ), true ) === true );

$writeList( $appData, $listBefore );
\Nino\Filesystem::putFileContent( $appData, '/data/newsletter-removed.php', $removedBefore );
$freshMailWindow( $appData );

echo "\n";


// --- A site without an address ------------------------------------------------

echo "Modules\\Newsletter - a site without an address has no link to put in a mail\n";

/*	\Nino\Html::absoluteUrl() answers '' where the website has no address, and a
	confirmation or an unsubscribe mail without its link is no mail: nothing is
	sent, and the log says why. The signup is answered as any mail that is not
	sent is - 500 - and still records the entry, so that a re-submit mails it
	once the address is set. The ask for an unsubscribe link keeps its one answer
	for every address, whatever happened to the mail	*/
$listBeforeNoAddress = $readList( $appData );
$writeList( $appData, [ $memberEntry, $waitingEntry ] );

// A call the cap refused comes first: its flag must not be read as the answer of the one after it, which sends nothing at all
$spentMailWindow( $appData );
$mails = [];
$cappedBefore = submitNewsletter( $appData, [ 'email' => 'brand-new@example.com' ] );

\Nino\Html::removeFill( $appData, '/project/website/general/url', '*' );
$freshMailWindow( $appData );
$mails = [];
ninoWarnings();
$noAddress = submitNewsletter( $appData, [ 'email' => 'brand-new@example.com' ] );
$noAddressWarnings = ninoWarnings();
check( 'the call before was capped, as set up', $cappedBefore['/nino/http/response']['statusCode'] === 429 );
check( 'a signup on a site without an address is answered 500, as for a mail that cannot be sent - not 429 again - with no body', $noAddress['/nino/http/response']['statusCode'] === 500
	&& ( $noAddress['/nino/http/response']['body'] ?? null ) === null );
check( '...nothing is sent, and the warning names the fill the address is', $mails === [] && count( $noAddressWarnings ) === 1 && str_contains( $noAddressWarnings[0], '/project/website/general/url' ) === true );
check( '...and the signup is recorded all the same, for the re-submit that will be mailed',
	in_array( 'brand-new@example.com', array_column( array_filter( $readList( $appData ), static fn( array $e ): bool => ( $e['status'] ?? '' ) === 'pending' ), 'email' ), true ) === true );
check( '...no fill is left behind by the mail that was not sent', isset( $appData['./nino/html/fills']['*']['[[/feature/newsletter/confirm/url]]'] ) === false );

$noAddressAsk = $asked( $appData, [ 'email' => 'member@example.com' ] );
$noAddressAskWarnings = ninoWarnings();
check( 'asking for an unsubscribe link is answered as always - 200, the same page and fills - and nothing is sent', $noAddressAsk['status'] === 200 && $noAddressAsk['body'] === $known['body']
	&& $noAddressAsk['fills'] === $known['fills'] && $noAddressAsk['mails'] === 0 );
check( '...and the warning names the fill', count( $noAddressAskWarnings ) === 1 && str_contains( $noAddressAskWarnings[0], '/project/website/general/url' ) === true );
check( 'there is no unsubscribe link to give out either - the same false as for an address that is not on the list', \Nino\Modules\Newsletter::getUnsubscribeLink( $appData, 'member@example.com' ) === false );

\Nino\Html::addFills( $appData, [ '[[/project/website/general/url]]' => $siteAddress ], '*' );
$answer( $appData, 'brand-new@example.com' );
check( 'with the address set again the re-submit is mailed, with the link on it', count( $mails ) === 1 && $sentLink( 'confirm' ) !== '' && str_starts_with( $sentLink( 'confirm' ), $siteUrl. '/.newsletter?confirm=' ) === true );
check( '...and the link on the unsubscribe side is built too', str_starts_with( (string) \Nino\Modules\Newsletter::getUnsubscribeLink( $appData, 'member@example.com' ), $siteUrl. '/.newsletter?unsubscribe=' ) === true );

$writeList( $appData, $listBeforeNoAddress );
$freshMailWindow( $appData );

echo "\n";


// --- The panel -----------------------------------------------------------------

echo "Modules\\Newsletter\\Admin - the subscriber list and its permission\n";

// A set-up project with a full-access account, the way the wizard leaves one
$appData['/nino/install/completed'] = true;
ninoUser( $appData, 'plain@example.com', [], 'plain password' );
ninoUser( $appData, 'admin@example.com' );

/*	The page and the confirmation mail carry [[/feature/newsletter/page/title]],
	[[/feature/newsletter/page/text]] and [[/feature/newsletter/confirm/url]], which the class
	fills at request time and no text file answers. The Text panel's scan for
	missing keys reads the templates as source, so without the unit's
	blacklist entries it reported the three - and the Dashboard counted them -
	on every project with this feature, as gaps nobody could close	*/
[ $status, $body ] = ninoCall( $appData, $panel, [], 'keys/scan' );
check( 'the Text panel\'s scan reports no key of this feature: what the templates use is in the text files or in the blacklist', $status === 200
	&& array_filter( array_column( (array) ( $body['missing'] ?? [] ), 'key' ), static fn( string $key ): bool => str_starts_with( $key, '/feature/newsletter/' ) === true || str_starts_with( $key, '/template/page-newsletter-' ) === true || str_starts_with( $key, '/template/mail-newsletter-' ) === true ) === [] );

check( 'the panel is in the registry while the feature is active', isset( \Nino\Admin\Admin::panels( $appData )['newsletter'] ) === true );

$freshMailWindow( $appData );
$annaRequest = submitNewsletter( $appData, [ 'email' => 'anna@example.com' ] );
check( 'a second valid signup succeeds', $annaRequest['/nino/http/response']['statusCode'] === 200 );

[ $status, $body ] = ninoCall( $appData, $panel, [], 'newsletter/list' );
check( 'newsletter/list succeeds', $status === 200 );
check( 'newsletter/list finds both signups, most recent first', count( $body['entries'] ) === 2 && $body['entries'][0]['email'] === 'anna@example.com' );

/*	A token is not a field, it is a credential: ?unsubscribe=<token> on the
	public route takes that address off the list and ?confirm=<token> confirms
	a signup, both with nothing else to show. The panel never draws it and
	deletes by address - but Nino.admin.exportCsv() writes the union of every
	row's keys, so it went into a file that is opened in a spreadsheet, mailed
	around and handed to a sending provider	*/
check( 'the list the panel gets carries no token, so neither does the csv it exports', count( array_filter( $body['entries'], static fn( array $e ): bool => isset( $e['token'] ) ) ) === 0 );
check( '...while the record of the consent stays, which is what the panel is for', count( array_filter( $body['entries'], static fn( array $e ): bool => isset( $e['email'], $e['date'], $e['ip'] ) ) ) === 2 );
check( '...and the token is still stored, or no link in a sent mail would work again', count( array_filter(
	\Nino\Filesystem::getFileContent( $appData, '/data/newsletter.php', [] ),
	static fn( array $e ): bool => ( $e['token'] ?? '' ) !== ''
) ) === 2 );

/*	A signup nobody confirmed has agreed to nothing: the count, the Dashboard's
	tile and the BCC line are the confirmed addresses, and the list says which
	is which	*/
check( 'with both signups unconfirmed the count and the Dashboard tile say 0', \Nino\Modules\Newsletter\Admin::count( $appData ) === 0
	&& \Nino\Modules\Newsletter\Admin::summary( $appData )['value'] === 0 );
check( '...and every row of the list says it is pending', array_column( $body['entries'], 'status' ) === [ 'pending', 'pending' ] && $body['counts'] === [ 'subscribed' => 0, 'pending' => 2 ] );

$annaToken = array_column( \Nino\Filesystem::getFileContent( $appData, '/data/newsletter.php', [] ), 'token', 'email' )['anna@example.com'];
performNewsletterLink( $appData, [ 'confirm' => $annaToken ] );
check( 'a confirmed signup is counted, and the tile with it', \Nino\Modules\Newsletter\Admin::count( $appData ) === 1
	&& \Nino\Modules\Newsletter\Admin::summary( $appData )['value'] === 1 );

// An entry from before the double opt-in flow has no status at all
\Nino\Filesystem::putFileContent( $appData, '/data/newsletter.php', array_merge(
	\Nino\Filesystem::getFileContent( $appData, '/data/newsletter.php', [] ),
	[ [ 'email' => 'legacy@example.com', 'date' => '2025-05-06 07:08:09' ] ]
) );
[ , $body ] = ninoCall( $appData, $panel, [], 'newsletter/list' );
check( 'the list carries a status for every row - an entry with none reads as subscribed', array_column( $body['entries'], 'status', 'email' ) === [ 'legacy@example.com' => 'subscribed', 'anna@example.com' => 'subscribed', 'jo@example.com' => 'pending' ] );
check( '...the counts of both, a legacy entry among the subscribed', $body['counts'] === [ 'subscribed' => 2, 'pending' => 1 ] && \Nino\Modules\Newsletter\Admin::count( $appData ) === 2 );
check( '...the address of the page where a subscriber asks for a link, built like the links in the mails', ( $body['unsubscribeUrl'] ?? '' ) === $siteUrl. '/.newsletter/unsubscribe' );

\Nino\Html::removeFill( $appData, '/project/website/general/url', '*' );
[ , $noAddressBody ] = ninoCall( $appData, $panel, [], 'newsletter/list' );
\Nino\Html::addFills( $appData, [ '[[/project/website/general/url]]' => $siteAddress ], '*' );
check( '...and none, for a site without an address: there is nothing to name, and the panel draws no line for it', array_key_exists( 'unsubscribeUrl', $noAddressBody ) === true && $noAddressBody['unsubscribeUrl'] === '' );
check( '...and still no token in any row, whatever its status', count( array_filter( $body['entries'], static fn( array $e ): bool => isset( $e['token'] ) ) ) === 0 );
ninoCall( $appData, $panel, [ 'email' => 'legacy@example.com' ], 'newsletter/delete' );
\Nino\Filesystem::putFileContent( $appData, '/data/newsletter-removed.php', [] );

unset( $appData['./nino/auth/current'] );
[ $status ] = ninoCall( $appData, $panel, [], 'newsletter/list' );
check( 'newsletter/list requires a signed-in account', $status === 401 );
[ $status ] = ninoCall( $appData, $panel, [ 'email' => 'jo@example.com' ], 'newsletter/delete' );
check( 'newsletter/delete requires a signed-in account', $status === 401 );

\Nino\Auth::loginUser( $appData, 'plain@example.com', 'plain password' );
[ $status ] = ninoCall( $appData, $panel, [], 'newsletter/list' );
check( 'an account without the permission is rejected from newsletter/list', $status === 403 );
[ $status ] = ninoCall( $appData, $panel, [ 'email' => 'jo@example.com' ], 'newsletter/delete' );
check( 'an account without the permission is rejected from newsletter/delete', $status === 403 );

\Nino\Auth::loginUser( $appData, 'admin@example.com', 'correct horse battery staple' );

[ $status, $body ] = ninoCall( $appData, $panel, [ 'email' => 'does-not-exist@example.com' ], 'newsletter/delete' );
check( 'newsletter/delete 404s for an email that was never subscribed, with the code newsletter_unknown', $status === 404 && $body['code'] === 'newsletter_unknown' );
foreach( [ 'en_US', 'de_DE' ] as $locale )
	check( "...and the code has its sentence in $locale", trim( \Nino\Admin\Admin::textFills( $appData, '/features/Newsletter/text', $locale )['[[/_admin/error/'. $body['code']. ']]'] ?? '' ) !== '' );

// The payload is untrusted json: an address that is a list, or none, is as unknown as one that is not on the list - no warning, no 500
ninoWarnings();
[ $listedStatus, $listedBody ] = ninoCall( $appData, $panel, [ 'email' => [ 'jo@example.com' ] ], 'newsletter/delete' );
[ $noneStatus, $noneBody ] = ninoCall( $appData, $panel, [], 'newsletter/delete' );
check( 'an address posted as a list, and none at all, are unknown the same way', $listedStatus === 404 && $listedBody['code'] === 'newsletter_unknown'
	&& $noneStatus === 404 && $noneBody['code'] === 'newsletter_unknown' && ninoWarnings() === [] );
check( '...and the list is as it was', in_array( 'jo@example.com', array_column( \Nino\Filesystem::getFileContent( $appData, '/data/newsletter.php', [] ), 'email' ), true ) === true );

/*	The removal record is written first, inside the list's lock, and the
	address leaves the list only once it is: with the record's sidecar lock
	made unopenable - a directory where the lock file goes, the answer a
	read-only or full disk gives - the delete fails and the subscriber stays,
	so the panel's delete can simply be repeated	*/
$removalLock = \Nino\Filesystem::path( $appData, '/data' ). '/.locks/'. sha1( '/data/newsletter-removed.php' ). '.lock';
unset( $appData['./nino/filesystem/locks'] );
@unlink( $removalLock );
@mkdir( $removalLock );
[ $status, $body ] = ninoCall( $appData, $panel, [ 'email' => 'jo@example.com' ], 'newsletter/delete' );
check( 'a delete whose removal record cannot be written answers 500, with its sentence and no code - the server\'s to report, nothing a person caused', $status === 500 && isset( $body['code'] ) === false && is_string( $body['error'] ) === true );
check( '...and leaves the subscriber on the list', in_array( 'jo@example.com', array_column( \Nino\Filesystem::getFileContent( $appData, '/data/newsletter.php', [] ), 'email' ), true ) === true );
check( '...because the one writer of the record says it failed', \Nino\Modules\Newsletter::recordRemoval( $appData, 'jo@example.com' ) === false
	&& in_array( hash( 'sha256', 'jo@example.com' ), \Nino\Filesystem::getFileContent( $appData, '/data/newsletter-removed.php', [] ), true ) === false );
@rmdir( $removalLock );
unset( $appData['./nino/filesystem/locks'] );

[ $status ] = ninoCall( $appData, $panel, [ 'email' => 'jo@example.com' ], 'newsletter/delete' );
check( 'newsletter/delete succeeds for an existing subscriber', $status === 200 );

[ , $body ] = ninoCall( $appData, $panel, [], 'newsletter/list' );
check( 'the deleted subscriber is gone, the other one remains', count( $body['entries'] ) === 1 && $body['entries'][0]['email'] === 'anna@example.com' );

check( 'the admin delete also records the removal, same as a self-service unsubscribe', in_array( hash( 'sha256', 'jo@example.com' ), \Nino\Filesystem::getFileContent( $appData, '/data/newsletter-removed.php', [] ), true ) === true );

// The panel is the feature's: switch the feature off and the screen and its
// actions are gone from the workbench
check( 'deactivation succeeds', \Nino\Features::deactivate( $appData, 'newsletter' ) === true );
check( 'with the feature off, its panel is not in the registry', isset( \Nino\Admin\Admin::panels( $appData )['newsletter'] ) === false );
[ $status ] = ninoCall( $appData, $panel, [], 'newsletter/list' );
check( 'with the feature off, newsletter/list is an unknown action', $status === 404 );
check( 'the subscribers survive the deactivation', count( \Nino\Filesystem::getFileContent( $appData, $subscribersPath, [] ) ) === 1 );

echo "\n";


// --- Restore -------------------------------------------------------------------

echo "Modules\\Newsletter::callbackRestore - a restore merges instead of overwriting (Art. 17)\n";

// The merge is reached from the Backups panel's Restore through the
// '/nino/admin/restore' callback init() registers - exercised here directly
// rather than through a real two-backup round trip: the daily backup only
// ever creates one per calendar day, so a sandboxed run cannot produce an
// "older backup" and a "current, since-changed state" days apart
$mergeInvoke = static function( string $dataDir, string $staging ) use ( &$appData ): void {
	$args = [ 'dataDir' => $dataDir, 'staging' => $staging ];
	\Nino\Modules\Newsletter::callbackRestore( $appData, $args );
};

$sandbox			= ninoSandboxDir( $appData );
$mergeRoot 		= $sandbox. '/merge-root';
$mergeStaging = $sandbox. '/merge-staging';
mkdir( $mergeRoot. '/data', 0755, true );
mkdir( $mergeStaging. '/data', 0755, true );

// root (current): alice unsubscribed since the backup was taken - gone from
// newsletter.php, recorded as a sha256 in newsletter-removed.php; bob untouched
$aliceHash = hash( 'sha256', 'alice@example.com' );
file_put_contents( $mergeRoot. '/data/newsletter.php', '<?php return [ [ "email" => "bob@example.com", "status" => "subscribed" ] ];' );
file_put_contents( $mergeRoot. '/data/newsletter-removed.php', '<?php return [ '. var_export( $aliceHash, true ). ' ];' );

// staging (the backup being restored): taken before Alice unsubscribed, so it
// still has her subscription and no removal record of its own
file_put_contents( $mergeStaging. '/data/newsletter.php', '<?php return [ [ "email" => "alice@example.com", "status" => "subscribed" ], [ "email" => "bob@example.com", "status" => "subscribed" ] ];' );

$mergeInvoke( $mergeRoot. '/data', $mergeStaging );

$mergedEntries = include $mergeStaging. '/data/newsletter.php';
$mergedRemoved = include $mergeStaging. '/data/newsletter-removed.php';

check( 'a restore does not resurrect an address unsubscribed since the backup was taken', in_array( 'alice@example.com', array_column( $mergedEntries, 'email' ), true ) === false );
check( 'an untouched subscriber survives the restore', in_array( 'bob@example.com', array_column( $mergedEntries, 'email' ), true ) === true );
check( 'the removal record itself is carried into the restored state, not just the filtered entries', in_array( $aliceHash, $mergedRemoved, true ) === true );

// A project without the feature carries neither file. Merging that state
// must be a no-op, not an error
$mergeRoot2 	= $sandbox. '/merge-root2';
$mergeStaging2 = $sandbox. '/merge-staging2';
mkdir( $mergeRoot2, 0755, true );
mkdir( $mergeStaging2, 0755, true );

$mergeInvoke( $mergeRoot2, $mergeStaging2 );
check( 'a backup with no newsletter files at all is a no-op, not an error', is_file( $mergeStaging2. '/data/newsletter.php' ) === false );


// --- What an unconfirmed signup costs ----------------------------------------

echo "Modules\\Newsletter - an unconfirmed signup expires, and there is a ceiling under it\n";

/*	The signup endpoint is public and unauthenticated, and mutate() rewrites
	the whole file on every post. Without an expiry and a ceiling, 2000 posts
	with distinct addresses stored 404 KB, none of it expiring, and took a
	signup from 0.97 ms to 5.87 ms because each one reads and rewrites
	everything before it	*/
$writeList( $appData, [
	$entryAt( 'stale@example.com', 'pending', date( 'Y-m-d H:i:s', time() - 8 * 86400 ) ),
	$entryAt( 'fresh@example.com', 'pending', date( 'Y-m-d H:i:s', time() - 3600 ) ),
	$entryAt( 'member@example.com', 'subscribed', date( 'Y-m-d H:i:s', time() - 400 * 86400 ) ),
	[ 'email' => 'ancient@example.com', 'date' => date( 'Y-m-d H:i:s', time() - 900 * 86400 ) ],
] );
submitNewsletter( $appData, [ 'email' => 'arrival@example.com' ] );
$afterSweep = array_column( $readList( $appData ), 'email' );

check( 'an unconfirmed signup that ran out of time is dropped when the next one arrives', in_array( 'stale@example.com', $afterSweep, true ) === false );
check( '...while one still inside the window stays', in_array( 'fresh@example.com', $afterSweep, true ) === true );
check( 'a subscriber is never swept, however old the entry', in_array( 'member@example.com', $afterSweep, true ) === true );
check( '...and neither is an entry from before the double opt-in flow, which counts as subscribed', in_array( 'ancient@example.com', $afterSweep, true ) === true );

// The days are a project's to set - a slower audience confirms later
$appData[ \Nino\Modules\Newsletter::PENDING_DAYS ] = 30;
$writeList( $appData, [ $entryAt( 'week-old@example.com', 'pending', date( 'Y-m-d H:i:s', time() - 8 * 86400 ) ) ] );
submitNewsletter( $appData, [ 'email' => 'arrival2@example.com' ] );
check( 'a longer window keeps what the default would have dropped', in_array( 'week-old@example.com', array_column( $readList( $appData ), 'email' ), true ) === true );
unset( $appData[ \Nino\Modules\Newsletter::PENDING_DAYS ] );

/*	And the ceiling, for a burst that arrives faster than a week passes. The
	oldest unconfirmed entry makes room; a subscriber is not counted and not
	touched	*/
$flood = [ $entryAt( 'keeper@example.com', 'subscribed', date( 'Y-m-d H:i:s' ) ) ];
for( $i = 0; $i < \Nino\Modules\Newsletter::PENDING_LIMIT + 50; $i++ )
	$flood[] = $entryAt( 'flood'. $i. '@example.com', 'pending', date( 'Y-m-d H:i:s', time() - 3600 + $i ) );
$writeList( $appData, $flood );
submitNewsletter( $appData, [ 'email' => 'last@example.com' ] );
$afterFlood = $readList( $appData );
$stillPending = array_filter( $afterFlood, static fn( array $e ): bool => ( $e['status'] ?? '' ) === 'pending' );

check( 'the unconfirmed entries are held to the ceiling', count( $stillPending ) <= \Nino\Modules\Newsletter::PENDING_LIMIT );
check( '...and what went is the oldest of them, not the newest', in_array( 'flood0@example.com', array_column( $afterFlood, 'email' ), true ) === false
	&& in_array( 'last@example.com', array_column( $afterFlood, 'email' ), true ) === true );
check( '...while the subscriber is still there - a ceiling on unconfirmed entries is not a ceiling on the list', in_array( 'keeper@example.com', array_column( $afterFlood, 'email' ), true ) === true );
check( 'the stored list is a list again, with no gaps left by what was removed', array_keys( $afterFlood ) === range( 0, count( $afterFlood ) - 1 ) );

echo "\n";


// --- A signup that waited too long is not confirmed ---------------------------

echo "Modules\\Newsletter - a pending signup past its days is not confirmed\n";

/*	The privacy section promises that an unconfirmed signup expires after the
	pending days; the sweep only runs when somebody signs up, so until then the
	entry is still on the list - and its link must not work. The token is as
	unknown as one never issued (404, the same page - and no button on it, for
	a link that opens the page is a link the button would be refused for), the
	entry is left for the next sweep, and a signup inside the window is
	confirmed as ever	*/
$staleAt = date( 'Y-m-d H:i:s', time() - 8 * 86400 );
$writeList( $appData, [
	[ 'email' => 'stale@example.com', 'token' => 'stale0token', 'status' => 'pending', 'date' => $staleAt, 'ip' => '127.0.0.1' ],
	[ 'email' => 'recent@example.com', 'token' => 'recent0token', 'status' => 'pending', 'date' => date( 'Y-m-d H:i:s', time() - 6 * 86400 ), 'ip' => '127.0.0.1' ],
	[ 'email' => 'undated@example.com', 'token' => 'undated0token', 'status' => 'pending', 'ip' => '127.0.0.1' ],
] );
$staleVisit = serveNewsletter( $appData, '/.newsletter?confirm=stale0token' );
check( 'the link of a pending signup eight days old answers 404, as an unknown token does, with no button on the page', $staleVisit['status'] === 404 && newsletterForms( $staleVisit['html'] ) === []
	&& $staleVisit['status'] === serveNewsletter( $appData, '/.newsletter?confirm=never0issued' )['status'] );
check( '...and so does the post of the button, if one is made', performNewsletterLink( $appData, [ 'confirm' => 'stale0token' ] )['/nino/http/response']['statusCode'] === 404 );
check( '...the entry is not confirmed, and it stays until the next sweep', $readList( $appData )[0] === [ 'email' => 'stale@example.com', 'token' => 'stale0token', 'status' => 'pending', 'date' => $staleAt, 'ip' => '127.0.0.1' ] );
$recentVisit = serveNewsletter( $appData, '/.newsletter?confirm=recent0token' );
check( 'the link of one six days old shows the button (200) and confirms nothing yet', $recentVisit['status'] === 200 && count( newsletterForms( $recentVisit['html'] ) ) === 1 && ( $readList( $appData )[1]['status'] ?? '' ) === 'pending' );
$recentPost = performNewsletterLink( $appData, [ 'confirm' => 'recent0token' ] );
check( '...its button confirms it (200)', $recentPost['/nino/http/response']['statusCode'] === 200 && ( $readList( $appData )[1]['status'] ?? '' ) === 'subscribed' );
$undatedVisit = serveNewsletter( $appData, '/.newsletter?confirm=undated0token' );
check( 'one with no date to count from is not confirmed either - the sweep drops it too: no page with a button, no confirmation', $undatedVisit['status'] === 404 && newsletterForms( $undatedVisit['html'] ) === []
	&& performNewsletterLink( $appData, [ 'confirm' => 'undated0token' ] )['/nino/http/response']['statusCode'] === 404
	&& ( $readList( $appData )[2]['status'] ?? '' ) === 'pending' );

// The days are a project's to set, here as well as in the sweep
$appData[ \Nino\Modules\Newsletter::PENDING_DAYS ] = 30;
check( 'a longer window shows the button for what the default would have refused, and confirms it', serveNewsletter( $appData, '/.newsletter?confirm=stale0token' )['status'] === 200
	&& performNewsletterLink( $appData, [ 'confirm' => 'stale0token' ] )['/nino/http/response']['statusCode'] === 200
	&& ( $readList( $appData )[0]['status'] ?? '' ) === 'subscribed' );
unset( $appData[ \Nino\Modules\Newsletter::PENDING_DAYS ] );

// An entry that is subscribed is confirmed as many times as the link is clicked, however old
$writeList( $appData, [ $entryAt( 'old-member@example.com', 'subscribed', date( 'Y-m-d H:i:s', time() - 400 * 86400 ) ) ] );
check( 'the link of a subscriber stays good, however old the entry', visitNewsletterLink( $appData, [ 'confirm' => (string) $readList( $appData )[0]['token'] ] )['/nino/http/response']['statusCode'] === 200
	&& performNewsletterLink( $appData, [ 'confirm' => (string) $readList( $appData )[0]['token'] ] )['/nino/http/response']['statusCode'] === 200 );

// An unsubscribe link is read as _unsubscribe() reads it - an entry whose signup ran out of days and has not been swept yet
// still has its link, so the page that is shown is one whose button works
$writeList( $appData, [ [ 'email' => 'lapsed@example.com', 'token' => 'lapsed0token', 'status' => 'pending', 'date' => $staleAt, 'ip' => '127.0.0.1' ] ] );
check( 'the unsubscribe link of an unswept expired signup shows its button, and the button removes it', serveNewsletter( $appData, '/.newsletter?unsubscribe=lapsed0token' )['status'] === 200
	&& performNewsletterLink( $appData, [ 'unsubscribe' => 'lapsed0token' ] )['/nino/http/response']['statusCode'] === 200 && $readList( $appData ) === [] );

echo "\n";


// --- The pages carry their names, and the home button its directory -----------

echo "Modules\\Newsletter - the details of its two pages, the home button, the refusal\n";

// The unit's words, as the activation merged them into the project's files
$pageKeys = [];
foreach( [ '.newsletter', '.newsletter/unsubscribe' ] as $pageUri )
	foreach( [ 'name', 'title', 'description' ] as $detail )
		$pageKeys[] = '[[/_nino/webpage/'. $pageUri. '/'. $detail. ']]';
$projectText = [];
foreach( [ 'en_US', 'de_DE' ] as $textLocale )
	$projectText[$textLocale] = \Nino\Filesystem::getFileContent( $appData, '/text/'. $textLocale. '.php', [] );
$missingDetails = [];
foreach( $projectText as $textLocale => $words )
	foreach( $pageKeys as $pageKey )
		if( trim( (string) ( $words[$pageKey] ?? '' ) ) === '' )
			$missingDetails[] = $textLocale. ' '. $pageKey;
check( 'the unit brings name, title and description of /.newsletter and of /.newsletter/unsubscribe in both languages'. ( $missingDetails === [] ? '' : ' - missing: '. implode( ', ', $missingDetails ) ), $missingDetails === [] );
check( '...in words, none of them the key as it stands', array_filter( $projectText, static fn( array $words ): bool => array_filter( $pageKeys, static fn( string $pageKey ): bool => str_starts_with( (string) ( $words[$pageKey] ?? '' ), '[[/' ) === true || str_starts_with( (string) ( $words[$pageKey] ?? '' ), '/_nino/' ) === true ) !== [] ) === [] );
check( '...the German ones are German: the description of both pages and the name and title of the unsubscribe page differ from the English', array_filter( [ '.newsletter/description', '.newsletter/unsubscribe/name', '.newsletter/unsubscribe/title', '.newsletter/unsubscribe/description' ],
	static fn( string $detail ): bool => $projectText['de_DE']['[[/_nino/webpage/'. $detail. ']]'] === $projectText['en_US']['[[/_nino/webpage/'. $detail. ']]'] ) === [] );

// The home button: the address of the page is the home page's uri below the directory of the site
$pageTemplate = (string) \Nino\Filesystem::getFileContent( $appData, '/templates/page-newsletter.tpl', '' );
\Nino\Html::addFills( $appData, [ '[[/_nino/webpage/home/uri]]' => '/', '[[/_nino/webpage/home/name]]' => 'Home' ], '*' );
$appData['/nino/dir'] = '/sub';
\Nino\Html::addFills( $appData, \Nino\Html::bootFills( $appData ), '*' );
$homeButton = \Nino\Html::renderHtml( $appData, $pageTemplate );
$appData['/nino/dir'] = '';
\Nino\Html::addFills( $appData, \Nino\Html::bootFills( $appData ), '*' );
$homeButtonRoot = \Nino\Html::renderHtml( $appData, $pageTemplate );
check( 'the home button of the page leads to the home page below the website\'s directory', str_contains( $homeButton, '<a href="/sub/" class="nino-btn nino-btn--primary">Home</a>' ) === true );
check( '...and to / where the site stands at the root', str_contains( $homeButtonRoot, '<a href="/" class="nino-btn nino-btn--primary">Home</a>' ) === true );

// The refusal says which mail it was, and that the address is kept
check( 'the sentence of a refused signup names the mail, in both languages', ( $projectText['en_US']['[[/feature/newsletter/info/error]]'] ?? '' ) === 'The mail could not be sent just now. Please try again later.'
	&& ( $projectText['de_DE']['[[/feature/newsletter/info/error]]'] ?? '' ) === 'Die Mail konnte gerade nicht versendet werden. Bitte versuche es später noch einmal.' );

echo "\n";


// --- The page a link opens, and the words that lead to its button --------------

echo "Modules\\Newsletter - the page a link opens: its form under the site's directory, the token written safely, the words of the mails\n";

// The site stands below a directory: the form posts to the action under it, like every other link of the feature
$appData['/nino/dir'] = '/sub';
\Nino\Html::addFills( $appData, \Nino\Html::bootFills( $appData ), '*' );
$writeList( $appData, [ [ 'email' => 'below@example.com', 'token' => 'below0token', 'status' => 'pending', 'date' => date( 'Y-m-d H:i:s' ), 'ip' => '127.0.0.1' ] ] );
$belowForms = newsletterForms( serveNewsletter( $appData, '/.newsletter?confirm=below0token' )['html'] );
check( 'the form of the page posts to /.newsletter/action below the website\'s directory', count( $belowForms ) === 1 && $belowForms[0]['action'] === '/sub/.newsletter/action' );
$appData['/nino/dir'] = '';
\Nino\Html::addFills( $appData, \Nino\Html::bootFills( $appData ), '*' );
$rootForms = newsletterForms( serveNewsletter( $appData, '/.newsletter?confirm=below0token' )['html'] );
check( '...and to /.newsletter/action where the site stands at the root', count( $rootForms ) === 1 && $rootForms[0]['action'] === '/.newsletter/action' );

/*	The token is written into an attribute, and a fill is read for fills and shortcodes once more: a token
	that is not the hex string the class makes (a hand-edited list, an import) must come out exactly as it
	is stored, as a value and as nothing else. A "] and a [csrf] in it would end the attribute and draw a field	*/
$oddToken = 'odd"><input name="x"> [csrf] [[/feature/newsletter/page/title]]';
$writeList( $appData, [ [ 'email' => 'odd@example.com', 'token' => $oddToken, 'status' => 'pending', 'date' => date( 'Y-m-d H:i:s' ), 'ip' => '127.0.0.1' ] ] );
$oddPage = serveNewsletter( $appData, '/.newsletter?confirm='. rawurlencode( $oddToken ) );
$oddForms = newsletterForms( $oddPage['html'] );
check( 'a token with markup, a shortcode and a fill in it is written into the hidden field exactly as stored, and draws nothing else', $oddPage['status'] === 200
	&& count( $oddForms ) === 1 && ( $oddForms[0]['fields']['confirm'] ?? null ) === $oddToken && array_keys( $oddForms[0]['fields'] ) === [ '_csrf', 'confirm' ] && $oddForms[0]['buttons'] === 1 );

// The answer to an address that asked for its link shares the page, and has no button on it
$writeList( $appData, [ $entryAt( 'member@example.com', 'subscribed', '2026-01-02 03:04:05' ) ] );
$freshMailWindow( $appData );
$asked = serveNewsletter( $appData, '/.newsletter/unsubscribe', [ 'email' => 'member@example.com', 'location' => '' ] );
check( 'the page that says a link is on its way has no form and no placeholder of one', $asked['status'] === 200 && newsletterForms( $asked['html'] ) === [] && str_contains( $asked['html'], '/feature/newsletter/page/form' ) === false );
$writeList( $appData, [] );

// What the mails say about the button: the sentence exists in both languages in the unit, the mail reads it, and it names the button the page draws
$mailWords = [];
foreach( [ 'en_US', 'de_DE' ] as $textLocale )
	$mailWords[$textLocale] = \Nino\Filesystem::getFileContent( $appData, '/text/'. $textLocale. '.php', [] );
foreach( [ 'confirm', 'unsubscribe' ] as $kind ) {
	$step = '[[/template/mail-newsletter-'. $kind. '/action/step]]';
	$mailTemplate = (string) \Nino\Filesystem::getFileContent( $appData, '/templates/mail-newsletter-'. $kind. '.tpl', '' );
	check( "the $kind mail reads the sentence that names the button of the page", str_contains( $mailTemplate, $step ) === true );
	foreach( [ 'en_US', 'de_DE' ] as $textLocale )
		check( "...which both languages carry, and which refers to the button the page draws ($kind, $textLocale)", trim( (string) ( $mailWords[$textLocale][$step] ?? '' ) ) !== ''
			&& str_contains( (string) $mailWords[$textLocale][$step], '[[/feature/newsletter/action/'. $kind. ']]' ) === true );
}

// The words of the page itself, in both languages: the button of each kind, and the title and text of each question
$pageWords = [];
foreach( [ 'confirm', 'unsubscribe' ] as $kind )
	foreach( [ '/feature/newsletter/action/'. $kind, '/feature/newsletter/result-pending-'. $kind. '/title', '/feature/newsletter/result-pending-'. $kind. '/text' ] as $wordKey )
		$pageWords[] = '[['. $wordKey. ']]';
foreach( [ 'title', 'text' ] as $part )
	$pageWords[] = '[[/feature/newsletter/result-expired/'. $part. ']]';
$missingWords = [];
foreach( $mailWords as $textLocale => $words )
	foreach( $pageWords as $wordKey )
		if( trim( (string) ( $words[$wordKey] ?? '' ) ) === '' )
			$missingWords[] = $textLocale. ' '. $wordKey;
$expiredFormWords = [];
foreach( $mailWords as $textLocale => $words )
	if( trim( (string) ( $words['[[/feature/newsletter/info/expired]]'] ?? '' ) ) === '' || str_starts_with( (string) $words['[[/feature/newsletter/info/expired]]'], '[[/' ) === true )
		$expiredFormWords[] = $textLocale;
check( 'the line of the unsubscribe form that has expired has its words in both languages, in each their own'. ( $expiredFormWords === [] ? '' : ' - missing: '. implode( ', ', $expiredFormWords ) ), $expiredFormWords === []
	&& $mailWords['de_DE']['[[/feature/newsletter/info/expired]]'] !== $mailWords['en_US']['[[/feature/newsletter/info/expired]]'] );
check( 'the page of an opened link has its button and its title and text in both languages, for confirming and for unsubscribing, and the page that has expired has its title and text'. ( $missingWords === [] ? '' : ' - missing: '. implode( ', ', $missingWords ) ), $missingWords === [] );
check( '...in each language their own words: none is the key as it stands, none the same in both', array_filter( $pageWords, static fn( string $wordKey ): bool => ( $mailWords['de_DE'][$wordKey] ?? '' ) === ( $mailWords['en_US'][$wordKey] ?? '' )
	|| str_starts_with( (string) ( $mailWords['de_DE'][$wordKey] ?? '' ), '[[/' ) === true || str_starts_with( (string) ( $mailWords['en_US'][$wordKey] ?? '' ), '[[/' ) === true ) === [] );
check( '...and the unit blacklists what the class fills in for the form, so the Text panel does not list them as gaps', array_diff(
	[ '/feature/newsletter/page/form', '/feature/newsletter/action/field', '/feature/newsletter/action/token', '/feature/newsletter/action/button' ],
	(array) ( \Nino\Features::readUnitManifest( dirname( __DIR__ ). '/install' )['blacklist'] ?? [] ) ) === [] );

echo "\n";


// --- The confirmation mail's Reply-To ----------------------------------------

echo "Modules\\Newsletter::_sendConfirmMail - who a confirmation can be replied to\n";

/*	The reply address is '[[/project/mail/address/owner]]', which the base install unit
	ships as '[[/project/company/contact/email]]' - the mailbox the project already named.
	It is the base unit's, so a project without the contact form has it all the
	same; the chained value is resolved here rather than assumed, because a fill
	that resolves to another fill is where "it is installed" stops meaning "it
	is an address".

	A transport, registered at the top, takes the mail so nothing is
	actually sent	*/

\Nino\Html::addFills( $appData, [ '[[/project/company/contact/email]]' => 'hallo@example.com', '[[/project/mail/address/owner]]' => '[[/project/company/contact/email]]' ], '*' );
$mails = [];
$writeList( $appData, [] );
$freshMailWindow( $appData );
submitNewsletter( $appData, [ 'email' => 'reply-to@example.org' ] );

check( 'the confirmation mail carries the mailbox the project named, through the chained fill',
	( $mails[0]['replyTo'] ?? '' ) === 'hallo@example.com'
	&& str_contains( (string) ( $mails[0]['headers'] ?? '' ), 'Reply-To: hallo@example.com' ) === true );

/*	And where the fill is not installed at all, the kernel drops it rather than
	sending a header naming a fill: the recipient and the body were never the
	problem, and a confirmation nobody receives is worse than one nobody can
	reply to. Checked here because it is what this feature relies on - it hands
	Mail::send() whatever the fill rendered to	*/
\Nino\Html::addFills( $appData, [ '[[/project/company/contact/email]]' => '', '[[/project/mail/address/owner]]' => '' ], '*' );
$mails = [];
$writeList( $appData, [] );
$freshMailWindow( $appData );
ninoWarnings();
$unresolved = submitNewsletter( $appData, [ 'email' => 'no-owner@example.org' ] );

check( 'with no owner mailbox installed, the mail still goes out and carries no Reply-To at all',
	( $unresolved['/nino/http/response']['statusCode'] ?? 0 ) === 200 && count( $mails ) === 1
	&& ( $mails[0]['replyTo'] ?? 'x' ) === ''
	&& str_contains( (string) ( $mails[0]['headers'] ?? '' ), 'Reply-To:' ) === false );

echo "\n";


// --- The signup form: a component and a shortcode ---------------------------------

echo "Modules\\Newsletter - [newsletter-signup], the signup form the Builder offers\n";

/*	The kernel reads the manifest ahead of the feature's own init() and registers its components with the
	Components module; the csrf shortcode and the fills of the page are put in the way serveNewsletter() has them.
	All of it on a copy of the project's data, which nothing else in this test sees	*/
$siteBelow = static function( array $base, string $directory ): array {
	$site = $base;
	$site['/nino/dir'] = $directory;
	$site['/nino/modules'] = [ '\\Nino\\Modules\\Newsletter' ];
	\Nino\Csrf::init( $site );
	\Nino\Html::init( $site );
	\Nino\Html::addFills( $site, \Nino\Html::bootFills( $site ), '*' );
	\Nino\Modules\Csrf::init( $site );
	\Nino\Modules\Components::init( $site );
	return $site;
};
$site = $siteBelow( $appData, '' );
ninoWarnings();

$declared = \Nino\Features::manifest( $dir )['components'] ?? [];
$registered = \Nino\Modules\Components::components( $site )['newsletter-signup'] ?? null;

check( 'the manifest declares one component, newsletter-signup, and the Components module registers exactly that schema as the feature\'s own',
	array_keys( $declared ) === [ 'newsletter-signup' ] && is_array( $registered ) && ( $registered['by'] ?? null ) === 'newsletter'
	&& array_intersect_key( $registered, $declared['newsletter-signup'] ) === $declared['newsletter-signup'] );
check( '...it needs no source and no loop: the form is its own, and says nothing of the element around it',
	( $registered['source'] ?? null ) === 'none' && ( $registered['loop'] ?? null ) === false );
check( '...it is drawn as a block and says what it draws on the outside - a form with the kernel\'s class - and where it may stand: a section, a card and a hero',
	( $registered['preview'] ?? null ) === 'block' && ( $registered['draws'] ?? null ) === 'form .nino-form' && ( $registered['in'] ?? null ) === [ 'section', 'card', 'hero' ] );
check( '...it carries a picture of its own and a label in both languages, and no group: a tool offers it under the feature',
	is_array( $registered['icon'] ?? null ) && $registered['icon'] !== [] && isset( $registered['group'] ) === false
	&& ( $registered['label']['en_US'] ?? '' ) !== '' && ( $registered['label']['de_DE'] ?? '' ) !== '' && $registered['label']['en_US'] !== $registered['label']['de_DE'] );
check( '...one choice, inline, a flag that is off unless the call turns it on',
	array_keys( $registered['attributes'] ?? [] ) === [ 'inline' ] && ( $registered['attributes']['inline']['type'] ?? null ) === 'bool'
	&& \Nino\Modules\Components::defaults( $site, 'newsletter-signup' ) === [ 'inline' => '0', 'class' => '' ] );
\Nino\Modules\Newsletter::init( $site );
check( 'the shortcode is registered by the component alone: the class draws no second one, however often it boots',
	in_array( 'newsletter-signup', \Nino\Html::shortcodes( $site ), true ) === true && count( \Nino\Callbacks::registered( $site, '/nino/html/shortcode/newsletter-signup' ) ) === 1 );

$drawn = \Nino\Html::renderHtml( $site, '[newsletter-signup]' );
$forms = newsletterSignupForms( $drawn );
$form = $forms[0] ?? [];

check( 'the shortcode draws one form with the kernel\'s classes - nino-form, which Nino.ui.js takes over, and nino-newsletter-form, which picks the endpoint and the words - and nothing inline or hidden',
	count( $forms ) === 1 && in_array( 'nino-form', $form['classes'], true ) === true && in_array( 'nino-newsletter-form', $form['classes'], true ) === true
	&& in_array( 'nino-form--inline', $form['classes'], true ) === false && ninoWarnings() === [] );
check( '...a post to the endpoint, below the directory of the website',
	( $form['method'] ?? '' ) === 'post' && ( $form['action'] ?? '' ) === '/.newsletter' && \Nino\Http::requestRoute( $site, $form['action'], 'POST' ) !== null );
check( '...the csrf token of the page in a hidden field and nothing else hidden', ( $form['hidden'] ?? [] ) === [ '_csrf' => \Nino\Csrf::getToken( $site ) ] && \Nino\Csrf::getToken( $site ) !== '' );
check( '...the honeypot the endpoint reads: a text field called location, empty, out of the tab order and hidden from assistive technology, the kernel\'s trap class on it',
	count( $form['traps'] ?? [] ) === 1 && $form['traps'][0]['type'] === 'text' && $form['traps'][0]['value'] === '' && $form['traps'][0]['tabindex'] === '-1'
	&& $form['traps'][0]['aria-hidden'] === 'true' && in_array( 'nino-form-trap', $form['traps'][0]['classes'], true ) === true );
check( '...one required email field called email, with a placeholder, and one submit button of the kernel\'s primary kind',
	count( $form['emails'] ?? [] ) === 1 && $form['emails'][0]['type'] === 'email' && $form['emails'][0]['required'] === true && $form['emails'][0]['placeholder'] !== ''
	&& count( $form['buttons'] ?? [] ) === 1 && $form['buttons'][0]['type'] === 'submit' && in_array( 'nino-btn--primary', $form['buttons'][0]['classes'], true ) === true );
check( '...a visible label that belongs to the email field, and an empty status line that is the first paragraph of the form, announced politely',
	count( $form['labels'] ?? [] ) === 1 && $form['labels'][0]['for'] === $form['emails'][0]['id'] && $form['emails'][0]['id'] !== '' && in_array( 'nino-sr-only', $form['labels'][0]['classes'], true ) === false
	&& count( $form['messages'] ?? [] ) === 1 && $form['messages'][0]['first'] === true && $form['messages'][0]['live'] === 'polite' && $form['messages'][0]['text'] === '' );
check( '...and the validation is left to Nino.ui.js, whose messages are the project\'s words, not the browser\'s', ( $form['novalidate'] ?? false ) === true );

$inline = newsletterSignupForms( \Nino\Html::renderHtml( $site, '[newsletter-signup inline="1"]' ) )[0] ?? [];
check( 'inline="1" puts the kernel\'s one-line class on the form and keeps the others', in_array( 'nino-form--inline', $inline['classes'] ?? [], true ) === true
	&& array_diff( $form['classes'] ?? [], $inline['classes'] ?? [] ) === [] );
check( '...and the label is read by screen readers only, still the label of the email field',
	count( $inline['labels'] ?? [] ) === 1 && in_array( 'nino-sr-only', $inline['labels'][0]['classes'], true ) === true && $inline['labels'][0]['for'] === ( $inline['emails'][0]['id'] ?? null ) );
$isInline = static fn( string $word ): bool => in_array( 'nino-form--inline', newsletterSignupForms( \Nino\Html::renderHtml( $site, '[newsletter-signup inline="'. $word. '"]' ) )[0]['classes'] ?? [], true );
check( '...by the words a flag of a component is read by', array_filter( [ 'yes', 'true', 'on' ], static fn( string $word ): bool => $isInline( $word ) === false ) === [] );
check( '...and not by the others: no, 0, and a word that is none leave the block form', array_filter( [ 'no', '0', 'maybe', '' ], $isInline ) === [] );

$classed = newsletterSignupForms( \Nino\Html::renderHtml( $site, '[newsletter-signup class="my-class [[/nino/dir]]"]' ) )[0] ?? [];
check( 'a class of the call joins those of the form, and its brackets are written so that nothing reads them again',
	in_array( 'my-class', $classed['classes'] ?? [], true ) === true && in_array( 'nino-form', $classed['classes'], true ) === true && ( $classed['action'] ?? '' ) === '/.newsletter'
	&& str_contains( \Nino\Html::renderHtml( $site, '[newsletter-signup class="a[[b"]' ), '&#91;&#91;b' ) === true );

$twice = newsletterSignupForms( \Nino\Html::renderHtml( $site, '[newsletter-signup][newsletter-signup inline="1"]' ) );
$ids = array_map( static fn( array $one ): string => $one['emails'][0]['id'] ?? '', $twice );
check( 'two forms on one page have two fields of two ids, and each label belongs to the field of its own form',
	count( $twice ) === 2 && count( array_unique( $ids ) ) === 2 && !in_array( '', $ids, true ) && $twice[0]['labels'][0]['for'] === $ids[0] && $twice[1]['labels'][0]['for'] === $ids[1] );

$shop = $siteBelow( $appData, '/shop' );
check( 'a website below a directory is posted to below it', ( newsletterSignupForms( \Nino\Html::renderHtml( $shop, '[newsletter-signup]' ) )[0]['action'] ?? '' ) === '/shop/.newsletter' );

/*	A post of the fields as the form renders them goes through the endpoint the way the hand-written form's does:
	the csrf guard first, then the callback of the route - the round Http::response() makes. The fields are what
	the document says, the address is what a visitor typed	*/
$postForm = static function( array &$site, array $form, string $address ): array {
	$_POST = $form['hidden'] + [ 'location' => $form['traps'][0]['value'], 'email' => $address ];
	$request = ninoVisit( $site, $form['action'], 'POST' );
	\Nino\Csrf::callbackResponse( $site, $request );
	\Nino\Modules\Newsletter::callbackResponse( $site, $request );
	$_POST = [];
	return $request;
};
$handWritten = static function( array &$site, string $address ): array {
	$_POST = [ '_csrf' => \Nino\Csrf::getToken( $site ), 'location' => '', 'email' => $address ];
	$request = ninoVisit( $site, '/.newsletter', 'POST' );
	\Nino\Csrf::callbackResponse( $site, $request );
	\Nino\Modules\Newsletter::callbackResponse( $site, $request );
	$_POST = [];
	return $request;
};

$writeList( $site, [] );
$mails = [];
$freshMailWindow( $site );
$signedUp = $postForm( $site, $form, 'form-signup@example.org' );
$same = $handWritten( $site, 'hand-signup@example.org' );
check( 'a post from the rendered form is a signup like the hand-written form\'s: 200, the same answer, the confirmation mail out',
	$signedUp['/nino/http/response']['statusCode'] === 200 && $signedUp['/nino/http/response']['body'] === $same['/nino/http/response']['body']
	&& $same['/nino/http/response']['statusCode'] === 200 && count( $mails ) === 2 && $mails[0]['to'] === 'form-signup@example.org' );
$entries = \Nino\Filesystem::getFileContent( $site, $subscribersPath, [] );
check( '...the address waits as pending, as it does for the form written by hand, until the link of the mail is pressed',
	array_column( $entries, 'status', 'email' ) === [ 'form-signup@example.org' => 'pending', 'hand-signup@example.org' => 'pending' ] );
$freshMailWindow( $site );
check( '...the same form refuses what the endpoint refuses: no address, a wrong one, a filled trap, and a post without its token',
	$postForm( $site, $form, '' )['/nino/http/response']['statusCode'] === 400 && $postForm( $site, $form, 'not-an-email' )['/nino/http/response']['statusCode'] === 400
	&& $postForm( $site, [ 'traps' => [ [ 'value' => 'bot' ] ] ] + $form, 'bot@example.org' )['/nino/http/response']['statusCode'] === 418
	&& $postForm( $site, [ 'hidden' => [] ] + $form, 'no-token@example.org' )['/nino/http/response']['statusCode'] === 403 );

/*	The words are the project's: the label, the placeholder and the button are keys of the Text panel that the
	unit delivers, in both languages, and the form shows what the language of the page says for each	*/
$keys = [ '[[/feature/newsletter/signup/label]]', '[[/feature/newsletter/signup/placeholder]]', '[[/feature/newsletter/label/submit]]' ];
$ofLocale = [];
foreach( [ 'en_US', 'de_DE' ] as $locale ) {
	$ofLocale[$locale] = include $dir. '/install/text/'. $locale. '.php';
	check( 'the unit brings the label, the placeholder and the button of the form in '. $locale. ', each with a word',
		array_filter( $keys, static fn( string $key ): bool => trim( (string) ( $ofLocale[$locale][$key] ?? '' ) ) === '' ) === [] );
	check( '...and activation merged them into text/'. $locale. '.php',
		array_filter( $keys, static fn( string $key ): bool => ( \Nino\Filesystem::getFileContent( $appData, '/text/'. $locale. '.php', [] )[$key] ?? null ) !== $ofLocale[$locale][$key] ) === [] );

	$site['./nino/locales/current'] = $locale;
	$spoken = newsletterSignupForms( \Nino\Html::renderHtml( $site, '[newsletter-signup]' ) )[0] ?? [];
	check( 'a page in '. $locale. ' shows the three words of that language in the form, none of them left as a key',
		( $spoken['labels'][0]['text'] ?? null ) === $ofLocale[$locale][$keys[0]] && ( $spoken['emails'][0]['placeholder'] ?? null ) === $ofLocale[$locale][$keys[1]]
		&& ( $spoken['buttons'][0]['text'] ?? null ) === $ofLocale[$locale][$keys[2]] && str_contains( \Nino\Html::renderHtml( $site, '[newsletter-signup]' ), '[[' ) === false );
}
check( '...in two languages that are not one: the label and the button differ',
	$ofLocale['en_US'][$keys[0]] !== $ofLocale['de_DE'][$keys[0]] && $ofLocale['en_US'][$keys[2]] !== $ofLocale['de_DE'][$keys[2]] );
$site['./nino/locales/current'] = 'en_US';

echo "\n";


// --- The panel's script, where node is on the path ------------------------------
//
// newsletter-js-smoke.js beside this file draws the list over a dom stand-in,
// with the kernel's own Nino.js running in it - the one of the checkout this
// suite runs against, which is handed on; this suite runs it too where node is
// on the path, the way stats-smoke.php runs its twin, so bin/check.sh and CI
// cover both halves in one go
$jsTest	= __DIR__. '/newsletter-js-smoke.js';
$node		= function_exists( 'shell_exec' ) === true ? trim( (string) @shell_exec( 'command -v node 2>/dev/null' ) ) : '';

if( $node === '' || function_exists( 'exec' ) === false ) {
	echo "  --  - node is not available here: newsletter-js-smoke.js was NOT run\n";
} else {
	$output = []; $status = 1;
	exec( 'NINO_ROOT='. escapeshellarg( $root ). ' '. escapeshellarg( $node ). ' '. escapeshellarg( $jsTest ). ' 2>&1', $output, $status );
	$summary = (string) end( $output );
	check( 'newsletter-js-smoke.js passes - '. ( $summary === '' ? 'no output' : $summary ), $status === 0 );
}

ninoDone( $appData );
