<?php
declare(strict_types=1);

namespace Nino\Modules\Crm {

	/**
	 *	Api		The endpoint of the CRM, POST /crm/api: { "action": "crm-clients/list", "data": { ... } } in, the answer of the method of the action out, with the status, the body, the code, the params and
	 *				the field the workbench gives them. The kernel checks the token of the session (the route says csrf; the page sends it in X-CSRF-Token) and answers a refused one with the 403 and the
	 *				code csrf; no session is the 401 with the code session, a session without MANAGE_PERM the 403, an action the table does not hold the 404 with the code action, a body that is none the 400 with
	 *				the code crm_request. The methods an action reaches read their input as the workbench posts it, from $_POST['data']: the endpoint puts the json it was given there for the length of the call and
	 *				puts back what stood there. A method keeps its own guardPerm().
	 *
	 *				The table of the actions is entered, not written here: the core enters its own in init() and a package enters the actions it owns with add() - a map action => [ class, method ] and
	 *				the options package (the key of the feature: an action is named by it, <key>/<name>, or by a name that begins with <key>-), files (the actions that take a multipart file) and needs (the key of
	 *				one feature that has to be on, else the 409 <key>_off; a second feature is the action's own to ask). No other option is there: an option that is none is refused, with a warning, and none of the actions is entered. A line of the activity
	 *				log is written by the action itself, with made(): the dispatcher puts it into the log after an answer of 200, and a read writes none.
	 *
	 *	@package						Dape/Nino
	 */
	class Api {

		/**	The address of the endpoint	*/
		public const string ROUTE = '/crm/api';

		/**	The most nested a body may be: the endpoint takes small objects, never a tree	*/
		public const int DEPTH = 16;

		// What the module keeps for the request: the actions entered, the line of the log an action wrote
		private const string REGISTER = './crm/api/actions';
		private const string MADE = './crm/api/made';

		// The shape of the name of an action, and of an id the surface posts
		/**	The key the core enters its own actions under	*/
		public const string CORE = 'crm';

		private const string NAME = '~^[a-z][a-z0-9-]*(?:/[a-z0-9]+(?:-[a-z0-9]+)*)+$~D';
		private const string ID = '/^[1-9][0-9]{0,9}$/D';

		/**
		 *	The route and what answers it, and the actions of the core. Nothing is read or written
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	void
		 */
		public static function init( array &$appData ): void {

			$appData['/nino/http/routes']['POST:/'. self::ROUTE] = [ 'uri' => self::ROUTE, 'csrf' => true ];
			\Nino\Callbacks::registerCallback( $appData, '/nino/http/response/POST:/'. self::ROUTE, [ self::class, 'callbackApi' ] );

			// The core's own actions, each action on its own: one that is refused does not take the others with it. They are the core's whatever order the features start in: a package cannot enter a name of them
			foreach( self::core() as $list )
				foreach( $list as $action => $target )
					self::enter( $appData, [ $action => $target ], [ 'package' => self::CORE ] );
		}

		/**
		 *	The actions of the core, by the class that makes them
		 *
		 *	@return 	array											A list of action => [ class, method ] lists
		 */
		private static function core(): array {

			return [ Clients\Api::actions(), Projects\Api::actions(), Tasks\Api::actions(), Logbook\Api::actions(), Settings\Api::actions(), Overview::actions(), Notifications::actions(), Wipe\Api::actions(),
				[ 'crm/status' => [ Admin::class, 'apiStatus' ], 'crm/setup' => [ Admin::class, 'apiSetup' ] ], Values::actions(), Company::actions(), Surface::actions() ];
		}

		/**
		 *	The first words of the names of the actions of the core: crm, crm-clients, elements, text ... A package may not be one of them
		 *
		 *	@return 	array											The words
		 */
		public static function reserved(): array {

			$words = [ self::CORE ];

			foreach( self::core() as $list )
				foreach( array_keys( $list ) as $action )
					$words[] = explode( '/', (string) $action, 2 )[0];

			return array_values( array_unique( $words ) );
		}

		/**
		 *	Enter the actions of a package. The name of an action is the key of the package, a slash and the name of the action (crm-pdf/layout-get); the key is a feature that is on and none of the core's -
		 *	not crm, and not one of the first words of its actions. The core's actions are fixed and are none of this
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		$actions			action => [ class, method ], the method ( array &$appData, array &$request ): void
		 *	@param		array 		$opts					package (required), files (a list of the actions that take a file) and needs (a key of a feature; none)
		 *
		 *	@return 	bool											false, with a warning, where an action is refused - a name that is none, one that is not the package's, one another package has, a package that is not on or is the core's - and none of the list is entered then
		 */
		public static function add( array &$appData, array $actions, array $opts = [] ): bool {

			$package = $opts['package'] ?? null;
			$known = Register::package( $appData, $package );

			if( $known === null ) {

				trigger_error( 'Crm: the actions of a package were refused (package)', E_USER_WARNING );

				return false;
			}

			// A package answers with its own code: the class of a target is the feature's main class or one below it (CrmPdf for crm-pdf), never a panel of the workbench, the kernel or another package
			$space = $known['space'];

			foreach( $actions as $target )
				if( is_array( $target ) === false || is_string( $target[0] ?? null ) === false || ( ltrim( $target[0], '\\' ) !== $space && str_starts_with( ltrim( $target[0], '\\' ), $space. '\\' ) === false ) ) {

					trigger_error( 'Crm: the actions of a package were refused (target)', E_USER_WARNING );

					return false;
				}

			return self::enter( $appData, $actions, $opts );
		}

		/**
		 *	Enter actions: the work of add() and of the core, which names itself
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		$actions
		 *	@param		array 		$opts
		 *
		 *	@return 	bool
		 */
		private static function enter( array &$appData, array $actions, array $opts ): bool {

			$package = $opts['package'] ?? null;
			$files = $opts['files'] ?? [];
			$needs = $opts['needs'] ?? '';

			if( array_diff( array_keys( $opts ), [ 'package', 'files', 'needs' ] ) !== [] || Register::word( $package ) === false || is_array( $files ) === false || array_filter( $files, static fn( mixed $file ): bool => is_string( $file ) === false ) !== []
				|| is_string( $needs ) === false || ( $needs !== '' && Register::word( $needs ) === false ) || array_diff( $files, array_keys( $actions ) ) !== [] ) {

				trigger_error( 'Crm: the actions of a package were refused (options)', E_USER_WARNING );

				return false;
			}

			$list = [];

			foreach( $actions as $action => $target )
				$list[] = [ 'action' => (string) $action, 'target' => $target, 'package' => $package, 'files' => in_array( $action, $files, true ), 'needs' => $needs ];

			return Register::add( $appData, self::REGISTER, 'actions', $list, [
				'id' => 'action',
				'idRule' => self::NAME,
				'fields' => [ 'target' => Register::REQUIRED, 'package' => Register::REQUIRED, 'files' => false, 'needs' => '' ],
				'rules' => [ 'target' => static function( mixed $target ) use ( &$appData ): bool { return Register::callable( $appData, $target, 2, [ 0, 1 ] ); } ],
				'refuse' => static function( array $spec ): string {

					return $spec['package'] === self::CORE || explode( '/', (string) $spec['action'], 2 )[0] === $spec['package'] ? '' : 'name';
				},
			] );
		}

		/**
		 *	The actions that are entered
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	array											action => target, package, panel, files, needs
		 */
		public static function actions( array &$appData ): array {

			return Register::all( $appData, self::REGISTER );
		}

		/**
		 *	A line of the activity log from the request of a package, in the name of an account: what happened, never a name, an address or a text of a client. The package does not reach for the workbench for it
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$actor				The address of the account
		 *	@param		string		$line					One line; a break in it, or any control character, is a blank, and it is cut at 300 characters
		 *
		 *	@return 	bool											false where nothing was written: no line, no account, or no workbench
		 */
		public static function record( array &$appData, string $actor, string $line ): bool {

			$clean = static fn( string $text, int $most ): string => trim( (string) preg_replace( '/[\x00-\x1F\x7F\x{2028}\x{2029}]+/u', ' ', mb_substr( $text, 0, $most, 'UTF-8' ) ) );
			$actor = $clean( $actor, 200 );
			$line = $clean( $line, 300 );

			if( $actor === '' || $line === '' || Values::workbench( $appData ) === false )
				return false;

			\Nino\Modules\Crm\Values::log( $appData, $actor, $line );

			return true;
		}

		/**
		 *	The line of the activity log of the action that is running: what it did, never a name, an address or a text of a client. Written by the method of the action after it went through
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$line
		 *
		 *	@return 	void
		 */
		public static function made( array &$appData, string $line ): void {

			$appData[self::MADE] = (string) preg_replace( '/[\x00-\x1F\x7F]+/', ' ', mb_substr( $line, 0, 300, 'UTF-8' ) );
		}

		/**
		 *	Run an action over the database: a failure of it is a 500 with the code crm_database
		 *
		 *	@param		array 		&$request
		 *	@param		callable	$run
		 *
		 *	@return 	void
		 */
		public static function run( array &$request, callable $run ): void {

			try {
				$run();
			}
			catch( \PDOException $e ) {
				\Nino\Http::fail( $request, 500, 'the database failed', 'crm_database' );
			}
		}

		/**
		 *	The id a post names: a whole number from 1, at most ten digits, as text or as a number
		 *
		 *	@param		array 		&$request
		 *	@param		array 		$data
		 *	@param		string		$code					The code of the 400 for an id that is none
		 *	@param		string		$key
		 *
		 *	@return 	int|null									Null after the 400 that says why
		 */
		public static function id( array &$request, array $data, string $code, string $key = 'id' ): ?int {

			$id = \Nino\Modules\Crm\Values::string( $data, $key );

			if( preg_match( self::ID, $id ) !== 1 ) {
				\Nino\Http::fail( $request, 400, 'the id is not valid', $code, [], $key );
				return null;
			}

			return (int) $id;
		}

		/**
		 *	What the listeners of a hook answer, each asked on its own: every one is handed the argument as it is built here, and what it returns, unless that is null, takes the place of its argument. What
		 *	one listener makes of its argument is nothing of the others': a listener whose answer is none takes nothing away from the answer of another
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$hook
		 *	@param		array 		$args
		 *
		 *	@return 	array											The argument as each listener left it, in the order of the listeners
		 */
		public static function asked( array &$appData, string $hook, array $args ): array {

			$answers = [];

			foreach( \Nino\Callbacks::registered( $appData, $hook ) as $callback ) {
				$own = $args;
				$returned = call_user_func_array( $callback, [ &$appData, &$own ] );
				$answers[] = is_array( $returned ) === true ? $returned : $own;
			}

			return $answers;
		}

		/**
		 *	POST /crm/api: one action of the table, answered as the workbench answers it
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		&$request
		 *
		 *	@return 	void
		 */
		public static function callbackApi( array &$appData, array &$request ): void {

			$request['/nino/http/response']['header']['Cache-Control'] = 'no-store';

			// The methods of the actions are written against the shell of the workbench, which only its own entry point loads
			if( Values::workbench( $appData ) === false ) {
				\Nino\Http::fail( $request, 500, 'the workbench is not there', 'crm_workbench' );
				return;
			}

			// The token was refused before this callback ran: say it in the way the page reacts to
			if( (int) ( $request['/nino/http/response']['statusCode'] ?? 0 ) !== 200 ) {
				\Nino\Modules\Crm\Values::blocked( $request );
				return;
			}

			$user = \Nino\Auth::getCurrentUser( $appData );

			if( $user === false ) {
				\Nino\Http::fail( $request, 401, 'not logged in', 'session' );
				return;
			}

			if( \Nino\Auth::checkPermission( $appData, \Nino\Modules\Crm::MANAGE_PERM ) === false ) {
				\Nino\Http::fail( $request, 403, 'not allowed' );
				return;
			}

			$actions = self::actions( $appData );
			$type = strtolower( (string) ( $request['/nino/http/request']['header']['Content-Type'] ?? '' ) );

			// A picture larger than the server takes: the token came in the header, so the kernel let it pass, and php dropped the whole form - no field, no action
			if( $_POST === [] && str_starts_with( $type, 'multipart/form-data' ) === true && self::oversized( $request ) === true ) {
				\Nino\Modules\Crm\Values::blocked( $request );
				return;
			}

			$body = json_decode( (string) ( $request['/nino/http/request']['body'] ?? '' ), true, self::DEPTH );
			$posted = $_POST;

			// A file comes as a multipart post whose fields php has read already: the action and the data are the fields. Only the actions that take a file are read that way
			if( is_array( $body ) === false && is_string( $posted['action'] ?? null ) === true && ( $actions[$posted['action']]['files'] ?? false ) === true )
				$body = [ 'action' => $posted['action'], 'data' => json_decode( is_string( $posted['data'] ?? null ) === true ? $posted['data'] : '', true, self::DEPTH ) ];

			$action = is_array( $body ) === true && is_string( $body['action'] ?? null ) === true ? $body['action'] : '';

			if( isset( $actions[$action] ) === false ) {
				\Nino\Http::fail( $request, 404, 'unknown action', 'action' );
				return;
			}

			// What is posted for an action is an object (or nothing): a text, a number or a flag in its place is a body that is none, and no action is run on it
			if( array_key_exists( 'data', $body ) === true && is_array( $body['data'] ) === false && $body['data'] !== null ) {
				\Nino\Http::fail( $request, 400, 'the data of the action is no object', 'crm_request', [], 'data' );
				return;
			}

			$needs = (string) $actions[$action]['needs'];

			if( $needs !== '' && \Nino\Modules\Crm::featureOn( $appData, $needs ) === false ) {
				\Nino\Http::fail( $request, 409, 'the feature '. $needs. ' is not active', str_replace( '-', '_', $needs ). '_off' );
				return;
			}

			// What the methods read: the posted action and data, as the workbench posts them - and what stood there is put back, so that the next call in this process does not see this one's
			$_POST['action'] = $action;
			$_POST['data'] = json_encode( is_array( $body['data'] ?? null ) === true ? $body['data'] : [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR );
			unset( $appData[self::MADE] );

			try {
				[ $class, $method ] = $actions[$action]['target'];
				$class::{$method}( $appData, $request );
			}
			finally {
				$_POST = $posted;
			}

			$line = self::taken( $appData );

			if( self::status( $request ) === 200 && $line !== '' )
				\Nino\Modules\Crm\Values::log( $appData, (string) $user['mail'], $line );
		}

		/**
		 *	The line of the log an action left in the app data, which is taken out of it
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	string
		 */
		private static function taken( array &$appData ): string {

			$line = (string) ( $appData[self::MADE] ?? '' );
			unset( $appData[self::MADE] );

			return $line;
		}

		/**
		 *	The status the request has been answered with so far
		 *
		 *	@param		array 		$request
		 *
		 *	@return 	int
		 */
		private static function status( array $request ): int {

			return (int) ( $request['/nino/http/response']['statusCode'] ?? 0 );
		}

		/**
		 *	Whether the request says it is larger than the server takes (post_max_size): then php has dropped its body and its fields
		 *
		 *	@param		array 		$request
		 *
		 *	@return 	bool
		 */
		private static function oversized( array $request ): bool {

			$length = $request['/nino/http/request']['header']['Content-Length'] ?? '';
			$post = \Nino\Images::limits()['post'];

			return $post > 0 && is_string( $length ) === true && ctype_digit( $length ) === true && (int) $length > $post;
		}
	}
}
