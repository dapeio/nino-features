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
 *										1, the builder that writes '/nino/form/forms' into
 *										config.php, the stylesheet that draws the kernel's form
 *										family from properties of its own (what it declares and
 *										where, what reads them, what it is set on and what it
 *										leaves alone), the four looks in design/ that the
 *										Design feature offers (read by their grammar and, where
 *										that feature is beside this one, as its own parser and
 *										compiler take them) and the block it brings to the
 *										specimen of that feature's preview, design/preview.tpl -
 *										plus the thing that matters most
 *										about a feature like this: that switching it on changes
 *										nothing about a submission that should go through, and
 *										switching it off leaves every form working - and, where
 *										node is on the path, forms-js-smoke.js beside it, the
 *										panel's script over a dom stand-in.
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

$appData = ninoSandbox( 'forms' );
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$appData['/nino/dir'] = '';

// The workbench's entry point, which ninoCall() hands a panel action
$panel = [ \Nino\Admin\Admin::class, 'handlePost' ];

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
 *	engine, on the kernel module's own callback. What it does beyond
 *	ninoVisit(), which builds the request and stops before a route is
 *	resolved: it sets the post and runs the callbacks of the endpoint's route
 */
function submitForm( array &$appData, array $post ): array {
	// The kernel's own per-ip mail cap - five an hour, and a submission it
	// refuses is a 429 - is not what is tested here: every submission
	// starts with a fresh window, so only the feature's guards answer
	\Nino\Feature::forget( $appData, 'ratelimit', '127.0.0.1' );
	$_POST = array_merge( [ 'location' => '' ], $post );
	$request = [ '/nino/http/response' => [ 'statusCode' => 200 ] ];
	\Nino\Callbacks::doCallbacks( $appData, '/nino/http/response/POST://.form', $request );
	return $request;
}

/** What the forms of config.php are right now, as the engine reads them */
function definedForms( array &$appData ): array {
	return array_column( \Nino\Form::forms( $appData ), 'key' );
}


/**
 *	The rules of a stylesheet, its comments left out: for each the at-rule it stands in ('' for none, '@media (pointer: coarse)'
 *	for one), its selector with the whitespace folded, and the text of its body. One level of at-rule is read, which is as deep
 *	as the stylesheets of this feature go
 *
 *	@param		string		$css
 *
 *	@return 	array<int, array{at: string, selector: string, body: string}>
 */
function formsRules( string $css ): array {

	$rules	= [];
	$read		= static function( string $text, string $at ) use ( &$read, &$rules ): void {

		$offset = 0;

		while( ( $open = strpos( $text, '{', $offset ) ) !== false ) {

			$depth	= 1;
			$close	= $open + 1;

			for( ; $close < strlen( $text ) && $depth > 0; $close++ )
				$depth += $text[$close] === '{' ? 1 : ( $text[$close] === '}' ? -1 : 0 );

			$selector	= trim( (string) preg_replace( '/\s+/', ' ', substr( $text, $offset, $open - $offset ) ) );
			$body			= substr( $text, $open + 1, $close - $open - 2 );

			if( str_starts_with( $selector, '@' ) === true )
				$read( $body, $selector );
			else
				$rules[] = [ 'at' => $at, 'selector' => $selector, 'body' => $body ];

			$offset = $close;
		}
	};

	$read( (string) preg_replace( '#/\*.*?\*/#s', '', $css ), '' );

	return $rules;
}

/**
 *	The declarations in the body of a rule, in the order they stand, the whitespace of each value folded
 *
 *	@param		string		$body
 *
 *	@return 	array<int, array{property: string, value: string}>
 */
function formsDeclarations( string $body ): array {

	preg_match_all( '/(?:^|;)\s*(-{0,2}[a-z][a-z0-9-]*)\s*:\s*([^;]*)/i', $body, $found, PREG_SET_ORDER );

	return array_map( static fn( array $match ): array => [ 'property' => $match[1], 'value' => trim( (string) preg_replace( '/\s+/', ' ', $match[2] ) ) ], $found );
}

/**
 *	A selector taken apart at its :not() groups: what stands outside them - what a rule is set on, since the classes it leaves out are
 *	none it touches - and what stands in each of them
 *
 *	@param		string		$selector
 *
 *	@return 	array{0: string, 1: array<int, string>}
 */
function formsNot( string $selector ): array {

	$inside = [];

	while( ( $start = strpos( $selector, ':not(' ) ) !== false ) {
		for( $end = $start + 4, $depth = 0; $end < strlen( $selector ); $end++ ) {
			$depth += $selector[$end] === '(' ? 1 : ( $selector[$end] === ')' ? -1 : 0 );

			if( $depth === 0 )
				break;
		}

		$inside[]		= substr( $selector, $start + 5, $end - $start - 5 );
		$selector		= substr( $selector, 0, $start ). substr( $selector, $end + 1 );
	}

	return [ $selector, $inside ];
}

/**
 *	What a selector is set on, as one chain: what stands outside its :not() groups (see formsNot()), the spaces round a combinator and
 *	between the compounds made one, so that two spellings of a chain compare as one
 *
 *	@param		string		$selector
 *
 *	@return 	string
 */
function formsChain( string $selector ): string {

	return trim( (string) preg_replace( [ '/\s+/', '/\s*>\s*/' ], [ ' ', ' > ' ], formsNot( $selector )[0] ) );
}

/**
 *	The selectors of a list, split at the commas that stand in no parenthesis and no bracket
 *
 *	@param		string		$selector
 *
 *	@return 	array<int, string>
 */
function formsItems( string $selector ): array {

	$items	= [];
	$depth	= 0;
	$from		= 0;

	for( $at = 0; $at < strlen( $selector ); $at++ ) {
		$depth += in_array( $selector[$at], [ '(', '[' ], true ) ? 1 : ( in_array( $selector[$at], [ ')', ']' ], true ) ? -1 : 0 );

		if( $selector[$at] === ',' && $depth === 0 ) {
			$items[]	= trim( substr( $selector, $from, $at - $from ) );
			$from			= $at + 1;
		}
	}

	$items[] = trim( substr( $selector, $from ) );

	return $items;
}

/**
 *	The colours a stylesheet writes as a literal in the value of a declaration: a hex colour, a colour function or the name of a
 *	colour. What a value reads of the kernel - var(--color-ink) - is a name of a property and none of these; transparent and
 *	color-mix() of the kernel's tokens are none either
 *
 *	@param		string		$css
 *
 *	@return 	array<int, string>				property: literal, for each
 */
function formsColourLiterals( string $css ): array {

	$names = explode( ' ', 'aliceblue antiquewhite aqua aquamarine azure beige bisque black blanchedalmond blue blueviolet brown burlywood cadetblue chartreuse chocolate coral cornflowerblue cornsilk crimson cyan darkblue darkcyan darkgoldenrod darkgray darkgreen darkgrey darkkhaki darkmagenta darkolivegreen darkorange darkorchid darkred darksalmon darkseagreen darkslateblue darkslategray darkslategrey darkturquoise darkviolet deeppink deepskyblue dimgray dimgrey dodgerblue firebrick floralwhite forestgreen fuchsia gainsboro ghostwhite gold goldenrod gray green greenyellow grey honeydew hotpink indianred indigo ivory khaki lavender lavenderblush lawngreen lemonchiffon lightblue lightcoral lightcyan lightgoldenrodyellow lightgray lightgreen lightgrey lightpink lightsalmon lightseagreen lightskyblue lightslategray lightslategrey lightsteelblue lightyellow lime limegreen linen magenta maroon mediumaquamarine mediumblue mediumorchid mediumpurple mediumseagreen mediumslateblue mediumspringgreen mediumturquoise mediumvioletred midnightblue mintcream mistyrose moccasin navajowhite navy oldlace olive olivedrab orange orangered orchid palegoldenrod palegreen paleturquoise palevioletred papayawhip peachpuff peru pink plum powderblue purple rebeccapurple red rosybrown royalblue saddlebrown salmon sandybrown seagreen seashell sienna silver skyblue slateblue slategray slategrey snow springgreen steelblue tan teal thistle tomato turquoise violet wheat white whitesmoke yellow yellowgreen' );
	$literal = '/#[0-9a-f]{3,8}\b|\b(?:rgba?|hsla?|hwb|lab|lch|oklab|oklch|color)\(|(?<![\w-])(?:'. implode( '|', $names ). ')(?![\w-])/i';
	$found = [];

	foreach( formsRules( $css ) as $rule )
		foreach( formsDeclarations( $rule['body'] ) as $declaration )
			if( preg_match( $literal, (string) preg_replace( '/--[a-z0-9-]+/i', '', $declaration['value'] ), $match ) === 1 )
				$found[] = $declaration['property']. ': '. $match[0];

	return $found;
}

/**
 *	A variant of the Design feature, read by its grammar: the tags of its head, the knobs and options they declare, and the
 *	rules and declarations after it. Nothing is compared with what a head says - that is the file's to say
 *
 *	@param		string		$file
 *
 *	@return 	array											tags (tag => texts), order (the tags in the order of the lines of the head), knobs (name => [ fields, kind, tab, label, default ] for each line), knobsDe (name => labels),
 *																		options and optionsDe (knob => step => labels), rules (as formsRules()), declared (property => [ selector, value ] for each)
 */
function formsLook( string $file ): array {

	$css	= (string) file_get_contents( $file );
	$tags	= [];
	$order	= [];

	preg_match( '#^\s*/\*(.*?)\*/#s', $css, $comment );

	foreach( (array) preg_split( '/\R/', $comment[1] ?? '' ) as $line )
		if( preg_match( '/^\s*\*?\s*(@[a-z]+(?:-de)?)\s+(\S.*?)\s*$/', (string) $line, $found ) === 1 ) {
			$tags[ $found[1] ][] = $found[2];
			$order[] = $found[1];
		}

	$look = [ 'tags' => $tags, 'order' => $order, 'knobs' => [], 'knobsDe' => [], 'options' => [], 'optionsDe' => [], 'rules' => formsRules( $css ), 'declared' => [] ];

	foreach( $tags['@knob'] ?? [] as $text ) {
		$fields = (array) preg_split( '/\s+/', $text, 4 );
		preg_match( '/^(.*?)(?:\s+=\s+(\S+))?$/', (string) ( $fields[3] ?? '' ), $label );
		$look['knobs'][ (string) $fields[0] ][] = [ 'fields' => count( $fields ), 'kind' => (string) ( $fields[1] ?? '' ), 'tab' => (string) ( $fields[2] ?? '' ), 'label' => (string) ( $label[1] ?? '' ), 'default' => $label[2] ?? null ];
	}

	foreach( $tags['@knob-de'] ?? [] as $text ) {
		$fields = (array) preg_split( '/\s+/', $text, 2 );
		$look['knobsDe'][ (string) $fields[0] ][] = (string) ( $fields[1] ?? '' );
	}

	foreach( [ '@option' => 'options', '@option-de' => 'optionsDe' ] as $tag => $into )
		foreach( $tags[$tag] ?? [] as $text ) {
			$fields = (array) preg_split( '/\s+/', $text, 3 );
			$look[$into][ (string) $fields[0] ][ (string) ( $fields[1] ?? '' ) ][] = (string) ( $fields[2] ?? '' );
		}

	foreach( $look['rules'] as $rule )
		foreach( formsDeclarations( $rule['body'] ) as $declaration )
			$look['declared'][ $declaration['property'] ][] = [ 'selector' => $rule['selector'], 'value' => $declaration['value'] ];

	return $look;
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
check( 'it is filed under communication', $manifest['category'] === 'communication' );
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

// No Components module in this sandbox: where the module owns [form] it registers it from
// the manifest, and where there is none init() does (the components section below)
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

/*	The stylesheet joins Nino.css in one bundle and draws the kernel's form family from properties of its own - the ones the Design
	feature moves, see the head of the file. Read as the rules it has, not as the comments that talk about them: what it declares
	and where, what reads it, and what it sets its rules on	*/
$uncommented	= static fn( string $file ): string => (string) preg_replace( '#/\*.*?\*/#s', '', (string) file_get_contents( $file ) );
$ninoCss			= $uncommented( $root. '/_nino/Nino.css' );
$stylesheet		= (string) file_get_contents( $dir. '/assets/forms.css' );
$baseCode			= $uncommented( $dir. '/assets/forms.css' );
$baseRules		= formsRules( $stylesheet );
$kernelStyles	= static fn( string $class ): bool => preg_match( '/\.'. preg_quote( $class, '/' ). '(?![\w-])/', $ninoCss ) === 1;

// Each --forms- property with the places it is declared in - the selector, and the at-rule where it stands in one - and the value
$baseDeclared = [];

foreach( $baseRules as $rule )
	foreach( formsDeclarations( $rule['body'] ) as $declaration )
		if( str_starts_with( $declaration['property'], '--forms-' ) === true )
			$baseDeclared[ $declaration['property'] ][] = [ 'place' => ( $rule['at'] === '' ? '' : $rule['at']. ' ' ). $rule['selector'], 'value' => $declaration['value'] ];

$misplaced = [];

foreach( $baseDeclared as $property => $declarations ) {
	$places = array_column( $declarations, 'place' );
	sort( $places );

	if( in_array( $places, $property === '--forms-floor' ? [ [ ':where(:root)', '@media (pointer: coarse) :where(:root)' ] ] : [ [ ':where(:root)' ], [ ':where(*)' ] ], true ) === false )
		$misplaced[] = $property. ' in '. implode( ' and ', $places );
}

$knobProperties	= [ '--forms-scale', '--forms-space', '--forms-corners', '--forms-line', '--forms-labels', '--forms-edge', '--forms-label' ];
$onRoot					= array_filter( $baseDeclared, static fn( array $declarations ): bool => array_filter( $declarations, static fn( array $declaration ): bool => $declaration['place'] === ':where(:root)' && str_contains( $declaration['value'], '--color-' ) === true ) !== [] );
$unread					= array_values( array_filter( array_keys( $baseDeclared ), static fn( string $property ): bool => preg_match( '/var\(\s*'. preg_quote( $property, '/' ). '\s*[,)]/', $baseCode ) !== 1 ) );
$literals				= formsColourLiterals( $stylesheet );

check( 'forms.css declares each --forms- property it reads in the one place of its kind and in no other: lengths, numbers and keywords in :where(:root), the floor of a field\'s size there and again under pointer: coarse, the colours in :where(*)'. ( $misplaced === [] ? '' : ' - '. implode( ', ', $misplaced ) ),
	$misplaced === [] && array_diff( $knobProperties, array_keys( $baseDeclared ) ) === [] );
check( '...a colour token is read in :where(*) alone - read on :root it would be the page\'s on every ground, and a field in a dark section would keep a light fill - and the two inks stand there'. ( $onRoot === [] ? '' : ' - '. implode( ', ', array_keys( $onRoot ) ) ),
	$onRoot === [] && ( $baseDeclared['--forms-edge'][0]['place'] ?? '' ) === ':where(*)' && ( $baseDeclared['--forms-label'][0]['place'] ?? '' ) === ':where(*)' );
check( '...each property it declares is read by a var() in a declaration, since one that nothing reads is a knob that moves nothing'. ( $unread === [] ? '' : ' - '. implode( ', ', $unread ) ), $unread === [] );
check( '...no colour is written as a literal - a hex colour, a function, a name - and nothing is declared !important'. ( $literals === [] ? '' : ' - '. implode( ', ', $literals ) ), $literals === [] && stripos( $baseCode, '!important' ) === false );

/*	What the rules are set on is what stands outside the :not() of a selector: the classes a rule leaves out are none it touches. The
	kernel's form family is the one thing of the kernel's that is styled; the feature's own classes are none the kernel styles	*/
$family	= [ 'nino-form', 'nino-form-input', 'nino-form-textarea', 'nino-form-select', 'nino-form-message', 'nino-is-error', 'nino-is-success' ];
$setOn	= [];

foreach( $baseRules as $rule ) {
	preg_match_all( '/\.(nino-[a-z0-9-]+)/', formsNot( $rule['selector'] )[0], $found );
	$setOn = array_merge( $setOn, $found[1] );
}

$setOn	= array_values( array_unique( $setOn ) );
$styled	= array_values( array_filter( $setOn, $kernelStyles ) );
$own		= array_values( array_filter( $setOn, static fn( string $class ): bool => str_starts_with( $class, 'nino-forms-' ) ) );

check( 'its rules are set on the kernel\'s form family - the form, its three kinds of field, the status line with its two states - and on no other class Nino.css styles, and on the feature\'s own classes, which the kernel styles none of'. ( array_diff( $styled, $family ) === [] ? '' : ' - '. implode( ', ', array_diff( $styled, $family ) ) ),
	array_diff( $styled, $family ) === [] && array_diff( $family, $setOn ) === [] && array_diff( [ 'nino-forms-check', 'nino-forms-group' ], $own ) === [] && array_filter( $own, $kernelStyles ) === [] );

$templated = [];

foreach( [ 'form-checkbox', 'form-radio', 'form-radio-option' ] as $name ) {
	preg_match_all( '/class="([^"]*)"/', (string) file_get_contents( $dir. '/templates/'. $name. '.tpl' ), $found );

	foreach( $found[1] as $list )
		$templated = array_merge( $templated, (array) preg_split( '/\s+/', trim( $list ) ) );
}

check( '...and no class the feature\'s own templates write is one Nino.css styles', $templated !== [] && array_filter( $templated, $kernelStyles ) === [] );

/*	Where a label stands is the block form's alone: the inline form and the newsletter's are flex boxes of their own, and a label that is
	the words of a check or one a screen reader alone reads is no label of a field	*/
$layout		= array_filter( $baseRules, static fn( array $rule ): bool => str_contains( $rule['body'], 'var(--forms-labels' ) === true
	&& array_filter( formsDeclarations( $rule['body'] ), static fn( array $declaration ): bool => str_starts_with( $declaration['property'], '--' ) === false ) !== [] );
$unscoped	= array_filter( $layout, static fn( array $rule ): bool => preg_match( '/:not\(\s*\.nino-form--inline\s*,\s*\.nino-newsletter-inline\s*\)/', $rule['selector'] ) !== 1 );
$labelled	= array_filter( $baseRules, static fn( array $rule ): bool => preg_match( '/(?<![\w-])label(?![\w-])(?!:not\(\s*\.nino-forms-check\s*,\s*\.nino-sr-only\s*\))/', $rule['selector'] ) === 1 );

check( 'the layout of the labels is scoped to the block form: every rule that reads where a label stands leaves out the inline form and the newsletter\'s'. ( $unscoped === [] ? '' : ' - '. implode( ' | ', array_column( $unscoped, 'selector' ) ) ),
	count( $layout ) >= 4 && $unscoped === [] );
check( '...and no rule names a label without leaving out the words of a check and the label a screen reader reads'. ( $labelled === [] ? '' : ' - '. implode( ' | ', array_column( $labelled, 'selector' ) ) ),
	$labelled === [] && array_filter( $baseRules, static fn( array $rule ): bool => preg_match( '/(?<![\w-])label(?![\w-])/', $rule['selector'] ) === 1 ) !== [] );

/*	The cut of a lifted label is a colour or, left initial by a variant, the fill of the field read where the label stands: a colour
	a variant writes for it is read on :root, the page's on every ground, and on a dark section the label would be a light box	*/
$lifted	= array_values( array_filter( $layout, static fn( array $rule ): bool => str_contains( $rule['body'], 'var(--forms-labels-lift)' ) === true ) );
$cut		= array_column( formsDeclarations( $lifted[0]['body'] ?? '' ), 'value', 'property' )['background-color'] ?? '';

check( '...and the cut of a lifted label is its own property with the fill of the field as its fallback - left initial by a variant, the label reads the fill where it stands, on a dark section the fill of that section',
	count( $lifted ) === 1 && preg_match( '/^var\(\s*--forms-labels-cut\s*,\s*var\(\s*--forms-fill\s*\)\s*\)$/', $cut ) === 1 );

/*	A field set under 1rem makes the browser of a phone zoom into it when it is focused: the size of a field is a max() of the floor
	and the scale, the floor is nothing on a pointer and 1rem under pointer: coarse	*/
$coarse	= array_values( array_filter( $baseRules, static fn( array $rule ): bool => $rule['at'] === '@media (pointer: coarse)' ) );
$sized	= array_values( array_filter( $baseRules, static fn( array $rule ): bool => str_contains( $rule['selector'], '.nino-form-input' ) === true && str_contains( $rule['body'], 'font-size' ) === true ) );
$size		= array_column( formsDeclarations( $sized[0]['body'] ?? '' ), 'value', 'property' )['font-size'] ?? '';

check( 'the floor of the size of a field is 1rem under pointer: coarse and nothing without it, and the size of a field is a max() of the floor and the scale',
	count( $coarse ) === 1 && $coarse[0]['selector'] === ':where(:root)' && formsDeclarations( $coarse[0]['body'] ) === [ [ 'property' => '--forms-floor', 'value' => '1rem' ] ]
	&& preg_match( '/^0(?:rem|px)?$/', (string) ( $baseDeclared['--forms-floor'][0]['value'] ?? '1rem' ) ) === 1
	&& preg_match( '/^max\(\s*var\(--forms-floor\)\s*,\s*calc\(\s*1rem\s*\*\s*var\(--forms-scale\)\s*\)\s*\)$/', $size ) === 1 );

/*	The paddings and the margin of a field give way to the spacing classes of Nino.css: a box that names a padding or a margin of its
	own is left alone, on whichever side. The stylesheet comes after Nino.css in the bundle and a rule of it has the specificity of those
	classes, so without the guard its rule - the later one - would win. Which classes reach a property is read off Nino.css, off the
	utilities it ends with: a family it adds is one the guard has to leave out. The select keeps the room of its arrow	*/
$utilities	= [];

foreach( formsRules( $ninoCss ) as $rule )
	if( $rule['at'] === '' && preg_match( '/^\.nino-([a-z]+)-(?:\d+|auto)$/', $rule['selector'], $found ) === 1 )
		foreach( formsDeclarations( $rule['body'] ) as $declaration )
			$utilities[ $found[1] ][] = $declaration['property'];

/*	What a utility sets that reaches a property of the field, for the shorthand and the four sides of a padding and of a margin: a
	shorthand reaches every side, a side is reached by the shorthand and by itself	*/
$reached		= [];

foreach( [ 'padding', 'margin' ] as $box ) {
	$reached[ $box ] = [ $box, $box. '-top', $box. '-right', $box. '-bottom', $box. '-left' ];

	foreach( [ 'top', 'right', 'bottom', 'left' ] as $side )
		$reached[ $box. '-'. $side ] = [ $box, $box. '-'. $side ];
}

$unguarded	= [];
$spaced			= [ 'padding' => 0, 'padding-right' => 0, 'margin-bottom' => 0 ];

foreach( $baseRules as $rule )
	foreach( formsDeclarations( $rule['body'] ) as $declaration )
		foreach( formsItems( $rule['selector'] ) as $item ) {
			if( isset( $reached[ $declaration['property'] ] ) === false || preg_match( '/\.nino-form-(?:input|textarea|select)/', $item ) !== 1 )
				continue;

			$spaced[ $declaration['property'] ] = ( $spaced[ $declaration['property'] ] ?? 0 ) + 1;
			$guard = implode( ' ', formsNot( $item )[1] );

			foreach( $utilities as $prefix => $properties )
				if( array_intersect( $properties, $reached[ $declaration['property'] ] ) !== [] && str_contains( $guard, '[class*="nino-'. $prefix. '-"]' ) === false )
					$unguarded[] = $declaration['property']. ' on '. $item. ' lets nino-'. $prefix. '- lose';
		}

check( 'a rule that sets a padding or a margin of a field, the shorthand or a side, leaves out the box that names its own spacing - every class of Nino.css that reaches the property, on whichever side - and the select keeps the room of its arrow'. ( $unguarded === [] ? '' : ' - '. implode( ', ', $unguarded ) ),
	$unguarded === [] && isset( $utilities['p'], $utilities['m'] ) === true && min( $spaced ) > 0 );

/*	A display of an author rule - read from a property or written as a word - beats the browser's own display: none for the hidden
	attribute, whatever its specificity. So for each element the stylesheet gives a display - the selector outside its :not() groups,
	the whole chain of it - a rule after it says display: none again for the element when it is hidden	*/
$displayed	= [];
$rehiding		= [];

foreach( $baseRules as $number => $rule ) {
	$display	= array_column( formsDeclarations( $rule['body'] ), 'value', 'property' )['display'] ?? '';
	$items		= formsItems( (string) preg_replace( '/^:where\((.*)\)$/s', '$1', $rule['selector'] ) );

	if( $display !== '' && $display !== 'none' )
		foreach( $items as $item )
			$displayed[] = [ $number, formsChain( $item ) ];
	elseif( $display === 'none' )
		$rehiding[$number] = $items;
}

$shown = [];

foreach( $displayed as [ $number, $subject ] )
	if( array_filter( $rehiding, static fn( array $items, int $at ): bool => $at > $number && array_filter( $items, static fn( string $item ): bool => str_ends_with( formsChain( $item ), $subject. '[hidden]' ) ) !== [], ARRAY_FILTER_USE_BOTH ) === [] )
		$shown[] = $subject;

check( 'a form, a label or a check that is hidden stays hidden: for each element the stylesheet gives a display, a rule after it says display: none for the element with the hidden attribute'. ( $shown === [] ? '' : ' - '. implode( ', ', $shown ) ),
	$shown === [] && count( $displayed ) >= 3 );

// The manifest's 'assets', which \Nino\init() registers ahead of every module's init(): the path
// that calls callModules( 'init' ) alone does not
\Nino\Features::registerAssets( $appData );
check( 'the stylesheet is bundled into the project\'s own /.cache/style.css', in_array( '/features/Forms/assets/forms.css', \Nino\Html::getAssets( $appData, '/.cache/style.css' ), true ) === true );

echo "\n";


// --- The builder -------------------------------------------------------------

echo "Forms\\Admin - the builder writes the key the kernel reads\n";

ninoUser( $appData, 'dev@example.com', [ \Nino\Modules\Forms\Admin::MANAGE_PERM ] );
\Nino\Admin\Admin::init( $appData );

// The mail templates a form names are files of the project, and a form that
// names one that is not there is refused: a project has the pair its install
// unit wrote, the header and footer they are wrapped in, and one of its own
foreach( [ 'mail-owner', 'mail-user', 'mail-header', 'mail-footer', 'mail-quote' ] as $mail )
	\Nino\Filesystem::putFileContent( $appData, '/templates/'. $mail. '.tpl', '<p>'. $mail. '</p>[[fields]]' );
ninoWarnings();

[ $status, $body ] = ninoCall( $appData, $panel, [], 'forms/list' );
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

[ $status ] = ninoCall( $appData, $panel, [ 'form' => $quote, 'key' => '' ], 'forms/save' );
check( 'a saved form lands in /nino/form/forms in config.php - the key the engine reads', $status === 200
	&& array_column( \Nino\Filesystem::getFileContent( $appData, '/config.php', [] )['/nino/form/forms'], 'key' ) === [ 'contact', 'quote' ] );
check( '...and the engine has it on the very next read, without the panel telling it anything', definedForms( $appData ) === [ 'contact', 'quote' ] );

[ $status ] = ninoCall( $appData, $panel, [ 'form' => [ 'key' => 'Not A Key', 'fields' => [] ], 'key' => '' ], 'forms/save' );
check( 'a definition the engine would refuse is refused here, by the engine\'s own normalize()', $status === 400 );

/*	What the engine would repair instead of keep is refused here, and the answer
	says where: the code forms_fields and, as its params, the index of the field in
	the posted list with the word of what is wrong, the control of that field, and
	the controls of the form itself with theirs. The words are looked up by the
	script, in the language of whoever is looking. Nothing is written - the
	config is what it was	*/
$configBefore = \Nino\Filesystem::getFileContent( $appData, '/config.php', [] );
$withField = static fn( array $field ): array => [ 'form' => [ 'fields' => [ $quote['fields'][0], $field ] ] + $quote, 'key' => '' ];
$field = static fn( string $name, string $type = 'text', array $options = [] ): array => [ 'name' => $name, 'label' => $name, 'type' => $type, 'required' => false, 'options' => $options ];

$answered = [];

foreach( [
	'a name with an umlaut'							=> [ $field( 'Straße' ), 'name', 'name' ],
	'a name that is a label'						=> [ $field( 'Ihre Nachricht' ), 'name', 'name' ],
	'a name the form keeps for itself'	=> [ $field( 'date' ), 'name', 'reserved' ],
	'a name another field has'					=> [ $field( 'email' ), 'name', 'duplicate' ],
	'radio buttons without an option'		=> [ $field( 'plan', 'radio' ), 'options', 'options' ],
	'a type nobody knows'								=> [ $field( 'plan', 'color' ), 'type', 'type' ],
] as $label => [ $bad, $control, $word ] ) {
	[ $status, $body ] = ninoCall( $appData, $panel, $withField( $bad ), 'forms/save' );
	$answered[ (string) ( $body['code'] ?? '' ) ] = true;
	check( $label. ' is refused at its field: 400, the code forms_fields, the word "'. $word. '" under index 1 and the control '. $control. ', nothing written', $status === 400
		&& $body['code'] === 'forms_fields' && is_string( $body['error'] ) === true
		&& $body['params'] === [ [ 1 => $word ], [ 1 => $control ], [] ] && \Nino\Filesystem::getFileContent( $appData, '/config.php', [] ) === $configBefore );
}

[ $status, $body ] = ninoCall( $appData, $panel, [ 'form' => [ 'to' => 'a@b' ] + $quote, 'key' => '' ], 'forms/save' );
check( 'a recipient that is no address is refused at the "to" control', $status === 400 && $body['code'] === 'forms_fields' && $body['params'] === [ [], [], [ 'to' => 'to' ] ] );

[ $status, $body ] = ninoCall( $appData, $panel, [ 'form' => [ 'ownerTemplate' => '/templates/../x', 'userTemplate' => 'nope' ] + $quote, 'key' => '' ], 'forms/save' );
check( '...a template path that is none at the control of each of the two', $status === 400 && $body['params'] === [ [], [], [ 'ownertpl' => 'ownertpl', 'usertpl' => 'usertpl' ] ] );

[ $status, $body ] = ninoCall( $appData, $panel, [ 'form' => [ 'key' => 'Not A Key' ] + $quote, 'key' => '' ], 'forms/save' );
check( '...a key that is none at the key control', $status === 400 && $body['params'] === [ [], [], [ 'key' => 'key' ] ] );

[ $status, $body ] = ninoCall( $appData, $panel, [ 'form' => [ 'fields' => [] ] + $quote, 'key' => '' ], 'forms/save' );
check( '...a form without a field, which has no control of its own to mark', $status === 400 && $body['params'] === [ [], [], [ 'fields' => 'fields' ] ] );

[ $status, $body ] = ninoCall( $appData, $panel, [ 'form' => [ 'ownerTemplate' => '/templates/mail-gone' ] + $quote, 'key' => '' ], 'forms/save' );
check( 'a template that is well formed and not on disk is refused at its control, whatever else is right - an empty mail is worse than no form',
	$status === 400 && $body['code'] === 'forms_fields' && $body['params'] === [ [], [], [ 'ownertpl' => 'missingtpl' ] ]
	&& \Nino\Filesystem::getFileContent( $appData, '/config.php', [] ) === $configBefore );

[ $status ] = ninoCall( $appData, $panel, [ 'form' => [ 'key' => 'extra', 'userTemplate' => '/templates/mail-quote' ] + $quote, 'key' => '' ], 'forms/save' );
check( '...and one that is there, a project\'s own mail-* file, is not', $status === 200 );
ninoCall( $appData, $panel, [ 'key' => 'extra' ], 'forms/delete' );

[ $status, $body ] = ninoCall( $appData, $panel, [ 'form' => $quote, 'key' => '' ], 'forms/save' );
$answered[ (string) ( $body['code'] ?? '' ) ] = true;
check( 'creating a second form under a key that is taken is refused rather than silently replacing it - with the code forms_duplicate, at the key', $status === 400 && count( definedForms( $appData ) ) === 2
	&& $body['code'] === 'forms_duplicate' && $body['params'] === [ [], [], [ 'key' => 'keytaken' ] ] );

[ $status ] = ninoCall( $appData, $panel, [ 'form' => [ 'key' => 'offer' ] + $quote, 'key' => 'quote' ], 'forms/save' );
check( 'a rename stays one form rather than becoming two', $status === 200 && definedForms( $appData ) === [ 'contact', 'offer' ] );

[ $status ] = ninoCall( $appData, $panel, [ 'key' => 'offer' ], 'forms/delete' );
check( 'a form is deleted by its key', $status === 200 && definedForms( $appData ) === [ 'contact' ] );

[ $status, $body ] = ninoCall( $appData, $panel, [ 'key' => 'contact' ], 'forms/delete' );
$answered[ (string) ( $body['code'] ?? '' ) ] = true;
check( 'the last one is not - a project with none falls back to the built-in form, which is a decision, not a delete: forms_last', $status === 400 && $body['code'] === 'forms_last' && definedForms( $appData ) === [ 'contact' ] );

[ $status, $body ] = ninoCall( $appData, $panel, [ 'key' => 'Not A Key' ], 'forms/delete' );
$answered[ (string) ( $body['code'] ?? '' ) ] = true;
check( 'a key that is none cannot be deleted either: forms_key', $status === 400 && $body['code'] === 'forms_key' && definedForms( $appData ) === [ 'contact' ] );

[ $status, $body ] = ninoCall( $appData, $panel, [ 'retention' => 12, 'store' => false ], 'forms/settings' );
check( 'the two submission settings are written as the kernel\'s own config keys', $status === 200
	&& \Nino\Form::retention( $appData ) === 12 && \Nino\Form::stores( $appData ) === false
	&& \Nino\Filesystem::getFileContent( $appData, '/config.php', [] )['/nino/form/retention'] === 12 );

[ $status, $body ] = ninoCall( $appData, $panel, [ 'retention' => 0, 'store' => true ], 'forms/settings' );
$answered[ (string) ( $body['code'] ?? '' ) ] = true;
check( 'a retention outside the window is refused with forms_retention, and nothing is written', $status === 400 && $body['code'] === 'forms_retention' && \Nino\Form::retention( $appData ) === 12 );
[ $status, $body ] = ninoCall( $appData, $panel, [ 'retention' => '12', 'store' => true ], 'forms/settings' );
check( '...a retention that arrives as text is none either', $status === 400 && $body['code'] === 'forms_retention' && \Nino\Form::retention( $appData ) === 12 );

ninoCall( $appData, $panel, [ 'retention' => 3, 'store' => true ], 'forms/settings' );

/*	config.php is written under a lock of its own. With the lock file made
	unopenable - a directory where it goes, the answer a read-only or full disk
	gives - nothing can be written, and that is the server's to report: a 500
	with its English sentence and no code, for the three actions that write the
	file. The copy of the app data is the request that failed; the real one is
	the project as the file still has it	*/
ninoCall( $appData, $panel, [ 'form' => $quote, 'key' => '' ], 'forms/save' );
$configLock = ninoSandboxDir( $appData ). '/private/data/.locks/'. sha1( '/config.php' ). '.lock';
$unwritable = static function( array $appData, string $action, array $data ) use ( $panel, $configLock ): array {
	unset( $appData['./nino/filesystem/locks'] );
	@unlink( $configLock );
	mkdir( $configLock, 0755, true );
	$answer = [];
	ninoErrors( static function() use ( &$appData, $panel, $data, $action, &$answer ): void {
		$answer = ninoCall( $appData, $panel, $data, $action );
	} );
	rmdir( $configLock );
	return $answer;
};
$configNow = \Nino\Filesystem::getFileContent( $appData, '/config.php', [] );

[ $status, $body ] = $unwritable( $appData, 'forms/save', [ 'form' => [ 'key' => 'offer' ] + $quote, 'key' => '' ] );
check( 'a save whose config.php cannot be written is a 500 with its English sentence and no code', $status === 500 && $body === [ 'error' => 'the forms could not be written' ] );
[ $status, $body ] = $unwritable( $appData, 'forms/delete', [ 'key' => 'quote' ] );
check( '...and so is a delete', $status === 500 && $body === [ 'error' => 'the forms could not be written' ] );
[ $status, $body ] = $unwritable( $appData, 'forms/settings', [ 'retention' => 5, 'store' => true ] );
check( '...and the two settings', $status === 500 && $body === [ 'error' => 'the settings could not be written' ] );
check( '...and the file is what it was', \Nino\Filesystem::getFileContent( $appData, '/config.php', [] ) === $configNow && definedForms( $appData ) === [ 'contact', 'quote' ] );
ninoCall( $appData, $panel, [ 'key' => 'quote' ], 'forms/delete' );

$appData['./nino/auth/current'] = null;
[ $status ] = ninoCall( $appData, $panel, [ 'form' => $quote, 'key' => '' ], 'forms/save' );
check( 'every action needs the panel\'s permission', $status === 401 );
\Nino\Auth::loginUser( $appData, 'dev@example.com', 'correct horse battery staple' );

echo "\n";


// --- The guards --------------------------------------------------------------

echo "Modules\\Forms - refusing a submission ahead of the engine\n";

ninoCall( $appData, $panel, [ 'form' => $quote, 'key' => '' ], 'forms/save' );

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
// submission that was actually accepted costs a slot. The counter is the
// kernel's, \Nino\Feature::count() under the name "forms-rate": the file
// /data/forms-rate.php it always was, one entry for the sha256 of the address
$counterFile	= '/data/forms-rate.php';
$ipKey				= hash( 'sha256', '127.0.0.1' );
\Nino\Features::saveSettings( $appData, 'forms', [ 'minSeconds' => 0, 'rateLimit' => 2, 'blocklist' => [] ] );
\Nino\Filesystem::putFileContent( $appData, $counterFile, [] );

check( 'the first two submissions of the hour are accepted', submitForm( $appData, $valid )['/nino/http/response']['statusCode'] === 200
	&& submitForm( $appData, $valid )['/nino/http/response']['statusCode'] === 200 );

$sent = [];
$request = submitForm( $appData, $valid );
check( 'the third is turned away with a 429 - the one refusal a person can do something about', $request['/nino/http/response']['statusCode'] === 429 && $sent === [] );

$state = \Nino\Filesystem::getFileContent( $appData, $counterFile, [] );
check( 'the counter is keyed by a hash of the address, not by the address: its sha256, in the file it always was', array_keys( $state ) === [ $ipKey ]
	&& \Nino\Feature::spent( $appData, 'forms-rate', '127.0.0.1', 2 ) === true && \Nino\Feature::spent( $appData, 'forms-rate', '127.0.0.2', 2 ) === false );
// The kernel's own rule counts every try, the one past the limit too; this
// counter is called after the engine accepted a submission, so a turned-away one is never a try
check( '...with the two accepted submissions on it and not the turned-away one, for the hour', $state[$ipKey]['tries'] === 2 && $state[$ipKey]['reset'] > time() + 3500 && $state[$ipKey]['reset'] <= time() + 3600 );

// An entry written by the counter this feature had before the kernel's: the same
// file, the same key, the same two numbers
\Nino\Filesystem::putFileContent( $appData, $counterFile, [ $ipKey => [ 'tries' => 2, 'reset' => time() + 3600 ] ] );
check( 'an entry written before the kernel counted for it is read as it is: the allowance is used up', submitForm( $appData, $valid )['/nino/http/response']['statusCode'] === 429 );

\Nino\Filesystem::putFileContent( $appData, $counterFile, [ $ipKey => [ 'tries' => 2, 'reset' => time() - 1 ], 'lapsed' => [ 'tries' => 9, 'reset' => time() - 5 ] ] );
check( 'an entry whose hour is over lets the visitor in again', submitForm( $appData, $valid )['/nino/http/response']['statusCode'] === 200 );
$state = \Nino\Filesystem::getFileContent( $appData, $counterFile, [] );
check( '...and the next accepted submission starts a new hour, taking the entries that lapsed with it', array_keys( $state ) === [ $ipKey ] && $state[$ipKey]['tries'] === 1 );

// A refused submission must not spend a slot: reset, then send two the engine
// rejects and check the allowance is still whole
\Nino\Filesystem::putFileContent( $appData, $counterFile, [] );
submitForm( $appData, [ 'form' => 'quote', 'email' => 'not-an-email' ] );
submitForm( $appData, [ 'form' => 'quote', 'email' => 'still-not-one' ] );
check( 'a submission the engine refused costs nothing - a visitor who mistypes their address has not submitted', \Nino\Filesystem::getFileContent( $appData, $counterFile, [] ) === []
	&& submitForm( $appData, $valid )['/nino/http/response']['statusCode'] === 200
	&& submitForm( $appData, $valid )['/nino/http/response']['statusCode'] === 200 );

\Nino\Features::saveSettings( $appData, 'forms', [ 'minSeconds' => 0, 'rateLimit' => 0, 'blocklist' => [] ] );
\Nino\Filesystem::putFileContent( $appData, $counterFile, [] );
check( 'with the limit at 0 an accepted submission counts nothing and writes nothing', submitForm( $appData, $valid )['/nino/http/response']['statusCode'] === 200
	&& \Nino\Filesystem::getFileContent( $appData, $counterFile, [] ) === [] );

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
[ $status ] = ninoCall( $appData, $panel, [ 'form' => $consent, 'key' => '' ], 'forms/save' );
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
ninoCall( $appData, $panel, [ 'key' => 'consent' ], 'forms/delete' );

echo "\n";


// --- The words ---------------------------------------------------------------

echo "Forms\\Admin - the words the editor reads\n";

$words = [ 'en_US' => include $dir. '/text/en_US.php', 'de_DE' => include $dir. '/text/de_DE.php' ];

// One entry for each code the kernel's problems() answers, and the word the panel names for it;
// besides them the words of the answers that are not a problem of the definition, and of the
// fallback for a code the panel does not know
$problemCodes	= ( new ReflectionClassConstant( \Nino\Modules\Forms\Admin::class, 'PROBLEMS' ) )->getValue();
$problemWords	= array_values( array_unique( array_merge( array_values( $problemCodes ), [ 'keytaken', 'missingtpl', 'form' ] ) ) );

// The codes the panel answers with: the five this test met above, and forms_invalid, which only a
// definition that problems() lets through and normalize() refuses could reach
$panelCodes = [ 'forms_fields', 'forms_duplicate', 'forms_key', 'forms_last', 'forms_retention', 'forms_invalid' ];
check( 'every refusal the panel can give a person was asked for above, but forms_invalid', array_diff( $panelCodes, array_keys( $answered ), [ 'forms_invalid' ] ) === [] && array_diff( array_keys( $answered ), $panelCodes ) === [] );
check( 'both interface languages carry the same fills', array_diff( array_keys( $words['en_US'] ), array_keys( $words['de_DE'] ) ) === [] && array_diff( array_keys( $words['de_DE'] ), array_keys( $words['en_US'] ) ) === [] );
/*	A word the script asks for by its whole key - the labels and hints it hands to its
	helpers as well - is one both languages say: a key that is mistyped in the script
	reads as itself on screen. One that ends in a slash is the start of a key it puts
	together, and is none	*/
preg_match_all( "~'(/_admin/forms/[a-z0-9/-]*[a-z0-9])'~", (string) file_get_contents( $dir. '/assets/admin.js' ), $quoted );
$asked = array_values( array_unique( $quoted[1] ) );
$listWords = array_map( static fn( string $name ): string => '/_admin/forms/options/'. $name, [ 'add', 'remove', 'up', 'down', 'empty', 'item' ] );
foreach( $words as $locale => $fills ) {
	$missing = [];
	foreach( \Nino\Form::TYPES as $type )
		if( isset( $fills[ '[[/_admin/forms/type/'. $type. ']]' ] ) === false )
			$missing[] = $type;
	check( $locale. ' names every field type the kernel knows'. ( $missing === [] ? '' : ' - missing '. implode( ', ', $missing ) ), $missing === [] );

	// A sentence for every word a refused save names, to put under the field or the control
	$absent = array_values( array_filter( $problemWords, static fn( string $word ): bool => trim( $fills[ '[[/_admin/forms/problem/'. $word. ']]' ] ?? '' ) === '' ) );
	check( $locale. ' says every problem the engine can find, the key that is taken, the missing template and the form it cannot save'. ( $absent === [] ? '' : ' - missing '. implode( ', ', $absent ) ), $absent === [] );

	// ...and one for every code, in the place the workbench looks the code up
	$unsaid = array_values( array_filter( $panelCodes, static fn( string $code ): bool => trim( $fills[ '[[/_admin/error/'. $code. ']]' ] ?? '' ) === '' ) );
	check( $locale. ' has the sentence of every code the panel answers with'. ( $unsaid === [] ? '' : ' - missing '. implode( ', ', $unsaid ) ), $unsaid === [] );

	$unasked = array_values( array_filter( $asked, static fn( string $key ): bool => isset( $fills[ '[['. $key. ']]' ] ) === false ) );
	check( $locale. ' says every word the panel script asks for, the six of the list of options among them'. ( $unasked === [] ? '' : ' - missing '. implode( ', ', $unasked ) ), $unasked === [] && array_diff( $listWords, $asked ) === [] );

	// A row of that list is named for a screen reader by its number
	check( $locale. ' names a row of the list of options by its number', str_contains( (string) ( $fills['[[/_admin/forms/options/item]]'] ?? '' ), '%d' ) === true );

	// A fill carries no token of its own: the script puts [[fields]] in where the hint asks, with %s
	check( $locale. ': no fill carries a token the fill engine would answer for', array_filter( $fills, static fn( string $text ): bool => str_contains( $text, '[[' ) === true ) === [] );
}

check( 'the panel has a word for each of the eleven codes problems() answers', array_keys( $problemCodes ) === [ 'key', 'field', 'name', 'reserved', 'duplicate', 'type', 'options', 'fields', 'to', 'ownerTemplate', 'userTemplate' ] );

// A code the panel has no word for is said with the general word of its kind: a field that cannot
// be read, or a form that cannot be saved as it is
$unknownCode = new ReflectionMethod( \Nino\Modules\Forms\Admin::class, '_refuse' );
$unknownAnswer = [ '/nino/http/response' => [ 'statusCode' => 200 ] ];
$unknownCode->invokeArgs( null, [ &$unknownAnswer, [ [ 'field' => 2, 'code' => 'zzz' ], [ 'field' => null, 'code' => 'zzz' ] ] ] );
check( '...so a code of a later kernel is still answered with a sentence', $unknownAnswer['/nino/http/response']['statusCode'] === 400 && $unknownAnswer['/nino/http/response']['body']['code'] === 'forms_fields'
	&& $unknownAnswer['/nino/http/response']['body']['params'] === [ [ 2 => 'field' ], [ 2 => 'name' ], [ 'zzz' => 'form' ] ] );

echo "\n";


// --- Components ----------------------------------------------------------------

echo "Components - [form] as the Builder offers it\n";

( static function( array $appData ): void {

	$declared = \Nino\Features::manifest( dirname( __DIR__ ) )['components'] ?? [];

	// Every documented call form, as it renders before the wrapper is there
	$calls = [
		'[form]',
		'[form key="quote"]',
		'[form key="contact"]',
		'[form key="nowhere"]',
		'[form key="Not A Slug"]',
	];

	$before = [];
	foreach( $calls as $call )
		$before[$call] = preg_replace( '/name="_t" value="\d{10}"/', 'name="_t" value="0"', \Nino\Html::renderHtml( $appData, $call ) );

	/*	The kernel reads the manifest ahead of the feature's own init() - that is
		the order here too, on a copy of the project's data that nothing else in
		this test sees. The shortcodes the feature registered above are those of
		a boot that has none to read; this boot registers each of them once	*/
	$probe = $appData;
	foreach( [ 'form' ] as $shortcode )
		\Nino\Callbacks::removeCallbacks( $probe, '/nino/html/shortcode/'. $shortcode );
	\Nino\Modules\Components::init( $probe );
	\Nino\Modules\Forms::init( $probe );
	ninoWarnings();

	/*	The schema as the manifest declares it, but for the one attribute whose options are a method: the registry hands out the answer in
		the place of the name, the values as 'options' and the words as 'optionLabels' (see the next section)	*/
	$issued = \Nino\Modules\Components::components( $probe )['form'] ?? [];
	unset( $issued['attributes']['key']['options'], $issued['attributes']['key']['optionLabels'] );
	$wanted = $declared['form'] ?? false;

	if( is_array( $wanted ) === true )
		unset( $wanted['attributes']['key']['options'] );

	check( 'the component is registered with the schema the manifest declares', $issued === $wanted );
	check( '...as one meant once per page: the registered schema says it stays out of a loop',
		( \Nino\Modules\Components::components( $probe )['form']['loop'] ?? null ) === false );
	check( '...and what the feature registers is answered once - the component by its wrapper',
		\Nino\Features::shortcodes( $probe, 'forms' ) === [ 'form' ]
		&& count( $registered = \Nino\Callbacks::registered( $probe, '/nino/html/shortcode/form' ) ) === 1 && $registered[0] instanceof \Closure );
	check( '...with the defaults of the schema, as strings',
		\Nino\Modules\Components::defaults( $probe, 'form' ) === [ 'key' => '', 'class' => '' ] );

	/*	A key that names no form is the empty word where the Components module makes the call ready - its attribute is a select of the
		forms there are - and the call draws the first form, where the plain shortcode drew nothing	*/
	foreach( $calls as $call )
		check( 'renders as it did: '. $call. ( $call === '[form key="nowhere"]' ? ' - but for the key no form has, which is the first form now' : '' ),
			preg_replace( '/name="_t" value="\d{10}"/', 'name="_t" value="0"', \Nino\Html::renderHtml( $probe, $call ) ) === ( $call === '[form key="nowhere"]' ? $before['[form]'] : $before[$call] ) );

	ninoWarnings();

	check( 'every attribute written out at its default is the call without it',
		preg_replace( '/name="_t" value="\d{10}"/', 'name="_t" value="0"', \Nino\Html::renderHtml( $probe, '[form key="" class=""]' ) ) === $before['[form]'] );
	check( 'a class of its own comes after the form\'s own, no class leaves no space behind',
		str_contains( \Nino\Html::renderHtml( $probe, '[form key="quote" class="wide"]' ), '<form class="nino-form wide" id="form-quote"' ) === true
		&& str_contains( $before['[form key="quote"]'], '<form class="nino-form" id="form-quote"' ) === true );

} )( $appData );

echo "\n";


// --- The forms the Builder offers --------------------------------------------

echo "Components - the forms there are, as the choice of [form]\n";

( static function( array $appData ): void {

	$form		= static fn( string $key, string $name ): array => [ 'key' => $key, 'name' => $name, 'fields' => [ [ 'name' => 'who', 'label' => 'Who', 'type' => 'text', 'required' => false, 'options' => [] ] ] ];
	$locales	= \Nino\Locales::getAvailableLocales( $appData );

	// Three forms, in an order that is neither the alphabet's nor the keys': the order of the panel is the order of the list
	$several = $appData;
	$several[ \Nino\Form::FORMS ] = [ $form( 'news', 'Rundbrief' ), $form( 'contact', 'Kontakt' ), $form( 'quote', 'Angebot' ) ];

	$offered = \Nino\Modules\Forms::options( $several, 'key' );

	check( 'options(): the empty word first - the first form - and then each form by its key with its name, in the order the Forms panel lists them',
		array_keys( $offered ) === [ '', 'news', 'contact', 'quote' ] && array_slice( $offered, 1 ) === [ 'news' => 'Rundbrief', 'contact' => 'Kontakt', 'quote' => 'Angebot' ] );

	$first = $offered[''];

	check( '...the empty word has words in every language of the site, as a locale => string map the kernel reads (\Nino\Features::localized()), none of them empty',
		is_array( $first ) === true && array_keys( $first ) === array_map( 'strval', $locales ) && $locales !== [] && array_filter( $first, static fn( mixed $word ): bool => is_string( $word ) === false || trim( $word ) === '' ) === [] );
	check( '...and the words are the feature\'s own text, one file for each language, the key of the empty word in both',
		array_filter( glob( dirname( __DIR__ ). '/text/*.php' ) ?: [], static fn( string $file ): bool => is_string( ( require $file )[ \Nino\Modules\Forms::FIRST ] ?? null ) === false ) === [] );

	$none = $appData;
	unset( $none[ \Nino\Form::FORMS ] );

	check( '...where the project has defined no form it is the empty word and the contact form the kernel falls back to, which [form key="contact"] draws and the panel lists',
		array_keys( \Nino\Modules\Forms::options( $none, 'key' ) ) === [ '', 'contact' ] && str_contains( \Nino\Html::renderHtml( $none, '[form key="contact"]' ), 'id="form-contact"' ) === true );

	$mixed = $appData;
	$mixed[ \Nino\Form::FORMS ] = [ $form( 'quote', 'Angebot' ), [ 'key' => 'broken', 'fields' => [] ], $form( 'news', '' ) ];
	check( '...a definition the kernel does not read is not offered, and a form without a name is offered by its key', array_slice( \Nino\Modules\Forms::options( $mixed, 'key' ), 1 ) === [ 'quote' => 'Angebot', 'news' => 'news' ] );

	// The registry the Components module hands a tool: the values, and the words beside them
	$probe = $several;
	foreach( [ 'form' ] as $shortcode )
		\Nino\Callbacks::removeCallbacks( $probe, '/nino/html/shortcode/'. $shortcode );
	\Nino\Modules\Components::init( $probe );
	\Nino\Modules\Forms::init( $probe );
	ninoWarnings();

	$key = \Nino\Modules\Components::components( $probe )['form']['attributes']['key'] ?? [];

	check( 'the registry hands out the key of [form] as a select of the forms there are: the values as the options, the words by value as optionLabels, the empty word first',
		( $key['type'] ?? null ) === 'select' && ( $key['options'] ?? null ) === [ '', 'news', 'contact', 'quote' ] && array_keys( $key['optionLabels'] ?? [] ) === $key['options']
		&& array_slice( $key['optionLabels'], 1 ) === [ 'news' => 'Rundbrief', 'contact' => 'Kontakt', 'quote' => 'Angebot' ] && $key['optionLabels'][''] === $first && ( $key['default'] ?? null ) === '' );
	check( '...the name of the method is not handed out, and asking was no warning', array_filter( $key, static fn( mixed $value ): bool => is_string( $value ) === true && str_contains( $value, '::' ) ) === [] && ninoWarnings() === [] );

	// A form that was deleted: its key is a value outside the list, which the Components module makes the default - and the default draws the first form
	$strip = static fn( string $html ): string => preg_replace( '/name="_t" value="\d{10}"/', 'name="_t" value="0"', $html );

	check( 'a call with the key of a form there is not draws the first form, as a call without a key does',
		str_contains( \Nino\Html::renderHtml( $probe, '[form key="gone"]' ), 'id="form-news"' ) === true
		&& $strip( \Nino\Html::renderHtml( $probe, '[form key="gone"]' ) ) === $strip( \Nino\Html::renderHtml( $probe, '[form]' ) ) );
	check( '...and a call with the key of a form there is draws that one', str_contains( \Nino\Html::renderHtml( $probe, '[form key="quote"]' ), 'id="form-quote"' ) === true );

} )( $appData );

echo "\n";


// --- The look ----------------------------------------------------------------

echo "Forms - the look of the kernel's form family: the variants the Design feature offers\n";

/*	Four stylesheets in design/, each a variant of the feature for the Design feature (its manual, under Base, says what a variant
	is): a head of lines "@tag words" and, after it, nothing but :root declarations. The base stylesheet owns every rule, the variants
	move what it reads. Read here by their grammar - which tags, which fields, which names - and what a head says is never compared	*/
$looks = [];

foreach( glob( $dir. '/design/*.css' ) ?: [] as $file )
	$looks[ basename( $file, '.css' ) ] = formsLook( $file );

ksort( $looks );

$kinds			= [ 'scale', 'choice', 'ground', 'ink', 'colour' ];
$tabs				= [ 'type', 'surfaces', 'colours', 'motion' ];
$inkWords		= [ 'ink', 'subtitle', 'title', 'accent', 'primary', 'border', 'focus' ];
$stepsOf		= [ 'scale' => [ 'least', 'less', 'default', 'more', 'most' ], 'choice' => [ 'less', 'default', 'more' ] ];
$knobTable	= [ 'scale' => [ 'scale', 'type' ], 'space' => [ 'scale', 'surfaces' ], 'corners' => [ 'scale', 'surfaces' ], 'line' => [ 'scale', 'surfaces' ], 'labels' => [ 'choice', 'surfaces' ], 'edge' => [ 'ink', 'colours' ], 'label' => [ 'ink', 'colours' ] ];
$stepSuffix	= '/--(?:least|less|default|more|most)$/';

/** The ladders a variant declares for its knobs: the property each rung is named after, without its step - a choice carries one for every aspect the base declares of it, besides its own */
$stemsOf = static function( array $look ) use ( $baseDeclared, $stepsOf ): array {

	$stems = [];

	foreach( $look['knobs'] as $name => $lines )
		if( isset( $stepsOf[ $lines[0]['kind'] ] ) === true )
			$stems[ $name ] = $lines[0]['kind'] === 'choice'
				? array_merge( [ '--forms-'. $name ], array_values( array_filter( array_keys( $baseDeclared ), static fn( string $property ): bool => str_starts_with( $property, '--forms-'. $name. '-' ) ) ) )
				: [ '--forms-'. $name ];

	return $stems;
};

check( 'the feature brings four variants of the Design feature, a stylesheet each in design/ - and regular, which is the feature as it stands, is one of them', array_keys( $looks ) === [ 'contrast', 'filled', 'line', 'regular' ] );

$headless = [];

foreach( $looks as $variant => $look )
	foreach( [ '@name', '@name-de', '@description', '@description-de' ] as $tag )
		if( count( $look['tags'][$tag] ?? [] ) !== 1 )
			$headless[] = $variant. ' '. $tag;

check( 'each head names and describes its variant in both languages, once each: @name, @name-de, @description, @description-de'. ( $headless === [] ? '' : ' - '. implode( ', ', $headless ) ), $headless === [] && $looks !== [] );

// The block the feature brings to the specimen of the Design preview: the form, with its messages and its line, in a grid row of its own - what the section of the specimen was
$blockSource 	= (string) @file_get_contents( $dir. '/design/preview.tpl' );
$blockIds 		= [];
preg_match_all( '/<[a-z][^>]*>/i', $blockSource, $blockTagFound );

foreach( $blockTagFound[0] as $blockTag )
	if( preg_match( '/\sid="([^"]*)"/', $blockTag, $blockIdFound ) === 1 )
		$blockIds[] = $blockIdFound[1];

$blockLabels = [];
preg_match_all( '/<label for="([^"]+)"/', $blockSource, $blockForFound );

foreach( $blockForFound[1] as $blockFor )
	if( in_array( $blockFor, $blockIds, true ) === false )
		$blockLabels[] = $blockFor;

check( 'the feature brings a block to the specimen, design/preview.tpl: one grid row, every id in it forms-something, no section of its own and no inline handler, and each label points at a field of the block',
	str_starts_with( trim( $blockSource ), '<div class="nino-grid-row">' ) === true && str_ends_with( trim( $blockSource ), '</div>' ) === true && substr_count( $blockSource, 'class="nino-grid-row"' ) === 1
	&& $blockIds !== [] && array_filter( $blockIds, static fn( string $id ): bool => str_starts_with( $id, 'forms-' ) === false ) === [] && count( $blockIds ) === count( array_unique( $blockIds ) )
	&& preg_match( '/<section\b/i', $blockSource ) !== 1 && preg_match( '/\son[a-z]+\s*=/i', $blockSource ) !== 1 && $blockLabels === [] );

$blockMissing = [];

foreach( [ 'class="nino-form"', 'class="nino-form-input"', 'class="nino-form-select"', 'class="nino-form-textarea"', 'type="checkbox"', 'type="radio"', '<fieldset class="nino-forms-group">', '<legend>',
		'class="nino-form-message"', 'nino-form nino-is-error', 'nino-form nino-is-success', 'nino-form nino-form--inline', 'nino-form-submit', '<h2 class="nino-section-title' ] as $blockNeedle )
	if( str_contains( $blockSource, $blockNeedle ) === false )
		$blockMissing[] = $blockNeedle;

check( '...with the fields the looks are seen on: the text, the e-mail, the select, the textarea, the checkbox, a group of radio buttons under a legend, the messages of an error and of a success, the form in one line and the send button, headed as the section was'. ( $blockMissing === [] ? '' : ' - missing '. implode( ', ', $blockMissing ) ),
	$blockMissing === [] && substr_count( $blockSource, 'type="radio"' ) >= 2 );

$knobProblems = [];

foreach( $looks as $variant => $look ) {
	foreach( $look['knobs'] as $name => $lines ) {
		$line = $lines[0];

		if( count( $lines ) !== 1 || $line['fields'] !== 4 || in_array( $line['kind'], $kinds, true ) === false || in_array( $line['tab'], $tabs, true ) === false || $line['label'] === '' )
			$knobProblems[] = $variant. ' @knob '. $name;

		if( count( $look['knobsDe'][$name] ?? [] ) !== 1 || trim( $look['knobsDe'][$name][0] ) === '' )
			$knobProblems[] = $variant. ' @knob-de '. $name;
	}

	foreach( array_diff( array_keys( $look['knobsDe'] ), array_keys( $look['knobs'] ) ) as $name )
		$knobProblems[] = $variant. ' @knob-de '. $name. ' names no knob';
}

$usedKinds	= [];
$usedTabs		= [];

foreach( $looks as $look )
	foreach( $look['knobs'] as $lines ) {
		$usedKinds[]	= $lines[0]['kind'];
		$usedTabs[]		= $lines[0]['tab'];
	}

check( 'each @knob line has its five fields - the tag, the name, a kind, a tab, the words - once, and a @knob-de beside it'. ( $knobProblems === [] ? '' : ' - '. implode( ', ', $knobProblems ) ), $knobProblems === [] );
check( '...and the kinds are a scale, a choice and an ink, on the tabs Type, Surfaces and Colours: no ground, no colour pair, no motion', $usedKinds !== [] && array_diff( $usedKinds, [ 'scale', 'choice', 'ink' ] ) === [] && array_diff( $usedTabs, [ 'type', 'surfaces', 'colours' ] ) === [] );

$ladderProblems = [];

foreach( $looks as $variant => $look ) {
	$stems = [];

	foreach( $stemsOf( $look ) as $name => $list )
		foreach( $list as $stem ) {
			$stems[] = $stem;

			foreach( $stepsOf[ $look['knobs'][$name][0]['kind'] ] as $step )
				if( isset( $look['declared'][ $stem. '--'. $step ] ) === false )
					$ladderProblems[] = $variant. ' lacks '. $stem. '--'. $step;
		}

	foreach( array_keys( $look['declared'] ) as $property )
		if( preg_match( $stepSuffix, $property ) === 1 && in_array( (string) preg_replace( $stepSuffix, '', $property ), $stems, true ) === false )
			$ladderProblems[] = $variant. ' has a ladder no knob of its head moves: '. $property;
}

check( 'a scale declares its five rungs in the file and a choice its three - the choice for every aspect of where a label stands the base declares, its own gap among them - and a ladder belongs to a knob the head declares'. ( $ladderProblems === [] ? '' : ' - '. implode( ', ', $ladderProblems ) ), $ladderProblems === [] );

$optionProblems = [];

foreach( $looks as $variant => $look ) {
	foreach( [ 'options', 'optionsDe' ] as $field ) {
		foreach( $look['knobs'] as $name => $lines )
			if( $lines[0]['kind'] === 'choice' )
				foreach( $stepsOf['choice'] as $step )
					if( count( $look[$field][$name][$step] ?? [] ) !== 1 || trim( $look[$field][$name][$step][0] ) === '' )
						$optionProblems[] = $variant. ' '. $field. ' '. $name. ' '. $step;

		foreach( array_keys( $look[$field] ) as $name )
			if( ( $look['knobs'][$name][0]['kind'] ?? '' ) !== 'choice' )
				$optionProblems[] = $variant. ' '. $field. ' '. $name. ' is no choice';
	}

	foreach( $look['knobs'] as $name => $lines )
		if( ( $lines[0]['kind'] === 'ink' ) !== ( $lines[0]['default'] !== null ) || ( $lines[0]['default'] !== null && in_array( $lines[0]['default'], $inkWords, true ) === false ) )
			$optionProblems[] = $variant. ' default of '. $name;
}

check( 'a choice has an @option and an @option-de for each of its three steps, and an ink a default that is one of the seven tones of the kernel\'s tokens'. ( $optionProblems === [] ? '' : ' - '. implode( ', ', $optionProblems ) ), $optionProblems === [] );

$kindsOf = [];

foreach( $looks as $variant => $look )
	foreach( $look['knobs'] as $name => $lines )
		$kindsOf[$name][ $lines[0]['kind']. ' '. $lines[0]['tab'] ] = true;

check( 'the same knob is of the same kind and on the same tab in every variant that declares it', $kindsOf !== [] && array_filter( $kindsOf, static fn( array $seen ): bool => count( $seen ) !== 1 ) === [] );

/*	A variant writes no rule: its :root, which beats the defaults of the base wherever the two stand, and where the base declares a
	colour on every element (:where(*)), the :root, :root * the compiler writes the inks under. It moves what the base declares and
	invents no property, and nothing it says is a repetition of what the base says	*/
$ruleProblems = [];

foreach( $looks as $variant => $look ) {
	foreach( $look['rules'] as $rule ) {
		if( $rule['at'] !== '' || in_array( $rule['selector'], [ ':root', ':root, :root *' ], true ) === false ) {
			$ruleProblems[] = $variant. ' writes a rule on '. $rule['selector'];
			continue;
		}

		foreach( formsDeclarations( $rule['body'] ) as $declaration ) {
			$property	= $declaration['property'];
			$stem			= (string) preg_replace( $stepSuffix, '', $property );
			$rung			= $stem !== $property;
			$place		= $baseDeclared[$stem][0]['place'] ?? '';

			if( str_starts_with( $property, '--forms-' ) === false || $place === '' )
				$ruleProblems[] = $variant. ' sets '. $property. ', which the base does not declare';
			elseif( $rung === true && $rule['selector'] !== ':root' )
				$ruleProblems[] = $variant. ' sets the rung '. $property. ' under '. $rule['selector'];
			elseif( $rung === false && $place !== ( $rule['selector'] === ':root' ? ':where(:root)' : ':where(*)' ) )
				$ruleProblems[] = $variant. ' sets '. $property. ' under '. $rule['selector']. ', where the base does not declare it';
			elseif( $rung === false && $declaration['value'] === $baseDeclared[$property][0]['value'] )
				$ruleProblems[] = $variant. ' repeats the base: '. $property;
		}
	}

	foreach( $look['declared'] as $property => $places )
		if( count( $places ) !== 1 )
			$ruleProblems[] = $variant. ' declares '. $property. ' '. count( $places ). ' times';
}

check( 'a variant is a head and :root declarations - no rule on an element - of properties the base declares, in the place it declares them, each once, and none of them a repetition of the base\'s own default'. ( $ruleProblems === [] ? '' : ' - '. implode( ', ', $ruleProblems ) ), $ruleProblems === [] && count( $looks ) === 4 );

/*	A custom property that reads another is resolved where it is declared: what a variant declares on :root alone is read with the
	colours of the page, whatever section the field stands in. A colour a look needs is declared on every element, or left initial
	where the base has a fallback - the cut of a lifted label on a dark section would otherwise be a light box	*/
$perElement	= array_keys( array_filter( $baseDeclared, static fn( array $declarations ): bool => ( $declarations[0]['place'] ?? '' ) === ':where(*)' ) );
$readsColour	= '/var\(\s*(?:--color-[a-z0-9-]+|'. implode( '|', array_map( static fn( string $property ): string => preg_quote( $property, '/' ), $perElement ) ). ')\s*[,)]/';
$rootColours	= [];

foreach( $looks as $variant => $look )
	foreach( $look['rules'] as $rule )
		if( $rule['selector'] === ':root' )
			foreach( formsDeclarations( $rule['body'] ) as $declaration )
				if( preg_match( $readsColour, $declaration['value'] ) === 1 )
					$rootColours[] = $variant. ' '. $declaration['property']. ': '. $declaration['value'];

check( '...and nothing it declares on :root alone reads a colour - the page\'s on every ground, where a lifted label on a dark section would be a light box'. ( $rootColours === [] ? '' : ' - '. implode( ', ', $rootColours ) ), $rootColours === [] && $perElement !== [] );

$colourProblems = [];

foreach( $looks as $variant => $look ) {
	$code = (string) preg_replace( '#/\*.*?\*/#s', '', (string) file_get_contents( $dir. '/design/'. $variant. '.css' ) );

	foreach( formsColourLiterals( $code ) as $literal )
		$colourProblems[] = $variant. ' '. $literal;

	if( stripos( $code, '!important' ) !== false )
		$colourProblems[] = $variant. ' !important';
}

check( '...and none writes a colour as a literal or declares anything !important'. ( $colourProblems === [] ? '' : ' - '. implode( ', ', $colourProblems ) ), $colourProblems === [] );

/*	Regular is the feature as it stands: the ladders and nothing else, the middle of each the value the base has as its default,
	and the tone of its inks the colour the base reads	*/
$regular	= $looks['regular'] ?? [ 'declared' => [], 'knobs' => [] ];
$middles	= [];
$unequal	= [];

foreach( $regular['declared'] as $property => $places )
	if( preg_match( '/^(.+)--default$/', $property, $stem ) === 1 ) {
		$middles[] = $stem[1];

		if( ( $baseDeclared[ $stem[1] ][0]['value'] ?? null ) !== $places[0]['value'] )
			$unequal[] = $property;
	}

foreach( $regular['knobs'] as $name => $lines )
	if( $lines[0]['kind'] === 'ink' && ( $baseDeclared[ '--forms-'. $name ][0]['value'] ?? null ) !== 'var(--color-'. $lines[0]['default'] .')' )
		$unequal[] = $name;

check( 'Regular declares ladders and nothing else, and the middle of each is the default the base declares - so a form under it looks as the kernel draws it - and so is the tone of each of its inks'. ( $unequal === [] ? '' : ' - '. implode( ', ', $unequal ) ),
	$regular['declared'] !== [] && array_filter( array_keys( $regular['declared'] ), static fn( string $property ): bool => preg_match( '/--(?:least|less|default|more|most)$/', $property ) !== 1 ) === []
	&& $unequal === [] && count( $middles ) === count( array_merge( ...array_values( $stemsOf( $regular ) ) ) ) && $middles !== [] );

// --- The manual: the base's properties, and the looks the Design feature offers
$manual = (array) ( $manifest['manual'] ?? [] );
$manualProblems = [];

foreach( [ 'markup' => 'Custom properties', 'panel' => 'Design: the look of the forms' ] as $section => $title )
	foreach( [ 'en_US', 'de_DE' ] as $locale ) {
		$text = $manual[$section][$title][$locale] ?? null;

		if( is_string( $text ) === false || trim( $text ) === '' || max( array_map( 'strlen', explode( "\n", $text ) ) ) >= 1000 )
			$manualProblems[] = $section. ' / '. $title. ' / '. $locale;
	}

check( 'the manual names the properties of the stylesheet under markup and the looks of the Design feature under panel, in both languages, every line under 1000 bytes'. ( $manualProblems === [] ? '' : ' - '. implode( ', ', $manualProblems ) ), $manualProblems === [] );

echo "\n";


// --- The looks, run through the Design feature ------------------------------------

echo "Forms - the variants as the Design feature compiles them\n";

$designSetup			= '\\Nino\\Modules\\Design\\Setup';
$designPreview		= '\\Nino\\Modules\\Design\\Preview';
$designCompiler		= '\\Nino\\Modules\\Design\\Compiler';

if( class_exists( $designSetup ) === false || class_exists( $designCompiler ) === false )
	echo "  note - there is no Design feature beside this one: the variants are not run through it here, bin/check.sh runs them with it in the checkout\n";
else {
	$library			= \Nino\Features::dir(). '/Design/library';
	$modulesKept	= (array) ( $appData['/nino/modules'] ?? [] );
	$localeKept		= $appData['./nino/locales/current'];

	/** Which of the two features are on: their classes are listed in /nino/modules, and what the kernel and the Design feature have read of the features is read again */
	$featuresOn = static function( array $names ) use ( &$appData, $modulesKept ): void {
		$appData['/nino/modules'] = array_merge( array_values( array_diff( $modulesKept, [ '\\Nino\\Modules\\Forms', '\\Nino\\Modules\\Design' ] ) ), array_map( static fn( string $name ): string => '\\Nino\\Modules\\'. $name, $names ) );
		unset( $appData['./nino/features/all'], $appData['./nino/features/listed'], $appData['./design/features'] );
	};

	/** The sheet a setup makes of the raw entry given */
	$compile = static function( array $raw, array &$notes = [] ) use ( &$appData, $designSetup, $designCompiler, $library ): string {
		$setup = $designSetup::normalize( $appData, $raw, $library, $notes );

		return $designCompiler::compile( $appData, $setup, $library, $notes );
	};

	/** The words a head says in a language, by its position: the name and the description, once the language of the workbench is chosen */
	$described = static function( array $catalogue, string $suffix ) use ( $looks ): array {
		return array_filter( array_keys( $looks ), static fn( string $variant ): bool => ( $catalogue[$variant]['name'] ?? null ) !== $looks[$variant]['tags']['@name'. $suffix][0]
			|| ( $catalogue[$variant]['description'] ?? null ) !== $looks[$variant]['tags']['@description'. $suffix][0] );
	};

	ninoWarnings();
	$featuresOn( [ 'Forms', 'Design' ] );

	$scanNotes	= [];
	$scan				= $designSetup::features( $appData, $scanNotes );
	$feature		= $scan['forms'] ?? [];
	$catalogue	= $feature['catalogue'] ?? [];

	check( 'with the Design feature on, the scan lists this feature by its variants - the four, regular the one it has before anything is chosen - and has nothing to say of any of them',
		isset( $scan['forms'] ) === true && realpath( (string) $feature['dir'] ) === realpath( $dir ) && array_keys( $catalogue ) === array_keys( $looks ) && $feature['default'] === 'regular' && $scanNotes === [] );

	$appData['./nino/locales/current'] = 'en_US';
	$english = $designSetup::features( $appData )['forms']['catalogue'] ?? [];
	$appData['./nino/locales/current'] = 'de_DE';
	$german = $designSetup::features( $appData )['forms']['catalogue'] ?? [];

	check( '...each described in the language of the workbench by the lines of its head, the name and the description - the English ones or the ones with -de', $described( $english, '' ) === [] && $described( $german, '-de' ) === [] && count( $english ) === 4 );

	// The block is the feature's, not a variant's: the scan names its anchor once, with the feature, and Design makes the section round it
	$blockTold 		= [];
	$blockInside 	= $designSetup::previewOf( (string) $feature['dir'], 'forms', $blockTold );
	$blocks 			= $designPreview::blocks( $appData );

	check( 'with the Design feature on, the block stands the check Design holds it to and the scan publishes its anchor, feature-forms, with the feature and with none of its four variants, without a word to say of it - and the preview makes the section of it, named like the feature in the language of the workbench',
		$blockInside === trim( $blockSource ) && $blockInside !== '' && $blockTold === [] && ( $feature['anchor'] ?? null ) === 'feature-forms' && count( $catalogue ) === 4 && array_keys( $catalogue['regular'] ) === [ 'name', 'description', 'knobs' ]
		&& $scanNotes === [] && array_keys( $blocks ) === [ 'forms' ] && preg_match( '~^<section id="feature-forms" class="nino-section" aria-label="Formulare">\s*(.*?)\s*</section>$~s', $blocks['forms'], $blockSection ) === 1
		&& str_contains( $blockSection[1] ?? '', 'id="forms-name"' ) === true );

	$offTable = [];

	foreach( $catalogue as $variant => $look )
		foreach( $look['knobs'] as $name => $knob )
			if( ( $knobTable[$name] ?? null ) !== [ $knob['kind'], $knob['tab'] ] )
				$offTable[] = $variant. ' '. $name;

	check( 'every knob of a variant is one of the seven, with its kind and its tab: the size of the text on Type; space, corners, the weight of the line and where the label stands on Surfaces; the colour of the lines and of the labels on Colours'. ( $offTable === [] ? '' : ' - '. implode( ', ', $offTable ) ),
		$offTable === [] && array_keys( $catalogue['regular']['knobs'] ?? [] ) === array_keys( $knobTable ) );
	check( '...Regular, Filled and Contrast answer to all seven, Line to six - a field with no box has no corner to round',
		array_keys( $catalogue['filled']['knobs'] ?? [] ) === array_keys( $knobTable ) && array_keys( $catalogue['contrast']['knobs'] ?? [] ) === array_keys( $knobTable )
		&& array_keys( $catalogue['line']['knobs'] ?? [] ) === array_values( array_diff( array_keys( $knobTable ), [ 'corners' ] ) ) );

	$optionless = [];
	$tones			= [];

	foreach( $catalogue as $variant => $look ) {
		$words = $look['knobs']['labels']['options'] ?? [];

		if( array_keys( $words ) !== $designSetup::words( 'choice' ) || count( array_unique( $words ) ) !== 3 || in_array( '', $words, true ) === true )
			$optionless[] = $variant;

		foreach( [ 'edge', 'label' ] as $ink )
			$tones[$variant][$ink] = $look['knobs'][$ink]['default'] ?? null;
	}

	check( 'the choice of where a label stands has its three words, each its own, and the inks stand at the tone of their heads: the lines at the border in three variants and at the ink in Contrast, the labels at the ink in all four'. ( $optionless === [] ? '' : ' - '. implode( ', ', $optionless ) ),
		$optionless === [] && array_keys( $designSetup::INKS ) === $inkWords
		&& array_column( $tones, 'edge' ) === [ 'ink', 'border', 'border', 'border' ] && array_column( $tones, 'label' ) === [ 'ink', 'ink', 'ink', 'ink' ] );
	check( '...and a scale and a choice are published by their ladders: the scales by their own, the choice by its ten - its gap and the nine aspects the base declares, which are the ones every variant carries',
		$catalogue !== [] && array_filter( $catalogue, static fn( array $look ): bool => ( $look['knobs']['scale']['ladder'] ?? [] ) !== [ 'scale' ] || count( $look['knobs']['labels']['ladder'] ?? [] ) !== 10 || in_array( 'labels', $look['knobs']['labels']['ladder'] ?? [], true ) === false
			|| array_diff( array_map( static fn( string $ladder ): string => '--forms-'. $ladder, $look['knobs']['labels']['ladder'] ?? [] ), array_keys( $baseDeclared ) ) !== [] ) === [] );

	// The sheet: the section of the variant after the seven parts, the line of every ladder, the tone of every ink
	$featuresOn( [ 'Forms', 'Design' ] );
	$withNone	= $compile( [] );
	$featuresOn( [ 'Design' ] );
	$withOff	= $compile( [] );
	$featuresOn( [ 'Forms', 'Design' ] );

	check( 'with no variant chosen the feature compiles nothing: the sheet of a project that has the feature on is the sheet of one that has it off, byte for byte', $withNone === $withOff && str_contains( $withNone, 'feature forms' ) === false );

	$sheetProblems = [];

	foreach( $catalogue as $variant => $look ) {
		$sheetNotes	= [];
		$sheet			= $compile( [ 'features' => [ 'forms' => [ 'set' => $variant ] ] ], $sheetNotes );
		$ladders		= [];

		foreach( $look['knobs'] as $name => $knob ) {
			if( in_array( $knob['kind'], [ 'scale', 'choice' ], true ) === true ) {
				$ladders[] = $name. ' default';

				foreach( $knob['ladder'] ?? [] as $ladder )
					if( str_contains( $sheet, "\n\t--forms-". $ladder. ': var('. sprintf( $designSetup::KNOB_TOKEN, 'forms', $ladder, 'default' ). ");\n" ) === false )
						$sheetProblems[] = $variant. ' selects no '. $ladder;
			}
			elseif( preg_match( '~\n:root, :root \* \{\n(?:\t[^\n]*\n)*?\t--forms-'. preg_quote( $name, '~' ). ': var\('. preg_quote( $designSetup::INKS[ (string) $knob['default'] ] ?? '', '~' ). '\);\n~', $sheet ) !== 1 )
				$sheetProblems[] = $variant. ' writes no ink '. $name;
		}

		if( preg_match( '~^/\* ==== \d+\. feature forms: '. preg_quote( $variant, '~' ). ' \((.*)\) ==== \*/$~m', $sheet, $title ) !== 1 || $title[1] !== implode( ', ', $ladders ) )
			$sheetProblems[] = $variant. ' has no section of its own, or one that does not say its ladders';
		elseif( str_contains( $sheet, rtrim( (string) file_get_contents( $dir. '/design/'. $variant. '.css' ), "\n" ) ) === false )
			$sheetProblems[] = $variant. ' is not in its section as the file has it';

		if( $sheetNotes !== [] )
			$sheetProblems[] = $variant. ' compiled with a note';
	}

	check( '...and with a variant chosen the sheet carries its section, its stylesheet as the file has it, a line for every ladder of every knob it declares - the choice\'s ten among them - and the tone of every ink under the selector the compiler writes the inks under'. ( $sheetProblems === [] ? '' : ' - '. implode( ', ', $sheetProblems ) ), $sheetProblems === [] && $catalogue !== [] );

	$selects	= static fn( string $sheet, string $ladder, string $step ): bool => str_contains( $sheet, "\n\t--forms-". $ladder. ': var('. sprintf( $designSetup::KNOB_TOKEN, 'forms', $ladder, $step ). ");\n" );
	$moved	= $compile( [ 'features' => [ 'forms' => [ 'set' => 'regular', 'knobs' => [ 'scale' => -2, 'space' => 2, 'corners' => -1, 'line' => 1, 'labels' => -1, 'edge' => 'accent', 'label' => 'subtitle' ] ] ] ] );
	$wider	= $compile( [ 'features' => [ 'forms' => [ 'set' => 'filled', 'knobs' => [ 'labels' => 1 ] ] ] ] );
	$ladder	= $catalogue['regular']['knobs']['labels']['ladder'] ?? [];

	check( 'a position moved reaches the sheet as the rung it stands for, a tone chosen as the kernel\'s token of that tone, and the choice at less and at more as the rung of each of its ten ladders',
		$selects( $moved, 'scale', 'least' ) && $selects( $moved, 'space', 'most' ) && $selects( $moved, 'corners', 'less' ) && $selects( $moved, 'line', 'more' )
		&& array_filter( $ladder, static fn( string $each ) => $selects( $moved, $each, 'less' ) === false ) === [] && count( $ladder ) === 10
		&& array_filter( $ladder, static fn( string $each ) => $selects( $wider, $each, 'more' ) === false ) === []
		&& str_contains( $moved, "\n\t--forms-edge: var(". $designSetup::INKS['accent']. ");\n" ) === true && str_contains( $moved, "\n\t--forms-label: var(". $designSetup::INKS['subtitle']. ");\n" ) === true );

	// The bundle of a project the wizard has set up lists the sheet of the feature after the theme the Design feature writes
	$bundled = $appData;
	$bundled['/nino/html/assets'] = [ '/.cache/style.css' => array_merge( (array) ( \Nino\AppData::DEFAULTS['/nino/html/assets']['/.cache/style.css'] ?? [] ), [ $designCompiler::TARGET, '/assets/style.css' ] ) ];
	\Nino\Features::registerAssets( $bundled );
	$bundle = $bundled['/nino/html/assets']['/.cache/style.css'];

	check( 'in a booted project the bundle lists forms.css after the theme.css the Design feature writes its sheet to - the order the :where() of its defaults is for, and the reason a variant writes no rule',
		in_array( '/features/Forms/assets/forms.css', $bundle, true ) === true && array_search( '/features/Forms/assets/forms.css', $bundle, true ) > array_search( $designCompiler::TARGET, $bundle, true ) );

	$appData['/nino/modules']					= $modulesKept;
	$appData['./nino/locales/current']	= $localeKept;
	unset( $appData['./nino/features/all'], $appData['./nino/features/listed'], $appData['./design/features'] );

	check( 'and none of it raised a warning', ninoWarnings() === [] );
}

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
	defines or one Nino.adminUi.panel() gives it (the manual's list of its
	members), read off the script rather than listed here	*/
$panelScript = (string) file_get_contents( __DIR__. '/../assets/admin.js' );
preg_match_all( '/Nino\.admin\.forms\.([_a-zA-Z0-9]+)\s*\(/', $panelScript, $calledMethods );
preg_match_all( '/^\t\t([_a-zA-Z0-9]+)\s*:\s*function/m', $panelScript, $definedMethods );
$undefinedCalls = array_values( array_unique( array_diff( $calledMethods[1], $definedMethods[1], [ 'mount', 'call', 'fail', 'init', 'reload' ] ) ) );
check( 'every method the panel script calls on its own namespace is one it defines or panel() gives it'. ( $undefinedCalls === [] ? '' : ' - '. implode( ', ', $undefinedCalls ) ), $definedMethods[1] !== [] && $undefinedCalls === [] );

echo "\n";


// --- The panel's script, where node is on the path ----------------------------
//
// forms-js-smoke.js beside this file draws the list, the settings card and the
// editor over a dom stand-in, with the kernel's own Nino.js running in it - the
// one of the checkout this suite runs against, which is handed on - and counts
// the fixed action bars on each screen; this suite runs it too where node is on
// the path, the way redirects-smoke.php does, so bin/check.sh and CI cover both
// halves in one go
$jsTest	= __DIR__. '/forms-js-smoke.js';
$node		= function_exists( 'shell_exec' ) === true ? trim( (string) @shell_exec( 'command -v node 2>/dev/null' ) ) : '';

if( $node === '' || function_exists( 'exec' ) === false ) {
	echo "  --  - node is not available here: forms-js-smoke.js was NOT run\n";
} else {
	$output = []; $status = 1;
	exec( 'NINO_ROOT='. escapeshellarg( $root ). ' '. escapeshellarg( $node ). ' '. escapeshellarg( $jsTest ). ' 2>&1', $output, $status );
	$summary = (string) end( $output );
	check( 'forms-js-smoke.js passes - '. ( $summary === '' ? 'no output' : $summary ), $status === 0 );
}

ninoWarnings();
ninoDone( $appData );
