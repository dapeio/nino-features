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
