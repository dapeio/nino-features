<?php
declare(strict_types=1);
/**
 *	Nino								A compact filesystembased php framework
 *	Modules\ProtectedArea\Admin		The /_admin panel of the Protected area feature - see docs/features.md, "The panel"
 *
 *	@package						Dape/Nino
 *	@author							David Perchermeier <mail@dape.io>
 *	@link								https://github.com/dapeio/nino
 */
namespace Nino\Modules\ProtectedArea {

	/**
	 *	Nino							A compact filesystembased php framework
	 *	ProtectedArea\Admin	"Protected area" panel: the two things somebody who runs
	 *										a protected area does after the feature is set up and
	 *										does not want to open the Features panel for - choose
	 *										the protected paths and change the password - in one
	 *										form, and the one action beside it, locking every
	 *										session at once. The settings the feature keeps stay
	 *										the Features panel's: this panel reads and writes the
	 *										same two (`paths`, `password`) through
	 *										\Nino\Features::saveSettings(), so nothing here can
	 *										disagree with that form. The password never comes
	 *										back: the answer says that one is set and nothing
	 *										more, and what is stored is a hash.
	 *
	 *	@package					Dape/Nino
	 *	@author						David Perchermeier <mail@dape.io>
	 *	@link							https://github.com/dapeio/nino
	 */
	class Admin {

		public const string MANAGE_PERM = '/_admin/protected/manage';

		// The same floor the kernel holds an account's password to
		// (\Nino\Admin\Admin, \Nino\Modules\Users\Admin). The ceiling is this
		// panel's own: a shared password is typed and passed around, and a
		// value past it is a paste that went wrong
		private const int MIN_PW_LENGTH = 8;
		private const int MAX_PW_LENGTH = 200;

		// What the setting 'paths' takes (\Nino\Features::validateSettings(), type lines)
		private const int MAX_PATHS = 200;
		private const int MAX_PATH_LENGTH = 1000;

		private const string FEATURE_KEY = 'protected';

		public static function perm(): string {
			return self::MANAGE_PERM;
		}

		public static function actions(): array {
			return [
				'protected/state'		=> [ self::class, 'apiState' ],
				'protected/save'		=> [ self::class, 'apiSave' ],
				'protected/signout'	=> [ self::class, 'apiSignOut' ],
			];
		}

		public static function nav(): array {
			return [ 'protected', '/_admin/nav/protected', 40 ];
		}

		public static function icon(): string {
			return '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>';
		}

		public static function panes(): array {
			return [ 'protected-form' ];
		}

		public static function assets(): array {
			return [ \Nino\Admin\Panels::relative( dirname( __DIR__ ). '/assets/admin.js' ) ];
		}

		// The panel's own strings, one <locale>.php per interface language
		public static function text(): string {
			return \Nino\Admin\Panels::relative( dirname( __DIR__ ). '/text' );
		}

		/**
		 *	The activity-log line of a completed action. Never the password,
		 *	nor anything it could be recovered from: the line says that it was
		 *	changed and nothing more.
		 *
		 *	@param		string		$action
		 *	@param		array 		$data					What the action was posted
		 *
		 *	@return 	string
		 */
		public static function log( string $action, array $data ): string {

			return match( $action ) {
				'protected/save'		=> 'Saved the protected area'. ( is_string( $data['pw'] ?? null ) === true && $data['pw'] !== '' ? ', with a new password, and signed everybody out' : '' ),
				'protected/signout'	=> 'Signed everybody out of the protected area',
				default					=> '',
			};
		}

		/**
		 *	What the screen draws: the protected paths as they are written in the
		 *	setting, whether a password is set (never the password), and the
		 *	length the password has to have
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$request			(reference) Current server request
		 *
		 *	@return 	void
		 */
		public static function apiState( array &$appData, array &$request ): void {

			if( \Nino\Admin\Admin::guardPerm( $appData, $request, self::MANAGE_PERM ) === false )
				return;

			\Nino\Http::ok( $request, self::_state( $appData ) );
		}

		/**
		 *	Save the form: the protected paths, and - where one was typed - a new
		 *	password. The paths come as the lines of the field (a list is taken as
		 *	well) and every one has to be a prefix the gate can hold, see
		 *	\Nino\Modules\ProtectedArea::cleanPrefix(); one that is none is refused
		 *	with the code protected_path and its own text. An empty field is
		 *	accepted: it protects nothing.
		 *
		 *	A password is posted as 'pw', checked for its length here - the screen
		 *	checked that it was typed twice - and never echoed back, not in the
		 *	answer, not in the log. An empty 'pw' keeps the one there is. A new one
		 *	signs everybody out: the sessions go first, since a write that fails
		 *	there leaves the old password in place, where the other order would
		 *	leave the new one beside sessions it never asked for. They go again
		 *	once the settings are written: between the two, somebody who knew the
		 *	old password could unlock and be stamped with the epoch of the first
		 *	round, a session the new password never asked for. Its length is
		 *	counted in characters, as the screen counts it, and one outside the
		 *	bounds is refused with the code protected_short or protected_long, the
		 *	limit in its params and the field it was posted in. What is stored is
		 *	the hash. A setting the kernel refuses is protected_setting, its English
		 *	message the one param; a write that does not happen is the server's, a
		 *	500 with no code. Nothing is written for a refused form.
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$request			(reference) Current server request
		 *
		 *	@return 	void
		 */
		public static function apiSave( array &$appData, array &$request ): void {

			if( \Nino\Admin\Admin::guardPerm( $appData, $request, self::MANAGE_PERM ) === false )
				return;

			$data		= \Nino\Admin\Admin::postData();
			$posted	= $data['paths'] ?? null;
			$pw			= $data['pw'] ?? '';

			if( is_string( $posted ) === true )
				$posted = preg_split( '/\R/u', $posted );

			if( is_array( $posted ) === false || array_is_list( $posted ) === false || count( $posted ) > self::MAX_PATHS ) {
				\Nino\Http::fail( $request, 400, 'paths must be a list of lines', 'protected_paths', [], 'paths' );
				return;
			}

			$lines = [];

			foreach( $posted as $line ) {

				if( is_string( $line ) === false || strlen( $line ) > self::MAX_PATH_LENGTH ) {
					\Nino\Http::fail( $request, 400, 'paths must be a list of lines', 'protected_paths', [], 'paths' );
					return;
				}

				$line = trim( $line );

				if( $line === '' )
					continue;

				if( \Nino\Modules\ProtectedArea::cleanPrefix( $line ) === null ) {
					\Nino\Http::fail( $request, 400, 'not a path that can be protected', 'protected_path', [ mb_substr( $line, 0, 60, 'UTF-8' ) ], 'paths' );
					return;
				}

				$lines[] = $line;
			}

			if( is_string( $pw ) === false || ( $pw !== '' && mb_strlen( $pw, 'UTF-8' ) < self::MIN_PW_LENGTH ) ) {
				\Nino\Http::fail( $request, 400, 'password must be at least '. self::MIN_PW_LENGTH. ' characters', 'protected_short', [ self::MIN_PW_LENGTH ], 'pw' );
				return;
			}

			if( mb_strlen( $pw, 'UTF-8' ) > self::MAX_PW_LENGTH ) {
				\Nino\Http::fail( $request, 400, 'password must be at most '. self::MAX_PW_LENGTH. ' characters', 'protected_long', [ self::MAX_PW_LENGTH ], 'pw' );
				return;
			}

			$fields = [ 'paths' => $lines ];

			if( $pw !== '' ) {

				if( \Nino\Modules\ProtectedArea::signOutAll( $appData ) === false ) {
					\Nino\Http::fail( $request, 500, 'could not sign everybody out' );
					return;
				}

				$fields['password'] = \Nino\Modules\ProtectedArea::hashPassword( $pw );
			}

			if( self::_failed( $request, \Nino\Features::saveSettings( $appData, self::FEATURE_KEY, $fields ) ) === true )
				return;

			// Again, now that the new password is the one in force
			if( $pw !== '' && \Nino\Modules\ProtectedArea::signOutAll( $appData ) === false ) {
				\Nino\Http::fail( $request, 500, 'the password was saved, but not everybody could be signed out again' );
				return;
			}

			\Nino\Http::ok( $request, self::_state( $appData ) );
		}

		/**
		 *	Lock every session at once
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$request			(reference) Current server request
		 *
		 *	@return 	void
		 */
		public static function apiSignOut( array &$appData, array &$request ): void {

			if( \Nino\Admin\Admin::guardPerm( $appData, $request, self::MANAGE_PERM ) === false )
				return;

			if( \Nino\Modules\ProtectedArea::signOutAll( $appData ) === false ) {
				\Nino\Http::fail( $request, 500, 'could not sign everybody out' );
				return;
			}

			\Nino\Http::ok( $request, [ 'signedOut' => true ] );
		}

		/**
		 *	The answer of the screen's data - see apiState()
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	array
		 */
		private static function _state( array &$appData ): array {

			return [
				'paths'				=> array_values( array_filter( (array) \Nino\Features::setting( $appData, self::FEATURE_KEY, 'paths', [] ), 'is_string' ) ),
				'hasPassword'	=> (string) \Nino\Features::setting( $appData, self::FEATURE_KEY, 'password', '' ) !== '',
				'minLength'		=> self::MIN_PW_LENGTH,
				'maxLength'		=> self::MAX_PW_LENGTH,
			];
		}

		/**
		 *	Answer a failed save. A message for a setting that did not validate
		 *	is the sender's to fix (400, the code protected_setting with the
		 *	kernel's English message as its param); one without a name is the
		 *	write that did not happen (500, no code).
		 *
		 *	@param		array 		&$request			(reference) Current server request
		 *	@param		array 		$errors				What \Nino\Features::saveSettings() answered
		 *
		 *	@return 	bool										True where the request was answered as failed
		 */
		private static function _failed( array &$request, array $errors ): bool {

			if( $errors === [] )
				return false;

			if( isset( $errors[''] ) === true ) {
				\Nino\Http::fail( $request, 500, 'could not save' );
				return true;
			}

			$message = (string) reset( $errors );

			\Nino\Http::fail( $request, 400, $message, 'protected_setting', [ $message ] );

			return true;
		}
	}
}
