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
	 *										sending or writing anything. 418 for all of them: the
	 *										shared .nino-form script shows one generic message for
	 *										anything that is not 200 or 400, so a bot never learns
	 *										which check it tripped.
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

			\Nino\Filesystem::mutate( $appData, self::RATE, function( array $state ) use ( $now ): array {

				$key = hash( 'sha256', \Nino\Http::getClientIp() );

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
			$form	= \Nino\Form::form( $appData, preg_match( '/^[a-z][a-z0-9-]*$/', $key ) === 1 ? $key : '' );

			if( $form === null )
				return '';

			$safe = static fn( string $value ): string => htmlspecialchars( $value, ENT_QUOTES, 'UTF-8' );
			$id 	= 'form-'. $form['key'];

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
		 *	The four keys the endpoint reads off the post itself are left out
		 *	- a csrf token is not prose
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
			$key		= hash( 'sha256', \Nino\Http::getClientIp() );
			$entry	= \Nino\Filesystem::getFileContent( $appData, self::RATE, [] )[$key] ?? null;

			if( is_array( $entry ) === false || (int) ( $entry['reset'] ?? 0 ) <= time() )
				return false;

			return (int) ( $entry['tries'] ?? 0 ) >= $max;
		}
	}

}
