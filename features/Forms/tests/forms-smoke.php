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
 *										it off leaves every form working - and, where node is on
 *										the path, forms-js-smoke.js beside it, the panel's script
 *										over a dom stand-in.
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

// The engine this feature extends. Its manifest names ^1.3 for it and ^1.4
// for what follows below (a sectioned 'manual' needs a kernel newer than the
// v1.2.0-beta tag as well).
// A checkout that predates it cannot run a line of what follows, and a stack
// trace two screens down is a worse way to learn that than one sentence here
// (tests/build-smoke.php in this repository does the same for \Nino\Features)
if( class_exists( '\Nino\Form' ) === false ) {
	fwrite( STDERR, 'The Nino checkout at '. $root. ' ('. \Nino\VERSION. ') has no \Nino\Form - the Forms feature extends the kernel\'s form engine, which arrived with it'. "\n" );
	exit( 2 );
}

// What the builder asks the engine, and the reason the manifest names ^1.4:
// \Nino\Form::problems() arrived with the checkbox, radio and date types, in
// the kernel that is tagged 1.4. On an older one there is nothing here to
// test - the catalogue's CI runs this against Nino's latest tag as well, where
// this file leaves quietly (tests/build-smoke.php, which asks the kernel for
// the feature's availability, is the one that needs the matching tag)
if( method_exists( '\Nino\Form', 'problems' ) === false ) {
	echo 'The Nino checkout at '. $root. ' ('. \Nino\VERSION. ') has no \Nino\Form::problems() - the Forms feature is written for Nino 1.4 and later, so there is nothing to test against it'. "\n";
	exit( 0 );
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
	'[[/project/mail/address/owner]]'		=> 'owner@example.com',
	'[[/module/form/subject/owner]]'	=> 'New inquiry',
	'[[/module/form/subject/user]]'	=> 'Thanks',
	'[[/template/common/form/name]]'		=> 'Name',
	'[[/template/common/form/email]]'		=> 'E-Mail',
	'[[/template/common/form/reason]]'			=> 'Subject',
	'[[/template/common/form/message]]'	=> 'Message',
	'[[/template/common/form/submit]]'	=> 'Send',
	'[[/template/common/form/required]]'			=> 'required',
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
	// The kernel's own per-ip mail cap - five an hour, and a submission it
	// refuses is a 429 - is not what is tested here: every submission
	// starts with a fresh window, so only the feature's guards answer
	\Nino\Filesystem::putFileContent( $appData, '/data/ratelimit.php', [] );
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
check( 'it brings an install unit that carries only the section of the privacy policy: the mail templates a form points at are the kernel\'s own', is_dir( $dir. '/install/templates' ) === false && is_dir( $dir. '/install/text' ) === false );

\Nino\AppData::writeContentData( $appData, [ '/nino/modules', '/nino/locales/available', '/nino/locales/native' ] );
ninoWarnings();

// Where this kernel has the Legal module: the type its unit creates, which is what
// the feature's section is added to when it is activated below
$legalFile	= $root. '/_nino/Nino/Modules/Legal/install/elements/privacy.php';
$hasLegal		= class_exists( '\\Nino\\Modules\\Legal' ) === true && is_file( $legalFile ) === true;

if( $hasLegal === true )
	check( 'the module\'s type is seeded', \Nino\Elements::seed( $appData, 'privacy', include $legalFile, [ 'de_DE', 'en_US' ] ) === true );

check( 'the Features registry lists it, inactive', ( \Nino\Features::get( $appData, 'forms' )['active'] ?? null ) === false );
check( 'activation succeeds', \Nino\Features::activate( $appData, 'forms' ) === true );
check( 'the class is listed in /nino/modules and the version recorded', in_array( '\\Nino\\Modules\\Forms', \Nino\Filesystem::getFileContent( $appData, '/config.php', [] )['/nino/modules'], true ) === true
	&& \Nino\Features::get( $appData, 'forms' )['installed'] === $manifest['version'] );
check( 'its unit carries the feature\'s section of the privacy policy, and nothing else', array_keys( include $dir. '/install/manifest.php' ) === [ 'elements' ]
	&& ( include $dir. '/install/manifest.php' )['elements'] === [ 'privacy' => 'elements/privacy.php' ] && is_file( $dir. '/install/elements/privacy.php' ) === true );

if( $hasLegal === false )
	echo "  note - this Nino has no \\Nino\\Modules\\Legal: the section is not added to a type here, tests/legal-smoke.php of the catalogue says what it can\n";
else {
	$privacyOf = static fn( string $locale ): array => (array) \Nino\Elements::getElement( $appData, '/privacy/forms', $locale, false );
	check( 'activation added the section "forms" to the module\'s type, in both languages and at its position', ( $privacyOf( 'de_DE' )['title'] ?? null ) === 'Weitere Formulare'
		&& ( $privacyOf( 'en_US' )['title'] ?? null ) === 'Other forms' && ( $privacyOf( 'en_US' )['order'] ?? null ) === 520 );

	// An editor's change stays, and the next activation adds nothing
	\Nino\Filesystem::mutate( $appData, '/elements/privacy.php', static function( array $type ): array {
		$type['de_DE']['forms']['title'] = 'Vom Redakteur geändert';
		return $type;
	}, [] );
	$typeBefore = \Nino\Filesystem::getFileContent( $appData, '/elements/privacy.php', [] );
	check( 'activating again leaves the type as it is - an edited section stays and nothing is added twice', \Nino\Features::activate( $appData, 'forms' ) === true
		&& \Nino\Filesystem::getFileContent( $appData, '/elements/privacy.php', [] ) === $typeBefore );
}

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
check( 'a label written as a fill is resolved, not printed', str_contains( $html, '>Message *<' ) === true && str_contains( $html, '[[/template/common/form/' ) === false );

/*	...and drawn as the text it is. A label and a select option are the same
	kind of value - a fill key, or a word an operator typed - and the option
	was rendered and then escaped while the label was only rendered. Whichever
	of the two is wrong it is that one: the <label> is the template's markup,
	and a form definition is not where more of it comes from	*/
$markupLabel = $appData;
$markupLabel[ \Nino\Form::FORMS ] = [ [
	'key' 		=> 'markup',
	'fields'	=> [
		[ 'name' => 'name', 'label' => '<b onclick="alert(1)">Name</b>', 'type' => 'text', 'required' => true, 'options' => [] ],
		[ 'name' => 'pick', 'label' => 'Pick', 'type' => 'select', 'required' => false, 'options' => [ '<i>one</i>' ] ],
	],
] ];
$markupLabelHtml = \Nino\Html::renderHtml( $markupLabel, '[form key="markup"]' );
check( 'a label carrying markup is drawn as text, not as markup', str_contains( $markupLabelHtml, '<b onclick' ) === false
	&& str_contains( $markupLabelHtml, '&lt;b onclick=&quot;alert(1)&quot;&gt;Name&lt;/b&gt;' ) === true );
check( '...the same way a select option already was', str_contains( $markupLabelHtml, '<i>one</i>' ) === false
	&& str_contains( $markupLabelHtml, '&lt;i&gt;one&lt;/i&gt;' ) === true );
check( 'the classes the shared .nino-form script drives are all there', str_contains( $html, 'class="nino-form"' ) === true
	&& str_contains( $html, 'class="nino-form-message"' ) === true && str_contains( $html, 'class="nino-form-trap"' ) === true
	&& str_contains( $html, 'nino-form-submit' ) === true );

check( 'a key no form has draws nothing at all - an empty page beats a form that posts nowhere', \Nino\Html::renderHtml( $appData, '[form key="nowhere"]' ) === '' );

/*	The three types a form can ask for beyond text: a checkbox for a consent, a
	group of radio buttons and a date. The checkbox carries its words inside
	its own label, so there is no second <label> for it; the radio group is one
	fieldset with a legend, an input for every option and the required mark on
	every member - the browser asks for the group, not for one button	*/
$typed = $appData;
$typed[ \Nino\Form::FORMS ] = [ [
	'key' 		=> 'typed',
	'fields'	=> [
		[ 'name' => 'privacy',	'label' => 'I agree',		'type' => 'checkbox',	'required' => true,		'options' => [] ],
		[ 'name' => 'news',			'label' => 'Newsletter',	'type' => 'checkbox',	'required' => false,	'options' => [] ],
		[ 'name' => 'plan',			'label' => 'Plan',				'type' => 'radio',		'required' => true,		'options' => [ 'Small', 'Large' ] ],
		[ 'name' => 'day',			'label' => 'Day',					'type' => 'date',			'required' => false,	'options' => [] ],
	],
] ];
$typedHtml = \Nino\Html::renderHtml( $typed, '[form key="typed"]' );
check( 'a checkbox is one label around the input and its words, with the required mark after them and no <label for> of its own',
	str_contains( $typedHtml, '<label class="nino-forms-check"><input type="checkbox" id="form-typed-privacy" name="privacy" value="1" required> I agree *</label>' ) === true
	&& str_contains( $typedHtml, '<label for="form-typed-privacy">' ) === false );
check( '...an optional one carries neither', str_contains( $typedHtml, '<input type="checkbox" id="form-typed-news" name="news" value="1"> Newsletter</label>' ) === true );
check( 'a radio group is a fieldset with a legend and one input for every option, all of them named like the field',
	str_contains( $typedHtml, '<fieldset class="nino-forms-group"><legend>Plan *</legend>' ) === true
	&& substr_count( $typedHtml, 'type="radio"' ) === 2 && substr_count( $typedHtml, 'name="plan"' ) === 2
	&& str_contains( $typedHtml, 'id="form-typed-plan-1" name="plan" value="Small" required> Small</label>' ) === true
	&& str_contains( $typedHtml, 'id="form-typed-plan-2" name="plan" value="Large" required> Large</label>' ) === true );
check( 'a date goes through the input every other typed field uses', str_contains( $typedHtml, '<input type="date" id="form-typed-day" name="day" class="nino-form-input">' ) === true );

/*	Hostile text in the new fields is text as well, in the label and in the
	option and in the value attribute	*/
$hostile = $appData;
$hostile[ \Nino\Form::FORMS ] = [ [
	'key' 		=> 'hostile',
	'fields'	=> [
		[ 'name' => 'agree',	'label' => '<script>alert(1)</script>',		'type' => 'checkbox',	'required' => false,	'options' => [] ],
		[ 'name' => 'plan',		'label' => '"><img src=x onerror=alert(2)>',	'type' => 'radio',		'required' => false,	'options' => [ '<b>x</b>', '" onfocus="alert(3)' ] ],
	],
] ];
$hostileHtml = \Nino\Html::renderHtml( $hostile, '[form key="hostile"]' );
check( 'a checkbox label, a radio legend and radio options carrying markup are drawn as text', str_contains( $hostileHtml, '<script>' ) === false && str_contains( $hostileHtml, '<img' ) === false
	&& str_contains( $hostileHtml, '<b>x</b>' ) === false && str_contains( $hostileHtml, '" onfocus="' ) === false
	&& str_contains( $hostileHtml, '&lt;script&gt;alert(1)&lt;/script&gt;' ) === true && str_contains( $hostileHtml, 'value="&quot; onfocus=&quot;alert(3)"' ) === true );

/*	[form]'s output is rendered once more, which turns an option written as a
	fill key into the text it stands for - in the value as well as in the
	words, and a value that is not what is stored is a value the engine
	refuses. The bracket is a character reference in the attribute, which the
	browser reads back as the bracket	*/
\Nino\Html::addFills( $appData, [ '[[/x/opt]]' => 'Small', '[[/x/opt-other]]' => 'Other' ], '*' );
$optionsForm = $appData;
$optionsForm[ \Nino\Form::FORMS ] = [ [
	'key' 		=> 'options',
	'fields'	=> [
		[ 'name' => 'size',	'label' => 'Size',	'type' => 'select',	'required' => true,	'options' => [ '[[/x/opt]]', '[[/x/opt-other]]' ] ],
		[ 'name' => 'plan',	'label' => 'Plan',	'type' => 'radio',	'required' => true,	'options' => [ '[[/x/opt]]', 'Large' ] ],
	],
] ];
$optionsHtml = \Nino\Html::renderHtml( $optionsForm, '[form key="options"]' );
check( 'an option written as a fill key shows the text it stands for and keeps the key as its value, in a select and in a radio group',
	str_contains( $optionsHtml, '<option value="&#91;&#91;/x/opt]]">Small</option>' ) === true
	&& str_contains( $optionsHtml, 'name="plan" value="&#91;&#91;/x/opt]]" required> Small</label>' ) === true
	&& str_contains( $optionsHtml, 'value="Small"' ) === false && str_contains( $optionsHtml, 'value="Other"' ) === false );

/*	No field template carries a <p> or a <button>: the shared .nino-form script
	takes the form's first <p> for its message and the first <button> for its
	submit, and the fields come first	*/
$fieldTemplates = [ 'form-label', 'form-input', 'form-textarea', 'form-select', 'form-option', 'form-checkbox', 'form-radio', 'form-radio-option' ];
$paragraphs = array_filter( $fieldTemplates, static fn( string $name ): bool => preg_match( '/<(p|button)[\s>]/i', (string) file_get_contents( dirname( __DIR__ ). '/templates/'. $name. '.tpl' ) ) === 1 );
check( 'no field template carries a <p> or a <button>'. ( $paragraphs === [] ? '' : ' - '. implode( ', ', $paragraphs ) ), $paragraphs === [] );

/*	The stylesheet joins Nino.css in one bundle, and .nino-form-* is the
	kernel's family. The classes the new templates and the stylesheet write are
	this feature's own - read as selectors, not as the comments that talk about
	the other family	*/
$uncommented = static fn( string $file ): string => (string) preg_replace( '#/\*.*?\*/#s', '', (string) file_get_contents( $file ) );
$ninoCss = $uncommented( $root. '/_nino/Nino.css' );
$written = [];
foreach( [ 'form-checkbox', 'form-radio', 'form-radio-option' ] as $name ) {
	preg_match_all( '/class="([^"]*)"/', (string) file_get_contents( dirname( __DIR__ ). '/templates/'. $name. '.tpl' ), $found );
	foreach( $found[1] as $list )
		$written = array_merge( $written, (array) preg_split( '/\s+/', trim( $list ) ) );
}
preg_match_all( '/\.(nino-[a-z0-9-]+)/', $uncommented( dirname( __DIR__ ). '/assets/forms.css' ), $found );
$written = array_values( array_unique( array_merge( $written, $found[1] ) ) );
$styledByKernel = array_values( array_filter( $written, static fn( string $class ): bool => preg_match( '/\.'. preg_quote( $class, '/' ). '(?![\w-])/', $ninoCss ) === 1 ) );
check( 'no class the new templates or the stylesheet write is one Nino.css styles', $written !== [] && $styledByKernel === [] );
check( 'the stylesheet is bundled into the project\'s own /.cache/style.css', in_array( '/features/Forms/assets/forms.css', \Nino\Html::getAssets( $appData, '/.cache/style.css' ), true ) === true );

echo "\n";


// --- The builder -------------------------------------------------------------

echo "Forms\\Admin - the builder writes the key the kernel reads\n";

\Nino\Auth::insertUser( $appData, 'dev@example.com', 'correct horse battery staple', [ \Nino\Modules\Forms\Admin::MANAGE_PERM ] );
\Nino\Auth::loginUser( $appData, 'dev@example.com', 'correct horse battery staple' );
\Nino\Admin\Admin::init( $appData );

// The mail templates a form names are files of the project, and a form that
// names one that is not there is refused: a project has the pair its install
// unit wrote, the header and footer they are wrapped in, and one of its own
foreach( [ 'mail-owner', 'mail-user', 'mail-header', 'mail-footer', 'mail-quote' ] as $mail )
	\Nino\Filesystem::putFileContent( $appData, '/templates/'. $mail. '.tpl', '<p>'. $mail. '</p>[[fields]]' );
ninoWarnings();

[ $status, $body ] = callFormsAdmin( $appData, 'forms/list' );
check( 'forms/list answers the forms, the types a field may be and the names it may not take', $status === 200
	&& array_column( $body['forms'], 'key' ) === [ 'contact' ] && $body['types'] === \Nino\Form::TYPES && $body['reserved'] === \Nino\Form::RESERVED );
check( '...and says that nothing is defined yet, so the list is showing the fallback', $body['default'] === true );
check( '...and that the endpoint every form posts to is switched on', $body['endpoint'] === true );
check( '...with the two things about the submissions a project decides', $body['retention'] === \Nino\Form::RETENTION_MONTHS && $body['store'] === true );
check( '...and the mail templates a form can name: the project\'s mail-* files, without the header and the footer, sorted',
	$body['templates'] === [ '/templates/mail-owner', '/templates/mail-quote', '/templates/mail-user' ] );

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

/*	What the engine would repair instead of keep is refused here, and the answer
	says where: the index of the field in the posted list, the control of that
	field, and the sentence in the language of whoever is looking. Nothing is
	written - the config is what it was	*/
$configBefore = \Nino\Filesystem::getFileContent( $appData, '/config.php', [] );
$withField = static fn( array $field ): array => [ 'form' => [ 'fields' => [ $quote['fields'][0], $field ] ] + $quote, 'key' => '' ];
$field = static fn( string $name, string $type = 'text', array $options = [] ): array => [ 'name' => $name, 'label' => $name, 'type' => $type, 'required' => false, 'options' => $options ];

foreach( [
	'a name with an umlaut'							=> [ $field( 'Straße' ), 'name' ],
	'a name that is a label'						=> [ $field( 'Ihre Nachricht' ), 'name' ],
	'a name the form keeps for itself'	=> [ $field( 'date' ), 'name' ],
	'a name another field has'					=> [ $field( 'email' ), 'name' ],
	'radio buttons without an option'		=> [ $field( 'plan', 'radio' ), 'options' ],
	'a type nobody knows'								=> [ $field( 'plan', 'color' ), 'type' ],
] as $label => [ $bad, $control ] ) {
	[ $status, $body ] = callFormsAdmin( $appData, 'forms/save', $withField( $bad ) );
	check( $label. ' is refused at its field: 400, the sentence under index 1 and the control '. $control. ', nothing written', $status === 400
		&& is_string( $body['error'] ) === true && array_keys( $body['fields'] ) === [ 1 ] && is_string( $body['fields'][1] ) === true && $body['fields'][1] !== ''
		&& $body['controls'] === [ 1 => $control ] && \Nino\Filesystem::getFileContent( $appData, '/config.php', [] ) === $configBefore );
}

[ $status, $body ] = callFormsAdmin( $appData, 'forms/save', [ 'form' => [ 'to' => 'a@b' ] + $quote, 'key' => '' ] );
check( 'a recipient that is no address is refused at the "to" control', $status === 400 && array_keys( $body['about'] ) === [ 'to' ] && $body['fields'] === [] );

[ $status, $body ] = callFormsAdmin( $appData, 'forms/save', [ 'form' => [ 'ownerTemplate' => '/templates/../x', 'userTemplate' => 'nope' ] + $quote, 'key' => '' ] );
check( '...a template path that is none at the control of each of the two', $status === 400 && array_keys( $body['about'] ) === [ 'ownertpl', 'usertpl' ] );

[ $status, $body ] = callFormsAdmin( $appData, 'forms/save', [ 'form' => [ 'key' => 'Not A Key' ] + $quote, 'key' => '' ] );
check( '...a key that is none at the key control', $status === 400 && array_keys( $body['about'] ) === [ 'key' ] );

[ $status, $body ] = callFormsAdmin( $appData, 'forms/save', [ 'form' => [ 'fields' => [] ] + $quote, 'key' => '' ] );
check( '...a form without a field, which has no control of its own to mark', $status === 400 && array_keys( $body['about'] ) === [ 'fields' ] && $body['fields'] === [] );

[ $status, $body ] = callFormsAdmin( $appData, 'forms/save', [ 'form' => [ 'ownerTemplate' => '/templates/mail-gone' ] + $quote, 'key' => '' ] );
check( 'a template that is well formed and not on disk is refused at its control, whatever else is right - an empty mail is worse than no form',
	$status === 400 && array_keys( $body['about'] ) === [ 'ownertpl' ] && \Nino\Filesystem::getFileContent( $appData, '/config.php', [] ) === $configBefore );

[ $status ] = callFormsAdmin( $appData, 'forms/save', [ 'form' => [ 'key' => 'extra', 'userTemplate' => '/templates/mail-quote' ] + $quote, 'key' => '' ] );
check( '...and one that is there, a project\'s own mail-* file, is not', $status === 200 );
callFormsAdmin( $appData, 'forms/delete', [ 'key' => 'extra' ] );

[ $status, $body ] = callFormsAdmin( $appData, 'forms/save', [ 'form' => $quote, 'key' => '' ] );
check( 'creating a second form under a key that is taken is refused rather than silently replacing it - at the key', $status === 400 && count( definedForms( $appData ) ) === 2 && array_keys( $body['about'] ) === [ 'key' ] );

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


// --- The typed fields, through the endpoint ------------------------------------

echo "Modules\\Forms - what a checkbox, a radio group, a date and an option written as a fill post\n";

$consent = [
	'key' => 'consent', 'name' => 'Consent', 'to' => 'sales@example.com', 'subject' => '', 'confirm' => false,
	'ownerTemplate' => '/templates/mail-owner', 'userTemplate' => '/templates/mail-user',
	'fields' => [
		[ 'name' => 'email',		'label' => 'Mail',		'type' => 'email',		'required' => true,		'options' => [] ],
		[ 'name' => 'privacy',	'label' => 'I agree',	'type' => 'checkbox',	'required' => true,		'options' => [] ],
		[ 'name' => 'plan',			'label' => 'Plan',		'type' => 'radio',		'required' => true,		'options' => [ '[[/x/opt]]', 'Large' ] ],
		[ 'name' => 'size',			'label' => 'Size',		'type' => 'select',		'required' => false,	'options' => [ '[[/x/opt]]', 'Large' ] ],
		[ 'name' => 'day',			'label' => 'Day',			'type' => 'date',			'required' => false,	'options' => [] ],
	],
];
[ $status ] = callFormsAdmin( $appData, 'forms/save', [ 'form' => $consent, 'key' => '' ] );
check( 'a form with all of them saves', $status === 200 );

$good = [ 'form' => 'consent', 'email' => 'jo@example.com', 'privacy' => '1', 'plan' => 'Large', 'day' => '2026-02-28' ];
$sent = [];
check( 'a ticked required checkbox, a ticked radio and a real date are accepted, and the answers reach the owner\'s mail through [[fields]]',
	submitForm( $appData, $good )['/nino/http/response']['statusCode'] === 200 && count( $sent ) === 1
	&& str_contains( $sent[0]['body'], '2026-02-28' ) && str_contains( $sent[0]['body'], 'Large' ) );
check( 'an unticked required checkbox is not', submitForm( $appData, [ 'privacy' => '' ] + $good )['/nino/http/response']['statusCode'] === 400 );
check( 'a radio value that is none of the options is not', submitForm( $appData, [ 'plan' => 'Medium' ] + $good )['/nino/http/response']['statusCode'] === 400 );
check( 'nor is a date that does not exist, or one written the other way round', submitForm( $appData, [ 'day' => '2026-02-30' ] + $good )['/nino/http/response']['statusCode'] === 400
	&& submitForm( $appData, [ 'day' => '28.02.2026' ] + $good )['/nino/http/response']['statusCode'] === 400 );
check( 'an option written as a fill key is accepted as the key it is stored as - the value the form draws, read back by the browser',
	submitForm( $appData, [ 'plan' => '[[/x/opt]]', 'size' => '[[/x/opt]]' ] + $good )['/nino/http/response']['statusCode'] === 200 );
callFormsAdmin( $appData, 'forms/delete', [ 'key' => 'consent' ] );

echo "\n";


// --- The words ---------------------------------------------------------------

echo "Forms\\Admin - the words the editor reads\n";

$words = [ 'en_US' => include $dir. '/text/en_US.php', 'de_DE' => include $dir. '/text/de_DE.php' ];
check( 'both interface languages carry the same fills', array_diff( array_keys( $words['en_US'] ), array_keys( $words['de_DE'] ) ) === [] && array_diff( array_keys( $words['de_DE'] ), array_keys( $words['en_US'] ) ) === [] );
foreach( $words as $locale => $fills ) {
	$missing = [];
	foreach( \Nino\Form::TYPES as $type )
		if( isset( $fills[ '[[/_admin/forms/type/'. $type. ']]' ] ) === false )
			$missing[] = $type;
	check( $locale. ' names every field type the kernel knows'. ( $missing === [] ? '' : ' - missing '. implode( ', ', $missing ) ), $missing === [] );

	// A sentence for every refusal the panel can answer with
	$codes = [ 'key', 'field', 'name', 'reserved', 'duplicate', 'type', 'options', 'fields', 'to', 'ownertpl', 'usertpl', 'missingtpl' ];
	$absent = array_values( array_filter( $codes, static fn( string $code ): bool => isset( $fills[ '[[/_admin/forms/problem/'. $code. ']]' ] ) === false ) );
	check( $locale. ' says every problem the engine can find, and the missing template'. ( $absent === [] ? '' : ' - missing '. implode( ', ', $absent ) ), $absent === [] && isset( $fills['[[/_admin/forms/error/fields]]'] ) === true );

	// A fill carries no token of its own: the script puts [[fields]] in where the hint asks, with %s
	check( $locale. ': no fill carries a token the fill engine would answer for', array_filter( $fills, static fn( string $text ): bool => str_contains( $text, '[[' ) === true ) === [] );
}

// One entry for each code the kernel's problems() answers, and the panel's
// sentence for it is the one in the list
$problemCodes = ( new ReflectionClassConstant( \Nino\Modules\Forms\Admin::class, 'PROBLEMS' ) )->getValue();
check( 'the panel has a sentence for each of the eleven codes problems() answers', array_keys( $problemCodes ) === [ 'key', 'field', 'name', 'reserved', 'duplicate', 'type', 'options', 'fields', 'to', 'ownerTemplate', 'userTemplate' ] );

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

/*	The panel script calls itself by name - Nino.admin.forms._renderList() and
	the rest - and a name it calls that it never defines is a TypeError the
	first time the shell reopens the panel: showCurrent() called _showList(),
	a method that has been _renderList() since the list and the form became
	two levels, and every return to the Forms panel threw instead of drawing
	the list. Every method the script calls on its own namespace is one it
	defines, read off the script rather than listed here	*/
$panelScript = (string) file_get_contents( __DIR__. '/../assets/admin.js' );
preg_match_all( '/Nino\.admin\.forms\.([_a-zA-Z0-9]+)\s*\(/', $panelScript, $calledMethods );
preg_match_all( '/^\t\t([_a-zA-Z0-9]+)\s*:\s*function/m', $panelScript, $definedMethods );
$undefinedCalls = array_values( array_unique( array_diff( $calledMethods[1], $definedMethods[1] ) ) );
check( 'every method the panel script calls on its own namespace is one it defines'. ( $undefinedCalls === [] ? '' : ' - '. implode( ', ', $undefinedCalls ) ), $definedMethods[1] !== [] && $undefinedCalls === [] );

echo "\n";


// --- The panel's script, where node is on the path ----------------------------
//
// forms-js-smoke.js beside this file draws the list, the settings card and the
// editor over a dom stand-in and counts the fixed action bars on each screen;
// this suite runs it too where node is on the path, the way redirects-smoke.php
// does, so bin/check.sh and CI cover both halves in one go
$jsTest	= __DIR__. '/forms-js-smoke.js';
$node		= function_exists( 'shell_exec' ) === true ? trim( (string) @shell_exec( 'command -v node 2>/dev/null' ) ) : '';

if( $node === '' || function_exists( 'exec' ) === false ) {
	echo "  --  - node is not available here: forms-js-smoke.js was NOT run\n";
} else {
	$output = []; $status = 1;
	exec( escapeshellarg( $node ). ' '. escapeshellarg( $jsTest ). ' 2>&1', $output, $status );
	$summary = (string) end( $output );
	check( 'forms-js-smoke.js passes - '. ( $summary === '' ? 'no output' : $summary ), $status === 0 );
}

ninoWarnings();
ninoDone( $appData );
