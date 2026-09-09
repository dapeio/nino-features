<?php
declare(strict_types=1);

/**
 *	Nino
 *	forms-smoke.php		Contract test for the Forms feature (Modules\Forms).
 *
 *										The feature replaces nothing: \Nino\Form is the engine
 *										and \Nino\Modules\Form owns the route, both untouched.
 *										So what is checked here is only what the feature adds -
 *										the [form] shortcode over a definition the kernel reads,
 *										the guards on the kernel's own route callback at priority
 *										1, and the builder that writes '/nino/form/forms' into
 *										config.php - plus the thing that matters most about a
 *										feature like this: that switching it on changes nothing
 *										about a submission that should go through, and switching
 *										it off leaves every form working.
 *
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

// The engine this feature extends, and the reason its manifest names ^1.1.
// A checkout that predates it cannot run a line of what follows, and a stack
// trace two screens down is a worse way to learn that than one sentence here
// (tests/build-smoke.php in this repository does the same for \Nino\Features)
if( class_exists( '\Nino\Form' ) === false ) {
	fwrite( STDERR, 'The Nino checkout at '. $root. ' ('. \Nino\VERSION. ') has no \Nino\Form - the Forms feature extends the kernel\'s form engine, which arrived with it'. "\n" );
	exit( 2 );
}

$appData = ninoSandbox( 'forms' );
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$appData['/nino/dir'] = '';
// What \Nino\AppData::init() sets from config.php in a real request, and the
// sandbox does not: without it \Nino\Html::getFills() reads no text file at
// all, so a label written as a fill would arrive as its own literal
$appData['/nino/locales/textfiles'] = '/text';

// The kernel module that owns POST /.form, and the two core modules a project
// always carries: [template ...] for the mail bodies the engine renders and
// [csrf] for the token in the form this feature draws. All three are things a
// project cannot be without - the sandbox simply starts with none
$appData['/nino/modules'][] = '\\Nino\\Modules\\Form';
\Nino\Modules\Form::init( $appData );
\Nino\Modules\Template::init( $appData );
\Nino\Modules\Csrf::init( $appData );

\Nino\Html::addFills( $appData, [
	'[[/form/email/owner]]'		=> 'owner@example.com',
	'[[/form/subject/owner]]'	=> 'New inquiry',
	'[[/form/subject/user]]'	=> 'Thanks',
	'[[/form/label/name]]'		=> 'Name',
	'[[/form/label/email]]'		=> 'E-Mail',
	'[[/form/label/cat]]'			=> 'Subject',
	'[[/form/label/message]]'	=> 'Message',
	'[[/form/label/submit]]'	=> 'Send',
	'[[/form/required]]'			=> 'required',
], '*' );

// Every mail lands here instead of going out - the kernel's own transport
// callback, which is exactly how a project swaps mail() for something else
$sent = [];
\Nino\Callbacks::registerCallback( $appData, \Nino\Mail::TRANSPORT, static function( array &$appData, array &$mail ) use ( &$sent ): void {
	$sent[] = $mail;
	$mail['sent'] = true;
} );

/**
 *	Submit one form the way a browser does, through the whole route callback
 *	chain - so the feature's guards run where they really run: ahead of the
 *	engine, on the kernel module's own callback
 */
function submitForm( array &$appData, array $post ): array {
	$_POST = array_merge( [ 'location' => '' ], $post );
	$request = [ '/nino/http/response' => [ 'statusCode' => 200 ] ];
	\Nino\Callbacks::doCallbacks( $appData, '/nino/http/response/POST://.form', $request );
	return $request;
}

function callFormsAdmin( array &$appData, string $action, array $data = [] ): array {
	$request = [ '/nino/http/response' => [ 'statusCode' => 200 ] ];
	$_POST = [ 'action' => $action, 'data' => json_encode( $data ) ];
	\Nino\Admin\Admin::handlePost( $appData, $request );
	return [ $request['/nino/http/response']['statusCode'], $request['/nino/http/response']['body'] ?? null ];
}

/** What the forms of config.php are right now, as the engine reads them */
function definedForms( array &$appData ): array {
	return array_column( \Nino\Form::forms( $appData ), 'key' );
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
check( 'it is filed under communication', ( include $dir. '/feature.php' )['category'] === 'communication' );
// The definitions are in config.php, where the kernel reads them and a backup
// already carries them; the submissions are the kernel's own files. What is
// left is a spam counter that rebuilds itself
check( 'it owns nothing under data/ - there is nothing of its own to back up', is_array( $manifest ) === true && $manifest['data'] === [] );
check( 'its settings are the three guards and nothing else', is_array( $manifest ) === true
	&& array_keys( $manifest['settings'] ) === [ 'rateLimit', 'minSeconds', 'blocklist' ] );
check( 'it needs no other feature and no php extension', is_array( $manifest ) === true && $manifest['requires'] === [] && ( $manifest['php']['ext'] ?? [] ) === [] );
check( 'and it brings no install unit: the mail templates a form points at are the kernel\'s own', is_dir( $dir. '/install' ) === false );

\Nino\AppData::writeContentData( $appData, [ '/nino/modules', '/nino/locales/available', '/nino/locales/native' ] );
ninoWarnings();

check( 'the Features registry lists it, inactive', ( \Nino\Features::get( $appData, 'forms' )['active'] ?? null ) === false );
check( 'activation succeeds', \Nino\Features::activate( $appData, 'forms' ) === true );
check( 'the class is listed in /nino/modules and the version recorded', in_array( '\\Nino\\Modules\\Forms', \Nino\Filesystem::getFileContent( $appData, '/config.php', [] )['/nino/modules'], true ) === true
	&& \Nino\Features::get( $appData, 'forms' )['installed'] === $manifest['version'] );

\Nino\Modules\Forms::init( $appData );

check( 'init adds the [form] shortcode', isset( $appData['./nino/html/shortcodes']['form'] ) === true );
// The seam: the kernel module's own route callback, at priority 1 - ahead of
// the engine, which sits at the default. No route of its own, no callback
// name of its own
check( 'and two listeners on the kernel\'s own route callback - a guard ahead of the engine, a counter behind it',
	isset( $appData['./nino/callbacks'][ \Nino\Modules\Forms::ROUTE ][1] ) === true
	&& isset( $appData['./nino/callbacks'][ \Nino\Modules\Forms::ROUTE ][8] ) === true );
check( 'it registers no route of its own - the endpoint stays the kernel module\'s', \Nino\Modules\Forms::endpointActive( $appData ) === true );

echo "\n";


// --- The shortcode -----------------------------------------------------------

echo "Modules\\Forms - [form], over the definitions the kernel reads\n";

check( 'with nothing defined, [form] draws the contact form Nino falls back to', ( static function( array &$appData ): bool {
	$html = \Nino\Html::renderHtml( $appData, '[form]' );
	return str_contains( $html, 'name="name"' ) && str_contains( $html, 'name="email"' ) && str_contains( $html, 'name="message"' );
} )( $appData ) );

$html = \Nino\Html::renderHtml( $appData, '[form]' );
check( 'it posts to the kernel\'s endpoint and says which form it is', str_contains( $html, 'action="/.form"' ) === true
	&& str_contains( $html, '<input type="hidden" name="form" value="contact">' ) === true );
check( 'it carries the csrf token, rendered rather than left as a shortcode', str_contains( $html, 'name="_csrf"' ) === true && str_contains( $html, '[csrf]' ) === false );
check( 'it carries the honeypot the engine checks and the stamp the guard checks', str_contains( $html, 'name="location"' ) === true
	&& preg_match( '/name="_t" value="\d{10}"/', $html ) === 1 );
check( 'a label written as a fill is resolved, not printed', str_contains( $html, '>Message *<' ) === true && str_contains( $html, '[[/form/label/' ) === false );
check( 'the classes the shared .nino-form script drives are all there', str_contains( $html, 'class="nino-form"' ) === true
	&& str_contains( $html, 'class="nino-form-message"' ) === true && str_contains( $html, 'class="nino-form-trap"' ) === true
	&& str_contains( $html, 'nino-form-submit' ) === true );

check( 'a key no form has draws nothing at all - an empty page beats a form that posts nowhere', \Nino\Html::renderHtml( $appData, '[form key="nowhere"]' ) === '' );

echo "\n";


// --- The builder -------------------------------------------------------------

echo "Forms\\Admin - the builder writes the key the kernel reads\n";

\Nino\Auth::insertUser( $appData, 'dev@example.com', 'correct horse battery staple', [ \Nino\Modules\Forms\Admin::MANAGE_PERM ] );
\Nino\Auth::loginUser( $appData, 'dev@example.com', 'correct horse battery staple' );
\Nino\Admin\Admin::init( $appData );

[ $status, $body ] = callFormsAdmin( $appData, 'forms/list' );
check( 'forms/list answers the forms, the types a field may be and the names it may not take', $status === 200
	&& array_column( $body['forms'], 'key' ) === [ 'contact' ] && $body['types'] === \Nino\Form::TYPES && $body['reserved'] === \Nino\Form::RESERVED );
check( '...and says that nothing is defined yet, so the list is showing the fallback', $body['default'] === true );
check( '...and that the endpoint every form posts to is switched on', $body['endpoint'] === true );
check( '...with the two things about the submissions a project decides', $body['retention'] === \Nino\Form::RETENTION_MONTHS && $body['store'] === true );

$quote = [
	'key' => 'quote', 'name' => 'Quote', 'to' => 'sales@example.com', 'subject' => '', 'confirm' => false,
	'ownerTemplate' => '/templates/mail-owner', 'userTemplate' => '/templates/mail-user',
	'fields' => [
		[ 'name' => 'email',  'label' => 'Mail',   'type' => 'email',  'required' => true, 'options' => [] ],
		[ 'name' => 'budget', 'label' => 'Budget', 'type' => 'number', 'required' => false, 'options' => [] ],
	],
];

[ $status ] = callFormsAdmin( $appData, 'forms/save', [ 'form' => $quote, 'key' => '' ] );
check( 'a saved form lands in /nino/form/forms in config.php - the key the engine reads', $status === 200
	&& array_column( \Nino\Filesystem::getFileContent( $appData, '/config.php', [] )['/nino/form/forms'], 'key' ) === [ 'contact', 'quote' ] );
check( '...and the engine has it on the very next read, without the panel telling it anything', definedForms( $appData ) === [ 'contact', 'quote' ] );

[ $status ] = callFormsAdmin( $appData, 'forms/save', [ 'form' => [ 'key' => 'Not A Key', 'fields' => [] ], 'key' => '' ] );
check( 'a definition the engine would refuse is refused here, by the engine\'s own normalize()', $status === 400 );

[ $status ] = callFormsAdmin( $appData, 'forms/save', [ 'form' => $quote, 'key' => '' ] );
check( 'creating a second form under a key that is taken is refused rather than silently replacing it', $status === 400 && count( definedForms( $appData ) ) === 2 );

[ $status ] = callFormsAdmin( $appData, 'forms/save', [ 'form' => [ 'key' => 'offer' ] + $quote, 'key' => 'quote' ] );
check( 'a rename stays one form rather than becoming two', $status === 200 && definedForms( $appData ) === [ 'contact', 'offer' ] );

[ $status ] = callFormsAdmin( $appData, 'forms/delete', [ 'key' => 'offer' ] );
check( 'a form is deleted by its key', $status === 200 && definedForms( $appData ) === [ 'contact' ] );

[ $status ] = callFormsAdmin( $appData, 'forms/delete', [ 'key' => 'contact' ] );
check( 'the last one is not - a project with none falls back to the built-in form, which is a decision, not a delete', $status === 400 && definedForms( $appData ) === [ 'contact' ] );

[ $status, $body ] = callFormsAdmin( $appData, 'forms/settings', [ 'retention' => 12, 'store' => false ] );
check( 'the two submission settings are written as the kernel\'s own config keys', $status === 200
	&& \Nino\Form::retention( $appData ) === 12 && \Nino\Form::stores( $appData ) === false
	&& \Nino\Filesystem::getFileContent( $appData, '/config.php', [] )['/nino/form/retention'] === 12 );

[ $status ] = callFormsAdmin( $appData, 'forms/settings', [ 'retention' => 0, 'store' => true ] );
check( 'a retention outside the window is refused, and nothing is written', $status === 400 && \Nino\Form::retention( $appData ) === 12 );

callFormsAdmin( $appData, 'forms/settings', [ 'retention' => 3, 'store' => true ] );

$appData['./nino/auth/current'] = null;
[ $status ] = callFormsAdmin( $appData, 'forms/save', [ 'form' => $quote, 'key' => '' ] );
check( 'every action needs the panel\'s permission', $status === 401 );
\Nino\Auth::loginUser( $appData, 'dev@example.com', 'correct horse battery staple' );

echo "\n";


// --- The guards --------------------------------------------------------------

echo "Modules\\Forms - refusing a submission ahead of the engine\n";

callFormsAdmin( $appData, 'forms/save', [ 'form' => $quote, 'key' => '' ] );

$valid = [ 'form' => 'quote', 'email' => 'jo@example.com', 'budget' => '5000' ];

// Nothing configured: the feature is on and a good submission goes through
// exactly as it did without it
$sent = [];
$before = count( \Nino\Form::entries( $appData ) );
$request = submitForm( $appData, $valid );
check( 'with no guard configured, a submission goes through the engine untouched - mailed and recorded', $request['/nino/http/response']['statusCode'] === 200
	&& count( $sent ) === 1 && count( \Nino\Form::entries( $appData ) ) === $before + 1 );

\Nino\Features::saveSettings( $appData, 'forms', [ 'minSeconds' => 30, 'rateLimit' => 0, 'blocklist' => [] ] );

$sent = [];
$before = count( \Nino\Form::entries( $appData ) );
$request = submitForm( $appData, $valid + [ '_t' => (string) time() ] );
check( 'a submission that comes back faster than a person could type is refused, and nothing is sent or recorded',
	$request['/nino/http/response']['statusCode'] === 418 && $sent === [] && count( \Nino\Form::entries( $appData ) ) === $before );

$request = submitForm( $appData, $valid + [ '_t' => (string) ( time() - 120 ) ] );
check( '...and one that took its time is not', $request['/nino/http/response']['statusCode'] === 200 );

$request = submitForm( $appData, $valid );
check( 'a form without the stamp is not checked at all - a hand-written one carries none', $request['/nino/http/response']['statusCode'] === 200 );

\Nino\Features::saveSettings( $appData, 'forms', [ 'minSeconds' => 0, 'rateLimit' => 0, 'blocklist' => [ 'casino' ] ] );

$sent = [];
$request = submitForm( $appData, [ 'form' => 'quote', 'email' => 'jo@example.com', 'budget' => 'Casino-Bonus' ] );
check( 'a blocked word in any value is refused, case and word boundaries ignored', $request['/nino/http/response']['statusCode'] === 418 && $sent === [] );

$request = submitForm( $appData, $valid );
check( '...and a submission carrying none is not', $request['/nino/http/response']['statusCode'] === 200 );

// The rate limit: checked before the engine, counted after it - so only a
// submission that was actually accepted costs a slot
\Nino\Features::saveSettings( $appData, 'forms', [ 'minSeconds' => 0, 'rateLimit' => 2, 'blocklist' => [] ] );
\Nino\Filesystem::putFileContent( $appData, \Nino\Modules\Forms::RATE, [] );

check( 'the first two submissions of the hour are accepted', submitForm( $appData, $valid )['/nino/http/response']['statusCode'] === 200
	&& submitForm( $appData, $valid )['/nino/http/response']['statusCode'] === 200 );

$sent = [];
$request = submitForm( $appData, $valid );
check( 'the third is turned away with a 429 - the one refusal a person can do something about', $request['/nino/http/response']['statusCode'] === 429 && $sent === [] );

check( 'the counter is keyed by a hash of the address, not by the address', ( static function( array &$appData ): bool {
	$state = \Nino\Filesystem::getFileContent( $appData, \Nino\Modules\Forms::RATE, [] );
	return count( $state ) === 1 && preg_match( '/^[a-f0-9]{64}$/', (string) array_key_first( $state ) ) === 1;
} )( $appData ) );

// A refused submission must not spend a slot: reset, then send two the engine
// rejects and check the allowance is still whole
\Nino\Filesystem::putFileContent( $appData, \Nino\Modules\Forms::RATE, [] );
submitForm( $appData, [ 'form' => 'quote', 'email' => 'not-an-email' ] );
submitForm( $appData, [ 'form' => 'quote', 'email' => 'still-not-one' ] );
check( 'a submission the engine refused costs nothing - a visitor who mistypes their address has not submitted', submitForm( $appData, $valid )['/nino/http/response']['statusCode'] === 200
	&& submitForm( $appData, $valid )['/nino/http/response']['statusCode'] === 200 );

\Nino\Features::saveSettings( $appData, 'forms', [ 'minSeconds' => 0, 'rateLimit' => 0, 'blocklist' => [] ] );

// The other seam: a guard has to respect a refusal that came before it
$blocked = [ '/nino/http/response' => [ 'statusCode' => 403 ], './nino/csrf/blocked' => true ];
$_POST = $valid + [ 'location' => '' ];
\Nino\Modules\Forms::callbackGuard( $appData, $blocked );
check( 'a request the csrf guard already refused is left alone', $blocked['/nino/http/response']['statusCode'] === 403 );

echo "\n";


// --- Switching it off --------------------------------------------------------

echo "The feature - what a project keeps when it is switched off again\n";

check( 'deactivation succeeds', \Nino\Features::deactivate( $appData, 'forms' ) === true );
check( 'the class is gone from /nino/modules', in_array( '\\Nino\\Modules\\Forms', \Nino\Filesystem::getFileContent( $appData, '/config.php', [] )['/nino/modules'], true ) === false );
// The whole point of the design: the forms are the kernel's own configuration,
// not the feature's data. What is lost is the builder, the shortcode and the
// guards - never a form, and never a submission
check( 'the forms stay in config.php and the engine goes on reading them', definedForms( $appData ) === [ 'contact', 'quote' ] );
check( '...and a submission to one of them still goes through', ( static function( array &$appData ): bool {
	$fresh = $appData;
	unset( $fresh['./nino/callbacks'][ \Nino\Modules\Forms::ROUTE ][1], $fresh['./nino/callbacks'][ \Nino\Modules\Forms::ROUTE ][8] );
	return submitForm( $fresh, [ 'form' => 'quote', 'email' => 'jo@example.com', 'budget' => '1' ] )['/nino/http/response']['statusCode'] === 200;
} )( $appData ) );
check( 'and the recorded submissions are where they always were - the kernel\'s own files', count( \Nino\Form::entries( $appData ) ) > 0 );

ninoWarnings();
ninoDone( $appData );
