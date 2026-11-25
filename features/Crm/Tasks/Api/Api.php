<?php
declare(strict_types=1);

namespace Nino\Modules\Crm\Tasks {

	use Nino\Modules\Crm\Api as Endpoint;
	use Nino\Modules\Crm\Projects;
	use Nino\Modules\Crm\Tasks;

	/**
	 *	Tasks\Api		The actions of the tasks behind the endpoint: the tab of the tasks of the card of a project and the app of the open tasks read and change them through these actions, all under the permission of the
	 *									CRM. Every action that writes answers with the whole list of the project, so that the screen draws it from the answer:
	 *
	 *									crm-tasks/list		the tasks of a project (project): tasks - id, text, done, done_at, created_at, source and ref of each, the open ones from the top and then the done ones - and open (how many are open)
	 *									crm-tasks/open		the projects the app lists: projects (those with open tasks) and others (those not finished that have none), each with id, label, title, client_title and open
	 *									crm-tasks/add			make a task at the top (project, text)
	 *									crm-tasks/text		change the text of a task (id, text)
	 *									crm-tasks/done		mark a task as done or open it again (id, on: true or false); an opened task is the top of the open ones
	 *									crm-tasks/move		swap an open task with the open one above or below it (id, dir: up or down); the first and the last are the edge (409 crm_tasks_edge)
	 *									crm-tasks/delete	delete a task (id)
	 *
	 *									The text is a string of at most Tasks::TEXT characters that is not empty, with no control character but the line break (400 crm_tasks_format, crm_tasks_empty, crm_tasks_long); a project holds at most
	 *									Tasks::PER_PROJECT tasks (409 crm_tasks_full); the project is an id of the type and a project that is there (400 crm_tasks_project, 404 crm_tasks_unknown, as for a task nobody has). Nothing in an answer
	 *									names an account. The activity log gets no line. A database failure is answered 500 with the code crm_database.
	 *
	 *	@package						Dape/Nino
	 */
	class Api {

			public static function actions(): array {
			return [
				'crm-tasks/list'   => [ self::class, 'apiList' ],
				'crm-tasks/open'   => [ self::class, 'apiOpen' ],
				'crm-tasks/add'    => [ self::class, 'apiAdd' ],
				'crm-tasks/text'   => [ self::class, 'apiText' ],
				'crm-tasks/done'   => [ self::class, 'apiDone' ],
				'crm-tasks/move'   => [ self::class, 'apiMove' ],
				'crm-tasks/delete' => [ self::class, 'apiDelete' ],
			];
		}

		/**
		 *	The tasks of a project: project (its id)
		 */
		public static function apiList( array &$appData, array &$request ): void {

			if( \Nino\Modules\Crm\Values::permit( $appData, $request, \Nino\Modules\Crm::MANAGE_PERM ) === false )
				return;

			Endpoint::run( $request, static function() use ( &$appData, &$request ): void {

				$project = self::project( $appData, $request, \Nino\Modules\Crm\Values::posted() );

				if( $project !== null )
					self::answer( $appData, $request, $project );
			} );
		}

		/**
		 *	The projects with open tasks and the other projects that are not finished
		 */
		public static function apiOpen( array &$appData, array &$request ): void {

			if( \Nino\Modules\Crm\Values::permit( $appData, $request, \Nino\Modules\Crm::MANAGE_PERM ) === false )
				return;

			Endpoint::run( $request, static function() use ( &$appData, &$request ): void {

				\Nino\Http::ok( $request, Tasks::openProjects( $appData ) );
			} );
		}

		/**
		 *	Make a task at the top of the list: project and text
		 */
		public static function apiAdd( array &$appData, array &$request ): void {

			if( \Nino\Modules\Crm\Values::permit( $appData, $request, \Nino\Modules\Crm::MANAGE_PERM ) === false )
				return;

			Endpoint::run( $request, static function() use ( &$appData, &$request ): void {

				$data = \Nino\Modules\Crm\Values::posted();
				$project = self::project( $appData, $request, $data );
				$text = $project === null ? null : self::text( $request, $data );

				if( $project === null || $text === null )
					return;

				$user = \Nino\Auth::getCurrentUser( $appData );

				if( Tasks::add( $appData, $project, $text, $user === false ? '' : (string) $user['mail'] ) === 'full' ) {
					\Nino\Http::fail( $request, 409, 'the project holds as many tasks as it can', 'crm_tasks_full', [ Tasks::PER_PROJECT ], 'text' );
					return;
				}

				self::answer( $appData, $request, $project );
			} );
		}

		/**
		 *	Change the text of a task: id and text
		 */
		public static function apiText( array &$appData, array &$request ): void {

			if( \Nino\Modules\Crm\Values::permit( $appData, $request, \Nino\Modules\Crm::MANAGE_PERM ) === false )
				return;

			Endpoint::run( $request, static function() use ( &$appData, &$request ): void {

				$data = \Nino\Modules\Crm\Values::posted();
				$id = Endpoint::id( $request, $data, 'crm_tasks_unknown' );
				$text = $id === null ? null : self::text( $request, $data );

				if( $id === null || $text === null )
					return;

				self::change( $appData, $request, $id, static function() use ( &$appData, $id, $text ): bool { return Tasks::setText( $appData, $id, $text ); } );
			} );
		}

		/**
		 *	Mark a task as done or open it again: id and on
		 */
		public static function apiDone( array &$appData, array &$request ): void {

			if( \Nino\Modules\Crm\Values::permit( $appData, $request, \Nino\Modules\Crm::MANAGE_PERM ) === false )
				return;

			Endpoint::run( $request, static function() use ( &$appData, &$request ): void {

				$data = \Nino\Modules\Crm\Values::posted();
				$id = Endpoint::id( $request, $data, 'crm_tasks_unknown' );

				if( $id === null )
					return;

				if( is_bool( $data['on'] ?? null ) === false ) {
					\Nino\Http::fail( $request, 400, 'on is true or false', 'crm_tasks_format', [], 'on' );
					return;
				}

				self::change( $appData, $request, $id, static function() use ( &$appData, $id, $data ): bool { return Tasks::setDone( $appData, $id, $data['on'] ); } );
			} );
		}

		/**
		 *	Move an open task: id and dir
		 */
		public static function apiMove( array &$appData, array &$request ): void {

			if( \Nino\Modules\Crm\Values::permit( $appData, $request, \Nino\Modules\Crm::MANAGE_PERM ) === false )
				return;

			Endpoint::run( $request, static function() use ( &$appData, &$request ): void {

				$data = \Nino\Modules\Crm\Values::posted();
				$id = Endpoint::id( $request, $data, 'crm_tasks_unknown' );

				if( $id === null )
					return;

				if( in_array( $data['dir'] ?? null, [ 'up', 'down' ], true ) === false ) {
					\Nino\Http::fail( $request, 400, 'dir is up or down', 'crm_tasks_format', [], 'dir' );
					return;
				}

				$project = Tasks::projectOf( $appData, $id );

				if( $project === null ) {
					self::unknown( $request );
					return;
				}

				if( Tasks::move( $appData, $id, $data['dir'] ) === 'edge' ) {
					\Nino\Http::fail( $request, 409, 'the task cannot move that way', 'crm_tasks_edge' );
					return;
				}

				self::answer( $appData, $request, $project );
			} );
		}

		/**
		 *	Delete a task: id
		 */
		public static function apiDelete( array &$appData, array &$request ): void {

			if( \Nino\Modules\Crm\Values::permit( $appData, $request, \Nino\Modules\Crm::MANAGE_PERM ) === false )
				return;

			Endpoint::run( $request, static function() use ( &$appData, &$request ): void {

				$id = Endpoint::id( $request, \Nino\Modules\Crm\Values::posted(), 'crm_tasks_unknown' );

				if( $id !== null )
					self::change( $appData, $request, $id, static function() use ( &$appData, $id ): bool { return Tasks::remove( $appData, $id ); } );
			} );
		}

		/**
		 *	Do a change to a task and answer with the list of its project, or with the 404 for a task nobody has
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		&$request
		 *	@param		int				$id
		 *	@param		callable	$run					Does the change, answers whether the task was there
		 *
		 *	@return 	void
		 */
		private static function change( array &$appData, array &$request, int $id, callable $run ): void {

			$project = Tasks::projectOf( $appData, $id );

			if( $project === null || $run() === false ) {
				self::unknown( $request );
				return;
			}

			self::answer( $appData, $request, $project );
		}

		/**
		 *	The project a post names: its id in the form of the type, and a project that is there
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		&$request
		 *	@param		array 		$data
		 *
		 *	@return 	string|null								The id; null after the answer that says why not
		 */
		private static function project( array &$appData, array &$request, array $data ): ?string {

			$project = is_string( $data['project'] ?? null ) === true ? $data['project'] : '';

			if( preg_match( Projects::ID, $project ) !== 1 ) {
				\Nino\Http::fail( $request, 400, 'the project is not valid', 'crm_tasks_project', [], 'project' );
				return null;
			}

			if( Projects::get( $appData, $project ) === null ) {
				\Nino\Http::fail( $request, 404, 'there is no such project', 'crm_tasks_unknown', [], 'project' );
				return null;
			}

			return $project;
		}

		/**
		 *	The text a post carries, as Tasks::clean() makes it
		 *
		 *	@param		array 		&$request
		 *	@param		array 		$data
		 *
		 *	@return 	string|null								Null after the answer that says why not
		 */
		private static function text( array &$request, array $data ): ?string {

			$text = Tasks::clean( $data['text'] ?? null );

			if( is_string( $text ) === true ) {
				\Nino\Http::fail( $request, 400, 'the text is not valid', 'crm_tasks_'. $text, $text === 'long' ? [ Tasks::TEXT ] : [], 'text' );
				return null;
			}

			return $text[0];
		}

		/**
		 *	@param		array 		&$request
		 *
		 *	@return 	void
		 */
		private static function unknown( array &$request ): void {

			\Nino\Http::fail( $request, 404, 'there is no such task', 'crm_tasks_unknown', [], 'id' );
		}

		/**
		 *	The answer: the project and its tasks
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		&$request
		 *	@param		string		$project
		 *
		 *	@return 	void
		 */
		private static function answer( array &$appData, array &$request, string $project ): void {

			$tasks = Tasks::list( $appData, $project );

			\Nino\Http::ok( $request, [ 'project' => $project, 'tasks' => $tasks, 'open' => count( array_filter( $tasks, static fn( array $task ): bool => $task['done'] === false ) ) ] );
		}
	}
}
