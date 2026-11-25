<?php
declare(strict_types=1);

namespace Nino\Modules {

	/**
	 *	CrmSurfaceProbe		A package of the suite of the surface, made in the sandbox of the suite and never a feature of the catalogue: it docks to every place the surface of the core offers - words, a
	 *												script, a style sheet, an entry of the rail, the data of the page, a page the devices that stay signed in come back to, an app and the icon of the app -, to show that a package can,
	 *												without the core naming it. It begins with the question every package begins with: whether the core is on.
	 *
	 *												The suite sets what it wants of it in app data: './crm-surface-probe/data' what its data of the page answers
	 */
	class CrmSurfaceProbe {

		/**
		 *	@param		array 		&$appData
		 *
		 *	@return 	void
		 */
		public static function init( array &$appData ): void {

			if( \Nino\Modules\Crm::featureOn( $appData, 'crm' ) === false )
				return;

			// The suite asks for a package that has entered nothing, to try the places by hand
			if( ( $appData['./crm-surface-probe/quiet'] ?? false ) === true )
				return;

			$appData['./crm-surface-probe/answers'] = [
				'texts'  => \Nino\Modules\Crm\Surface::addTexts( $appData, __DIR__. '/text', [ 'package' => 'crm-surface-probe' ] ),
				'style'  => \Nino\Modules\Crm\Surface\Scripts::addStyle( $appData, 'probe.css', __DIR__. '/assets/probe.css', [ 'package' => 'crm-surface-probe' ] ),
				'script' => \Nino\Modules\Crm\Surface\Scripts::add( $appData, 'probe.js', __DIR__. '/assets/probe.js', [ 'package' => 'crm-surface-probe' ] ),
				'menu'   => \Nino\Modules\Crm\Surface\Menu::add( $appData, '/crm/probe', (string) ( $appData['./crm-surface-probe/menu-word'] ?? 'Probe <b>' ), 'probe', [ 'package' => 'crm-surface-probe' ] ),
				'digits' => \Nino\Modules\Crm\Surface\Menu::add( $appData, '/crm/2024', 'Year', '2024', [ 'package' => 'crm-surface-probe' ] ),
				'data'   => \Nino\Modules\Crm\Surface\Data::add( $appData, [ self::class, 'pageData' ], [ 'package' => 'crm-surface-probe' ] ),
				'allow'  => \Nino\Modules\Crm\Surface\Devices::allow( $appData, '/crm/probe', [ 'package' => 'crm-surface-probe' ] ),
				'icons'  => \Nino\Modules\Crm\Surface\Icons::add( $appData, [ 'package' => 'crm-surface-probe', 'key' => 'probeapp', 'prefix' => 'probe-', 'slot' => '/icon/crm-probe', 'route' => '/crm/probeapp/icon', 'letters' => $appData['./crm-surface-probe/letters'] ?? 'PRB' ] ),
				'app'    => \Nino\Modules\Crm\Surface\Apps::add( $appData, [ 'package' => 'crm-surface-probe', 'key' => 'probeapp', 'route' => '/crm/probeapp', 'scope' => '/crm/probeapp', 'manifest' => '/crm/probeapp/manifest.webmanifest', 'values' => [ self::class, 'appValues' ], 'icon' => 'probeapp' ] ),
			];

			\Nino\Modules\Crm\Surface\Icons::registerIcons( $appData, 'probeapp' );

			$appData['/nino/http/routes']['GET://crm/probe'] = [ 'uri' => '/crm/probe' ];
			$appData['/nino/http/routes']['GET://crm/probeapp'] = [ 'uri' => '/crm/probeapp' ];
			\Nino\Callbacks::registerCallback( $appData, '/nino/http/response/GET://crm/probe', [ self::class, 'callbackPage' ] );
			\Nino\Callbacks::registerCallback( $appData, '/nino/http/response/GET://crm/probeapp', [ self::class, 'callbackApp' ] );
		}

		/**
		 *	A call of the suite made as this package makes it: the package is named in the last argument, as every package names itself
		 *
		 *	@param		string		$class
		 *	@param		string		$method
		 *	@param		array 		&$appData
		 *	@param		mixed			...$arguments
		 *
		 *	@return 	mixed
		 */
		public static function via( string $class, string $method, array &$appData, mixed ...$arguments ): mixed {

			return $class::$method( $appData, ...array_merge( $arguments, [ [ 'package' => 'crm-surface-probe' ] ] ) );
		}

		/**
		 *	The letters of the icon of the app, said by a method of the package
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	string
		 */
		public static function initials( array &$appData ): string {

			return 'FN';
		}

		/**
		 *	The data of the package in the data block of the page
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	array
		 */
		public static function pageData( array &$appData ): array {

			return (array) ( $appData['./crm-surface-probe/data'] ?? [ 'answer' => 42 ] );
		}

		/**
		 *	@param		array 		&$appData
		 *	@param		string		$dir
		 *
		 *	@return 	array
		 */
		public static function appValues( array &$appData, string $dir ): array {

			return [ 'manifest' => $dir. '/crm/probeapp/manifest.webmanifest', 'app-title' => 'Probe app', 'title' => 'Probe app' ] + \Nino\Modules\Crm\Surface\App::values( $appData, $dir, 'probeapp' );
		}

		/**
		 *	GET /crm/probe: a page of a package below /crm, under the gate of the surface
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		&$request
		 *
		 *	@return 	void
		 */
		public static function callbackPage( array &$appData, array &$request ): void {

			$user = \Nino\Modules\Crm\Surface::gate( $appData, $request, [ 'route' => '/crm/probe', 'icon' => 'crm', 'values' => [ \Nino\Modules\Crm\Surface\App::class, 'values' ] ] );

			if( $user === null )
				return;

			$request['/nino/http/response']['body'] = 'the probe page of '. $user['mail'];
		}

		/**
		 *	GET /crm/probeapp: the page of the app of the package
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		&$request
		 *
		 *	@return 	void
		 */
		public static function callbackApp( array &$appData, array &$request ): void {

			\Nino\Modules\Crm\Surface::page( $appData, $request, 'probeapp' );
		}
	}
}
