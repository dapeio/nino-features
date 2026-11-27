<?php
declare(strict_types=1);

namespace Nino\Modules\CrmDates {

	/**
	 *	Status		The two lines of the status of the CRM that this package enters. dates-type: whether the type of the dates of Events is there and whole (Dates::state()), in the states the panel and the
	 *						surface know - ok, missing (the feature Events is off or has no type file) and damaged (the file cannot be read, or the type is not the one this package writes). dates-unassigned, only a hint:
	 *						how many elements carry the group of a client and are nobody's - no row of the package names them for that client -, which the owner assigns to the client of the group or deletes with the
	 *						action adopt, never by itself. The label and the sentence are the words of the surface in the language of the account. No fix: the type is the project's, and a package does not write it over.
	 *
	 *	@package						Dape/Nino
	 */
	class Status {

		/**	The key of the line	*/
		public const string KEY = 'dates-type';
		public const string UNASSIGNED = 'dates-unassigned';

		/**
		 *	@param		array 		&$appData
		 *
		 *	@return 	array											state, label and detail
		 */
		public static function state( array &$appData ): array {

			$state = Dates::state( $appData );

			return [
				'state'		=> [ 'ok' => 'ok', 'off' => 'missing', 'missing' => 'missing', 'broken' => 'damaged' ][$state],
				'label'		=> \Nino\Modules\Crm\Surface\Texts::word( $appData, 'dates.status-label' ),
				'detail'	=> \Nino\Modules\Crm\Surface\Texts::word( $appData, 'dates.status-'. $state ),
			];
		}

		/**
		 *	@param		array 		&$appData
		 *
		 *	@return 	array											state (ok, leftover where there are elements nobody owns, error where they cannot be counted), label and detail
		 *
		 *	@throws		\PDOException							The database cannot be read
		 */
		public static function unassigned( array &$appData ): array {

			$list = Dates::unassigned( $appData );
			$label = \Nino\Modules\Crm\Surface\Texts::word( $appData, 'dates.unassigned-label' );

			if( is_string( $list ) === true )
				return [ 'state' => 'error', 'label' => $label, 'detail' => \Nino\Modules\Crm\Surface\Texts::word( $appData, 'dates.unassigned-unread' ) ];

			$count = array_sum( array_map( static fn( array $one ): int => count( $one[1] ), $list ) );
			$lonely = Dates::lonely( $appData );
			$rows = is_array( $lonely ) === true ? count( $lonely ) : 0;
			$words = static function( string $key ) use ( &$appData ): string { return \Nino\Modules\Crm\Surface\Texts::word( $appData, $key ); };
			$detail = array_filter( [ $count === 0 ? '' : sprintf( $words( 'dates.unassigned-some' ), $count, count( $list ) ), $rows === 0 ? '' : sprintf( $words( 'dates.unassigned-rows' ), $rows ) ] );

			return [ 'state' => $detail === [] ? 'ok' : 'leftover', 'label' => $label, 'detail' => $detail === [] ? $words( 'dates.unassigned-ok' ) : implode( ' ', $detail ) ];
		}
	}
}
