<?php
declare(strict_types=1);

namespace Nino\Modules\Crm {

	/**
	 *	Sequences		The number sequences of the core and of the packages: one counter for each category and year in the table sequences, taken inside the write transaction of the
	 *							caller. The counter is read and raised in the transaction that spends the number, so a number is spent with what it numbers and with nothing else - a rollback
	 *							gives it back, and the numbers of a category and year are gapless. A sequence begins at the start number of its category - 1 unless the owner chose another -:
	 *							the first number of a year gets it, and a sequence that is below it is lifted to it by the next number - a start number raised later moves the counting forward, one
	 *							that lies below what was given changes nothing, so no number is given twice.
	 *
	 *							A category is the slug of a kind of number (client, offer, invoice) and belongs to the package that entered it with add(): a category another package has is
	 *							refused, the same one again is as good as the first time. The core enters client, whose year is 0. How a number is written - its format, its prefix - is the
	 *							package's; this class counts.
	 *
	 *	@package						Dape/Nino
	 */
	class Sequences {

		/**	The key of $appData that holds the categories entered, category => package	*/
		private const string REGISTER = './crm/sequences/categories';

		/**
		 *	Enter a category of numbers
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		$spec					category (a slug, at most 40 characters) and package (the key of the feature, a slug)
		 *
		 *	@return 	bool											false, with a warning, where the description is refused - a category of another package, a slug that is none - and nothing is entered
		 */
		public static function add( array &$appData, array $spec ): bool {

			return self::enter( $appData, $spec, false );
		}

		/**
		 *	The category of the core: the clients, entered under the package crm
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	bool
		 */
		public static function init( array &$appData ): bool {

			return self::enter( $appData, [ 'category' => 'client', 'package' => Api::CORE ], true );
		}

		/**
		 *	@param		array 		&$appData
		 *	@param		array 		$spec
		 *	@param		bool			$core					The core enters itself
		 *
		 *	@return 	bool
		 */
		private static function enter( array &$appData, array $spec, bool $core ): bool {

			return Register::add( $appData, self::REGISTER, 'number sequence', [ $spec ], [
				'id' => 'category',
				'idRule' => static fn( string $category ): bool => Register::word( $category ),
				'fields' => [ 'package' => Register::REQUIRED ],
				'rules' => [ 'package' => Register::packageRule( $appData, $core ) ],
			] );
		}

		/**
		 *	The categories entered, with the package of each
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	array											category => package, in the order they were entered
		 */
		public static function categories( array &$appData ): array {

			return array_map( static fn( array $entry ): string => (string) $entry['package'], Register::all( $appData, self::REGISTER ) );
		}

		/**
		 *	The next number of a category in a year. The caller holds a write transaction (BEGIN IMMEDIATE)
		 *
		 *	@param		array 		&$appData
		 *	@param		\PDO			$pdo
		 *	@param		string		$category			The slug of a category that was entered with add()
		 *	@param		int				$year					0 for a category that counts over the years
		 *	@param		int				$start				The start number of the category: the least the counter is
		 *
		 *	@return 	int												The number, 1 or more
		 *
		 *	@throws		\InvalidArgumentException	A category that is no slug or that nobody entered, a year below 0
		 */
		public static function next( array &$appData, \PDO $pdo, string $category, int $year, int $start = 1 ): int {

			self::guard( $category, $year );
			self::entered( $appData, $category );
			$start = max( 1, $start );

			$statement = $pdo->prepare( 'INSERT OR IGNORE INTO sequences ( category, year, next ) VALUES ( :category, :year, :start )' );
			$statement->execute( [ 'category' => $category, 'year' => $year, 'start' => $start ] );

			// A sequence that is there and below the start number is lifted to it
			$statement = $pdo->prepare( 'UPDATE sequences SET next = :start WHERE category = :category AND year = :year AND next < :start' );
			$statement->execute( [ 'category' => $category, 'year' => $year, 'start' => $start ] );

			$statement = $pdo->prepare( 'SELECT next FROM sequences WHERE category = :category AND year = :year' );
			$statement->execute( [ 'category' => $category, 'year' => $year ] );
			$counter = (int) $statement->fetchColumn();

			$statement = $pdo->prepare( 'UPDATE sequences SET next = next + 1 WHERE category = :category AND year = :year' );
			$statement->execute( [ 'category' => $category, 'year' => $year ] );

			return $counter;
		}

		/**
		 *	The number a category would get next in a year, as it is now: read, never taken - the counter does not move. A year with no number yet starts at the start
		 *	number, and so does a sequence that is below it
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$category
		 *	@param		int				$year
		 *	@param		int				$start
		 *
		 *	@return 	int
		 *
		 *	@throws		\InvalidArgumentException	A category that is no slug or that nobody entered, a year below 0
		 */
		public static function upcoming( array &$appData, string $category, int $year, int $start = 1 ): int {

			self::guard( $category, $year );
			self::entered( $appData, $category );

			$row = Db::row( $appData, 'SELECT next FROM sequences WHERE category = :category AND year = :year', [ 'category' => $category, 'year' => $year ] );

			return max( 1, $start, (int) ( $row['next'] ?? 1 ) );
		}

		/**
		 *	Make sure the next number of a sequence is at least a given one: what a deletion calls inside its transaction, so that the number it spent is never given again.
		 *	The counter never goes down
		 *
		 *	@param		callable	$run					The statement runner of the transaction, see Db::runner()
		 *	@param		string		$category
		 *	@param		int				$year
		 *	@param		int				$least				The least the next number is
		 *
		 *	@return 	void
		 *
		 *	@throws		\InvalidArgumentException	A category that is no slug, a year below 0
		 */
		public static function floor( callable $run, string $category, int $year, int $least ): void {

			self::guard( $category, $year );

			$run( 'INSERT INTO sequences ( category, year, next ) VALUES ( :category, :year, :next ) ON CONFLICT( category, year ) DO UPDATE SET next = MAX( next, excluded.next )',
				[ 'category' => $category, 'year' => $year, 'next' => $least ] );
		}

		/**
		 *	A category nobody entered counts nothing: a number of a kind the core does not know is a typing mistake, and a counter made for it would stay in the table for good
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$category
		 *
		 *	@return 	void
		 *
		 *	@throws		\InvalidArgumentException
		 */
		private static function entered( array &$appData, string $category ): void {

			if( isset( self::categories( $appData )[$category] ) === false )
				throw new \InvalidArgumentException( 'There is no number sequence "'. $category. '" entered' );
		}

		/**
		 *	@param		string		$category
		 *	@param		int				$year
		 *
		 *	@return 	void
		 *
		 *	@throws		\InvalidArgumentException
		 */
		private static function guard( string $category, int $year ): void {

			if( Register::word( $category ) === false )
				throw new \InvalidArgumentException( 'There is no number sequence "'. $category. '"' );

			if( $year < 0 )
				throw new \InvalidArgumentException( 'A year of a number sequence is 0 or more' );
		}
	}
}
