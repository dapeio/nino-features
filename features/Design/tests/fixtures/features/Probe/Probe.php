<?php
declare(strict_types=1);
/**
 *	Nino										A compact filesystembased php framework
 *	Modules\Probe						The fixture of the Design feature's tests: a feature with
 *													design/ variants and one element, [probe], for them to style
 *
 *	@package								Dape/Nino
 *	@author									David Perchermeier <mail@dape.io>
 *	@link										https://github.com/dapeio/nino
 */
namespace Nino\Modules {

	class Probe {

		public static function init( array &$appData ): void {
			\Nino\Feature::shortcode( $appData, 'probe', [ self::class, 'doShortcode' ] );
		}

		public static function doShortcode( array &$appData, array $args ): string {

			return str_replace(
				[ '[[classes]]' ],
				[ \Nino\Feature::classes( $args ) ],
				\Nino\Feature::template( $appData, '/features/Probe/templates', 'probe' )
			);
		}
	}
}
