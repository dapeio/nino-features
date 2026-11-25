<?php
declare(strict_types=1);

/**
 *	Nino features
 *	snapshot-load.php		A loader for the comparison of the core with the CRM it replaces, for the test alone: it brings the tables and the elements of a copy of an installation of the old CRM (a directory private/ with
 *											data/crm.sqlite and elements/projekte.php, elements/phasen.php) into a project of the new core, column by column as the schema of the core has them. It is no tool of a move: it reads what it is given
 *											and writes into a sandbox, takes nothing that belongs to a package (accounts, documents, the download of a project, its folder), and nothing of what it reads is kept in the repository.
 *
 *											crmSnapshotLoad( $appData, $private ) answers the number of rows it made for each table.
 */

/**
 *	@param		array 		&$appData			The app data of a project in which the core is switched on
 *	@param		string		$private			The directory private/ of the copy of the old installation
 *
 *	@return 	array											table => rows made, elements => projects and phases made
 */
function crmSnapshotLoad( array &$appData, string $private ): array {

	$old = new \PDO( 'sqlite:file:'. $private. '/data/crm.sqlite?mode=ro', null, null, [ \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION, \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC ] );
	$pdo = \Nino\Modules\Crm\Db::pdo( $appData );
	$made = [];

	// The columns the core has for each table; what the old table has besides belongs to a package and is not taken
	$columns = static function( string $table ) use ( $pdo ): array {
		return array_column( $pdo->query( 'PRAGMA table_info( "'. $table. '" )' )->fetchAll( \PDO::FETCH_ASSOC ), 'name' );
	};
	$has = static function( string $table ) use ( $old ): bool {
		return $old->query( 'SELECT 1 FROM sqlite_master WHERE type = \'table\' AND name = \''. $table. '\'' )->fetchColumn() !== false;
	};
	$take = static function( string $table, ?callable $each = null ) use ( $old, $pdo, $columns, $has, &$made ): void {

		$made[$table] = 0;

		if( $has( $table ) === false )
			return;

		$mine = $columns( $table );

		foreach( $old->query( 'SELECT * FROM "'. $table. '"' )->fetchAll() as $row ) {

			$row = $each === null ? $row : $each( $row );
			$row = array_intersect_key( $row, array_flip( $mine ) );
			$pdo->prepare( 'INSERT INTO "'. $table. '" ( "'. implode( '", "', array_keys( $row ) ). '" ) VALUES ( :'. implode( ', :', array_keys( $row ) ). ' )' )->execute( $row );
			$made[$table]++;
		}
	};

	$pdo->exec( 'PRAGMA foreign_keys = OFF' );
	$pdo->exec( 'BEGIN IMMEDIATE' );

	$take( 'clients' );
	$take( 'settings' );
	$take( 'sequences' );
	$take( 'project_states' );
	$take( 'project_shares' );
	$take( 'owner_devices' );
	$take( 'logbook' );

	// A record that named a document names a thing of a package now: the kind of thing and its id as text
	$take( 'records', static function( array $row ): array {

		$row['thing'] = $row['document_id'] === null ? '' : 'document';
		$row['thing_id'] = $row['document_id'] === null ? '' : (string) $row['document_id'];

		return $row;
	} );

	// A task that came from a comment names the file and the comment; the core keeps one reference of its source
	$take( 'tasks', static function( array $row ): array {

		$row['ref'] = (string) ( $row['file'] ?? '' ) === '' ? '' : (string) $row['file']. ':'. (string) $row['comment'];

		return $row;
	} );

	$pdo->exec( 'COMMIT' );
	$pdo->exec( 'PRAGMA foreign_keys = ON' );

	// The projects and the phases are elements, of the types under their new names, without the folder of a project
	$made['elements'] = [ 'projects' => 0, 'phases' => 0 ];

	foreach( [ 'projekte' => [ 'crm-projects', 'projects' ], 'phasen' => [ 'crm-phases', 'phases' ] ] as $type => [ $new, $label ] ) {

		$file = $private. '/elements/'. $type. '.php';

		if( is_file( $file ) === false )
			continue;

		$data = (array) include $file;
		$model = (array) ( include dirname( __DIR__, 2 ). '/install/'. $new. '.php' )['model'];

		foreach( $data as $bucket => $elements ) {

			if( is_array( $elements ) === false || in_array( $bucket, [ 'model', 'title', 'autoincrement' ], true ) === true )
				continue;

			foreach( $elements as $id => $fields )
				if( is_array( $fields ) === true ) {
					unset( $data[$bucket][$id]['folder'] );
					$made['elements'][$label] += $bucket === '*' || $type === 'phasen' ? 1 : 0;
				}
		}

		$data['title'] = (string) ( ( include dirname( __DIR__, 2 ). '/install/'. $new. '.php' )['title'] );
		$data['model'] = $model;
		\Nino\Filesystem::putFileContent( $appData, '/elements/'. $new. '.php', $data );
	}

	unset( $appData['./nino/elements/cache'] );

	return $made;
}
