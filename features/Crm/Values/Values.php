<?php
declare(strict_types=1);

namespace Nino\Modules\Crm {

	/**
	 *	Values		The way into the Elements panel of the workbench, and the one class of the core that takes it: what the screens of the values and of the projects take from the panel are its actions -
	 *						elements/types, list, get, save and delete - answered under the permission of the panel, with the sentences of the panel's codes. This is a bridge until the kernel has a public
	 *						guard and a public element API: the shell of the workbench (\Nino\Admin\Admin, which guardPerm(), postData() and postString() belong to) is a file only the entry point of the
	 *						workbench loads, so workbench() loads it, and the api methods of \Nino\Modules\Elements\Admin are called as they are. The guard of the panel starts the daily backup and brings the base texts
	 *						up to date, as it does for the workbench; a call through here does the same.
	 *
	 *						The actions are for the element types that were entered with addType() and for no other: the core enters crm-projects (a screen of its own) and crm-phases (the screen of the values), a
	 *						package enters its own. A post that names another type, or none, is refused before the panel is asked (404 crm_values_type), so that the endpoint never becomes an editor for the elements
	 *						of the site. A refusal of the panel that has no code gets one here (coded()), and each sentence of the panel that is translated is pinned by a request.
	 *
	 *						Two things the panel does not know are the core's: a project that a package holds (Projects::holds()) is not deleted and its client does not change while it does, and a phase has a key, a
	 *						title and an order of the shape the screens take. What a package wants of a save is asked with the hook CHECK ('/crm/element/check', [ type, data, refusal ]; a refusal is [ status 400-499,
	 *						message, code, params, field ]) and what a new element of a type starts with with the hook START ('/crm/element/start', [ type, defaults, hidden ]).
	 *
	 *	@package						Dape/Nino
	 */
	class Values {

		/**	The permission of the panel: the one the actions ask for	*/
		public const string PERM = '/_admin/elements/manage';

		/**	The hooks a save is checked with and a new element starts with	*/
		public const string CHECK = '/crm/element/check';
		public const string START = '/crm/element/start';

		/**	The types of the core: the projects, which have a screen of their own, and the phases, which the screen of the values edits	*/
		public const string PROJECTS = 'crm-projects';
		public const string PHASES = 'crm-phases';

		// The actions of the panel that are offered, and the method that answers each
		private const array ACTIONS = [ 'elements/types' => 'apiTypes', 'elements/list' => 'apiList', 'elements/get' => 'apiGet', 'elements/save' => 'apiSave', 'elements/delete' => 'apiDelete' ];

		// The types that were entered, and what a save of a project posted for the hook of the project that was made
		private const string REGISTER = './crm/values/types';

		/**
		 *	Make sure the shell of the workbench is loaded: \Nino\Admin\Admin and its Panels are classes of _admin/Admin.php, which declares them and runs nothing. The public entry point does not load it;
		 *	_admin/index.php does
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	bool											Whether the classes are there
		 */
		public static function workbench( array &$appData ): bool {

			if( class_exists( '\\Nino\\Admin\\Admin', false ) === true )
				return true;

			$file = \Nino\Filesystem::path( $appData, '/_admin/Admin.php' );

			if( is_file( $file ) === false )
				return false;

			require_once $file;

			return true;
		}

		/**
		 *	The language the workbench speaks in this session, the one the page /crm speaks too: the grip into the workbench is this class's alone, so the surface asks here
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	string										A locale of the site; its native locale where the workbench cannot be loaded
		 */
		public static function locale( array &$appData ): string {

			return self::workbench( $appData ) === true ? \Nino\Admin\Admin::sessionLocale( $appData ) : \Nino\Locales::getNativeLocale( $appData );
		}

		/**
		 *	The one bridge to the helpers of the workbench: every other class of the feature calls these, so that the day the helpers have a public guard they are replaced in one place. They need the shell loaded
		 *	(workbench()), which the endpoint does before it runs an action
		 */

		/**
		 *	@param		array 		&$appData
		 *	@param		array 		&$request
		 *	@param		string		$perm
		 *
		 *	@return 	bool											Whether the account holds the permission - the refusal is the answer where not
		 */
		public static function permit( array &$appData, array &$request, string $perm ): bool {

			return \Nino\Admin\Admin::guardPerm( $appData, $request, $perm );
		}

		/**
		 *	@return 	array											The data of the action as the workbench posts them
		 */
		public static function posted(): array {

			return \Nino\Admin\Admin::postData();
		}

		/**
		 *	@param		array 		$data
		 *	@param		string		$key
		 *	@param		string		$default
		 *
		 *	@return 	string
		 */
		public static function string( array $data, string $key, string $default = '' ): string {

			return \Nino\Admin\Admin::postString( $data, $key, $default );
		}

		/**
		 *	One line of the activity log, as it is given: @internal for the endpoint, the deletion of a client and the test phase, which have made the line themselves. A package writes its line through Api::record(),
		 *	which cleans it
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$actor
		 *	@param		string		$line
		 *
		 *	@return 	void
		 */
		public static function log( array &$appData, string $actor, string $line ): void {

			\Nino\Admin\Admin::record( $appData, $actor, $line );
		}

		/**
		 *	@param		array 		&$request
		 *
		 *	@return 	void
		 */
		public static function blocked( array &$request ): void {

			\Nino\Admin\Admin::failBlocked( $request );
		}

		/**
		 *	@param		array 		&$appData
		 *	@param		string		$prefix
		 *	@param		string		$perm
		 *
		 *	@return 	bool
		 */
		public static function scoped( array &$appData, string $prefix, string $perm ): bool {

			return \Nino\Admin\Admin::scoped( $appData, $prefix, $perm );
		}

		/**
		 *	@param		array 		&$appData
		 *
		 *	@return 	array											The languages of the texts
		 */
		public static function locales( array &$appData ): array {

			return \Nino\Admin\Admin::textLocales( $appData );
		}

		/**
		 *	A word of the panel's own texts in the language the account works in
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$key					[[/_admin/...]]
		 *
		 *	@return 	string										'' where there is none, or the shell is not there
		 */
		public static function word( array &$appData, string $key ): string {

			if( self::workbench( $appData ) === false )
				return '';

			return (string) ( \Nino\Admin\Admin::textFills( $appData, \Nino\Admin\Panels::relative( dirname( __DIR__ ). '/text' ), \Nino\Admin\Admin::sessionLocale( $appData ) )[$key] ?? '' );
		}

		/**
		 *	Enter an element type the actions are for
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		$spec					type (an element type: lower case words joined by hyphens), screen (values: the screen of the values edits it and elements/types offers it; own: the type has a screen of its own), rank (the order, 500 by default) and package (the key of the feature that enters it, see Register::package())
		 *
		 *	@return 	bool											false, with a warning, where the description is refused - a type another package has among them - and nothing is entered
		 */
		public static function addType( array &$appData, array $spec ): bool {

			return self::enter( $appData, $spec, false );
		}

		/**
		 *	@param		array 		&$appData
		 *	@param		array 		$spec
		 *	@param		bool			$core					The core enters its own types
		 *
		 *	@return 	bool
		 */
		private static function enter( array &$appData, array $spec, bool $core ): bool {

			return Register::add( $appData, self::REGISTER, 'element types', [ $spec ], [
				'id' => 'type',
				'idRule' => static fn( string $type ): bool => Register::word( $type, 60 ),
				'fields' => [ 'screen' => 'values', 'rank' => 500, 'package' => Register::REQUIRED ],
				'rules' => [
					'screen' => static fn( mixed $screen ): bool => in_array( $screen, [ 'values', 'own' ], true ),
					'rank' => static fn( mixed $rank ): bool => is_int( $rank ),
					'package' => Register::packageRule( $appData, $core ),
				],
			] );
		}

		/**
		 *	The types that were entered, by their rank and then the order they were entered in
		 *
		 *	@param		array 		&$appData
		 *	@param		string|null	$screen				Only the types of this screen
		 *
		 *	@return 	array											A list of type names
		 */
		public static function types( array &$appData, ?string $screen = null ): array {

			$types = Register::all( $appData, self::REGISTER );
			uasort( $types, static fn( array $a, array $b ): int => [ $a['rank'], $a[Register::SEQ] ] <=> [ $b['rank'], $b[Register::SEQ] ] );

			return array_map( 'strval', array_keys( array_filter( $types, static fn( array $type ): bool => $screen === null || $type['screen'] === $screen ) ) );
		}

		/**
		 *	The types of the core
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	void
		 */
		public static function init( array &$appData ): void {

			self::enter( $appData, [ 'type' => self::PROJECTS, 'screen' => 'own', 'rank' => 100, 'package' => Api::CORE ], true );
			self::enter( $appData, [ 'type' => self::PHASES, 'screen' => 'values', 'rank' => 200, 'package' => Api::CORE ], true );
		}

		/**
		 *	The actions of the panel the endpoint answers here
		 *
		 *	@return 	array											action => [ class, method ]
		 */
		public static function actions(): array {

			$actions = [];

			foreach( self::ACTIONS as $action => $method )
				$actions[$action] = [ self::class, $method ];

			return $actions;
		}

		/**
		 *	The guard of every action: the permission of the panel first (a 403 as the panel gives it), then the type
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		&$request
		 *
		 *	@return 	bool											Whether the panel may be asked
		 */
		private static function guard( array &$appData, array &$request ): bool {

			if( \Nino\Admin\Admin::guardPerm( $appData, $request, self::PERM ) === false )
				return false;

			if( in_array( \Nino\Admin\Admin::postString( \Nino\Admin\Admin::postData(), 'type' ), self::types( $appData ), true ) === false ) {
				\Nino\Http::fail( $request, 404, 'these actions are for the element types that were entered', 'crm_values_type' );
				return false;
			}

			return true;
		}

		/**
		 *	The line of the log of a save and a delete as the panel phrases it
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		$request
		 *	@param		string		$action
		 *
		 *	@return 	void
		 */
		private static function logged( array &$appData, array $request, string $action ): void {

			if( (int) ( $request['/nino/http/response']['statusCode'] ?? 0 ) === 200 )
				Api::made( $appData, \Nino\Modules\Elements\Admin::log( $action, \Nino\Admin\Admin::postData() ) );
		}

		/**
		 *	The types of the screen of the values with their definition: the panel's answer cut to the types of that screen, in their rank, and with each type what a new element starts with and what
		 *	the form leaves out (defaults, hidden), missing - the types of the screen that the project has not - and native, the language the screen writes in
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		&$request
		 *
		 *	@return 	void
		 */
		public static function apiTypes( array &$appData, array &$request ): void {

			if( \Nino\Admin\Admin::guardPerm( $appData, $request, self::PERM ) === false )
				return;

			\Nino\Modules\Elements\Admin::apiTypes( $appData, $request );

			$body = $request['/nino/http/response']['body'] ?? null;

			if( (int) ( $request['/nino/http/response']['statusCode'] ?? 200 ) >= 400 || is_array( $body ) === false || is_array( $body['types'] ?? null ) === false )
				return;

			$wanted = self::types( $appData, 'values' );
			$found = [];

			foreach( $body['types'] as $type )
				if( is_array( $type ) === true && in_array( $type['type'] ?? null, $wanted, true ) )
					$found[(string) $type['type']] = $type;

			$types = [];
			$native = \Nino\Locales::getNativeLocale( $appData );
			$fills = \Nino\Html::getFills( $appData, $native );
			$own = \Nino\Admin\Admin::textFills( $appData, \Nino\Admin\Panels::relative( dirname( __DIR__ ). '/text' ), $native );

			// The note of a type is the text the project says for it, or else the panel's own, in the language of the installation: '' where there is none
			foreach( $wanted as $name )
				if( isset( $found[$name] ) === true ) {
					$key = '[[/_admin/elements/type/'. $name. '/hint]]';
					$hint = is_string( $fills[$key] ?? null ) === true && $fills[$key] !== '' ? $fills[$key] : ( $own[$key] ?? '' );
					$types[] = $found[$name] + [ 'hint' => is_string( $hint ) === true ? $hint : '' ] + self::startOf( $appData, $name );
				}

			$request['/nino/http/response']['body'] = [ 'types' => $types, 'missing' => array_values( array_diff( $wanted, array_keys( $found ) ) ), 'native' => \Nino\Locales::getNativeLocale( $appData ) ] + $body;
		}

		/**
		 *	What a new element of a type starts with and what the form leaves out, where a package says so through the hook START. Without a listener a form starts empty and hides nothing
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$type
		 *
		 *	@return 	array											defaults (field => value) and hidden (the fields the form does not draw, does not post and does not list)
		 */
		private static function startOf( array &$appData, string $type ): array {

			$start = [ 'defaults' => [], 'hidden' => [] ];

			foreach( Api::asked( $appData, self::START, [ 'type' => $type ] + $start ) as $answer ) {
				$start['defaults'] = array_merge( $start['defaults'], is_array( $answer['defaults'] ?? null ) === true ? $answer['defaults'] : [] );
				$start['hidden'] = array_values( array_unique( array_merge( $start['hidden'], is_array( $answer['hidden'] ?? null ) === true ? array_map( 'strval', array_filter( $answer['hidden'], 'is_string' ) ) : [] ) ) );
			}

			return $start;
		}

		/**
		 *	@param		array 		&$appData
		 *	@param		array 		&$request
		 *
		 *	@return 	void
		 */
		public static function apiList( array &$appData, array &$request ): void {

			if( self::guard( $appData, $request ) === false )
				return;

			\Nino\Modules\Elements\Admin::apiList( $appData, $request );
			self::coded( $appData, $request );
		}

		/**
		 *	@param		array 		&$appData
		 *	@param		array 		&$request
		 *
		 *	@return 	void
		 */
		public static function apiGet( array &$appData, array &$request ): void {

			if( self::guard( $appData, $request ) === false )
				return;

			\Nino\Modules\Elements\Admin::apiGet( $appData, $request );
			self::coded( $appData, $request );
		}

		/**
		 *	@param		array 		&$appData
		 *	@param		array 		&$request
		 *
		 *	@return 	void
		 */
		public static function apiSave( array &$appData, array &$request ): void {

			if( self::guard( $appData, $request ) === false || self::projectSaved( $appData, $request ) === false || self::phaseSaved( $appData, $request ) === false || self::checked( $appData, $request ) === false )
				return;

			\Nino\Modules\Elements\Admin::apiSave( $appData, $request );

			$data = \Nino\Admin\Admin::postData();

			// A project that was made is told to the packages, with what the screen posted for them: a package that refuses takes it back, and the save is none
			if( (int) ( $request['/nino/http/response']['statusCode'] ?? 0 ) === 200 && ( $data['isNew'] ?? false ) === true && \Nino\Admin\Admin::postString( $data, 'type' ) === self::PROJECTS ) {

				$body = $request['/nino/http/response']['body'] ?? null;
				$id = is_array( $body ) === true && is_array( $body['element'] ?? null ) === true ? \Nino\Elements::getElementUriFromUri( (string) ( $body['element']['.uri'] ?? '' ) ) : '';
				$posted = is_array( $data['packages'] ?? null ) === true && array_is_list( $data['packages'] ) === false ? $data['packages'] : [];
				$refusal = null;

				// The project is made whole or not at all: its line in the logbook is there, and the packages have been told and none of them broke - else it is taken back and the answer says what failed
				try {
					if( $id !== '' && Db::row( $appData, 'SELECT id FROM logbook WHERE project = :project AND source = :source AND kind = \'project-added\'', [ 'project' => $id, 'source' => Logbook::SOURCE ] ) === null )
						$refusal = [ 'status' => 500, 'message' => 'the logbook of the project could not be written', 'code' => 'crm_database', 'params' => [], 'field' => '' ];
					elseif( $id !== '' )
						$refusal = Projects::tell( $appData, $id, is_string( $data['fields']['client'] ?? null ) === true ? $data['fields']['client'] : '', $posted );
				}
				catch( \Throwable $e ) {
					trigger_error( 'Crm: a package that was told of the project '. $id. ' broke', E_USER_WARNING );
					$refusal = [ 'status' => 500, 'message' => 'the packages could not be told of the project', 'code' => 'crm_database', 'params' => [], 'field' => '' ];
				}

				if( $refusal !== null ) {

					\Nino\Elements::deleteElement( $appData, '/'. self::PROJECTS. '/'. $id, '*' );

					try {
						Db::exec( $appData, 'DELETE FROM logbook WHERE project = :project', [ 'project' => $id ] );
					}
					catch( \PDOException $e ) {
						trigger_error( 'Crm: the logbook of the project '. $id. ' could not be cleared', E_USER_WARNING );
					}

					\Nino\Http::fail( $request, $refusal['status'], $refusal['message'], $refusal['code'], $refusal['params'], $refusal['field'] );
					return;
				}
			}

			self::coded( $appData, $request );
			self::logged( $appData, $request, 'elements/save' );
		}

		/**
		 *	@param		array 		&$appData
		 *	@param		array 		&$request
		 *
		 *	@return 	void
		 */
		public static function apiDelete( array &$appData, array &$request ): void {

			if( self::guard( $appData, $request ) === false || self::projectDeleted( $appData, $request ) === false || self::phaseDeleted( $appData, $request ) === false )
				return;

			$id = self::projectId( $appData );

			if( $id === '' ) {
				\Nino\Modules\Elements\Admin::apiDelete( $appData, $request );
				self::coded( $appData, $request );
				self::logged( $appData, $request, 'elements/delete' );
				return;
			}

			self::deleteProject( $appData, $request, $id );
		}

		/**
		 *	The id of the project the post asks to delete, or '' where it asks for another element
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	string
		 */
		private static function projectId( array &$appData ): string {

			$data = \Nino\Admin\Admin::postData();
			$uri = \Nino\Admin\Admin::postString( $data, 'uri' );

			return \Nino\Admin\Admin::postString( $data, 'type' ) === self::PROJECTS && preg_match( Projects::ID, $uri ) === 1 ? $uri : '';
		}

		/**
		 *	Delete a project: the rows of the core and the element go together or not at all. The transaction is opened first - where it cannot be (the file is held), the element is not touched and the answer
		 *	is 503 crm_busy -, the rows are deleted, then the element; where the panel deletes it the transaction is committed and the packages are told, else it is rolled back and the rows are as they were
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		&$request
		 *	@param		string		$id
		 *
		 *	@return 	void
		 */
		private static function deleteProject( array &$appData, array &$request, string $id ): void {

			try {
				$pdo = Db::pdo( $appData );
				$pdo->exec( 'BEGIN IMMEDIATE' );
			}
			catch( \PDOException $e ) {
				\Nino\Http::fail( $request, 503, 'the database is held by another process', 'crm_busy' );
				$request['/nino/http/response']['header']['Retry-After'] = '5';
				return;
			}

			$appData[Projects::DELETING] = $id;

			try {
				Projects::dropRows( $appData, $id );
				\Nino\Modules\Elements\Admin::apiDelete( $appData, $request );
				$through = (int) ( $request['/nino/http/response']['statusCode'] ?? 0 ) === 200;
				$committed = $through === false;

				if( $through === false )
					$pdo->exec( 'ROLLBACK' );

				// The commit needs the lock the readers hold off: it is tried for the whole of the wait twice. The element is gone by then, and a commit that does not go through leaves the rows to be cleared
				// (Projects::sweep()) - the answer says the project is deleted
				for( $try = 0; $through === true && $committed === false && $try < 2; $try++ )
					try {
						$pdo->exec( 'COMMIT' );
						$committed = true;
					}
					catch( \PDOException $e ) {
						continue;
					}

				if( $through === true && $committed === false ) {
					$pdo->exec( 'ROLLBACK' );
					unset( $appData[Projects::DELETING] );
					$request['/nino/http/response']['body'] = [ 'ok' => true, 'leftover' => true ];
					trigger_error( 'Crm: the rows of the project '. $id. ' stay until they are cleared', E_USER_WARNING );
					self::logged( $appData, $request, 'elements/delete' );
					return;
				}
			}
			catch( \Throwable $e ) {
				try {
					$pdo->exec( 'ROLLBACK' );
				}
				catch( \PDOException $ignored ) {
				}

				unset( $appData[Projects::DELETING] );
				\Nino\Http::fail( $request, 500, 'the database failed', 'crm_database' );
				return;
			}

			unset( $appData[Projects::DELETING] );

			if( $through === true )
				Projects::forgotten( $appData, $id );

			self::coded( $appData, $request );
			self::logged( $appData, $request, 'elements/delete' );
		}

		/**
		 *	A refusal of the panel that has no code gets one - the panel answers some of its refusals with the sentence of the kernel alone -, and the field it is about where it names one, so that the page
		 *	says it in its own words at the field. The sentences that are recognised are those of the panel and of the kernel's element store as they are today; each is pinned by a request
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		&$request					The answer of the panel
		 *
		 *	@return 	void
		 */
		private static function coded( array &$appData, array &$request ): void {

			$body = $request['/nino/http/response']['body'] ?? null;

			if( (int) ( $request['/nino/http/response']['statusCode'] ?? 200 ) < 400 || is_array( $body ) === false || isset( $body['code'] ) === true || is_string( $body['error'] ?? null ) === false )
				return;

			$error = $body['error'];
			$data = \Nino\Admin\Admin::postData();
			$field = '';

			if( $error === 'invalid type, uri or locale' || $error === 'invalid type or uri' ) {

				$code = match( true ) {
					$error === 'invalid type, uri or locale' && \Nino\Locales::verifyLocale( $appData, \Nino\Admin\Admin::postString( $data, 'locale' ) ) === false => 'locale',
					default => 'uri',
				};
				$field = $code;
				$code = 'crm_elements_'. $code;
			}
			elseif( str_starts_with( $error, 'not allowed to' ) === true )
				$code = 'crm_elements_denied';
			elseif( str_contains( $error, 'does not exist' ) === true || $error === 'unknown type or element' || $error === 'unknown type' || $error === 'element not found' ) {
				$code = 'crm_elements_unknown';
				$field = 'uri';
			}
			elseif( str_contains( $error, 'already exists' ) === true ) {
				$code = 'crm_elements_exists';
				$field = 'uri';
			}
			elseif( preg_match( '/^Element reference \'([^\']+)\'/', $error, $found ) === 1 ) {
				$code = 'crm_elements_value';
				$field = $found[1];
			}
			elseif( (int) $request['/nino/http/response']['statusCode'] === 409 && self::elementStands( $appData, $data ) === true )
				$code = 'crm_elements_vetoed';
			else
				$code = 'crm_elements_failed';

			$request['/nino/http/response']['body']['code'] = $code;

			if( $field !== '' )
				$request['/nino/http/response']['body']['field'] = $field;
		}

		/**
		 *	Whether the element a post names is still there: a refusal of the panel that leaves it is a veto - the reason is the listener's sentence, which the answer carries - and one that does not is a failure
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		$data
		 *
		 *	@return 	bool
		 */
		private static function elementStands( array &$appData, array $data ): bool {

			$type = \Nino\Admin\Admin::postString( $data, 'type' );
			$uri = \Nino\Admin\Admin::postString( $data, 'uri' );

			if( $type === '' || $uri === '' )
				return false;

			foreach( \Nino\Elements::queryElements( $appData, '/'. $type, [], '*', [] ) as $element )
				if( \Nino\Elements::getElementUriFromUri( (string) ( $element['.uri'] ?? '' ) ) === $uri )
					return true;

			return false;
		}

		/**
		 *	What a save of a project must hold to: the client it names is a client, and it is the client it was while a package holds the project. A project that is new gets its number
		 *	from the counter of the type, which is lifted to the start number of the projects first (Projects::lift())
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		&$request
		 *
		 *	@return 	bool											Whether the panel may be asked
		 */
		private static function projectSaved( array &$appData, array &$request ): bool {

			$data = \Nino\Admin\Admin::postData();

			if( \Nino\Admin\Admin::postString( $data, 'type' ) !== self::PROJECTS || is_array( $data['fields'] ?? null ) === false || array_key_exists( 'client', $data['fields'] ) === false )
				return true;

			$number = is_string( $data['fields']['client'] ) === true ? $data['fields']['client'] : '';
			$allowed = false;

			Api::run( $request, static function() use ( &$appData, &$request, $data, $number, &$allowed ): void {

				if( $number === '' || Clients::byNumber( $appData, $number ) === null ) {
					\Nino\Http::fail( $request, 400, 'the client is none', 'crm_project_client_unknown', [], 'client' );
					return;
				}

				$id = \Nino\Admin\Admin::postString( $data, 'uri' );
				$before = ( $data['isNew'] ?? false ) === true ? null : Projects::get( $appData, $id );
				$hold = $before !== null && $before['client'] !== $number ? Projects::held( $appData, $id, 'client' ) : null;

				if( $hold !== null ) {
					self::refuse( $request, $hold, 'client' );
					return;
				}

				if( ( $data['isNew'] ?? false ) === true )
					Projects::lift( $appData );

				$allowed = true;
			} );

			return $allowed;
		}

		/**
		 *	What a save of a phase must hold to: the key of a new one (its uri) is lower-case words divided by single hyphens, thirty characters at most - the key the rows of the projects hold and the
		 *	pages of a package write into an attribute -, a title is one line of sixty characters at most and the order a whole number from 0 to a million
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		&$request
		 *
		 *	@return 	bool											Whether the panel may be asked
		 */
		private static function phaseSaved( array &$appData, array &$request ): bool {

			$data = \Nino\Admin\Admin::postData();

			if( \Nino\Admin\Admin::postString( $data, 'type' ) !== self::PHASES )
				return true;

			if( ( $data['isNew'] ?? false ) === true && preg_match( Projects::PHASE_KEY, \Nino\Admin\Admin::postString( $data, 'uri' ) ) !== 1 ) {
				\Nino\Http::fail( $request, 400, 'the key of a phase is lower-case words divided by hyphens', 'crm_phase_key', [], 'uri' );
				return false;
			}

			$fields = is_array( $data['fields'] ?? null ) === true ? $data['fields'] : [];

			if( array_key_exists( 'title', $fields ) === true && ( is_string( $fields['title'] ) === false || Projects::phaseTitleOk( $fields['title'] ) === false ) ) {
				\Nino\Http::fail( $request, 400, 'the title of a phase is a line of at most sixty characters', 'crm_phase_title', [], 'title' );
				return false;
			}

			if( array_key_exists( 'order', $fields ) === true && Projects::phaseOrderOk( $fields['order'] ) === false ) {
				\Nino\Http::fail( $request, 400, 'the order of a phase is a whole number from 0 to '. Projects::PHASE_ORDER, 'crm_phase_order', [], 'order' );
				return false;
			}

			return true;
		}

		/**
		 *	What a save of an element must hold to that a package knows of: the hook CHECK is asked with the type and what was posted, and a refusal it answers is the answer of the endpoint. Without a listener nothing is refused
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		&$request
		 *
		 *	@return 	bool											Whether the panel may be asked
		 */
		private static function checked( array &$appData, array &$request ): bool {

			$data = \Nino\Admin\Admin::postData();

			foreach( Api::asked( $appData, self::CHECK, [ 'type' => \Nino\Admin\Admin::postString( $data, 'type' ), 'data' => $data, 'refusal' => null ] ) as $answer ) {

				$refusal = $answer['refusal'] ?? null;

				if( is_array( $refusal ) === true && is_int( $refusal['status'] ?? null ) === true && $refusal['status'] >= 400 && $refusal['status'] <= 499 && is_string( $refusal['message'] ?? null ) === true
					&& is_string( $refusal['code'] ?? null ) === true && is_array( $refusal['params'] ?? null ) === true && is_string( $refusal['field'] ?? null ) === true ) {

					\Nino\Http::fail( $request, $refusal['status'], $refusal['message'], $refusal['code'], $refusal['params'], $refusal['field'] );

					return false;
				}
			}

			return true;
		}

		/**
		 *	A phase that a project is in is not deleted: the row of the project holds its key, and the project would be in a phase nobody can name. The answer carries the number of the projects
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		&$request
		 *
		 *	@return 	bool											Whether the panel may be asked
		 */
		private static function phaseDeleted( array &$appData, array &$request ): bool {

			$data = \Nino\Admin\Admin::postData();

			if( \Nino\Admin\Admin::postString( $data, 'type' ) !== self::PHASES )
				return true;

			$count = null;

			Api::run( $request, static function() use ( &$appData, $data, &$count ): void {
				$count = Projects::inPhase( $appData, \Nino\Admin\Admin::postString( $data, 'uri' ) );
			} );

			if( $count === null )
				return false;

			if( $count > 0 ) {
				\Nino\Http::fail( $request, 409, 'projects are in this phase', 'crm_phase_used', [ $count ] );
				return false;
			}

			return true;
		}

		/**
		 *	A project that a package holds is not deleted, and neither is one a record of the evidence names: what names it would name a project nobody can find
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		&$request
		 *
		 *	@return 	bool											Whether the panel may be asked
		 */
		private static function projectDeleted( array &$appData, array &$request ): bool {

			$data = \Nino\Admin\Admin::postData();

			if( \Nino\Admin\Admin::postString( $data, 'type' ) !== self::PROJECTS )
				return true;

			$hold = null;
			$asked = false;
			$named = false;

			Api::run( $request, static function() use ( &$appData, $data, &$hold, &$asked, &$named ): void {
				$uri = \Nino\Admin\Admin::postString( $data, 'uri' );
				$hold = Projects::held( $appData, $uri, 'delete' );
				$asked = true;
				$named = Records::namesProject( $appData, $uri );
			} );

			if( $asked === false )
				return false;

			if( $hold !== null ) {
				self::refuse( $request, $hold, 'delete' );
				return false;
			}

			if( $named === true ) {
				\Nino\Http::fail( $request, 409, 'a record of the evidence names this project', 'crm_project_records' );
				return false;
			}

			return true;
		}

		/**
		 *	The refusal of a change of a project that a package holds: 409, the sentence of the hold, and the code the hold brings (delete_code, client_code), else the code of the change with the key of the
		 *	package and the count as params
		 *
		 *	@param		array 		&$request
		 *	@param		array 		$hold					As Projects::holds() has an entry
		 *	@param		string		$change				delete or client
		 *
		 *	@return 	void
		 */
		private static function refuse( array &$request, array $hold, string $change ): void {

			$own = (string) $hold[$change. '_code'];
			$code = $own !== '' ? $own : ( $change === 'client' ? 'crm_project_client_held' : 'crm_project_held' );
			$params = $own !== '' ? [ $hold['count'] ] : [ $hold['key'], $hold['count'] ];

			if( $change === 'client' )
				\Nino\Http::fail( $request, 409, $hold['why'], $code, $params, 'client' );
			else
				\Nino\Http::fail( $request, 409, $hold['why'], $code, $params );
		}
	}
}
