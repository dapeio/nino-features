<?php
declare(strict_types=1);

namespace Nino\Modules\Crm {

	/**
	 *	Projects		What the owner works on for a client, read from the elements of the type /crm-projects. The projects are made, changed and removed in the screens of the surface and in the Elements panel of the
	 *								workbench (Values answers the panel's own actions for this type); this class reads them. What belongs to a project in a package names it by its id - the last part of its uri, the number the
	 *								type gives its elements (00001). Every field of a project is the same in all languages, so the project is read as the element holds it ('*'). The type itself is brought by the unit of the
	 *								core: missing() says whether it is there, and while it is not, list() has nothing.
	 *
	 *								The core knows no package that works on a project. It asks three hooks of the kernel and takes what a package answers: HOLDS '/crm/project/holds' (what keeps a project from being deleted or
	 *								from changing its client: holds(), held()), FIGURES '/crm/project/figures' (the numbers and marks a package adds to the rows of list()) and WITHHELD '/crm/project/withheld' (whether a package holds
	 *								back what it shows the client of the project: waiting()). Without a listener nothing holds a project, the rows have no figures and nothing is withheld. It tells the packages when a project is made
	 *								(ADDED '/crm/project/added', which a listener may answer with a refusal that takes the project back) and when one is gone (REMOVED '/crm/project/removed').
	 *
	 *								The phase a project is in (none by default) and whether it is a favorite of the owner are kept in the table project_states (setPhase(), setFavorite()). The phases are the elements of the type
	 *								/crm-phases (the name of an element is the key the row holds, its title the word of each language, its order the order of the work; phases() reads them): recording, mix, master and review where the
	 *								type is not there (PHASES), and the four the unit brings as elements where it is.
	 *
	 *								A project can be released to other clients (share(), unshare(), sharesOf(), sharedWith(); the table project_shares). A release is a row of the project, the client it is released to, the time, the
	 *								account that made it (or system), the source (crm for the release the owner makes in the card of the project, the key of a package for one that package makes) and a reference of the source. A project is
	 *								released to a client at most once and never to its own client; a client that is switched off may be released to - the release holds for the time it is switched on again. A release is no part of the
	 *								projects of a client: list() and ofClient() answer the projects whose client the number names, whatever else the project is released to. The release goes with the project, with the client it is released to and
	 *								with the client the project belongs to; when a project changes its client, the release to its new client ends and the others stay. The core writes the logbook entries of both (project-shared,
	 *								project-unshared).
	 *
	 *								add() is what a package calls to make a project for a client, with its own key as the source: the entry of the logbook that says it was made is the package's own, with the sentence it gives.
	 *
	 *	@package						Dape/Nino
	 */
	class Projects {

		public const string TYPE = '/crm-projects';
		private const string FILE = '/elements/crm-projects.php';

		// The hooks that ask a package what it holds of a project (see the class)
		public const string HOLDS = '/crm/project/holds';
		public const string FIGURES = '/crm/project/figures';
		public const string WITHHELD = '/crm/project/withheld';
		public const string ADDED = '/crm/project/added';
		/**	The id of the project the endpoint is deleting, in app data: the rows go in its transaction	*/
		public const string DELETING = './crm/projects/deleting';

		public const string REMOVED = '/crm/project/removed';

		// What the name of a figure of a package looks like, and the fields of a row that a figure never overwrites
		private const string FIGURE = '/^[a-z][a-z0-9_]{0,29}$/D';
		private const array ROW = [ 'id', 'title', 'client', 'description', 'started', 'done', 'phase', 'favorite', 'guest' ];

		// The most characters of the sentence of a hold
		private const int WHY = 300;

		// What an id looks like: the number the type gives (00001), or any name an element of the type has - the same pattern the screens take
		public const string ID = '/^[A-Za-z0-9][A-Za-z0-9_-]{0,99}$/D';

		// The phases of a project in the order of the work where the element type /phasen is not there; a project has one of them or none ('')
		public const array PHASES = [ 'recording', 'mix', 'master', 'review' ];

		// The element type of the phases, and the file the kernel keeps it in
		public const string PHASE_TYPE = '/crm-phases';
		private const string PHASE_FILE = '/elements/crm-phases.php';

		// What the key of a phase looks like: lower-case words divided by single hyphens, at most thirty characters
		public const string PHASE_KEY = '/^(?=.{1,30}$)[a-z][a-z0-9]*(?:-[a-z0-9]+)*$/D';

		// How many characters the title of a phase has at most
		public const int PHASE_TITLE = 60;

		// The order of a phase is a whole number from 0 to this
		public const int PHASE_ORDER = 999999;

		/**
		 *	Whether a text is a title a phase may have: one line of at most PHASE_TITLE characters, with no control character and none of the characters that turn the direction of the text around
		 *
		 *	@param		string		$title
		 *
		 *	@return 	bool
		 */
		public static function phaseTitleOk( string $title ): bool {

			return mb_strlen( $title, 'UTF-8' ) <= self::PHASE_TITLE && preg_match( '/[\x00-\x1F\x7F\x{202A}-\x{202E}\x{2066}-\x{2069}]/u', $title ) === 0;
		}

		/**
		 *	@param		mixed			$order
		 *
		 *	@return 	bool											Whether it is a whole number from 0 to PHASE_ORDER
		 */
		public static function phaseOrderOk( mixed $order ): bool {

			return is_int( $order ) === true && $order >= 0 && $order <= self::PHASE_ORDER;
		}

		// The source of a release the owner makes in the surface; a feature names itself by its key (a slug)
		public const string SHARE_SOURCE = 'crm';

		// The time of the day a state is written with
		private const string TIME = 'Y-m-d\TH:i:s\Z';

		// Set in the app data while add() inserts the element: the entry of the logbook for that project is the package's, which add() writes, and not the core's own; and what the request kept of its answers
		private const string ADDING = './crm/projects/adding';
		private const string LOOKUPS = './crm/projects/lookups';

		/**
		 *	The vetoes of the projects and of their phases, which the kernel asks wherever an element of the type is written - in the Elements panel of the workbench as at the endpoint of the CRM -, and the notice that
		 *	takes the state, the releases and the tasks of a project away with it. Nothing is read or written
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	void
		 */
		public static function init( array &$appData ): void {

			// A project a package holds is not deleted, and its client is a client that is there and stays
			\Nino\Callbacks::registerCallback( $appData, '/nino/elements/delete'. self::TYPE, [ self::class, 'callbackDelete' ] );
			\Nino\Callbacks::registerCallback( $appData, '/nino/elements'. self::TYPE. '/insert', [ self::class, 'callbackWrite' ] );
			\Nino\Callbacks::registerCallback( $appData, '/nino/elements'. self::TYPE. '/update', [ self::class, 'callbackWrite' ] );

			// A phase that a project is in is not deleted, and a phase the panel writes has the key, the title and the order the surface asks of one
			\Nino\Callbacks::registerCallback( $appData, '/nino/elements/delete'. self::PHASE_TYPE, [ self::class, 'callbackPhaseDelete' ] );
			\Nino\Callbacks::registerCallback( $appData, '/nino/elements'. self::PHASE_TYPE. '/insert', [ self::class, 'callbackPhaseWrite' ] );
			\Nino\Callbacks::registerCallback( $appData, '/nino/elements'. self::PHASE_TYPE. '/update', [ self::class, 'callbackPhaseWrite' ] );

			// A project that is deleted takes the row of its state in the database with it
			\Nino\Callbacks::registerCallback( $appData, '/nino/elements/committed', [ self::class, 'callbackCommitted' ] );

		}

		/**
		 *	Whether the type is not there
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	bool
		 */
		public static function missing( array &$appData ): bool {

			return \Nino\Filesystem::fileExists( $appData, self::FILE ) === false;
		}

		/**
		 *	Whether the element type of the phases is not there (the four of PHASES stand in its place)
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	bool
		 */
		public static function phasesMissing( array &$appData ): bool {

			return \Nino\Filesystem::fileExists( $appData, self::PHASE_FILE ) === false;
		}

		/**
		 *	The phases a project can be in, in the order of the work: the elements of the type /phasen by their field order (two of one order by their key), or - where the type is not there - the four of PHASES
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$locale				The language of the titles; '' for the current one. A language that has no title takes it from another, as the kernel does
		 *
		 *	@return 	array											key, title ('' for none: the words of the module stand in for it) and order of each
		 */
		public static function phases( array &$appData, string $locale = '' ): array {

			return (array) self::remember( $appData, 'phases|'. $locale, static function() use ( &$appData, $locale ): array {

				if( self::phasesMissing( $appData ) === true )
					return array_map( static fn( string $key, int $index ): array => [ 'key' => $key, 'title' => '', 'order' => ( $index + 1 ) * 10 ], self::PHASES, array_keys( self::PHASES ) );

				$phases = [];

				foreach( \Nino\Elements::queryElements( $appData, self::PHASE_TYPE, [], '*', [] ) as $hit ) {

					$uri = (string) ( $hit['.uri'] ?? '' );
					$key = \Nino\Elements::getElementUriFromUri( $uri );

					if( preg_match( self::PHASE_KEY, $key ) !== 1 )
						continue;

					$element = \Nino\Elements::getElementWithFallback( $appData, $uri, $locale );
					$order = is_array( $element ) === true && is_int( $element['order'] ?? null ) === true ? $element['order'] : ( is_int( $hit['order'] ?? null ) === true ? $hit['order'] : 0 );
					$phases[] = [ 'key' => $key, 'title' => is_array( $element ) === true && is_string( $element['title'] ?? null ) === true ? trim( $element['title'] ) : '', 'order' => $order ];
				}

				usort( $phases, static fn( array $a, array $b ): int => [ $a['order'], $a['key'] ] <=> [ $b['order'], $b['key'] ] );

				return $phases;
			} );
		}

		/**
		 *	The phases as the screens of the surface take them: the key and the title in the native language ('' where the phase has none: the screen says the word of the module)
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	array											key and title of each, in the order of the work
		 */
		public static function phaseList( array &$appData ): array {

			return array_map( static fn( array $phase ): array => [ 'key' => $phase['key'], 'title' => $phase['title'] ], self::phases( $appData, \Nino\Locales::getNativeLocale( $appData ) ) );
		}

		/**
		 *	@param		array 		&$appData
		 *
		 *	@return 	array											The keys of the phases, in the order of the work
		 */
		public static function phaseKeys( array &$appData ): array {

			return array_column( self::phases( $appData ), 'key' );
		}

		/**
		 *	How many projects are in a phase: every project that holds the key, the done ones among them
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$key
		 *
		 *	@return 	int
		 */
		public static function inPhase( array &$appData, string $key ): int {

			return (int) ( Db::row( $appData, 'SELECT COUNT(*) AS n FROM project_states WHERE phase = :phase', [ 'phase' => $key ] )['n'] ?? 0 );
		}

		/**
		 *	The projects, the newest first (the order of their numbers, the last made on top)
		 *
		 *	@param		array 		&$appData
		 *	@param		string|null	$clientNumber	Only the projects of this client (K-0001); null for every client's
		 *	@param		bool			$openOnly			Leave out the projects that are done
		 *	@param		string		$search				Part of the title or of the name the project is shown by, P-0001 (case does not matter, no wildcards)
		 *
		 *	@return 	array											id, title, client (the number), description, started, done of each - and the figures the packages add (figures(): one question for all the projects), phase ('' for none) and favorite; one query for all the states
		 */
		public static function list( array &$appData, ?string $clientNumber = null, bool $openOnly = false, string $search = '' ): array {

			$search = trim( $search );
			$rows = [];

			foreach( self::read( $appData ) as $project ) {

				if( $clientNumber !== null && $project['client'] !== $clientNumber )
					continue;

				if( $openOnly === true && $project['done'] === true )
					continue;

				if( $search !== '' && mb_stripos( $project['title'], $search, 0, 'UTF-8' ) === false && mb_stripos( self::label( $project['id'] ), $search, 0, 'UTF-8' ) === false )
					continue;

				$rows[] = $project;
			}

			$figures = self::figures( $appData, array_column( $rows, 'id' ) );
			$states = self::states( $appData );
			$projects = [];

			foreach( $rows as $project )
				$projects[] = $project + ( $figures[$project['id']] ?? [] ) + [ 'phase' => $states[$project['id']]['phase'] ?? '', 'favorite' => $states[$project['id']]['favorite'] ?? false ];

			return $projects;
		}

		/**
		 *	One project
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$id						The name of the element, the last part of its uri
		 *
		 *	@return 	array|null								id, title, client, description, started, done, phase, favorite; null for none
		 */
		public static function get( array &$appData, string $id ): ?array {

			if( preg_match( self::ID, $id ) !== 1 || self::missing( $appData ) === true )
				return null;

			$element = \Nino\Elements::getElement( $appData, self::TYPE. '/'. $id, '*' );

			if( is_array( $element ) === false || $element === [] )
				return null;

			$state = self::state( $appData, $id );

			return self::shape( $element + [ '.uri' => self::TYPE. '/'. $id ] ) + [ 'phase' => $state['phase'], 'favorite' => $state['favorite'] ];
		}

		/**
		 *	What the owner decided about a project for the portal
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$id						The id of a project; a project without a row, and an id that is none, has no phase and no mark
		 *
		 *	@return 	array											phase (one of phaseKeys(), '' for none) and favorite (bool)
		 */
		public static function state( array &$appData, string $id ): array {

			$row = preg_match( self::ID, $id ) === 1 ? Db::row( $appData, 'SELECT phase, favorite FROM project_states WHERE project = :project', [ 'project' => $id ] ) : null;

			return [ 'phase' => self::phaseOf( $appData, (string) ( $row['phase'] ?? '' ) ), 'favorite' => $row !== null && (int) $row['favorite'] === 1 ];
		}

		/**
		 *	Put a project into a phase, or take it out of the one it is in. The row of the project is made where it has none
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$id						The id of a project
		 *	@param		string		$phase				One of phaseKeys(), or '' for none
		 *
		 *	@return 	array|string							The state as state() has it, with the id of the project; 'unknown' for a project that is not there, 'invalid' for a phase that is none of them
		 */
		public static function setPhase( array &$appData, string $id, string $phase ): array|string {

			if( $phase !== '' && in_array( $phase, self::phaseKeys( $appData ), true ) === false )
				return 'invalid';

			if( self::get( $appData, $id ) === null )
				return 'unknown';

			Db::exec( $appData, 'INSERT INTO project_states ( project, phase ) VALUES ( :project, :phase ) ON CONFLICT ( project ) DO UPDATE SET phase = excluded.phase', [ 'project' => $id, 'phase' => $phase ] );

			self::forget( $appData );

			return [ 'id' => $id ] + self::state( $appData, $id );
		}

		/**
		 *	Mark a project as a favorite of the owner, or take the mark off. The row of the project is made where it has none, and what it holds of the phase stays
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$id						The id of a project
		 *	@param		bool			$on
		 *
		 *	@return 	array|string							The state as state() has it, with the id of the project; 'unknown' for a project that is not there
		 */
		public static function setFavorite( array &$appData, string $id, bool $on ): array|string {

			if( self::get( $appData, $id ) === null )
				return 'unknown';

			Db::exec( $appData, 'INSERT INTO project_states ( project, favorite ) VALUES ( :project, :favorite ) ON CONFLICT ( project ) DO UPDATE SET favorite = excluded.favorite', [ 'project' => $id, 'favorite' => $on === true ? 1 : 0 ] );

			self::forget( $appData );

			return [ 'id' => $id ] + self::state( $appData, $id );
		}

		/**
		 *	@param		array 		&$appData
		 *	@param		string		$phase				As the table holds it
		 *
		 *	@return 	string										The phase if it is one of phaseKeys(), else ''
		 */
		private static function phaseOf( array &$appData, string $phase ): string {

			return in_array( $phase, self::phaseKeys( $appData ), true ) === true ? $phase : '';
		}

		/**
		 *	The number the next project gets, as it is now: read, never taken - the counter of the type, never below a number a project has and never below the start number of the projects
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	string										'' where the type is not there or does not number its elements
		 */
		public static function upcoming( array &$appData ): string {

			$typeData = self::missing( $appData ) === true ? null : \Nino\Filesystem::getFileContent( $appData, self::FILE, null );
			$counter = is_array( $typeData ) === true ? \Nino\Elements::readAutoincrement( $typeData ) : null;

			return $counter === null ? '' : \Nino\Elements::autoincrementUri( max( $counter, \Nino\Elements::autoincrementSeed( $typeData ), Settings::start( $appData, 'project' ) ) );
		}

		/**
		 *	Lift the counter of the type to the start number of the projects where it is below it: the kernel numbers the next project by that counter, in the surface and in the Elements panel alike.
		 *	The counter is never lowered - a number a project had is not given again, since what belongs to a project names it by its number - and nothing is written where the counter is high enough
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	bool											False where the type file could not be written
		 */
		public static function lift( array &$appData ): bool {

			$start = Settings::start( $appData, 'project' );
			$counter = self::missing( $appData ) === true ? null : \Nino\Elements::getAutoincrement( $appData, self::TYPE );

			if( $counter === null || $counter >= $start )
				return true;

			$lifted = \Nino\Filesystem::mutate( $appData, self::FILE, static function( mixed $typeData ) use ( $start ): ?array {

				if( is_array( $typeData ) === false || \Nino\Elements::readAutoincrement( $typeData ) === null || \Nino\Elements::readAutoincrement( $typeData ) >= $start )
					return null;

				$typeData['autoincrement'] = $start;

				return $typeData;
			}, false );

			unset( $appData['./nino/elements/cache'] );

			return $lifted !== false || (int) \Nino\Elements::getAutoincrement( $appData, self::TYPE ) >= $start;
		}

		/**
		 *	Forget what waiting() and the lists kept for this request
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	void
		 */
		public static function forget( array &$appData ): void {

			unset( $appData[self::LOOKUPS] );
			Db::drop( $appData, 'projects' );
		}

		/**
		 *	What holds the files of a project back from its client: a package says so through the hook WITHHELD ('/crm/project/withheld'; the argument has project and client and
		 *	withheld, a list the listener adds to: key - the slug of the package -, id - a whole number from 1 or a name -, label - a few words as one line of text, what the owner
		 *	reads - and detail, the package's own record of what holds the files back, for the pages of the package). While something holds them the client has a step to take first;
		 *	it is taken, declined or taken back, or it runs out, and the files are open again. Every listener is asked on its own (ask()): the files are held back as soon as one of them says so, and the entry of the first listener that does is the answer (its label is what the owner reads); none where no package listens or none says so
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$project			The id of a project
		 *	@param		int				$clientId			The client the project is of
		 *
		 *	@return 	array|null								key, id, label and detail of what holds the files back; null where nothing does
		 */
		public static function waiting( array &$appData, string $project, int $clientId ): ?array {

			if( $project === '' )
				return null;

			return self::remember( $appData, 'waiting|'. $project. '|'. $clientId, static function() use ( &$appData, $project, $clientId ): ?array {

				foreach( self::ask( $appData, self::WITHHELD, [ 'project' => $project, 'client' => $clientId, 'withheld' => [] ], 'withheld' ) as $held ) {

					$key = is_array( $held ) === true && is_string( $held['key'] ?? null ) === true ? $held['key'] : '';
					$id = is_array( $held ) === true ? ( $held['id'] ?? null ) : null;

					if( preg_match( '/^[a-z0-9]+(?:-[a-z0-9]+)*$/D', $key ) !== 1 || ( is_int( $id ) === false || $id < 1 ) && ( is_string( $id ) === false || $id === '' ) )
						continue;

					return [ 'key' => $key, 'id' => $id, 'label' => self::line( $held['label'] ?? '', self::WHY ), 'detail' => is_array( $held['detail'] ?? null ) === true ? $held['detail'] : [] ];
				}

				return null;
			} );
		}

		/**
		 *	The list a listener answered with: the list of its argument, which is none where the argument holds no list at all
		 *
		 *	@param		mixed			$answer				What the argument is after the listener
		 *	@param		string		$list
		 *
		 *	@return 	array|null								The list; null where the answer is no array or the list is none
		 */
		private static function listed( mixed $answer, string $list ): ?array {

			if( is_array( $answer ) === false )
				return null;

			return array_key_exists( $list, $answer ) === false ? [] : ( is_array( $answer[$list] ) === true ? $answer[$list] : null );
		}

		/**
		 *	The answer to a question about a project, kept for the request: a page asks it for each row of a list, and it takes a handful of queries. The answer is kept with
		 *	the question it was read for, so that a second request in the same process (a test, a worker) never gets the answer of the first; a write that moves it -
		 *	a release or a state of what holds the files back, a project written or deleted - drops what was kept (forget())
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$key
		 *	@param		callable	$read
		 *
		 *	@return 	array|null
		 */
		private static function remember( array &$appData, string $key, callable $read ): ?array {

			if( array_key_exists( $key, $appData[self::LOOKUPS] ?? [] ) === false )
				$appData[self::LOOKUPS][$key] = $read();

			return $appData[self::LOOKUPS][$key];
		}

		/**
		 *	The notification of the kernel that an element was written or deleted ('/nino/elements/committed'): the row of its state and its tasks go with a project that is gone; a phase that is written or
		 *	deleted makes the list of the phases be read anew, and one of the four the unit brings that the owner deleted is remembered as deleted, so that applying the unit again does not bring it back
		 *
		 *	@param		array 		&$appData
		 *	@param		mixed			&$change			operation, type and uri
		 *
		 *	@return 	void
		 */
		public static function callbackCommitted( array &$appData, mixed &$change ): void {

			if( is_array( $change ) === true && in_array( $change['type'] ?? '', [ self::TYPE, self::PHASE_TYPE ], true ) )
				self::forget( $appData );

			if( is_array( $change ) === true && ( $change['type'] ?? '' ) === self::PHASE_TYPE ) {
				self::keepRemoved( $appData, self::PHASE_TYPE, self::PHASES, $change );
				return;
			}

			if( is_array( $change ) === true && ( $change['operation'] ?? '' ) === 'insert' && ( $change['type'] ?? '' ) === self::TYPE ) {
				self::added( $appData, \Nino\Elements::getElementUriFromUri( (string) ( $change['uri'] ?? '' ) ) );
				return;
			}

			if( is_array( $change ) === true && ( $change['operation'] ?? '' ) === 'update' && ( $change['type'] ?? '' ) === self::TYPE ) {
				self::moved( $appData, \Nino\Elements::getElementUriFromUri( (string) ( $change['uri'] ?? '' ) ) );
				return;
			}

			if( is_array( $change ) === false || ( $change['operation'] ?? '' ) !== 'delete' || ( $change['type'] ?? '' ) !== self::TYPE )
				return;

			$id = \Nino\Elements::getElementUriFromUri( (string) ( $change['uri'] ?? '' ) );

			// The endpoint that deletes a project has taken its rows in the transaction of the deletion and tells the packages when it is through: nothing is done twice
			if( ( $appData[self::DELETING] ?? '' ) === $id )
				return;

			try {
				// The rows go where the element is known to be gone: a type file that cannot be read says nothing of it
				if( preg_match( self::ID, $id ) === 1 && self::absent( $id, self::elementIds( $appData ) ) === true ) {
					self::dropRows( $appData, $id );
					self::forgotten( $appData, $id );
				}
			}
			catch( \PDOException $e ) {
				trigger_error( 'Crm: the state and the tasks of the project '. $id. ' could not be removed', E_USER_WARNING );
			}
		}

		/**
		 *	The ids of the elements of the projects, in any language: the type file is read once, and every bucket of it - the one for all and each of the languages, whether the site serves that language or not - is a list of elements, as get()
		 *	finds an element in any of them. Where the type file is missing or cannot be read, or one of its buckets is none, nobody knows which projects there are, and no row is taken for the row of a project that is gone
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	array|null								The ids; null where the type file is missing or cannot be read, or its elements cannot
		 */
		public static function elementIds( array &$appData ): ?array {

			// A type file that is there is one that says what a project is: its model. A file with none - empty, or no type file at all - is a file that cannot be read as the one of the projects
			$typeData = self::missing( $appData ) === true ? null : \Nino\Filesystem::getFileContent( $appData, self::FILE, null );

			if( is_array( $typeData ) === false || is_array( $typeData['model'] ?? null ) === false || $typeData['model'] === [] || is_array( $typeData['*'] ?? [] ) === false )
				return null;

			$ids = [];

			// The top level holds the buckets and plain values beside them (its title, its counter): a bucket is a key that holds an array, whatever it is called
			foreach( $typeData as $bucket => $elements ) {

				if( $bucket === 'model' || is_array( $elements ) === false )
					continue;

				foreach( $elements as $id => $element ) {

					if( $id === '*' )
						continue;

					if( is_array( $element ) === false )
						return null;

					$ids[(string) $id] = true;
				}
			}

			return array_map( 'strval', array_keys( $ids ) );
		}

		/**
		 *	Whether the element of a project is certainly not there: the list of the elements, in any language, was read (elementIds()) and the id is not in it
		 *
		 *	@param		string		$id
		 *	@param		array|null	$ids					What elementIds() answered; null: nothing is known, and the answer is no
		 *
		 *	@return 	bool
		 */
		private static function absent( string $id, ?array $ids ): bool {

			return $ids !== null && in_array( $id, $ids, true ) === false;
		}

		/**
		 *	The projects that rows of the core are left of while the element of the project is gone: the deletion of an element and of its rows are two stores and may part when the file is held. They are cleared by
		 *	sweep(), and the status of the CRM says that they are there. A project is one of them only where it is certain that its element is not there: the type file is there and can be read, and so is the list of the
		 *	elements, and the element is in no bucket of it
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	array|null								The ids; null where the database cannot be read or it is not known which projects there are (see sweep())
		 */
		public static function strays( array &$appData ): ?array {

			$found = self::decide( $appData );

			return is_array( $found ) === true ? $found : null;
		}

		/**
		 *	The one reading behind every decision about the leftovers: the type file is read once and both answers come from it - which projects there are, and whether anything is left of one that is gone. Where the type
		 *	file is not there at all and no row of any project is in the database either, there is nothing to clear and nothing that could be lost, whatever the type file would have said; where rows are there, or it cannot be
		 *	told whether they are, nobody knows which of them belong to a project that is gone, and nothing is cleared
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	array|string							The ids of the projects that are gone; unreadable (it is not known which projects there are, and rows of projects are there) or busy (the database could not be read)
		 */
		private static function decide( array &$appData ): array|string {

			$ids = self::elementIds( $appData );

			// A type file that is there and cannot be read says nothing of the projects, whatever the database holds
			if( $ids === null && self::missing( $appData ) === false )
				return 'unreadable';

			try {
				$rows = Db::rows( $appData, 'SELECT project FROM project_states WHERE project <> \'\' UNION SELECT project FROM project_shares WHERE project <> \'\' UNION SELECT project FROM tasks WHERE project <> \'\'
					UNION SELECT project FROM logbook WHERE project <> \'\' AND ( client_id IS NULL OR ( source = :source AND kind IN ( '. Logbook::shareKinds(). ' ) ) )', [ 'source' => Logbook::SOURCE ] );
			}
			catch( \PDOException $e ) {
				return 'busy';
			}

			$bound = array_map( static fn( array $row ): string => (string) $row['project'], $rows );

			if( $ids === null )
				return $bound === [] ? [] : 'unreadable';

			return array_values( array_filter( $bound, static fn( string $id ): bool => self::absent( $id, $ids ) === true ) );
		}

		/**
		 *	Clear the rows of the projects that are gone: each with dropRows(), and the packages are told as at any deletion of a project
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	array|string							The ids that were cleared; unreadable where it is not known which projects there are and rows of projects are there, busy where the database could not be read or written - and nothing was cleared then
		 */
		public static function sweep( array &$appData ): array|string {

			$found = self::decide( $appData );

			if( is_string( $found ) === true )
				return $found;

			try {
				foreach( $found as $id ) {
					self::dropRows( $appData, $id );
					self::forgotten( $appData, $id );
				}
			}
			catch( \PDOException $e ) {
				return 'busy';
			}

			return $found;
		}

		/**
		 *	The line of the status that says whether rows are left of projects that are gone
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	array											state ok, leftover, unreadable (it is not known which projects there are) or busy
		 */
		public static function strayState( array &$appData ): array {

			// A status is a read: a file that is not made yet has no rows to be left, and one that is held or is not the core's is not opened here
			$file = Db::inspect( $appData )['file'];

			if( in_array( $file, [ 'absent', 'empty' ], true ) === true )
				return [ 'state' => 'ok' ];

			if( $file !== 'ours' )
				return [ 'state' => 'busy' ];

			$found = self::decide( $appData );

			return [ 'state' => $found === 'unreadable' || $found === 'busy' ? $found : ( $found === [] ? 'ok' : 'leftover' ) ];
		}

		/**
		 *	The button of that line
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	true|string
		 */
		public static function sweepFix( array &$appData ): true|string {

			$swept = self::sweep( $appData );

			return is_string( $swept ) === true ? $swept : true;
		}

		/**
		 *	The rows of the core that belong to a project: its state, its releases, its tasks and what the logbook holds of it alone (the entries of its releases among them; an entry that names the client stays with
		 *	the client). A write, to be made inside the transaction of the caller where the deletion of the element goes with it
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$id						The id of the project
		 *
		 *	@return 	void
		 */
		public static function dropRows( array &$appData, string $id ): void {

			Db::exec( $appData, 'DELETE FROM project_states WHERE project = :project', [ 'project' => $id ] );
			Db::exec( $appData, 'DELETE FROM project_shares WHERE project = :project', [ 'project' => $id ] );
			Tasks::removeProject( $appData, $id );
			Logbook::dropProject( $appData, $id );
		}

		/**
		 *	The packages that keep something of a project that is gone forget it: nothing they answer changes what was deleted
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$id
		 *
		 *	@return 	void
		 */
		public static function forgotten( array &$appData, string $id ): void {

			$told = [ 'project' => $id ];
			\Nino\Callbacks::doCallbacks( $appData, self::REMOVED, $told );
		}

		/**
		 *	A project that was made: the logbook of its client and its own has it
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$id						The id of the project
		 *
		 *	@return 	void
		 */
		private static function added( array &$appData, string $id ): void {

			if( preg_match( self::ID, $id ) !== 1 || ( $appData[self::ADDING] ?? false ) === true )
				return;

			try {
				$project = self::get( $appData, $id );

				Logbook::crm( $appData, 'project-added', [ 'project' => $id, 'ref' => $id, 'data' => [ 'label' => mb_substr( $project === null ? '' : self::named( $id, $project['title'] ), 0, Logbook::VALUE, 'UTF-8' ) ] ] );
			}
			catch( \PDOException $e ) {
				trigger_error( 'Crm: the logbook entry of the project '. $id. ' could not be written', E_USER_WARNING );
			}
		}

		/**
		 *	The list the kernel keeps of the elements a unit must not bring back (Elements::REMOVED): an element of those the unit brings is on it once it is deleted for good (in every language: the delete of the element as a whole), and off it
		 *	again when an element of that name is made. The four phases and the sentences of the greeting are kept this way
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$type					The element type, /phasen
		 *	@param		array 		$names				The names of the elements the unit brings to the type
		 *	@param		array 		$change				operation, uri and locale of the write
		 *
		 *	@return 	void
		 */
		public static function keepRemoved( array &$appData, string $type, array $names, array $change ): void {

			$key = \Nino\Elements::getElementUriFromUri( (string) ( $change['uri'] ?? '' ) );
			$operation = (string) ( $change['operation'] ?? '' );
			$name = trim( $type, '/' );

			if( in_array( $key, $names, true ) === false || in_array( $operation, [ 'delete', 'insert' ], true ) === false || ( $operation === 'delete' && ( $change['locale'] ?? '' ) !== '*' ) )
				return;

			$removed = \Nino\AppData::stored( $appData, \Nino\Elements::REMOVED, [] );
			$removed = is_array( $removed ) === true ? $removed : [];
			$list = array_values( array_map( 'strval', (array) ( $removed[$name] ?? [] ) ) );

			if( ( $operation === 'delete' ) === in_array( $key, $list, true ) )
				return;

			if( \Nino\Filesystem::lockFile( $appData, '/config.php' ) === false )
				return;

			try {
				$list = $operation === 'delete' ? array_merge( $list, [ $key ] ) : array_values( array_diff( $list, [ $key ] ) );

				if( $list === [] )
					unset( $removed[$name] );
				else
					$removed[$name] = $list;

				if( $removed === [] )
					unset( $appData[\Nino\Elements::REMOVED] );
				else
					$appData[\Nino\Elements::REMOVED] = $removed;

				\Nino\AppData::writeContentData( $appData, [ \Nino\Elements::REMOVED ] );
			}
			finally {
				\Nino\Filesystem::unlockFile( $appData, '/config.php' );
			}
		}

		/**
		 *	The projects of a client, the newest first, the done ones among them
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$clientNumber	K-0001
		 *
		 *	@return 	array											As list() has them
		 */
		public static function ofClient( array &$appData, string $clientNumber ): array {

			return self::list( $appData, $clientNumber );
		}

		/**
		 *	Make a project for a client: the opening for packages, as share() is for the release of one. The project is what the owner makes in the screen of the projects - the title, the client, a description and the day
		 *	it begins - and it is in the list of the client as any other. The entry of the logbook that says it was made is the package's: its source is the key the package names, the sentence is the one it gives, and the core
		 *	writes no entry of its own for it. A client that is switched off gets no project (the screen lists none). A project whose entry of the logbook cannot be written is taken back, and so is one that a listener of the hook
		 *	ADDED refuses
		 *
		 *	@param		array 		&$appData
		 *	@param		int				$clientId			The client the project is for
		 *	@param		array 		$project			title (one line, up to 200 characters), description (up to 2000, optional), started (Y-m-d, optional) and packages (what the forms of the packages posted for the project: key => values, optional)
		 *	@param		array 		$by						source (the key of the package, a slug, required; crm is the core's own and refused), text (the finished sentence of the logbook, one line, required), ref (a reference of the source, optional) and by (the address of the account; the account that is signed in where none is given, else system)
		 *
		 *	@return 	array|string							project (as get() has it); or why not: source, text, ref, by (not what they are asked to be), title, description, started, client (no such client), inactive (the client is switched off), type (the element type of the projects is not there), refused (a listener of ADDED took the project back) or write (the project could not be written)
		 */
		public static function add( array &$appData, int $clientId, array $project, array $by ): array|string {

			$source = $by['source'] ?? self::SHARE_SOURCE;
			$who = $by['by'] ?? Logbook::who( $appData );
			$ref = $by['ref'] ?? '';
			$cleaned = Logbook::clean( $by['text'] ?? null );
			$text = is_array( $cleaned ) === true ? $cleaned[0] : '';
			$title = is_string( $project['title'] ?? null ) === true ? trim( $project['title'] ) : null;
			$description = $project['description'] ?? '';
			$started = $project['started'] ?? '';
			$day = is_string( $started ) === true && $started !== '' ? \DateTimeImmutable::createFromFormat( '!Y-m-d', $started ) : false;

			$refused = match( true ) {
				is_string( $source ) === false || $source === self::SHARE_SOURCE || strlen( $source ) > 40 || preg_match( '/^[a-z0-9]+(?:-[a-z0-9]+)*$/D', $source ) !== 1 => 'source',
				$text === '' => 'text',
				is_string( $ref ) === false || preg_match( '//u', $ref ) !== 1 || mb_strlen( $ref, 'UTF-8' ) > Logbook::REF || Logbook::scrub( $ref ) !== $ref => 'ref',
				is_string( $who ) === false || preg_match( '//u', $who ) !== 1 || mb_strlen( $who, 'UTF-8' ) > Logbook::VALUE || Logbook::scrub( $who ) !== $who => 'by',
				is_string( $title ) === false || preg_match( '//u', $title ) !== 1 || $title === '' || mb_strlen( $title, 'UTF-8' ) > 200 || preg_match( '/[\p{Cc}\p{Cf}\x{2028}\x{2029}]/u', $title ) === 1 => 'title',
				is_string( $description ) === false || preg_match( '//u', $description ) !== 1 || mb_strlen( $description, 'UTF-8' ) > 2000 || preg_match( '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $description ) === 1 => 'description',
				is_string( $started ) === false || ( $started !== '' && ( $day === false || $day->format( 'Y-m-d' ) !== $started ) ) => 'started',
				default => '',
			};

			if( $refused !== '' )
				return $refused;

			if( self::missing( $appData ) === true )
				return 'type';

			$client = $clientId < 1 ? null : Clients::get( $appData, $clientId );

			if( $client === null )
				return 'client';

			if( $client['active'] !== true )
				return 'inactive';

			$title = (string) $title;
			$fields = [ 'title' => $title, 'client' => (string) $client['number'] ] + ( $description === '' ? [] : [ 'description' => $description ] ) + ( $started === '' ? [] : [ 'started' => $started ] );
			$problem = '';
			$element = false;

			set_error_handler( static function( int $number, string $message ) use ( &$problem ): bool {
				$problem = $message;

				return true;
			} );

			$appData[self::ADDING] = true;

			try {
				$element = \Nino\Elements::insertElement( $appData, self::TYPE. '/', $fields, '*' );
			}
			finally {
				unset( $appData[self::ADDING] );
				restore_error_handler();
			}

			$id = is_array( $element ) === true ? \Nino\Elements::getElementUriFromUri( (string) ( $element['.uri'] ?? '' ) ) : '';
			$found = $id === '' ? null : self::get( $appData, $id );

			if( $found === null ) {
				trigger_error( 'Crm: the project of '. $source. ' for client '. $client['number']. ' was not made: '. $problem, E_USER_WARNING );

				return 'write';
			}

			// The line of the logbook is the package's, and it is the proof that the project was made for what the package names in ref: a project without it is not made, so that nobody can take for the package's what it did not make
			$written = Logbook::add( $appData, [
				'source' => $source, 'kind' => 'project-added', 'text' => $text, 'client_id' => (int) $client['id'], 'project' => $id, 'ref' => $ref, 'by' => $who,
				'data'   => [ 'label' => mb_substr( self::named( $id, $found['title'] ), 0, Logbook::VALUE, 'UTF-8' ) ],
			] );

			if( is_string( $written ) === true ) {
				\Nino\Elements::deleteElement( $appData, self::TYPE. '/'. $id, '*' );

				return 'write';
			}

			$refusal = self::tell( $appData, $id, (string) $client['number'], is_array( $project['packages'] ?? null ) === true ? $project['packages'] : [] );

			if( $refusal !== null ) {
				\Nino\Elements::deleteElement( $appData, self::TYPE. '/'. $id, '*' );
				Db::exec( $appData, 'DELETE FROM logbook WHERE project = :project', [ 'project' => $id ] );

				return 'refused';
			}

			return [ 'project' => $found ];
		}

		/**
		 *	Tell the packages that a project was made: the hook ADDED is asked with the project, its client's number and what the forms of the packages posted, each listener on its own. A refusal one answers
		 *	- [ status 400-499, message, code, params, field ] - takes the project back; the first is kept for the answer of the endpoint
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$id
		 *	@param		string		$client				The number of the client
		 *	@param		array 		$posted				key of the package => values
		 *
		 *	@return 	array|null								The refusal; null where none refused
		 */
		public static function tell( array &$appData, string $id, string $client, array $posted ): ?array {

			foreach( Api::asked( $appData, self::ADDED, [ 'project' => $id, 'client' => $client, 'posted' => $posted, 'refusal' => null ] ) as $answer ) {

				$refusal = $answer['refusal'] ?? null;

				if( is_array( $refusal ) === true && is_int( $refusal['status'] ?? null ) === true && $refusal['status'] >= 400 && $refusal['status'] <= 499 && is_string( $refusal['message'] ?? null ) === true
					&& is_string( $refusal['code'] ?? null ) === true && is_array( $refusal['params'] ?? null ) === true && is_string( $refusal['field'] ?? null ) === true )
					return $refusal;
			}

			return null;
		}

		/**
		 *	Release a project to a client: the client sees it in its list of released projects, and the logbook of the project and of the client says so. The opening for features: a feature calls it with its own key as the source.
		 *	Releasing a project to the client it is released to already changes nothing and says so (created is false); a client that is switched off may be released to, and the release holds when it is switched on again.
		 *	What is read and what is written are one step, so that two releases at the same moment make one row and one entry
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$project			The id of a project
		 *	@param		int				$clientId			The client the project is released to
		 *	@param		array 		$by						source (the key of the feature, a slug, required; crm is the CRM's own and refused), by (the address of the account that does it; the account that is signed in where none is given, else system) and ref (a reference of the source, optional)
		 *
		 *	@return 	array|string							created (whether this call made the release) and share (as sharesOf() has one); or why not: project (no such project), client (no such client), owner (the project is the client's own), source (none, not a slug, or crm), by, ref (not what they are asked to be)
		 */
		public static function share( array &$appData, string $project, int $clientId, array $by ): array|string {

			// The source of the CRM is its own: a feature names itself, and crm is refused as it is for Logbook::add()
			if( ( $by['source'] ?? self::SHARE_SOURCE ) === self::SHARE_SOURCE )
				return 'source';

			return self::put( $appData, $project, $clientId, $by );
		}

		/**
		 *	Release a project to a client as the CRM does it - the owner in the card of the project -: the source is crm, the account is the one that is signed in. The rules are those of share(), whose own door
		 *	is for features, as Logbook::crm() is beside Logbook::add()
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$project			The id of a project
		 *	@param		int				$clientId			The client the project is released to
		 *
		 *	@return 	array|string							As share() has it
		 */
		public static function shareCrm( array &$appData, string $project, int $clientId ): array|string {

			return self::put( $appData, $project, $clientId, [] );
		}

		/**
		 *	The release itself, for share() and shareCrm()
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$project
		 *	@param		int				$clientId
		 *	@param		array 		$by						source (crm where none is given), by and ref
		 *
		 *	@return 	array|string
		 */
		private static function put( array &$appData, string $project, int $clientId, array $by ): array|string {

			$source = $by['source'] ?? self::SHARE_SOURCE;
			$who = $by['by'] ?? Logbook::who( $appData );
			$ref = $by['ref'] ?? '';

			if( is_string( $source ) === false || strlen( $source ) > 40 || preg_match( '/^[a-z0-9]+(?:-[a-z0-9]+)*$/D', $source ) !== 1 )
				return 'source';

			if( is_string( $who ) === false || preg_match( '//u', $who ) !== 1 || mb_strlen( $who, 'UTF-8' ) > Logbook::VALUE || Logbook::scrub( $who ) !== $who )
				return 'by';

			if( is_string( $ref ) === false || preg_match( '//u', $ref ) !== 1 || mb_strlen( $ref, 'UTF-8' ) > Logbook::REF || Logbook::scrub( $ref ) !== $ref )
				return 'ref';

			$who = $who === '' ? 'system' : $who;

			// The checks are made inside the transaction, with the write lock held: a client that is deleted at the same moment is gone before this or the release is none
			$pdo = Db::pdo( $appData );
			$mine = false;

			try {
				$pdo->exec( 'BEGIN IMMEDIATE' );
				$mine = true;
			}
			catch( \Throwable $e ) {
				// the caller has a transaction, which holds the write lock already
			}

			try {
				$found = preg_match( self::ID, $project ) === 1 ? self::get( $appData, $project ) : null;
				$client = $clientId < 1 ? null : Db::row( $appData, 'SELECT id, number, company, name, email, active FROM clients WHERE id = :id', [ 'id' => $clientId ] );

				$refused = match( true ) {
					$found === null                         => 'project',
					$client === null                        => 'client',
					$found['client'] === $client['number']  => 'owner',
					default                                 => '',
				};

				if( $refused !== '' ) {

					if( $mine === true )
						$pdo->exec( 'ROLLBACK' );

					return $refused;
				}

				$made = Db::exec( $appData, 'INSERT INTO project_shares ( project, client_id, since, "by", source, ref ) VALUES ( :project, :client, :since, :by, :source, :ref ) ON CONFLICT ( project, client_id ) DO NOTHING', [
					'project' => $project, 'client' => $clientId, 'since' => gmdate( self::TIME ), 'by' => $who, 'source' => $source, 'ref' => $ref,
				] ) === 1;

				if( $made === true ) {
					Logbook::crm( $appData, 'project-shared', self::shareEntry( $found, $client, $who, $ref ) );
					self::forget( $appData );
				}

				if( $mine === true )
					$pdo->exec( 'COMMIT' );
			}
			catch( \Throwable $e ) {

				if( $mine === true )
					$pdo->exec( 'ROLLBACK' );

				throw $e;
			}

			return [ 'created' => $made, 'share' => self::sharesOf( $appData, $project, $clientId )[0] ?? [] ];
		}

		/**
		 *	End the release of a project to a client; the logbook of the project and of the client says so. Ending a release that is none changes nothing and says so (removed is false)
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$project			The id of a project
		 *	@param		int				$clientId			The client the project was released to
		 *	@param		array 		$by						by (the address of the account that does it; the account that is signed in where none is given, else system) and owner (true where the release ends because the client became the owner of the project)
		 *
		 *	@return 	array|string							removed (whether this call ended it) and share (the release as sharesOf() had it; null where there was none); or why not: project, client (not the id of one), by
		 */
		public static function unshare( array &$appData, string $project, int $clientId, array $by = [] ): array|string {

			$who = $by['by'] ?? Logbook::who( $appData );

			if( preg_match( self::ID, $project ) !== 1 )
				return 'project';

			if( $clientId < 1 )
				return 'client';

			if( is_string( $who ) === false || preg_match( '//u', $who ) !== 1 || mb_strlen( $who, 'UTF-8' ) > Logbook::VALUE || Logbook::scrub( $who ) !== $who )
				return 'by';

			$who = $who === '' ? 'system' : $who;
			$pdo = Db::pdo( $appData );
			$mine = false;

			try {
				$pdo->exec( 'BEGIN IMMEDIATE' );
				$mine = true;
			}
			catch( \Throwable $e ) {
				// the caller has a transaction, which holds the write lock already
			}

			try {
				$was = self::sharesOf( $appData, $project, $clientId )[0] ?? null;
				$removed = $was !== null && Db::exec( $appData, 'DELETE FROM project_shares WHERE project = :project AND client_id = :client', [ 'project' => $project, 'client' => $clientId ] ) === 1;

				if( $removed === true ) {
					Logbook::crm( $appData, 'project-unshared', self::shareEntry( self::get( $appData, $project ) ?? [ 'id' => $project, 'title' => '' ], $was + [ 'id' => $clientId ], $who, '', ( $by['owner'] ?? false ) === true ? [ 'owner' => true ] : [] ) );
					self::forget( $appData );
				}

				if( $mine === true )
					$pdo->exec( 'COMMIT' );
			}
			catch( \Throwable $e ) {

				if( $mine === true )
					$pdo->exec( 'ROLLBACK' );

				throw $e;
			}

			return [ 'removed' => $removed, 'share' => $was ];
		}

		/**
		 *	Make a release the owner's own: the owner keeps it. A release a feature made carries that feature's source and is the feature's to end when its own rule says so (the match of the Workshops takes back the releases it made); the owner who wants
		 *	the client to keep the project whatever the feature does takes the row over here: its source becomes crm, its reference is cleared and the account that is signed in is the one who made it. The time stays, and so does everything else, and no
		 *	entry of the logbook is written (nothing is released or ended; the release was written when it was made). A release that is the owner's already changes nothing and says so (kept is false). This is the way the owner's wish wins where both
		 *	made a release for one client: share() and shareCrm() leave the first row as it is, as they must, and keep() is what the screen puts on a row that is not the CRM's own
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$project			The id of a project
		 *	@param		int				$clientId			The client the project is released to
		 *
		 *	@return 	array|string							kept (whether this call took the row over) and share (as sharesOf() has it now); or why not: project (no such project), client (not the id of one), none (the project is not released to that client)
		 */
		public static function keep( array &$appData, string $project, int $clientId ): array|string {

			if( preg_match( self::ID, $project ) !== 1 || self::get( $appData, $project ) === null )
				return 'project';

			if( $clientId < 1 )
				return 'client';

			$changed = Db::exec( $appData, 'UPDATE project_shares SET source = :crm, ref = \'\', "by" = :who WHERE project = :project AND client_id = :client AND source <> :crm', [ 'crm' => self::SHARE_SOURCE, 'who' => Logbook::who( $appData ), 'project' => $project, 'client' => $clientId ] ) === 1;
			$share = self::sharesOf( $appData, $project, $clientId )[0] ?? null;

			if( $share === null )
				return 'none';

			if( $changed === true )
				self::forget( $appData );

			return [ 'kept' => $changed, 'share' => $share ];
		}

		/**
		 *	The clients a project is released to, the earliest release first
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$project			The id of a project
		 *	@param		int|null	$clientId			Only the release to this client; null for all
		 *
		 *	@return 	array											client_id, number, title (how the client is called), active (whether it is switched on), since, by, source and ref of each; none for a project nobody has it released to
		 */
		public static function sharesOf( array &$appData, string $project, ?int $clientId = null ): array {

			if( preg_match( self::ID, $project ) !== 1 )
				return [];

			$rows = Db::rows( $appData, 'SELECT s.project, s.client_id, s.since, s."by", s.source, s.ref, c.number, c.company, c.name, c.email, c.active FROM project_shares s JOIN clients c ON c.id = s.client_id WHERE s.project = :project'. ( $clientId === null ? '' : ' AND s.client_id = :client' ). ' ORDER BY s.since, s.client_id', [ 'project' => $project ] + ( $clientId === null ? [] : [ 'client' => $clientId ] ) );

			return array_map( static fn( array $row ): array => [
				'client_id' => (int) $row['client_id'],
				'number'    => (string) $row['number'],
				'title'     => Clients::title( $row ),
				'active'    => (int) $row['active'] === 1,
				'since'     => (string) $row['since'],
				'by'        => (string) $row['by'],
				'source'    => (string) $row['source'],
				'ref'       => (string) $row['ref'],
			], $rows );
		}

		/**
		 *	The projects released to a client - not its own - the earliest release first
		 *
		 *	@param		array 		&$appData
		 *	@param		int				$clientId
		 *
		 *	@return 	array											id (of the project), title, owner (the number of the client it belongs to), owner_id, owner_title, done, since, by, source and ref of each; a release whose project is gone is left out
		 */
		public static function sharedWith( array &$appData, int $clientId ): array {

			$rows = $clientId < 1 ? [] : Db::rows( $appData, 'SELECT project, since, "by", source, ref FROM project_shares WHERE client_id = :client ORDER BY since, project', [ 'client' => $clientId ] );

			if( $rows === [] )
				return [];

			$projects = array_column( self::read( $appData ), null, 'id' );
			$owners = [];

			foreach( Db::rows( $appData, 'SELECT id, number, company, name, email FROM clients' ) as $client )
				$owners[(string) $client['number']] = [ 'id' => (int) $client['id'], 'title' => Clients::title( $client ) ];

			$shared = [];

			foreach( $rows as $row ) {

				$project = $projects[(string) $row['project']] ?? null;

				if( $project === null )
					continue;

				$shared[] = [
					'id'          => $project['id'],
					'title'       => $project['title'],
					'owner'       => $project['client'],
					'owner_id'    => $owners[$project['client']]['id'] ?? 0,
					'owner_title' => $owners[$project['client']]['title'] ?? '',
					'done'        => $project['done'],
					'since'       => (string) $row['since'],
					'by'          => (string) $row['by'],
					'source'      => (string) $row['source'],
					'ref'         => (string) $row['ref'],
				];
			}

			return $shared;
		}

		/**
		 *	One project that is released to a client, as the portal reads it for the members of that client - the guests of the project. The project as get() has it, with guest true and nothing of
		 *	the owner's work: no phase and no favorite mark. It is what the portal asks for a project it does not hold as its own, and it is no part of list() and ofClient(): none for a project that
		 *	is not released to the client, for one that is the client's own, for an id that is none and for a project that is gone
		 *
		 *	@param		array 		&$appData
		 *	@param		int				$clientId			The client the project is released to
		 *	@param		string		$id						The id of a project
		 *
		 *	@return 	array|null								As get() has it, with guest true; null for none
		 */
		public static function shared( array &$appData, int $clientId, string $id ): ?array {

			if( $clientId < 1 || preg_match( self::ID, $id ) !== 1 )
				return null;

			foreach( self::sharedList( $appData, $clientId ) as $project )
				if( $project['id'] === $id )
					return $project;

			return null;
		}

		/**
		 *	The projects released to a client as the portal lists them, the newest first like list(): each as shared() has it, with the download of the project and nothing of the owner's work
		 *
		 *	@param		array 		&$appData
		 *	@param		int				$clientId			The client the projects are released to
		 *
		 *	@return 	array											As list() has them, each with guest true; none for a client nothing is released to
		 */
		public static function sharedList( array &$appData, int $clientId ): array {

			if( $clientId < 1 )
				return [];

			return self::remember( $appData, 'shared|'. $clientId, static function() use ( &$appData, $clientId ): array {

				$ids = array_column( Db::rows( $appData, 'SELECT project FROM project_shares WHERE client_id = :client', [ 'client' => $clientId ] ), 'project' );

				if( $ids === [] )
					return [];

				$held = Db::row( $appData, 'SELECT number FROM clients WHERE id = :id', [ 'id' => $clientId ] )['number'] ?? '';
				$projects = [];

				foreach( self::read( $appData ) as $project )
					if( in_array( $project['id'], $ids, true ) === true && $project['client'] !== $held )
						$projects[] = self::guest( $project );

				return $projects;
			} ) ?? [];
		}

		/**
		 *	A project as the guest of it reads it: the owner's phase and favorite mark are left out
		 *
		 *	@param		array 		$project			As get() has it
		 *
		 *	@return 	array
		 */
		private static function guest( array $project ): array {

			return [ 'guest' => true, 'phase' => '', 'favorite' => false ] + $project;
		}

		/**
		 *	The logbook entry of a release that is made or ended: about the client it is released to and the project, with the project and the client named in the words of the sentence
		 *
		 *	@param		array 		$project			id and title
		 *	@param		array 		$client				id, number, company, name and email (or title)
		 *	@param		string		$who
		 *	@param		string		$ref
		 *	@param		array 		$more					Values besides the two names: owner, for a release that ends because the client became the owner of the project
		 *
		 *	@return 	array											As Logbook::crm() takes it
		 */
		private static function shareEntry( array $project, array $client, string $who, string $ref, array $more = [] ): array {

			$title = (string) ( $client['title'] ?? Clients::title( $client ) );
			$name = trim( (string) $client['number']. ( $title === '' ? '' : ' '. $title ) );

			return [
				'client_id' => (int) $client['id'], 'project' => (string) $project['id'], 'ref' => $ref, 'by' => $who,
				'data'      => [ 'label' => mb_substr( self::named( (string) $project['id'], (string) $project['title'] ), 0, Logbook::VALUE, 'UTF-8' ), 'client' => mb_substr( $name, 0, Logbook::VALUE, 'UTF-8' ) ] + $more,
			];
		}

		/**
		 *	A project that was changed: where it belongs to a client that it was released to, that release ends - a client does not hold a release of its own project - and the others stay
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$id						The id of the project
		 *
		 *	@return 	void
		 */
		private static function moved( array &$appData, string $id ): void {

			if( preg_match( self::ID, $id ) !== 1 )
				return;

			try {
				$project = self::get( $appData, $id );
				$own = $project === null || $project['client'] === '' ? null : Db::row( $appData, 'SELECT s.client_id FROM project_shares s JOIN clients c ON c.id = s.client_id WHERE s.project = :project AND c.number = :number', [ 'project' => $id, 'number' => $project['client'] ] );

				if( $own !== null )
					self::unshare( $appData, $id, (int) $own['client_id'], [ 'owner' => true ] );
			}
			catch( \PDOException $e ) {
				trigger_error( 'Crm: the release of the project '. $id. ' to its client could not be ended', E_USER_WARNING );
			}
		}

		/**
		 *	What keeps a project from being deleted or from changing its client: a package says so through the hook HOLDS ('/crm/project/holds'; the argument has project and holds,
		 *	a list the listener adds to: key - the slug of the package -, count - a whole number from 1, how many things of the
		 *	package name the project -, delete - whether the project is not deleted while it does -, client - whether the client of the project does not change while it does -, why - the sentence
		 *	of the refusal, as one line of text - and names, who names the project in the line of the log: 'Tasks' makes 'Tasks name the project 00001.', and the line is what the panel of the workbench answers with). Every listener is asked on its own
		 *	(ask()) and the answers are put together: a listener can add a hold and cannot take one away. Holds of one key are one hold - the greatest count, a refusal that any of them makes, the
		 *	first sentence and the first names there are. An entry of another form is dropped, and what a listener answers wrongly is nothing of the others'
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$id						The id of a project
		 *
		 *	@return 	array											key, count, delete, client, why, names, delete_code and client_code of each, by key; none where no package listens
		 */
		public static function holds( array &$appData, string $id ): array {

			$found = [];

			foreach( self::ask( $appData, self::HOLDS, [ 'project' => $id, 'holds' => [] ], 'holds' ) as $hold ) {

				$key = is_array( $hold ) === true && is_string( $hold['key'] ?? null ) === true ? $hold['key'] : '';
				$count = is_array( $hold ) === true ? ( $hold['count'] ?? null ) : null;

				if( preg_match( '/^[a-z0-9]+(?:-[a-z0-9]+)*$/D', $key ) !== 1 || is_int( $count ) === false || $count < 1 )
					continue;

				$one = [ 'key' => $key, 'count' => $count, 'delete' => ( $hold['delete'] ?? false ) === true, 'client' => ( $hold['client'] ?? false ) === true, 'why' => self::line( $hold['why'] ?? '', self::WHY ), 'names' => self::line( $hold['names'] ?? '', self::WHY ),
					'delete_code' => self::code( $hold['delete_code'] ?? '' ), 'client_code' => self::code( $hold['client_code'] ?? '' ) ];
				$one['names'] = $one['names'] === '' ? ucfirst( str_replace( '-', ' ', $key ) ) : $one['names'];

				if( isset( $found[$key] ) === true )
					$one = [ 'key' => $key, 'count' => max( $one['count'], $found[$key]['count'] ), 'delete' => $one['delete'] || $found[$key]['delete'], 'client' => $one['client'] || $found[$key]['client'], 'why' => $found[$key]['why'] !== '' ? $found[$key]['why'] : $one['why'], 'names' => $found[$key]['names'],
						'delete_code' => $found[$key]['delete_code'] !== '' ? $found[$key]['delete_code'] : $one['delete_code'], 'client_code' => $found[$key]['client_code'] !== '' ? $found[$key]['client_code'] : $one['client_code'] ];

				$found[$key] = $one;
			}

			ksort( $found );

			return array_values( $found );
		}

		/**
		 *	What the listeners of a hook answer, each asked on its own: every one is handed an argument of its own, as it is built here, and the contract of the kernel's hooks holds for it and for it alone - what it
		 *	returns, unless that is null, takes the place of its argument, and the list in the argument it ends with is its answer (a listener may fill the list by reference or return the argument, and a
		 *	return is the answer where it does both). What one listener makes of its argument is nothing of the others': the kernel's hooks hand one argument through all listeners, and this is not one of them,
		 *	because a refusal that a listener can take away is none. An answer that is no array, or whose list is none, is not counted and is said in a warning, so that the author of the package sees it
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$hook
		 *	@param		array 		$args					The argument of the hook, as a listener finds it
		 *	@param		string		$list					The key of the list the listeners add to
		 *
		 *	@return 	array											The entries of every listener, one after the other, in the order of the listeners (the order they were registered in: the priority, then the registration)
		 */
		private static function ask( array &$appData, string $hook, array $args, string $list ): array {

			$entries = [];

			foreach( \Nino\Callbacks::registered( $appData, $hook ) as $callback ) {

				// The contract of the kernel's hooks, for this one listener: what it returns, unless that is null, takes the place of its argument; else the argument as it left it is the answer
				$own = $args;
				$returned = call_user_func_array( $callback, [ &$appData, &$own ] );
				$answer = self::listed( $returned ?? $own, $list );

				if( $answer === null ) {
					trigger_error( 'Crm: a listener of '. $hook. ' answered in a form that is none: its answer is not counted', E_USER_WARNING );
					continue;
				}

				foreach( $answer as $at => $entry )
					$entries[] = $list === 'figures' ? [ $at => $entry ] : $entry;
			}

			return $entries;
		}

		/**
		 *	The sentence a veto of the core gives: a word of the surface (veto.<name>) in the language of the request, with the values put in
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$name
		 *	@param		string		...$values
		 *
		 *	@return 	string
		 */
		private static function veto( array &$appData, string $name, string ...$values ): string {

			return vsprintf( Surface\Texts::word( $appData, 'veto.'. $name ), $values );
		}

		/**
		 *	The first hold that refuses a change of a project
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$id						The id of a project
		 *	@param		string		$change				delete or client
		 *
		 *	@return 	array|null								As holds() has an entry; null where nothing refuses the change
		 */
		public static function held( array &$appData, string $id, string $change ): ?array {

			foreach( self::holds( $appData, $id ) as $hold )
				if( $hold[$change] === true )
					return $hold;

			return null;
		}

		/**
		 *	The name a project is shown by: P- and its number, four digits wide (P-0001; five from 10000 on), the way a client is K-0001. Only the display - the id stays what
		 *	the address, the folder and the log hold. An id that is no number (a project made by hand with a name of its own) is shown as it is
		 *
		 *	@param		string		$id						The id of a project, the last part of its uri
		 *
		 *	@return 	string										P-0001; the id as it is where it is no number from 1 to 999999999
		 */
		public static function label( string $id ): string {

			return preg_match( '/^[0-9]{1,9}$/D', $id ) === 1 && (int) $id > 0 ? 'P-'. sprintf( '%04d', (int) $id ) : $id;
		}

		/**
		 *	A project in a line of text: the name it is shown by and its title, P-0001 Album 2027; the name alone where the title is empty, nothing for no project
		 *
		 *	@param		string		$id						The id of a project, '' for none
		 *	@param		string		$title
		 *
		 *	@return 	string
		 */
		public static function named( string $id, string $title ): string {

			return $id === '' ? '' : self::label( $id ). ( $title === '' ? '' : ' '. $title );
		}

		/**
		 *	The title of each project by its id, for the lists that show the project of something that names it
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	array											id => title
		 */
		public static function titles( array &$appData ): array {

			return array_column( self::read( $appData ), 'title', 'id' );
		}

		/**
		 *	The veto of the kernel for a delete in the type /projekte ('/nino/elements/delete/crm-projects'): a project that a package holds (holds()), or that any record of the evidence names (a consent, a
		 *	revocation, whatever the record is), is not deleted - in the
		 *	Elements panel of the workbench as in the surface, and anywhere else. The kernel hands over the type file as it will be written; what is gone from it is
		 *	what is being deleted
		 *
		 *	@param		array 		&$appData
		 *	@param		mixed			&$typeData		The type file after the delete
		 *
		 *	@return 	mixed											false vetoes; the type file otherwise
		 */
		public static function callbackDelete( array &$appData, mixed &$typeData ): mixed {

			if( is_array( $typeData ) === false )
				return $typeData;

			$was = self::stored( $appData );

			// A project goes with its rows, which only a write can take: where the file is held the project stays, and says that the CRM is busy - not a deletion that leaves rows behind. The endpoint holds its own
			// transaction and needs no probe
			if( array_diff( array_keys( $was ), array_keys( self::bucketOf( $typeData ) ) ) !== [] && ( $appData[self::DELETING] ?? '' ) === '' ) {

				try {
					$pdo = Db::pdo( $appData );
					$pdo->exec( 'BEGIN IMMEDIATE' );
					$pdo->exec( 'ROLLBACK' );
				}
				catch( \PDOException $e ) {
					trigger_error( self::veto( $appData, 'busy' ) );
					return false;
				}
			}

			try {
				foreach( array_diff( array_keys( $was ), array_keys( self::bucketOf( $typeData ) ) ) as $id ) {

					$hold = self::held( $appData, (string) $id, 'delete' );

					if( $hold !== null ) {
						trigger_error( self::veto( $appData, 'hold', (string) $hold['names'], $id ) );
						return false;
					}

					// A record of what a customer agreed to is evidence that names the project: the project stays as long as any record of it does
					if( Records::namesProject( $appData, (string) $id ) === true ) {
						trigger_error( self::veto( $appData, 'records', $id ) );
						return false;
					}
				}
			}
			catch( \PDOException $e ) {
				return false;
			}

			return $typeData;
		}

		/**
		 *	The veto of the kernel for an insert and an update in the type ('/nino/elements/crm-projects/insert' and '.../update'): the client of a project is the number of
		 *	a client that is there, and it does not change while a package holds the project (holds())
		 *
		 *	@param		array 		&$appData
		 *	@param		mixed			&$typeData		The type file after the write
		 *
		 *	@return 	mixed											false vetoes; the type file otherwise
		 */
		public static function callbackWrite( array &$appData, mixed &$typeData ): mixed {

			if( is_array( $typeData ) === false )
				return $typeData;

			$was = self::stored( $appData );

			try {
				foreach( self::bucketOf( $typeData ) as $id => $fields ) {

					$client = is_string( $fields['client'] ?? null ) === true ? $fields['client'] : '';
					$before = $was[$id] ?? null;

					// What is not changed is not asked: a project of the old days keeps its client
					if( $before !== null && ( $before['client'] ?? null ) === $client )
						continue;

					if( $client === '' || Clients::byNumber( $appData, $client ) === null ) {
						trigger_error( self::veto( $appData, 'client', $client, $id ) );
						return false;
					}

					$hold = $before === null ? null : self::held( $appData, (string) $id, 'client' );

					if( $hold !== null ) {
						trigger_error( self::veto( $appData, 'hold-client', (string) $hold['names'], $id ) );
						return false;
					}
				}
			}
			catch( \PDOException $e ) {
				return false;
			}

			return $typeData;
		}

		/**
		 *	The veto of the kernel for a delete in the type /phasen ('/nino/elements/delete/crm-phases'): a phase that a project is in is not deleted - in the Elements panel of the workbench as in the
		 *	surface. The kernel hands over the type file as it will be written; what is gone from it is what is being deleted
		 *
		 *	@param		array 		&$appData
		 *	@param		mixed			&$typeData		The type file after the delete
		 *
		 *	@return 	mixed											false vetoes; the type file otherwise
		 */
		public static function callbackPhaseDelete( array &$appData, mixed &$typeData ): mixed {

			if( is_array( $typeData ) === false )
				return $typeData;

			try {
				foreach( array_diff( array_keys( self::phaseBucket( self::phaseFile( $appData ) ) ), array_keys( self::phaseBucket( $typeData ) ) ) as $key ) {

					if( self::inPhase( $appData, (string) $key ) > 0 ) {
						trigger_error( self::veto( $appData, 'phase-used', $key ) );
						return false;
					}
				}
			}
			catch( \PDOException $e ) {
				return false;
			}

			return $typeData;
		}

		/**
		 *	The veto of the kernel for an insert and an update in the type /phasen ('/nino/elements/crm-phases/insert' and '.../update'): what the endpoint of the surface asks of a phase the Elements panel
		 *	is asked too - the key of a new phase, the title of each language, the order. Only a phase that this write changes is looked at
		 *
		 *	@param		array 		&$appData
		 *	@param		mixed			&$typeData		The type file after the write
		 *
		 *	@return 	mixed											false vetoes; the type file otherwise
		 */
		public static function callbackPhaseWrite( array &$appData, mixed &$typeData ): mixed {

			if( is_array( $typeData ) === false )
				return $typeData;

			$was = self::phaseFile( $appData );
			$before = self::phaseBucket( $was );

			foreach( self::phaseBucket( $typeData ) as $key => $fields ) {

				$key = (string) $key;

				if( isset( $before[$key] ) === false && preg_match( self::PHASE_KEY, $key ) !== 1 ) {
					trigger_error( self::veto( $appData, 'phase-key', $key ) );
					return false;
				}

				if( array_key_exists( 'order', $fields ) === true && ( $before[$key]['order'] ?? null ) !== $fields['order'] && self::phaseOrderOk( $fields['order'] ) === false ) {
					trigger_error( self::veto( $appData, 'phase-order', $key, (string) self::PHASE_ORDER ) );
					return false;
				}
			}

			foreach( $typeData as $locale => $bucket ) {

				if( $locale === '*' || is_array( $bucket ) === false )
					continue;

				foreach( $bucket as $key => $fields ) {

					$title = is_array( $fields ) === true ? ( $fields['title'] ?? null ) : null;

					if( is_string( $title ) === true && $title !== ( $was[$locale][$key]['title'] ?? null ) && self::phaseTitleOk( $title ) === false ) {
						trigger_error( self::veto( $appData, 'phase-title', $key, (string) self::PHASE_TITLE ) );
						return false;
					}
				}
			}

			return $typeData;
		}

		/**
		 *	@param		array 		&$appData
		 *
		 *	@return 	array											The type file of the phases as it is stored; none where it is not there
		 */
		private static function phaseFile( array &$appData ): array {

			$file = \Nino\Filesystem::getFileContent( $appData, self::PHASE_FILE, [] );

			return is_array( $file ) === true ? $file : [];
		}

		/**
		 *	@param		array 		$typeData
		 *
		 *	@return 	array											key => fields of the bucket for all
		 */
		private static function phaseBucket( array $typeData ): array {

			return self::bucketOf( $typeData );
		}

		/**
		 *	The global fields of every project as the type file holds them now (what the write in progress will replace)
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	array											id => fields
		 */
		private static function stored( array &$appData ): array {

			$file = \Nino\Filesystem::getFileContent( $appData, self::FILE, [] );

			return is_array( $file ) === true ? self::bucketOf( $file ) : [];
		}

		/**
		 *	@param		array 		$typeData
		 *
		 *	@return 	array											id => fields of the bucket for all (a project has nothing per language)
		 */
		private static function bucketOf( array $typeData ): array {

			$bucket = is_array( $typeData['*'] ?? null ) === true ? $typeData['*'] : [];
			unset( $bucket['*'] );

			return array_filter( $bucket, 'is_array' );
		}

		/**
		 *	Every project as get() has it, the newest first
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	array
		 */
		private static function read( array &$appData ): array {

			if( self::missing( $appData ) === true )
				return [];

			$hits = \Nino\Elements::queryElements( $appData, self::TYPE, [], '*', [] );
			$projects = [];

			foreach( is_array( $hits ) === true ? $hits : [] as $hit ) {

				if( is_array( $hit ) === true && isset( $hit['.uri'] ) === true )
					$projects[] = self::shape( $hit );
			}

			// The numbers count up with the making: a longer number is a later one, and of two of a length the greater is
			usort( $projects, static fn( array $a, array $b ): int => [ strlen( $b['id'] ), $b['id'] ] <=> [ strlen( $a['id'] ), $a['id'] ] );

			return $projects;
		}

		/**
		 *	The numbers and marks the packages add to the rows of the projects: a package says them through the hook FIGURES ('/crm/project/figures'; the argument has projects, the
		 *	ids of the rows, and figures, id => name => value, which the listener adds to). One question for all the projects of a list, however many packages listen; every listener is asked on its own (ask()) and a name that a listener has set is not set again by another - a package does not overwrite the numbers of another. A name is lower-case
		 *	words with underscores and is none of the fields of a row; a value is a whole number, a truth value or a line of text. Anything else is dropped
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		$ids					The ids of the projects of a list
		 *
		 *	@return 	array											id => name => value; none where no package listens or there is no project
		 */
		private static function figures( array &$appData, array $ids ): array {

			if( $ids === [] )
				return [];

			$found = [];

			foreach( self::ask( $appData, self::FIGURES, [ 'projects' => $ids, 'figures' => [] ], 'figures' ) as $answer )
				foreach( $answer as $id => $figures )
					foreach( is_array( $figures ) === true ? $figures : [] as $name => $value )
						if( in_array( (string) $id, $ids, true ) === true && is_string( $name ) === true && preg_match( self::FIGURE, $name ) === 1 && in_array( $name, self::ROW, true ) === false && isset( $found[(string) $id][$name] ) === false && ( is_int( $value ) === true || is_bool( $value ) === true || is_string( $value ) === true ) )
							$found[(string) $id][$name] = is_string( $value ) === true ? self::line( $value, self::WHY ) : $value;

			return $found;
		}

		/**
		 *	The code a hold answers its refusal with: lower case letters, digits and underscores, beginning with a letter - the code of the core's own words stays the core's, and what is none is left out
		 *
		 *	@param		mixed			$code
		 *
		 *	@return 	string										'' for none
		 */
		private static function code( mixed $code ): string {

			return is_string( $code ) === true && preg_match( '/^[a-z][a-z0-9_]{0,59}$/D', $code ) === 1 ? $code : '';
		}

		/**
		 *	A word of a package as one line of plain text, cut to a length
		 *
		 *	@param		mixed			$text
		 *	@param		int				$max
		 *
		 *	@return 	string
		 */
		private static function line( mixed $text, int $max ): string {

			return mb_substr( trim( (string) preg_replace( '/\s{2,}/u', ' ', (string) preg_replace( '/[\x{0}-\x{1F}\x{7F}-\x{9F}\x{2028}\x{2029}\x{202A}-\x{202E}\x{2066}-\x{2069}]+/u', ' ', is_string( $text ) === true ? $text : '' ) ) ), 0, $max, 'UTF-8' );
		}

		/**
		 *	The phase and the mark of the projects that have a row: one query
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	array											id => phase, favorite (bool)
		 */
		private static function states( array &$appData ): array {

			return (array) Db::kept( $appData, 'projects', 'states', static function() use ( &$appData ): array {

				$states = [];

				foreach( Db::rows( $appData, 'SELECT project, phase, favorite FROM project_states' ) as $row )
					$states[(string) $row['project']] = [ 'phase' => self::phaseOf( $appData, (string) $row['phase'] ), 'favorite' => (int) $row['favorite'] === 1 ];

				return $states;
			} );
		}

		/**
		 *	@param		array 		$element			As the kernel answers it, with its '.uri'
		 *
		 *	@return 	array
		 */
		private static function shape( array $element ): array {

			$text = static fn( mixed $value ): string => is_string( $value ) === true ? trim( $value ) : '';

			return [
				'id'          => \Nino\Elements::getElementUriFromUri( (string) $element['.uri'] ),
				'title'       => $text( $element['title'] ?? '' ),
				'client'      => $text( $element['client'] ?? '' ),
				'description' => $text( $element['description'] ?? '' ),
				'started'     => $text( $element['started'] ?? '' ),
				'done'        => ( $element['done'] ?? false ) === true,
			];
		}
	}
}
