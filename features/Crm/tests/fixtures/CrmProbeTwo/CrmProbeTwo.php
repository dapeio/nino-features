<?php
declare(strict_types=1);

namespace Nino\Modules {

	/**
	 *	CrmProbeTwo		A second package of the suite of the core, made in the sandbox of the suite and never a feature of the catalogue: it enters nothing by itself, so that the suite can say what a
	 *									package may not enter under the key of another
	 */
	class CrmProbeTwo {

		/**
		 *	@param		array 		&$appData
		 *
		 *	@return 	void
		 */
		public static function init( array &$appData ): void {

			// A step of the test phase that holds what it read, when the suite asks for it: the package has no schema, so that it can be removed
			if( ( $appData['./crm-probe/two-wipe'] ?? false ) === true )
				\Nino\Modules\Crm\Wipe::add( $appData, [ 'package' => 'crm-probe-two', 'key' => 'twog', 'gather' => [ self::class, 'wipeGather' ], 'leftover' => [ self::class, 'wipeKept' ] ] );

			// A second package that answers the view of a client and of the list, when the suite asks for it in './crm-probe/view-two' (throw: it breaks)
			if( is_array( $appData['./crm-probe/view-two'] ?? null ) === true ) {
				\Nino\Callbacks::registerCallback( $appData, \Nino\Modules\Crm\Clients::VIEW, [ self::class, 'view' ] );
				\Nino\Callbacks::registerCallback( $appData, \Nino\Modules\Crm\Clients::VIEWS, [ self::class, 'views' ] );
			}
		}

		/**
		 *	@param		array 		&$appData
		 *
		 *	@return 	array
		 */
		public static function wipeGather( array &$appData ): array {

			return [ 'numbers' => array_column( \Nino\Modules\Crm\Db::rows( $appData, 'SELECT number FROM clients ORDER BY id' ), 'number' ) ];
		}

		/**
		 *	@param		array 		&$appData
		 *	@param		array 		&$done
		 *	@param		array 		$gathered
		 *
		 *	@return 	bool
		 */
		public static function wipeKept( array &$appData, array &$done, array $gathered ): bool {

			return \Nino\Filesystem::putFileContent( $appData, '/data/crm-probe-two-kept.php', $gathered ) !== false;
		}

		/**
		 *	@param		array 		&$appData
		 *	@param		mixed			&$argument
		 *
		 *	@return 	mixed
		 */
		public static function view( array &$appData, mixed &$argument ): mixed {

			if( ( $appData['./crm-probe/view-two']['throw'] ?? false ) === true )
				throw new \RuntimeException( 'the second probe breaks' );

			// What the suite steers: the key it answers under (as), and a value it writes to what another listener keeps a reference to (touch)
			if( array_key_exists( 'touch', (array) $appData['./crm-probe/view-two'] ) === true )
				$appData['./crm-probe/live'] = $appData['./crm-probe/view-two']['touch'];

			$argument['packages'][(string) ( $appData['./crm-probe/view-two']['as'] ?? 'crm-probe-two' )] = [ 'two' => true ];

			return $argument;
		}

		/**
		 *	@param		array 		&$appData
		 *	@param		mixed			&$argument
		 *
		 *	@return 	mixed
		 */
		public static function views( array &$appData, mixed &$argument ): mixed {

			if( ( $appData['./crm-probe/view-two']['throw'] ?? false ) === true )
				throw new \RuntimeException( 'the second probe breaks' );

			foreach( (array) ( $argument['clients'] ?? [] ) as $client )
				$argument['packages'][(string) ( $appData['./crm-probe/view-two']['as'] ?? 'crm-probe-two' )][(int) $client['id']] = isset( $appData['./crm-probe/bulk'] ) === true ? [ 'pad' => str_repeat( 'x', (int) $appData['./crm-probe/bulk'] ) ] : [ 'two' => true ];

			if( array_key_exists( 'touch', (array) $appData['./crm-probe/view-two'] ) === true )
				$appData['./crm-probe/live'] = $appData['./crm-probe/view-two']['touch'];

			return $argument;
		}

		/**
		 *	A method that takes its two arguments as values, as the check of a setting is called ( string $key, string $value )
		 *
		 *	@param		string		$key
		 *	@param		string		$value
		 *
		 *	@return 	string
		 */
		public static function clean( string $key, string $value ): string {

			return $value;
		}
	}
}
