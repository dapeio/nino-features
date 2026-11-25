<?php
declare(strict_types=1);

namespace Nino\Modules\Crm\Projects {

	use Nino\Modules\Crm\Api as Endpoint;
	use Nino\Modules\Crm\Clients;
	use Nino\Modules\Crm\Logbook;
	use Nino\Modules\Crm\Projects;
	use Nino\Modules\Crm\Tasks;

	/**
	 *	Projects\Api		The actions of the projects behind the endpoint: what the screens of the projects read beside the element itself - the title, the client, the note, the day and the switch come from the
	 *											type /crm-projects, the figures the packages add and the counts from what the packages answer to the hooks of Projects - and the state of a project: its phase, its mark and its releases.
	 *											The projects are made, changed and removed through the actions of the Elements panel that Values answers; these actions read, or change what the table project_states and project_shares hold:
	 *
	 *											crm-projects/list			the projects (a client's, the open ones, a search in the title or in P-0001) - and, for a client's, shared: the projects of other clients that are released to it - with their client
	 *																						and the figures the packages add; native (the language the screens save in) and phases
	 *											crm-projects/get			one project with its client, its phase, what holds back what a package shows the client (waiting: its id and number, or null), how many tasks are open and the clients it is
	 *																						released to; the data of a package is the package's own action
	 *											crm-projects/phase		put a project into a phase or take it out of one (id, phase: the key of a phase or empty for none)
	 *											crm-projects/favorite	mark a project as a favorite of the owner or take the mark off (id, on)
	 *											crm-projects/shares		the clients a project is released to (id): shares and owner, the id of the client the project belongs to
	 *											crm-projects/share		release a project to a client (id, client: the id of a client): created (false where it was released to that client already: nothing is changed) and shares
	 *											crm-projects/keep			make a release that a package made the owner's own (id, client): kept and shares; 404 crm_projects_none where the project is not released to that client
	 *											crm-projects/unshare	end the release of a project to a client (id, client): removed (false where there was none) and shares
	 *
	 *											It validates what it is posted and answers the failures a person can cause with codes; the rules of the data are Projects'. A database failure is answered 500 with the code crm_database.
	 *
	 *	@package						Dape/Nino
	 */
	class Api {

		/**
		 *	@return 	array											action => [ class, method ]
		 */
		public static function actions(): array {

			return [
				'crm-projects/list'     => [ self::class, 'apiList' ],
				'crm-projects/get'      => [ self::class, 'apiGet' ],
				'crm-projects/phase'    => [ self::class, 'apiPhase' ],
				'crm-projects/favorite' => [ self::class, 'apiFavorite' ],
				'crm-projects/shares'   => [ self::class, 'apiShares' ],
				'crm-projects/share'    => [ self::class, 'apiShare' ],
				'crm-projects/unshare'  => [ self::class, 'apiUnshare' ],
				'crm-projects/keep'     => [ self::class, 'apiKeep' ],
			];
		}

		/**
		 *	The projects by the filters: client (the id of a client), open (leave out the done ones) and q (part of the title)
		 */
		public static function apiList( array &$appData, array &$request ): void {

			if( \Nino\Modules\Crm\Values::permit( $appData, $request, \Nino\Modules\Crm::MANAGE_PERM ) === false )
				return;

			$data = \Nino\Modules\Crm\Values::posted();
			$client = \Nino\Modules\Crm\Values::string( $data, 'client' );

			if( $client !== '' && preg_match( '/^[1-9][0-9]{0,9}$/D', $client ) !== 1 ) {
				\Nino\Http::fail( $request, 400, 'the filter is not valid', 'crm_projects_format', [], 'client' );
				return;
			}

			if( array_key_exists( 'open', $data ) === true && is_bool( $data['open'] ) === false ) {
				\Nino\Http::fail( $request, 400, 'open is true or false', 'crm_projects_format', [], 'open' );
				return;
			}

			Endpoint::run( $request, static function() use ( &$appData, &$request, $data, $client ): void {

				$number = null;
				$head = [ 'missing' => Projects::missing( $appData ), 'native' => \Nino\Locales::getNativeLocale( $appData ), 'phases' => Projects::phaseList( $appData ) ];

				if( $client !== '' ) {

					$found = Clients::get( $appData, (int) $client );

					// A client that is not there has no projects
					if( $found === null ) {
						\Nino\Http::ok( $request, $head + [ 'projects' => [], 'shared' => [] ] );
						return;
					}

					$number = $found['number'];
				}

				$rows = Projects::list( $appData, $number, ( $data['open'] ?? false ) === true, \Nino\Modules\Crm\Values::string( $data, 'q' ) );

				\Nino\Http::ok( $request, $head + [ 'projects' => self::shown( $appData, $rows ) ] + ( $client === '' ? [] : [ 'shared' => Projects::sharedWith( $appData, (int) $client ) ] ) );
			} );
		}

		/**
		 *	One project: id
		 */
		public static function apiGet( array &$appData, array &$request ): void {

			if( \Nino\Modules\Crm\Values::permit( $appData, $request, \Nino\Modules\Crm::MANAGE_PERM ) === false )
				return;

			Endpoint::run( $request, static function() use ( &$appData, &$request ): void {

				$id = \Nino\Modules\Crm\Values::string( \Nino\Modules\Crm\Values::posted(), 'id' );

				if( Projects::missing( $appData ) === true ) {
					\Nino\Http::fail( $request, 404, 'the element type of the projects is not there', 'crm_projects_missing', [], 'id' );
					return;
				}

				$project = Projects::get( $appData, $id );

				if( $project === null ) {
					\Nino\Http::fail( $request, 404, 'there is no such project', 'crm_projects_unknown', [], 'id' );
					return;
				}

				// The figures come with the list of the client's projects: one question for all of them
				$row = $project;

				foreach( Projects::list( $appData, $project['client'] ) as $one )
					if( $one['id'] === $id )
						$row = $one;

				$shown = self::shown( $appData, [ $row ] )[0];
				$waiting = $shown['client_id'] === 0 ? null : Projects::waiting( $appData, $id, $shown['client_id'] );

				\Nino\Http::ok( $request, [
					'native'  => \Nino\Locales::getNativeLocale( $appData ),
					'phases'  => Projects::phaseList( $appData ),
					'project' => $shown,
					'waiting' => $waiting === null ? null : [ 'id' => $waiting['id'], 'number' => $waiting['label'] ],
					'client'  => $shown['client_id'] === 0 ? null : [ 'id' => $shown['client_id'], 'number' => $shown['client'], 'title' => $shown['client_title'], 'active' => $shown['client_active'] ],
					// How many tasks of the project are open: the badge of the tab of the tasks
					'tasks'   => Tasks::openCount( $appData, $id ),
					// The clients the project is released to: the badge and the list of the tab of the releases
					'shares'  => Projects::sharesOf( $appData, $id ),
				] );
			} );
		}

		/**
		 *	Put a project into a phase: id and phase (one of Projects::phaseKeys(), or '' to take it out of one). Answers the project's id and its phase
		 */
		public static function apiPhase( array &$appData, array &$request ): void {

			if( \Nino\Modules\Crm\Values::permit( $appData, $request, \Nino\Modules\Crm::MANAGE_PERM ) === false )
				return;

			Endpoint::run( $request, static function() use ( &$appData, &$request ): void {

				$data = \Nino\Modules\Crm\Values::posted();
				$id = \Nino\Modules\Crm\Values::string( $data, 'id' );

				if( is_string( $data['phase'] ?? null ) === false || ( $data['phase'] !== '' && in_array( $data['phase'], Projects::phaseKeys( $appData ), true ) === false ) ) {
					\Nino\Http::fail( $request, 400, 'the phase is none of the phases', 'crm_projects_phase', [], 'phase' );
					return;
				}

				if( Projects::missing( $appData ) === true ) {
					\Nino\Http::fail( $request, 404, 'the element type of the projects is not there', 'crm_projects_missing', [], 'id' );
					return;
				}

				$state = Projects::setPhase( $appData, $id, $data['phase'] );

				if( is_string( $state ) === true ) {
					\Nino\Http::fail( $request, 404, 'there is no such project', 'crm_projects_unknown', [], 'id' );
					return;
				}

				Endpoint::made( $appData, $data['phase'] === '' ? 'Took project '. $id. ' out of its phase' : 'Put project '. $id. ' into the phase '. $data['phase'] );
				\Nino\Http::ok( $request, [ 'id' => $id, 'phase' => $state['phase'] ] );
			} );
		}

		/**
		 *	Mark a project as a favorite or take the mark off: id and on (true or false). Answers the project's id and whether it is a favorite
		 */
		public static function apiFavorite( array &$appData, array &$request ): void {

			if( \Nino\Modules\Crm\Values::permit( $appData, $request, \Nino\Modules\Crm::MANAGE_PERM ) === false )
				return;

			Endpoint::run( $request, static function() use ( &$appData, &$request ): void {

				$data = \Nino\Modules\Crm\Values::posted();
				$id = \Nino\Modules\Crm\Values::string( $data, 'id' );

				if( is_bool( $data['on'] ?? null ) === false ) {
					\Nino\Http::fail( $request, 400, 'on is true or false', 'crm_projects_format', [], 'on' );
					return;
				}

				if( Projects::missing( $appData ) === true ) {
					\Nino\Http::fail( $request, 404, 'the element type of the projects is not there', 'crm_projects_missing', [], 'id' );
					return;
				}

				$state = Projects::setFavorite( $appData, $id, $data['on'] );

				if( is_string( $state ) === true ) {
					\Nino\Http::fail( $request, 404, 'there is no such project', 'crm_projects_unknown', [], 'id' );
					return;
				}

				Endpoint::made( $appData, $data['on'] === true ? 'Marked project '. $id. ' as a favorite' : 'Took the favorite mark off project '. $id );
				\Nino\Http::ok( $request, [ 'id' => $id, 'favorite' => $state['favorite'] ] );
			} );
		}

		/**
		 *	The clients a project is released to: id
		 */
		public static function apiShares( array &$appData, array &$request ): void {

			if( \Nino\Modules\Crm\Values::permit( $appData, $request, \Nino\Modules\Crm::MANAGE_PERM ) === false )
				return;

			Endpoint::run( $request, static function() use ( &$appData, &$request ): void {

				$project = self::sharedProject( $appData, $request, \Nino\Modules\Crm\Values::posted() );

				if( $project !== null )
					\Nino\Http::ok( $request, self::sharing( $appData, $project ) );
			} );
		}

		/**
		 *	Release a project to a client: id and client (the id of the client)
		 */
		public static function apiShare( array &$appData, array &$request ): void {

			self::change( $appData, $request, 'crm-projects/share' );
		}

		/**
		 *	End the release of a project to a client: id and client
		 */
		public static function apiUnshare( array &$appData, array &$request ): void {

			self::change( $appData, $request, 'crm-projects/unshare' );
		}

		/**
		 *	Take a release over as the owner's own: id and client
		 */
		public static function apiKeep( array &$appData, array &$request ): void {

			self::change( $appData, $request, 'crm-projects/keep' );
		}

		/**
		 *	The three actions that change a release
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		&$request
		 *	@param		string		$action
		 *
		 *	@return 	void
		 */
		private static function change( array &$appData, array &$request, string $action ): void {

			if( \Nino\Modules\Crm\Values::permit( $appData, $request, \Nino\Modules\Crm::MANAGE_PERM ) === false )
				return;

			Endpoint::run( $request, static function() use ( &$appData, &$request, $action ): void {

				$data = \Nino\Modules\Crm\Values::posted();
				$client = \Nino\Modules\Crm\Values::string( $data, 'client' );

				if( preg_match( '/^[1-9][0-9]{0,9}$/D', $client ) !== 1 ) {
					\Nino\Http::fail( $request, 400, 'the client is not valid', 'crm_projects_client_id', [], 'client' );
					return;
				}

				$project = self::sharedProject( $appData, $request, $data );

				if( $project === null )
					return;

				$done = match( $action ) { 'crm-projects/share' => Projects::shareCrm( $appData, $project, (int) $client ), 'crm-projects/keep' => Projects::keep( $appData, $project, (int) $client ), default => Projects::unshare( $appData, $project, (int) $client ) };

				if( is_string( $done ) === true ) {

					match( $done ) {
						'owner'   => \Nino\Http::fail( $request, 409, 'the project belongs to this client', 'crm_projects_owner', [], 'client' ),
						'project' => \Nino\Http::fail( $request, 404, 'there is no such project', 'crm_projects_unknown', [], 'id' ),
						'none'    => \Nino\Http::fail( $request, 404, 'the project is not released to this client', 'crm_projects_none', [], 'client' ),
						default   => \Nino\Http::fail( $request, 404, 'there is no such client', 'crm_projects_client', [], 'client' ),
					};

					return;
				}

				Endpoint::made( $appData, match( $action ) { 'crm-projects/share' => 'Released project '. $project. ' to a client', 'crm-projects/keep' => 'Took over a release of project '. $project. ' to a client', default => 'Ended the release of project '. $project. ' to a client' } );

				$word = match( $action ) { 'crm-projects/share' => 'created', 'crm-projects/keep' => 'kept', default => 'removed' };

				\Nino\Http::ok( $request, [ $word => $done[$word] ] + self::sharing( $appData, $project ) );
			} );
		}

		/**
		 *	The project a post names, which is there: its id
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		&$request
		 *	@param		array 		$data
		 *
		 *	@return 	string|null								The id; null after the answer that says why not
		 */
		private static function sharedProject( array &$appData, array &$request, array $data ): ?string {

			$id = \Nino\Modules\Crm\Values::string( $data, 'id' );

			if( Projects::missing( $appData ) === true ) {
				\Nino\Http::fail( $request, 404, 'the element type of the projects is not there', 'crm_projects_missing', [], 'id' );
				return null;
			}

			if( Projects::get( $appData, $id ) === null ) {
				\Nino\Http::fail( $request, 404, 'there is no such project', 'crm_projects_unknown', [], 'id' );
				return null;
			}

			return $id;
		}

		/**
		 *	The answer of the actions of the releases: the releases of the project, and the id of the client it belongs to
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$id
		 *
		 *	@return 	array
		 */
		private static function sharing( array &$appData, string $id ): array {

			return [ 'shares' => Projects::sharesOf( $appData, $id ), 'owner' => Logbook::clientOf( $appData, $id ) ?? 0 ];
		}

		/**
		 *	The projects with what the screens show beside them: the client's id and title
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		$projects			As Projects::list() has them
		 *
		 *	@return 	array
		 */
		private static function shown( array &$appData, array $projects ): array {

			$clients = [];

			foreach( Clients::list( $appData ) as $client )
				$clients[$client['number']] = $client;

			$shown = [];

			foreach( $projects as $project ) {

				$client = $clients[$project['client']] ?? null;

				$shown[] = $project + [
					'client_id'     => $client === null ? 0 : $client['id'],
					'client_title'  => $client === null ? '' : $client['title'],
					'client_active' => $client !== null && $client['active'] === true,
				];
			}

			return $shown;
		}
	}
}
