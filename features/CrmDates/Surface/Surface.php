<?php
declare(strict_types=1);

namespace Nino\Modules\CrmDates {

	/**
	 *	Surface		What the package gives the page /crm besides its script, style sheet and words: its part of the data block, which the script reads with kit.data( 'crm-dates' ). It holds perm, whether
	 *						the account holds the permission of the Elements panel, and nothing of a client. What the account may do with the dates, field by field, is told by each answer of the actions.
	 *
	 *	@package						Dape/Nino
	 */
	class Surface {

		/**
		 *	@param		array 		&$appData
		 *
		 *	@return 	array											perm
		 */
		public static function data( array &$appData ): array {

			return [ 'perm' => \Nino\Auth::checkPermission( $appData, Api::ELEMENTS ) ];
		}
	}
}
