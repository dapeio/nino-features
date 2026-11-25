<?php
declare(strict_types=1);

namespace Nino\Modules\Crm {

	/**
	 *	Format		How the core writes what several parts show: a day as d.m.Y (day()) and a country by its name in a language (country()). The money of the documents is
	 *							the documents', the name of a document its own.
	 *
	 *	@package						Dape/Nino
	 */
	class Format {

		/**
		 *	d.m.Y of a Y-m-d
		 *
		 *	@param		string		$date
		 *
		 *	@return 	string										'' for anything that is not a day written Y-m-d
		 */
		public static function day( string $date ): string {

			return preg_match( '/^(\d{4})-(\d{2})-(\d{2})$/D', $date, $found ) === 1 ? $found[3]. '.'. $found[2]. '.'. $found[1] : '';
		}

		/**
		 *	The name of a country in a language where the intl extension is loaded, its code where it is not
		 *
		 *	@param		string		$code					Two capital letters
		 *	@param		string		$locale				The language the name is written in, a locale of the site (de_DE)
		 *
		 *	@return 	string
		 */
		public static function country( string $code, string $locale ): string {

			if( extension_loaded( 'intl' ) === false || preg_match( '/^[A-Z]{2}$/D', $code ) !== 1 || preg_match( \Nino\Locales::PATTERN, $locale ) !== 1 )
				return $code;

			$name = \Locale::getDisplayRegion( '-'. $code, $locale );

			return $name === '' ? $code : $name;
		}
	}
}
