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
	 *	Forms							The three things Nino's own form endpoint does not do:
	 *										draw a form from its definition, edit the definitions,
	 *										and turn a submission away before the engine ever
	 *										sees it.
	 *
	 *										It replaces nothing. \Nino\Form is the engine - which
	 *										forms there are, what a submission has to look like,
	 *										the mail pair it sends, the record it leaves - and
	 *										\Nino\Modules\Form owns the route POST /.form. Both
	 *										stay exactly as they are with this feature switched
	 *										on; a project keeps its endpoint, its submissions and
	 *										its Submissions panel, and gains a builder, a
	 *										shortcode and the guards. Switching the feature off
	 *										again leaves a project with more than one form still
	 *										working: the definitions are in config.php, where the
	 *										kernel reads them.
	 *
	 *										The guards sit on the route callback at priority 1,
	 *										ahead of the module - the seam \Nino\Csrf::init()
	 *										already uses, and the reason there is no callback name
	 *										of its own for refusing a submission. A guard leaves a
	 *										status behind and \Nino\Form::handle() returns without
	 *										sending or writing anything. 418 for the stamp and for a
	 *										blocked word: the shared .nino-form script shows one generic
	 *										message for anything that is not 200 or 400, so a bot never
	 *										learns which check it tripped. The rate limit answers 429
	 *										instead - see the paragraph below.
	 *
	 *										The rate limit is the one guard in two halves. It is
	 *										checked at priority 1 and counted at priority 8, after
	 *										the engine answered 200 - a visitor who mistypes their
	 *										address four times has not submitted four times, and
	 *										locking them out for the hour would be this feature
	 *										defending the site against its own readers.
	 *
	 *	@package					Dape/Nino
	 *	@author						David Perchermeier <mail@dape.io>
	 *	@link							https://github.com/dapeio/nino
	 */
	class Forms {

		// Where this feature's own templates are, as \Nino\Filesystem resolves
		// them: /features is the installed features directory, wherever
		// NINO_FEATURES_DIR put it
		public const string TEMPLATES = '/features/Forms/templates';

		// The endpoint's route callback - the kernel module's own, which is
		// what makes a guard registered here run ahead of the engine
		public const string ROUTE = '/nino/http/response/POST://.form';

		// This feature's only file: one counter per hashed client ip, for the
		// hour it is in. Not in the manifest's 'data': it is a spam counter
		// that rebuilds itself, and a backup carrying it would restore an
		// hour of somebody's rate limit along with the site
		public const string RATE = '/data/forms-rate.php';

		// How long an entry of that file lives, in seconds
		private const int WINDOW = 3600;

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
		 *	Register the shortcode and the two halves of the guard. No route:
		 *	the endpoint is \Nino\Modules\Form's, and a project that switched
		 *	that module off has no form endpoint on purpose - this feature is
		 *	not the place to put one back
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	void
		 */
		public static function init( array &$appData ): void {

			\Nino\Html::addShortcode( $appData, 'form', [ self::class, 'doShortcode' ] );

			\Nino\Callbacks::registerCallback( $appData, self::ROUTE, [ self::class, 'callbackGuard' ], 1 );
			\Nino\Callbacks::registerCallback( $appData, self::ROUTE, [ self::class, 'callbackCount' ], 8 );
		}

		/**
		 *	Whether the endpoint these guards sit on is there at all. The
		 *	panel says so on screen, which is the only place a person would
		 *	look for it - a form that draws fine and posts to a 404 is the
		 *	one failure this feature could produce silently
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	bool
		 */
		public static function endpointActive( array &$appData ): bool {

			foreach( (array) ( $appData['/nino/modules'] ?? [] ) as $class )
				if( '\\'. ltrim( (string) $class, '\\' ) === '\\Nino\\Modules\\Form' )
					return true;

			return false;
		}

		/**
		 *	Refuse a submission ahead of the engine: the timestamp [form]
		 *	draws, a blocked word in any value, and this ip's allowance for
		 *	the hour. Everything else - the honeypot, the csrf token, the
		 *	fields themselves - is already checked where it belongs, and is
		 *	not repeated here
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$request			(reference) Current server request
		 *
		 *	@return 	void
		 */
		public static function callbackGuard( array &$appData, array &$request ): void {

			// Somebody ahead of this one already said no - the global csrf
			// guard sets a flag of its own, anything else left a status behind
			if( ( $request['./nino/csrf/blocked'] ?? false ) === true )
				return;

			if( (int) ( $request['/nino/http/response']['statusCode'] ?? 200 ) !== 200 )
				return;

			$posted = \Nino\Form::posted();

			if( self::_tooFast( $appData, (string) ( $posted['_t'] ?? '' ) ) === true || self::_blocked( $appData, $posted ) === true ) {
				$request['/nino/http/response']['statusCode'] = 418;
				return;
			}

			// 429 rather than 418 for this one: it is the only refusal a
			// person can meet by using the site normally, and the one they
			// can do something about - come back later
			if( self::_rateExceeded( $appData ) === true )
				$request['/nino/http/response']['statusCode'] = 429;
		}

		/**
		 *	Count a submission the engine accepted, for the ip that made it.
		 *	Runs after the engine (priority 8) so that only a submission that
		 *	was actually sent costs the visitor one of their hourly slots
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$request			(reference) Current server request
		 *
		 *	@return 	void
		 */
		public static function callbackCount( array &$appData, array &$request ): void {

			if( (int) ( $request['/nino/http/response']['statusCode'] ?? 0 ) !== 200 )
				return;

			if( (int) \Nino\Features::setting( $appData, 'forms', 'rateLimit', 0 ) <= 0 )
				return;

			$now = time();
			// Both resolved out here: the closure has no $appData, and which
			// address a visitor counts as depends on the proxy list in it
			// (see \Nino\Http::getClientIp())
			$key = hash( 'sha256', \Nino\Http::getClientIp( $appData ) );

			\Nino\Filesystem::mutate( $appData, self::RATE, function( array $state ) use ( $now, $key ): array {

				// Every key whose window has passed goes on write, not just
				// this one's - otherwise the file grows with every visitor the
				// site ever had
				foreach( $state as $stateKey => $entry )
					if( (int) ( $entry['reset'] ?? 0 ) <= $now )
						unset( $state[$stateKey] );

				$entry					= $state[$key] ?? [ 'tries' => 0, 'reset' => $now + self::WINDOW ];
				$entry['tries']	= (int) $entry['tries'] + 1;
				$state[$key]		= $entry;

				return $state;
			} );
		}

		/**
		 *	Render one form from its definition: [form] for the first one
		 *	defined, [form key="quote"] for another.
		 *
		 *	The markup is templates/form.tpl and one template per kind of field
		 *	beside it - see AGENTS.md, "Markup belongs in a template". What the
		 *	form template carries besides the fields is what the shared
		 *	.nino-form script looks for and would not work without: the honeypot
		 *	input, the live region it writes a result into, the submit button it
		 *	disables - plus the hidden key that says which form this is and the
		 *	moment it was drawn
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		$args					Shortcode attributes ( key )
		 *
		 *	@return 	string
		 */
		public static function doShortcode( array &$appData, array $args ): string {

			$key	= (string) ( $args['key'] ?? '' );
			$form	= \Nino\Form::form( $appData, preg_match( '/^[a-z][a-z0-9-]*$/', $key ) === 1 ? $key : '' );

			if( $form === null )
				return '';

			$safe = static fn( string $value ): string => htmlspecialchars( $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' );
			$id 	= 'form-'. $form['key'];

			$fields = '';

			foreach( $form['fields'] as $field ) {

				$fieldId	= $id. '-'. $field['name'];
				$required	= $field['required'] === true ? ' required' : '';

				/*	Rendered and then escaped, the same two steps the option
					below takes. A label is a fill key or a word an operator
					typed, so rendering it is what makes a localised form
					possible - and what comes out of that is text, which is why
					the option escapes it. The label did not, so the two halves
					of one form treated one kind of value two ways, and
					whichever of them is wrong, it is this one: a label is
					drawn inside a <label> the template owns, and markup in it
					is markup the template did not put there	*/
				$fields .= str_replace(
					[ '[[id]]', '[[star]]', '[[label]]' ],
					[ $safe( $fieldId ), ( $field['required'] === true ? ' *' : '' ), $safe( \Nino\Html::renderHtml( $appData, $field['label'] ) ) ],
					self::template( $appData, 'form-label' )
				);

				if( $field['type'] === 'textarea' ) {
					$fields .= str_replace(
						[ '[[id]]', '[[name]]', '[[required]]' ],
						[ $safe( $fieldId ), $safe( $field['name'] ), $required ],
						self::template( $appData, 'form-textarea' )
					);
				}

				else if( $field['type'] === 'select' ) {

					$options = '';

					foreach( $field['options'] as $option )
						$options .= str_replace(
							[ '[[value]]', '[[label]]' ],
							[ $safe( $option ), $safe( \Nino\Html::renderHtml( $appData, $option ) ) ],
							self::template( $appData, 'form-option' )
						);

					$fields .= str_replace(
						[ '[[id]]', '[[name]]', '[[required]]', '[[options]]' ],
						[ $safe( $fieldId ), $safe( $field['name'] ), $required, $options ],
						self::template( $appData, 'form-select' )
					);
				}

				else {
					$fields .= str_replace(
						[ '[[type]]', '[[id]]', '[[name]]', '[[required]]' ],
						[ $safe( $field['type'] ), $safe( $fieldId ), $safe( $field['name'] ), $required ],
						self::template( $appData, 'form-input' )
					);
				}
			}

			// [csrf] rendered here rather than left in the output: this string
			// is a shortcode's result, and the render pass that produced it has
			// already walked past the point where a shortcode of its own would
			// have been replaced.
			//
			// The subdirectory read from the configuration rather than through
			// the '[[/nino/dir]]' fill a template would use, for the same
			// reason: that fill is registered mid-request (see \Nino\request())
			// and a shortcode's output is not rendered again, so a form drawn
			// outside that window would carry the literal in its action

			/*	The three that carry markup last - [csrf] and the fields are built
				html, and str_replace() works through its arrays in order, so a token
				after them would be looked for in what they put in as well */
			return str_replace(
				[ '[[id]]', '[[action]]', '[[key]]', '[[time]]', '[[required]]', '[[submit]]', '[[csrf]]', '[[fields]]' ],
				[
					$safe( $id ),
					$safe( (string) ( $appData['/nino/dir'] ?? '' ) ),
					$safe( $form['key'] ),
					(string) time(),
					\Nino\Html::renderHtml( $appData, '[[/form/required]]' ),
					\Nino\Html::renderHtml( $appData, '[[/form/label/submit]]' ),
					\Nino\Html::renderHtml( $appData, '[csrf]' ),
					$fields,
				],
				self::template( $appData, 'form' )
			);
		}

		/**
		 *	Whether the submission came back faster than a person could have
		 *	filled the form in. Only a form [form] drew carries the stamp - a
		 *	hand-written one posts without it and is simply not checked,
		 *	which is why this is off unless a project asks for it
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$stamp				The posted '_t'
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
		 *	an operator writing "casino" means to catch "Casino-Bonus" too.
		 *	The keys the endpoint reads off the post itself are left out -
		 *	\Nino\Form::RESERVED, whatever that list holds today; a csrf
		 *	token is not prose
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		$posted				What \Nino\Form::posted() read
		 *
		 *	@return 	bool
		 */
		private static function _blocked( array &$appData, array $posted ): bool {

			$words = (array) \Nino\Features::setting( $appData, 'forms', 'blocklist', [] );

			if( $words === [] )
				return false;

			$values = array_diff_key( $posted, array_flip( \Nino\Form::RESERVED ) );
			$text 	= mb_strtolower( implode( "\n", $values ) );

			foreach( $words as $word ) {

				$word = mb_strtolower( trim( (string) $word ) );

				if( $word !== '' && str_contains( $text, $word ) === true )
					return true;
			}

			return false;
		}

		/**
		 *	Whether this ip has already used up its allowance for the hour.
		 *
		 *	Reads without writing - counting happens in callbackCount() once
		 *	the engine has accepted the submission. So a burst arriving at the
		 *	same moment can read the same counter and let a couple through
		 *	beyond the limit before the writes catch up. That is the deliberate
		 *	half of the trade: the counter itself is written under a lock and
		 *	stays exact, the site's hard stop against being used as a relay is
		 *	the kernel's own per-ip mail cap (\Nino\Mail), and what this limit
		 *	is for - a form submitted over and over - is slowed either way
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	bool
		 */
		private static function _rateExceeded( array &$appData ): bool {

			$max = (int) \Nino\Features::setting( $appData, 'forms', 'rateLimit', 0 );

			if( $max <= 0 )
				return false;

			// The ip is hashed: this file is a spam counter, not a visitor log
			$key		= hash( 'sha256', \Nino\Http::getClientIp( $appData ) );
			$entry	= \Nino\Filesystem::getFileContent( $appData, self::RATE, [] )[$key] ?? null;

			if( is_array( $entry ) === false || (int) ( $entry['reset'] ?? 0 ) <= time() )
				return false;

			return (int) ( $entry['tries'] ?? 0 ) >= $max;
		}
		/**
		 *	One of this feature's own templates, read the way a project's are.
		 *	Markup belongs in a template - see AGENTS.md, "Markup belongs in a
		 *	template" - so what this class holds is which one and what goes in it
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$name					A file name below TEMPLATES, without .tpl
		 *
		 *	@return 	string								'' where the file is not there, which is logged
		 */
		public static function template( array &$appData, string $name ): string {

			// A name from this class and nowhere else, and held to a slug anyway:
			// the one thing this could otherwise be turned into is a read of
			// something outside the feature
			if( preg_match( '/^[a-z][a-z0-9-]*$/', $name ) !== 1 )
				return '';

			$template = \Nino\Filesystem::getFileContent( $appData, self::TEMPLATES. '/'. $name. '.tpl', '' );

			$template = is_string( $template ) === true ? rtrim( $template, "\n" ) : '';

			/*	A template that is not there renders as nothing, which on a page looks
				like a shortcode nobody wrote rather than like a feature missing a file.
				Said out loud instead: E_USER_WARNING is Nino's "record this and carry
				on" channel (see \Nino\Runtime::NON_FATAL_LEVELS), so the request
				finishes and the log says which file	*/
			if( $template === '' )
				trigger_error( 'Nino: the template '. self::TEMPLATES. '/'. $name. '.tpl is missing or empty.', E_USER_WARNING );

			return $template;
		}
	}

}
