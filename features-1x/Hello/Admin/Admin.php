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
