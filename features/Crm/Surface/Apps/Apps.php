<?php
declare(strict_types=1);

namespace Nino\Modules\Crm\Surface {

	/**
	 *	Apps		The apps below /crm: a page of its own with no rail (the tasks of the projects are the one of the core), drawn by the script of the surface from the key the data block names. An app is
	 *						entered once, with the route of its page, the part of the site it covers (its scope), its manifest, the method that answers the tokens of its head and the key of its icon; its page is
	 *						answered by Surface::page() under the same gate, the same policy and the same endpoint as the page of the surface, and the devices that stay signed in come back to it: the route is
	 *						told to Devices::allow() here. The app registers its own routes and callbacks (Icons::registerIcons() for its icon); this register is what the page asks.
	 *
	 *	@package						Dape/Nino
	 */
	class Apps {

		// What the module keeps for the request: the apps that were entered
		private const string REGISTER = './crm/surface/apps';

		/**
		 *	Enter an app of a package
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		$spec					package (the key of the feature that enters it, see Register::package()), key (lower case letters, the data block says app: <key>), route (/crm/<name>), scope (the part of the site it covers, a route), manifest (the route of its manifest), values ( [ class, method ]:
		 *																	( array &$appData, string $dir ): array, the tokens of its head), icon (the key of its app in Icons)
		 *
		 *	@return 	bool											false, with a warning, where the description is refused - a key or a route another app has - and nothing is entered; the same description again is as good as the first
		 */
		public static function add( array &$appData, array $spec ): bool {

			return self::enter( $appData, $spec, false );
		}

		/**
		 *	The app of the core: the tasks of the projects
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	bool
		 */
		public static function init( array &$appData ): bool {

			return self::enter( $appData, [ 'package' => \Nino\Modules\Crm\Api::CORE, 'key' => 'todo', 'route' => Todo::ROUTE, 'scope' => Todo::SCOPE, 'manifest' => Todo::MANIFEST, 'values' => [ Todo::class, 'values' ], 'icon' => 'todo' ], true );
		}

		/**
		 *	@param		array 		&$appData
		 *	@param		array 		$spec
		 *	@param		bool			$core					The core enters its own app
		 *
		 *	@return 	bool
		 */
		private static function enter( array &$appData, array $spec, bool $core ): bool {

			$entered = \Nino\Modules\Crm\Register::add( $appData, self::REGISTER, 'apps', [ $spec ], [
				'id' => 'key',
				'idRule' => '/^[a-z]{1,20}$/D',
				'fields' => [ 'package' => \Nino\Modules\Crm\Register::REQUIRED, 'route' => \Nino\Modules\Crm\Register::REQUIRED, 'scope' => \Nino\Modules\Crm\Register::REQUIRED, 'manifest' => \Nino\Modules\Crm\Register::REQUIRED, 'values' => \Nino\Modules\Crm\Register::REQUIRED, 'icon' => \Nino\Modules\Crm\Register::REQUIRED ],
				'rules' => [
					'package' => \Nino\Modules\Crm\Register::packageRule( $appData, $core ),
					'route' => static fn( mixed $route ): bool => is_string( $route ) === true && Devices::isRoute( $route, $core ),
					'scope' => static fn( mixed $scope, array $full ): bool => is_string( $scope ) === true && Devices::isRoute( $scope, $core ) && ( $core === true || $scope === $full['route'] || str_starts_with( $scope, $full['route']. '/' ) === true ),
					'manifest' => static fn( mixed $manifest, array $full ): bool => is_string( $manifest ) === true && str_starts_with( $manifest, '/crm/' ) === true && str_contains( $manifest, '..' ) === false
						&& ( $core === true || ( str_starts_with( $manifest, (string) $full['route']. '/' ) === true && preg_match( '~^/crm/[a-z0-9/.-]+$~D', $manifest ) === 1 ) ),
					'values' => static fn( mixed $values ): bool => \Nino\Modules\Crm\Register::callable( $values ),
					'icon' => static fn( mixed $icon ): bool => is_string( $icon ) === true && preg_match( '/^[a-z]{1,20}$/D', $icon ) === 1,
				],
				'refuse' => static function( array $entry, array $known ): string {

					foreach( $known as $other )
						if( $other['key'] !== $entry['key'] && $other['route'] === $entry['route'] )
							return 'route taken';

					return '';
				},
			] );

			return $entered === true && ( $core === true || ( Devices::claim( $appData, (string) $spec['route'], (string) $spec['package'] ) === true && Devices::allow( $appData, (string) $spec['route'], [ 'package' => $spec['package'] ] ) === true ) );
		}

		/**
		 *	The apps that are entered
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	array											key => description
		 */
		public static function all( array &$appData ): array {

			return \Nino\Modules\Crm\Register::all( $appData, self::REGISTER );
		}

		/**
		 *	One app
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$key
		 *
		 *	@return 	array|null								null for a key nobody entered
		 */
		public static function get( array &$appData, string $key ): ?array {

			return self::all( $appData )[$key] ?? null;
		}
	}
}
