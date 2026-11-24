<?php
declare(strict_types=1);
/**
 *	Nino								A compact filesystembased php framework
 *	Nino\Modules\Files\Store		The data of the Files feature: the index, the bytes and the parts
 *
 *	@package						Dape/Nino
 *	@author							David Perchermeier <mail@dape.io>
 *	@link								https://github.com/dapeio/nino
 */
namespace Nino\Modules\Files {

	/**
	 *	Nino							A compact filesystembased php framework
	 *	Files\Store				What the feature keeps and how it keeps it. The index is one
	 *										file, /data/files.php, that holds the folders and the files:
	 *
	 *											[ 'folders' => [ <id> => [ group, name, upload, created ] ],
	 *											  'files'   => [ <id> => [ folder, name, ext, size, mime, sha256, by, visible, final, created, gone? ] ] ]
	 *
	 *										Every write is a Filesystem::mutate() of that file, and the
	 *										file is plain php anybody can edit, so parse() reads what is
	 *										there for what it has to be and leaves out an entry that is not.
	 *										The bytes are another matter: /data/files/<id>.<ext>, flat, named
	 *										by the id of the entry - a path is built from an id that matched
	 *										ID and an extension that matched EXTENSION and from nothing a
	 *										request sends. A file is deleted from the index first and from
	 *										the disk second. An upload that is not finished lies in
	 *										/data/files/.upload/<upload id>.part and <upload id>.chunk (see Files\Upload).
	 *
	 *										The class holds the rules the panel and the route share: the
	 *										extensions and the MIME types they may carry (MIME), the name of
	 *										a file as a browser sent it made into the name it is kept
	 *										under, the limits of the settings, the quota of a group, and
	 *										the words of a size.
	 *
	 *	@package					Dape/Nino
	 *	@author						David Perchermeier <mail@dape.io>
	 *	@link							https://github.com/dapeio/nino
	 */
	class Store {

		public const string INDEX = '/data/files.php';

		// The index as parse() made it, with the state it was made from
		private const string PARSED = './files/store/parsed';

		// Where the bytes lie, and the uploads that are not finished
		public const string BYTES = '/data/files';
		public const string PARTS = '/data/files/.upload';

		// The shape of an id of a folder and of a file, of the extension a file is kept under and of the slug a group is
		public const string ID = '/^[a-f0-9]{12}$/D';
		public const string EXTENSION = '/^[a-z0-9]{1,8}$/D';
		public const string GROUP = '/^[a-z0-9]+(?:-[a-z0-9]+)*$/D';

		public const string TIME = 'Y-m-d\TH:i:s\Z';

		public const int NAME_LENGTH = 160;
		public const int FOLDER_LENGTH = 80;
		public const int MEGABYTE = 1048576;

		// An upload that has not been touched for this long is gone when the next one starts
		public const int STALE = 86400;

		// An upload holds its room against the quota while its chunks come: it holds nothing once its last chunk is older than this (15 minutes)
		public const int IDLE = 900;

		// What a request of a chunk carries besides the chunk: php counts the fields in post_max_size too
		public const int HEADROOM = 65536;

		/*	The extensions the feature knows and the MIME types a file of each may be: what finfo says of the bytes has to be
			one of them, whatever the name claims, for every extension in this list. An extension that is not here is refused whatever
			the setting types says - the setting chooses among these. The list of an extension holds the names libmagic has used for
			it over time - and the one it gives a text of comma-separated lines, whatever the name of the file says. Nothing a browser
			executes is in it: no html, no svg, no xml, no script	*/
		public const array MIME = [
			'jpg'		=> [ 'image/jpeg' ],
			'jpeg'	=> [ 'image/jpeg' ],
			'png'		=> [ 'image/png' ],
			'gif'		=> [ 'image/gif' ],
			'webp'	=> [ 'image/webp' ],
			'pdf'		=> [ 'application/pdf' ],
			'docx'	=> [ 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' ],
			'xlsx'	=> [ 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' ],
			'pptx'	=> [ 'application/vnd.openxmlformats-officedocument.presentationml.presentation' ],
			'odt'		=> [ 'application/vnd.oasis.opendocument.text' ],
			'ods'		=> [ 'application/vnd.oasis.opendocument.spreadsheet' ],
			'odp'		=> [ 'application/vnd.oasis.opendocument.presentation' ],
			'txt'		=> [ 'text/plain', 'text/csv' ],
			'md'		=> [ 'text/plain', 'text/markdown', 'text/csv' ],
			'csv'		=> [ 'text/csv', 'text/plain' ],
			'zip'		=> [ 'application/zip', 'application/x-zip-compressed', 'application/x-zip' ],
			'mp3'		=> [ 'audio/mpeg' ],
			'wav'		=> [ 'audio/x-wav', 'audio/wav', 'audio/vnd.wave' ],
			'flac'	=> [ 'audio/flac', 'audio/x-flac' ],
			'aiff'	=> [ 'audio/x-aiff', 'audio/aiff' ],
			'aif'		=> [ 'audio/x-aiff', 'audio/aiff' ],
			'm4a'		=> [ 'audio/x-m4a', 'audio/mp4', 'video/mp4' ],
			'ogg'		=> [ 'audio/ogg', 'application/ogg', 'video/ogg' ],
			'opus'	=> [ 'audio/ogg', 'application/ogg' ],
			'aac'		=> [ 'audio/aac', 'audio/x-hx-aac-adts' ],
			'mp4'		=> [ 'video/mp4' ],
			'm4v'		=> [ 'video/x-m4v', 'video/mp4' ],
			'mov'		=> [ 'video/quicktime' ],
			'webm'	=> [ 'video/webm', 'audio/webm' ],
		];

		/**
		 *	The index, read for what it has to be. A folder is an id of 12 hex characters with a group that is a
		 *	slug and strings for the rest; a file is an id of 12 hex characters in a folder the index has, an
		 *	extension, a size that is a whole number, who put it there (the owner, or a member by the id of the
		 *	account) and whether it is visible. What is not that is left out - the file is plain php and editable
		 *
		 *	@param		mixed			$state				As the file holds it
		 *
		 *	@return 	array										[ 'folders' => id => entry, 'files' => id => entry ] - an entry of a file that was removed carries gone, the time its bytes went
		 */
		public static function parse( mixed $state ): array {

			$text = static fn( mixed $value ): string => is_string( $value ) === true ? $value : '';
			$source = is_array( $state ) === true ? $state : [];
			$folders = [];
			$files = [];

			foreach( is_array( $source['folders'] ?? null ) === true ? $source['folders'] : [] as $id => $record ) {

				$id = (string) $id;

				if( preg_match( self::ID, $id ) !== 1 || is_array( $record ) === false || preg_match( self::GROUP, $text( $record['group'] ?? null ) ) !== 1 )
					continue;

				$folders[$id] = [
					'group'		=> $text( $record['group'] ),
					'name'		=> $text( $record['name'] ?? null ),
					'upload'	=> ( $record['upload'] ?? false ) === true,
					'created'	=> $text( $record['created'] ?? null ),
				];
			}

			foreach( is_array( $source['files'] ?? null ) === true ? $source['files'] : [] as $id => $record ) {

				$id = (string) $id;

				if( preg_match( self::ID, $id ) !== 1 || is_array( $record ) === false || isset( $folders[ $text( $record['folder'] ?? null ) ] ) === false
					|| preg_match( self::EXTENSION, $text( $record['ext'] ?? null ) ) !== 1 || is_int( $record['size'] ?? null ) === false || $record['size'] < 0 )
					continue;

				$by = is_array( $record['by'] ?? null ) === true ? $record['by'] : [];

				if( in_array( $by['kind'] ?? null, [ 'owner', 'member' ], true ) === false )
					continue;

				$files[$id] = [
					'folder'	=> $text( $record['folder'] ),
					'name'		=> $text( $record['name'] ?? null ),
					'ext'			=> $text( $record['ext'] ),
					'size'		=> $record['size'],
					'mime'		=> $text( $record['mime'] ?? null ),
					'sha256'	=> $text( $record['sha256'] ?? null ),
					'by'			=> [ 'kind' => $by['kind'], 'id' => $text( $by['id'] ?? null ) ],
					'visible'	=> ( $record['visible'] ?? false ) === true,
					'final'		=> ( $record['final'] ?? false ) === true,
					'created'	=> $text( $record['created'] ?? null ),
				] + ( $text( $record['gone'] ?? null ) !== '' ? [ 'gone' => $text( $record['gone'] ) ] : [] );
			}

			return [ 'folders' => $folders, 'files' => $files ];
		}

		/**
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	array										The index as parse() has it
		 */
		public static function read( array &$appData ): array {

			$state = \Nino\Filesystem::getFileContent( $appData, self::INDEX, [] );

			// A page asks for a folder or a file once for each file it draws: what parse() made of the state it was given is kept with that state (for the request, in the app data), so that a state
			// that is not the one before - the index was written - is parsed anew and the same state never twice
			if( ( $appData[self::PARSED]['state'] ?? null ) !== $state || isset( $appData[self::PARSED]['index'] ) === false )
				$appData[self::PARSED] = [ 'state' => $state, 'index' => self::parse( $state ) ];

			return $appData[self::PARSED]['index'];
		}

		/**
		 *	The folders of a group, sorted by name (without regard to case)
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$group				A slug
		 *
		 *	@return 	array										A list of folders, each with its id
		 */
		public static function folders( array &$appData, string $group ): array {

			$list = [];

			foreach( self::read( $appData )['folders'] as $id => $folder )
				if( $folder['group'] === $group )
					$list[] = [ 'id' => (string) $id ] + $folder;

			usort( $list, static fn( array $a, array $b ): int => [ mb_strtolower( $a['name'] ), $a['id'] ] <=> [ mb_strtolower( $b['name'] ), $b['id'] ] );

			return $list;
		}

		/**
		 *	One folder
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$id
		 *
		 *	@return 	array|null								With its id; null for an id nobody has
		 */
		public static function folder( array &$appData, string $id ): ?array {

			$folder = self::read( $appData )['folders'][$id] ?? null;

			return $folder === null ? null : [ 'id' => $id ] + $folder;
		}

		/**
		 *	The files of a folder, the newest first
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$folder				The id of a folder
		 *
		 *	@return 	array										A list of files, each with its id - the hidden ones too
		 */
		public static function files( array &$appData, string $folder ): array {

			$list = [];

			foreach( self::read( $appData )['files'] as $id => $file )
				if( $file['folder'] === $folder )
					$list[] = [ 'id' => (string) $id ] + $file;

			usort( $list, static fn( array $a, array $b ): int => [ $b['created'], mb_strtolower( $a['name'] ), $a['id'] ] <=> [ $a['created'], mb_strtolower( $b['name'] ), $b['id'] ] );

			return $list;
		}

		/**
		 *	One file
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$id
		 *
		 *	@return 	array|null								With its id; null for an id nobody has
		 */
		public static function file( array &$appData, string $id ): ?array {

			if( preg_match( self::ID, $id ) !== 1 )
				return null;

			$file = self::read( $appData )['files'][$id] ?? null;

			return $file === null ? null : [ 'id' => $id ] + $file;
		}

		/**
		 *	Where the bytes of a file are: the directory of the bytes, the id and the extension. Built from those two
		 *	and nothing else, and only when both are what they have to be
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$id
		 *	@param		string		$ext
		 *
		 *	@return 	string|null								Null for an id or an extension that is none
		 */
		public static function bytes( array &$appData, string $id, string $ext ): ?string {

			if( preg_match( self::ID, $id ) !== 1 || preg_match( self::EXTENSION, $ext ) !== 1 )
				return null;

			return \Nino\Filesystem::path( $appData, self::BYTES ). '/'. $id. '.'. $ext;
		}

		/**
		 *	The absolute path of the bytes of a file that is in the index and on the disk
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$id
		 *
		 *	@return 	string|null								Null where the index has no such file, the file was removed or its bytes are gone
		 */
		public static function path( array &$appData, string $id ): ?string {

			$file = self::file( $appData, $id );
			$path = $file === null || isset( $file['gone'] ) === true ? null : self::bytes( $appData, $id, $file['ext'] );

			return $path !== null && is_file( $path ) === true ? $path : null;
		}

		/**
		 *	Add a folder to a group. The name is cleaned of control characters and at most FOLDER_LENGTH characters
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$group				A slug
		 *	@param		string		$name
		 *
		 *	@return 	array|string							The folder with its id; or why not: 'group' (no slug), 'empty', 'long', 'write'
		 */
		public static function addFolder( array &$appData, string $group, string $name ): array|string {

			$name = self::cleanFolder( $name );

			if( preg_match( self::GROUP, $group ) !== 1 )
				return 'group';

			if( $name === '' )
				return 'empty';

			if( mb_strlen( $name, 'UTF-8' ) > self::FOLDER_LENGTH )
				return 'long';

			$record = [ 'group' => $group, 'name' => $name, 'upload' => false, 'created' => gmdate( self::TIME ) ];
			$id = '';

			$written = \Nino\Filesystem::mutate( $appData, self::INDEX, static function( mixed $state ) use ( $record, &$id ): array {

				$index = self::parse( $state );

				do
					$id = bin2hex( random_bytes( 6 ) );
				while( isset( $index['folders'][$id] ) === true );

				$index['folders'][$id] = $record;

				return $index;
			}, [] );

			return $written === true ? [ 'id' => $id ] + $record : 'write';
		}

		/**
		 *	Rename a folder
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$id
		 *	@param		string		$name
		 *
		 *	@return 	array|string							The folder as it is now; or why not: 'unknown', 'empty', 'long', 'write'
		 */
		public static function renameFolder( array &$appData, string $id, string $name ): array|string {

			$name = self::cleanFolder( $name );

			if( $name === '' )
				return 'empty';

			if( mb_strlen( $name, 'UTF-8' ) > self::FOLDER_LENGTH )
				return 'long';

			return self::_folderChange( $appData, $id, static function( array &$index ) use ( $id, $name ): void {
				$index['folders'][$id]['name'] = $name;
			} );
		}

		/**
		 *	Mark a folder as the one a member of its group uploads into - and take the mark from the folder that had it:
		 *	a group has one at most, and the two changes are one write. Or take the mark off, and the group has none
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$id
		 *	@param		bool			$on
		 *
		 *	@return 	array|string							The folder as it is now; or why not: 'unknown', 'write'
		 */
		public static function markUpload( array &$appData, string $id, bool $on ): array|string {

			return self::_folderChange( $appData, $id, static function( array &$index ) use ( $id, $on ): void {

				foreach( $index['folders'] as $other => $folder )
					if( $folder['group'] === $index['folders'][$id]['group'] )
						$index['folders'][$other]['upload'] = $on === true && (string) $other === $id;
			} );
		}

		/**
		 *	Delete a folder and its files: the entries leave the index in one write, the bytes go after that
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$id
		 *
		 *	@return 	array|string							[ 'folder' => the folder, 'files' => the files it had ]; or why not: 'unknown', 'write'
		 */
		public static function deleteFolder( array &$appData, string $id ): array|string {

			$ran = false;
			$gone = null;
			$lost = [];

			$written = \Nino\Filesystem::mutate( $appData, self::INDEX, static function( mixed $state ) use ( $id, &$ran, &$gone, &$lost ): ?array {

				$ran = true;
				$index = self::parse( $state );

				if( isset( $index['folders'][$id] ) === false )
					return null;

				$gone = [ 'id' => $id ] + $index['folders'][$id];
				unset( $index['folders'][$id] );

				foreach( $index['files'] as $fileId => $file )
					if( $file['folder'] === $id ) {
						$lost[] = [ 'id' => (string) $fileId ] + $file;
						unset( $index['files'][$fileId] );
					}

				return $index;
			}, [] );

			if( $ran === true && $gone === null )
				return 'unknown';

			if( $written === false || $gone === null )
				return 'write';

			foreach( $lost as $file )
				self::_unlink( $appData, $file );

			return [ 'folder' => $gone, 'files' => $lost ];
		}

		/**
		 *	Delete every folder of the groups a test picks, with the files in them: all the entries leave the index in one write, the bytes go after that. A group nobody picks keeps its folders
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		callable	$select				( string $group ): bool - whether the folders of the group go
		 *
		 *	@return 	array|string							[ 'folders' => the folders that went, 'files' => the files they had ], each with its id; or why not: 'write'
		 */
		public static function wipeGroups( array &$appData, callable $select ): array|string {

			$ran = false;
			$lost = [ 'folders' => [], 'files' => [] ];

			$written = \Nino\Filesystem::mutate( $appData, self::INDEX, static function( mixed $state ) use ( $select, &$ran, &$lost ): ?array {

				$ran = true;
				$lost = [ 'folders' => [], 'files' => [] ];
				$index = self::parse( $state );

				foreach( $index['folders'] as $id => $folder )
					if( $select( $folder['group'] ) === true ) {
						$lost['folders'][(string) $id] = [ 'id' => (string) $id ] + $folder;
						unset( $index['folders'][$id] );
					}

				if( $lost['folders'] === [] )
					return null;

				foreach( $index['files'] as $fileId => $file )
					if( isset( $lost['folders'][ $file['folder'] ] ) === true ) {
						$lost['files'][] = [ 'id' => (string) $fileId ] + $file;
						unset( $index['files'][$fileId] );
					}

				return $index;
			}, [] );

			if( $ran === false || ( $lost['folders'] !== [] && $written === false ) )
				return 'write';

			foreach( $lost['files'] as $file )
				self::_unlink( $appData, $file );

			return [ 'folders' => array_values( $lost['folders'] ), 'files' => $lost['files'] ];
		}

		/**
		 *	Put a file into the index, its bytes already in place: the folder has to be there and, where the group has a quota,
		 *	the file may not take the group over it - decided inside the write, so that two uploads at once cannot both fit
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$id
		 *	@param		array 		$record				folder, name, ext, size, mime, sha256, by, visible, final (false where it is left out) and created
		 *	@param		int				$quota				The most bytes a group may hold, 0 for no limit
		 *	@param		string		$upload				The id of the upload the file is made of, whose own reservation is no other upload's: '' for a file that is no upload
		 *
		 *	@return 	array|string							The file with its id; or why not: 'folder' (gone), 'quota', 'taken' (the id is in use), 'write'
		 */
		public static function addFile( array &$appData, string $id, array $record, int $quota, string $upload = '' ): array|string {

			$refused = '';

			$written = \Nino\Filesystem::mutate( $appData, self::INDEX, static function( mixed $state ) use ( $appData, $id, $record, $quota, $upload, &$refused ): ?array {

				$index = self::parse( $state );
				$folder = $index['folders'][ (string) $record['folder'] ] ?? null;

				if( $folder === null ) {
					$refused = 'folder';
					return null;
				}

				if( isset( $index['files'][$id] ) === true ) {
					$refused = 'taken';
					return null;
				}

				// The bytes the group holds, and those the unfinished uploads of its folders will bring (every one but this) - decided in the write
				if( $quota > 0 && self::_used( $index, $folder['group'] ) + ( $record['by']['kind'] === 'owner' ? 0 : self::_pending( $appData, $index, $folder['group'], $upload ) ) + (int) $record['size'] > $quota ) {
					$refused = 'quota';
					return null;
				}

				$index['files'][$id] = $record;

				return $index;
			}, [] );

			if( $written === true )
				return [ 'id' => $id ] + $record;

			return $refused !== '' ? $refused : 'write';
		}

		/**
		 *	Change a file: whether it is visible, whether the owner marked it as final, and the folder it is in - another folder of the same group
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$id
		 *	@param		array 		$changes			Any of 'visible' (bool), 'final' (bool) and 'folder' (an id)
		 *
		 *	@return 	array|string							[ 'file' => as it is now, 'before' => as it was ]; or why not: 'unknown', 'folder' (no such folder, or one of another group), 'write'
		 */
		public static function changeFile( array &$appData, string $id, array $changes ): array|string {

			$before = null;
			$after = null;
			$refused = '';

			$written = \Nino\Filesystem::mutate( $appData, self::INDEX, static function( mixed $state ) use ( $id, $changes, &$before, &$after, &$refused ): ?array {

				$index = self::parse( $state );

				if( isset( $index['files'][$id] ) === false ) {
					$refused = 'unknown';
					return null;
				}

				$before = [ 'id' => $id ] + $index['files'][$id];

				if( is_string( $changes['folder'] ?? null ) === true ) {

					$target = $index['folders'][ $changes['folder'] ] ?? null;

					if( $target === null || $target['group'] !== $index['folders'][ $before['folder'] ]['group'] ) {
						$refused = 'folder';
						return null;
					}

					$index['files'][$id]['folder'] = $changes['folder'];
				}

				if( is_bool( $changes['visible'] ?? null ) === true )
					$index['files'][$id]['visible'] = $changes['visible'];

				if( is_bool( $changes['final'] ?? null ) === true )
					$index['files'][$id]['final'] = $changes['final'];

				$after = [ 'id' => $id ] + $index['files'][$id];

				return $index;
			}, [] );

			if( $written === true && $before !== null && $after !== null )
				return [ 'file' => $after, 'before' => $before ];

			return $refused !== '' ? $refused : 'write';
		}

		/**
		 *	Move a file into a folder - of its group or of another: the entry changes its folder inside one write, and whether it is visible and the id stay,
		 *	so that whatever hangs on the id (the comments of the feature Review) stays with it. Who put it there stays within the group; a file of a member that
		 *	leaves its group has the owner as its sender, since the member who sent it may have it wherever it is - and must not reach a file of another group.
		 *	Into another group the file moves only where that group has room for it (a file whose bytes were removed takes none); within a group the quota is not asked
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$id
		 *	@param		string		$folder				The id of the folder it goes to
		 *
		 *	@return 	array|string							[ 'file' => as it is now, 'before' => as it was ]; or why not: 'unknown', 'folder' (no such folder), 'quota', 'write'
		 */
		public static function moveFile( array &$appData, string $id, string $folder ): array|string {

			$quota = self::quotaBytes( $appData );
			$before = null;
			$after = null;
			$refused = '';

			$written = \Nino\Filesystem::mutate( $appData, self::INDEX, static function( mixed $state ) use ( $id, $folder, $quota, &$before, &$after, &$refused ): ?array {

				$index = self::parse( $state );

				if( isset( $index['files'][$id] ) === false ) {
					$refused = 'unknown';
					return null;
				}

				if( isset( $index['folders'][$folder] ) === false ) {
					$refused = 'folder';
					return null;
				}

				$before = [ 'id' => $id ] + $index['files'][$id];
				$group = $index['folders'][$folder]['group'];

				if( $group !== $index['folders'][$before['folder']]['group'] && $quota > 0 && isset( $before['gone'] ) === false && self::_used( $index, $group ) + $before['size'] > $quota ) {
					$refused = 'quota';
					return null;
				}

				$index['files'][$id]['folder'] = $folder;

				if( $group !== $index['folders'][$before['folder']]['group'] && $before['by']['kind'] === 'member' )
					$index['files'][$id]['by'] = [ 'kind' => 'owner', 'id' => '' ];

				$after = [ 'id' => $id ] + $index['files'][$id];

				return $index;
			}, [] );

			if( $written === true && $before !== null && $after !== null )
				return [ 'file' => $after, 'before' => $before ];

			return $refused !== '' ? $refused : 'write';
		}

		/**
		 *	Copy a file into a folder of any group under a name: the bytes are copied under a new id first, then the entry is put into the index - with the quota
		 *	of the group of the folder decided inside that write - and it is visible as the original is. The copy is the owner's: it carries by owner, not the member who
		 *	put the original there, so that the member of one group does not get a file of another by it. The name keeps the extension of the original
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$id
		 *	@param		string		$folder				The id of the folder the copy goes to
		 *	@param		string		$name					As a person typed it: cleanName() makes the name it is kept under
		 *
		 *	@return 	array|string							The new file with its id; or why not: 'unknown', 'gone' (the bytes were removed or are not there), 'folder', 'name' (none), 'ext' (not the extension of the original), 'quota', 'write'
		 */
		public static function copyFile( array &$appData, string $id, string $folder, string $name ): array|string {

			$file = self::file( $appData, $id );

			if( $file === null )
				return 'unknown';

			$source = self::path( $appData, $id );

			if( $source === null )
				return 'gone';

			if( self::folder( $appData, $folder ) === null )
				return 'folder';

			[ $clean, $ext ] = self::cleanName( $name );

			if( $clean === '' )
				return 'name';

			if( $ext !== $file['ext'] )
				return 'ext';

			$directory = \Nino\Filesystem::path( $appData, self::PARTS );

			if( is_dir( $directory ) === false && @mkdir( $directory, 0755, true ) === false && is_dir( $directory ) === false )
				return 'write';

			// The bytes first, under a name of the parts that the sweep knows, then in place under the new id
			$part = $directory. '/'. bin2hex( random_bytes( 8 ) ). '.part';

			if( @copy( $source, $part ) === false ) {
				@unlink( $part );
				return 'write';
			}

			do
				$new = bin2hex( random_bytes( 6 ) );
			while( self::file( $appData, $new ) !== null || is_file( (string) self::bytes( $appData, $new, $file['ext'] ) ) === true );

			$target = (string) self::bytes( $appData, $new, $file['ext'] );

			if( @rename( $part, $target ) === false ) {
				@unlink( $part );
				return 'write';
			}

			$added = self::addFile( $appData, $new, [
				'folder'	=> $folder,
				'name'		=> $clean,
				'ext'			=> $file['ext'],
				'size'		=> $file['size'],
				'mime'		=> $file['mime'],
				'sha256'	=> $file['sha256'],
				'by'			=> [ 'kind' => 'owner', 'id' => '' ],
				'visible'	=> $file['visible'],
				'final'		=> false,
				'created'	=> gmdate( self::TIME ),
			], self::quotaBytes( $appData ) );

			if( is_string( $added ) === true ) {
				@unlink( $target );
				return $added === 'taken' ? 'write' : $added;
			}

			return $added;
		}

		/**
		 *	Put other bytes in the place of the bytes of a file: they are written to a part first and renamed over the bytes inside the write of the index, so that a reader
		 *	sees the old bytes or the new ones. Where the write of the index itself fails after the rename the answer is 'write' and the index keeps the old size and sha256 until the next
		 *	write puts them right. The size, the sha256 and the day follow the bytes (the day is the one the bytes
		 *	were put there); the name, the folder, who put the file there, whether it is visible and the id stay, and so does the MIME type unless finfo says another one the
		 *	extension carries. A file may grow only where its group has room for the growth
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$id
		 *	@param		string		$bytes				The new content
		 *	@param		string|null	$expected			The sha256 the file has to have now: a file that has another was changed since the caller read it, and nothing is written. Null: no question
		 *
		 *	@return 	array|string							[ 'file' => as it is now, 'before' => as it was ]; or why not: 'unknown', 'gone' (the bytes were removed or are not there), 'changed', 'quota', 'write'
		 */
		public static function writeBytes( array &$appData, string $id, string $bytes, ?string $expected = null ): array|string {

			$file = self::file( $appData, $id );

			if( $file === null )
				return 'unknown';

			$target = self::path( $appData, $id );

			if( $target === null )
				return 'gone';

			$directory = \Nino\Filesystem::path( $appData, self::PARTS );

			if( is_dir( $directory ) === false && @mkdir( $directory, 0755, true ) === false && is_dir( $directory ) === false )
				return 'write';

			// The bytes first, under a name of the parts that the sweep knows
			$part = $directory. '/'. bin2hex( random_bytes( 8 ) ). '.part';

			if( @file_put_contents( $part, $bytes, LOCK_EX ) !== strlen( $bytes ) ) {
				@unlink( $part );
				return 'write';
			}

			$mime = self::sniff( $part, $file['ext'] );
			$sha = hash( 'sha256', $bytes );
			$size = strlen( $bytes );
			$quota = self::quotaBytes( $appData );
			$before = null;
			$after = null;
			$refused = '';

			$written = \Nino\Filesystem::mutate( $appData, self::INDEX, static function( mixed $state ) use ( $id, $part, $target, $mime, $sha, $size, $quota, $expected, &$before, &$after, &$refused ): ?array {

				$index = self::parse( $state );

				if( isset( $index['files'][$id] ) === false ) {
					$refused = 'unknown';
					return null;
				}

				$before = [ 'id' => $id ] + $index['files'][$id];

				if( isset( $before['gone'] ) === true ) {
					$refused = 'gone';
					return null;
				}

				if( $expected !== null && hash_equals( $before['sha256'], $expected ) === false ) {
					$refused = 'changed';
					return null;
				}

				$group = $index['folders'][$before['folder']]['group'];

				if( $quota > 0 && $size > $before['size'] && self::_used( $index, $group ) - $before['size'] + $size > $quota ) {
					$refused = 'quota';
					return null;
				}

				if( @rename( $part, $target ) === false ) {
					$refused = 'write';
					return null;
				}

				$index['files'][$id]['size'] = $size;
				$index['files'][$id]['sha256'] = $sha;
				$index['files'][$id]['created'] = gmdate( self::TIME );

				if( $mime !== '' )
					$index['files'][$id]['mime'] = $mime;

				$after = [ 'id' => $id ] + $index['files'][$id];

				return $index;
			}, [] );

			if( $written === true && $before !== null && $after !== null )
				return [ 'file' => $after, 'before' => $before ];

			@unlink( $part );

			return $refused !== '' ? $refused : 'write';
		}

		/**
		 *	Rename a file: the name it is kept under. The extension stays the extension of the file (the bytes were checked against it), and a file whose name says a
		 *	version (Title.03.ext, see Files::version()) keeps saying one. Its folder, whether it is visible, its id and its bytes stay
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$id
		 *	@param		string		$name					As a person typed it: cleanName() makes the name it is kept under
		 *
		 *	@return 	array|string							[ 'file' => as it is now, 'before' => as it was ]; or why not: 'unknown', 'name' (none), 'ext' (not the extension of the file), 'version' (a version no more), 'write'
		 */
		public static function renameFile( array &$appData, string $id, string $name ): array|string {

			[ $clean, $ext ] = self::cleanName( $name );

			$before = null;
			$after = null;
			$refused = '';

			$written = \Nino\Filesystem::mutate( $appData, self::INDEX, static function( mixed $state ) use ( $id, $clean, $ext, &$before, &$after, &$refused ): ?array {

				$index = self::parse( $state );

				if( isset( $index['files'][$id] ) === false ) {
					$refused = 'unknown';
					return null;
				}

				$before = [ 'id' => $id ] + $index['files'][$id];

				if( $clean === '' )
					$refused = 'name';
				else if( $ext !== $before['ext'] )
					$refused = 'ext';
				else if( \Nino\Modules\Files::version( $before['name'] )['number'] !== null && \Nino\Modules\Files::version( $clean )['number'] === null )
					$refused = 'version';

				if( $refused !== '' )
					return null;

				$index['files'][$id]['name'] = $clean;
				$after = [ 'id' => $id ] + $index['files'][$id];

				return $index;
			}, [] );

			if( $written === true && $before !== null && $after !== null )
				return [ 'file' => $after, 'before' => $before ];

			return $refused !== '' ? $refused : 'write';
		}

		/**
		 *	Delete a file: the entry leaves the index first, the bytes go after that - a file that is in the index and has
		 *	no bytes is an error to find out, bytes with no entry are rubbish nobody can reach
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$id
		 *
		 *	@return 	array|string							The file as it was, with its id; or why not: 'unknown', 'write'
		 */
		public static function deleteFile( array &$appData, string $id ): array|string {

			$ran = false;
			$gone = null;

			$written = \Nino\Filesystem::mutate( $appData, self::INDEX, static function( mixed $state ) use ( $id, &$ran, &$gone ): ?array {

				$ran = true;
				$index = self::parse( $state );

				if( isset( $index['files'][$id] ) === false )
					return null;

				$gone = [ 'id' => $id ] + $index['files'][$id];
				unset( $index['files'][$id] );

				return $index;
			}, [] );

			if( $ran === true && $gone === null )
				return 'unknown';

			if( $written === false || $gone === null )
				return 'write';

			self::_unlink( $appData, $gone );

			return $gone;
		}

		/**
		 *	Remove the bytes of a file and keep the entry: the entry gets the time they went in gone - the name, the size, the sha256, who
		 *	put the file there and when stay - and the bytes are unlinked after that. A file that was removed is answered as it is, with
		 *	its first time; its bytes are looked for again, so that a remove that could not unlink them is finished by the next
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$id
		 *
		 *	@return 	array|string							[ 'file' => as it is now, 'changed' => whether the entry was changed ]; or why not: 'unknown', 'write' (the entry or the bytes could not be written)
		 */
		public static function removeBytes( array &$appData, string $id ): array|string {

			$after = null;
			$changed = false;
			$ran = false;

			$written = \Nino\Filesystem::mutate( $appData, self::INDEX, static function( mixed $state ) use ( $id, &$after, &$changed, &$ran ): ?array {

				$ran = true;
				$index = self::parse( $state );

				if( isset( $index['files'][$id] ) === false )
					return null;

				if( isset( $index['files'][$id]['gone'] ) === false ) {
					$index['files'][$id]['gone'] = gmdate( self::TIME );
					$changed = true;
				}

				$after = [ 'id' => $id ] + $index['files'][$id];

				return $changed === true ? $index : null;
			}, [] );

			if( $ran === true && $after === null )
				return 'unknown';

			if( $changed === true && $written === false || $after === null )
				return 'write';

			self::_unlink( $appData, $after );

			$path = self::bytes( $appData, $id, $after['ext'] );

			return $path !== null && is_file( $path ) === true ? 'write' : [ 'file' => $after, 'changed' => $changed ];
		}

		/**
		 *	How many bytes the files of a group take - the files that were removed take none
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$group
		 *
		 *	@return 	int
		 */
		public static function used( array &$appData, string $group ): int {

			return self::_used( self::read( $appData ), $group );
		}

		/**
		 *	The extensions that may be uploaded: the lines of the setting types - a dot in front and capital letters are
		 *	forgiven - that are an extension the feature has a MIME rule for, each once
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	array										Extensions, in the order of the setting
		 */
		public static function types( array &$appData ): array {

			$types = [];

			foreach( (array) \Nino\Features::setting( $appData, 'files', 'types', [] ) as $line ) {

				$ext = is_string( $line ) === true ? strtolower( ltrim( trim( $line ), '.' ) ) : '';

				if( preg_match( self::EXTENSION, $ext ) === 1 && isset( self::MIME[$ext] ) === true && in_array( $ext, $types, true ) === false )
					$types[] = $ext;
			}

			return $types;
		}

		/**
		 *	The name a browser sent made into the name a file is kept under: no path (a backslash is a separator as
		 *	well), no control character and no format character that turns a name around, at most NAME_LENGTH characters
		 *	with the extension kept whole and in lower case. The extension has to be 1 to 8 letters and digits and the
		 *	name something before it
		 *
		 *	@param		string		$raw
		 *
		 *	@return 	array										[ name, extension ] - both '' for a name that is none
		 */
		public static function cleanName( string $raw ): array {

			$name = str_replace( '\\', '/', $raw );
			$name = substr( $name, strrpos( $name, '/' ) === false ? 0 : strrpos( $name, '/' ) + 1 );
			$name = (string) preg_replace( '/[\x00-\x1f\x7f]+/', '', $name );
			$name = mb_scrub( $name, 'UTF-8' );
			$name = (string) preg_replace( '/[\x{200B}-\x{200F}\x{202A}-\x{202E}\x{2066}-\x{2069}\x{FEFF}]/u', '', $name );
			$name = ltrim( trim( $name ), '.' );

			$dot = strrpos( $name, '.' );
			$ext = $dot === false ? '' : strtolower( substr( $name, $dot + 1 ) );
			$base = $dot === false ? '' : trim( substr( $name, 0, $dot ) );

			if( preg_match( self::EXTENSION, $ext ) !== 1 || $base === '' )
				return [ '', '' ];

			$base = rtrim( mb_substr( $base, 0, self::NAME_LENGTH - 1 - strlen( $ext ), 'UTF-8' ) );

			return [ $base. '.'. $ext, $ext ];
		}

		/**
		 *	The name of a folder as it is kept: no control character, no blank space at its ends
		 *
		 *	@param		string		$name
		 *
		 *	@return 	string
		 */
		public static function cleanFolder( string $name ): string {

			return trim( mb_scrub( (string) preg_replace( '/[\x00-\x1f\x7f]+/', '', $name ), 'UTF-8' ) );
		}

		/**
		 *	Whether the bytes of a file are what its extension says: finfo's MIME type of the file has to be one of the
		 *	types MIME lists for the extension
		 *
		 *	@param		string		$path
		 *	@param		string		$ext
		 *
		 *	@return 	string										The MIME type; '' where the bytes are something else (or finfo cannot say)
		 */
		public static function sniff( string $path, string $ext ): string {

			$mime = @( new \finfo( FILEINFO_MIME_TYPE ) )->file( $path );

			return is_string( $mime ) === true && in_array( $mime, self::MIME[$ext] ?? [], true ) === true ? $mime : '';
		}

		/**
		 *	Whether a file is shown in the browser instead of saved: audio, video, images and pdf. A vector image runs
		 *	scripts of its own when it is opened, so it is saved like the rest
		 *
		 *	@param		string		$mime
		 *
		 *	@return 	bool
		 */
		public static function inline( string $mime ): bool {

			return $mime === 'application/pdf' || ( preg_match( '#^(?:audio|video|image)/#', $mime ) === 1 && $mime !== 'image/svg+xml' );
		}

		/**
		 *	The size of one chunk of an upload, in bytes: the setting chunk, which is in megabytes - and never more than
		 *	php takes in a request, less the room the fields of the request need
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	int
		 */
		public static function chunkBytes( array &$appData ): int {

			return self::clamp( (int) \Nino\Features::setting( $appData, 'files', 'chunk', 4 ), \Nino\Images::limits()['php'] );
		}

		/**
		 *	A size in megabytes made into bytes, held under what php takes in one request
		 *
		 *	@param		int				$megabytes
		 *	@param		int				$php					What php takes in a request, 0 where it sets no limit
		 *
		 *	@return 	int
		 */
		public static function clamp( int $megabytes, int $php ): int {

			$bytes = max( 1, $megabytes ) * self::MEGABYTE;

			return $php > 0 ? min( $bytes, max( self::HEADROOM, $php - self::HEADROOM ) ) : $bytes;
		}

		/**
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	int											The most a file may have, in bytes
		 */
		public static function maxBytes( array &$appData ): int {

			return max( 1, (int) \Nino\Features::setting( $appData, 'files', 'maxSize', 500 ) ) * self::MEGABYTE;
		}

		/**
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	int											The most a group may hold, in bytes - 0 for no limit
		 */
		public static function quotaBytes( array &$appData ): int {

			return max( 0, (int) \Nino\Features::setting( $appData, 'files', 'quota', 0 ) ) * self::MEGABYTE;
		}

		/**
		 *	A size in the largest unit that leaves a number of one digit or more: 1.5 MB, 120 MB, 512 KB
		 *
		 *	@param		int				$bytes
		 *	@param		array 		$words				The units - b, kb, mb and gb - and the separator of the decimals
		 *
		 *	@return 	string
		 */
		public static function size( int $bytes, array $words ): string {

			$separator = (string) ( $words['separator'] ?? '.' );

			foreach( [ 'gb' => 1073741824, 'mb' => 1048576, 'kb' => 1024 ] as $unit => $factor )
				if( $bytes >= $factor ) {

					$value = $bytes / $factor;
					$text = $value < 10 ? number_format( round( $value, 1 ), 1, $separator, '' ) : (string) (int) round( $value );

					if( str_ends_with( $text, $separator. '0' ) === true )
						$text = substr( $text, 0, -strlen( $separator ) - 1 );

					return $text. ' '. (string) ( $words[$unit] ?? '' );
				}

			return $bytes. ' '. (string) ( $words['b'] ?? '' );
		}

		/**
		 *	Remove the uploads nobody has touched for STALE seconds: the parts that are older. The next upload that starts
		 *	does it, so that nothing runs on a timer
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	int											How many parts went
		 */
		public static function sweep( array &$appData ): int {

			$gone = 0;

			foreach( glob( \Nino\Filesystem::path( $appData, self::PARTS ). '/*.part' ) ?: [] as $part )
				if( is_file( $part ) === true && (int) @filemtime( $part ) < time() - self::STALE && @unlink( $part ) === true )
					$gone++;

			// The length kept with a part goes with the part (the part went above, or with its upload), and what was left without one is not kept
			foreach( glob( \Nino\Filesystem::path( $appData, self::PARTS ). '/*.chunk' ) ?: [] as $kept )
				if( is_file( substr( $kept, 0, -6 ). '.part' ) === false )
					@unlink( $kept );

			// The reservation of an upload goes with it too - an upload that was begun a moment ago has no part yet, so a reservation is let be for ten minutes
			foreach( glob( \Nino\Filesystem::path( $appData, self::PARTS ). '/*.owner' ) ?: [] as $kept )
				if( is_file( substr( $kept, 0, -6 ). '.part' ) === false && (int) @filemtime( $kept ) < time() - 600 )
					@unlink( $kept );

			return $gone;
		}

		/**
		 *	Begin an upload: its file of reservation is made inside the write of the index, which holds one upload at a time, so that the uploads that are begun at
		 *	once cannot all fit - the declared size of every unfinished upload of the group counts against its quota - and the upload belongs to the folder and the
		 *	account that began it
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		$folder				The folder, with its id
		 *	@param		string		$upload				The id of the new upload
		 *	@param		array 		$by						kind and id of who uploads
		 *	@param		int				$size					The declared size of the whole file
		 *	@param		int				$quota				The most bytes a group may hold, 0 for no limit
		 *
		 *	@return 	string										'' when it is made; 'folder' (gone), 'quota' or 'write'
		 */
		public static function reserve( array &$appData, array $folder, string $upload, array $by, int $size, int $quota ): string {

			$refused = '';
			$ran = false;

			// An upload id is what the protocol makes it, whoever calls: nothing else is a name of a file here
			if( preg_match( Upload::UPLOAD, $upload ) !== 1 )
				return 'write';

			\Nino\Filesystem::mutate( $appData, self::INDEX, static function( mixed $state ) use ( $appData, $folder, $upload, $by, $size, $quota, &$refused, &$ran ): ?array {

				$ran = true;
				$index = self::parse( $state );
				$known = $index['folders'][ (string) $folder['id'] ] ?? null;

				if( $known === null ) {
					$refused = 'folder';
					return null;
				}

				// An account holds one unfinished upload in a folder: the ones it began before are what it gives up for this one, and they hold nothing against it
				$older = [];

				foreach( glob( \Nino\Filesystem::path( $appData, self::PARTS ). '/*.owner' ) ?: [] as $kept ) {

					$each = basename( $kept, '.owner' );
					$was = preg_match( Upload::UPLOAD, $each ) === 1 ? self::reservation( $appData, $each ) : null;

					if( $was !== null && $was['folder'] === (string) $folder['id'] && $was['kind'] === (string) $by['kind'] && $was['id'] === (string) $by['id'] )
						$older[] = $each;
				}

				// The owner of the website goes first: what members have begun holds nothing against an upload of the owner - the quota is held against what lies there when a file is put together
				if( $by['kind'] !== 'owner' && $quota > 0 && self::_used( $index, $known['group'] ) + self::_pending( $appData, $index, $known['group'], $older ) + $size > $quota ) {
					$refused = 'quota';
					return null;
				}

				if( $by['kind'] === 'owner' && $quota > 0 && self::_used( $index, $known['group'] ) + $size > $quota ) {
					$refused = 'quota';
					return null;
				}

				foreach( $older as $each )
					foreach( [ '.owner', '.part', '.chunk' ] as $kind )
						@unlink( \Nino\Filesystem::path( $appData, self::PARTS ). '/'. $each. $kind );

				$kept = json_encode( [ 'folder' => (string) $folder['id'], 'kind' => (string) $by['kind'], 'id' => (string) $by['id'], 'size' => $size ] );
				$refused = is_string( $kept ) === true && @file_put_contents( \Nino\Filesystem::path( $appData, self::PARTS ). '/'. $upload. '.owner', $kept ) !== false ? '' : 'write';

				return null;
			}, [] );

			return $ran === false ? 'write' : $refused;
		}

		/**
		 *	Who began an upload and for what: what reserve() kept
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$upload
		 *
		 *	@return 	array|null								folder, kind, id and size; null for an upload nobody reserved
		 */
		public static function reservation( array &$appData, string $upload ): ?array {

			if( preg_match( Upload::UPLOAD, $upload ) !== 1 )
				return null;

			$read = @file_get_contents( \Nino\Filesystem::path( $appData, self::PARTS ). '/'. $upload. '.owner' );
			$kept = is_string( $read ) === true ? json_decode( $read, true ) : null;

			if( is_array( $kept ) === false || is_string( $kept['folder'] ?? null ) === false || is_string( $kept['kind'] ?? null ) === false || is_string( $kept['id'] ?? null ) === false || is_int( $kept['size'] ?? null ) === false )
				return null;

			return [ 'folder' => $kept['folder'], 'kind' => $kept['kind'], 'id' => $kept['id'], 'size' => $kept['size'] ];
		}

		/**
		 *	The bytes the unfinished uploads of a group will bring: the declared size of each that was begun within STALE and whose folder is the group's
		 *
		 *	@param		array 		$appData
		 *	@param		array 		$index
		 *	@param		string		$group
		 *	@param		string		$except				The upload that is asked about, which is not counted
		 *
		 *	@return 	int
		 */
		private static function _pending( array $appData, array $index, string $group, string|array $except ): int {

			$bytes = 0;
			$except = (array) $except;

			foreach( glob( \Nino\Filesystem::path( $appData, self::PARTS ). '/*.owner' ) ?: [] as $kept ) {

				$upload = basename( $kept, '.owner' );
				$part = substr( $kept, 0, -6 ). '.part';

				// An upload holds its room while chunks come: the age is the age of its last chunk, and of the reservation where no chunk is there yet
				clearstatcache( true, $part );
				$last = is_file( $part ) === true ? (int) @filemtime( $part ) : (int) @filemtime( $kept );

				if( in_array( $upload, $except, true ) === true || $last < time() - self::IDLE )
					continue;

				$reserved = self::reservation( $appData, $upload );

				if( $reserved !== null && ( $index['folders'][ $reserved['folder'] ]['group'] ?? null ) === $group )
					$bytes += $reserved['size'];
			}

			return $bytes;
		}

		/**
		 *	The bytes the unfinished uploads of a group hold against its quota now: what the panel tells the owner
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$group
		 *
		 *	@return 	int
		 */
		public static function held( array &$appData, string $group ): int {

			return self::_pending( $appData, self::read( $appData ), $group, [] );
		}

		/**
		 *	Remove every upload that is not finished, whatever its age: the parts and the lengths kept with them. A part carries no folder, so the ones of any folder go
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	int											How many parts went
		 */
		public static function dropParts( array &$appData ): int {

			$gone = 0;

			foreach( glob( \Nino\Filesystem::path( $appData, self::PARTS ). '/*.part' ) ?: [] as $part )
				if( is_file( $part ) === true && @unlink( $part ) === true )
					$gone++;

			foreach( [ '/*.chunk', '/*.owner' ] as $kind )
				foreach( glob( \Nino\Filesystem::path( $appData, self::PARTS ). $kind ) ?: [] as $kept )
					if( is_file( $kept ) === true )
						@unlink( $kept );

			return $gone;
		}

		/**
		 *	Change one folder inside the write and answer it as it is after
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$id
		 *	@param		callable	$change				( array &$index ): void
		 *
		 *	@return 	array|string							The folder; 'unknown' or 'write'
		 */
		private static function _folderChange( array &$appData, string $id, callable $change ): array|string {

			$found = false;
			$after = null;

			$written = \Nino\Filesystem::mutate( $appData, self::INDEX, static function( mixed $state ) use ( $id, $change, &$found, &$after ): ?array {

				$index = self::parse( $state );

				if( isset( $index['folders'][$id] ) === false )
					return null;

				$found = true;
				$change( $index );
				$after = [ 'id' => $id ] + $index['folders'][$id];

				return $index;
			}, [] );

			if( $found === false )
				return 'unknown';

			return $written === true && $after !== null ? $after : 'write';
		}

		/**
		 *	@param		array 		$index
		 *	@param		string		$group
		 *
		 *	@return 	int											The bytes of the files in the folders of the group, those that were removed not among them
		 */
		private static function _used( array $index, string $group ): int {

			$used = 0;

			foreach( $index['files'] as $file )
				if( ( $index['folders'][ $file['folder'] ]['group'] ?? null ) === $group && isset( $file['gone'] ) === false )
					$used += $file['size'];

			return $used;
		}

		/**
		 *	Remove the bytes of a file that has left the index
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		$file					With its id
		 *
		 *	@return 	void
		 */
		private static function _unlink( array &$appData, array $file ): void {

			$path = self::bytes( $appData, $file['id'], $file['ext'] );

			if( $path !== null && is_file( $path ) === true )
				@unlink( $path );
		}
	}
}
