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
	 *										draw a form from its definition (the component form),
	 *										edit the definitions, and turn a submission away
	 *										before the engine ever sees it.
	 *
	 *										It replaces nothing. \Nino\Form is the engine - which
	 *										forms there are, what a submission has to look like,
	 *										the mail pair it sends, the record it leaves - and
	 *										\Nino\Modules\Form owns the route POST /.form. Both
	 *										stay exactly as they are with this feature switched
	 *										on; a project keeps its endpoint, its submissions and
	 *										its Submissions panel, and gains a builder, a
	 *										component and the guards. Switching the feature off
	 *										again leaves a project with more than one form still
	 *										working: the definitions are in config.php, where the
	 *										kernel reads them.
	 *
	 *										The guards sit on the route callback at priority 1,
	 *										ahead of the module - the seam Nino documents for
	 *										refusing a submission, and the reason there is no
	 *										callback name of its own for it. A guard leaves a
	 *										status behind and \Nino\Form::handle() returns without
	 *										sending or writing anything. 418 for the stamp and for a
	 *										blocked word: the shared .nino-form script shows one generic
	 *										message for anything that is not 200 or 400, so a bot never
	 *										learns which check it tripped. The rate limit answers 429
	 *										instead - see the paragraph below.
	 *
	 *										A browser without the script gets the same refusals as a
	 *										redirect: the kernel answers every status of 400 and
	 *										above that a guard left with a 303 back to the page and
	 *										?form=error (\Nino\Form::answer()), and the component draws
	 *										what the page was sent back with (\Nino\Form::outcome())
	 *										into the message of its form.
	 *
	 *										The rate limit is the one guard in two halves. It is
	 *										checked at priority 1 and counted at priority 8, after
	 *										the engine accepted the submission - a visitor who
	 *										mistypes their address four times has not submitted
	 *										four times, and locking them out for the hour would be
	 *										this feature defending the site against its own
	 *										readers. Accepted is a 200 for the script of the page
	 *										and the 303 that says ?form=success for a browser
	 *										without it.
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

		// The mark of a field that has to be filled, behind its label: a sign for the eye, which the required attribute says to everybody else
		private const string STAR = ' <span class="form-star" aria-hidden="true">[[mark]]</span>';

		// Where this feature's own words are, and the word of the first option of
		// options(): the form [form] draws where it is told none
		public const string TEXT = '/features/Forms/text';
		public const string FIRST = '[[/_admin/forms/label/first]]';

		// The words a post is answered with, which the script of the Form module says as well: data-form-texts of the form
		// names this prefix, and an outcome is the last part of the key
		public const string INFO = '/module/form/info/';

		// How many times each form was drawn in this request, for the ids of the forms of a page
		private const string DRAWN = './nino/forms/drawn';

		// The endpoint's route callback - the kernel module's own, which is
		// what makes a guard registered here run ahead of the engine
		public const string ROUTE = '/nino/http/response/POST://.form';

		// This feature's only file, the counter \Nino\Feature::count() keeps
		// under this name: /data/forms-rate.php, one entry per hashed client ip
		// for the hour it is in. Not in the manifest's 'data': it is a spam
		// counter that rebuilds itself, and a backup carrying it would restore
		// an hour of somebody's rate limit along with the site
		public const string COUNTER = 'forms-rate';

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
		 *	Register the two halves of the guard. The component form is in its folder and the
		 *	kernel registers it. No route: the endpoint is \Nino\Modules\Form's, and a project that
		 *	switched that module off has no form endpoint on purpose - this feature is not the place
		 *	to put one back
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	void
		 */
		public static function init( array &$appData ): void {
			\Nino\Callbacks::registerCallback( $appData, self::ROUTE, [ self::class, 'callbackGuard' ], 1 );
			\Nino\Callbacks::registerCallback( $appData, self::ROUTE, [ self::class, 'callbackCount' ], 8 );
		}

		/**
		 *	The forms there are, as the options of the attribute key of [form]
		 *	(the schema's 'options'; the register asks for them where a tool needs
		 *	the list and where a call of [form] is made ready): the empty
		 *	word first, which draws the first form, and then each form by its key
		 *	with its name for the label, in the order the Forms panel lists them -
		 *	what \Nino\Form::forms() reads, the contact form the kernel falls back to
		 *	where a project defines none included, since [form] draws it by that key.
		 *	The label of the empty word is a word of this feature's own text, in each
		 *	language of the site (English where the feature has no words of it), so
		 *	that a tool reads it in the language it is in. A key the list does not hold
		 *	- the form was deleted since - is the empty word where the register makes a
		 *	call ready, and draws the first form
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$attribute		The attribute asked for
		 *
		 *	@return 	array										key => name, a locale => string map for the first
		 */
		public static function options( array &$appData, string $attribute ): array {

			$first = [];

			foreach( \Nino\Locales::getAvailableLocales( $appData ) as $locale ) {

				$words = \Nino\Filesystem::getFileContent( $appData, self::TEXT. '/'. $locale. '.php', false );

				if( is_array( $words ) === false )
					$words = \Nino\Filesystem::getFileContent( $appData, self::TEXT. '/en_US.php', [] );

				$first[(string) $locale] = (string) ( ( (array) $words )[ self::FIRST ] ?? '' );
			}

			$options = [ '' => $first ];

			foreach( \Nino\Form::forms( $appData ) as $form )
				$options[ $form['key'] ] = $form['name'];

			return $options;
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
			if( \Nino\Form::blocked( $request ) === true )
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
		 *	was actually sent costs the visitor one of their hourly slots. For the
		 *	script of the page that is a 200; for a browser without it the engine
		 *	has answered with the 303 back to the page, and the word it carries
		 *	says whether the submission was taken - without this a limit would
		 *	count nothing for every visitor who has no script
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$request			(reference) Current server request
		 *
		 *	@return 	void
		 */
		public static function callbackCount( array &$appData, array &$request ): void {

			if( self::accepted( $request ) === false )
				return;

			// Whichever address a visitor counts as depends on the proxy list of the
			// kernel (see \Nino\Http::getClientIp()). A limit of 0 is no limit: nothing
			// is counted then, and nothing written
			\Nino\Feature::count( $appData, self::COUNTER, \Nino\Http::getClientIp( $appData ), (int) \Nino\Features::setting( $appData, 'forms', 'rateLimit', 10 ), self::WINDOW );
		}

		/**
		 *	Whether the engine took the submission this response answers: a 200, or the 303 that
		 *	\Nino\Form::answer() makes for a browser without the script, back to a page whose query
		 *	says success. Every other status - a guard's 418 or 429, the engine's own refusals,
		 *	which a browser without the script reads as ?form=error - took nothing
		 *
		 *	@param		array 		$request			Current server request
		 *
		 *	@return 	bool
		 */
		public static function accepted( array $request ): bool {

			$response	= (array) ( $request['/nino/http/response'] ?? [] );
			$status		= (int) ( $response['statusCode'] ?? 0 );

			if( $status === 200 )
				return true;

			$location	= $response['header']['Location'] ?? '';

			if( $status !== 303 || is_string( $location ) === false )
				return false;

			parse_str( (string) parse_url( $location, PHP_URL_QUERY ), $query );

			return ( $query[ \Nino\Form::OUTCOME_PARAM ] ?? null ) === 'success';
		}

		/**
		 *	The renderer of the component form, [form] for the first form defined, [form key="quote"] for
		 *	another. The markup of the form is components/form/template.tpl and one template of the
		 *	feature for each kind of field (templates/form-*.tpl) - see AGENTS.md, "Markup belongs in a
		 *	template". What the form template carries besides the fields is what the script of the Form
		 *	module looks for and would not work without - the class nino-form, the first paragraph as
		 *	its message, the first button as its submit button, data-form-texts - plus the honeypot the
		 *	engine checks, the hidden key that says which form this is, the id of the form on its page
		 *	(\Nino\Form::ANCHOR_FIELD, which the redirect of a browser without the script ends in) and
		 *	the moment it was drawn.
		 *
		 *	Without the script the page is the answer: a post that came back with ?form=<outcome>
		 *	(\Nino\Form::outcome()) gets the words of that outcome, the ones the script says, in the
		 *	message of every form drawn, marked data-form-outcome. A server never sees the fragment of
		 *	the address, so which form was sent cannot be known here: the part shows the message of the
		 *	form the fragment names (:target) and hides the others
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		$args					The attributes of the call, made ready by the register
		 *	@param		string		$content			Nothing: the component is a leaf
		 *	@param		array 		$context			Where the call stands
		 *
		 *	@return 	string										Finished markup; '' for a key no form has
		 */
		public static function componentForm( array &$appData, array $args, string $content, array $context ): string {

			$key	= (string) ( $args['key'] ?? '' );
			$form	= \Nino\Form::form( $appData, preg_match( '/^[a-z][a-z0-9-]*$/D', $key ) === 1 ? $key : '' );

			if( $form === null )
				return '';

			$id			= self::_id( $appData, $form['key'] );
			$fields	= '';

			foreach( $form['fields'] as $field )
				$fields .= self::_field( $appData, $id, $field );

			$outcome	= \Nino\Form::outcome( (array) \Nino\Http::getRequest( $appData ) );
			$word			= $outcome === '' ? null : \Nino\Html::resolveTextfill( $appData, self::INFO. $outcome );
			$say			= is_string( $word ) === true && trim( $word ) !== '';

			// The subdirectory read from the configuration rather than through
			// the '[[/nino/dir]]' fill: that fill is registered mid-request (see
			// \Nino\request()), so a form drawn outside that window would carry the
			// literal in its action
			return \Nino\Components::fill( $appData, 'form', $args, $content, [
				'[[id]]'			=> \Nino\Components::escape( $id ),
				'[[form]]'		=> \Nino\Components::escape( $form['key'] ),
				'[[action]]'	=> \Nino\Components::escape( rtrim( \Nino\Filesystem::getDir( $appData ), '/' ). '/.form' ),
				'[[time]]'		=> (string) time(),
				'[[csrf]]'		=> \Nino\Html::renderHtml( $appData, '[csrf]' ),
				'[[fields]]'	=> $fields,
				'[[outcome]]'	=> $say === true ? ' data-form-outcome="'. $outcome. '"' : '',
				'[[message]]'	=> $say === true ? \Nino\Components::escape( (string) $word ) : '',
			] );
		}

		/**
		 *	The id a form is drawn with, which is the fragment a browser without the script is sent
		 *	back to: form-<key>, and form-<key>-2 for the same form drawn a second time on a page. At
		 *	most 64 characters, the shape the kernel takes an anchor in
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$key					The key of the form
		 *
		 *	@return 	string
		 */
		private static function _id( array &$appData, string $key ): string {

			$stem	= strlen( 'form-'. $key ) <= 60 ? 'form-'. $key : 'form-'. substr( sha1( $key ), 0, 12 );
			$n		= (int) ( $appData[ self::DRAWN ][ $stem ] ?? 0 ) + 1;

			$appData[ self::DRAWN ][ $stem ] = $n;

			return $n === 1 ? $stem : $stem. '-'. $n;
		}

		/**
		 *	One field of a form from the template of its kind. Every value is escaped
		 *	(\Nino\Components::escape()) before it goes in; the label and the options put the fills of
		 *	the site in first and are text after that
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$formId				The id of the form, the stem of the ids of its fields
		 *	@param		array 		$field				One normalized field
		 *
		 *	@return 	string
		 */
		private static function _field( array &$appData, string $formId, array $field ): string {

			$fieldId	= $formId. '-'. $field['name'];
			$required	= $field['required'] === true ? ' required' : '';
			$star			= $field['required'] === true ? strtr( self::STAR, [ '[[mark]]' => '*' ] ) : '';
			$label		= self::_words( $appData, $field['label'] );
			$id				= \Nino\Components::escape( $fieldId );
			$name			= \Nino\Components::escape( $field['name'] );

			if( $field['type'] === 'checkbox' )
				return str_replace(
					[ '[[id]]', '[[name]]', '[[required]]', '[[star]]', '[[label]]' ],
					[ $id, $name, $required, $star, $label ],
					\Nino\Feature::template( $appData, self::TEMPLATES, 'form-checkbox' )
				);

			if( $field['type'] === 'radio' ) {

				$options = '';

				foreach( $field['options'] as $number => $option )
					$options .= str_replace(
						[ '[[id]]', '[[name]]', '[[required]]', '[[value]]', '[[label]]' ],
						[ \Nino\Components::escape( $fieldId. '-'. ( $number + 1 ) ), $name, $required, \Nino\Components::escape( $option ), self::_words( $appData, $option ) ],
						\Nino\Feature::template( $appData, self::TEMPLATES, 'form-radio-option' )
					);

				return str_replace( [ '[[star]]', '[[label]]', '[[options]]' ], [ $star, $label, $options ], \Nino\Feature::template( $appData, self::TEMPLATES, 'form-radio' ) );
			}

			if( $field['type'] === 'textarea' )
				return str_replace( [ '[[id]]', '[[name]]', '[[required]]', '[[star]]', '[[label]]' ], [ $id, $name, $required, $star, $label ], \Nino\Feature::template( $appData, self::TEMPLATES, 'form-textarea' ) );

			if( $field['type'] === 'select' ) {

				$options = '';

				foreach( $field['options'] as $option )
					$options .= str_replace( [ '[[value]]', '[[label]]' ], [ \Nino\Components::escape( $option ), self::_words( $appData, $option ) ], \Nino\Feature::template( $appData, self::TEMPLATES, 'form-option' ) );

				return str_replace( [ '[[id]]', '[[name]]', '[[required]]', '[[star]]', '[[label]]', '[[options]]' ], [ $id, $name, $required, $star, $label, $options ], \Nino\Feature::template( $appData, self::TEMPLATES, 'form-select' ) );
			}

			// An input of its own type: text, email, tel, url, number and date. The hint a browser fills
			// from is one of a fixed few, never a value of the definition
			$hint = $field['type'] === 'email' ? 'email' : ( $field['type'] === 'tel' ? 'tel' : ( $field['name'] === 'name' ? 'name' : '' ) );

			return str_replace(
				[ '[[type]]', '[[id]]', '[[name]]', '[[required]]', '[[autocomplete]]', '[[star]]', '[[label]]' ],
				[ \Nino\Components::escape( $field['type'] ), $id, $name, $required, $hint === '' ? '' : ' autocomplete="'. $hint. '"', $star, $label ],
				\Nino\Feature::template( $appData, self::TEMPLATES, 'form-input' )
			);
		}

		/**
		 *	An editor's text as the page shows it: the fills in it put in as the fill
		 *	pass puts them - a fill is a word of the project's - and nothing else
		 *	read, so a shortcode in the text is characters and calls nothing. Then
		 *	escaped for text and for an attribute; what the renderer returns is
		 *	finished, nobody reads it again
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$text					What the editor wrote
		 *
		 *	@return 	string									Ready for markup
		 */
		private static function _words( array &$appData, string $text ): string {

			// The words a person wrote per language are stored as the text files keep them, with their quotes and brackets as
			// references: a fill is made characters again here, so that the one escape below is the only one
			$put = static function( array $match ) use ( &$appData ): string {
				$word = \Nino\Html::resolveTextfill( $appData, $match[1] );
				return $word === null ? $match[0] : self::characters( $word );
			};

			return \Nino\Components::escape( (string) preg_replace_callback( '#\[\[(/[a-zA-Z0-9/_.-]*)\]\]#', $put, $text ) );
		}

		/**
		 *	The words of a language as a person typed them: the four references the text files write for a quote and a bracket
		 *	(\Nino\Text::put() with the format plain) made characters again - and nothing else, so that what was typed as an entity stays
		 *	the entity it was
		 *
		 *	@param		string		$word					As a text file holds it
		 *
		 *	@return 	string
		 */
		public static function characters( string $word ): string {
			return strtr( $word, [ '&#039;' => "'", '&quot;' => '"', '&#91;' => '[', '&#93;' => ']' ] );
		}

		/**
		 *	Whether the submission came back faster than a person could have
		 *	filled the form in - minSeconds, 3 by default, 0 switches it off.
		 *	Only a form [form] drew carries the stamp: a hand-written one
		 *	posts without it and is not checked
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$stamp				The posted '_t'
		 *
		 *	@return 	bool
		 */
		private static function _tooFast( array &$appData, string $stamp ): bool {

			$min = (int) \Nino\Features::setting( $appData, 'forms', 'minSeconds', 3 );

			if( $min <= 0 || preg_match( '/^\d{1,10}$/D', $stamp ) !== 1 )
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

			// The ip is hashed by the counter: this file is a spam counter, not a
			// visitor log. A limit of 0 is no limit
			return \Nino\Feature::spent( $appData, self::COUNTER, \Nino\Http::getClientIp( $appData ), (int) \Nino\Features::setting( $appData, 'forms', 'rateLimit', 10 ) );
		}
	}

}
