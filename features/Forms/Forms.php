<?php
declare(strict_types=1);
/**
 *	Nino								A compact filesystembased php framework
 *	Modules\Forms				see _nino/Nino/Modules/Modules.php for the
 *											package-level docblock
 *
 *	@package						Dape/Nino
 *	@author							David Perchermeier <mail@dape.io>
 *	@link								https://github.com/dapeio/nino
 */
namespace Nino\Modules {

	/**
	 *	Nino							A compact filesystembased php framework
	 *	Forms							Any number of forms, each with fields of its own, all
	 *										behind the one endpoint POST /.form the kernel's
	 *										contact form has always used - so a page written
	 *										against that endpoint keeps working, and a form the
	 *										panel defines is reachable without a route of its
	 *										own. Which form a submission belongs to is the
	 *										hidden "form" field [form] renders; a submission
	 *										without one belongs to the first form defined, which
	 *										is what a hand-written contact page posts.
	 *
	 *										The definitions live in /data/forms/definitions.php,
	 *										the submissions beside them as one file per form and
	 *										month (<key>.<Y-m>.php, pruned to the retention
	 *										setting) - plain "<?php return [...];" array files,
	 *										readable without the workbench, the same shape
	 *										config.php and the text files have. Read and written
	 *										by Forms\Admin next door, which is the panel.
	 *
	 *										It stands down entirely while the kernel's own
	 *										\Nino\Modules\Form is still listed in /nino/modules:
	 *										both answer POST /.form, and two handlers on one
	 *										endpoint means two mails and two records for one
	 *										visitor. The panel says so rather than the log.
	 *
	 *	@package					Dape/Nino
	 *	@author						David Perchermeier <mail@dape.io>
	 *	@link							https://github.com/dapeio/nino
	 */
	class Forms {

		// What one posted value may carry, the same cap Form and Newsletter
		// use - a field is a line or a message, never an upload
		private const int MAX_FIELD_LENGTH = 1000;

		public const string DIR					= '/data/forms';
		public const string DEFINITIONS	= '/data/forms/definitions.php';

		// The kernel module this one replaces. Listed as the literal both
		// sides of the comparison normalise to (see \Nino\Features::all())
		public const string KERNEL_MODULE = '\\Nino\\Modules\\Form';

		// The field types a form may declare - 'textarea' is the only one
		// that is not an <input type>, and the panel offers exactly these.
		//
		// No 'checkbox': the shared .nino-form script every Nino form is
		// driven by posts each field's .value unconditionally (see
		// _nino/Nino.ui.js), and an unticked checkbox's value is still the
		// string "on" - so a box nobody ticked would be mailed and recorded
		// as ticked. That is a gap in the kernel's own script, not something
		// a feature may paper over with a script of its own; when Nino sends
		// a checkbox's checked state, the type can be added here
		public const array TYPES = [ 'text', 'email', 'tel', 'url', 'number', 'textarea', 'select' ];

		// Field names something else already owns - see normalize(). A form
		// that wants a date asks for 'birthdate', not 'date'
		public const array RESERVED = [ 'form', 'location', '_csrf', '_t', 'id', 'date' ];

		// The form a project has before it ever opens the panel: the fields
		// the kernel's contact form had, under the labels its install unit
		// already wrote. Answered by forms() while nothing is stored, so a
		// contact page that came with the wizard keeps working the moment
		// this feature is switched on - and saving in the panel is what
		// first writes a definitions file
		public const array DEFAULT_FORM = [
			'key'						=> 'contact',
			'name'					=> 'Contact',
			'to'						=> '',
			'subject'				=> '',
			'confirm'				=> true,
			'ownerTemplate'	=> '/templates/mail-form-owner',
			'userTemplate'	=> '/templates/mail-form-user',
			'fields'				=> [
				[ 'name' => 'name',			'label' => '[[/form/label/name]]',		'type' => 'text',			'required' => true,		'options' => [] ],
				[ 'name' => 'email',		'label' => '[[/form/label/email]]',		'type' => 'email',		'required' => true,		'options' => [] ],
				[ 'name' => 'cat',			'label' => '[[/form/label/cat]]',			'type' => 'text',			'required' => false,	'options' => [] ],
				[ 'name' => 'message',	'label' => '[[/form/label/message]]',	'type' => 'textarea',	'required' => true,		'options' => [] ],
			],
		];

		/**
		 *	The /_admin screen this feature brings along - collected by
		 *	Admin::panels(), so it appears exactly while the feature is
		 *	active and vanishes with it
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	array										Panel class names
		 */
		public static function adminPanels( array &$appData ): array {
			return [ \Nino\Modules\Forms\Admin::class ];
		}

		/**
		 *	Register the endpoint, the shortcode and the restore merge -
		 *	nothing at all while the kernel's own contact form is still on,
		 *	see the class docblock. The route is registered here rather than
		 *	written into config.php: this feature owns the endpoint it needs
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	void
		 */
		public static function init( array &$appData ): void {

			if( self::kernelFormActive( $appData ) === true )
				return;

			$appData['/nino/http/routes']['POST://.form'] = [ 'uri' => '/.form' ];

			\Nino\Callbacks::registerCallback( $appData, '/nino/http/response/POST://.form', [ self::class, 'callbackResponse' ] );
			\Nino\Html::addShortcode( $appData, 'form', [ self::class, 'doShortcode' ] );

			// A restore from /_admin merges this feature's files instead of
			// overwriting them - see callbackRestore()
			\Nino\Callbacks::registerCallback( $appData, '/nino/admin/restore', [ self::class, 'callbackRestore' ] );
		}

		/**
		 *	Whether the kernel's own contact form is listed in /nino/modules -
		 *	the one state in which this feature does nothing at all. Public
		 *	because the panel says so on screen, which is the only place a
		 *	person would look
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	bool
		 */
		public static function kernelFormActive( array &$appData ): bool {

			foreach( (array) ( $appData['/nino/modules'] ?? [] ) as $class )
				if( '\\'. ltrim( (string) $class, '\\' ) === self::KERNEL_MODULE )
					return true;

			return false;
		}

		/**
		 *	Every defined form, validated - the stored definitions, or the
		 *	one built-in default while nothing is stored (see DEFAULT_FORM).
		 *	A stored entry that does not validate is left out rather than
		 *	handed on half-read: a form nobody can submit is better than one
		 *	that mails to an address a hand edit mistyped
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	array										List of forms, in the order they are defined
		 */
		public static function forms( array &$appData ): array {

			$stored = \Nino\Filesystem::getFileContent( $appData, self::DEFINITIONS, [] );
			$forms	= [];

			foreach( is_array( $stored ) === true ? $stored : [] as $entry )
				if( is_array( $entry ) === true && ( $form = self::normalize( $entry ) ) !== null )
					$forms[] = $form;

			return $forms === [] ? [ self::normalize( self::DEFAULT_FORM ) ] : $forms;
		}

		/**
		 *	One form by key, or - for an empty key - the first one defined,
		 *	which is the form a submission without a "form" field belongs to
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$key					Form key, '' for the default
		 *
		 *	@return 	array | null						Null for a key no form has
		 */
		public static function form( array &$appData, string $key = '' ): ?array {

			$forms = self::forms( $appData );

			if( $key === '' )
				return $forms[0] ?? null;

			foreach( $forms as $form )
				if( $form['key'] === $key )
					return $form;

			return null;
		}

		/**
		 *	One definition in the shape the rest of this class relies on -
		 *	every key present, every value of its declared type, the fields
		 *	deduplicated by name. Null for a definition that has no usable
		 *	key or not one usable field
		 *
		 *	@param		array 		$entry				A definition as stored or as posted
		 *
		 *	@return 	array | null
		 */
		public static function normalize( array $entry ): ?array {

			$key = strtolower( trim( (string) ( $entry['key'] ?? '' ) ) );

			if( preg_match( '/^[a-z][a-z0-9-]*$/', $key ) !== 1 )
				return null;

			$fields = [];
			$seen 	= [];

			foreach( (array) ( $entry['fields'] ?? [] ) as $field ) {

				if( is_array( $field ) === false )
					continue;

				$name = trim( (string) ( $field['name'] ?? '' ) );
				$type = (string) ( $field['type'] ?? 'text' );

				// A field name becomes a posted key, a column of the export and
				// a placeholder in the mail - so it is an identifier, not a
				// label, and never one of the names something else already owns:
				// four the endpoint reads off the post, and the two a recorded
				// submission carries beside its fields. The panel lays a
				// submission out flat - one column per field, beside its id and
				// its date - so a field called 'id' would take the place of the
				// identity the per-row delete is aimed at
				if( preg_match( '/^[a-zA-Z][a-zA-Z0-9_-]*$/', $name ) !== 1 || in_array( $name, self::RESERVED, true ) === true )
					continue;

				if( in_array( $name, $seen, true ) === true )
					continue;

				$seen[] = $name;

				$options = [];
				foreach( (array) ( $field['options'] ?? [] ) as $option )
					if( is_string( $option ) === true && trim( $option ) !== '' )
						$options[] = substr( trim( $option ), 0, 200 );

				$fields[] = [
					'name'			=> $name,
					'label'			=> substr( trim( (string) ( $field['label'] ?? $name ) ), 0, 200 ),
					'type'			=> in_array( $type, self::TYPES, true ) === true ? $type : 'text',
					'required'	=> ( $field['required'] ?? false ) === true,
					'options'		=> $options,
				];
			}

			if( $fields === [] )
				return null;

			$to = trim( (string) ( $entry['to'] ?? '' ) );

			return [
				'key'						=> $key,
				'name'					=> substr( trim( (string) ( $entry['name'] ?? $key ) ), 0, 100 ) ?: $key,
				'to'						=> filter_var( $to, FILTER_VALIDATE_EMAIL ) === false ? '' : $to,
				'subject'				=> substr( trim( (string) ( $entry['subject'] ?? '' ) ), 0, 200 ),
				'confirm'				=> ( $entry['confirm'] ?? false ) === true,
				'ownerTemplate'	=> self::_template( $entry['ownerTemplate'] ?? '', '/templates/mail-form-owner' ),
				'userTemplate'	=> self::_template( $entry['userTemplate'] ?? '', '/templates/mail-form-user' ),
				'fields'				=> $fields,
			];
		}

		/**
		 *	A template path as [template ...] takes one: absolute, no
		 *	traversal, no shortcode syntax of its own. Anything else falls
		 *	back to the default rather than being rendered
		 *
		 *	@param		mixed			$value				As stored or posted
		 *	@param		string		$default			The feature's own template
		 *
		 *	@return 	string
		 */
		private static function _template( mixed $value, string $default ): string {

			$path = trim( (string) ( is_string( $value ) === true ? $value : '' ) );

			return preg_match( '#^/[a-zA-Z0-9/_-]+$#', $path ) === 1 && str_contains( $path, '..' ) === false
				? $path
				: $default;
		}

		/**
		 *	Validate one submission, mail it and record it. The answers are
		 *	the ones the shared .nino-form script already knows (see
		 *	_nino/Nino.ui.js): 200 done, 400 a field the visitor can fix,
		 *	anything else the generic "try again later" - which is why every
		 *	spam refusal answers 418 like the honeypot always did, and never
		 *	says which check it tripped
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$request			(reference) Current server request
		 *
		 *	@return 	void
		 */
		public static function callbackResponse( array &$appData, array &$request ): void {

			// Respect a rejection from the earlier global Csrf callback - the
			// same guard Form, Newsletter and Auth use
			if( ( $request['./nino/csrf/blocked'] ?? false ) === true )
				return;

			$clean = static fn( string $key ): string => substr( trim( (string) ( $_POST[$key] ?? '' ) ), 0, self::MAX_FIELD_LENGTH );

			$form = self::form( $appData, preg_match( '/^[a-z][a-z0-9-]*$/', $clean( 'form' ) ) === 1 ? $clean( 'form' ) : '' );

			// A key no form has: the visitor cannot fix that, but it is also
			// not spam - it is a page pointing at a form that was renamed
			if( $form === null ) {
				$request['/nino/http/response']['statusCode'] = 404;
				return;
			}

			// Honeypot filled, a blocked word, or back faster than a person
			// can type - one answer for all three, see the docblock
			if( $clean( 'location' ) !== '' || self::_tooFast( $appData, $clean( '_t' ) ) === true ) {
				$request['/nino/http/response']['statusCode'] = 418;
				return;
			}

			$values = [];
			$failed = false;

			foreach( $form['fields'] as $field ) {

				$value = $clean( $field['name'] );

				if( $field['required'] === true && $value === '' )
					$failed = true;

				if( $value !== '' && self::_valid( $field, $value ) === false )
					$failed = true;

				$values[$field['name']] = $value;
			}

			if( $failed === true ) {
				$request['/nino/http/response']['statusCode'] = 400;
				return;
			}

			if( self::_blocked( $appData, $values ) === true ) {
				$request['/nino/http/response']['statusCode'] = 418;
				return;
			}

			// Counted before anything is sent, and counted for a submission
			// that passed every check - a refused one already cost nothing
			if( self::_withinRate( $appData ) === false ) {
				$request['/nino/http/response']['statusCode'] = 429;
				return;
			}

			self::_send( $appData, $form, $values );

			$request['/nino/http/response']['statusCode'] = 200;
			$request['/nino/http/response']['body'] 			= [ 'status' => 'ok' ];

			// A submission whose mail the kernel refused on its own rate limit
			// is not recorded: one entry per request regardless would turn a
			// throttled flood into unthrottled disk growth from an
			// unauthenticated endpoint (the reasoning Form::callbackResponse
			// spells out). A mail() that simply failed still records - there
			// the inquiry did happen and losing it would be worse
			if( ( $appData['./nino/mail/ratelimited'] ?? false ) === true )
				return;

			if( \Nino\Features::setting( $appData, 'forms', 'store', true ) === true )
				self::_record( $appData, $form, $values );
		}

		/**
		 *	Render one form from its definition: [form] for the first one
		 *	defined, [form key="quote"] for another. The markup is the one
		 *	the shared .nino-form script drives - csrf, honeypot, one message
		 *	line, one submit button - plus the hidden key that says which
		 *	form this is and the moment it was drawn
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		$args					Shortcode attributes ( key )
		 *
		 *	@return 	string
		 */
		public static function doShortcode( array &$appData, array $args ): string {

			$key	= (string) ( $args['key'] ?? '' );
			$form	= self::form( $appData, preg_match( '/^[a-z][a-z0-9-]*$/', $key ) === 1 ? $key : '' );

			if( $form === null )
				return '';

			$safe = static fn( string $value ): string => htmlspecialchars( $value, ENT_QUOTES, 'UTF-8' );
			$id 	= 'form-'. $form['key'];

			// Rendered here rather than left in the output: this string is a
			// shortcode's result, and the render pass that produced it has
			// already walked past the point where a [csrf] of its own would
			// have been replaced
			// The subdirectory read from the configuration rather than through
			// the '[[/nino/dir]]' fill a template would use: that fill is
			// registered mid-request (see \Nino\request()), and a shortcode's
			// output is not rendered again - so a form drawn outside that
			// window would carry the literal in its action
			$html = '<form class="nino-form" id="'. $safe( $id ). '" action="'. $safe( (string) ( $appData['/nino/dir'] ?? '' ) ). '/.form" method="post">'
				. \Nino\Html::renderHtml( $appData, '[csrf]' )
				. '<input type="hidden" name="form" value="'. $safe( $form['key'] ). '">'
				. '<input type="hidden" name="_t" value="'. time(). '">';

			foreach( $form['fields'] as $field ) {

				$fieldId	= $id. '-'. $field['name'];
				$label		= \Nino\Html::renderHtml( $appData, $field['label'] );
				$required	= $field['required'] === true ? ' required' : '';

				$html .= '<label for="'. $safe( $fieldId ). '">'. $label. ( $field['required'] === true ? ' *' : '' ). '</label>';

				if( $field['type'] === 'textarea' )
					$html .= '<textarea id="'. $safe( $fieldId ). '" name="'. $safe( $field['name'] ). '" class="nino-form-textarea"'. $required. '></textarea>';

				else if( $field['type'] === 'select' ) {
					$html .= '<select id="'. $safe( $fieldId ). '" name="'. $safe( $field['name'] ). '" class="nino-form-input"'. $required. '>';
					foreach( $field['options'] as $option )
						$html .= '<option value="'. $safe( $option ). '">'. $safe( \Nino\Html::renderHtml( $appData, $option ) ). '</option>';
					$html .= '</select>';
				}

				else
					$html .= '<input type="'. $safe( $field['type'] ). '" id="'. $safe( $fieldId ). '" name="'. $safe( $field['name'] ). '" class="nino-form-input"'. $required. '>';
			}

			// The trap, the live region the script writes into and the button
			// it disables - all three are what .nino-form looks for
			$html .= '<input type="text" name="location" value="" tabindex="-1" autocomplete="off" aria-hidden="true" class="nino-form-trap">'
				. '<p class="nino-form-message" aria-live="polite"></p>'
				. '<p><small>* '. \Nino\Html::renderHtml( $appData, '[[/form/required]]' ). '</small></p>'
				. '<button type="submit" class="nino-btn nino-btn--primary nino-form-submit">'. \Nino\Html::renderHtml( $appData, '[[/form/label/submit]]' ). '</button>'
				. '</form>';

			return $html;
		}

		/**
		 *	Whether one posted value is of the shape its field declares -
		 *	the same checks the browser ran, repeated here because a post
		 *	does not have to come from a browser
		 *
		 *	@param		array 		$field				One normalized field
		 *	@param		string		$value				The posted value, non-empty
		 *
		 *	@return 	bool
		 */
		private static function _valid( array $field, string $value ): bool {

			return match( $field['type'] ) {
				'email'		=> filter_var( $value, FILTER_VALIDATE_EMAIL ) !== false,
				'url'			=> filter_var( $value, FILTER_VALIDATE_URL ) !== false,
				'number'	=> is_numeric( $value ) === true,
				'select'	=> $field['options'] === [] || in_array( $value, $field['options'], true ) === true,
				default		=> true,
			};
		}

		/**
		 *	Whether a submission came back faster than a person could have
		 *	filled it in. Only a form [form] drew carries the moment, so a
		 *	hand-written one is never judged by this - and a stamp from the
		 *	future is as wrong as one from a millisecond ago
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$stamp				The posted _t, '' when the form carried none
		 *
		 *	@return 	bool
		 */
		private static function _tooFast( array &$appData, string $stamp ): bool {

			$min = (int) \Nino\Features::setting( $appData, 'forms', 'minSeconds', 0 );

			if( $min <= 0 || preg_match( '/^\d{1,10}$/', $stamp ) !== 1 )
				return false;

			// A stamp from the future gives a negative age, which is below any
			// positive minimum - so it is refused by the same comparison
			return time() - (int) $stamp < $min;
		}

		/**
		 *	Whether any posted value carries a blocked word. Compared
		 *	case-insensitively as a substring: a list is written by hand and
		 *	an operator writing "casino" means to catch "Casino-Bonus" too
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		$values				name => posted value
		 *
		 *	@return 	bool
		 */
		private static function _blocked( array &$appData, array $values ): bool {

			$words = (array) \Nino\Features::setting( $appData, 'forms', 'blocklist', [] );
			$text 	= mb_strtolower( implode( "\n", $values ) );

			foreach( $words as $word ) {

				$word = mb_strtolower( trim( (string) $word ) );

				if( $word !== '' && str_contains( $text, $word ) === true )
					return true;
			}

			return false;
		}

		/**
		 *	Count this ip's submission and say whether it is still within
		 *	the hour's allowance. An unlocked read-modify-write would let a
		 *	burst - which is what this exists to stop - read the same counter
		 *	twice, so a counter that could not be written closes rather than
		 *	opens (the reasoning \Nino\Mail::_hit() spells out). The ip is
		 *	hashed: this file is a spam counter, not a visitor log
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	bool
		 */
		private static function _withinRate( array &$appData ): bool {

			$max = (int) \Nino\Features::setting( $appData, 'forms', 'rateLimit', 0 );

			if( $max <= 0 )
				return true;

			$key		= hash( 'sha256', \Nino\Http::getClientIp() );
			$now		= time();
			$tries	= null;

			$written = \Nino\Filesystem::mutate( $appData, self::DIR. '/rate.php', function( array $state ) use ( $key, $now, &$tries ): array {

				foreach( $state as $stateKey => $entry )
					if( (int) ( $entry['reset'] ?? 0 ) <= $now )
						unset( $state[$stateKey] );

				$entry					= $state[$key] ?? [ 'tries' => 0, 'reset' => $now + 3600 ];
				$entry['tries']	= (int) $entry['tries'] + 1;
				$state[$key]		= $entry;
				$tries					= $entry['tries'];

				return $state;
			} );

			return $written === true && $tries <= $max;
		}

		/**
		 *	Send the owner notification and, where the form asks for one and
		 *	the submission carries an address to send it to, the visitor's
		 *	confirmation. The owner mail always goes out in the site's native
		 *	locale, the visitor's in the locale they filled the form in -
		 *	the split \Nino\Modules\Form has always made
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		$form					One normalized form
		 *	@param		array 		$values				name => posted value
		 *
		 *	@return 	void
		 */
		private static function _send( array &$appData, array $form, array $values ): void {

			$owner = $form['to'] !== '' ? $form['to'] : \Nino\Html::renderHtml( $appData, '[[/form/email/owner]]' );
			$reply = self::_firstEmail( $form, $values );

			$visitorLocale = \Nino\Locales::getCurrentLocale( $appData );
			\Nino\Locales::setCurrentLocale( $appData, \Nino\Locales::getNativeLocale( $appData ) );

			$body			= self::_render( $appData, $form, $values, $form['ownerTemplate'] );
			$subject	= $form['subject'] !== ''
				? \Nino\Html::renderHtml( $appData, $form['subject'] )
				: \Nino\Html::renderHtml( $appData, '[[/form/subject/owner]]' );

			\Nino\Locales::setCurrentLocale( $appData, $visitorLocale );

			\Nino\Mail::send( $appData, $owner, $subject, $body, $reply !== '' ? $reply : $owner );

			if( $form['confirm'] === false || $reply === '' )
				return;

			\Nino\Mail::send( $appData, $reply,
				\Nino\Html::renderHtml( $appData, '[[/form/subject/user]]' ),
				self::_render( $appData, $form, $values, $form['userTemplate'] ),
				$owner );
		}

		/**
		 *	One mail body: the template rendered, then the placeholders
		 *	replaced in the result - the order \Nino\Modules\Form uses, so a
		 *	value can never be read as a fill or a shortcode of its own.
		 *	[[fields]] is the whole submission as a table, which is what a
		 *	template for a form with fields nobody knew in advance needs;
		 *	[[name]], [[email]], [[message]], [[subject]] and [[date]] are
		 *	filled where the form has a field of that name, so a project's
		 *	own mail template from before this feature keeps rendering
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		$form					One normalized form
		 *	@param		array 		$values				name => posted value
		 *	@param		string		$template			Absolute template path
		 *
		 *	@return 	string
		 */
		private static function _render( array &$appData, array $form, array $values, string $template ): string {

			$safe = static fn( string $value ): string => htmlspecialchars( $value, ENT_QUOTES, 'UTF-8' );
			$rows = '';

			foreach( $form['fields'] as $field )
				$rows .= '<tr><th>'. \Nino\Html::renderHtml( $appData, $field['label'] ). '</th><td>'
					. nl2br( $safe( (string) ( $values[$field['name']] ?? '' ) ) ). '</td></tr>';

			$fills = [
				'[[fields]]'	=> '<table>'. $rows. '</table>',
				'[[form]]'		=> $safe( $form['name'] ),
				'[[date]]'		=> date( 'Y-m-d H:i:s' ),
				'[[subject]]'	=> $safe( (string) ( $values['cat'] ?? $values['subject'] ?? '' ) ),
			];

			foreach( [ 'name', 'email', 'message' ] as $name )
				$fills['[['. $name. ']]'] = $name === 'message'
					? nl2br( $safe( (string) ( $values[$name] ?? '' ) ) )
					: $safe( (string) ( $values[$name] ?? '' ) );

			$html = \Nino\Html::renderHtml( $appData, '[template '. $template. ']' );

			return str_replace( array_keys( $fills ), array_values( $fills ), $html );
		}

		/**
		 *	The first email the submission carries, which is who a
		 *	confirmation goes to and who a reply to the owner mail reaches
		 *
		 *	@param		array 		$form					One normalized form
		 *	@param		array 		$values				name => posted value
		 *
		 *	@return 	string									'' when the form asks for no address
		 */
		private static function _firstEmail( array $form, array $values ): string {

			foreach( $form['fields'] as $field )
				if( $field['type'] === 'email' && ( $values[$field['name']] ?? '' ) !== '' )
					return (string) $values[$field['name']];

			return '';
		}

		/**
		 *	Append one submission to this form's file for this month, then
		 *	prune the months past the retention setting. Never thrown: a
		 *	failed record must not turn a delivered mail into a 500 for the
		 *	visitor. Values are stored escaped, the way \Nino\Modules\Form
		 *	stored them - the panel decodes them again on render
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		$form					One normalized form
		 *	@param		array 		$values				name => posted value
		 *
		 *	@return 	void
		 */
		private static function _record( array &$appData, array $form, array $values ): void {

			try {

				$fields = [];
				foreach( $values as $name => $value )
					$fields[$name] = htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8' );

				$entry = [
					// An identity of its own, so the panel can delete exactly one
					// entry: a position in the file is not one - every deletion
					// above it would move the rest
					'id'			=> bin2hex( random_bytes( 8 ) ),
					'date'		=> date( 'Y-m-d H:i:s' ),
					'form'		=> $form['key'],
					'ip'			=> \Nino\Http::getClientIp(),
					'fields'	=> $fields,
				];

				\Nino\Filesystem::mutate( $appData, self::DIR. '/'. $form['key']. '.'. date( 'Y-m' ). '.php', function( array $entries ) use ( $entry ): array {
					$entries[] = $entry;
					return $entries;
				} );

				self::prune( $appData );

			} catch( \Throwable $e ) {
				trigger_error( 'Forms: recording a submission failed: '. $e->getMessage() );
			}
		}

		/**
		 *	Delete every month file older than the retention setting, for
		 *	every form - including one that was renamed or deleted, whose
		 *	months would otherwise sit there until someone found them
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	void
		 */
		public static function prune( array &$appData ): void {

			$months = (int) \Nino\Features::setting( $appData, 'forms', 'retention', 3 );
			$dir 		= \Nino\Filesystem::path( $appData, self::DIR );
			$cutoff = ( new \DateTime( 'first day of -'. max( 1, $months ). ' months' ) )->setTime( 0, 0 );

			foreach( glob( $dir. '/*.*.php' ) ?: [] as $file ) {

				$name = basename( $file, '.php' );
				$at 	= strrpos( $name, '.' );

				if( $at === false || preg_match( '/^\d{4}-\d{2}$/', substr( $name, $at + 1 ) ) !== 1 )
					continue;

				\Nino\RotatingLog::prune( $dir, substr( $name, 0, $at + 1 ), 'Y-m', '.php', $cutoff );
			}
		}

		/**
		 *	Merge this feature's data on a restore instead of letting the
		 *	backup overwrite it: a submission that arrived after the backup
		 *	was taken is an inquiry nobody else has a copy of, so the
		 *	restored month and the live one are joined and deduplicated
		 *	rather than one replacing the other. The definitions are the
		 *	backup's, which is what restoring them means
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$args				{ dataDir, staging }
		 *
		 *	@return 	void
		 */
		public static function callbackRestore( array &$appData, array &$args ): void {

			$dataDir = (string) ( $args['dataDir'] ?? '' );
			$staging = (string) ( $args['staging'] ?? '' );

			if( $staging === '' || is_dir( $staging. '/data/forms' ) === false )
				return;

			foreach( glob( $staging. '/data/forms/*.*.php' ) ?: [] as $staged ) {

				$name = basename( $staged, '.php' );
				$at 	= strrpos( $name, '.' );

				if( $at === false || preg_match( '/^\d{4}-\d{2}$/', substr( $name, $at + 1 ) ) !== 1 )
					continue;

				$merged = self::_readDataFile( $staged );
				$known 	= [];

				foreach( $merged as $entry )
					$known[] = serialize( $entry );

				foreach( self::_readDataFile( $dataDir. '/forms/'. basename( $staged ) ) as $entry )
					if( in_array( serialize( $entry ), $known, true ) === false )
						$merged[] = $entry;

				usort( $merged, static fn( array $a, array $b ): int => strcmp( (string) ( $a['date'] ?? '' ), (string) ( $b['date'] ?? '' ) ) );

				file_put_contents( $staged, '<?php return '. var_export( $merged, true ). ';' );
			}
		}

		/**
		 *	Read one plain array file by absolute path - used by
		 *	callbackRestore() for both sides of its merge: the staged copy
		 *	lives in a tempdir, not the project root \Nino\Filesystem
		 *	resolves against, so both go through the identical read path
		 *	(the shape \Nino\Modules\Newsletter uses for the same reason)
		 *
		 *	@param		string		$path					Absolute path
		 *
		 *	@return 	array
		 */
		private static function _readDataFile( string $path ): array {

			if( is_file( $path ) === false )
				return [];

			$data = include $path;

			return is_array( $data ) === true ? $data : [];
		}
	}

}
