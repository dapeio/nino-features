<?php
declare(strict_types=1);

namespace Nino\Modules\Crm\Surface {

	/**
	 *	Data		What a package adds to the data block of the page /crm: the block is the one JSON object the script reads at the start, and a package's part of it stands under packages.<key>, where the
	 *						script of the package reads it with kit.data( key ). The answer is made for the request of the page (never cached) and holds what the account of that request may see - the method is asked
	 *						with the account signed in, after the gate - and nothing a visitor of the login page gets: the login has no data block of packages.
	 *
	 *	@package						Dape/Nino
	 */
	class Data {

		// What the module keeps for the request: the entries
		private const string REGISTER = './crm/surface/data';

		/**
		 *	Add the data of a package to the page, under the key of the package: one entry for each package; the same entry again is as good as the first time
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		$source				[ class, method ]: the method ( array &$appData ): array answers what goes into the block
		 *	@param		array 		$opts					package (required): the key of the package, see Register::package(); the data stand under it
		 *
		 *	@return 	bool											false, with a warning, where the entry is refused
		 */
		public static function add( array &$appData, array $source, array $opts = [] ): bool {

			return \Nino\Modules\Crm\Register::add( $appData, self::REGISTER, 'page data', [ [ 'package' => $opts['package'] ?? null, 'source' => $source ] + array_diff_key( $opts, [ 'package' => 1 ] ) ], [
				'id' => 'package',
				'idRule' => \Nino\Modules\Crm\Register::packageRule( $appData ),
				'fields' => [ 'source' => \Nino\Modules\Crm\Register::REQUIRED ],
				'rules' => [ 'source' => static function( mixed $source ) use ( &$appData ): bool { return \Nino\Modules\Crm\Register::callable( $appData, $source, 1, [ 0 ] ); } ],
			] );
		}

		/**
		 *	The data of the packages as the page carries it: what each method answers, as a list or an object, under the key of its package. A method that answers none is left out - the page is made
		 *	without it and the warning says which
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	array											key => what the package answered
		 */
		public static function all( array &$appData ): array {

			$data = [];

			foreach( \Nino\Modules\Crm\Register::all( $appData, self::REGISTER ) as $key => $entry ) {

				$answer = $entry['source']( $appData );

				if( is_array( $answer ) === false ) {
					trigger_error( 'Crm: the page data of "'. $key. '" were left out: the method did not answer a list', E_USER_WARNING );
					continue;
				}

				$data[$key] = $answer;
			}

			return $data;
		}
	}
}
