<?php
declare(strict_types=1);

namespace Nino\Modules\Crm\Logbook {

	use Nino\Modules\Crm\Api as Endpoint;
	use Nino\Modules\Crm\Db;
	use Nino\Modules\Crm\Logbook;
	use Nino\Modules\Crm\Projects;

	/**
	 *	Logbook\Api		The actions of the logbook behind the endpoint: the tab of the card of a client and of a project reads it and adds the owner's own line through these actions, both under the permission of the CRM:
	 *
	 *										crm-logbook/list		the entries of a client (client) or a project (project), the newest first: entries - id, at, source, kind, text, by, data (the values of an entry whose sentence a script says: the core's own and the kinds a package entered; none for a sentence a package wrote),
	 *																				project (its id, '' for none), project_label (P-0001 and the title) and project_known (whether the project is there still: a link to its card) - and more (whether older entries are
	 *																				there). Page: PAGE entries; before (the id of the last entry of the page before) asks for the one that follows it
	 *										crm-logbook/add			the line the owner writes (client or project, text): entry, the line as list has it. It is the only kind a post can make; the entries of the core and of the packages come from the code
	 *
	 *										The client is a client that is there, the project a project that is there (404 crm_logbook_unknown); a post that names both or neither, or an id that is none, is 400 crm_logbook_scope. The text of a
	 *										line is one line of at most Logbook::TEXT characters (400 crm_logbook_format, crm_logbook_empty, crm_logbook_long). No action changes or deletes an entry.
	 *
	 *	@package						Dape/Nino
	 */
	class Api {

		/**
		 *	@return 	array											action => [ class, method ]
		 */
		public static function actions(): array {

			return [ 'crm-logbook/list' => [ self::class, 'apiList' ], 'crm-logbook/add' => [ self::class, 'apiAdd' ] ];
		}

		/**
		 *	The entries of a client or a project: client or project, and before
		 */
		public static function apiList( array &$appData, array &$request ): void {

			if( \Nino\Modules\Crm\Values::permit( $appData, $request, \Nino\Modules\Crm::MANAGE_PERM ) === false )
				return;

			Endpoint::run( $request, static function() use ( &$appData, &$request ): void {

				$data = \Nino\Modules\Crm\Values::posted();
				$scope = self::scope( $appData, $request, $data );
				$before = null;

				if( $scope === null )
					return;

				if( array_key_exists( 'before', $data ) === true ) {

					$before = Endpoint::id( $request, $data, 'crm_logbook_scope', 'before' );

					if( $before === null )
						return;
				}

				$page = Logbook::of( $appData, $scope, $before );

				if( is_string( $page ) === true ) {
					\Nino\Http::fail( $request, 400, 'the page is not valid', 'crm_logbook_scope', [], 'before' );
					return;
				}

				\Nino\Http::ok( $request, [ 'entries' => self::rows( $appData, $page['rows'] ), 'more' => $page['more'] ] );
			} );
		}

		/**
		 *	The line of the owner: client or project, and text
		 */
		public static function apiAdd( array &$appData, array &$request ): void {

			if( \Nino\Modules\Crm\Values::permit( $appData, $request, \Nino\Modules\Crm::MANAGE_PERM ) === false )
				return;

			Endpoint::run( $request, static function() use ( &$appData, &$request ): void {

				$data = \Nino\Modules\Crm\Values::posted();
				$scope = self::scope( $appData, $request, $data );

				if( $scope === null )
					return;

				$text = Logbook::clean( $data['text'] ?? null );

				if( is_array( $text ) === true && $text[0] === '' )
					$text = 'empty';

				if( is_string( $text ) === true ) {
					\Nino\Http::fail( $request, 400, 'the text is not valid', 'crm_logbook_'. $text, $text === 'long' ? [ Logbook::TEXT ] : [], 'text' );
					return;
				}

				$user = \Nino\Auth::getCurrentUser( $appData );
				$entry = Logbook::note( $appData, $scope, $text[0], $user === false ? 'system' : (string) $user['mail'] );

				if( is_string( $entry ) === true ) {
					if( $entry === 'write' )
						\Nino\Http::fail( $request, 500, 'the line could not be written', 'crm_database', [], 'text' );
					else
						\Nino\Http::fail( $request, 400, 'the line could not be written', 'crm_logbook_format', [], 'text' );

					return;
				}

				\Nino\Http::ok( $request, [ 'entry' => self::rows( $appData, [ $entry ] )[0] ] );
			} );
		}

		/**
		 *	The client or the project a post names: exactly one, and one that is there
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		&$request
		 *	@param		array 		$data
		 *
		 *	@return 	array|null								[ 'client' => id ] or [ 'project' => id ]; null after the answer that says why not
		 */
		private static function scope( array &$appData, array &$request, array $data ): ?array {

			$client = \Nino\Modules\Crm\Values::string( $data, 'client' );
			$project = \Nino\Modules\Crm\Values::string( $data, 'project' );

			if( ( $client === '' ) === ( $project === '' ) || ( $client !== '' && preg_match( '/^[1-9][0-9]{0,9}$/D', $client ) !== 1 ) || ( $project !== '' && preg_match( Projects::ID, $project ) !== 1 ) ) {
				\Nino\Http::fail( $request, 400, 'name a client or a project', 'crm_logbook_scope' );
				return null;
			}

			$there = $client !== '' ? Db::row( $appData, 'SELECT id FROM clients WHERE id = :id', [ 'id' => (int) $client ] ) !== null : Projects::get( $appData, $project ) !== null;

			if( $there === false ) {
				\Nino\Http::fail( $request, 404, 'there is no such client or project', 'crm_logbook_unknown' );
				return null;
			}

			return $client !== '' ? [ 'client' => (int) $client ] : [ 'project' => $project ];
		}

		/**
		 *	The entries as the surface draws them
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		$entries			As Logbook::of() has them
		 *
		 *	@return 	array
		 */
		private static function rows( array &$appData, array $entries ): array {

			$titles = array_filter( $entries, static fn( array $entry ): bool => $entry['project'] !== '' ) === [] || Projects::missing( $appData ) === true ? [] : Projects::titles( $appData );
			$rows = [];

			foreach( $entries as $entry ) {

				// The values of an entry are for the script that says its sentence: the core's own and those of a kind a package entered; a sentence a package wrote itself needs none
				$own = $entry['source'] === Logbook::SOURCE || Logbook::kindOf( $appData, $entry['source'], $entry['kind'] );

				$rows[] = [
					'id'            => $entry['id'],
					'at'            => $entry['at'],
					'source'        => $entry['source'],
					'kind'          => $entry['kind'],
					'text'          => $entry['text'],
					'by'            => $entry['by'],
					'data'          => (object) ( $own === true ? $entry['data'] : [] ),
					'project'       => $entry['project'],
					'project_label' => Projects::named( $entry['project'], (string) ( $titles[$entry['project']] ?? '' ) ),
					'project_known' => isset( $titles[$entry['project']] ),
				];
			}

			return $rows;
		}
	}
}
