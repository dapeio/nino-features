<?php
declare(strict_types=1);
/**
 *	Nino								A compact filesystembased php framework
 *	Modules\Crm						see _nino/Nino/Modules/Modules.php for the
 *											package-level docblock
 *
 *	@package						Dape/Nino
 *	@author							David Perchermeier <mail@dape.io>
 *	@link								https://github.com/dapeio/nino
 */
namespace Nino\Modules {

	/**
	 *	Nino							A compact filesystembased php framework
	 *	Crm								The core of the CRM: the database all of it keeps its data in (/data/crm.sqlite, with the schema of the core in one piece and a schema of its own for
	 *											every package that docks to it), the number sequences, the settings and the hosts the CRM and the workbench are served under, the band at the head of the
	 *											mails and the way mails leave through the outbox of the kernel, and the panel of the workbench that says what is there and what is missing. What the
	 *											packages build on is a list of places where they enter themselves - Db::addSchema(), Settings::add(), Sequences::add(), Status::add(), Hosts::share(),
	 *											Mail::addWords() - and a way to ask whether the core is on, featureOn(): the core names no package, and a package that docks to it begins its init() with
	 *											that question, since a package is initialised whether or not the core is on.
	 *
	 *											init() registers behaviour and writes nothing: the database is opened on first use, never in an init().
	 *
	 *											THE BACKUP. The database is the one file /data/crm.sqlite, in the DELETE journal mode (no file of a log beside it). The core answers '/nino/admin/backup' (callbackBackup()): the backup does not pack
	 *											the file as it lies but a whole copy of it, made with VACUUM INTO through a connection of its own - the state before a write that is open, which carries the application id of the core and has no free
	 *											pages and no file of a log beside it. The copy is looked at before it is handed over: PRAGMA integrity_check, the full one - every index against its table and the unique keys, so that an index with a wrong,
	 *											a missing or a doubled entry is found, which VACUUM INTO carries over as it is -, and its application id. A file that is not there, one with nothing in it and one that is not the core's by its header - another
	 *											database, a text, a directory - is not handed over and not touched, and the backup packs it as it lies; so is a file of the core that was cut off to less than its header of 72 bytes, which has no header to be told
	 *											by: it is packed as it lies, without an error. A file that is the core's and cannot be copied - it cannot be read, a writer holds it longer than the wait (five seconds), it is damaged, or its copy is -
	 *											makes the callback throw, and the backup ends with the core named (backups_hook, crm); nothing is left in the folder of the copy, and the safety copy before a restore goes on all the same and holds the files as they lie.
	 *											The callback opens the file for writing, like every connection of the core: a journal that a process that was ended in the middle of a transaction left beside the file (a hot journal) is rolled back by SQLite
	 *											before it reads, so that the file is whole as it was before that transaction and the copy has that state - the one write this callback makes below data/. A writer that holds a transaction open is not waited
	 *											for as long as the transaction has written nothing to the file (its changes are in the cache of its connection until the cache is full or it ends): the copy is the state before it. Where it has written -
	 *											a large transaction, or one at its commit - the callback waits five seconds and throws. Nothing else is made for the copy: no folder below data/, no table, no file of a log.
	 *
	 *											THE FILE IS OPENED ONLY BY ITS CONNECTION. While a process holds a connection to /data/crm.sqlite - and a request of the core does from its first query to its end - nothing of that process opens the file, or its
	 *											-journal, any other way: no file_get_contents, fopen, file, readfile, copy, hash_file, md5_file, sha1_file, SplFileObject, finfo or the like. POSIX locks belong to the process and the file, not to the descriptor
	 *											(fcntl(2)): the close() of ANY descriptor of the process on the file lets go of every lock the process holds on it, those of SQLite included, the write lock of an open transaction among them, and another process then
	 *											writes into the middle of that transaction and the file is damaged - SQLite does not notice, for it keeps the locks it thinks it has in its own memory. This holds for a package too, above all for what it runs inside a
	 *											transaction of the core - the callables of Erasure::add() and Wipe::add(), the schema it enters with Db::addSchema(), the hooks of Settings::add() and Values::addType(): a package asks the core (Db::kindFor(), Db::inspect(),
	 *											Settings::get()) and never opens the file to look at it. Whether a file is the core's is told, for the file a connection is open on, by the device and inode that connection was opened on, and never by reading the file again
	 *
	 *											THE PLACES A PACKAGE ENTERS ITSELF. Every call is made from the init() of the package, after
	 *											\Nino\Modules\Crm::featureOn( $appData, 'crm' ) has said yes. Every call names the package that makes it, the same way: the field package of the description where the call
	 *											takes a description (a spec; Settings::add() a list of them), the option package of the last argument, an array $opts, where it takes plain arguments - and the one place that
	 *											says what a package is is Register::package(): a slug, none of the first words of the core's (Api::reserved()) and the key of a feature that is on. What the package may bring is
	 *											what the kernel says of that feature - its folder and its namespace -, never guessed from the call: files lie below that folder (with a separator at its end: features/Foo is not
	 *											features/FooBar), classes in that namespace - the callables of Erasure::add() and Wipe::add(), and the letters of Icons::add() where they are no text (a text there is always the letters; a method is written as [ class, method ] alone), are public static methods of classes there, written as [ class, method ] with a plain method name or as class::method, and both the class and the method are written in the folder of the package - a method that is inherited from a class in a file outside that folder, comes from a trait in such a file or is the language's own (cases and from of an enum) is refused, as are a closure, a function, self, static, parent, a method name that has a :: in it or begins with two underscores, a method that needs more arguments than the place passes it (a place that calls a method says how many it passes), one that takes an argument as a reference where the place passes a value (&$appData is a reference where the place holds the variable; the places say which), and a class that cannot be loaded - its file does not parse or its code throws when it is loaded; the question is answered no at every asking of the request and the warning names the class and the kind of the throwable, never its message and no path; a file that cannot be compiled - a method declared twice, say - is a fatal error of PHP that nothing catches: Register::owned( array &$appData, mixed $package, mixed $value, ?int $arguments = null, array $reference = [] ): bool; the name of the class is read as text against the namespace, letter for letter, before any class is loaded -, routes are the ones the package entered itself, and the pages of the core (/crm/todo and what lies below it) are no package's. Each call
	 *											answers bool - true where all it was given is entered, false with an E_USER_WARNING (a text that carries no personal data: the register, the field that was refused and, for a class that could not be loaded, its name and the kind of the throwable, never its message) where anything was refused, and then none of the list is
	 *											entered. The same description a second time is as good as the first where the place takes a list or a description (Register::add()); Menu::add() refuses a second entry of the same route or key, while Devices::allow() takes the same page again and refuses a page the package did not enter itself, with a warning. Whether a package is removed - for the steps of an open deletion, the data of a package that is gone and the button that takes them away - is asked in one place:
	 *											Register::gone( array &$appData, string $key, string $folder = '' ): bool, true only where the features directory can be read, the kernel knows no feature of the key and refuses no directory, every entry of the directory that is no regular file (a package is a directory for the kernel; .htaccess, .DS_Store, a README are files) is one the kernel knows and the folder - the name the kernel had for it, Register::directory() - is not there; nothing is taken from a name.
	 *											- Db::addSchema( array &$appData, array $spec ): bool       package, version, tables, file (a php file in the folder of the package): the tables of the package in the file of the CRM, only statements on its own tables
	 *											- Settings::add( array &$appData, array $keys ): bool       the keys of the settings the package owns, each with package, its rule, default and what is done after a write
	 *											- Sequences::add( array &$appData, array $spec ): bool      package, category (a number sequence that is no category of the core), format, start
	 *											- Status::add( array &$appData, array $spec ): bool         package, key, rank, state, fix: a line of the status
	 *											- Hosts::share( array &$appData, array $prefixes, array $exact = [], array $opts = [] ): bool   opts package: paths that are the places of the owner and are answered on the CRM host
	 *											- Mail::addWords( array &$appData, string $root, array $opts = [] ): bool   opts package: the root of the words of the package's mails, /feature/<package>/
	 *											- Api::add( array &$appData, array $actions, array $opts = [] ): bool   actions named <package>/<name>; opts package, files, needs
	 *											- Values::addType( array &$appData, array $spec ): bool     package, type, screen, rank: an element type the endpoint and the values screen serve
	 *											- Erasure::add( array &$appData, array $spec ): bool        package, key, count, rows, gather, finish, reason, answer, perm: what the package holds of a client that goes with its deletion; the journal keeps the package as the owner of the step
	 *											- Wipe::add( array &$appData, array $spec ): bool           package, key, rows, leftover( &$appData, &$done ), count, perm: what the package removes in the test phase
	 *											- Records::addKind( array &$appData, array $spec ): bool    package, kind, ...: a kind of record (evidence) the package writes, with its purposes, parent and owner rules
	 *											- Logbook::addKinds( array &$appData, array $kinds, array $opts = [] ): bool   opts package: the kinds of entry a package writes in the logbook (its key is the source of the entries)
	 *											- Notifications::add( array &$appData, array $spec ): bool  package, key, rank, read, write: a switch of the notifications the package owns
	 *											The places of the surface /crm, entered the same way (a package that has none of them has no surface):
	 *											- Texts::add( array &$appData, string $directory, array $opts = [] ): bool    opts package: the words of the package for the surface, <locale>.php with flat keys (Surface::addTexts() is the same call)
	 *											- Scripts::add( array &$appData, string $name, string $file, array $opts = [] ): bool   opts package: a script of the package, served by the hash of its content, after the scripts of the core; Scripts::addStyle() the same for a style sheet
	 *											- Scripts::addStyle( array &$appData, string $name, string $file, array $opts = [] ): bool   opts package: a style sheet of the package, on the same terms
	 *											- Surface::addTexts( array &$appData, string $directory, array $opts = [] ): bool   opts package: the same call as Texts::add()
	 *											- Menu::add( array &$appData, string $route, string $word, string $key, array $opts = [] ): bool   opts package: an entry of the rail that leads to a page of the package below /crm
	 *											- Icons::add( array &$appData, array $spec ): bool          package, key, prefix, slot, route, letters: the icon of an app of the package
	 *											- Apps::add( array &$appData, array $spec ): bool           package, key, route, scope, manifest, values, icon: an app of the package; its scope and its manifest lie below its own route
	 *											- Data::add( array &$appData, array $source, array $opts = [] ): bool   opts package: what the package adds to the data block of the page, under its key (the key of the package)
	 *											- Devices::allow( array &$appData, string $route, array $opts = [] ): bool   opts package: lets the devices that stay signed in come back to a page the package entered with Menu::add() or Apps::add()
	 *											What a package calls after it is entered: Clients::add(), Projects::add(), Projects::share(), Tasks::add(), Tasks::addFrom(), Logbook::add(), Records::add(), Logbook::find(),
	 *											Records::forThings(), Records::ofProject(), Clients::ofGroup(), Clients::complete(), Api::record(), Surface\Icons::registerIcons() (its class in the namespace of the package that entered the app);
	 *											and the hooks of the manual (callbacks), which answer by returning a value that takes the place of the argument. The file doc/ of the catalogue has no section for these places, so
	 *											this list is the one place that names them all.
	 *
	 *	@package					Dape/Nino
	 */
	class Crm {

		/**	The one permission of the CRM: the panel of the workbench, the surface and the API behind it	*/
		public const string MANAGE_PERM = '/_admin/crm/manage';

		/**
		 *	Register the places the core owns while it is on: the number sequence of the clients, the line of the status, the words of the mails, the band at the head of every mail,
		 *	the hosts and the hook of the backup (callbackBackup()). Nothing is opened or written
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	void
		 */
		public static function init( array &$appData ): void {

			\Nino\Modules\Crm\Sequences::init( $appData );
			\Nino\Modules\Crm\Status::init( $appData );
			\Nino\Modules\Crm\Mail::init( $appData );
			\Nino\Modules\Crm\Mail::registerHead( $appData );

			// The names the installation answers under: the app (the workbench), the CRM and its start page
			\Nino\Modules\Crm\Hosts::init( $appData );

			// The element types of the core (the projects and their phases), the vetoes that keep them whole, and the endpoint with the actions of the core
			\Nino\Modules\Crm\Values::init( $appData );
			\Nino\Modules\Crm\Projects::init( $appData );
			\Nino\Modules\Crm\Api::init( $appData );

			// The page /crm with its login, its devices that stay signed in and its files
			\Nino\Modules\Crm\Surface::init( $appData );

			// What the backup packs of the database is a copy that is whole, made by the core when the backup asks for it
			\Nino\Callbacks::registerCallback( $appData, '/nino/admin/backup', [ self::class, 'callbackBackup' ] );
		}

		/**
		 *	Hand the backup a whole copy of the database: the file is copied with VACUUM INTO into the folder of the run, under the name it has below data/, the copy is checked (integrity_check and the application id, Db::backup()), and the backup packs
		 *	the copy where the live file stands. What is not the core's is not handed over and not touched - a file that is not there, one that has nothing in it, another database, a text, a directory, and a file of the core that is cut
		 *	off to less than its header of 72 bytes: the backup packs it as it lies, as it would without this callback, and says nothing. What is the core's and cannot be copied - it cannot be read, a writer that has written to the file holds it for longer than the wait,
		 *	it is damaged or its copy is - throws, and the backup ends with the core named: nothing falls back to the live file. The file is opened for writing, so that a hot journal beside it is rolled back first (Db::backup()). Nothing else is made here: no folder
		 *	below data/, no table, no row, and the connection of the request is not used
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$args				(reference) dataDir: the live data directory, staging: the folder of this run for this feature
		 *
		 *	@return 	void
		 *
		 *	@throws		\RuntimeException					The arguments are not what the backup gives
		 *	@throws		\PDOException							The database of the core cannot be copied
		 */
		public static function callbackBackup( array &$appData, array &$args ): void {

			if( is_string( $args['dataDir'] ?? null ) === false || is_string( $args['staging'] ?? null ) === false || $args['dataDir'] === '' || $args['staging'] === '' )
				throw new \RuntimeException( 'the backup did not say where the data is and where the copy goes' );

			$name = basename( \Nino\Modules\Crm\Db::FILE );

			\Nino\Modules\Crm\Db::backup( $args['dataDir']. '/'. $name, $args['staging']. '/'. $name, \Nino\Modules\Crm\Db::held( $appData ) );
		}

		/**
		 *	Raise the database to the schemas of this version. A first activation calls nothing, which is why the schema is made on the first use of the connection and not here; an update
		 *	that changes the schema of the core is raised here, in a transaction, and a file that cannot be raised refuses the update: the kernel asks again at the next activation. Idempotent
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$fromVersion	The version the project had
		 *
		 *	@return 	bool											false refuses the update
		 */
		public static function upgrade( array &$appData, string $fromVersion ): bool {

			try {
				\Nino\Modules\Crm\Db::ensure( $appData );
			}
			catch( \PDOException $e ) {
				trigger_error( 'Crm: the database could not be raised to the schema of this version', E_USER_WARNING );

				return false;
			}

			return true;
		}

		/**
		 *	Whether a feature is installed, active and without problems - the one answer a package asks to know whether the core, or another package, is on
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$key					The feature's key
		 *
		 *	@return 	bool
		 */
		public static function featureOn( array &$appData, string $key ): bool {

			$feature = \Nino\Features::get( $appData, $key );

			return $feature !== null && $feature['active'] === true && $feature['problems'] === [];
		}

		/**
		 *	The name of the site: a text of the project, read the way the kernel reads a text (the fills in it are resolved, as Settings::ownerName() reads the name of the owner) and then put in as text - markup, a fill or a shortcode that is left in it is words, and nothing in the answer is read again. The name the apps of the surface and the portal call the site by
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	string										Not escaped; '' where the site has none
		 */
		public static function siteName( array &$appData ): string {

			return \Nino\Modules\Crm\Settings::name( html_entity_decode( (string) \Nino\Html::resolveTextfill( $appData, '/project/company/general/name' ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
		}

		/**
		 *	The panels this module answers; the workbench reads them while the feature is on
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	array											The classes of the panels
		 */
		public static function adminPanels( array &$appData ): array {

			return [ \Nino\Modules\Crm\Admin::class ];
		}
	}
}
