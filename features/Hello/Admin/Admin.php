<?php
declare(strict_types=1);

namespace Nino\Modules\Hello {

	class Admin {

		public static function perm(): string {
			return '/_admin/hello/manage';
		}

		public static function actions(): array {
			return [ 'hello/list' => [ self::class, 'apiList' ], 'hello/save' => [ self::class, 'apiSave' ] ];
		}

		public static function nav(): array {
			return [ 'hello', '/_admin/hello/nav', 90 ];
		}

		public static function assets(): array {
			return [ \Nino\Admin\Panels::relative( dirname( __DIR__ ). '/assets/admin.js' ) ];
		}

		// The sign of the rail entry: Lucide hand, geometry and no view
		public static function icon(): string {
			return '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 11V6a2 2 0 0 0-2-2a2 2 0 0 0-2 2"/><path d="M14 10V4a2 2 0 0 0-2-2a2 2 0 0 0-2 2v2"/><path d="M10 10.5V6a2 2 0 0 0-2-2a2 2 0 0 0-2 2v8"/><path d="M18 8a2 2 0 1 1 4 0v6a8 8 0 0 1-8 8h-2c-2.8 0-4.5-.86-5.99-2.34l-3.6-3.6a2 2 0 0 1 2.83-2.82L7 15"/></svg>';
		}

		public static function text(): string {
			return \Nino\Admin\Panels::relative( dirname( __DIR__ ). '/text' );
		}

		public static function apiList( array &$appData, array &$request ): void {

			if( \Nino\Admin\Admin::guardPerm( $appData, $request, self::perm() ) === false )
				return;

			\Nino\Http::ok( $request, [ 'name' => \Nino\Modules\Hello::name( $appData ), 'longest' => self::_longest( $appData ) ] );
		}

		public static function apiSave( array &$appData, array &$request ): void {

			if( \Nino\Admin\Admin::guardPerm( $appData, $request, self::perm() ) === false )
				return;

			$name = trim( \Nino\Admin\Admin::postString( \Nino\Admin\Admin::postData(), 'name' ) );

			if( mb_strlen( $name ) > self::_longest( $appData ) )
				\Nino\Http::fail( $request, 400, 'the name is too long', 'hello_long', [ self::_longest( $appData ) ], 'name' );
			else if( \Nino\Filesystem::mutate( $appData, \Nino\Modules\Hello::STORAGE, static fn( mixed $state ): array => array_merge( [ 'format' => 1 ], is_array( $state ) === true ? $state : [], [ 'name' => $name ] ) ) === false )
				\Nino\Http::fail( $request, 500, 'could not write the name' );
			else
				\Nino\Http::ok( $request, [ 'name' => \Nino\Modules\Hello::name( $appData ) ] );
		}

		private static function _longest( array &$appData ): int {
			return (int) \Nino\Features::setting( $appData, 'hello', 'longest', 40 );
		}
	}
}
