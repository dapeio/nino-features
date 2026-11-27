<?php
declare(strict_types=1);

namespace Nino\Modules\CrmDates {

	/**
	 *	Overview		What the package says on the first screen of the CRM: the date that comes next, of all the clients. The hook /crm/overview is asked with packages and needs; the answer is the part under the
	 *									key of the package: state (ok, or the word of why the dates cannot be read: off, missing, broken, unreadable) and next (the date as Dates::list() has it, null for none). A state that is
	 *									not ok is never a "no date coming": the tile says it cannot read them.
	 *
	 *	@package						Dape/Nino
	 */
	class Overview {

		/**
		 *	@param		array 		&$appData
		 *	@param		array 		&$ask					packages and needs, as the core asks them
		 *
		 *	@return 	void
		 */
		public static function callbackOverview( array &$appData, array &$ask ): void {

			if( is_array( $ask['packages'] ?? null ) === false )
				return;

			try {
				$next = Dates::first( $appData );
			}
			catch( \PDOException $e ) {
				$next = 'unreadable';
			}

			$ask['packages'][Api::KEY] = is_string( $next ) === true ? [ 'state' => $next, 'next' => null ] : [ 'state' => 'ok', 'next' => $next ];
		}
	}
}
