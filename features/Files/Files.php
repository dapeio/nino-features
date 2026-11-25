<?php
declare(strict_types=1);
/**
 *	Nino								A compact filesystembased php framework
 *	Modules\\Files						see _nino/Nino/Modules/Modules.php for the
 *											package-level docblock
 *
 *	@package						Dape/Nino
 *	@author							David Perchermeier <mail@dape.io>
 *	@link								https://github.com/dapeio/nino
 */
namespace Nino\Modules {

	/**
	 *	Nino							A compact filesystembased php framework
	 *	Files							Folders and files for the groups of the Members feature. A folder
	 *										belongs to one group; the owner makes folders and uploads files
	 *										in the panel, and may mark one folder of a group as the one its
	 *										members upload into. A member sees the folders of their group and
	 *										the files that are visible to them, and downloads them through
	 *										GET /.files/<id>, which answers the file the kernel streams
	 *										(\Nino\Http, "Answering a File": the length, the ranges and the
	 *										pieces are the kernel's, so that a browser can play a long audio
	 *										file); a stranger and a member of another group are answered 404
	 *										alike, so nothing tells that an id exists - and the owner, whose
	 *										workbench session holds the permission of the panel, is answered with
	 *										any file that has bytes, the hidden ones and those of any group
	 *										included, so that the panel links its files to the same route. A
	 *										member uploads through POST /.files/upload, in chunks (see
	 *										Files\Upload), or with one plain form post when there is no script.
	 *
	 *										The data is Files\Store's: the index /data/files.php and the bytes
	 *										in /data/files/ - the manifest names the index only, so the daily backup
	 *										carries the index and the operator saves the bytes with the server,
	 *										and a restore marks an entry whose bytes are not there as removed (callbackRestore()).
	 *										A file can be removed from the server and stay in the index: the
	 *										entry keeps its name, size and sender and carries gone, the time the bytes went; its address
	 *										answers 410 to those who may have the file, and nothing offers it for download.
	 *
	 *										Two components put the file browser and the upload form on any page
	 *										the Builder composes (componentFilesBrowser() and componentFilesUpload()),
	 *										and the panel in Admin/Admin.php manages everything.
	 *
	 *										What other features may call - the interface this feature declares:
	 *										folders(), files(), file(), path(), allowed(), url(), version(),
	 *										grouped(), disposition(), page(), notifyOwner() and notifyMembers(); the owner's
	 *										operations, which every one of them asks the permission of the panel for itself
	 *										(overview(), createFolder(), renameFolder(), markFolder(), deleteFolder(), upload(),
	 *										change(), notify(), deleteFile() and removeFile(), which the panel's actions are made of);
	 *										and the class Files\Store, the data layer, whose methods do not ask - the caller has
	 *										decided who may. The callbacks it fires are '/files/upload' (a member's upload), '/files/add' (the
	 *										owner's) and '/files/delete', each with the entry, '/files/granted',
	 *										which allowed() fires when its own rule says no for a visible file and which
	 *										lets a module give a member read access to that file, and '/files/allowed',
	 *										which allowed() fires when it would say yes and which can withhold the file.
	 *										It listens to '/members/removed': the uploads of an account that is deleted stay in
	 *										their folder, and their sender has no name any more.
	 *
	 *	@package					Dape/Nino
	 *	@author						David Perchermeier <mail@dape.io>
	 *	@link							https://github.com/dapeio/nino
	 */
	class Files {

		// Where this feature's own templates are, as \Nino\Filesystem resolves them
		public const string TEMPLATES = '/features/Files/templates';

		// The permission of the panel, which the owner's operations ask for as well
		public const string MANAGE_PERM = '/_admin/files/manage';

		// The longest page the mail about new files may link
		public const int PAGE_MAX = 500;

		// The most files one mail of a notify names
		public const int NOTIFY_MAX = 200;

		// The counter of the chunk requests, \Nino\Feature::count() under this name: the folder /data/.counters/files-tries/.
		// Not 'files' all the same: a counter and the file of the index of one name do not disturb each other, but the kernel names the file in a warning
		// and the operator who finds both has to guess. It is not in the manifest's 'data': a window of an hour is nothing a backup carries
		private const string COUNTER = 'files-tries';

		private const int WINDOW = 3600;
		private const int DEFAULT_ATTEMPTS = 300;
		private const string FEATURE_KEY = 'files';

		// How many upload forms this request has drawn
		private const string FORMS = './files/forms';

		// How many mails the last call of notifyMembers() delivered
		private const string MAILED = './nino/files/mailed';

		// What the form may say besides nothing: the query 'files' is one of these, the refusals by their codes
		private const array STATES = [ 'sent', 'files_folder', 'files_type', 'files_size', 'files_post', 'files_quota', 'files_chunk', 'files_limit', 'files_write' ];

		/**
		 *	Register the two routes this feature owns with their handlers, the merge of a restore, the hook of a deleted
		 *	account, and tell the Jstext module the words the script reads
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	void
		 */
		public static function init( array &$appData ): void {

			// [mail-head] at the head of the mails is the place of a band: the project's own module answers it, and where none does it is empty
			\Nino\Feature::shortcode( $appData, 'mail-head', [ self::class, 'doShortcodeMailHead' ], false );

			$appData['/nino/http/routes']['GET://.files/*']				= [ 'uri' => '/.files/*' ];
			$appData['/nino/http/routes']['POST://.files/upload']	= [ 'uri' => '/.files/upload' ];

			\Nino\Callbacks::registerCallback( $appData, '/nino/http/response/GET://.files/*', [ self::class, 'callbackDownload' ] );
			\Nino\Callbacks::registerCallback( $appData, '/nino/http/response/POST://.files/upload', [ self::class, 'callbackUpload' ] );

			// A restore of the daily backup may bring the index of a day that names files whose bytes are not on the server - see callbackRestore()
			\Nino\Callbacks::registerCallback( $appData, '/nino/admin/restore', [ self::class, 'callbackRestore' ] );

			// The accounts Members deletes: what they uploaded stays, and no longer says who sent it
			\Nino\Callbacks::registerCallback( $appData, '/members/removed', [ self::class, 'callbackRemoved' ] );

			// The words of the upload script: a published key is public, so the two lists are the words the script reads and nothing else
			if( class_exists( '\\Nino\\Modules\\Jstext' ) === true )
				\Nino\Modules\Jstext::publish( $appData, [ '/feature/files/state/', '/feature/files/script/' ] );
		}

		/**
		 *	The shortcode [mail-head], the first line of every mail that has the frame: the place of the band at its head. Another module's answer for it stands (the shortcode runs
		 *	the callbacks registered for it in turn, each given the answer of the one before); where no module answers it the place is empty
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		mixed			$args					The arguments of the shortcode, or the answer of a callback that ran before this one
		 *
		 *	@return 	string
		 */
		public static function doShortcodeMailHead( array &$appData, mixed $args ): string {

			return is_string( $args ) === true ? $args : '';
		}

		// The panel comes and goes with the feature
		public static function adminPanels( array &$appData ): array {

			return [ \Nino\Modules\Files\Admin::class ];
		}

		/**
		 *	The folders of a group, sorted by name
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$group				A slug, as Members writes it
		 *
		 *	@return 	array										A list of folders: id, group, name, upload (whether the members upload into it) and created
		 */
		public static function folders( array &$appData, string $group ): array {

			return \Nino\Modules\Files\Store::folders( $appData, $group );
		}

		/**
		 *	The files of a folder, the newest first - the hidden ones too: whether the current member may have one is
		 *	allowed()'s to say
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$folder				The id of a folder
		 *
		 *	@return 	array										A list of files: id, folder, name, ext, size, mime, sha256, by (kind and id), visible, final (whether the owner marked it as the finished file) and created - and gone, the time the bytes were removed, for a file that was
		 */
		public static function files( array &$appData, string $folder ): array {

			return \Nino\Modules\Files\Store::files( $appData, $folder );
		}

		/**
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$id
		 *
		 *	@return 	array|null								The entry with its id (and gone, for a file whose bytes were removed); null for an id nobody has
		 */
		public static function file( array &$appData, string $id ): ?array {

			return \Nino\Modules\Files\Store::file( $appData, $id );
		}

		/**
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$id
		 *
		 *	@return 	string|null								The absolute path of the bytes; null where there is no such file, it was removed or its bytes are gone
		 */
		public static function path( array &$appData, string $id ): ?string {

			return \Nino\Modules\Files\Store::path( $appData, $id );
		}

		/**
		 *	The rule for the member who is signed in, which the browser component lists by and the features that build on
		 *	this one filter with: a member of the group the folder of the file belongs to, if the file is visible - and
		 *	the member who uploaded the file, whatever it is and wherever it is now. The owner's workbench session is no
		 *	member: nobody who is not signed in as a member is allowed anything here. The route answers the owner by the
		 *	permission of the panel on its own (see callbackDownload()) and leaves this rule as it is.
		 *
		 *	Where the rule says no and the file is visible, the callback '/files/granted' is asked, with the file, the member, whether the request is a download, whether a page lists the
		 *	file (listing) and granted (false): a module that has a reason to let a member read a file of a group the member is not in - the file of a project that was shared with the member's
		 *	client - sets granted to true. It gives READ access and nothing else: the upload, the renaming and the deleting of a file are no part of this rule, and a file that is not
		 *	visible is never asked about. A value other than true is ignored, and with no callback registered nobody is granted anything: the rule stands as it is.
		 *
		 *	Where the rule says yes - or a callback granted the file -, the callback '/files/allowed' is asked before the answer is given, with the file, the member, whether the request is a download and
		 *	allowed (true): a project that has a reason to withhold a file from a member - a condition of its own, before the member has what the group is given - sets
		 *	allowed to false. It never makes a no a yes: a value other than false is ignored, a no of one callback - allowed false, or the false a callback returns as a veto - stays whatever the next sets, and a no of the rule that no callback granted is not asked about
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		$file					An entry, as file() answers it
		 *	@param		bool			$download			Whether the file is asked for to be saved (?download=1) and not shown
		 *	@param		bool			$listing			Whether a page lists the file and does not hand it out: the callback is told (listing), so that a project that withholds the bytes of a file still has the row of it to show
		 *
		 *	@return 	bool
		 */
		public static function allowed( array &$appData, array $file, bool $download = false, bool $listing = false ): bool {

			$member = \Nino\Modules\Members::current( $appData );

			if( $member === null )
				return false;

			if( ( $file['by']['kind'] ?? null ) === 'member' && ( $file['by']['id'] ?? null ) === $member['id'] )
				$allowed = true;
			else if( ( $file['visible'] ?? false ) !== true || $member['group'] === '' )
				$allowed = false;
			else {
				$folder = \Nino\Modules\Files\Store::folder( $appData, (string) ( $file['folder'] ?? '' ) );
				$allowed = $folder !== null && $folder['group'] === $member['group'];
			}

			// A visible file the rule keeps from the member may be granted by a module: read access, asked only where the rule says no
			if( $allowed === false && ( $file['visible'] ?? false ) === true )
				$allowed = self::_granted( $appData, $file, $member, $download, $listing );

			if( $allowed === false )
				return false;

			// Each callback is asked in turn with allowed true, and a no stays a no: what a later callback sets does not take it back
			$asked = [ 'file' => $file, 'member' => $member, 'download' => $download, 'listing' => $listing, 'allowed' => true ];
			$denied = false;

			foreach( \Nino\Callbacks::registered( $appData, '/files/allowed' ) as $callback ) {

				$asked['allowed'] = true;
				$replaced = call_user_func_array( $callback, [ &$appData, &$asked ] );
				$asked = is_array( $replaced ) === true ? $replaced : $asked;
				$denied = $denied === true || $replaced === false || ( $asked['allowed'] ?? true ) === false;
			}

			return $denied === false;
		}

		/**
		 *	Whether a module grants a member read access to a file that the rule of allowed() keeps from them: the callback '/files/granted' is asked in turn, each with granted false,
		 *	and one yes is enough. A value other than true, and a file with no callback registered, is no grant
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		$file					An entry, as file() answers it
		 *	@param		array 		$member				The account that is signed in
		 *	@param		bool			$download			Whether the file is asked for to be saved
		 *	@param		bool			$listing			Whether a page lists the file and does not hand it out
		 *
		 *	@return 	bool
		 */
		private static function _granted( array &$appData, array $file, array $member, bool $download, bool $listing ): bool {

			$granted = false;

			foreach( \Nino\Callbacks::registered( $appData, '/files/granted' ) as $callback ) {

				$asked = [ 'file' => $file, 'member' => $member, 'download' => $download, 'listing' => $listing, 'granted' => false ];
				$replaced = call_user_func_array( $callback, [ &$appData, &$asked ] );
				$asked = is_array( $replaced ) === true ? $replaced : $asked;
				$granted = $granted === true || ( $asked['granted'] ?? false ) === true;
			}

			return $granted;
		}

		/**
		 *	The address a file is downloaded at, from the directory the project is served from: a page links it and an
		 *	audio element plays it
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$id
		 *
		 *	@return 	string										'' for an id that is none
		 */
		public static function url( array &$appData, string $id ): string {

			return preg_match( \Nino\Modules\Files\Store::ID, $id ) === 1 ? \Nino\Filesystem::getDir( $appData ). '/.files/'. $id : '';
		}

		/**
		 *	The title and the version a file name says: Title.NN.ext, the version one to three digits between two dots and the
		 *	extension one to eight letters and digits, in any case. The title is what stands before the version, dots among it
		 *	('Kunde-Titel.final.03.wav' is the title 'Kunde-Titel.final'). A name that does not say it - no version, a version of
		 *	four digits, no extension after it - is a title of its own: the name without its extension. Pure; a name is read as
		 *	it stands and nothing is looked up
		 *
		 *	@param		string		$name					A name as the index keeps it
		 *
		 *	@return 	array											title, version (the digits as written, '' for none) and number (the version as an integer, null for none)
		 */
		public static function version( string $name ): array {

			if( preg_match( '/^(.+)\.([0-9]{1,3})\.[A-Za-z0-9]{1,8}$/D', $name, $found ) === 1 && trim( $found[1] ) !== '' )
				return [ 'title' => $found[1], 'version' => $found[2], 'number' => (int) $found[2] ];

			return [ 'title' => preg_match( '/^(.+)\.[A-Za-z0-9]{1,8}$/D', $name, $found ) === 1 ? $found[1] : $name, 'version' => '', 'number' => null ];
		}

		/**
		 *	Files of one folder as the titles they are versions of: two files belong together when the title version() reads
		 *	from their names is the same, in the case it stands in, and so is their extension (Song.03.wav and Song.03.mp3 are
		 *	two titles). The versions of a title stand from the highest
		 *	number down, the one of the same number that came later first and a file without a version after the numbered ones: it counts as the
		 *	oldest version of its title, so that the version a list shows is never older than a file it leaves out. The titles come in the order of the day of
		 *	that shown version - the first that is not removed from the server, else the first. A file whose name says no version is a title with one version
		 *	that has no number. The files of one call are the
		 *	caller's choice of one folder: nothing is looked up, so a title never spans two folders. Pure
		 *
		 *	@param		array 		$files				A list of files as files() has them
		 *
		 *	@return 	array											A list of titles: title, ext and versions - a list of the files as they were given, each with version and number added
		 */
		public static function grouped( array $files ): array {

			$titles = [];

			foreach( $files as $file ) {

				if( is_array( $file ) === false )
					continue;

				$read = self::version( (string) ( $file['name'] ?? '' ) );
				$ext = strtolower( (string) ( $file['ext'] ?? '' ) );
				$key = $ext. "\0". $read['title'];

				$titles[$key] ??= [ 'title' => $read['title'], 'ext' => $ext, 'versions' => [] ];
				$titles[$key]['versions'][] = $file + [ 'version' => $read['version'], 'number' => $read['number'] ];
			}

			foreach( $titles as $key => $title ) {

				// A stable sort: the files of the same number keep the order they were given in, the newer first among them
				usort( $title['versions'], static fn( array $a, array $b ): int => [ $b['number'] ?? -1, (string) ( $b['created'] ?? '' ) ] <=> [ $a['number'] ?? -1, (string) ( $a['created'] ?? '' ) ] );
				$titles[$key] = $title;
			}

			// The title whose shown version - the highest number on the server - is the newest first; of two of the same time the one that was listed first
			$order = array_keys( $titles );
			$newest = static function( string $key ) use ( $titles ): string {

				foreach( $titles[$key]['versions'] as $file )
					if( isset( $file['gone'] ) === false )
						return (string) ( $file['created'] ?? '' );

				return (string) ( $titles[$key]['versions'][0]['created'] ?? '' );
			};

			usort( $order, static fn( string $a, string $b ): int => strcmp( $newest( $b ), $newest( $a ) ) );

			return array_map( static fn( string $key ): array => $titles[$key], $order );
		}

		/**
		 *	The value of the header Content-Disposition for a file: shown (inline) or saved (attachment), with the name
		 *	twice - the plain one for a browser that knows no better, cut down to letters, digits and a few signs, and the
		 *	one of RFC 5987 with the whole UTF-8 name percent-encoded
		 *
		 *	@param		string		$name					The name a file is kept under
		 *	@param		bool			$inline
		 *
		 *	@return 	string
		 */
		public static function disposition( string $name, bool $inline ): string {

			$plain = trim( (string) preg_replace( '/[^A-Za-z0-9._ -]+/', '_', $name ) );

			return ( $inline === true ? 'inline' : 'attachment' ). '; filename="'. ( $plain === '' ? 'file' : $plain ). '"; filename*=UTF-8\'\''. rawurlencode( $name );
		}

		/**
		 *	GET /.files/<id>: answer a file. The segment after /.files/ is an id of 12 hex characters or the answer is
		 *	the 404; the file is named for the kernel to stream (it is the absolute path of the bytes this feature
		 *	resolved itself, never a path the request said) with the type of the index, shown for audio, video, images
		 *	and pdf and saved for the rest - and always saved with ?download=1 - under the cleaned name, kept out of
		 *	every cache but the browser's own, and with a Content-Security-Policy that lets the bytes run nothing (a pdf that is shown: sandbox; the rest: no script, and the own picture, sound or film of the address - which a media page loads from the origin of the site, hence allow-same-origin without allow-scripts). It is answered to a member by the rule of allowed() and to the workbench
		 *	session of the owner by the permission of the panel (\Nino\Auth::checkPermission(), which a parent of it
		 *	such as '/_admin/*' or '/*' holds as well): any file that has bytes, a hidden one and one of any group
		 *	included. A file that was removed from the server is answered 410 with a plain sentence to whoever may have it
		 *	(allowed(), or the permission) - nobody else is told that it ever was. All the rest is the same 404 with a plain
		 *	body - a visitor, a member of another group, an account at the workbench without the permission, an id nobody has
		 *	and a file whose bytes are gone
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$request			(reference) Current server request
		 *
		 *	@return 	void
		 */
		public static function callbackDownload( array &$appData, array &$request ): void {

			$uri = (string) ( $request['/nino/http/request']['uri'] ?? '' );
			$id = str_starts_with( $uri, '/.files/' ) === true ? substr( $uri, 8 ) : '';
			$file = $id !== '' ? \Nino\Modules\Files\Store::file( $appData, $id ) : null;
			$query = $request['/nino/http/request']['query'] ?? [];
			$saved = is_array( $query ) === true && ( $query['download'] ?? null ) === '1';
			$may = $file !== null && ( self::allowed( $appData, $file, $saved ) === true || self::_may( $appData ) === true );
			$path = $may === true ? \Nino\Modules\Files\Store::path( $appData, $id ) : null;

			if( $may === true && isset( $file['gone'] ) === true ) {

				$request['/nino/http/response']['statusCode']								= 410;
				$request['/nino/http/response']['body']										= trim( (string) \Nino\Html::resolveTextfill( $appData, '/feature/files/download/removed' ) );
				$request['/nino/http/response']['header']['Content-Type']		= 'text/plain; charset=utf-8';
				$request['/nino/http/response']['header']['Cache-Control']	= 'no-store';

				return;
			}

			if( $file === null || $path === null ) {

				$request['/nino/http/response']['statusCode']								= 404;
				$request['/nino/http/response']['body']										= 'Not Found';
				$request['/nino/http/response']['header']['Content-Type']		= 'text/plain; charset=utf-8';
				$request['/nino/http/response']['header']['Cache-Control']	= 'no-store';

				return;
			}

			$request['/nino/http/response']['file']															= $path;
			$request['/nino/http/response']['header']['Content-Type']						= $file['mime'] !== '' ? $file['mime'] : 'application/octet-stream';
			$request['/nino/http/response']['header']['Content-Disposition']		= self::disposition( $file['name'], $saved === false && \Nino\Modules\Files\Store::inline( $file['mime'] ) === true );
			$request['/nino/http/response']['header']['Cache-Control']					= 'private, no-store';
			$request['/nino/http/response']['header']['X-Content-Type-Options']	= 'nosniff';

			// What a browser may do with the bytes: a pdf that is shown is shown in a sandbox, so that nothing in it - a script of its own among it - runs as this site; anything else is let run nothing at all
			$request['/nino/http/response']['header']['Content-Security-Policy']	= $file['mime'] === 'application/pdf' && $saved === false ? 'sandbox' : "default-src 'none'; img-src 'self'; media-src 'self'; style-src 'unsafe-inline'; sandbox allow-same-origin";
		}

		/**
		 *	POST /.files/upload: one chunk of a member's upload, with the csrf token of the form (a post the csrf guard
		 *	refused is left as the kernel answers it, a 403). The visitor is counted first (the setting attempts, per
		 *	hour), whoever they are; then the member has to be signed in and the folder the one their group uploads
		 *	into - a visitor, a member of another group and a folder that is not the upload folder are the same
		 *	files_folder, a 404. The rest is Files\Upload's. The answer is json: the upload id of a chunk that is taken,
		 *	or the id, the name and the size of the file of the last chunk - and, with that, the callback '/files/upload'
		 *	and, after the answer, the mail to the owner.
		 *
		 *	A form post with no script is the same request with one chunk that is the whole file and no name or size of
		 *	its own: the file carries them. It is answered with a redirect (303) to the page it was posted from, with
		 *	?files=sent, or the code of the refusal
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$request			(reference) Current server request
		 *
		 *	@return 	void
		 */
		public static function callbackUpload( array &$appData, array &$request ): void {

			if( \Nino\Form::blocked( $request ) === true )
				return;

			$plain = array_key_exists( 'name', $_POST ) === false;
			$return = $plain === true ? self::_returnPath( self::_posted( 'return' ) ) : '';
			$attempts = (int) \Nino\Features::setting( $appData, self::FEATURE_KEY, 'attempts', self::DEFAULT_ATTEMPTS );

			// Counted before anything is looked at: a visitor who is no member is counted as well
			if( \Nino\Feature::count( $appData, self::COUNTER, 'visitor|'. \Nino\Http::clientBucket( \Nino\Http::getClientIp( $appData ) ), $attempts, self::WINDOW ) === false ) {
				self::_refuse( $appData, $request, $return, [ 429, 'too many requests', 'files_limit', [], '' ] );
				return;
			}

			$member = \Nino\Modules\Members::current( $appData );
			$folder = \Nino\Modules\Files\Store::folder( $appData, self::_posted( 'folder' ) );

			if( $member === null || $folder === null || $member['group'] === '' || $folder['group'] !== $member['group'] || $folder['upload'] !== true ) {
				self::_refuse( $appData, $request, $return, [ 404, 'there is no such folder', 'files_folder', [], 'folder' ] );
				return;
			}

			$file = $_FILES['chunk'] ?? null;
			$post = [ 'name' => self::_posted( 'name' ), 'size' => self::_posted( 'size' ), 'upload' => self::_posted( 'upload' ), 'index' => self::_posted( 'index' ), 'count' => self::_posted( 'count' ) ];

			if( $plain === true ) {

				$post = [ 'name' => is_array( $file ) === true && is_string( $file['name'] ?? null ) === true ? $file['name'] : '',
					'size' => is_array( $file ) === true && is_int( $file['size'] ?? null ) === true ? $file['size'] : 0, 'upload' => '', 'index' => 0, 'count' => 1 ];

				// One post is one chunk: a file longer than a chunk is for the script to cut - and a file php refused for its size
				// (upload_max_filesize, or the field of the form) comes with no bytes and no size, which is no chunk that did not arrive
				$refused = is_array( $file ) === true && in_array( $file['error'] ?? null, [ UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE ], true ) === true;

				if( $refused === true || $post['size'] > \Nino\Modules\Files\Store::chunkBytes( $appData ) ) {
					self::_refuse( $appData, $request, $return, [ 400, 'the file is larger than one post takes', 'files_post', [], 'size' ] );
					return;
				}
			}

			$result = \Nino\Modules\Files\Upload::receive( $appData, $folder, $post, $file, [ 'kind' => 'member', 'id' => $member['id'] ], true );

			if( isset( $result['fail'] ) === true ) {
				self::_refuse( $appData, $request, $return, $result['fail'] );
				return;
			}

			if( isset( $result['file'] ) === true ) {

				$entry = $result['file'];

				\Nino\Callbacks::doCallbacks( $appData, '/files/upload', $entry );

				// The mail to the owner waits in the outbox of the kernel, which delivers it after the answer: the member who uploaded has no business waiting for a transport,
				// and the upload is in whatever becomes of the mail
				if( \Nino\Features::setting( $appData, self::FEATURE_KEY, 'notifyOwner', false ) === true )
					self::notifyOwner( $appData, $entry, $folder, $member );

				if( $plain === true ) {
					\Nino\Http::redirect( $appData, $request, $return. '?files=sent', 303 );
					return;
				}

				\Nino\Http::ok( $request, [ 'id' => $entry['id'], 'name' => $entry['name'], 'size' => $entry['size'] ] );
				return;
			}

			\Nino\Http::ok( $request, [ 'upload' => $result['part']['upload'], 'index' => $result['part']['index'] ] );
		}

		/**
		 *	The account Members deleted: what it uploaded stays in its folder - the files are the folder's - and the sender of each is
		 *	nobody any more: the entry says it was a member's and no longer which. The write is one mutation of the index; nothing is
		 *	told to anybody, since no file changed hands
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$account			(reference) What '/members/removed' hands over: id, email and group of the account
		 *
		 *	@return 	void
		 */
		public static function callbackRemoved( array &$appData, array &$account ): void {

			$id = is_string( $account['id'] ?? null ) === true ? $account['id'] : '';

			if( $id === '' )
				return;

			$ran = false;
			$changed = false;

			$written = \Nino\Filesystem::mutate( $appData, \Nino\Modules\Files\Store::INDEX, static function( mixed $state ) use ( $id, &$ran, &$changed ): ?array {

				$ran = true;
				$changed = false;
				$index = \Nino\Modules\Files\Store::parse( $state );

				foreach( $index['files'] as $key => $file )
					if( $file['by']['kind'] === 'member' && $file['by']['id'] === $id ) {
						$index['files'][$key]['by']['id'] = '';
						$changed = true;
					}

				return $changed === true ? $index : null;
			}, [] );

			// A write that did not work leaves the id of a deleted account in the index: said, without the address of the account
			if( $written === false && ( $ran === false || $changed === true ) )
				trigger_error( 'Files: the uploads of a deleted account could not be made nameless, the index was not written', E_USER_WARNING );
		}

		/**
		 *	[files-browser] - for a member who is signed in, the folders of their group, each with the files that are
		 *	visible to them (allowed()): the name as a link to the file, its size, the day it was put there and - where the
		 *	attribute downloads is on - a link that saves it. A row carries the id and the MIME type of its file, for the
		 *	features and the scripts that build on it. Nothing for a visitor
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			$args					The resolved arguments (see \Nino\Components::dispatch())
		 *	@param		string		$content			What stands between its tags (nothing: it is a leaf)
		 *	@param		array			$context			Where the call stands
		 *
		 *	@return 	string
		 */
		public static function componentFilesBrowser( array &$appData, array $args, string $content, array $context ): string {

			$member = \Nino\Modules\Members::current( $appData );

			if( $member === null )
				return '';

			$words = self::_words( $appData );
			$folders = '';

			foreach( $member['group'] === '' ? [] : \Nino\Modules\Files\Store::folders( $appData, $member['group'] ) as $folder ) {

				$rows = '';
				$count = 0;

				foreach( \Nino\Modules\Files\Store::files( $appData, $folder['id'] ) as $file ) {

					if( self::allowed( $appData, $file ) === false || isset( $file['gone'] ) === true )
						continue;

					$count++;
					$url = \Nino\Components::escape( self::url( $appData, $file['id'] ) );

					$rows .= self::_draw( $appData, 'files-row', [
						'[[id]]'				=> $file['id'],
						'[[mime]]'			=> \Nino\Components::escape( $file['mime'] ),
						'[[name]]'			=> \Nino\Components::escape( $file['name'] ),
						'[[size]]'			=> \Nino\Components::escape( \Nino\Modules\Files\Store::size( $file['size'], $words ) ),
						'[[created]]'		=> \Nino\Components::escape( $file['created'] ),
						'[[date]]'			=> \Nino\Components::escape( substr( $file['created'], 0, 10 ) ),
						'[[url]]'				=> $url,
						'[[download]]'	=> ( $args['downloads'] ?? '1' ) === '1' ? self::_draw( $appData, 'files-download', [ '[[url]]' => $url ] ) : '',
					] );
				}

				$folders .= self::_draw( $appData, 'files-folder', [
					'[[id]]'		=> $folder['id'],
					'[[name]]'	=> \Nino\Components::escape( $folder['name'] ),
					'[[count]]'	=> (string) $count,
					'[[rows]]'	=> $rows !== '' ? $rows : self::_draw( $appData, 'files-empty', [] ),
				] );
			}

			return \Nino\Components::fill( $appData, 'files-browser', $args, $content, [ '[[folders]]' => $folders !== '' ? $folders : self::_draw( $appData, 'files-none', [] ) ] );
		}

		/**
		 *	[files-upload] - for a member whose group has an upload folder, the form that uploads into it: a file input,
		 *	the size of the chunks (data-files-chunk) and of the largest file (data-files-max) for the script, the
		 *	hidden folder, the csrf token and the page the form stands on, and a state line when the query says one. It
		 *	is a form that posts to /.files/upload and works without the script, for one file at a time. Nothing for a
		 *	visitor, and nothing for a group without an upload folder
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			$args					The resolved arguments
		 *	@param		string		$content			What stands between its tags (nothing: it is a leaf)
		 *	@param		array			$context			Where the call stands
		 *
		 *	@return 	string
		 */
		public static function componentFilesUpload( array &$appData, array $args, string $content, array $context ): string {

			$member = \Nino\Modules\Members::current( $appData );

			if( $member === null || $member['group'] === '' )
				return '';

			$target = null;

			foreach( \Nino\Modules\Files\Store::folders( $appData, $member['group'] ) as $folder )
				if( $folder['upload'] === true ) {
					$target = $folder;
					break;
				}

			if( $target === null )
				return '';

			$words = self::_words( $appData );
			$state = self::_state( $appData );
			$request = \Nino\Http::getRequest( $appData );

			$id = 'files-upload-'. ( $appData[self::FORMS] = (int) ( $appData[self::FORMS] ?? 0 ) + 1 );

			return \Nino\Components::fill( $appData, 'files-upload', $args, $content, [
				'[[multiple]]'	=> ( $args['multiple'] ?? '1' ) === '1' ? '1' : '0',
				'[[id]]'				=> $id,
				// The field is invalid where the form says a refusal, and what describes it - the limits and, where there is one, the sentence of the state - is named by ids
				'[[invalid]]'		=> $state !== '' && $state !== 'sent' ? 'true' : 'false',
				'[[described]]'	=> $id. '-size '. $id. '-types'. ( $state === '' ? '' : ' '. $id. '-state' ),
				'[[action]]'		=> \Nino\Components::escape( rtrim( \Nino\Filesystem::getDir( $appData ), '/' ). '/.files/upload' ),
				'[[csrf]]'			=> \Nino\Components::escape( \Nino\Csrf::getToken( $appData ) ),
				'[[return]]'		=> \Nino\Components::escape( $request === false ? '' : (string) ( $request['/nino/http/request']['uri'] ?? '' ) ),
				'[[folder]]'		=> $target['id'],
				'[[chunk]]'			=> (string) \Nino\Modules\Files\Store::chunkBytes( $appData ),
				'[[max]]'				=> (string) \Nino\Modules\Files\Store::maxBytes( $appData ),
				'[[size]]'			=> \Nino\Components::escape( \Nino\Modules\Files\Store::size( \Nino\Modules\Files\Store::maxBytes( $appData ), $words ) ),
				'[[types]]'			=> \Nino\Components::escape( implode( ', ', \Nino\Modules\Files\Store::types( $appData ) ) ),
				'[[state]]'			=> $state === '' ? '' : self::_draw( $appData, 'files-state', [ '[[id]]' => $id, '[[text]]' => \Nino\Components::escape( $state === 'post' ? sprintf( (string) \Nino\Html::resolveTextfill( $appData, '/feature/files/state/post' ), \Nino\Modules\Files\Store::size( \Nino\Modules\Files\Store::chunkBytes( $appData ), $words ) ) : (string) \Nino\Html::resolveTextfill( $appData, '/feature/files/state/'. $state ) ) ] ),
			] );
		}

		/**
		 *	Tell the owner of a member's upload, where the setting notifyOwner is on: one mail to the contact address
		 *	of the website, with the name of the file, its folder and who put it there, kept in the outbox of the kernel
		 *	(\Nino\Mail::queue(), key 'files/upload-<id>'), which delivers it after the answer and tries again where the
		 *	transport does not answer. A site without an address, or without a contact address that is one, sends none and
		 *	says so in a warning that names neither; a mail the outbox refuses (invalid, size, full, conflict, ratelimited,
		 *	unwritable) is a warning too, naming the file by its id and the reason and never an address - the upload is in
		 *	whatever becomes of the mail
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		$file					The entry, with its id
		 *	@param		array 		$folder				Its folder
		 *	@param		array 		$member				The account that uploaded it
		 *
		 *	@return 	bool										Whether the mail is in the outbox
		 */
		public static function notifyOwner( array &$appData, array $file, array $folder, array $member ): bool {

			if( \Nino\Features::setting( $appData, self::FEATURE_KEY, 'notifyOwner', false ) !== true )
				return false;

			$to = trim( (string) \Nino\Html::resolveTextfill( $appData, '/project/company/contact/email' ) );
			$link = \Nino\Html::absoluteUrl( $appData, '/_admin'. \Nino\AdminAddress::of( 'files', [ $folder['id'] ] ) );

			if( filter_var( $to, FILTER_VALIDATE_EMAIL ) === false || $link === '' ) {
				trigger_error( 'Files: no mail was sent to the owner, the site has no contact address or no address of its own', E_USER_WARNING );
				return false;
			}

			$mail = self::_render( $appData, 'mail-upload', 'upload', static function( array $words ) use ( $file, $folder, $member, $link ): array {

				return [
					'[[file]]'		=> \Nino\Components::escape( $file['name'] ),
					'[[size]]'		=> \Nino\Components::escape( \Nino\Modules\Files\Store::size( $file['size'], $words ) ),
					'[[folder]]'	=> \Nino\Components::escape( $folder['name'] ),
					'[[who]]'			=> \Nino\Components::escape( $member['name'] !== '' ? $member['name'] : $member['email'] ),
					'[[link]]'		=> \Nino\Components::escape( $link ),
				];
			} );

			// One mail for one file, however often the request comes: the key is the feature's name and the id of the file
			$id = \Nino\Mail::queue( $appData, [ 'to' => $to, 'subject' => $mail['subject'], 'body' => $mail['body'], 'replyTo' => '' ], \Nino\Mail::CLIENT, 'files/upload-'. $file['id'] );

			if( $id !== '' )
				return true;

			// The upload is in; the mail is a message and no part of the answer: said in the log - by the id of the file and the reason, never an address - and not an error
			trigger_error( 'Files: the mail to the owner about the file '. $file['id']. ' was not kept for delivery: '. \Nino\Mail::queueRefused( $appData ), E_USER_WARNING );

			return false;
		}

		/**
		 *	Whether a text is a page the mail about new files may link: a path of this site - a single slash first, no blank or control character, no colon before the next slash,
		 *	no backslash after the first slash and no endpoint of a module ('/.') - of at most PAGE_MAX characters. The query is part of it
		 *
		 *	@param		string		$page
		 *
		 *	@return 	bool
		 */
		public static function page( string $page ): bool {

			return strlen( $page ) <= self::PAGE_MAX && str_starts_with( $page, '/' ) === true && str_starts_with( $page, '//' ) === false && str_starts_with( $page, '/\\' ) === false
				&& str_starts_with( $page, '/.' ) === false && preg_match( '/[\s\x00-\x1f\x7f]/', $page ) !== 1 && preg_match( '#^/[^/]*:#', $page ) !== 1;
		}

		/**
		 *	Tell a group of files that are there for them, where the setting notifyMembers is on or the owner chose so for this action: one mail to each
		 *	active member of the folder's group naming the files - all the files of one panel request in one mail - and
		 *	linking the page members land on after they sign in - or the page the caller names (see page()). The mails go out in one call of the kernel's mail
		 *	cap, which counts a call and not a mail. Mails that are not all delivered, or that the cap refuses, are a
		 *	warning that names the files by their ids and never an address
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		$folder				The folder, with its id
		 *	@param		array 		$files				The entries that are visible now, each with its id
		 *	@param		bool|null	$notify				The owner's choice for this action: true mails the group whatever the setting says, false mails nobody, null (not asked) is the setting
		 *	@param		string|null	$page				The page the mail links, a path of this site as page() accepts it; null, or a text it does not accept, is the page members land on
		 *
		 *	@return 	bool										Whether every mail was delivered - true where there was none to send: the setting off or the choice false, no file, nobody in the group. The number delivered is in './nino/files/mailed'
		 */
		public static function notifyMembers( array &$appData, array $folder, array $files, ?bool $notify = null, ?string $page = null ): bool {

			// How many mails were delivered, for the panel's answer to say whether the members were told: none until some are
			$appData[self::MAILED] = 0;

			if( ( $notify ?? \Nino\Features::setting( $appData, self::FEATURE_KEY, 'notifyMembers', false ) ) !== true || $files === [] )
				return true;

			$recipients = array_values( array_filter( \Nino\Modules\Members::accounts( $appData ),
				static fn( array $account ): bool => $account['group'] === $folder['group'] && $account['active'] === true ) );

			if( $recipients === [] )
				return true;

			$link = \Nino\Html::absoluteUrl( $appData, $page !== null && self::page( $page ) === true ? $page : (string) \Nino\Features::setting( $appData, 'members', 'start', '/' ) );

			if( $link === '' ) {
				trigger_error( 'Files: no mail was sent to the group, the site has no address of its own', E_USER_WARNING );
				return false;
			}

			$replyTo = trim( (string) \Nino\Html::resolveTextfill( $appData, '/project/company/contact/email' ) );
			$replyTo = filter_var( $replyTo, FILTER_VALIDATE_EMAIL ) === false ? '' : $replyTo;
			$mails = [];

			foreach( $recipients as $account ) {

				$mail = self::_render( $appData, 'mail-new', 'new', function( array $words ) use ( &$appData, $folder, $files, $link, $account ): array {

					$items = '';

					foreach( $files as $file )
						$items .= strtr( \Nino\Feature::template( $appData, self::TEMPLATES, 'mail-item' ), [
							'[[file]]' => \Nino\Components::escape( $file['name'] ),
							'[[size]]' => \Nino\Components::escape( \Nino\Modules\Files\Store::size( $file['size'], $words ) ),
						] );

					return [
						'[[name]]'		=> \Nino\Components::escape( $account['name'] !== '' ? $account['name'] : $account['email'] ),
						'[[folder]]'	=> \Nino\Components::escape( $folder['name'] ),
						'[[link]]'		=> \Nino\Components::escape( $link ),
						'[[files]]'		=> $items,
					];
				}, $account['id'] );

				$mails[] = [ 'to' => $account['email'], 'subject' => $mail['subject'], 'body' => $mail['body'], 'replyTo' => $replyTo ];
			}

			if( \Nino\Mail::sendAll( $appData, $mails ) === true ) {
				$appData[self::MAILED] = count( $mails );
				return true;
			}

			trigger_error( 'Files: the mails to the group about the files '. implode( ', ', array_column( $files, 'id' ) ). ( \Nino\Mail::rateLimited( $appData ) === true ? ' were refused by the cap of mails an hour' : ' were not all delivered' ), E_USER_WARNING );

			return false;
		}

		/**
		 *	What the panel draws: the groups, the folders - those of one group, or of every group - with their number of files and the bytes
		 *	they hold, the files of the folders that are asked for - each with the address it is downloaded at, url() - and the limits the
		 *	uploads run into. The files are those of the folder $folder where it names one, else those of every folder of $group, else
		 *	none. A group is listed when a member has it and when a folder has it: a folder of a group that has no member any more can
		 *	still be seen and deleted. Asks the permission of the panel
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$group				A slug that is one of the groups; any other is no group
		 *	@param		string		$folder				The id of a folder; any other text is none
		 *
		 *	@return 	array|string							groups, group, folders, files, types, notify and limits; or 'denied'
		 */
		public static function overview( array &$appData, string $group = '', string $folder = '' ): array|string {

			if( self::_may( $appData ) === false )
				return 'denied';

			$groups = self::_groups( $appData );
			$group = in_array( $group, $groups, true ) === true ? $group : '';
			$open = preg_match( \Nino\Modules\Files\Store::ID, $folder ) === 1 ? \Nino\Modules\Files\Store::folder( $appData, $folder ) : null;
			$index = \Nino\Modules\Files\Store::read( $appData );
			$counts = array_count_values( array_column( $index['files'], 'folder' ) );
			$sizes = [];
			$folders = [];
			$files = [];
			$labels = [];

			foreach( $index['files'] as $each )
				if( isset( $each['gone'] ) === false )
					$sizes[$each['folder']] = ( $sizes[$each['folder']] ?? 0 ) + $each['size'];

			foreach( $index['folders'] as $id => $each ) {

				if( $group !== '' && $each['group'] !== $group )
					continue;

				$folders[] = [ 'id' => (string) $id, 'key' => (string) $id ] + $each + [ 'count' => (int) ( $counts[$id] ?? 0 ), 'bytes' => (int) ( $sizes[$id] ?? 0 ) ];
			}

			usort( $folders, static fn( array $a, array $b ): int => [ $a['group'], mb_strtolower( $a['name'] ), $a['id'] ] <=> [ $b['group'], mb_strtolower( $b['name'] ), $b['id'] ] );

			foreach( $folders as $each ) {

				if( $open !== null ? $each['id'] !== $open['id'] : $group === '' )
					continue;

				foreach( \Nino\Modules\Files\Store::files( $appData, $each['id'] ) as $file ) {

					$who = $file['by']['id'];

					if( $file['by']['kind'] === 'member' && isset( $labels[$who] ) === false ) {
						$account = $who === '' ? null : \Nino\Modules\Members::account( $appData, $who );
						$labels[$who] = $account === null ? '' : ( $account['name'] !== '' ? $account['name'] : $account['email'] );
					}

					$files[] = array_merge( $file, [ 'key' => $file['id'], 'by' => $file['by'] + [ 'label' => $file['by']['kind'] === 'member' ? $labels[$who] : '' ], 'url' => self::url( $appData, $file['id'] ) ] );
				}
			}

			// The folder that is open is in the answer even where the group filter would leave it out
			$used = $open !== null ? \Nino\Modules\Files\Store::used( $appData, $open['group'] ) : ( $group === '' ? 0 : \Nino\Modules\Files\Store::used( $appData, $group ) );

			$held = $open !== null ? \Nino\Modules\Files\Store::held( $appData, $open['group'] ) : ( $group === '' ? 0 : \Nino\Modules\Files\Store::held( $appData, $group ) );

			return [
				'groups'	=> $groups,
				'members'	=> \Nino\Modules\Members::groups( $appData ),
				'group'		=> $group,
				'folders'	=> $folders,
				'files'		=> $files,
				'types'		=> \Nino\Modules\Files\Store::types( $appData ),
				'notify'	=> \Nino\Features::setting( $appData, self::FEATURE_KEY, 'notifyMembers', false ) === true,
				'limits'	=> [
					'chunk'		=> \Nino\Modules\Files\Store::chunkBytes( $appData ),
					'max'			=> \Nino\Modules\Files\Store::maxBytes( $appData ),
					'php'			=> \Nino\Images::limits()['php'],
					'quota'		=> \Nino\Modules\Files\Store::quotaBytes( $appData ),
					'used'		=> $used,
					'held'		=> $held,
				],
			];
		}

		/**
		 *	Add a folder to a group of the Members feature. A group the members do not have, a name that is empty and one
		 *	that is too long are refused with their codes. Asks the permission of the panel
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$group				One of the groups Members::groups() names
		 *	@param		string		$name
		 *
		 *	@return 	array|string							The folder with its id; or why not: 'denied', 'group', 'empty', 'long', 'write'
		 */
		public static function createFolder( array &$appData, string $group, string $name ): array|string {

			if( self::_may( $appData ) === false )
				return 'denied';

			if( in_array( $group, \Nino\Modules\Members::groups( $appData ), true ) === false )
				return 'group';

			return \Nino\Modules\Files\Store::addFolder( $appData, $group, $name );
		}

		/**
		 *	Rename a folder. Asks the permission of the panel
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$id						The id of the folder
		 *	@param		string		$name
		 *
		 *	@return 	array|string							The folder as it is now; or why not: 'denied', 'unknown', 'empty', 'long', 'write'
		 */
		public static function renameFolder( array &$appData, string $id, string $name ): array|string {

			if( self::_may( $appData ) === false )
				return 'denied';

			return \Nino\Modules\Files\Store::renameFolder( $appData, preg_match( \Nino\Modules\Files\Store::ID, $id ) === 1 ? $id : '', $name );
		}

		/**
		 *	Mark a folder as the one the members of its group upload into - the folder that had the mark loses it - or,
		 *	with $on false, take the mark off, and the group has none. Asks the permission of the panel
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$id						The id of the folder
		 *	@param		bool			$on
		 *
		 *	@return 	array|string							The folder as it is now; or why not: 'denied', 'unknown', 'write'
		 */
		public static function markFolder( array &$appData, string $id, bool $on = true ): array|string {

			if( self::_may( $appData ) === false )
				return 'denied';

			return \Nino\Modules\Files\Store::markUpload( $appData, preg_match( \Nino\Modules\Files\Store::ID, $id ) === 1 ? $id : '', $on );
		}

		/**
		 *	Delete a folder and its files, the bytes with them; the callback '/files/delete' is told of each file. Asks the permission of the panel
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$id						The id of the folder
		 *
		 *	@return 	array|string							deleted, the number of files that went with it; or why not: 'denied', 'unknown', 'write'
		 */
		public static function deleteFolder( array &$appData, string $id ): array|string {

			if( self::_may( $appData ) === false )
				return 'denied';

			$gone = \Nino\Modules\Files\Store::deleteFolder( $appData, preg_match( \Nino\Modules\Files\Store::ID, $id ) === 1 ? $id : '' );

			if( is_string( $gone ) === true )
				return $gone;

			foreach( $gone['files'] as $file )
				\Nino\Callbacks::doCallbacks( $appData, '/files/delete', $file );

			return [ 'deleted' => count( $gone['files'] ) ];
		}

		/**
		 *	One chunk of the owner's upload, with the fields of the route of the members' - name, size, upload, index and count - and the
		 *	bytes as the file part of php ($_FILES['chunk']). The owner uploads into any folder. The last chunk puts the file in: the callback
		 *	'/files/add' is told of it and, where it is visible and the setting notifyMembers is on - or $notify says true; false mails nobody -
		 *	the group is mailed, the page the mail links being $page where it is a path page() accepts. Asks the permission of the panel
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$folder				The id of the folder
		 *	@param		array 		$post					The fields: name, size, upload, index and count
		 *	@param		mixed			$file					The file part of the request - whatever is there
		 *	@param		bool			$visible			Whether the file is shown to the members once it is in
		 *	@param		bool|null	$notify				See notifyMembers()
		 *	@param		string|null	$page					See notifyMembers()
		 *
		 *	@return 	array|string							fail (status, error, code, params and field, as Upload::receive() has them), or part (upload, index and count) for a chunk that is taken, or file (the entry) and mailed (the number of mails delivered) for the last; 'denied'
		 */
		public static function upload( array &$appData, string $folder, array $post, mixed $file, bool $visible = true, ?bool $notify = null, ?string $page = null ): array|string {

			if( self::_may( $appData ) === false )
				return 'denied';

			$target = preg_match( \Nino\Modules\Files\Store::ID, $folder ) === 1 ? \Nino\Modules\Files\Store::folder( $appData, $folder ) : null;

			if( $target === null )
				return [ 'fail' => [ 404, 'there is no such folder', 'files_folder', [], 'folder' ] ];

			$result = \Nino\Modules\Files\Upload::receive( $appData, $target, $post, $file, [ 'kind' => 'owner', 'id' => '' ], $visible );

			if( isset( $result['file'] ) === false )
				return $result;

			$entry = $result['file'];

			\Nino\Callbacks::doCallbacks( $appData, '/files/add', $entry );

			$appData[self::MAILED] = 0;

			if( $entry['visible'] === true )
				self::notifyMembers( $appData, $target, [ $entry ], $notify, $page );

			return [ 'file' => $entry, 'mailed' => (int) $appData[self::MAILED] ];
		}

		/**
		 *	Change a file: its name, whether it is visible to the members, whether the owner marked it as final, and the folder it is in - another folder of the same group. A file that is
		 *	made visible is told to the group where the setting notifyMembers is on or $notify says true (false: not told). Asks the permission of the panel
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$id						The id of the file
		 *	@param		array 		$changes			Any of 'visible' (bool), 'final' (bool), 'folder' (an id) and 'name' (as typed): what is no such value is not changed
		 *	@param		bool|null	$notify				See notifyMembers()
		 *	@param		string|null	$page					See notifyMembers()
		 *
		 *	@return 	array|string							file (as it is now) and mailed; or why not: 'denied', 'unknown', 'name' (none), 'ext' (not the extension of the file), 'version' (a version no more), 'folder', 'write'
		 */
		public static function change( array &$appData, string $id, array $changes, ?bool $notify = null, ?string $page = null ): array|string {

			if( self::_may( $appData ) === false )
				return 'denied';

			$wanted = [];
			$id = preg_match( \Nino\Modules\Files\Store::ID, $id ) === 1 ? $id : '';

			if( is_bool( $changes['visible'] ?? null ) === true )
				$wanted['visible'] = $changes['visible'];

			if( is_bool( $changes['final'] ?? null ) === true )
				$wanted['final'] = $changes['final'];

			if( is_string( $changes['folder'] ?? null ) === true )
				$wanted['folder'] = $changes['folder'];

			// The name goes first: a refusal of it changes nothing else
			$file = \Nino\Modules\Files\Store::file( $appData, $id );

			if( $file === null )
				return 'unknown';

			if( is_string( $changes['name'] ?? null ) === true && $file['name'] !== \Nino\Modules\Files\Store::cleanName( $changes['name'] )[0] ) {

				$renamed = \Nino\Modules\Files\Store::renameFile( $appData, $id, $changes['name'] );

				if( is_string( $renamed ) === true )
					return $renamed;
			}

			$saved = \Nino\Modules\Files\Store::changeFile( $appData, $id, $wanted );

			if( is_string( $saved ) === true )
				return $saved;

			$appData[self::MAILED] = 0;

			if( $saved['before']['visible'] === false && $saved['file']['visible'] === true && isset( $saved['file']['gone'] ) === false ) {

				$folder = \Nino\Modules\Files\Store::folder( $appData, $saved['file']['folder'] );

				if( $folder !== null )
					self::notifyMembers( $appData, $folder, [ $saved['file'] ], $notify, $page );
			}

			return [ 'file' => $saved['file'], 'mailed' => (int) $appData[self::MAILED] ];
		}

		/**
		 *	Tell the group of a folder of the files an upload brought, in one mail: the files of one upload are put in one by one, each with notify false, and this is the one
		 *	call that mails them. Asks the permission of the panel
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		$ids					The ids of the files - at most NOTIFY_MAX, all of them visible, kept and in one folder, or nobody is mailed
		 *	@param		bool|null	$notify				See notifyMembers()
		 *	@param		string|null	$page					See notifyMembers()
		 *
		 *	@return 	array|string							mailed, and told - the line of the activity log; or why not: 'denied', 'unknown'
		 */
		public static function notify( array &$appData, array $ids, ?bool $notify = null, ?string $page = null ): array|string {

			if( self::_may( $appData ) === false )
				return 'denied';

			$files = [];

			if( $ids === [] || count( $ids ) > self::NOTIFY_MAX )
				return 'unknown';

			foreach( $ids as $id ) {

				$file = is_string( $id ) === true && preg_match( \Nino\Modules\Files\Store::ID, $id ) === 1 ? \Nino\Modules\Files\Store::file( $appData, $id ) : null;

				if( $file === null || $file['visible'] !== true || isset( $file['gone'] ) === true || ( $files !== [] && $file['folder'] !== $files[0]['folder'] ) )
					return 'unknown';

				// A file named twice is told once
				if( in_array( $file['id'], array_column( $files, 'id' ), true ) === false )
					$files[] = $file;
			}

			$folder = \Nino\Modules\Files\Store::folder( $appData, (string) $files[0]['folder'] );

			if( $folder === null )
				return 'unknown';

			self::notifyMembers( $appData, $folder, $files, $notify, $page );

			return [ 'mailed' => (int) $appData[self::MAILED], 'told' => 'Told the group of folder '. $folder['id']. ' of '. count( $files ). ' files'. ( $notify === false ? ', members not mailed' : '' ) ];
		}

		/**
		 *	Delete a file, the bytes with it; the callback '/files/delete' is told of it. Asks the permission of the panel
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$id
		 *
		 *	@return 	array|string							The file as it was, with its id; or why not: 'denied', 'unknown', 'write'
		 */
		public static function deleteFile( array &$appData, string $id ): array|string {

			if( self::_may( $appData ) === false )
				return 'denied';

			$gone = \Nino\Modules\Files\Store::deleteFile( $appData, preg_match( \Nino\Modules\Files\Store::ID, $id ) === 1 ? $id : '' );

			if( is_string( $gone ) === true )
				return $gone;

			\Nino\Callbacks::doCallbacks( $appData, '/files/delete', $gone );

			return $gone;
		}

		/**
		 *	Remove the bytes of a file from the server and keep its row: the name, the size, who put it there and when stay, with the time the bytes went
		 *	in gone. A file that is removed already is answered as it is, nothing changed; the comments of other features on it stay. No callback is told:
		 *	the file is still there for them. Asks the permission of the panel
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$id
		 *
		 *	@return 	array|string							file (as it is now) and changed (whether the entry was changed); or why not: 'denied', 'unknown', 'write'
		 */
		public static function removeFile( array &$appData, string $id ): array|string {

			if( self::_may( $appData ) === false )
				return 'denied';

			return \Nino\Modules\Files\Store::removeBytes( $appData, preg_match( \Nino\Modules\Files\Store::ID, $id ) === 1 ? $id : '' );
		}

		/**
		 *	Rewrite the staged index of a backup before /_admin's Restore copies it over the live one. The backup carries the index and
		 *	not the bytes (the operator saves /data/files with the server), so the index of a day may name files whose bytes are not
		 *	on this server: such an entry is kept and marked as removed from the server, with the time of the restore, so that the owner
		 *	sees the row and no member is offered a file that is not there. An entry whose bytes are in the live directory (or, for an
		 *	older backup that has them, in the backup) and as long as the entry says is kept as it is. An entry that was removed from the server stays removed, and so does
		 *	one the live index has removed since the day of the backup. Reached through the '/nino/admin/restore' callback init() registers,
		 *	with the live /data directory and the extracted backup as arguments; a no-op if the backup carries no index. The write is not
		 *	silenced: one that fails is php's own warning, and the staged index stays as it was
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$args				(reference) { dataDir: live /data (read only), staging: extracted backup }
		 *
		 *	@return 	void
		 */
		public static function callbackRestore( array &$appData, array &$args ): void {

			$dataDir = (string) ( $args['dataDir'] ?? '' );
			$staging = (string) ( $args['staging'] ?? '' );
			$staged = $staging. '/data/files.php';

			if( $dataDir === '' || $staging === '' || is_file( $staged ) === false )
				return;

			$index = \Nino\Modules\Files\Store::parse( include $staged );
			$live = is_file( $dataDir. '/files.php' ) === true ? \Nino\Modules\Files\Store::parse( include $dataDir. '/files.php' ) : [ 'files' => [] ];

			foreach( $index['files'] as $id => $file ) {

				// An entry that was removed has no bytes to look for: it stays, as removed - and so does one the live index has removed since the backup
				if( isset( $file['gone'] ) === false && isset( $live['files'][$id]['gone'] ) === true )
					$index['files'][$id]['gone'] = $live['files'][$id]['gone'];
				elseif( isset( $file['gone'] ) === false ) {

					// The bytes under the name of the row, on the server or in an older backup, have to be as long as the row says: a file of another size is not the file of the row
					$there = false;

					foreach( [ $dataDir. '/files/'. $id. '.'. $file['ext'], $staging. '/data/files/'. $id. '.'. $file['ext'] ] as $path )
						if( is_file( $path ) === true && filesize( $path ) === $file['size'] )
							$there = true;

					if( $there === false )
						$index['files'][$id]['gone'] = gmdate( \Nino\Modules\Files\Store::TIME );
				}
			}

			file_put_contents( $staged, '<?php return '. var_export( $index, true ). ';' );
		}

		/**
		 *	Whether the account at the workbench holds the permission of the panel (or one above it, such as '/_admin/*')
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	bool
		 */
		private static function _may( array &$appData ): bool {

			return \Nino\Auth::checkPermission( $appData, self::MANAGE_PERM ) === true;
		}

		/**
		 *	The groups the owner is offered: those the members have and those that have a folder
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	array										Slugs, sorted
		 */
		private static function _groups( array &$appData ): array {

			$groups = \Nino\Modules\Members::groups( $appData );

			foreach( \Nino\Modules\Files\Store::read( $appData )['folders'] as $folder )
				$groups[] = $folder['group'];

			$groups = array_values( array_unique( $groups ) );
			sort( $groups );

			return $groups;
		}

		/**
		 *	A template of the feature's own filled: the tokens first, and the words of the template - every
		 *	/feature/files/... fill of it - as the project has them, each as text. One pass over the template, so
		 *	nothing that is put in is read again
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$name					The template, without .tpl, in TEMPLATES
		 *	@param		array 		$tokens				[ '[[token]]' => finished markup or escaped text ]
		 *
		 *	@return 	string
		 */
		private static function _draw( array &$appData, string $name, array $tokens ): string {

			$text = \Nino\Feature::template( $appData, self::TEMPLATES, $name );

			preg_match_all( '~\[\[(/feature/files/[a-z0-9/-]+)\]\]~', $text, $found );

			foreach( array_unique( $found[1] ) as $key )
				$tokens['[['. $key. ']]'] = \Nino\Components::escape( (string) \Nino\Html::resolveTextfill( $appData, $key ) );

			return strtr( $text, $tokens );
		}

		/**
		 *	The state the form says: what the query 'files' names, one of STATES
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	string										The name of its word below /feature/files/state/ - 'sent', or the code without 'files_'; '' for none
		 */
		private static function _state( array &$appData ): string {

			$request = \Nino\Http::getRequest( $appData );
			$query = $request === false ? null : ( $request['/nino/http/request']['query'] ?? null );
			$asked = is_array( $query ) === true ? ( $query['files'] ?? null ) : null;

			if( is_string( $asked ) === false || in_array( $asked, self::STATES, true ) === false )
				return '';

			return str_starts_with( $asked, 'files_' ) === true ? substr( $asked, 6 ) : $asked;
		}

		/**
		 *	The words of a size: the units and the separator of the decimals, in the language of the page
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	array
		 */
		private static function _words( array &$appData ): array {

			$words = [];

			foreach( [ 'b' => 'byte', 'kb' => 'kilobyte', 'mb' => 'megabyte', 'gb' => 'gigabyte', 'separator' => 'separator' ] as $key => $name )
				$words[$key] = (string) \Nino\Html::resolveTextfill( $appData, '/feature/files/unit/'. $name );

			return $words;
		}

		/**
		 *	A mail rendered in the site's native language - or, for a mail to an account that has a language, in that - whoever asked: the template and the subject are rendered
		 *	first - their words are the keys of the unit - and the values are put in afterwards, in the result, so that
		 *	nothing in them is ever read as a fill or a shortcode (the order \Nino\Form::render() keeps)
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$template			mail-upload or mail-new
		 *	@param		string		$kind					upload or new: the subject is /feature/files/mail/<kind>-subject
		 *	@param		callable	$values				( array $words ): array - token => value, the values escaped, the markup last
		 *	@param		string		$account			The account the mail is for, where it is for one: the language Members::mailLocale() names for it is the language of the mail, else the native one
		 *
		 *	@return 	array										[ 'subject' => ..., 'body' => ... ]
		 */
		private static function _render( array &$appData, string $template, string $kind, callable $values, string $account = '' ): array {

			$locale = \Nino\Locales::getCurrentLocale( $appData );
			$written = $account === '' ? '' : \Nino\Modules\Members::mailLocale( $appData, $account );

			\Nino\Locales::useLocale( $appData, $written !== '' ? $written : \Nino\Locales::getNativeLocale( $appData ) );

			try {
				$body = \Nino\Html::renderHtml( $appData, \Nino\Feature::template( $appData, self::TEMPLATES, $template ) );
				$subject = \Nino\Html::renderHtml( $appData, '[[/feature/files/mail/'. $kind. '-subject]]' );
				$filled = $values( self::_words( $appData ) );
			}
			finally {
				\Nino\Locales::useLocale( $appData, $locale );
			}

			return [ 'subject' => $subject, 'body' => strtr( $body, $filled ) ];
		}

		/**
		 *	Answer a refusal of the upload: the failure as json, or - for the form without script - a redirect to the page
		 *	it was posted from with the code in the query
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$request			(reference) Current server request
		 *	@param		string		$return				The page of a plain form post, '' for a post of the script
		 *	@param		array 		$fail					status, error, code, params and field, as Upload::receive() has them
		 *
		 *	@return 	void
		 */
		private static function _refuse( array &$appData, array &$request, string $return, array $fail ): void {

			if( $return !== '' ) {
				\Nino\Http::redirect( $appData, $request, $return. '?files='. $fail[2], 303 );
				return;
			}

			\Nino\Http::fail( $request, ...$fail );
		}

		/**
		 *	One posted value, as a string - nothing says a post carries strings, and 'folder[]=x' would be an "Array to
		 *	string conversion", a level the runtime treats as fatal
		 *
		 *	@param		string		$name
		 *
		 *	@return 	string
		 */
		private static function _posted( string $name ): string {

			return is_string( $_POST[$name] ?? null ) === true ? $_POST[$name] : '';
		}

		/**
		 *	The page the answer of a plain post goes back to: the posted 'return' without its query, where it is a path
		 *	of this site and no endpoint of a module - the front page for anything else
		 *
		 *	@param		string		$posted
		 *
		 *	@return 	string
		 */
		private static function _returnPath( string $posted ): string {

			$path = substr( $posted, 0, strcspn( $posted, '?#' ) );

			return str_starts_with( $path, '/' ) === true && str_starts_with( $path, '//' ) === false && str_starts_with( $path, '/\\' ) === false && str_starts_with( $path, '/.' ) === false
				&& preg_match( '/[\s\x00-\x1f\x7f]/', $path ) !== 1 && preg_match( '#^/[^/]*:#', $path ) !== 1 ? $path : '/';
		}
	}
}
