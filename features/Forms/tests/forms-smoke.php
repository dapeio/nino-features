<?php
declare(strict_types=1);

/**
 *	Nino
 *	forms-smoke.php		Contract test for the Forms feature (Modules\Forms): the
 *										manifest and the activation through \Nino\Features, the
 *										definitions and what normalize() makes of them, the [form]
 *										shortcode, the one endpoint every form is submitted to
 *										with its refusals and its spam guards, the mail it hands
 *										to the transport, what it records and prunes, the
 *										workbench panel with its permission, and the restore
 *										merge that keeps a submission the backup never saw.
 *										Travels with the feature and runs against the checkout
 *										three levels up, or the one NINO_ROOT names (see
 *										tests/harness.php there).
 *
 *	Usage: php features/Forms/tests/forms-smoke.php
 *	       NINO_ROOT=../nino php features/Forms/tests/forms-smoke.php
 */

// The checkout this test runs against: three levels up when the feature sits
// in a project's features/, else the one NINO_ROOT names (a checkout beside
// the catalogue, say). The features root is this feature's own parent either
// way, so the kernel's autoloader serves the class from here
$root = getenv( 'NINO_ROOT' ) ?: dirname( __DIR__, 3 );
defined( 'NINO_FEATURES_DIR' ) === true || define( 'NINO_FEATURES_DIR', dirname( __DIR__, 2 ) );
require $root. '/tests/harness.php';

$appData = ninoSandbox( 'forms' );
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$appData['/nino/dir'] = '';
// What \Nino\AppData::init() sets from config.php in a real request, and the
// sandbox does not: without it \Nino\Html::getFills() reads no text file at
// all, so a label written as a fill would arrive as its own literal
$appData['/nino/locales/textfiles'] = '/text';

// The two core modules a project always carries and this feature renders
// through: [template ...] for its mail bodies and [csrf] for the token in
// the form it draws. Both are in \Nino\Install\Setup::CORE_MODULES, so a
// project cannot be without them - the sandbox simply starts with none
\Nino\Modules\Template::init( $appData );
\Nino\Modules\Csrf::init( $appData );

// Every mail the feature hands to \Nino\Mail lands here instead of going out -
// the kernel's own transport callback, which is exactly how a project swaps
// mail() for something else (see Mail::TRANSPORT in the kernel)
$sent = [];
\Nino\Callbacks::registerCallback( $appData, \Nino\Mail::TRANSPORT, static function( array &$appData, array &$mail ) use ( &$sent ): void {
	$sent[] = $mail;
	$mail['sent'] = true;
} );

/**
 *	Submit one form the way the browser does: every field as a post value,
 *	the honeypot empty unless the caller fills it
 */
function submitForm( array &$appData, array $post ): array {
	$_POST = array_merge( [ 'location' => '' ], $post );
	$request = [ '/nino/http/response' => [ 'statusCode' => 200 ] ];
	\Nino\Modules\Forms::callbackResponse( $appData, $request );
	return $request;
}

function callFormsAdmin( array &$appData, string $action, array $data = [] ): array {
	$request = [ '/nino/http/response' => [ 'statusCode' => 200 ] ];
	$_POST = [ 'action' => $action, 'data' => json_encode( $data ) ];
	\Nino\Admin\Admin::handlePost( $appData, $request );
	return [ $request['/nino/http/response']['statusCode'], $request['/nino/http/response']['body'] ?? null ];
}

/** Every recorded submission of one form, oldest first */
function recorded( array &$appData, string $key ): array {
	return \Nino\Modules\Forms\Admin::entries( $appData, $key );
}


// --- The feature -------------------------------------------------------------

echo "The feature - manifest and activation\n";

$dir 			= dirname( __DIR__ );
$manifest	= \Nino\Features::manifest( $dir );

check( 'the manifest validates, key "forms"', is_array( $manifest ) === true && $manifest['key'] === 'forms' && ninoWarnings() === [] );
check( 'the feature is written for this kernel', is_array( $manifest ) === true && \Nino\Features::satisfies( $manifest['nino'] ) === true );
check( 'it names and describes itself in both interface languages', is_array( $manifest ) === true
	&& \Nino\Features::localized( $manifest['name'], 'de_DE' ) !== \Nino\Features::localized( $manifest['name'], 'en_US' )
	&& \Nino\Features::localized( $manifest['description'], 'de_DE' ) !== \Nino\Features::localized( $manifest['description'], 'en_US' ) );
check( 'it declares the one directory it owns under data/', is_array( $manifest ) === true && $manifest['data'] === [ '/data/forms' ] );
check( 'it declares the settings the panel offers, retention and the spam guards among them', is_array( $manifest ) === true
	&& array_keys( $manifest['settings'] ) === [ 'retention', 'rateLimit', 'minSeconds', 'blocklist', 'store' ] );
check( 'it needs no other feature and no php extension', is_array( $manifest ) === true && $manifest['requires'] === [] && ( $manifest['php']['ext'] ?? [] ) === [] );

\Nino\AppData::writeContentData( $appData, [ '/nino/modules', '/nino/locales/available', '/nino/locales/native' ] );
ninoWarnings();

check( 'the Features registry lists it, inactive', ( \Nino\Features::get( $appData, 'forms' )['active'] ?? null ) === false );
check( 'activation succeeds', \Nino\Features::activate( $appData, 'forms' ) === true );
check( 'the class is listed in /nino/modules and the version recorded', in_array( '\\Nino\\Modules\\Forms', \Nino\Filesystem::getFileContent( $appData, '/config.php', [] )['/nino/modules'], true ) === true
	&& \Nino\Features::get( $appData, 'forms' )['installed'] === $manifest['version'] );
check( 'the unit copied the two mail templates and the frame they include', array_filter( [ 'mail-form-owner.tpl', 'mail-form-user.tpl', 'mail-header.tpl', 'mail-footer.tpl' ],
	static fn( string $tpl ): bool => \Nino\Filesystem::fileExists( $appData, '/templates/'. $tpl ) === false ) === [] );
check( 'the unit merged the fills the mail and the default form read', \Nino\Filesystem::getFileContent( $appData, '/text/de_DE.php', [] )['[[/form/label/message]]'] === 'Nachricht'
	&& \Nino\Filesystem::getFileContent( $appData, '/text/global.php', [] )['[[/form/email/owner]]'] === 'contact@example.com' );

echo "\n";


// --- Standing down for the kernel's own contact form -------------------------

echo "Modules\\Forms - it stands down while Nino's own contact form is on\n";

$withKernelForm = $appData;
$withKernelForm['/nino/modules'][] = '\\Nino\\Modules\\Form';
check( 'the kernel module is seen however it is spelled', \Nino\Modules\Forms::kernelFormActive( $withKernelForm ) === true );
\Nino\Modules\Forms::init( $withKernelForm );
check( 'and then nothing at all is registered - one endpoint, one handler', isset( $withKernelForm['/nino/http/routes']['POST://.form'] ) === false
	&& isset( $withKernelForm['./nino/html/shortcodes']['form'] ) === false
	&& isset( $withKernelForm['./nino/callbacks']['/nino/admin/restore'] ) === false );
unset( $withKernelForm );

check( 'without it, the feature knows it may run', \Nino\Modules\Forms::kernelFormActive( $appData ) === false );

\Nino\Modules::callModules( $appData, 'init' );
check( 'init registers the endpoint every form posts to', isset( $appData['/nino/http/routes']['POST://.form'] ) === true
	&& isset( $appData['./nino/callbacks']['/nino/http/response/POST://.form'] ) === true );
check( 'init registers the [form] shortcode', isset( $appData['./nino/html/shortcodes']['form'] ) === true );
check( 'init registers the restore merge on /nino/admin/restore', isset( $appData['./nino/callbacks']['/nino/admin/restore'] ) === true );

echo "\n";


// --- The definitions ---------------------------------------------------------

echo "Modules\\Forms - the definitions, and what normalize() makes of one\n";

$forms = \Nino\Modules\Forms::forms( $appData );
check( 'with nothing stored, the built-in contact form is what is offered', count( $forms ) === 1 && $forms[0]['key'] === 'contact'
	&& array_column( $forms[0]['fields'], 'name' ) === [ 'name', 'email', 'cat', 'message' ] );
check( 'nothing was written to disk to answer that', \Nino\Filesystem::fileExists( $appData, \Nino\Modules\Forms::DEFINITIONS ) === false );
check( 'an empty key means the first form defined - which is what a page posting no key belongs to', \Nino\Modules\Forms::form( $appData, '' )['key'] === 'contact' );
check( 'a key no form has is null, not the default', \Nino\Modules\Forms::form( $appData, 'nowhere' ) === null );

check( 'a definition without a slug key is refused', \Nino\Modules\Forms::normalize( [ 'key' => 'Not A Key', 'fields' => [ [ 'name' => 'a' ] ] ] ) === null );
check( 'a definition without one usable field is refused', \Nino\Modules\Forms::normalize( [ 'key' => 'ok', 'fields' => [] ] ) === null );
// Four the endpoint reads off the post, and the two a recorded submission
// carries beside its fields - the panel lays a submission out flat, so a field
// called 'id' would take the place of the identity its delete button aims at
check( 'a field name something else already owns is dropped, and with it the whole form when nothing is left',
	\Nino\Modules\Forms::RESERVED === [ 'form', 'location', '_csrf', '_t', 'id', 'date' ]
	&& \Nino\Modules\Forms::normalize( [ 'key' => 'ok', 'fields' => array_map( static fn( string $n ): array => [ 'name' => $n ], \Nino\Modules\Forms::RESERVED ) ] ) === null );

$normalized = \Nino\Modules\Forms::normalize( [
	'key'		=> 'Quote',
	'name'	=> '  A quote  ',
	'to'		=> 'not-an-address',
	'fields'	=> [
		[ 'name' => 'email', 'type' => 'email', 'required' => true ],
		[ 'name' => 'email', 'type' => 'text' ],
		[ 'name' => '1bad', 'type' => 'text' ],
		[ 'name' => 'mode', 'type' => 'nonsense', 'options' => [ 'a', '  b  ', '' ] ],
	],
	'ownerTemplate'	=> '/templates/../../etc/passwd',
] );
check( 'a key is lowercased and a name trimmed', $normalized !== null && $normalized['key'] === 'quote' && $normalized['name'] === 'A quote' );
check( 'a recipient that is not an address is dropped rather than mailed to', $normalized['to'] === '' );
check( 'a duplicate field name is dropped, and so is one that is not an identifier', array_column( $normalized['fields'], 'name' ) === [ 'email', 'mode' ] );
check( 'a type the feature does not know falls back to text, and empty options are dropped and trimmed', $normalized['fields'][1]['type'] === 'text' && $normalized['fields'][1]['options'] === [ 'a', 'b' ] );
check( 'a template path that walks out of the project falls back to the feature\'s own', $normalized['ownerTemplate'] === '/templates/mail-form-owner' );
check( 'a field that says nothing about itself is optional and labelled by its name', $normalized['fields'][0]['required'] === true && $normalized['fields'][1]['required'] === false && $normalized['fields'][1]['label'] === 'mode' );

echo "\n";

// --- The shortcode -----------------------------------------------------------

echo "[form] - the markup a page renders\n";

$html = \Nino\Html::renderHtml( $appData, '[form]' );

check( 'it renders a form posting to the one endpoint', str_starts_with( $html, '<form class="nino-form"' ) === true && str_contains( $html, 'action="/.form"' ) === true && str_contains( $html, 'method="post"' ) === true );
check( 'it carries the csrf token the kernel checks, rendered rather than left as a shortcode', str_contains( $html, 'name="_csrf"' ) === true && str_contains( $html, '[csrf]' ) === false );
check( 'it says which form it is, so one page can carry several', str_contains( $html, '<input type="hidden" name="form" value="contact">' ) === true );
check( 'it stamps the moment it was drawn, which is what the speed trap reads', preg_match( '/name="_t" value="\d{10}"/', $html ) === 1 );
check( 'every declared field is there, by its own name and type', str_contains( $html, 'name="name" class="nino-form-input" required' ) === true
	&& str_contains( $html, 'type="email" id="form-contact-email"' ) === true && str_contains( $html, '<textarea id="form-contact-message" name="message"' ) === true );
check( 'a label written as a fill is resolved, not printed', str_contains( $html, 'Nachricht' ) === true && str_contains( $html, '[[/form/label/message]]' ) === false );
check( 'the honeypot, the live region and the submit the shared script drives are all there', str_contains( $html, 'name="location"' ) === true
	&& str_contains( $html, 'class="nino-form-message" aria-live="polite"' ) === true && str_contains( $html, 'type="submit"' ) === true );
check( 'a key no form has renders nothing at all', \Nino\Html::renderHtml( $appData, '[form key="nowhere"]' ) === '' );

echo "\n";


// --- The endpoint ------------------------------------------------------------

echo "POST /.form - what is refused, and what is sent and recorded\n";

$sent = [];

$blocked = [ './nino/csrf/blocked' => true, '/nino/http/response' => [ 'statusCode' => 403 ] ];
$_POST = [ 'name' => 'Jo', 'email' => 'jo@example.com', 'message' => 'Hallo', 'location' => '' ];
\Nino\Modules\Forms::callbackResponse( $appData, $blocked );
check( 'a request the csrf callback already refused is left alone', $blocked['/nino/http/response']['statusCode'] === 403 && $sent === [] && recorded( $appData, 'contact' ) === [] );

check( 'a key no form has is a 404 - a page pointing at a form that was renamed, not spam',
	submitForm( $appData, [ 'form' => 'nowhere', 'name' => 'Jo', 'email' => 'jo@example.com', 'message' => 'Hi' ] )['/nino/http/response']['statusCode'] === 404 );

check( 'a filled honeypot is a 418, and nothing is sent or written',
	submitForm( $appData, [ 'name' => 'Jo', 'email' => 'jo@example.com', 'message' => 'Hi', 'location' => 'filled-by-a-bot' ] )['/nino/http/response']['statusCode'] === 418
	&& $sent === [] && recorded( $appData, 'contact' ) === [] );

check( 'a required field left empty is a 400 - the one refusal the visitor can act on',
	submitForm( $appData, [ 'name' => '', 'email' => 'jo@example.com', 'message' => 'Hi' ] )['/nino/http/response']['statusCode'] === 400 );
check( 'an address that is not one is a 400 too', submitForm( $appData, [ 'name' => 'Jo', 'email' => 'not-an-address', 'message' => 'Hi' ] )['/nino/http/response']['statusCode'] === 400 );
check( 'neither sent nor recorded anything', $sent === [] && recorded( $appData, 'contact' ) === [] );

check( 'the settings save', \Nino\Features::saveSettings( $appData, 'forms', [ 'retention' => 3, 'rateLimit' => 0, 'minSeconds' => 3, 'blocklist' => "casino\n", 'store' => true ] ) === [] );
check( 'a blocked word anywhere in the submission answers exactly like the honeypot, so a spammer learns nothing',
	submitForm( $appData, [ 'name' => 'Jo', 'email' => 'jo@example.com', 'message' => 'Best CASINO bonus' ] )['/nino/http/response']['statusCode'] === 418
	&& $sent === [] && recorded( $appData, 'contact' ) === [] );
check( 'a form that came back faster than a person could fill it is a 418 too',
	submitForm( $appData, [ 'name' => 'Jo', 'email' => 'jo@example.com', 'message' => 'Hi', '_t' => (string) time() ] )['/nino/http/response']['statusCode'] === 418 );
check( 'a form drawn long enough ago passes, and one that carries no stamp at all is never judged by it',
	submitForm( $appData, [ 'name' => 'Jo', 'email' => 'jo@example.com', 'message' => 'Hi', '_t' => (string) ( time() - 600 ) ] )['/nino/http/response']['statusCode'] === 200 );

$sent = [];
$ok = submitForm( $appData, [ 'name' => 'Jo <b>Bloggs</b>', 'email' => 'jo@example.com', 'cat' => 'Angebot', 'message' => "Zeile 1\nZeile 2" ] );
check( 'a good submission answers 200 with the status the shared script reads', $ok['/nino/http/response']['statusCode'] === 200 && $ok['/nino/http/response']['body'] === [ 'status' => 'ok' ] );
check( 'two mails go out: the owner notification and the visitor\'s confirmation', count( $sent ) === 2 );
check( 'the owner mail goes to the address the text fill names, and can be replied to the visitor', $sent[0]['to'] === 'contact@example.com' && $sent[0]['replyTo'] === 'jo@example.com' );
check( 'the confirmation goes to the visitor, from the owner', $sent[1]['to'] === 'jo@example.com' && $sent[1]['replyTo'] === 'contact@example.com' );
check( 'the owner mail carries every field as a table, under the labels the form declares', str_contains( $sent[0]['body'], '<th>Nachricht</th>' ) === true && str_contains( $sent[0]['body'], 'Zeile 1<br />' ) === true );
check( 'a submitted value reaches the mail escaped, never as markup', str_contains( $sent[0]['body'], '&lt;b&gt;Bloggs&lt;/b&gt;' ) === true && str_contains( $sent[0]['body'], '<b>Bloggs</b>' ) === false );
check( 'the templates the unit copied rendered - the mail is a whole html document', str_contains( $sent[0]['body'], '<!DOCTYPE html>' ) === true && str_contains( $sent[0]['body'], '[template' ) === false );

$entries = recorded( $appData, 'contact' );
check( 'the submission is recorded, once', count( $entries ) === 2 );
$entry = $entries[1];
check( 'with an identity of its own, the moment, the form and the client', preg_match( '/^[0-9a-f]{16}$/', $entry['id'] ) === 1 && $entry['form'] === 'contact' && $entry['ip'] === '127.0.0.1'
	&& preg_match( '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $entry['date'] ) === 1 );
check( 'and every field as it was submitted, escaped the way the panel decodes it again', $entry['fields']['name'] === 'Jo &lt;b&gt;Bloggs&lt;/b&gt;' && $entry['fields']['cat'] === 'Angebot' );
check( 'it lands in this month\'s file of this form', \Nino\Filesystem::fileExists( $appData, \Nino\Modules\Forms::DIR. '/contact.'. date( 'Y-m' ). '.php' ) === true );

check( 'with recording switched off the mail still goes out and nothing is written', \Nino\Features::saveSettings( $appData, 'forms', [ 'store' => false ] ) === []
	&& submitForm( $appData, [ 'name' => 'Jo', 'email' => 'jo@example.com', 'message' => 'Hi', '_t' => (string) ( time() - 600 ) ] )['/nino/http/response']['statusCode'] === 200
	&& count( recorded( $appData, 'contact' ) ) === 2 );
\Nino\Features::saveSettings( $appData, 'forms', [ 'store' => true ] );

check( 'a rate limit turns an ip away once it is spent, without sending or recording', ( static function( array &$appData, array &$sent ): bool {
	\Nino\Features::saveSettings( $appData, 'forms', [ 'rateLimit' => 2 ] );
	$codes = [];
	for( $i = 0; $i < 4; $i++ )
		$codes[] = submitForm( $appData, [ 'name' => 'Jo', 'email' => 'jo@example.com', 'message' => 'Hi '. $i, '_t' => (string) ( time() - 600 ) ] )['/nino/http/response']['statusCode'];
	return $codes === [ 200, 200, 429, 429 ];
} )( $appData, $sent ) );
\Nino\Features::saveSettings( $appData, 'forms', [ 'rateLimit' => 0 ] );

echo "\n";

// --- The panel ---------------------------------------------------------------

echo "The panel - the forms, their fields and their submissions\n";

check( 'it is a content entry with three mount points and a permission of its own', \Nino\Modules\Forms\Admin::nav() === [ 'forms', '/_admin/nav/forms', 60, 'content' ]
	&& \Nino\Modules\Forms\Admin::panes() === [ 'forms-list', 'forms-form', 'forms-entries' ]
	&& \Nino\Modules\Forms\Admin::perm() === '/_admin/forms/manage' );
check( 'it offers exactly the five actions', array_keys( \Nino\Modules\Forms\Admin::actions() ) === [ 'forms/list', 'forms/save', 'forms/delete', 'forms/entries', 'forms/entry-delete' ] );
// The shared .nino-form script posts every field's .value unconditionally, and
// an unticked checkbox's value is still "on" - so a checkbox cannot be offered
// until Nino's own script sends its checked state (see Forms::TYPES)
check( 'no checkbox is offered, because the kernel\'s form script cannot submit one', in_array( 'checkbox', \Nino\Modules\Forms::TYPES, true ) === false
	&& \Nino\Modules\Forms::normalize( [ 'key' => 'k', 'fields' => [ [ 'name' => 'a', 'type' => 'checkbox' ] ] ] )['fields'][0]['type'] === 'text' );
check( 'the workbench finds it while the feature is active', isset( \Nino\Admin\Admin::panels( $appData )['forms'] ) === true );

$panelActions = [
	'forms/list'					=> [],
	'forms/save'					=> [ 'form' => [ 'key' => 'x', 'fields' => [ [ 'name' => 'a' ] ] ] ],
	'forms/delete'				=> [ 'key' => 'contact' ],
	'forms/entries'				=> [ 'key' => 'contact' ],
	'forms/entry-delete'	=> [ 'key' => 'contact', 'id' => str_repeat( 'a', 16 ) ],
];

// The gates a panel action is not what this tests: a backup and an activity
// log entry per call would only slow it down
$appData['/nino/admin/backups']	= false;
$appData['/nino/admin/logs']		= false;
$appData['/nino/auth/user']			= [];
$appData['/nino/auth/roles']		= [];

foreach( $panelActions as $action => $data )
	check( $action. ' is 401 without an account', callFormsAdmin( $appData, $action, $data ) === [ 401, [ 'error' => 'not logged in' ] ] );

\Nino\Auth::insertUser( $appData, 'editor@example.com', 'correct horse battery staple', [ '/_admin/text/manage' ] );
\Nino\Auth::insertUser( $appData, 'dev@example.com', 'correct horse battery staple', [ \Nino\Modules\Forms\Admin::MANAGE_PERM ] );

\Nino\Auth::loginUser( $appData, 'editor@example.com', 'correct horse battery staple' );
foreach( $panelActions as $action => $data )
	check( $action. ' is 403 without the permission', callFormsAdmin( $appData, $action, $data ) === [ 403, [ 'error' => 'not allowed' ] ] );

\Nino\Auth::loginUser( $appData, 'dev@example.com', 'correct horse battery staple' );

[ $status, $body ] = callFormsAdmin( $appData, 'forms/list' );
check( 'the list answers the forms, the field types the panel may offer, and the two states it draws differently', $status === 200
	&& array_keys( $body ) === [ 'forms', 'types', 'default', 'blocked' ]
	&& $body['types'] === \Nino\Modules\Forms::TYPES && $body['default'] === true && $body['blocked'] === false );
check( 'each form arrives with how many submissions it has on file', $body['forms'][0]['key'] === 'contact' && $body['forms'][0]['entries'] === count( recorded( $appData, 'contact' ) ) );

[ $status, $body ] = callFormsAdmin( $appData, 'forms/save', [ 'key' => '', 'form' => [
	'key' => 'quote', 'name' => 'Quote', 'to' => 'sales@example.com', 'confirm' => false,
	'fields' => [ [ 'name' => 'company', 'label' => 'Company', 'type' => 'text', 'required' => true ] ],
] ] );
check( 'a new form saves, normalized as the endpoint will read it', $status === 200 && $body['form']['key'] === 'quote' && $body['form']['to'] === 'sales@example.com' );
check( 'and the definitions file now exists, carrying the built-in default beside it', \Nino\Filesystem::fileExists( $appData, \Nino\Modules\Forms::DEFINITIONS ) === true
	&& array_column( \Nino\Modules\Forms::forms( $appData ), 'key' ) === [ 'contact', 'quote' ] );
check( 'so the list stops calling itself a default', callFormsAdmin( $appData, 'forms/list' )[1]['default'] === false );

check( 'a second form under a key another one already has is refused', callFormsAdmin( $appData, 'forms/save', [ 'key' => '', 'form' => [
	'key' => 'quote', 'name' => 'Other', 'fields' => [ [ 'name' => 'a', 'type' => 'text' ] ] ] ] )[0] === 400 );
check( 'a form with no usable field is refused', callFormsAdmin( $appData, 'forms/save', [ 'key' => '', 'form' => [ 'key' => 'empty', 'fields' => [] ] ] )[0] === 400 );

[ $status, $body ] = callFormsAdmin( $appData, 'forms/save', [ 'key' => 'quote', 'form' => [
	'key' => 'offer', 'name' => 'Offer', 'fields' => [ [ 'name' => 'company', 'type' => 'text' ] ] ] ] );
check( 'renaming a form stays one form rather than becoming two', $status === 200
	&& array_column( \Nino\Modules\Forms::forms( $appData ), 'key' ) === [ 'contact', 'offer' ] );

check( 'a key that is not a slug is refused before anything is read', callFormsAdmin( $appData, 'forms/delete', [ 'key' => '../../etc' ] )[0] === 400
	&& callFormsAdmin( $appData, 'forms/entries', [ 'key' => 'Nope!' ] )[0] === 400 );

[ $status, $body ] = callFormsAdmin( $appData, 'forms/entries', [ 'key' => 'contact' ] );
check( 'the submissions of one form come back newest first, with the columns to draw them under', $status === 200
	&& array_keys( $body ) === [ 'key', 'name', 'columns', 'entries' ]
	&& array_keys( $body['columns'] ) === [ 'name', 'email', 'cat', 'message' ]
	&& $body['columns']['message'] === 'Nachricht'
	&& count( $body['entries'] ) === count( recorded( $appData, 'contact' ) )
	&& $body['entries'][0]['date'] >= $body['entries'][ count( $body['entries'] ) - 1 ]['date'] );

$id = $body['entries'][0]['id'];
$before = count( recorded( $appData, 'contact' ) );
check( 'one submission is deleted by the identity it was recorded with', callFormsAdmin( $appData, 'forms/entry-delete', [ 'key' => 'contact', 'id' => $id ] )[0] === 200
	&& count( recorded( $appData, 'contact' ) ) === $before - 1
	&& in_array( $id, array_column( recorded( $appData, 'contact' ), 'id' ), true ) === false );
check( 'an id no submission has is refused rather than silently doing nothing', callFormsAdmin( $appData, 'forms/entry-delete', [ 'key' => 'contact', 'id' => str_repeat( 'f', 16 ) ] )[0] === 400 );
check( 'an id that is not one is refused too', callFormsAdmin( $appData, 'forms/entry-delete', [ 'key' => 'contact', 'id' => '../x' ] )[0] === 400 );

// A form whose key is a prefix of another's would read the other's months
// with a careless glob - "contact" must never see "contact-sales"
\Nino\Filesystem::putFileContent( $appData, \Nino\Modules\Forms::DIR. '/contact-sales.'. date( 'Y-m' ). '.php', [ [ 'id' => str_repeat( '1', 16 ), 'date' => '2026-01-01 00:00:00', 'form' => 'contact-sales', 'ip' => '', 'fields' => [] ] ] );
check( 'a form whose key is a prefix of another\'s reads only its own submissions', count( recorded( $appData, 'contact-sales' ) ) === 1
	&& array_column( recorded( $appData, 'contact' ), 'form' ) === array_fill( 0, count( recorded( $appData, 'contact' ) ), 'contact' ) );

$kept = count( recorded( $appData, 'contact' ) );
check( 'deleting a form leaves its submissions - they are what someone sent, not part of the definition', callFormsAdmin( $appData, 'forms/delete', [ 'key' => 'offer' ] )[0] === 200
	&& array_column( \Nino\Modules\Forms::forms( $appData ), 'key' ) === [ 'contact' ]
	&& count( recorded( $appData, 'contact' ) ) === $kept );

check( 'the dashboard tile counts every form\'s submissions', \Nino\Modules\Forms\Admin::count( $appData ) >= $kept );
// The posted 'key' is the one a form had *before* the edit, empty while it is
// being created - so a save is logged under the key it ends up with
check( 'the activity log names the form a save produced, not the key it replaced', \Nino\Modules\Forms\Admin::log( 'forms/save', [ 'key' => '', 'form' => [ 'key' => 'quote' ] ] ) === 'Save form "quote"'
	&& \Nino\Modules\Forms\Admin::log( 'forms/save', [ 'key' => 'quote', 'form' => [ 'key' => 'offer' ] ] ) === 'Save form "offer"' );
check( 'a delete names the form it removed, and a read logs nothing', \Nino\Modules\Forms\Admin::log( 'forms/delete', [ 'key' => 'quote' ] ) === 'Delete form "quote"'
	&& \Nino\Modules\Forms\Admin::log( 'forms/list', [] ) === '' );

echo "\n";


// --- Retention and restore ---------------------------------------------------

echo "Modules\\Forms - what is pruned, and what a restore merges\n";

$old = \Nino\Modules\Forms::DIR. '/contact.2020-01.php';
\Nino\Filesystem::putFileContent( $appData, $old, [ [ 'id' => str_repeat( '9', 16 ), 'date' => '2020-01-01 00:00:00', 'form' => 'contact', 'ip' => '', 'fields' => [] ] ] );
check( 'a month older than the retention window is on file before the prune', \Nino\Filesystem::fileExists( $appData, $old ) === true );
\Nino\Modules\Forms::prune( $appData );
check( 'and gone after it, while this month stays', \Nino\Filesystem::fileExists( $appData, $old ) === false
	&& \Nino\Filesystem::fileExists( $appData, \Nino\Modules\Forms::DIR. '/contact.'. date( 'Y-m' ). '.php' ) === true );
check( 'the definitions and the rate counter beside them are never read as a month', \Nino\Filesystem::fileExists( $appData, \Nino\Modules\Forms::DEFINITIONS ) === true );

$staging	= ninoSandboxDir( $appData ). '/staging';
$month		= 'contact.'. date( 'Y-m' ). '.php';
mkdir( $staging. '/data/forms', 0755, true );

$live			= \Nino\Filesystem::getFileContent( $appData, \Nino\Modules\Forms::DIR. '/'. $month, [] );
$backedUp	= [ [ 'id' => str_repeat( 'b', 16 ), 'date' => '2026-01-01 00:00:00', 'form' => 'contact', 'ip' => '', 'fields' => [ 'name' => 'From the backup' ] ] ];
file_put_contents( $staging. '/data/forms/'. $month, '<?php return '. var_export( $backedUp, true ). ';' );

$args = [ 'dataDir' => \Nino\Filesystem::path( $appData, '/data' ), 'staging' => $staging ];
\Nino\Modules\Forms::callbackRestore( $appData, $args );

$merged = include $staging. '/data/forms/'. $month;
check( 'a restore keeps what the backup carried and what arrived since, both', count( $merged ) === count( $live ) + 1
	&& in_array( str_repeat( 'b', 16 ), array_column( $merged, 'id' ), true ) === true );
check( 'nothing is duplicated, and the month is left in date order', count( array_unique( array_column( $merged, 'id' ) ) ) === count( $merged )
	&& array_column( $merged, 'date' ) === ( static function( array $m ): array { $d = array_column( $m, 'date' ); sort( $d ); return $d; } )( $merged ) );
check( 'a staging directory without this feature\'s data is left alone', ( static function( array &$appData ): bool {
	$empty = ninoSandboxDir( $appData ). '/staging-empty';
	mkdir( $empty, 0755, true );
	$args = [ 'dataDir' => \Nino\Filesystem::path( $appData, '/data' ), 'staging' => $empty ];
	\Nino\Modules\Forms::callbackRestore( $appData, $args );
	return is_dir( $empty. '/data' ) === false;
} )( $appData ) );

echo "\n";

ninoDone( $appData );
