<?php
declare(strict_types=1);

/**
 *	Nino
 *	forms-smoke.php		The test of the feature Forms: structure and behaviour, one pin each. The feature replaces nothing -
 *										\Nino\Form is the engine and \Nino\Modules\Form owns the route - so what is pinned is what it adds: the
 *										component form over a definition the kernel reads, the guards on the route callback of the kernel, the
 *										answer a page gives a browser without a script, the panel that writes '/nino/form/forms', and the
 *										boundaries between a visitor's input and a mail, a page and the panel. Where node is on the path the
 *										script of the panel is run over a stand-in as well (forms-js-smoke.js).
 *
 *	Usage: php features/Forms/tests/forms-smoke.php
 *	       NINO_ROOT=../nino php features/Forms/tests/forms-smoke.php
 */
$root = getenv( 'NINO_ROOT' ) ?: dirname( __DIR__, 3 );
defined( 'NINO_FEATURES_DIR' ) === true || define( 'NINO_FEATURES_DIR', dirname( __DIR__, 2 ) );
require $root. '/tests/harness.php';

$dir = dirname( __DIR__ );

$appData = ninoSandbox( 'forms' );
$appData['./nino/filesystem/path'] = $root;
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$appData['/nino/dir'] = '';
$appData['/nino/modules'] = [ '\\Nino\\Modules\\Basis', '\\Nino\\Modules\\Form', '\\Nino\\Modules\\Forms' ];

\Nino\Html::init( $appData );
\Nino\Features::registerComponents( $appData );
\Nino\Modules\Template::init( $appData );
\Nino\Modules\Csrf::init( $appData );
\Nino\Modules::callModules( $appData, 'init' );
\Nino\Features::registerParts( $appData );
\Nino\Admin\Admin::init( $appData );

\Nino\Html::addFills( $appData, [
	'[[/project/mail/address/owner]]'		=> 'owner@example.com',
	'[[/module/form/subject/owner]]'		=> 'New inquiry',
	'[[/module/form/subject/user]]'			=> 'Thanks',
	'[[/template/common/form/name]]'		=> 'Name',
	'[[/template/common/form/email]]'		=> 'E-Mail',
	'[[/template/common/form/reason]]'	=> 'Subject',
	'[[/template/common/form/message]]'	=> 'Message',
	'[[/template/common/form/submit]]'	=> 'Send',
	'[[/template/common/form/required]]'	=> 'required',
	'[[/module/form/info/success]]'			=> 'Thank you',
	'[[/module/form/info/error]]'				=> 'Not sent',
	'[[/module/form/info/invalid]]'			=> 'Check your entries',
	'[[/module/form/info/email]]'				=> 'Check your address',
], '*' );

// Every mail lands here instead of going out: the kernel's own transport callback
$sent = [];
\Nino\Callbacks::registerCallback( $appData, \Nino\Mail::TRANSPORT, static function( array &$appData, array &$mail ) use ( &$sent ): void {
	$sent[] = $mail;
	$mail['sent'] = true;
} );

foreach( [ 'mail-owner', 'mail-user', 'mail-header', 'mail-footer', 'mail-quote' ] as $mail )
	\Nino\Filesystem::putFileContent( $appData, '/templates/'. $mail. '.tpl', '<p>'. $mail. '</p>[[fields]]' );

// A page is drawn once in a request: what the register and the component keep for a request (the options of the choice, the forms drawn) is dropped between two drawings
$draw = static function( string $source ) use ( &$appData ): string { unset( $appData[ \Nino\Components::OPTIONS ], $appData['./nino/forms/drawn'] ); return trim( \Nino\Html::renderSource( $appData, $source, \Nino\Html::context( $appData ) ) ); };

/**
 *	One post through everything a request passes: the csrf guard of the kernel (a token that is not the session's, unless the
 *	test says it is not wanted), then the callbacks of the route of the endpoint - the guards of the feature at priority 1, the
 *	kernel module at 5, the counter at 8. A post of the script carries the marker, one of a browser without it a Referer
 */
function submit( array &$appData, array $post, bool $script = true, bool $token = false, string $referer = 'http://localhost/contact?x=1' ): array {

	\Nino\Feature::forget( $appData, 'ratelimit', '127.0.0.1' );

	$_POST = array_merge( [ 'location' => '' ], $post );
	$header = $script === true ? [ \Nino\Form::SCRIPT_HEADER => '1' ] : [ 'Referer' => $referer, 'Host' => 'localhost' ];
	$request = [ '/nino/http/request' => [ 'method' => 'POST', 'header' => $header ], '/nino/http/response' => [ 'statusCode' => 200, 'header' => [], 'body' => '' ] ];

	if( $token === false ) {
		$_POST['_csrf'] = 'wrong-token';
		\Nino\Csrf::callbackResponse( $appData, $request );
	}

	\Nino\Callbacks::doCallbacks( $appData, '/nino/http/response/POST://.form', $request );

	return $request;
}

function status( array $request ): int { return (int) $request['/nino/http/response']['statusCode']; }
function where( array $request ): string { return (string) ( $request['/nino/http/response']['header']['Location'] ?? '' ); }

// A post without the csrf guard in front of it: what the route callbacks do with a submission that is the session's own
function post( array &$appData, array $post, bool $script = true, string $referer = 'http://localhost/contact?x=1' ): array {
	$_POST = array_merge( [ 'location' => '' ], $post );
	\Nino\Feature::forget( $appData, 'ratelimit', '127.0.0.1' );
	$header = $script === true ? [ \Nino\Form::SCRIPT_HEADER => '1' ] : [ 'Referer' => $referer, 'Host' => 'localhost' ];
	$request = [ '/nino/http/request' => [ 'method' => 'POST', 'header' => $header ], '/nino/http/response' => [ 'statusCode' => 200, 'header' => [], 'body' => '' ] ];
	\Nino\Callbacks::doCallbacks( $appData, '/nino/http/response/POST://.form', $request );
	return $request;
}

echo "The feature - what the folders say of it\n";

$manifest = \Nino\Features::manifest( $dir );
check( 'the manifest validates and is written for this kernel', is_array( $manifest ) === true && $manifest['key'] === 'forms' && $manifest['version'] === '2.0.0' && \Nino\Features::satisfies( (string) $manifest['nino'] ) === true );
check( 'the folders are read without a warning', ninoWarnings() === [] && \Nino\Features::componentProblems( $appData, 'forms' ) === [] );

$schema = \Nino\Components::components( $appData )['form'] ?? [];
$group = \Nino\Components::groups( $appData )['form'] ?? [];
check( 'the component is registered in its group, with a label in both languages, an icon of the table and a standard part',
	( $schema['group'] ?? '' ) === 'form' && isset( $schema['label']['de_DE'], $schema['label']['en_US'] ) === true && ( $schema['icon'] ?? '' ) === 'text-cursor-input' && in_array( $schema['icon'], \Nino\Components::ICONS, true ) === true
	&& ( $group['part'] ?? false ) === true );
check( 'every attribute has a label in both languages, and the component has the two variants', array_filter( $schema['attributes'] ?? [], static fn( array $a ): bool => isset( $a['label']['de_DE'], $a['label']['en_US'] ) === false ) === []
	&& array_keys( \Nino\Components::variants( $appData, 'form' )['variants'] ?? [] ) === [ 'regular', 'filled', 'line' ] );
check( 'the group has the three knobs, and the standard part keeps the rule of mixing', array_keys( $group['knobs'] ?? [] ) === [ 'form.gap', 'form.radius', 'form.size' ]
	&& \Nino\Components::partProblems( (string) file_get_contents( $dir. '/groups/form/part/part.css' ), [ 'form' ] ) === [] );
check( 'a tool offers it in the flow of a column', in_array( 'form', \Nino\Components::allowed( $appData, 'section-col' ), true ) === true );
check( 'the key is a choice among the forms there are - the first form for the empty word', ( $schema['attributes']['key']['type'] ?? '' ) === 'choice'
	&& array_keys( \Nino\Modules\Forms::options( $appData, 'key' ) ) === [ '', 'contact' ] );

$blocks = \Nino\Blocks::all( $appData );
check( 'both blocks are listed with the feature as their origin, without a missing feature and without a refusal',
	isset( $blocks['contact-form'], $blocks['form-plain'] ) === true && in_array( 'forms', $blocks['contact-form']['features'] ?? [], true ) === true
	&& ( $blocks['contact-form']['missing'] ?? [] ) === [] && ( $blocks['form-plain']['missing'] ?? [] ) === [] && \Nino\Blocks::rejected( $appData ) === [] );
check( 'a block needs no form of its own: with none defined the form it draws is the contact form the kernel falls back to',
	\Nino\Form::forms( $appData )[0]['key'] === 'contact' && str_contains( $draw( \Nino\Blocks::get( $appData, 'contact-form' )['source'] ?? '' ), 'name="form" value="contact"' ) === true );

$demo = $group['demo'] ?? '';
check( 'the demo of the group is read without a note and draws the component, in the two variants and without one', $demo !== '' && substr_count( $draw( $demo ), 'class="form-submit"' ) === 3 );
check( 'the part is in the style bundle, in the layer of the components', str_contains( \Nino\Modules\Assets::stylesheet( $appData, '/.cache/style.css' ), '.form-control' ) === true );

$off = ninoSandbox( 'forms-off' );
\Nino\Html::init( $off );
\Nino\Features::registerComponents( $off );
check( 'where the feature is off the call draws nothing', \Nino\Html::renderSource( $off, '[form]', \Nino\Html::context( $off ) ) === '' );
ninoWarnings();

// Nothing of the old vocabulary is left in the folder
$old = [];
foreach( new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $dir, \FilesystemIterator::SKIP_DOTS ) ) as $file )
	if( str_contains( (string) $file, '/tests/' ) === false && preg_match( '/Feature::shortcode|addShortcode|Modules\\\\Components|nino-forms|nino-form-|nino-btn|--color-|--space-[0-9]|--radius-|--fontsize-|Nino\.ui\./', (string) file_get_contents( (string) $file ) ) === 1 )
		$old[] = (string) $file;
check( 'no file of the feature names the vocabulary of Nino 1', $old === [] );

echo "\n";
echo "The component - the form as a page draws it\n";

\Nino\Form::forms( $appData );
$quote = [
	'key' => 'quote', 'name' => 'Quote', 'to' => 'sales@example.com', 'subject' => '', 'confirm' => false,
	'ownerTemplate' => '/templates/mail-owner', 'userTemplate' => '/templates/mail-user',
	'fields' => [
		[ 'name' => 'email', 'label' => 'Mail', 'type' => 'email', 'required' => true, 'options' => [] ],
		[ 'name' => 'budget', 'label' => 'Budget', 'type' => 'number', 'required' => false, 'options' => [] ],
	],
];
$appData['/nino/form/forms'] = [ \Nino\Form::normalize( \Nino\Form::DEFAULT_FORM ), \Nino\Form::normalize( $quote ) ];

$html = $draw( '[form]' );
check( 'with a key it draws that form, without one the first', str_contains( $draw( '[form key="quote"]' ), 'name="form" value="quote"' ) === true && str_contains( $html, 'name="form" value="contact"' ) === true );
check( 'a key that is no form is the first form, and a key that spells markup is no key', str_contains( $draw( '[form key="gone"]' ), 'value="contact"' ) === true
	&& str_contains( $draw( '[form key="&quot;><script>"]' ), '<script' ) === false );

// What the script of the Form module needs and would not work without: the class, the message as the first paragraph, the submit as the first button, the words
preg_match_all( '/<p[ >]/', $html, $paragraphs, PREG_OFFSET_CAPTURE );
preg_match_all( '/<button[ >]/', $html, $buttons, PREG_OFFSET_CAPTURE );
check( 'the contract of the kernel\'s script: nino-form, the message is the first paragraph and the submit the first button, data-form-texts is a published prefix',
	str_contains( $html, ' nino-form"' ) === true && preg_match( '/<p class="form-message"/', $html, $first, PREG_OFFSET_CAPTURE ) === 1 && $first[0][1] === $paragraphs[0][0][1]
	&& count( $buttons[0] ) === 1 && str_contains( $html, 'class="form-submit"' ) === true && str_contains( $html, 'data-form-texts="'. \Nino\Modules\Forms::INFO. '"' ) === true
	&& \Nino\Modules\Forms::INFO === '/module/form/info/' && str_contains( $html, 'novalidate' ) === false );
check( 'the hidden fields the engine reads: the token, the form, the id of the form on its page, the moment, and the honeypot the engine checks',
	str_contains( $html, 'name="_csrf"' ) === true && str_contains( $html, '[csrf]' ) === false && str_contains( $html, 'name="'. \Nino\Form::ANCHOR_FIELD. '" value="form-contact"' ) === true
	&& preg_match( '/name="_t" value="\d{10}"/', $html ) === 1 && in_array( 'location', \Nino\Form::RESERVED, true ) === true && str_contains( $html, 'name="location" value="" tabindex="-1" autocomplete="off" aria-hidden="true"' ) === true );
preg_match_all( '/class="([^"]*)"/', $html, $classes );
$all = array_unique( explode( ' ', implode( ' ', $classes[1] ) ) );
check( 'no class of the old world: nino-form is the one class of the kernel the form carries', array_values( array_filter( $all, static fn( string $c ): bool => str_starts_with( $c, 'nino-' ) === true ) ) === [ 'nino-form' ] );
check( 'it posts to the endpoint of the kernel, below the directory of the project', str_contains( $html, 'action="/.form" method="post"' ) === true );
check( 'the same form twice on a page has a second id, which its fields follow',
	str_contains( $draw( '[form][form]' ), 'id="form-contact-2"' ) === true && str_contains( $draw( '[form][form]' ), 'id="form-contact-2-email"' ) === true );
$appData['/nino/form/forms'][] = \Nino\Form::normalize( [ 'key' => 'a'. str_repeat( 'b', 90 ) ] + $quote );
preg_match( '/name="id" value="([^"]+)"/', $draw( '[form key="a'. str_repeat( 'b', 90 ). '"]' ), $long );
check( 'a key too long for an anchor draws an id of the shape the kernel takes one in, 64 characters at most', strlen( $long[1] ?? '' ) <= 64 && preg_match( '/^[A-Za-z][A-Za-z0-9_-]{0,63}$/D', $long[1] ?? '' ) === 1 );
array_pop( $appData['/nino/form/forms'] );

// Every kind of field from the template of its kind
$appData['/nino/form/forms'][] = \Nino\Form::normalize( [ 'key' => 'kinds', 'fields' => [
	[ 'name' => 'who', 'label' => 'Who', 'type' => 'text', 'required' => true ],
	[ 'name' => 'about', 'label' => 'About', 'type' => 'textarea' ],
	[ 'name' => 'plan', 'label' => 'Plan', 'type' => 'select', 'options' => [ 'S', 'L' ] ],
	[ 'name' => 'size', 'label' => 'Size', 'type' => 'radio', 'required' => true, 'options' => [ 'S', 'L' ] ],
	[ 'name' => 'agree', 'label' => 'I agree', 'type' => 'checkbox', 'required' => true ],
	[ 'name' => 'day', 'label' => 'Day', 'type' => 'date' ],
] ] + $quote );
$kinds = $draw( '[form key="kinds"]' );
check( 'every kind of field is drawn from the template of its kind: a label for a control, a fieldset with a legend for a radio group, one label round a checkbox, a date as the input of its type',
	str_contains( $kinds, '<label class="form-label" for="form-kinds-who">Who' ) === true && str_contains( $kinds, '<textarea class="form-control form-textarea" id="form-kinds-about"' ) === true
	&& str_contains( $kinds, '<select class="form-control" id="form-kinds-plan"' ) === true && str_contains( $kinds, '<fieldset class="form-field form-group"><legend class="form-label">Size' ) === true
	&& substr_count( $kinds, 'type="radio"' ) === 2 && preg_match( '#<label class="form-check"><input class="form-box" type="checkbox"[^>]*required> <span>I agree#', $kinds ) === 1
	&& str_contains( $kinds, 'type="date"' ) === true && str_contains( $kinds, 'required>' ) === true );
check( 'no template of a field carries a paragraph or a button, which the script of the kernel would take for the message and the submit button',
	array_filter( glob( $dir. '/templates/*.tpl' ), static fn( string $f ): bool => preg_match( '/<p[ >]|<button/', (string) file_get_contents( $f ) ) === 1 ) === [] );

// A label, an option, a name that spell a call, a fill or markup are text
$appData['/nino/form/forms'][] = \Nino\Form::normalize( [ 'key' => 'evil', 'fields' => [
	[ 'name' => 'x', 'label' => '"><script>alert(1)</script> [template /templates/evil] [csrf] <b>b</b>', 'type' => 'select', 'options' => [ '"><img src=x onerror=alert(1)>', '[[/project/mail/address/owner]]', "a\0b" ] ],
] ] + $quote );
$evil = $draw( '[form key="evil"]' );
check( 'a label and an option that spell markup, a call or a fill are text; a fill of the site in them is put in, once',
	str_contains( $evil, '<script' ) === false && str_contains( $evil, '<img' ) === false && str_contains( $evil, '<b>' ) === false && str_contains( $evil, '&lt;script&gt;' ) === true
	&& substr_count( $evil, 'name="_csrf"' ) === 1 && str_contains( $evil, '<option value="[[/project/mail/address/owner]]">owner@example.com</option>' ) === true && str_contains( $evil, "\0" ) === false );
check( 'a value is escaped once', str_contains( $draw( '[form key="evil"]' ), '&amp;amp;' ) === false );

echo "\n";
echo "Without a script - the page is the answer\n";

$onPage = static function( string $query ) use ( &$appData, $draw ): string {
	$appData['./nino/http/requests'] = [ [ '/nino/http/request' => [ 'query' => $query === '' ? [] : [ 'form' => $query ], 'uri' => '/contact' ] ] ];
	$html = $draw( '[form][form key="quote"]' );
	$appData['./nino/http/requests'] = [];
	return $html;
};

$said = $onPage( 'success' );
check( 'a page that was sent back with ?form=success has the words of the script in the message of every form, marked with the outcome',
	substr_count( $said, '<p class="form-message" data-form-outcome="success">Thank you</p>' ) === 2 );
check( '...each word of the four outcomes is the one of the key the script says it by', str_contains( $onPage( 'error' ), 'data-form-outcome="error">Not sent<' ) === true && str_contains( $onPage( 'invalid' ), '>Check your entries<' ) === true && str_contains( $onPage( 'email' ), '>Check your address<' ) === true );
check( 'an address that names no outcome, or a word of its own, draws no message and nothing of it', str_contains( $onPage( '' ), 'data-form-outcome' ) === false && str_contains( $onPage( '<script>' ), 'data-form-outcome' ) === false
	&& str_contains( $onPage( 'x" onclick="y' ), 'onclick' ) === false );
$appData['./nino/http/requests'] = [ [ '/nino/http/request' => [ 'query' => [ 'form' => [ 'success' ] ] ] ] ];
check( 'an outcome that is an array in the query is none', str_contains( $draw( '[form]' ), 'data-form-outcome' ) === false );
$appData['./nino/http/requests'] = [];
$part = (string) file_get_contents( $dir. '/groups/form/part/part.css' );
check( 'which form shows the message is the part\'s: the one the fragment names (:target), and a paragraph the script has written into (tabindex) is its own',
	preg_match( '/\.form:target\s*>\s*\.form-message\[data-form-outcome\]:not\(\[tabindex\]\)\s*\{\s*display:\s*block/', $part ) === 1 && preg_match( '/\.form-message\[data-form-outcome\]:not\(\[tabindex\]\)\s*\{\s*display:\s*none/', $part ) === 1 );

echo "\n";
echo "The boundaries - the guards, with a script and without\n";

$valid = [ 'form' => 'quote', 'email' => 'jo@example.com', 'budget' => '5000', 'id' => 'form-quote' ];
$before = count( \Nino\Form::entries( $appData ) );
$sent = [];
$request = post( $appData, $valid, false );
check( 'a submission of a browser without a script is answered with the redirect of the kernel to the page, ?form=success and the id of the form, and is mailed and recorded',
	status( $request ) === 303 && where( $request ) === '/contact?x=1&form=success#form-quote' && count( $sent ) === 1 && count( \Nino\Form::entries( $appData ) ) === $before + 1 );
$request = post( $appData, $valid, true );
check( 'the script of the page gets a 200 and no redirect', status( $request ) === 200 && where( $request ) === '' );

$source = (string) file_get_contents( $dir. '/Forms.php' ). (string) file_get_contents( $dir. '/Admin/Admin.php' );
check( 'the redirect is the kernel\'s: the feature writes no Location, no redirect and no header of its own', preg_match( '/\[\'Location\'\]\s*=[^=>]|redirect\(|\bheader\(/', $source ) === 0 );

$bounce = static function( array $request ): bool { return status( $request ) === 303 && str_contains( where( $request ), 'form=error' ); };
$sent = [];
$before = count( \Nino\Form::entries( $appData ) );

$request = post( $appData, [ 'location' => 'http://spam.example' ] + $valid, true );
$scripted = status( $request );
$request = post( $appData, [ 'location' => 'http://spam.example' ] + $valid, false );
check( 'the honeypot refuses with a script (418) and without (back to the page with ?form=error), and nothing is sent or recorded', $scripted === 418 && $bounce( $request ) === true && $sent === [] && count( \Nino\Form::entries( $appData ) ) === $before );

\Nino\Features::saveSettings( $appData, 'forms', [ 'minSeconds' => 30, 'rateLimit' => 0, 'blocklist' => [] ] );
$fast = [ '_t' => (string) time() ] + $valid;
check( 'the minimum time refuses a submission that comes back too fast, with a script (418) and without (?form=error), and nothing is sent or recorded',
	status( post( $appData, $fast, true ) ) === 418 && $bounce( post( $appData, $fast, false ) ) === true && $sent === [] && count( \Nino\Form::entries( $appData ) ) === $before );
check( '...one that took its time, or carries no stamp, is not refused', status( post( $appData, [ '_t' => (string) ( time() - 120 ) ] + $valid, true ) ) === 200 && status( post( $appData, $valid, true ) ) === 200 );

\Nino\Features::saveSettings( $appData, 'forms', [ 'minSeconds' => 0, 'rateLimit' => 0, 'blocklist' => [ 'casino' ] ] );
$sent = [];
$bad = [ 'budget' => '5 Casino-Bonus' ] + $valid;
check( 'a word of the blocklist in any field refuses, case ignored, with a script (418) and without (?form=error)', status( post( $appData, $bad, true ) ) === 418 && $bounce( post( $appData, $bad, false ) ) === true && $sent === [] );

// The rate limit counts what the engine accepted: a 200 for the script, the redirect that says success for a browser without it
\Nino\Features::saveSettings( $appData, 'forms', [ 'minSeconds' => 0, 'rateLimit' => 2, 'blocklist' => [] ] );
\Nino\Filesystem::putFileContent( $appData, '/data/forms-rate.php', [] );
$sent = [];
$go = static fn( bool $script ): array => post( $appData, $valid, $script );
$wasCounter = static fn(): int => (int) ( \Nino\Filesystem::getFileContent( $appData, '/data/forms-rate.php', [] )[ hash( 'sha256', '127.0.0.1' ) ]['tries'] ?? 0 );
$one = status( $go( false ) );
check( 'a submission of a browser without a script is counted: the limit holds for it as for the script', $one === 303 && $wasCounter() === 1 );
\Nino\Feature::forget( $appData, 'ratelimit', '127.0.0.1' );
$two = status( $go( true ) );
$third = $go( true );
check( 'the second is accepted and the third is turned away with a 429, which costs nothing', $two === 200 && status( $third ) === 429 && $wasCounter() === 2 && count( $sent ) === 2 );
$fourth = $go( false );
check( '...and without a script the same refusal comes back as the redirect with ?form=error, and counts nothing', $bounce( $fourth ) === true && $wasCounter() === 2 && count( $sent ) === 2 );
\Nino\Filesystem::putFileContent( $appData, '/data/forms-rate.php', [] );
post( $appData, [ 'email' => 'not-an-email' ] + $valid, true );
post( $appData, [ 'email' => 'not-an-email' ] + $valid, false );
check( 'a submission the engine refused counts nothing', $wasCounter() === 0 );
\Nino\Features::saveSettings( $appData, 'forms', [ 'minSeconds' => 0, 'rateLimit' => 0, 'blocklist' => [] ] );

$before = count( \Nino\Form::entries( $appData ) );
$sent = [];
$refused = [];
foreach( [ true, false ] as $script ) {
	$request = submit( $appData, $valid, $script, false );
	$refused[] = status( $request );
}
check( 'a post without the token of the session is a 403, for the script and without it - no redirect, nothing sent, nothing recorded, nothing counted', $refused === [ 403, 403 ] && $sent === []
	&& count( \Nino\Form::entries( $appData ) ) === $before && $wasCounter() === 0 );

// What a visitor types never reaches a header line of the mail, and stays text in the mail
$appData['/nino/form/forms'][] = \Nino\Form::normalize( [ 'key' => 'lines', 'subject' => "Subject\r\nBcc: victim@example.org", 'fields' => [
	[ 'name' => 'name', 'label' => 'Name', 'type' => 'text', 'required' => true ],
	[ 'name' => 'email', 'label' => 'Mail', 'type' => 'email', 'required' => true ],
	[ 'name' => 'message', 'label' => 'Message', 'type' => 'textarea' ],
] ] + $quote );
$sent = [];
$hostile = [ 'form' => 'lines', 'name' => "Evil\r\nBcc: victim@example.org\r\n\r\nbody", 'email' => "jo@example.com\r\nBcc: victim@example.org", 'message' => '<script>alert(1)</script> [[/project/mail/address/owner]]' ];
$request = post( $appData, $hostile, true );
check( 'SAFETY: a line break in the email of a post is no email and sends nothing; in any other field it stays in the body', status( $request ) === 400 && $sent === [] );
$hostile['email'] = 'jo@example.com';
$request = post( $appData, $hostile, true );
$owner = $sent[0] ?? [ 'to' => '', 'subject' => '', 'replyTo' => '', 'headers' => '', 'body' => '' ];
check( 'SAFETY: no header line of the mail holds a CR or an LF - recipient, subject, reply-to - and the headers are the ones the kernel writes, whatever a form carries',
	status( $request ) === 200 && count( $sent ) === 1 && preg_match( '/[\r\n]/', $owner['to']. $owner['subject']. $owner['replyTo'] ) === 0 && substr_count( (string) $owner['headers'], "\r\n" ) <= 3
	&& str_contains( (string) $owner['headers'], 'Bcc' ) === false && $owner['to'] === 'sales@example.com' );
check( 'SAFETY: what was typed is text in the mail - escaped in the body, a fill in it not read', str_contains( $owner['body'], '&lt;script&gt;' ) === true && str_contains( $owner['body'], '<script' ) === false && str_contains( $owner['body'], '[[/project/mail/address/owner]]' ) === true );
check( 'SAFETY: the recipient is the form\'s or the owner\'s, never a field of the post', ( static function() use ( &$appData, &$sent ): bool {
	$sent = [];
	post( $appData, [ 'form' => 'quote', 'email' => 'jo@example.com', 'to' => 'evil@example.com', 'replyTo' => 'evil@example.com', '_to' => 'evil@example.com', 'subject' => 'evil' ], true );
	return count( $sent ) === 1 && $sent[0]['to'] === 'sales@example.com' && str_contains( json_encode( $sent[0] ), 'evil@example.com' ) === false;
} )() );

// Only what the form names is taken, within the limits
$sent = [];
$before = count( \Nino\Form::entries( $appData ) );
post( $appData, [ 'form' => 'quote', 'email' => 'jo@example.com', 'budget' => str_repeat( '9', 5000 ), 'extra' => 'unasked', 'a[]' => 'x' ], true );
$entry = (array) end( ( static fn() => \Nino\Form::entries( $appData ) )() );
check( 'only the fields the form names are taken - an extra field is neither mailed nor recorded - and a value is cut at the length the kernel allows',
	isset( $entry['extra'] ) === false && str_contains( (string) ( $sent[0]['body'] ?? '' ), 'unasked' ) === false && strlen( (string) ( $entry['budget'] ?? '' ) ) <= \Nino\Form::MAX_FIELD_LENGTH );
$request = post( $appData, [ 'form' => 'Quote', 'email' => 'jo@example.com' ], true );
check( 'a key that is no form is refused (404) and is not taken for the first form', status( $request ) === 404 );
$request = post( $appData, [ 'form' => [ 'quote' ], 'email' => [ 'x' ] ], true );
check( 'a field that is an array is no field: a refusal and no error of the engine', in_array( status( $request ), [ 400, 404 ], true ) === true );

echo "\n";
echo "The panel - the forms of config.php\n";

$panel = [ \Nino\Admin\Admin::class, 'handlePost' ];
$appData['/nino/modules'][] = '\\Nino\\Modules\\Forms';
\Nino\AppData::writeContentData( $appData, [ '/nino/modules', '/nino/locales/available', '/nino/locales/native' ] );
ninoUser( $appData, 'dev@example.com', [ '/_admin/forms/manage' ] );
unset( $appData['/nino/form/forms'] );
\Nino\Filesystem::putFileContent( $appData, '/text/de_DE.php', [ '[[/template/common/form/phone]]' => 'Telefon' ] );
\Nino\Filesystem::putFileContent( $appData, '/text/en_US.php', [ '[[/template/common/form/phone]]' => 'Phone' ] );
ninoWarnings();

// A project of Nino 1: the definitions as a 1.x panel wrote them, in config.php, a label as a word and as a fill
$legacy = [
	'key' => 'legacy', 'name' => 'Old form', 'to' => 'old@example.com', 'subject' => 'Old subject', 'confirm' => true,
	'ownerTemplate' => '/templates/mail-owner', 'userTemplate' => '/templates/mail-user',
	'fields' => [
		[ 'name' => 'email', 'label' => '[[/template/common/form/phone]]', 'type' => 'email', 'required' => true, 'options' => [] ],
		[ 'name' => 'note', 'label' => 'A note', 'type' => 'textarea', 'required' => false, 'options' => [] ],
	],
];
\Nino\Filesystem::mutate( $appData, '/config.php', static function( array $config ) use ( $legacy ): array { $config['/nino/form/forms'] = [ $legacy ]; return $config; }, [] );
$appData['/nino/form/forms'] = [ $legacy ];

[ $status, $body ] = ninoCall( $appData, $panel, [], 'forms/list' );
check( 'the forms of a Nino 1 site are read as they are, and a label that is a fill is shown by what it says in each language',
	$status === 200 && array_column( $body['forms'], 'key' ) === [ 'legacy' ] && $body['forms'][0]['fields'][0]['label'] === '[[/template/common/form/phone]]'
	&& $body['forms'][0]['fields'][0]['texts'] === [ 'de_DE' => 'Telefon', 'en_US' => 'Phone' ] && $body['forms'][0]['fields'][1]['texts'] === [ 'de_DE' => 'A note', 'en_US' => 'A note' ]
	&& $body['forms'][0]['subjectTexts'] === [ 'de_DE' => 'Old subject', 'en_US' => 'Old subject' ] && $body['locales'] === [ 'de_DE', 'en_US' ] && $body['types'] === \Nino\Form::TYPES && $body['reserved'] === \Nino\Form::RESERVED
	&& $body['endpoint'] === true && $body['guards'] === [ 'minSeconds' => 0, 'rateLimit' => 0, 'blocked' => 0 ]
	&& $body['templates'] === [ '/templates/mail-owner', '/templates/mail-quote', '/templates/mail-user' ] );

// Saving what a person did not touch leaves it as it was: the words the panel shows come back as they came
$same = static function( array $form ) use ( $body ): array {
	return [ 'form' => $form, 'key' => $form['key'], 'texts' => [ 'subject' => $body['forms'][0]['subjectTexts'], 'fields' => array_column( $body['forms'][0]['fields'], 'texts' ) ] ];
};
[ $status ] = ninoCall( $appData, $panel, $same( $legacy ), 'forms/save' );
check( 'a form of Nino 1 saved without a change is stored as it was, byte for byte: no label becomes a key of the panel, no text is written',
	$status === 200 && \Nino\Filesystem::getFileContent( $appData, '/config.php', [] )['/nino/form/forms'] === [ $legacy ] && array_filter( array_keys( \Nino\Filesystem::getFileContent( $appData, '/text/en_US.php', [] ) ), static fn( string $k ): bool => str_contains( $k, '/project/form-' ) ) === [] );

// A language the person changed: the words are written under a key of the panel's own and the label is its fill
$edited = $same( $legacy );
$edited['texts']['fields'][1] = [ 'de_DE' => 'Eine Notiz', 'en_US' => 'A note' ];
$edited['texts']['subject'] = [ 'de_DE' => 'Alter Betreff', 'en_US' => 'Old subject' ];
[ $status ] = ninoCall( $appData, $panel, $edited, 'forms/save' );
$stored = \Nino\Filesystem::getFileContent( $appData, '/config.php', [] )['/nino/form/forms'][0];
$en = \Nino\Filesystem::getFileContent( $appData, '/text/en_US.php', [] );
$de = \Nino\Filesystem::getFileContent( $appData, '/text/de_DE.php', [] );
check( 'a label and a subject whose language was changed are written in every language under /project/form-<key>/ and the definition holds the fill; a label that was not changed stays',
	$status === 200 && $stored['fields'][1]['label'] === '[[/project/form-legacy/label/note]]' && $stored['subject'] === '[[/project/form-legacy/mail/subject]]' && $stored['fields'][0]['label'] === '[[/template/common/form/phone]]'
	&& $de['[[/project/form-legacy/label/note]]'] === 'Eine Notiz' && $en['[[/project/form-legacy/label/note]]'] === 'A note' && $de['[[/project/form-legacy/mail/subject]]'] === 'Alter Betreff' );
$sent = [];
$appData['/nino/form/forms'] = [ $stored ];
post( $appData, [ 'form' => 'legacy', 'email' => 'jo@example.com', 'note' => 'hi' ], true );
check( '...and the engine puts the words in: the mail goes out with the subject of the form', count( $sent ) >= 1 && $sent[0]['subject'] === 'Alter Betreff' && $sent[0]['to'] === 'old@example.com' );

// The words are words
$tries = [ 'a fill' => 'x [[/project/mail/address/owner]]', 'a closing bracket' => 'x ]] y' ];
$answers = [];
foreach( $tries as $what => $text ) {
	$bad = $same( $stored );
	$bad['texts']['fields'][1] = [ 'de_DE' => $text, 'en_US' => 'fine' ];
	[ $status, $answer ] = ninoCall( $appData, $panel, $bad, 'forms/save' );
	$answers[ $what ] = $status === 400 && $answer['code'] === 'forms_fields' && $answer['params'] === [ [ 1 => 'brackets' ], [ 1 => 'label' ], [] ];
}
check( 'SAFETY: words that hold a fill are refused at the label, for every language, and nothing is written', array_filter( $answers ) === $answers && \Nino\Filesystem::getFileContent( $appData, '/config.php', [] )['/nino/form/forms'][0] === $stored );
$lines = $same( $stored );
$lines['texts']['subject'] = [ 'de_DE' => "Betreff\r\nBcc: victim@example.org", 'en_US' => "Subject\nBcc: x" ];
$lines['form']['name'] = "Name\r\nBcc: y";
ninoCall( $appData, $panel, $lines, 'forms/save' );
$appData['/nino/form/forms'] = \Nino\Filesystem::getFileContent( $appData, '/config.php', [] )['/nino/form/forms'];
$sent = [];
post( $appData, [ 'form' => 'legacy', 'email' => 'jo@example.com', 'note' => 'hi' ], true );
check( 'SAFETY: control characters of a typed subject, name or label are made spaces by the panel, and no header line of a mail carries one', count( $sent ) >= 1 && preg_match( '/[\r\n]/', $sent[0]['subject']. $sent[0]['to'] ) === 0
	&& preg_match( '/[\x00-\x1F]/', implode( '', array_merge( array_column( \Nino\Filesystem::getFileContent( $appData, '/config.php', [] )['/nino/form/forms'], 'name' ), array_column( \Nino\Filesystem::getFileContent( $appData, '/config.php', [] )['/nino/form/forms'], 'subject' ) ) ) ) === 0 );

[ $status ] = ninoCall( $appData, $panel, [ 'form' => [ 'key' => 'Not A Key', 'fields' => [] ], 'key' => '' ], 'forms/save' );
check( 'a definition the engine would refuse is refused here, by the engine\'s own normalize(), and says where', $status === 400 );

$configBefore = \Nino\Filesystem::getFileContent( $appData, '/config.php', [] );
$field = static fn( string $name, string $type = 'text', array $options = [] ): array => [ 'name' => $name, 'label' => $name, 'type' => $type, 'required' => false, 'options' => $options ];
$problems = [];
foreach( [ [ $field( 'Straße' ), 'name', 'name' ], [ $field( 'date' ), 'name', 'reserved' ], [ $field( 'email' ), 'name', 'duplicate' ], [ $field( 'plan', 'radio' ), 'options', 'options' ], [ $field( 'plan', 'color' ), 'type', 'type' ] ] as [ $bad, $control, $word ] ) {
	[ $status, $answer ] = ninoCall( $appData, $panel, [ 'form' => [ 'fields' => [ $quote['fields'][0], $bad ] ] + $quote, 'key' => '' ], 'forms/save' );
	$problems[] = $status === 400 && $answer['code'] === 'forms_fields' && $answer['params'] === [ [ 1 => $word ], [ 1 => $control ], [] ];
}
check( 'a field the engine would repair is refused at its field with the word of what is wrong and the control it belongs to, nothing written',
	array_filter( $problems ) === $problems && \Nino\Filesystem::getFileContent( $appData, '/config.php', [] ) === $configBefore );

[ $status, $answer ] = ninoCall( $appData, $panel, [ 'form' => [ 'to' => 'a@b', 'ownerTemplate' => '/templates/mail-gone', 'userTemplate' => 'nope' ] + $quote, 'key' => '' ], 'forms/save' );
check( 'a recipient that is no address and a template path that is none are refused at their controls',
	$status === 400 && $answer['params'] === [ [], [], [ 'to' => 'to', 'usertpl' => 'usertpl' ] ] );
[ $status, $answer ] = ninoCall( $appData, $panel, [ 'form' => [ 'ownerTemplate' => '/templates/mail-gone' ] + $quote, 'key' => '' ], 'forms/save' );
check( '...a template of the right shape that is not on disk: an empty mail is worse than no form', $status === 400 && $answer['params'] === [ [], [], [ 'ownertpl' => 'missingtpl' ] ] );

[ $status ] = ninoCall( $appData, $panel, [ 'form' => $quote, 'key' => '' ], 'forms/save' );
[ $dupStatus, $dup ] = ninoCall( $appData, $panel, [ 'form' => $quote, 'key' => '' ], 'forms/save' );
check( 'a form is created, and creating another under a key that is taken is refused instead of replacing it', $status === 200 && $dupStatus === 400 && $dup['code'] === 'forms_duplicate'
	&& array_column( \Nino\Form::forms( $appData ), 'key' ) === [ 'legacy', 'quote' ] );
[ $status ] = ninoCall( $appData, $panel, [ 'form' => [ 'key' => 'offer' ] + $quote, 'key' => 'quote' ], 'forms/save' );
check( 'a rename stays one form', $status === 200 && array_column( \Nino\Form::forms( $appData ), 'key' ) === [ 'legacy', 'offer' ] );
[ $status ] = ninoCall( $appData, $panel, [ 'key' => 'offer' ], 'forms/delete' );
[ $lastStatus, $last ] = ninoCall( $appData, $panel, [ 'key' => 'legacy' ], 'forms/delete' );
[ $noStatus, $none ] = ninoCall( $appData, $panel, [ 'key' => 'Not A Key' ], 'forms/delete' );
check( 'a form is deleted by its key; the last one is not (the kernel would fall back to its contact form), and a key that is none is not a key',
	$status === 200 && $lastStatus === 400 && $last['code'] === 'forms_last' && $noStatus === 400 && $none['code'] === 'forms_key' && array_column( \Nino\Form::forms( $appData ), 'key' ) === [ 'legacy' ] );

[ $status ] = ninoCall( $appData, $panel, [ 'retention' => 12, 'store' => false ], 'forms/settings' );
[ $badStatus, $bad ] = ninoCall( $appData, $panel, [ 'retention' => '12', 'store' => true ], 'forms/settings' );
check( 'the two submission settings are the kernel\'s own config keys, and a retention that is not a number of months in the window is refused',
	$status === 200 && \Nino\Form::retention( $appData ) === 12 && \Nino\Form::stores( $appData ) === false && $badStatus === 400 && $bad['code'] === 'forms_retention' );
ninoCall( $appData, $panel, [ 'retention' => 3, 'store' => true ], 'forms/settings' );

$answers = [];
foreach( [ 'forms/list' => [], 'forms/save' => [ 'form' => $quote ], 'forms/delete' => [ 'key' => 'legacy' ], 'forms/settings' => [ 'retention' => 3 ] ] as $action => $data ) {
	$loggedOut = $appData;
	$loggedOut['./nino/auth/current'] = null;
	$noPerm = $appData;
	ninoUser( $noPerm, 'nobody@example.com', [ '/_admin/other/manage' ] );
	$answers[ $action ] = [ ninoCall( $loggedOut, $panel, $data, $action )[0], ninoCall( $noPerm, $panel, $data, $action )[0] ];
}
check( 'SAFETY: every action of the panel needs the permission of the panel: 401 without an account, 403 with another permission', array_filter( $answers, static fn( array $a ): bool => $a !== [ 401, 403 ] ) === []
	&& array_column( \Nino\Form::forms( $appData ), 'key' ) === [ 'legacy' ] );

// The words of a language are words: a quote, an ampersand and a bracket come out of the panel and onto the page once escaped, not twice
$hard = "What's \"this\" & [that]";
$mk = static fn( string $key, array $extra = [] ): array => [ 'form' => [ 'key' => $key, 'name' => 'K '. $key, 'to' => 'x@example.com', 'subject' => '', 'confirm' => false, 'ownerTemplate' => '/templates/mail-owner', 'userTemplate' => '/templates/mail-user', 'fields' => [ [ 'name' => 'q', 'label' => '', 'type' => 'select', 'required' => false, 'options' => [ $hard, 'B' ] ] ] ] + $extra, 'key' => '', 'texts' => [ 'subject' => [ 'de_DE' => $hard, 'en_US' => 'Subject' ], 'fields' => [ [ 'de_DE' => $hard, 'en_US' => "It's" ] ] ] ];
[ $status ] = ninoCall( $appData, $panel, $mk( 'words' ), 'forms/save' );
$appData['/nino/form/forms'] = \Nino\Filesystem::getFileContent( $appData, '/config.php', [] )['/nino/form/forms'];
[ , $listed ] = ninoCall( $appData, $panel, [], 'forms/list' );
$wordsForm = array_values( array_filter( $listed['forms'], static fn( array $f ): bool => $f['key'] === 'words' ) )[0];
\Nino\Locales::useLocale( $appData, 'de_DE' );
$page = $draw( '[form key="words"]' );
check( 'words with a quote, an ampersand and a bracket are shown as typed in the panel and once escaped on the page, in the label and in an option',
	$status === 200 && $wordsForm['fields'][0]['texts'] === [ 'de_DE' => $hard, 'en_US' => "It's" ] && $wordsForm['subjectTexts']['de_DE'] === $hard
	&& str_contains( $page, 'What&#039;s &quot;this&quot; &amp; [that]</label>' ) === true && str_contains( $page, '>What&#039;s &quot;this&quot; &amp; [that]</option>' ) === true && str_contains( $page, '&amp;#' ) === false && str_contains( $page, '&amp;quot;' ) === false );
\Nino\Locales::useLocale( $appData, 'en_US' );

// The key of a form is the stem of the keys of its words, as it is: forms never share one
$fits = [];
foreach( [ 'a-b', 'a--b', 'a-b-', str_repeat( 'a', 59 ). '-one', str_repeat( 'a', 59 ). '-two', str_repeat( 'a', 44 ). '1'. str_repeat( 'a', 15 ), str_repeat( 'a', 44 ). '2'. str_repeat( 'a', 15 ) ] as $key ) {
	$fits[ $key ] = \Nino\Modules\Forms\Admin::segment( $key );
}
check( 'only a key that is words of lower case letters and digits joined by single hyphens, at most 60 characters, is a stem of text keys', array_values( $fits ) === [ true, false, false, false, false, true, true ] );
$configBefore = \Nino\Filesystem::getFileContent( $appData, '/config.php', [] );
$refusals = [];
foreach( [ 'a--b', 'a-b-', 'new', str_repeat( 'a', 61 ) ] as $bad ) {
	[ $status, $answer ] = ninoCall( $appData, $panel, $mk( $bad ), 'forms/save' );
	$refusals[] = $status === 400 && $answer['params'] === [ [], [], [ 'key' => 'key' ] ];
}
check( 'a form is not made under a key that cannot be a stem (a double or a trailing hyphen, 61 characters, or the address of the panel\'s own "new")', array_filter( $refusals ) === $refusals && \Nino\Filesystem::getFileContent( $appData, '/config.php', [] ) === $configBefore );
foreach( [ 'a-b', str_repeat( 'a', 44 ). '1'. str_repeat( 'a', 15 ), str_repeat( 'a', 44 ). '2'. str_repeat( 'a', 15 ) ] as $good )
	ninoCall( $appData, $panel, array_replace_recursive( $mk( $good ), [ 'texts' => [ 'fields' => [ [ 'de_DE' => 'Wort '. $good, 'en_US' => 'Word '. $good ] ] ] ] ), 'forms/save' );
$en = \Nino\Filesystem::getFileContent( $appData, '/text/en_US.php', [] );
$owners = [];
foreach( $en as $k => $v )
	if( str_contains( (string) $k, '/project/form-' ) === true && str_contains( (string) $k, '/label/' ) === true )
		$owners[ (string) $k ] = $v;
check( 'forms whose keys differ only after the 40th character, or by one hyphen, keep words of their own: nothing of one is written under the key of another', count( array_filter( array_keys( $owners ), static fn( string $k ): bool => str_contains( $k, 'form-a-b/' ) ) ) === 1
	&& count( array_filter( $owners, static fn( string $v ): bool => str_starts_with( $v, 'Word a' ) ) ) === 3 && count( array_unique( array_keys( $owners ) ) ) === count( $owners ) );
// A form of a Nino 1 site whose key is no stem keeps its labels in the definition: changing a language of it is refused, not written elsewhere
$appData['/nino/form/forms'] = [ \Nino\Form::normalize( [ 'key' => 'x--y' ] + $quote ) ];
\Nino\Filesystem::mutate( $appData, '/config.php', static function( array $config ) use ( $appData ): array { $config['/nino/form/forms'] = $appData['/nino/form/forms']; return $config; }, [] );
[ $status, $answer ] = ninoCall( $appData, $panel, [ 'form' => [ 'key' => 'x--y' ] + $quote, 'key' => 'x--y', 'texts' => [ 'fields' => [ [ 'de_DE' => 'Neu', 'en_US' => 'New' ] ] ] ], 'forms/save' );
check( 'a language changed on a form whose key is no stem is refused at the label with its own word; nothing is written', $status === 400 && $answer['params'] === [ [ 0 => 'textkey' ], [ 0 => 'label' ], [] ]
	&& \Nino\Filesystem::getFileContent( $appData, '/text/en_US.php', [] ) === $en );

// A label may name only the words of its own form: the key of another word, however it is written, is written nowhere
$appData['/nino/form/forms'] = [ \Nino\Form::normalize( [ 'key' => 'own' ] + $quote ) ];
\Nino\Filesystem::putFileContent( $appData, '/text/en_US.php', $en + [ '[[/template/common/form/submit]]' => 'Send' ] );
$enBefore = \Nino\Filesystem::getFileContent( $appData, '/text/en_US.php', [] );
$foreign = [ 'key' => 'own', 'name' => 'Own', 'to' => 'x@example.com', 'subject' => '[[/template/common/form/submit]]', 'confirm' => false, 'ownerTemplate' => '/templates/mail-owner', 'userTemplate' => '/templates/mail-user',
	'fields' => [ [ 'name' => 'email', 'label' => '[[/template/common/form/submit]]', 'type' => 'email', 'required' => false, 'options' => [] ] ] ];
[ $status ] = ninoCall( $appData, $panel, [ 'form' => $foreign, 'key' => 'own', 'texts' => [ 'subject' => [ 'de_DE' => 'X', 'en_US' => 'Overwritten' ], 'fields' => [ [ 'de_DE' => 'Überschrieben', 'en_US' => 'Overwritten' ] ] ] ], 'forms/save' );
$now = \Nino\Filesystem::getFileContent( $appData, '/config.php', [] )['/nino/form/forms'];
$own = array_values( array_filter( $now, static fn( array $f ): bool => $f['key'] === 'own' ) )[0];
check( 'SAFETY: a label or a subject that names the key of another word is not written through: the words of the form go to a key of its own and the other word stays',
	$status === 200 && \Nino\Filesystem::getFileContent( $appData, '/text/en_US.php', [] )['[[/template/common/form/submit]]'] === 'Send' && $own['fields'][0]['label'] === '[[/project/form-own/label/email]]' && $own['subject'] === '[[/project/form-own/mail/subject]]' );

// The message of a page that was sent back is escaped
\Nino\Html::addFills( $appData, [ '[[/module/form/info/success]]' => '<b>"A" & \'B\'</b> [csrf]' ], '*' );
$appData['/nino/form/forms'] = [ \Nino\Form::normalize( [ 'key' => 'own' ] + $quote ) ];
$appData['./nino/http/requests'] = [ [ '/nino/http/request' => [ 'query' => [ 'form' => 'success' ] ] ] ];
$said = $draw( '[form key="own"]' );
$appData['./nino/http/requests'] = [];
check( 'SAFETY: the message drawn for a browser without a script is text: markup, quotes and a call in the words are escaped', str_contains( $said, 'data-form-outcome="success">&lt;b&gt;&quot;A&quot; &amp; &#039;B&#039;&lt;/b&gt; [csrf]</p>' ) === true && substr_count( $said, 'name="_csrf"' ) === 1 );

// A form that is renamed takes its words along; a form made under the old key begins with words of its own
$one = static fn( string $key, string $label, array $texts, string $was = '' ): array => [ 'form' => [ 'key' => $key, 'name' => 'N '. $key, 'to' => 'x@example.com', 'subject' => '', 'confirm' => false, 'ownerTemplate' => '/templates/mail-owner', 'userTemplate' => '/templates/mail-user', 'fields' => [ [ 'name' => 'q', 'label' => $label, 'type' => 'text', 'required' => false, 'options' => [] ] ] ], 'key' => $was, 'texts' => [ 'fields' => [ $texts ] ] ];
$listOf = static function() use ( &$appData, $panel ): array { [ , $body ] = ninoCall( $appData, $panel, [], 'forms/list' ); return array_column( $body['forms'], null, 'key' ); };
ninoCall( $appData, $panel, $one( 'ren', '', [ 'de_DE' => 'Original', 'en_US' => 'Original' ] ), 'forms/save' );
$before = $listOf()['ren']['fields'][0];
$renamed = $one( 'ren2', $before['label'], $before['texts'] );
$renamed['key'] = 'ren';
[ $status ] = ninoCall( $appData, $panel, $renamed, 'forms/save' );
ninoCall( $appData, $panel, $one( 'ren', '', [ 'de_DE' => 'Neu', 'en_US' => 'New' ] ), 'forms/save' );
$after = $listOf();
check( 'a form that is renamed takes its words to the stem of its new key, and a form made under the old key afterwards has words of its own: neither changes the other',
	$status === 200 && $after['ren2']['fields'][0]['texts'] === [ 'de_DE' => 'Original', 'en_US' => 'Original' ] && str_starts_with( $after['ren2']['fields'][0]['label'], '[[/project/form-ren2/' ) === true
	&& $after['ren']['fields'][0]['texts'] === [ 'de_DE' => 'Neu', 'en_US' => 'New' ] && str_starts_with( $after['ren']['fields'][0]['label'], '[[/project/form-ren/' ) === true );

// The words of a deleted form stay in the text files; a form made under its key begins with its own labels and writes them over the old words at its first words
ninoCall( $appData, $panel, $one( 'del', '', [ 'de_DE' => 'Alt', 'en_US' => 'Old' ] ), 'forms/save' );
ninoCall( $appData, $panel, [ 'key' => 'del' ], 'forms/delete' );
[ $status ] = ninoCall( $appData, $panel, $one( 'del', '', [ 'de_DE' => '', 'en_US' => '' ] ), 'forms/save' );
$fresh = $listOf()['del']['fields'][0];
ninoCall( $appData, $panel, $one( 'del', $fresh['label'], [ 'de_DE' => 'Frisch', 'en_US' => 'Fresh' ], 'del' ), 'forms/save' );
$again = $listOf()['del']['fields'][0];
check( 'a form made under the key of a deleted one inherits none of its words: it has its own label until words are given, and those replace the old words under the key',
	$status === 200 && $fresh['label'] === 'q' && $fresh['texts'] === [ 'de_DE' => 'q', 'en_US' => 'q' ] && $again['texts'] === [ 'de_DE' => 'Frisch', 'en_US' => 'Fresh' ] );

// The check of the key holds for every post that makes a form: saying it was that key makes nothing of it, a form that is there keeps its key
$maker = static fn( string $key ): array => $one( $key, '', [ 'de_DE' => 'X', 'en_US' => 'X' ], $key );
$bad = [];
foreach( [ 'a--x', 'new', 'q--' ] as $key )
	$bad[] = ninoCall( $appData, $panel, $maker( $key ), 'forms/save' )[0] === 400;
$legacyKey = array_column( \Nino\Form::forms( $appData ), 'key' );
check( 'SAFETY: a post that says it was the key it makes (a--x, new) makes no form of it; only a form that is there keeps a key that does not fit', array_filter( $bad ) === $bad && in_array( 'a--x', array_column( \Nino\Form::forms( $appData ), 'key' ), true ) === false && in_array( 'new', $legacyKey, true ) === false );
$appData['/nino/form/forms'] = [ \Nino\Form::normalize( [ 'key' => 'x--y' ] + $quote ) ];
\Nino\Filesystem::mutate( $appData, '/config.php', static function( array $config ) use ( $appData ): array { $config['/nino/form/forms'] = $appData['/nino/form/forms']; return $config; }, [] );
check( '...a form of Nino 1 that is there with such a key is saved as it was', ninoCall( $appData, $panel, [ 'form' => [ 'key' => 'x--y' ] + $quote, 'key' => 'x--y' ], 'forms/save' )[0] === 200 );

// What was typed is what comes back: an entity typed in a label stays an entity, also when another language changes
ninoCall( $appData, $panel, $one( 'ent', '', [ 'de_DE' => '&lt;b&gt; &amp; "x"', 'en_US' => 'one' ] ), 'forms/save' );
$got = $listOf()['ent']['fields'][0];
ninoCall( $appData, $panel, $one( 'ent', $got['label'], [ 'de_DE' => $got['texts']['de_DE'], 'en_US' => 'two' ], 'ent' ), 'forms/save' );
$got2 = $listOf()['ent']['fields'][0];
check( 'an entity typed in a label comes back as typed and survives the change of another language', $got['texts']['de_DE'] === '&lt;b&gt; &amp; "x"' && $got2['texts'] === [ 'de_DE' => '&lt;b&gt; &amp; "x"', 'en_US' => 'two' ] );

// A key of words belongs to one definition: a label of another form that names a key below this stem keeps it
$appData['/nino/form/forms'] = [ \Nino\Form::normalize( [ 'key' => 'u2', 'fields' => [ [ 'name' => 'q', 'label' => '[[/project/form-u1/label/q]]', 'type' => 'text' ] ] ] + $quote ) ];
\Nino\Filesystem::mutate( $appData, '/config.php', static function( array $config ) use ( $appData ): array { $config['/nino/form/forms'] = $appData['/nino/form/forms']; return $config; }, [] );
ninoCall( $appData, $panel, $one( 'u1', '', [ 'de_DE' => 'Eins', 'en_US' => 'One' ] ), 'forms/save' );
check( 'a key of words that another form names is not taken for a new label: the label gets a key of its own', ( $listOf()['u1']['fields'][0]['label'] ?? '' ) === '[[/project/form-u1/label/q-2]]' );

echo "\n";
echo "The panel - what it is built from\n";

$script = (string) file_get_contents( $dir. '/assets/admin.js' );
check( 'the panel script is built from the blocks of the workbench: a table, tabs, a dialog for a field, the languages one under the other, the cluster to move rows, one main button in the bar - and builds nothing itself',
	preg_match( '/Nino\.adminUi\.table\(/', $script ) === 1 && preg_match( '/Nino\.adminUi\.tabs\(/', $script ) === 1 && preg_match( '/Nino\.adminUi\.formDialog\(/', $script ) === 1 && str_contains( $script, "type : 'locales'" ) === true
	&& str_contains( $script, 'nino-admin-move' ) === true && preg_match( '/actionBar\(\s*\{/', $script ) === 1 && str_contains( $script, 'noValidate = true' ) === true && str_contains( $script, 'nino-admin-fieldgrid' ) === true
	&& str_contains( $script, 'Nino.adminUi.say(' ) === true && str_contains( $script, 'dirty' ) === true
	&& preg_match( '/createElement\(\s*[\'"]button|(?<![.\w])confirm\(|window\.confirm|(?<![.\w])alert\(|type\s*=\s*[\'"]file|nino-admin-btn-/', $script ) === 0 );
preg_match_all( '/\bw\(\s*\'([^\']+)\'\s*\)/', $script, $wanted );
$words = [ 'en_US' => include $dir. '/text/en_US.php', 'de_DE' => include $dir. '/text/de_DE.php' ];
$missing = [];
foreach( array_unique( $wanted[1] ) as $word )
	foreach( $words as $locale => $table )
		if( isset( $table[ '[[/_admin/forms/'. $word. ']]' ] ) === false )
			$missing[] = $locale. ' '. $word;
preg_match_all( '/\'\/_admin\/(?!common|features)([a-z\/_-]*[a-z])\'/', $script, $whole );
foreach( \Nino\Form::TYPES as $type )
	$whole[1][] = 'forms/type/'. $type;
foreach( array_unique( $whole[1] ) as $word )
	foreach( $words as $locale => $table )
		if( isset( $table[ '[[/_admin/'. $word. ']]' ] ) === false )
			$missing[] = $locale. ' '. $word;
check( 'every word the script asks for is said in both languages of the panel, and both say the same keys', $missing === [] && array_keys( $words['en_US'] ) === array_keys( $words['de_DE'] ) );
$codes = [ 'forms_fields', 'forms_invalid', 'forms_duplicate', 'forms_key', 'forms_last', 'forms_retention', 'forms_texts' ];
$problemCodes = ( new ReflectionClassConstant( \Nino\Modules\Forms\Admin::class, 'PROBLEMS' ) )->getValue();
check( 'the panel has a sentence for each code it answers and a word for each problem the engine names, so a code of a later kernel still has the general word',
	array_filter( $codes, static fn( string $c ): bool => isset( $words['en_US'][ '[[/_admin/error/'. $c. ']]' ] ) === false ) === []
	&& array_filter( array_merge( array_values( $problemCodes ), [ 'keytaken', 'missingtpl', 'form', 'brackets', 'field' ] ), static fn( string $w ): bool => isset( $words['en_US'][ '[[/_admin/forms/problem/'. $w. ']]' ] ) === false ) === [] );
check( 'the panel has a sign for the rail and its own icon, the words of its dialogs are not the workbench\'s: Save and Delete are taken from the common words',
	str_contains( \Nino\Modules\Forms\Admin::icon(), '<svg' ) === true && preg_match( '/forms\/label\/(save|cancel)\]\]/', (string) file_get_contents( $dir. '/text/en_US.php' ) ) === 0 );

echo "\n";
echo "The feature off - the forms stay\n";

$appData['/nino/modules'] = array_values( array_diff( $appData['/nino/modules'], [ '\\Nino\\Modules\\Forms' ] ) );
check( 'the definitions stay in config.php and the engine goes on reading them', array_column( \Nino\Form::forms( $appData ), 'key' ) === array_column( $appData['/nino/form/forms'], 'key' ) && $appData['/nino/form/forms'] !== [] );

// The script of the panel runs over a stand-in of the workbench (forms-js-smoke.js)
$node = function_exists( 'shell_exec' ) === true ? trim( (string) @shell_exec( 'command -v node 2>/dev/null' ) ) : '';
if( $node === '' || function_exists( 'exec' ) === false )
	echo "  --  - node is not available here: forms-js-smoke.js was NOT run\n";
else {
	$output = [];
	$exit = 1;
	exec( 'NINO_ROOT='. escapeshellarg( $root ). ' '. escapeshellarg( $node ). ' '. escapeshellarg( __DIR__. '/forms-js-smoke.js' ). ' 2>&1', $output, $exit );
	check( 'forms-js-smoke.js passes - '. ( $output === [] ? 'no output' : end( $output ) ), $exit === 0 );
}

ninoDone( $appData );
