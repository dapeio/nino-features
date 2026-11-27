<?php
declare(strict_types=1);

namespace Nino\Modules\CrmDocuments {

	use Nino\Modules\Crm\Sequences;

	/**
	 *	Numbers		The numbers of the documents: the counter of a category and a year is the core's (Sequences, entered by the package for offer, invoice and credit), and this class writes the number a counter gives in the format of the
	 *								settings. The counter is read and raised inside the write transaction that finalises the document, so a number is spent with its document and with nothing else: a rollback gives it back, and the numbers
	 *								of a category and a year run without a gap. A number is never given twice: the column is unique, and a number that a document has already - a format with no year that meets the next year, a counter lifted back by hand -
	 *								refuses the finalising as a whole, counter included, so that no document is numbered by a number that is taken.
	 *
	 *	@package						Dape/Nino
	 */
	class Numbers {

		/**	The categories that are numbered, and the settings that hold their format and their start number	*/
		public const array CATEGORIES = [ 'offer', 'invoice', 'credit' ];

		/**
		 *	The next number of a category in a year. The caller holds a write transaction (BEGIN IMMEDIATE)
		 *
		 *	@param		array 		&$appData
		 *	@param		\PDO			$pdo
		 *	@param		string		$category			offer, invoice or credit
		 *	@param		int				$year
		 *	@param		string		$format				A number format, see Rules::validFormat()
		 *	@param		int				$start				The start number of the category, see Settings::start(): the least the counter is
		 *
		 *	@return 	string|null								Null for a format that is none and for a number a document has already
		 */
		public static function next( array &$appData, \PDO $pdo, string $category, int $year, string $format, int $start = 1 ): ?string {

			if( Rules::validFormat( $format ) === false || in_array( $category, self::CATEGORIES, true ) === false )
				return null;

			$number = self::render( $format, $year, Sequences::next( $appData, $pdo, $category, $year, max( 1, $start ) ) );

			// Without a difference of the case: a file system that does not tell the names apart would put two documents on one file
			$statement = $pdo->prepare( 'SELECT COUNT(*) FROM documents WHERE lower( number ) = lower( :number )' );
			$statement->execute( [ 'number' => $number ] );

			return (int) $statement->fetchColumn() === 0 ? $number : null;
		}

		/**
		 *	The number a category would get next in a year, as it is now: read, never taken - the counter does not move, which is what the settings screen shows beside the format. A year with no document yet starts at the start number, and so does a sequence that is below it
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$category			offer, invoice or credit
		 *	@param		int				$year
		 *	@param		string		$format				A number format, see Rules::validFormat()
		 *	@param		int				$start				The start number of the category, see Settings::start()
		 *
		 *	@return 	string										'' for a format that is none
		 */
		public static function upcoming( array &$appData, string $category, int $year, string $format, int $start = 1 ): string {

			if( Rules::validFormat( $format ) === false || in_array( $category, self::CATEGORIES, true ) === false )
				return '';

			return self::render( $format, $year, Sequences::upcoming( $appData, $category, $year, max( 1, $start ) ) );
		}

		/**
		 *	A format filled: the year in four or two digits, the counter zero-padded to the width of the N's and never cut
		 *
		 *	@param		string		$format
		 *	@param		int				$year
		 *	@param		int				$counter
		 *
		 *	@return 	string
		 */
		public static function render( string $format, int $year, int $counter ): string {

			$format = str_replace( [ '{YYYY}', '{YY}' ], [ sprintf( '%04d', $year ), sprintf( '%02d', $year % 100 ) ], $format );

			return (string) preg_replace_callback( '/\{(N+)\}/', static fn( array $found ): string => str_pad( (string) $counter, strlen( $found[1] ), '0', STR_PAD_LEFT ), $format );
		}
	}
}
