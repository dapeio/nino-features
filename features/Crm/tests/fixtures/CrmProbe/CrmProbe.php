<?php
declare(strict_types=1);

namespace Nino\Modules {

	/**
	 *	CrmProbe		A package of the suite of the core, made in the sandbox of the suite and never a feature of the catalogue: it docks to every place the core offers - a schema, a
	 *							setting, a number sequence, a line of the status, a path shared on every host, the words of its mails, the band of its mails and the view of the settings -, to show that
	 *							a package can, without the core naming it. It begins with the question every package begins with: whether the core is on.
	 *
	 *							The suite sets what it wants of it in app data: './crm-probe/schema' the file of its schema below its folder (schema.php), './crm-probe/version' the version of it
	 */
	class CrmProbe {


		/**
		 *	@param		array 		&$appData
		 *
		 *	@return 	void
		 */
		public static function init( array &$appData ): void {

			if( \Nino\Modules\Crm::featureOn( $appData, 'crm' ) === false )
				return;

			\Nino\Modules\Crm\Db::addSchema( $appData, [ 'package' => 'crm-probe', 'version' => (int) ( $appData['./crm-probe/version'] ?? 1 ), 'tables' => [ 'crm_probe_items' ], 'file' => __DIR__. '/'. ( $appData['./crm-probe/schema'] ?? 'schema.php' ) ] );
			\Nino\Modules\Crm\Settings::add( $appData, [
				[ 'package' => 'crm-probe', 'key' => 'probe_limit', 'default' => '5', 'clean' => [ self::class, 'cleanLimit' ], 'before' => 'seller_name', 'rank' => 300, 'refusal' => 'crm_probe_limit' ],
				[ 'package' => 'crm-probe', 'key' => 'probe_host', 'default' => '', 'clean' => [ self::class, 'cleanHost' ], 'host' => true, 'written' => [ self::class, 'written' ] ],
			] );
			\Nino\Modules\Crm\Hosts::share( $appData, [ '/.probe/files/' ], [ '/.probe/zip' ], [ 'package' => 'crm-probe' ] );
			\Nino\Modules\Crm\Sequences::add( $appData, [ 'category' => 'probe-invoice', 'package' => 'crm-probe' ] );
			\Nino\Modules\Crm\Status::add( $appData, [ 'package' => 'crm-probe', 'key' => 'probe-line', 'rank' => 150, 'state' => [ self::class, 'status' ], 'fix' => [ self::class, 'fix' ] ] );
			\Nino\Modules\Crm\Mail::addWords( $appData, '/feature/crm-probe/', [ 'package' => 'crm-probe' ] );
			\Nino\Feature::shortcode( $appData, 'mail-head', [ self::class, 'doShortcodeMailHead' ], false );
			\Nino\Callbacks::registerCallback( $appData, '/crm/settings/view', [ self::class, 'callbackView' ] );

			// The places the second part of the core offers: the suite of the domain asks for them in app data
			if( ( $appData['./crm-probe/domain'] ?? false ) === true )
				\Nino\Modules\CrmProbe\Domain::init( $appData );
		}

		/**
		 *	A band is no business of the probe: it hands on what it was given, as the packages with a mail do
		 *
		 *	@param		array 		&$appData
		 *	@param		mixed			$args
		 *
		 *	@return 	string
		 */
		public static function doShortcodeMailHead( array &$appData, mixed $args ): string {

			return is_string( $args ) === true ? $args : '';
		}

		/**
		 *	@param		string		$key
		 *	@param		string		$value
		 *
		 *	@return 	string|array
		 */
		public static function cleanLimit( string $key, string $value ): string|array {

			return preg_match( '/^[0-9]{1,3}$/D', $value ) === 1 ? (string) (int) $value : [ 'value:'. $key ];
		}

		/**
		 *	@param		string		$key
		 *	@param		string		$value
		 *
		 *	@return 	string|array
		 */
		public static function cleanHost( string $key, string $value ): string|array {

			return \Nino\Modules\Crm\Settings::host( $key, $value );
		}

		/**
		 *	What was written is told: the suite reads it back
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$value
		 *	@param		bool			$changed
		 *
		 *	@return 	void
		 */
		public static function written( array &$appData, string $value, bool $changed ): void {

			$appData['./crm-probe/written'][] = [ $value, $changed ];
		}

		/**
		 *	@param		array 		&$appData
		 *
		 *	@return 	array
		 */
		public static function status( array &$appData ): array {

			if( ( $appData['./crm-probe/throws'] ?? false ) === true )
				throw new \RuntimeException( 'the probe breaks' );

			if( ( $appData['./crm-probe/shape'] ?? '' ) === 'none' )
				return [ 'state' => 'Not A State' ];

			return [ 'state' => (string) ( $appData['./crm-probe/state'] ?? 'ok' ), 'label' => 'Probe', 'detail' => 'A line of a package', 'items' => 3, 'nested' => [ [ 'a' ] ], 'object' => new \stdClass(), 'Bad Name' => 1, 'list' => [ 'x', 'y' ] ];
		}

		/**
		 *	@param		array 		&$appData
		 *
		 *	@return 	true|string
		 */
		public static function fix( array &$appData ): true|string {

			$how = (string) ( $appData['./crm-probe/fix'] ?? '' );

			if( $how === 'throws' )
				throw new \RuntimeException( 'the probe fix breaks' );

			if( $how === 'reason' )
				return 'disk-full';

			if( $how === 'junk' )
				return 'Not A Reason';

			$appData['./crm-probe/state'] = 'ok';
			$appData['./crm-probe/fixed'] = ( $appData['./crm-probe/fixed'] ?? 0 ) + 1;

			return true;
		}

		/**
		 *	The part of the probe in the view of the settings
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		&$ask
		 *
		 *	@return 	void
		 */
		public static function callbackView( array &$appData, array &$ask ): void {

			$ask['packages']['crm-probe'] = [ 'next' => 7 ];
			$ask['packages']['Bad Key'] = [ 'x' => 1 ];
			$ask['packages']['crm-text'] = 'no list';
		}
	}
}
