<?php
declare(strict_types=1);

namespace Nino\Modules\Crm {

	/**
	 *	Overview		The first screen of the surface: what the core knows at a glance - how many clients are active - and what the packages add to it. The hook OVERVIEW ('/crm/overview') is asked with
	 *									[ 'packages' => [], 'needs' => [] ]: a listener puts its part under packages, under its key (a list of small values: the summary of the documents, the open comments), and what needs the owner
	 *									into needs, each [ kind, package, ... ] (a kind is a slug, the package its key). An answer that is not the shape asked for - a key that is no slug, a part that is no list, a need that is no list
	 *									or has no kind - is left out. The action crm/overview answers summary (clients), needs and packages; the core has no need of its own.
	 *
	 *	@package						Dape/Nino
	 */
	class Overview {

		/**	The hook the packages add to the overview with	*/
		public const string HOOK = '/crm/overview';

		/**	The most needs the overview carries, and the most a need may hold	*/
		public const int NEEDS = 50;

		/**
		 *	@return 	array											action => [ class, method ]
		 */
		public static function actions(): array {

			return [ 'crm/overview' => [ self::class, 'apiOverview' ] ];
		}

		/**
		 *	What the overview holds
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	array											summary ([ clients => how many are active ]), needs and packages
		 */
		public static function read( array &$appData ): array {

			$ask = [ 'packages' => [], 'needs' => [] ];

			\Nino\Callbacks::doCallbacks( $appData, self::HOOK, $ask );

			$packages = [];
			$needs = [];

			foreach( is_array( $ask ) === true && is_array( $ask['packages'] ?? null ) === true ? $ask['packages'] : [] as $key => $part )
				if( Register::word( (string) $key ) === true && is_array( $part ) === true )
					$packages[(string) $key] = $part;

			foreach( is_array( $ask ) === true && is_array( $ask['needs'] ?? null ) === true ? $ask['needs'] : [] as $need )
				if( is_array( $need ) === true && is_string( $need['kind'] ?? null ) === true && Register::word( $need['kind'] ) === true && is_string( $need['package'] ?? null ) === true && Register::word( $need['package'] ) === true && count( $needs ) < self::NEEDS )
					$needs[] = $need;

			return [ 'summary' => [ 'clients' => Clients::countActive( $appData ) ], 'needs' => $needs, 'packages' => (object) $packages ];
		}

		/**
		 *	crm/overview
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		&$request
		 *
		 *	@return 	void
		 */
		public static function apiOverview( array &$appData, array &$request ): void {

			if( \Nino\Modules\Crm\Values::permit( $appData, $request, \Nino\Modules\Crm::MANAGE_PERM ) === false )
				return;

			Api::run( $request, static function() use ( &$appData, &$request ): void {
				\Nino\Http::ok( $request, self::read( $appData ) );
			} );
		}
	}
}
