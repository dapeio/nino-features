<?php
declare(strict_types=1);

namespace Nino\Modules\CrmDocuments {

	use Nino\Modules\Crm\Db;
	use Nino\Modules\Crm\Erasure;
	use Nino\Modules\Crm\Settings;
	use Nino\Modules\Crm\Wipe;

	/**
	 *	Hooks		What the documents answer where the core asks and what they hold of a client: the deletion of a client (a client with a document that is fixed is not deleted, and its drafts go with it), the test phase,
	 *							the projects (documents that name a project keep it from being deleted and from changing its client), the first screen of the CRM and the settings.
	 *
	 *							A document that is finalised is the books and is never deleted by a step of a package: the deletion of a client refuses while the client has one - the plan names the refusal in front of those of the core -,
	 *							the drafts are the only rows that go, and nothing is deleted without a step the owner asked for. The refusal is asked again inside the transaction of the deletion, with the write lock held, so that a document
	 *							finalised after the plan was made stops the deletion all the same. The test phase (Wipe) is the one thing that deletes the books, whole, and the owner types a word for it.
	 *
	 *	@package						Dape/Nino
	 */
	class Hooks {

		/**	The kinds of entry the documents write in the logbook; the key of the package is their source	*/
		public const array KINDS = [ 'document-finalised', 'document-released', 'document-sent', 'document-paid', 'document-reminded', 'document-signed' ];

		/**	The permission of the panel of Pdf that a page needs to look at the templates, which the settings view says whether the account holds	*/
		private const string PDF_PERM = '/_admin/pdf/manage';

		/**
		 *	Enter the documents where the core deletes and reads rows of the clients: a client with a document that is fixed is not deleted and its drafts with their lines go with it (the preview counts both), the projects a
		 *	document of another client names are the ones the client cannot take along, the test phase deletes the documents, counts them and removes their files, and the kinds of the logbook are the package's. The core names no
		 *	table, no column and no directory of the documents
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	bool											Whether every place took what was entered
		 */
		public static function register( array &$appData ): bool {

			$done = \Nino\Modules\Crm\Logbook::addKinds( $appData, self::KINDS, [ 'package' => 'crm-documents' ] );

			// The rows of the drafts are entered where the refusal for the fixed documents is: without it they are not entered
			$fixed = Erasure::add( $appData, [ 'package' => 'crm-documents', 'key' => 'fixed', 'before' => 'projects', 'count' => [ self::class, 'countFixed' ], 'reason' => 'fixed', 'answer' => [ 409, 'the client has a fixed document', 'crm_documents_client_fixed' ], 'shared' => [ self::class, 'sharedProjects' ] ] );
			$drafts = $fixed === true && Erasure::add( $appData, [ 'package' => 'crm-documents', 'key' => 'drafts', 'before' => 'projects', 'count' => [ self::class, 'countDrafts' ], 'rows' => [ self::class, 'eraseRows' ], 'rows_before' => 'logbook' ] );
			$wipe = Wipe::add( $appData, [ 'package' => 'crm-documents', 'key' => 'documents', 'count' => true, 'rows' => [ self::class, 'wipeRows' ], 'rows_before' => 'clients', 'leftover' => [ self::class, 'wipeFiles' ] ] );

			\Nino\Callbacks::registerCallback( $appData, \Nino\Modules\Crm\Projects::HOLDS, [ self::class, 'callbackProjectHolds' ] );
			\Nino\Callbacks::registerCallback( $appData, \Nino\Modules\Crm\Projects::FIGURES, [ self::class, 'callbackProjectFigures' ] );
			\Nino\Callbacks::registerCallback( $appData, \Nino\Modules\Crm\Overview::HOOK, [ self::class, 'callbackOverview' ] );
			\Nino\Callbacks::registerCallback( $appData, '/crm/settings/view', [ self::class, 'callbackSettingsView' ] );
			\Nino\Callbacks::registerCallback( $appData, \Nino\Modules\Crm\Settings\Api::SAVE, [ self::class, 'callbackSettingsSave' ] );
			\Nino\Callbacks::registerCallback( $appData, \Nino\Modules\Crm\Values::START, [ Rules::class, 'callbackStart' ] );

			return $done === true && $drafts === true && $wipe === true;
		}

		/**
		 *	'/crm/project/holds': the documents that name a project keep it from being deleted and from changing its client (the CRM asks before it deletes a project and before it changes the client of one)
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		&$args				project and holds
		 *
		 *	@return 	void
		 */
		public static function callbackProjectHolds( array &$appData, array &$args ): void {

			$count = (int) ( Db::row( $appData, 'SELECT COUNT(*) AS n FROM documents WHERE project = :project', [ 'project' => (string) ( $args['project'] ?? '' ) ] )['n'] ?? 0 );

			if( $count > 0 )
				$args['holds'][] = [ 'key' => 'crm-documents', 'count' => $count, 'delete' => true, 'client' => true, 'why' => 'documents name this project', 'names' => 'Documents', 'delete_code' => 'crm_documents_project_named', 'client_code' => 'crm_documents_project_client' ];
		}

		/**
		 *	'/crm/project/figures': how many documents each project of a list has and how many of them are offers that are open or sent - one grouped query for the whole list, whatever the length of the list
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		&$args				projects (the ids) and figures
		 *
		 *	@return 	void
		 */
		public static function callbackProjectFigures( array &$appData, array &$args ): void {

			$counts = [];

			foreach( Db::rows( $appData, 'SELECT project, COUNT(*) AS documents, SUM( CASE WHEN category = \'offer\' AND state IN ( \'open\', \'sent\' ) THEN 1 ELSE 0 END ) AS open FROM documents WHERE project <> :none GROUP BY project', [ 'none' => '' ] ) as $row )
				$counts[(string) $row['project']] = [ 'documents' => (int) $row['documents'], 'open' => (int) $row['open'] ];

			foreach( is_array( $args['projects'] ?? null ) === true ? $args['projects'] : [] as $id )
				$args['figures'][(string) $id] = ( $args['figures'][(string) $id] ?? [] ) + ( $counts[(string) $id] ?? [ 'documents' => 0, 'open' => 0 ] );
		}

		/**
		 *	'/crm/overview': what the documents show on the first screen - the books at a glance, the documents of the latest dates and the currency the amounts are written in - and what needs the owner
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		&$args				packages and needs
		 *
		 *	@return 	void
		 */
		public static function callbackOverview( array &$appData, array &$args ): void {

			$args['packages']['crm-documents'] = [ 'summary' => Figures::summary( $appData ), 'recent' => Figures::recent( $appData, 8 ), 'currency' => Settings::get( $appData, 'currency' ) ];

			foreach( Figures::needs( $appData ) as $need )
				$args['needs'][] = [ 'package' => 'crm-documents' ] + $need;
		}

		/**
		 *	'/crm/settings/view': the numbers the sequences give next, the templates of Pdf by tag (the names) and whether Pdf is there and the account may look at it
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		&$args				packages
		 *
		 *	@return 	void
		 */
		public static function callbackSettingsView( array &$appData, array &$args ): void {

			$year = (int) date( 'Y' );
			$next = [];

			foreach( Rules::FORMATS as $category => $key )
				$next[$category] = Numbers::upcoming( $appData, $category, $year, Settings::get( $appData, $key ), Settings::start( $appData, $category ) );

			$tags = [];

			foreach( [ 'offer', 'invoice', 'contract', 'privacy', 'information' ] as $tag )
				$tags[$tag] = array_column( Templates::of( $appData, $tag ), 'name' );

			$args['packages']['crm-documents'] = [ 'next' => $next, 'templates' => $tags, 'pdf' => [ 'on' => \Nino\Modules\Crm::featureOn( $appData, 'pdf' ), 'allowed' => \Nino\Auth::checkPermission( $appData, self::PDF_PERM ) ] ];
		}

		/**
		 *	'/crm/settings/save': the VAT rates written in percent, the form of the screen, are the rates the settings keep; a list that is none refuses the whole post
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		&$args				posted, clean and refusal
		 *
		 *	@return 	void
		 */
		public static function callbackSettingsSave( array &$appData, array &$args ): void {

			if( is_array( $args['posted'] ?? null ) === false )
				return;

			// Two kinds of document whose formats give one text for one counter in this year would be given the same numbers: the second would be refused at every finalising, for good. What the settings would hold after this post is what is compared
			if( is_array( $args['refusal'] ?? null ) === false && array_intersect_key( $args['clean'] ?? [], array_flip( Rules::FORMATS ) ) !== [] ) {

				$formats = [];

				foreach( Rules::FORMATS as $key )
					$formats[$key] = (string) ( $args['clean'][$key] ?? Settings::get( $appData, $key ) );

				foreach( array_keys( $formats ) as $at => $key )
					foreach( array_slice( array_keys( $formats ), $at + 1 ) as $other )
						if( Rules::collide( $formats[$key], $formats[$other] ) === true ) {
							$args['refusal'] = [ 'status' => 400, 'message' => 'two kinds of document have one number format', 'code' => 'crm_documents_numberformat_same', 'params' => [], 'field' => in_array( $key, array_keys( (array) ( $args['clean'] ?? [] ) ), true ) === true ? $key : $other ];

							return;
						}
			}

			if( array_key_exists( 'vat_rates_percent', $args['posted'] ) === false )
				return;

			$rates = Rules::percent( $args['posted']['vat_rates_percent'] );

			if( is_array( $rates ) === true ) {
				$args['refusal'] = [ 'status' => 400, 'message' => 'the setting has a wrong value', 'code' => 'crm_documents_setting', 'params' => [], 'field' => 'vat_rates_percent' ];

				return;
			}

			$args['clean']['vat_rates'] = $rates;
		}

		/**
		 *	The restore of a backup, asked by the kernel ('/nino/admin/restore') after the core has restored its database: the files of the books lie in the staging as files, and the kernel would copy them over the live ones. They belong
		 *	to the rows of the database, so they follow it: where the core says its database is the one of the archive now ('restored'), the files stay in the staging and are copied - a live file with a name the archive has is
		 *	replaced by the file of the archive, which is the file of the row that is there now. Where the database stays as it is ('kept': it is held by a process, the copy was refused, or the core is off), every file of the books is
		 *	taken out of the staging, so that not one file of a live document is touched: the files that the live rows name are what they were. Nothing outside the staging is touched
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		&$args					dataDir: the live data directory, staging: the folder of the extracted archive
		 *
		 *	@return 	void
		 *
		 *	@throws		\RuntimeException			The arguments are not what the restore gives, or the files could not be taken out of the staging
		 */
		public static function callbackRestore( array &$appData, array &$args ): void {

			if( is_string( $args['dataDir'] ?? null ) === false || is_string( $args['staging'] ?? null ) === false || $args['dataDir'] === '' || $args['staging'] === '' )
				throw new \RuntimeException( 'the restore did not say where the data is and where the copy lies' );

			// The core off is a core that did not restore its database
			if( \Nino\Modules\Crm::featureOn( $appData, 'crm' ) === true && \Nino\Modules\Crm::restore( $appData, $args ) === 'restored' )
				return;

			$books = rtrim( $args['staging'], '/' ). Render::DIR;

			if( is_link( $books ) === true ) {

				if( @unlink( $books ) === false )
					throw new \RuntimeException( 'the files of the books could not be taken out of the staging of the restore' );

				return;
			}

			if( is_dir( $books ) === false )
				return;

			$entries = new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $books, \FilesystemIterator::SKIP_DOTS ), \RecursiveIteratorIterator::CHILD_FIRST );

			foreach( $entries as $entry ) {

				$gone = $entry->isDir() === true && $entry->isLink() === false ? @rmdir( $entry->getPathname() ) : @unlink( $entry->getPathname() );

				if( $gone === false )
					throw new \RuntimeException( 'the files of the books could not be taken out of the staging of the restore' );
			}

			if( @rmdir( $books ) === false )
				throw new \RuntimeException( 'the files of the books could not be taken out of the staging of the restore' );
		}

		/**
		 *	How many documents of a client are fixed: a number, or a state but draft. The statement of remove() read the other way round (Erasure::add(): the count of the refusal, asked again inside the transaction)
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		$client				As Erasure::add() hands it: id
		 *
		 *	@return 	int
		 */
		public static function countFixed( array &$appData, array $client ): int {

			return (int) ( Db::row( $appData, 'SELECT COUNT(*) AS n FROM documents WHERE client_id = :id AND NOT ( state = \'draft\' AND number IS NULL )', [ 'id' => $client['id'] ] )['n'] ?? 0 );
		}

		/**
		 *	How many documents of a client are drafts: the ones that go with it (Erasure::add())
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		$client				As Erasure::add() hands it: id
		 *
		 *	@return 	int
		 */
		public static function countDrafts( array &$appData, array $client ): int {

			return (int) ( Db::row( $appData, 'SELECT COUNT(*) AS n FROM documents WHERE client_id = :id', [ 'id' => $client['id'] ] )['n'] ?? 0 ) - self::countFixed( $appData, $client );
		}

		/**
		 *	The projects of a client that a document of another client names (Erasure::add()): such a project is not the client's alone
		 *
		 *	@param		array 		&$appData
		 *	@param		int				$id						The client
		 *	@param		array 		$projects			The ids of the projects of the client
		 *
		 *	@return 	array											The ids that are named
		 */
		public static function sharedProjects( array &$appData, int $id, array $projects ): array {

			$named = [];

			foreach( array_values( $projects ) as $at => $project )
				$named['v'. $at] = (string) $project;

			if( $named === [] )
				return [];

			$in = implode( ', ', array_map( static fn( string $name ): string => ':'. $name, array_keys( $named ) ) );

			return array_map( 'strval', array_column( Db::rows( $appData, 'SELECT DISTINCT project FROM documents WHERE client_id <> :id AND project IN ( '. $in. ' )', [ 'id' => $id ] + $named ), 'project' ) );
		}

		/**
		 *	Delete the drafts of a client with their lines, and only them - and the files of cut-off finalisings that no document holds: the step of the deletion of a client (Erasure::add()) - in its transaction, after the records, which name documents. A draft that names another goes
		 *	first. A document that is fixed stays: the refusal of the step before it has stopped the deletion already, and the constraint of the table stops it where that was not asked
		 *
		 *	@param		array 		&$appData
		 *	@param		callable	$run					( string $sql, array $params = [] ): int, a statement of the transaction
		 *	@param		array 		$client				As Erasure::add() hands it: id
		 *
		 *	@return 	int												How many drafts went
		 */
		public static function eraseRows( array &$appData, callable $run, array $client ): int {

			$run( 'DELETE FROM document_lines WHERE document_id IN ( SELECT id FROM documents WHERE client_id = :id AND state = \'draft\' AND number IS NULL )', [ 'id' => $client['id'] ] );

			$gone = Erasure::peel( $run, 'documents', 'parent_id', $client['id'], [ 'state' => 'draft', 'number' => null ] );

			// What a finalising that was cut off before its commit left under pending names carries the address and the mail of the client: it goes with the drafts, in the transaction that holds the write lock (see Render::purge())
			Render::purge( $appData );

			return $gone;
		}

		/**
		 *	Delete every document with its lines: the step of the test phase (Wipe::add()) - in its transaction, after the records
		 *
		 *	@param		array 		&$appData
		 *	@param		callable	$run					( string $sql, array $params = [] ): int, a statement of the transaction
		 *
		 *	@return 	int												How many documents went
		 */
		public static function wipeRows( array &$appData, callable $run ): int {

			$run( 'DELETE FROM document_lines' );

			return Wipe::peel( $run, 'documents', 'parent_id' );
		}

		/**
		 *	Remove the PDF files and e-invoices of the documents: what the test phase leaves behind (Wipe::add()). A document that is numbered again would find its file. The year directories of the package, and nothing else
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		&$done				The counts so far: not touched, the step of the documents counts the documents
		 *
		 *	@return 	bool											false where a directory of a year is still there: the run stops at the package and the next press asks again
		 */
		public static function wipeFiles( array &$appData, array &$done ): bool {

			$directory = \Nino\Filesystem::path( $appData, Render::DIR );

			foreach( is_dir( $directory ) === true ? ( scandir( $directory ) ?: [] ) : [] as $year ) {

				if( preg_match( '/^[0-9]{4}$/D', $year ) !== 1 )
					continue;

				\Nino\Filesystem::removeDir( $directory. '/'. $year );

				if( file_exists( $directory. '/'. $year ) === true || is_link( $directory. '/'. $year ) === true )
					return false;
			}

			return true;
		}
	}
}
