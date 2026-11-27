<?php
declare(strict_types=1);

namespace Nino\Modules\CrmDates {

	/**
	 *	Organizer		Who the dates in a calendar file are with - the owner - as the feature Events asks for it (the hook /events/organizer, once for a file) - and only where the setting feedContact of this
	 *									package is on, which it is not until the owner says so: the file is public, and it carries the name and the contact of the owner for every public date, whether the CRM has to do with it or not. Off, the
	 *									listener answers nothing and does not open the database. On, the name is the one the CRM names the owner by
	 *									(Settings::ownerName()); the email, the phone and the street with the zip and the town are the settings of the block of the seller where a package has entered them (seller_email, seller_phone,
	 *									seller_street, seller_zip, seller_city: no setting that is not there is made up, and one that is empty is no line) and no other field. The settings are read the way a visitor's
	 *									page reads the name of the owner (Settings::readOnly(): a connection that only reads, never migrates, waits no longer than a moment) - the feed is for visitors. Every value is one line of text,
	 *									whatever the settings or the texts of the site hold.
	 *
	 *	@package						Dape/Nino
	 */
	class Organizer {

		/**
		 *	@param		array 		&$appData
		 *	@param		array 		&$organizer		name, email, phone, address, url and account, as Events hands them over
		 *
		 *	@return 	void
		 */
		public static function callbackOrganizer( array &$appData, array &$organizer ): void {

			if( \Nino\Features::setting( $appData, \Nino\Modules\CrmDates::KEY, 'feedContact', false ) !== true )
				return;

			$read = static function( string $key ) use ( &$appData ): string { return self::line( \Nino\Modules\Crm\Settings::readOnly( $appData, $key ) ); };
			$town = self::line( $read( 'seller_zip' ). ' '. $read( 'seller_city' ) );

			$organizer['name']		= self::line( \Nino\Modules\Crm\Settings::ownerName( $appData ) );
			$organizer['email']		= $read( 'seller_email' );
			$organizer['phone']		= $read( 'seller_phone' );
			$organizer['address']	= implode( "\n", array_filter( [ $read( 'seller_street' ), $town ], static fn( string $line ): bool => $line !== '' ) );
		}

		/**
		 *	A value as one line of text: a byte that is no UTF-8 replaced, every run of control characters and blanks - the line breaks and the null byte among them - one blank, the ends trimmed
		 *
		 *	@param		string		$value
		 *
		 *	@return 	string
		 */
		private static function line( string $value ): string {

			return trim( (string) preg_replace( '/[\p{C}\s]+/u', ' ', mb_scrub( $value, 'UTF-8' ) ) );
		}
	}
}
