<?php
declare(strict_types=1);

namespace Nino\Modules\Greeting {

	/**
	 *	Nino features					Greeting panel: the three switches of the card, one for each of its
	 *												lines. They are keys of config.php (see \Nino\Modules\Greeting::LINES):
	 *												a line the project switched off is off, and a line it never
	 *												mentioned is on
	 */
	class Admin {

		public static function perm(): string {
			return '/_admin/greeting/manage';
		}

		public static function actions(): array {
			return [ 'greeting/list' => [ self::class, 'apiList' ], 'greeting/save' => [ self::class, 'apiSave' ] ];
		}

		public static function nav(): array {
			return [ 'greeting', '/_admin/greeting/nav', 90 ];
		}

		public static function icon(): string {
			return '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M7.9 20A9 9 0 1 0 4 16.1L2 22Z"/></svg>';
		}

		public static function assets(): array {
			return [
				\Nino\Admin\Panels::relative( dirname( __DIR__ ). '/assets/admin.js' ),
				\Nino\Admin\Panels::relative( dirname( __DIR__ ). '/assets/admin.css' ),
			];
		}

		public static function text(): string {
			return \Nino\Admin\Panels::relative( dirname( __DIR__ ). '/text' );
		}

		public static function log( string $action, array $data ): string {
			return $action === 'greeting/save' ? 'Edit Greeting '. implode( ', ', array_keys( is_array( $data['lines'] ?? null ) ? $data['lines'] : [] ) ) : '';
		}

		/**
		 *	Every line and whether it is on: what config.php holds, read afresh rather than
		 *	from $appData - which a module may have changed for this request alone - and on
		 *	for a line it does not mention
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$request			(reference) Current server request
		 *
		 *	@return 	void
		 */
		public static function apiList( array &$appData, array &$request ): void {

			if( \Nino\Admin\Admin::guardPerm( $appData, $request, self::perm() ) === false )
				return;

			$stored = \Nino\Filesystem::getFileContent( $appData, '/config.php', [] );
			$lines 	= [];

			foreach( \Nino\Modules\Greeting::LINES as $line => $key )
				$lines[$line] = ( is_array( $stored ) === true ? ( $stored[$key] ?? true ) : true ) !== false;

			\Nino\Http::ok( $request, [ 'lines' => $lines ] );
		}

		/**
		 *	Save the lines the form sends in one write: each a boolean, nothing written until
		 *	every one of them is, and a line the form does not send keeps what config.php holds
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$request			(reference) Current server request
		 *
		 *	@return 	void
		 */
		public static function apiSave( array &$appData, array &$request ): void {

			if( \Nino\Admin\Admin::guardPerm( $appData, $request, self::perm() ) === false )
				return;

			$posted = \Nino\Admin\Admin::postData()['lines'] ?? null;

			if( is_array( $posted ) === false ) {
				\Nino\Http::fail( $request, 400, 'no lines posted' );
				return;
			}

			$clean = [];
			foreach( \Nino\Modules\Greeting::LINES as $line => $key ) {

				if( array_key_exists( $line, $posted ) === false )
					continue;

				$value = match( true ) {
					is_bool( $posted[$line] ) 																			=> $posted[$line],
					$posted[$line] === 'true', $posted[$line] === 1, $posted[$line] === '1' 	=> true,
					$posted[$line] === 'false', $posted[$line] === 0, $posted[$line] === '0' => false,
					default 																												=> null,
				};

				if( $value === null ) {
					\Nino\Admin\Admin::failType( $request, $line, [ 'type' => 'bool' ] );
					return;
				}

				$clean[$key] = $value;
			}

			if( $clean === [] ) {
				\Nino\Http::fail( $request, 400, 'no known lines posted' );
				return;
			}

			foreach( $clean as $key => $value )
				$appData[$key] = $value;

			if( \Nino\AppData::writeContentData( $appData, array_keys( $clean ) ) === false ) {
				\Nino\Http::fail( $request, 500, 'could not write the lines' );
				return;
			}

			self::apiList( $appData, $request );
		}
	}
}
