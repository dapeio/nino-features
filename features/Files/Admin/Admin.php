<?php
declare(strict_types=1);
/**
 *	Nino								A compact filesystembased php framework
 *	Nino\Modules\Files\Admin		The /_admin panel of the Files feature - see docs/features.md, "The panel"
 *
 *	@package						Dape/Nino
 *	@author							David Perchermeier <mail@dape.io>
 *	@link								https://github.com/dapeio/nino
 */
namespace Nino\Modules\Files {

	/**
	 *	Nino							A compact filesystembased php framework
	 *	Files\Admin				"Files" panel (Dateien): the owner's side of the feature. The folders of the groups as a
	 *										directory - a table, narrowed by a group - with a dialog to make one, rename it, mark it
	 *										as the folder the members of its group upload into and delete it; and a folder as a view
	 *										of its own with its files - name, size, who put it there, the day, whether the members see
	 *										it, a link that downloads it, move to another folder of the group, remove from the
	 *										server, delete - and the owner's upload, in the chunks of the members' (Files\Upload).
	 *										A file can be removed from the server (files/remove): its bytes go, its row stays with
	 *										the day they went - no download, no move, delete still there.
	 *
	 *										The panel is a thin wrapper: every action asks the permission (guardPerm()), reads the
	 *										request and hands the work to the method of \Nino\Modules\Files that has the same
	 *										name as the operation - which asks the permission once more and holds the inputs to
	 *										their rules - and answers with the codes of the failures a person can cause. Admin
	 *										itself is no interface for other features: what they may call is in Files and
	 *										Files\Store. The panel has no action that downloads: it hands every file its address
	 *										(Files::url()) and the screen links it, and the route GET /.files/<id>, which the kernel
	 *										streams, answers the workbench session of the owner by the permission of this panel
	 *										(Files::callbackDownload()).
	 *
	 *	@package					Dape/Nino
	 *	@author						David Perchermeier <mail@dape.io>
	 *	@link							https://github.com/dapeio/nino
	 */
	class Admin {

		public const string MANAGE_PERM = \Nino\Modules\Files::MANAGE_PERM;

		// The id of the file an action made or touched, by action: written by the action, read by log() in the same request
		private static array $_made = [];

		public static function perm(): string {
			return self::MANAGE_PERM;
		}

		public static function actions(): array {
			return [
				'files/list'								=> [ self::class, 'apiList' ],
				'files/folder/add'					=> [ self::class, 'apiFolderAdd' ],
				'files/folder/save'					=> [ self::class, 'apiFolderSave' ],
				'files/folder/delete'				=> [ self::class, 'apiFolderDelete' ],
				'files/folder/upload-target'	=> [ self::class, 'apiFolderTarget' ],
				'files/upload'							=> [ self::class, 'apiUpload' ],
				'files/save'								=> [ self::class, 'apiSave' ],
				'files/notify'							=> [ self::class, 'apiNotify' ],
				'files/delete'							=> [ self::class, 'apiDelete' ],
				'files/remove'							=> [ self::class, 'apiRemove' ],
			];
		}

		// No group is named here: a panel a feature brought always lands in the rail's own "features" group
		public static function nav(): array {
			return [ 'files', '/_admin/files/nav', 55 ];
		}

		public static function icon(): string {
			return '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 20a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.9a2 2 0 0 1-1.69-.9L9.6 3.9A2 2 0 0 0 7.93 3H4a2 2 0 0 0-2 2v13a2 2 0 0 0 2 2Z"/></svg>';
		}

		// The two levels of the pane: the directory of the folders and one folder with its files
		public static function panes(): array {
			return [ 'files-list', 'files-folder' ];
		}

		public static function assets(): array {
			return [ \Nino\Admin\Panels::relative( dirname( __DIR__ ). '/assets/admin.js' ), \Nino\Admin\Panels::relative( dirname( __DIR__ ). '/assets/admin.css' ) ];
		}

		// The panel's own strings, one <locale>.php per interface language
		public static function text(): string {
			return \Nino\Admin\Panels::relative( dirname( __DIR__ ). '/text' );
		}

		/**
		 *	The Dashboard's tile: how many files there are and how much room they take
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	array
		 */
		public static function summary( array &$appData ): array {

			$fills = \Nino\Admin\Admin::textFills( $appData, '/features/Files/text', \Nino\Admin\Admin::sessionLocale( $appData ) );
			$words = [];

			foreach( [ 'b' => 'byte', 'kb' => 'kilobyte', 'mb' => 'megabyte', 'gb' => 'gigabyte', 'separator' => 'separator' ] as $key => $name )
				$words[$key] = (string) ( $fills['[[/_admin/files/unit/'. $name. ']]'] ?? '' );

			$files = Store::read( $appData )['files'];
			$kept = array_filter( $files, static fn( array $file ): bool => isset( $file['gone'] ) === false );

			return [ 'value' => count( $files ). ' ('. Store::size( (int) array_sum( array_column( $kept, 'size' ) ), $words ). ')', 'label' => '/_admin/files/label/count' ];
		}

		/**
		 *	The activity-log line of a completed action: the id of the folder or the file where there is one, never the
		 *	address or the name of a member. An upload says so once, when its last chunk is in - not for every chunk
		 *
		 *	@param		string		$action
		 *	@param		array 		$data					What the action was posted
		 *
		 *	@return 	string
		 */
		public static function log( string $action, array $data ): string {

			$id = self::_id( $data );
			$of = $id === '' ? '' : ' '. $id;

			return match( $action ) {
				'files/folder/add'					=> 'Added folder'. ( self::$_made[$action] ?? '' ),
				'files/folder/save'					=> 'Renamed folder'. $of,
				'files/folder/delete'				=> 'Deleted folder'. $of,
				'files/folder/upload-target'	=> ( ( $data['on'] ?? true ) === false ? 'Took the upload mark off folder' : 'Marked as upload folder the folder' ). $of,
				'files/upload'							=> ( self::$_made[$action] ?? '' ) === '' ? '' : 'Uploaded file '. self::$_made[$action]. ( ( $data['notify'] ?? null ) === false ? ', members not mailed' : '' ),
				'files/save'								=> 'Changed file'. $of. ( ( $data['notify'] ?? null ) === false && ( $data['visible'] ?? null ) === true ? ', members not mailed' : '' ),
				'files/notify'							=> self::$_made[$action] ?? '',
				'files/delete'							=> 'Deleted file'. $of,
				'files/remove'							=> ( self::$_made[$action] ?? '' ) === '' ? '' : 'Removed file '. self::$_made[$action]. ' from the server',
				default											=> '',
			};
		}

		/**
		 *	What the screen draws: the groups, the folders of one group - or of every group - with their number of files, the
		 *	files of the folder that is open, each with the address it is downloaded at, and the limits the uploads run into
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$request			(reference) Current server request
		 *
		 *	@return 	void
		 */
		public static function apiList( array &$appData, array &$request ): void {

			if( \Nino\Admin\Admin::guardPerm( $appData, $request, self::MANAGE_PERM ) === false )
				return;

			$data = \Nino\Admin\Admin::postData();
			$overview = \Nino\Modules\Files::overview( $appData, \Nino\Admin\Admin::postString( $data, 'group' ), self::_id( $data, 'folder' ) );

			if( is_string( $overview ) === true )
				self::_failed( $request, $overview );
			else
				\Nino\Http::ok( $request, $overview );
		}

		/**
		 *	Add a folder to a group of the Members feature. A group the members do not have, a name that is empty and one
		 *	that is too long are refused with their codes, the field they were posted in beside them
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$request			(reference) Current server request
		 *
		 *	@return 	void
		 */
		public static function apiFolderAdd( array &$appData, array &$request ): void {

			if( \Nino\Admin\Admin::guardPerm( $appData, $request, self::MANAGE_PERM ) === false )
				return;

			$data = \Nino\Admin\Admin::postData();
			$folder = \Nino\Modules\Files::createFolder( $appData, \Nino\Admin\Admin::postString( $data, 'group' ), \Nino\Admin\Admin::postString( $data, 'name' ) );

			if( is_string( $folder ) === true ) {
				self::_failed( $request, $folder );
				return;
			}

			self::$_made['files/folder/add'] = ' '. $folder['id'];

			\Nino\Http::ok( $request, [ 'folder' => $folder ] );
		}

		/**
		 *	Rename a folder
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$request			(reference) Current server request
		 *
		 *	@return 	void
		 */
		public static function apiFolderSave( array &$appData, array &$request ): void {

			if( \Nino\Admin\Admin::guardPerm( $appData, $request, self::MANAGE_PERM ) === false )
				return;

			$data = \Nino\Admin\Admin::postData();
			$folder = \Nino\Modules\Files::renameFolder( $appData, self::_id( $data ), \Nino\Admin\Admin::postString( $data, 'name' ) );

			if( is_string( $folder ) === true ) {
				self::_failed( $request, $folder );
				return;
			}

			\Nino\Http::ok( $request, [ 'folder' => $folder ] );
		}

		/**
		 *	Delete a folder and its files, the bytes with them; the callback '/files/delete' is told of each file
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$request			(reference) Current server request
		 *
		 *	@return 	void
		 */
		public static function apiFolderDelete( array &$appData, array &$request ): void {

			if( \Nino\Admin\Admin::guardPerm( $appData, $request, self::MANAGE_PERM ) === false )
				return;

			$gone = \Nino\Modules\Files::deleteFolder( $appData, self::_id( \Nino\Admin\Admin::postData() ) );

			if( is_string( $gone ) === true )
				self::_failed( $request, $gone );
			else
				\Nino\Http::ok( $request, $gone );
		}

		/**
		 *	Mark a folder as the one the members of its group upload into - the folder that had the mark loses it - or,
		 *	with on false, take the mark off, and the group has none
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$request			(reference) Current server request
		 *
		 *	@return 	void
		 */
		public static function apiFolderTarget( array &$appData, array &$request ): void {

			if( \Nino\Admin\Admin::guardPerm( $appData, $request, self::MANAGE_PERM ) === false )
				return;

			$data = \Nino\Admin\Admin::postData();
			$folder = \Nino\Modules\Files::markFolder( $appData, self::_id( $data ), ( $data['on'] ?? true ) !== false );

			if( is_string( $folder ) === true )
				self::_failed( $request, $folder );
			else
				\Nino\Http::ok( $request, [ 'folder' => $folder ] );
		}

		/**
		 *	One chunk of the owner's upload, with the fields of the route of the members' - folder, name, size, upload,
		 *	index and count - and the bytes as the file part chunk; and visible, which is true unless it says false, for
		 *	a file that is not shown to the members yet; and notify, a bool that says whether the members are mailed (absent: the setting decides), and page, the path of the page the mail links (absent, or not a path of this site: the page members land on). A part that did not arrive is the shell's refusal
		 *	(Admin::uploadError()). The owner uploads into any folder. The last chunk puts the file in: the callback
		 *	'/files/add' is told of it and, where it is visible and the setting notifyMembers is on - or notify says true; false mails nobody - the group is mailed
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$request			(reference) Current server request
		 *
		 *	@return 	void
		 */
		public static function apiUpload( array &$appData, array &$request ): void {

			if( \Nino\Admin\Admin::guardPerm( $appData, $request, self::MANAGE_PERM ) === false )
				return;

			self::$_made['files/upload'] = '';

			$failure = \Nino\Admin\Admin::uploadError( $_FILES['chunk'] ?? null );

			if( $failure !== null ) {
				\Nino\Http::fail( $request, $failure['status'], $failure['error'], $failure['code'], $failure['params'], 'chunk' );
				return;
			}

			$data = \Nino\Admin\Admin::postData();
			$result = \Nino\Modules\Files::upload( $appData, \Nino\Admin\Admin::postString( $data, 'folder' ), $data, $_FILES['chunk'] ?? null, ( $data['visible'] ?? true ) !== false, self::_notify( $data ), self::_page( $data ) );

			if( is_string( $result ) === true ) {
				self::_failed( $request, $result );
				return;
			}

			if( isset( $result['fail'] ) === true ) {
				\Nino\Http::fail( $request, ...$result['fail'] );
				return;
			}

			if( isset( $result['part'] ) === true ) {
				\Nino\Http::ok( $request, [ 'upload' => $result['part']['upload'], 'index' => $result['part']['index'] ] );
				return;
			}

			self::$_made['files/upload'] = $result['file']['id'];

			\Nino\Http::ok( $request, [ 'id' => $result['file']['id'], 'name' => $result['file']['name'], 'size' => $result['file']['size'], 'mailed' => $result['mailed'] ] );
		}

		/**
		 *	Change a file: whether it is visible to the members, and the folder it is in - another folder of the same
		 *	group. A file that is made visible is told to the group where the setting notifyMembers is on or notify says true (false: not told); page names the page the mail links, as in the upload
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$request			(reference) Current server request
		 *
		 *	@return 	void
		 */
		public static function apiSave( array &$appData, array &$request ): void {

			if( \Nino\Admin\Admin::guardPerm( $appData, $request, self::MANAGE_PERM ) === false )
				return;

			$data = \Nino\Admin\Admin::postData();
			$saved = \Nino\Modules\Files::change( $appData, self::_id( $data ), $data, self::_notify( $data ), self::_page( $data ) );

			if( is_string( $saved ) === true )
				self::_failed( $request, $saved );
			else
				\Nino\Http::ok( $request, $saved );
		}

		/**
		 *	Tell the group of a folder of the files an upload brought, in one mail: the files of one upload are sent one by one, each with notify false, and this is the one
		 *	call that mails them. ids is the list of the ids of the files - all of them visible, kept and in one folder - and page names the page the mail links, as in the upload
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$request			(reference) Current server request
		 *
		 *	@return 	void
		 */
		public static function apiNotify( array &$appData, array &$request ): void {

			if( \Nino\Admin\Admin::guardPerm( $appData, $request, self::MANAGE_PERM ) === false )
				return;

			self::$_made['files/notify'] = '';

			$data = \Nino\Admin\Admin::postData();
			$told = \Nino\Modules\Files::notify( $appData, is_array( $data['ids'] ?? null ) === true ? array_values( $data['ids'] ) : [], self::_notify( $data ), self::_page( $data ) );

			if( is_string( $told ) === true ) {
				self::_failed( $request, $told );
				return;
			}

			self::$_made['files/notify'] = $told['told'];

			\Nino\Http::ok( $request, [ 'mailed' => $told['mailed'] ] );
		}

		/**
		 *	Delete a file, the bytes with it; the callback '/files/delete' is told of it
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$request			(reference) Current server request
		 *
		 *	@return 	void
		 */
		public static function apiDelete( array &$appData, array &$request ): void {

			if( \Nino\Admin\Admin::guardPerm( $appData, $request, self::MANAGE_PERM ) === false )
				return;

			$gone = \Nino\Modules\Files::deleteFile( $appData, self::_id( \Nino\Admin\Admin::postData() ) );

			if( is_string( $gone ) === true )
				self::_failed( $request, $gone );
			else
				\Nino\Http::ok( $request );
		}

		/**
		 *	Remove the bytes of a file from the server and keep its row: the name, the size, who put it there and when stay, with the time the bytes went
		 *	in gone. A file that is removed already is answered as it is, nothing changed; the comments of other features on it stay. No callback is told:
		 *	the file is still there for them
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$request			(reference) Current server request
		 *
		 *	@return 	void
		 */
		public static function apiRemove( array &$appData, array &$request ): void {

			if( \Nino\Admin\Admin::guardPerm( $appData, $request, self::MANAGE_PERM ) === false )
				return;

			self::$_made['files/remove'] = '';

			$removed = \Nino\Modules\Files::removeFile( $appData, self::_id( \Nino\Admin\Admin::postData() ) );

			if( is_string( $removed ) === true ) {
				self::_failed( $request, $removed );
				return;
			}

			self::$_made['files/remove'] = $removed['changed'] === true ? $removed['file']['id'] : '';

			\Nino\Http::ok( $request, [ 'file' => $removed['file'], 'changed' => $removed['changed'] ] );
		}

		/**
		 *	The owner's choice whether the members are mailed for this action
		 *
		 *	@param		array 		$data				The posted payload
		 *
		 *	@return 	bool|null								The field notify where it is a bool; null (the setting decides) where it is absent or anything else
		 */
		private static function _notify( array $data ): ?bool {

			return is_bool( $data['notify'] ?? null ) === true ? $data['notify'] : null;
		}

		/**
		 *	The page the mail to the members links, when the owner's action names one
		 *
		 *	@param		array 		$data				The posted payload
		 *
		 *	@return 	string|null							The field page where it is a path of this site as Files::page() accepts it; null (the page members land on) where it is absent or anything else
		 */
		private static function _page( array $data ): ?string {

			return is_string( $data['page'] ?? null ) === true && \Nino\Modules\Files::page( $data['page'] ) === true ? $data['page'] : null;
		}

		/**
		 *	Answer a refusal of the store: the failures a person can cause carry a code (and its sentence is in both
		 *	languages, see text/), the failure of a write is the server's and has none
		 *
		 *	@param		array 		&$request			(reference) Current server request
		 *	@param		string		$why					What Files answered: denied, empty, long, group, unknown, folder or write
		 *
		 *	@return 	void
		 */
		private static function _failed( array &$request, string $why ): void {

			match( $why ) {
				'denied'	=> \Nino\Http::fail( $request, 403, 'the permission of the panel is missing' ),
				'empty'		=> \Nino\Http::fail( $request, 400, 'the name is empty', 'files_name', [], 'name' ),
				'long'		=> \Nino\Http::fail( $request, 400, 'the name is too long', 'files_long', [ Store::FOLDER_LENGTH ], 'name' ),
				'group'		=> \Nino\Http::fail( $request, 400, 'the members have no such group', 'files_group', [], 'group' ),
				'unknown'	=> \Nino\Http::fail( $request, 404, 'unknown folder or file', 'files_unknown' ),
				'name'		=> \Nino\Http::fail( $request, 400, 'the name is not a name of a file', 'files_filename', [], 'name' ),
				'ext'			=> \Nino\Http::fail( $request, 400, 'the extension of a file stays', 'files_extension', [], 'name' ),
				'version'	=> \Nino\Http::fail( $request, 400, 'a name that says a version keeps saying one', 'files_version', [], 'name' ),
				'folder'	=> \Nino\Http::fail( $request, 400, 'the folder has to be another folder of the same group', 'files_target', [], 'folder' ),
				default		=> \Nino\Http::fail( $request, 500, 'could not write the files' ),
			};
		}

		/**
		 *	The posted id of a folder or a file: 12 hex characters or nothing. Anything else is an id nobody has
		 *
		 *	@param		array 		$data					What the action was posted
		 *	@param		string		$key					The field
		 *
		 *	@return 	string
		 */
		private static function _id( array $data, string $key = 'id' ): string {

			$id = \Nino\Admin\Admin::postString( $data, $key );

			return preg_match( Store::ID, $id ) === 1 ? $id : '';
		}
	}
}
