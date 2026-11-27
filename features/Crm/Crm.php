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
	 *											THE RESTORE. The core answers '/nino/admin/restore' (callbackRestore(), which asks restore()), so that the kernel never writes a copy over the database file: after the callback nothing of the database - the file of the archive or what an open copy leaves beside
	 *											it - is left in the staging, whatever the answer is. The copy of the archive is set to the DELETE mode, which it alone has at that moment, and looked at whole (Db::archived() - the integrity check, the application id of the core, the register of
	 *											the schemas with a row of the core whose version this core can read); one that is damaged, is none of the core's (of no byte, a link or a folder, another application id, no register) or comes from a NEWER version of the core is NOT written: the callback
	 *											throws with a sentence that says why and holds no path, the kernel names the core in its answer (backups_restore_callback), and the live database is as it was. A copy that is good is written into the live database with the online backup of SQLite
	 *											(\SQLite3::backup(), page by page under its locks, so that a process that has the file open sees it through its own locking); the file is in the DELETE mode with no file of a log, and the request lets go of what it read. The same where the live file is held by
	 *											another process longer than the wait (five seconds), or is another application's database or a folder or cannot be read, or ext/sqlite3 is not there: nothing is written, the staging is empty of it, the callback throws, the live
	 *											database stays. A live file that is the core's and DAMAGED but opens (the integrity check finds something) is written the same way, page by page under the locks of SQLite, and looked at through the connection afterwards; where SQLite cannot overwrite it
	 *											(a wrong count of pages in the header, a cut-off last page) nothing is written and the callback throws that the live database is damaged and could not be overwritten, and the owner takes the file away and restores again: that remainder is
	 *											named in the manual. A lock or a busy file is never taken for damage: it is waited for at most five seconds, and the answer is kept. Only a live file that is no database for SQLite at all (bytes that are none, a header that is cut off) is
	 *											REPLACED: the copy is written by SQLite into a new file in the same folder, looked at whole, and takes the place of the old by rename() - the old file is never opened by php -; the new file has the mode, and as root the owner, of the old one
	 *											(0644 where that cannot be told); what the old file keeps beside it (-journal, -wal, -shm) is taken away before the rename. The replacement fails closed: a link or a file that is not an ordinary one, a list of the locks (/proc/locks, as the
	 *											kernel asks it, never by opening) that cannot be read, and a lock that another process holds, asked before the copy and again before the rename, each leave the file as it is and make the callback throw with a sentence of its own. THE WINDOW that is
	 *											left is the time between the last question about the locks and the rename(): a process that takes a lock on a file that is no database in just that time loses it to the rename. A process that ends anywhere in this leaves the old file as it was or
	 *											the new one whole; a new file that was left (crm.sqlite.restore-*) is cleared at the start of every restore, whichever way it goes, and by the backup before it copies, and the status of writing names it while it lies. A limit that stays: a run of the
	 *											test phase interrupted between its check that the row stands and the deletion of the project takes that one project with it. The archive with no copy of the database restores none, and that is no failure.
	 *											While the connection to the live file is open nothing opens the file beside it. What the archive holds of the state of the test phase - the row wiping.run with the step of the core, or none - is the state afterwards. A run of the test phase or a deletion of a client that is after its database step when the database is written back does not go on over it: before
	 *											every project it checks that its row stands, and where it does not, it stops with a warning and no failure. The files a package keeps below data/ are put back by the kernel as files, and a package whose files belong to what the database says asks Crm::restore()
	 *											in its own callback of the restore, in whatever order the kernel asks them, and takes its files out of the staging where the answer is kept.
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
	 *											A package makes no connection of its own to the file - no new PDO, no SQLite3 on /data/crm.sqlite - and reads and writes through the connection of the core (Db::pdo(), Db::row(), Db::rows(), Db::exec()): the core knows the connections it made, and only those, when it asks what the file is (Db::held()). A connection that a package made is one it does not know: the question about the file then opens the file beside it, lets go of the locks that connection holds, and another process writes into its transaction. In a process of a test that holds a second connection of its own to the file, as the suites of the packages do, the core reads the header of the file beside it, and that is harmless only while that connection holds no lock.
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
	 *											- Settings::add( array &$appData, array $keys ): bool       the keys of the settings the package owns, each with package, its rule, default, before (the key it stands in front of in the list: a key of the core, of its own package or of another package - a position, resolved when the list is read: where a key stands does not depend on which package entered itself first, and keys that stand in front of the same key go by their rank, then by the key of their package, then by their own key; an anchor that is not entered, a ring of keys that stand in front of each other: the key goes to the end by the same order, with no refusal) and what is done after a write
	 *											- Sequences::add( array &$appData, array $spec ): bool      package, category (a number sequence that is no category of the core), format, start
	 *											- Status::add( array &$appData, array $spec ): bool         package, key, rank, state, fix: a line of the status
	 *											- Hosts::share( array &$appData, array $prefixes, array $exact = [], array $opts = [] ): bool   opts package: paths that are the places of the owner and are answered on the CRM host
	 *											- Mail::addWords( array &$appData, string $root, array $opts = [] ): bool   opts package: the root of the words of the package's mails, /feature/<package>/
	 *											- Api::add( array &$appData, array $actions, array $opts = [] ): bool   actions named <package>/<name>; opts package, files, needs
	 *											- Values::addType( array &$appData, array $spec ): bool     package, type, screen, rank: an element type the endpoint and the values screen serve
	 *											- Erasure::add( array &$appData, array $spec ): bool        package, key, count, rows, gather, finish, reason, answer, perm: what the package holds of a client that goes with its deletion; the journal keeps the package as the owner of the step
	 *											- Wipe::add( array &$appData, array $spec ): bool           package, key, rows, gather( &$appData ): array, leftover( &$appData, &$done, $gathered ), count, perm: what the package removes in the test phase; gather reads, in the transaction that deletes, what leftover needs to find its things (a list, which leftover gets as its third argument - [] where the package has no gather; it writes nothing); the list is kept in the row 'wiping.run' of the table settings, by the key of the step - data about persons, in the database and its backup - until the leftover of that step has answered true: a press that finds the row finishes the open leftovers with the lists they held, reads and joins nothing, and then begins a new run; a leftover is given what was read before the rows went, it can run days later and must delete nothing that belongs to somebody again, and it must bear that an entry is gone already; a gather that throws or answers no list that can be held stops the run before anything is deleted (the key step:<key> in the answer) and needs a leftover
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
	 *											What a package calls after it is entered: Clients::add() and Projects::add() (both answer 'wiping' while a run of the test phase is not finished, see Wipe::add()), Projects::share(), Tasks::add(), Tasks::addFrom(), Logbook::add(), Records::add(),
	 *											Logbook::find( array &$appData, string $source, string $kind, array $where, int $limit = 50 ): array - the entries of a source and a kind that is the package's own or one of the core's that the core writes for it (project-added, written by Projects::add() under the key of the package that made the project) -,
	 *											Records::forThings(), Records::ofProject(), Records::of( array &$appData, int $clientId, ?string $kind = null, ?string $purpose = null ): array,
	 *											Records::ofClients( array &$appData, array $ids, string $kind ): array - the records of many clients in one question, by the id of the client (whole numbers or their digits as text; the portions of Records::MOST are put together),
	 *											Clients::ofGroup( array &$appData, string $group ): ?array, Clients::group( string $number ): string and Clients::GROUP (the shape of the group of a client, k-0001: group() answers '' for a text that is no number of a client as the core writes it - K- and at least four digits, with nothing around them, no blank, no line end, no other dash -, ofGroup() null for a text that is not of that shape),
	 *											Clients::complete(), Api::record(), Mail::deliver( array &$appData, array $mails, string $about, bool $many = false, string $key = '', string $kind = 'client', ?array &$ids = null ): string|int
	 *											- the mails of the outbox of the kernel, the id of the outbox of each mail in $ids; the kinds CLIENT, SYSTEM, TEST, VOID and OWNER (OWNER only from a signed-in account of the workbench in a POST: the outbox takes no id otherwise, and each mail is a warning) -,
	 *											Hosts::refuse( array &$request, bool $closed = false ): void and Hosts::towards( string $app, array &$request ): void (from a listener of '/nino/http/request', with the argument it was given; towards() refuses, with a warning, a host that is no name and the host the request is on),
	 *											Surface\Icons::registerIcons() (its class in the namespace of the package that entered the app);
	 *											and the hooks of the manual (callbacks), which answer by returning a value that takes the place of the argument. Two of them ask what a package adds to a client: '/crm/client/view' (argument [ 'client' => the client, 'packages' => [] ]; the listener puts
	 *											its fields under packages.<its key> - Clients::VIEW) and '/crm/clients/view' (argument [ 'clients' => the list, 'packages' => [] ]; the listener puts a map of the id of a client to its fields under packages.<its key> - Clients::VIEWS).
	 *											The listener is a method of a class of the package, written as [ class, method ] ( array &$appData, mixed &$argument ) in its folder (Register::owned()), and answers under the key of its own package and under no other: a closure, a function, a
	 *											method of another package and a key that is none of the listener's are left out with a warning. What comes back is read for packages and for nothing else - a package cannot change a field of the core -, and each part is checked and copied value by value
	 *											when it is taken: texts of valid UTF-8, whole numbers, finite floats, booleans, null and lists of them, six levels deep and at most Clients::PART_BYTES bytes serialised - in the card and in each entry of the list alike. What does not fit leaves out the part of that package with a
	 *											warning (never the answer), a listener that throws is left out with a warning and the others are asked, and crm-clients/get and crm-clients/list answer the parts in packages of the client, an object of the parts by key ({} where none). The file doc/ of the catalogue has no section for these places, so
	 *											this list is the one place that names them all.
	 *
	 *											THE CALLS A PACKAGE MAKES OF THE CORE. What the packages that are built on the core call, declared here word for word - the class of the namespace \Nino\Modules\Crm, the method, its signature as php says it, and what it does; a constant is
	 *											given with its value. A test holds each of them by reflection (crm-smoke): a signature or a value that changes is a failure, and so is a line of this list that is no longer what the code says. What is not on this list
	 *											is not for packages - the classes of the core call each other as they like, and a package that calls what is not here is on its own. A package is code of the site, with the rights of the kernel: nothing here is a sandbox, and what the database calls let it write - Db::exec(), Db::pdo() - passes no check of the core:
	 *											- Crm::MANAGE_PERM = '/_admin/crm/manage'
	 *											- Crm::featureOn( array &$appData, string $key ): bool: whether a feature is on - the core, or a package
	 *											- Crm::restore( array &$appData, array &$args ): string: whether the live database is now the one of the archive (restored) or stays as it is (kept), done once for a request, never throws: a package that keeps files below data/ that belong to the database asks it in its own callback of '/nino/admin/restore' and takes its files out of the staging where the answer is kept
	 *											- Crm::siteName( array &$appData ): string: the name of the site
	 *											- Api::ROUTE = '/crm/api'
	 *											- Api::add( array &$appData, array $actions, array $opts = [] ): bool: enter the actions of a package
	 *											- Api::made( array &$appData, string $line ): void: the line of the activity log an action makes of what it did, after it answered 200
	 *											- Api::record( array &$appData, string $actor, string $line ): bool: a line of the activity log in the name of a package
	 *											- Api::id( array &$request, array $data, string $code, string $key = 'id' ): ?int: the id an action was posted, or the answer 400 with the code it is given
	 *											- Api::run( array &$request, callable $run ): void: one action: what throws in it is the answer 500 crm_database or a refusal, never a fault of the page
	 *											- Clients::GROUP = '/^k-[0-9]{4,}$/D'
	 *											- Clients::VIEW = '/crm/client/view'
	 *											- Clients::VIEWS = '/crm/clients/view'
	 *											- Clients::PART_DEPTH = 6
	 *											- Clients::PART_BYTES = 16384
	 *											- Clients::LIST_BYTES = 1048576
	 *											- Clients::get( array &$appData, int $id ): ?array: a client by its id
	 *											- Clients::list( array &$appData ): array: all clients, by number
	 *											- Clients::byNumber( array &$appData, string $number ): ?array: a client by its number
	 *											- Clients::title( array $client, string $fallback = '' ): string: how a client is called
	 *											- Clients::complete( array $values ): bool: whether the master data are complete for an invoice
	 *											- Clients::group( string $number ): string: the group of a number
	 *											- Clients::ofGroup( array &$appData, string $group ): ?array: the client of a group
	 *											- Clients::view( array &$appData, array $client ): array: a client with what the packages add
	 *											- Clients::views( array &$appData, array $clients ): array: the clients with what the packages add
	 *											- Db::addSchema( array &$appData, array $spec ): bool: enter the schema of a package
	 *											- Db::ensure( array &$appData ): int: make or raise the schemas of the file
	 *											- Db::exec( array &$appData, string $sql, array $params = [] ): int: run one statement, prepared: how many rows it changed - a package is code with the rights of the kernel and writes past every check of the core with it: the tables of the core are not the package's, only those it enters with Db::addSchema() are
	 *											- Db::pdo( array &$appData ): PDO: the connection of the request - a package is code with the rights of the kernel and writes past every check of the core with it: the tables of the core are not the package's, only those it enters with Db::addSchema() are
	 *											- Db::row( array &$appData, string $sql, array $params = [] ): ?array: one row or none - a package is code with the rights of the kernel and writes past every check of the core with it: the tables of the core are not the package's, only those it enters with Db::addSchema() are
	 *											- Db::rows( array &$appData, string $sql, array $params = [] ): array: all rows of a question - a package is code with the rights of the kernel and writes past every check of the core with it: the tables of the core are not the package's, only those it enters with Db::addSchema() are
	 *											- Erasure::add( array &$appData, array $spec ): bool: enter what a package holds of a client that goes with it
	 *											- Erasure::peel( callable $run, string $table, string $column, int $client, array $only = [] ): int: delete the rows of a table of a client that name themselves, the rows nobody names first
	 *											- Format::country( string $code, string $locale ): string: the name of a country in a language
	 *											- Format::day( string $date ): string: a day as the language writes it
	 *											- Hosts::share( array &$appData, array $prefixes, array $exact = [], array $opts = [] ): bool: paths that every host answers
	 *											- Hosts::refuse( array &$request, bool $closed = false ): void: turn a request away
	 *											- Hosts::towards( string $app, array &$request ): void: move a request to another host
	 *											- Logbook::add( array &$appData, array $entry ): array|string: write an entry
	 *											- Logbook::addKinds( array &$appData, array $kinds, array $opts = [] ): bool: enter the kinds a package writes without a sentence
	 *											- Logbook::find( array &$appData, string $source, string $kind, array $where, int $limit = 50 ): array: the entries of a source and a kind
	 *											- Logbook::scrub( string $value ): string: a text made fit for a sentence of the logbook
	 *											- Logbook::who( array &$appData ): string: the account that is signed in, or system
	 *											- Mail::deliver( array &$appData, array $mails, string $about, bool $many = false, string $key = '', string $kind = 'client', ?array &$ids = null ): string|int: hand mails to the outbox
	 *											- Mail::addWords( array &$appData, string $root, array $opts = [] ): bool: enter the root of the words of the mails of a package
	 *											- Erasure::HOLDS = '/crm/client/holds'
	 *											- Overview::HOOK = '/crm/overview'
	 *											- Projects::FIGURES = '/crm/project/figures'
	 *											- Projects::HOLDS = '/crm/project/holds'
	 *											- Projects::ID = '/^[A-Za-z0-9][A-Za-z0-9_-]{0,99}$/D'
	 *											- Projects::forget( array &$appData ): void: forget what the request kept of the projects
	 *											- Projects::get( array &$appData, string $id ): ?array: a project
	 *											- Projects::named( string $id, string $title ): string: the name a project is shown by
	 *											- Projects::ofClient( array &$appData, string $clientNumber ): array: the projects of a client
	 *											- Projects::titles( array &$appData ): array: the titles of all projects, by id
	 *											- Records::of( array &$appData, int $clientId, ?string $kind = null, ?string $purpose = null ): array: the records of a client
	 *											- Records::ofClients( array &$appData, array $ids, string $kind ): array: the records of many clients
	 *											- Records::names( array &$appData, string $thing, string $id ): bool: whether a record names a thing
	 *											- Sequences::add( array &$appData, array $spec ): bool: enter a number sequence
	 *											- Sequences::next( array &$appData, PDO $pdo, string $category, int $year, int $start = 1 ): int: take the next number inside a transaction
	 *											- Sequences::upcoming( array &$appData, string $category, int $year, int $start = 1 ): int: the number the next one would get, taken or not
	 *											- Settings::add( array &$appData, array $keys ): bool: enter the settings of a package
	 *											- Settings::all( array &$appData ): array: all settings
	 *											- Settings::get( array &$appData, string $key ): string: one setting
	 *											- Settings::oneOf( string $key, string $value, array $words ): array|string: the rule of a setting that is one of a list of words
	 *											- Settings::start( array &$appData, string $category ): int: the start number of a category
	 *											- Settings::ownerName( array &$appData ): string: the name of the owner
	 *											- Settings::readOnly( array &$appData, string $key ): string: one setting read through a connection that only reads, '' where it cannot be read - what a caller that decides by the answer must not do with an empty one
	 *											- Settings\Api::SAVE = '/crm/settings/save'
	 *											- Status::add( array &$appData, array $spec ): bool: enter a line of the status
	 *											- Surface::addTexts( array &$appData, string $directory, array $opts = [] ): bool: the words of a package for the surface
	 *											- Surface\Data::add( array &$appData, array $source, array $opts = [] ): bool: what a package adds to the data block of the page
	 *											- Surface\Scripts::add( array &$appData, string $name, string $file, array $opts = [] ): bool: a script of a package
	 *											- Surface\Scripts::addStyle( array &$appData, string $name, string $file, array $opts = [] ): bool: a style sheet of a package
	 *											- Surface\Texts::add( array &$appData, string $directory, array $opts = [] ): bool: the words of a package for the surface
	 *											- Surface\Texts::word( array &$appData, string $key ): string: one word in the language of the request
	 *											- Surface\Texts::locale( array &$appData ): string: the language of the request
	 *											- Surface\Texts::all( array &$appData ): array: all words in the language of the request
	 *											- Values::START = '/crm/element/start'
	 *											- Values::addType( array &$appData, array $spec ): bool: enter an element type of the values screen
	 *											- Values::permit( array &$appData, array &$request, string $perm ): bool: whether the account holds the permission - where not, the refusal is the answer (the bridge to Admin::guardPerm()); the first thing an action of a package does
	 *											- Values::posted(): array: the data of the action as the workbench posts them (the bridge to Admin::postData())
	 *											- Values::string( array $data, string $key, string $default = '' ): string: a posted text, with a default (the bridge to Admin::postString())
	 *											- Values::scoped( array &$appData, string $prefix, string $perm ): bool: the bridge to Admin::scoped(): whether the account holds the permission for the fields below a prefix
	 *											- Values::workbench( array &$appData ): bool: make sure the shell of the workbench is loaded; the endpoint does it before every action, a package that calls the three bridges outside an action asks it first
	 *											- Wipe::add( array &$appData, array $spec ): bool: enter what a package removes in the test phase
	 *											- Wipe::peel( callable $run, string $table, string $column ): int: delete the rows of a table that name themselves
	 *											THE NAMES A SCRIPT OF A PACKAGE CALLS under Nino.crm (the script of the surface, after the core's own), held by the script suite like the list above: the points a package registers at and the kit it draws with -
	 *											Nino.crm.kit.make(; Nino.crm.state.own(; Nino.crm.keys.add(; Nino.crm.menu.add(; Nino.crm.pages.add(; Nino.crm.overview.addTile(; Nino.crm.wipe.addCount(; Nino.crm.cards.addTab(; Nino.crm.cards.addFact( ) - and the members of the kit that Nino.crm.kit.make( name ) gives: kit.append(; kit.ask(; kit.badge(; kit.card(; kit.cardList(; kit.clear(; kit.clientField(; kit.dmy(; kit.el(; kit.formList(; kit.formOf(; kit.format.locale(; kit.has(; kit.kv(; kit.link(; kit.menu(; kit.note(; kit.submitter(; kit.t(; kit.toast(; kit.unsaver(; kit.valuesOfForm(; kit.api(; kit.call(; kit.crumb(; kit.search(; kit.sortHead(; kit.keepSearch(; kit.refuseAt(; kit.failed(; kit.errorText(; kit.saver(; kit.reveal(; kit.loading(; kit.loadFailed(; kit.data(; kit.dialog(; kit.head( ). The kit that Nino.crm.kit.make( name ) gives has exactly these members, held against what the core hands over at run time, name for name (THE MEMBERS OF THE KIT): kit.PAGE_SIZE kit.PHONE kit.api kit.append kit.ask kit.badge kit.call kit.card kit.cardBody kit.cardList kit.cardTabs kit.charCount kit.clear kit.clientField kit.clientTitle kit.copyText kit.crumb kit.data kit.dialog kit.dmy kit.el kit.errorText kit.extra kit.extraText kit.failed kit.formList kit.formOf kit.format kit.has kit.head kit.headChoice kit.headHasAction kit.headItems kit.headLines kit.headParts kit.hm kit.keepRoles kit.keepSearch kit.key kit.kv kit.link kit.loadFailed kit.loading kit.menu kit.note kit.pagerOf kit.phone kit.pickerClients kit.post kit.projectLabel kit.projectNo kit.projectText kit.quietField kit.quietText kit.recordWho kit.refresh kit.refuseAt kit.refusedText kit.reveal kit.route kit.saveFrom kit.saver kit.search kit.settingsOrdered kit.showLink kit.showRecord kit.sitePage kit.sortHead kit.stamp kit.step kit.submitter kit.t kit.toast kit.today kit.unsaver kit.valuesOfForm kit.wordOf kit.words kit.workbench. The object a page of a package is given when it draws (Nino.crm.pages.add(), the function draw( inner, where )) is the kit of the page and has exactly these members, held the same way (THE MEMBERS OF THE OBJECT OF A PAGE): inner.PAGE_SIZE inner.PHONE inner.alive inner.api inner.append inner.arm inner.ask inner.badge inner.call inner.card inner.cardBody inner.cardList inner.cardTabs inner.charCount inner.clear inner.clientField inner.clientTitle inner.copyText inner.crumb inner.data inner.dialog inner.dmy inner.el inner.errorText inner.extra inner.extraText inner.failed inner.form inner.formList inner.formOf inner.format inner.has inner.head inner.headChoice inner.headHasAction inner.headItems inner.headLines inner.headParts inner.hm inner.hostCard inner.keepRoles inner.keepSearch inner.kv inner.link inner.loadFailed inner.loading inner.main inner.menu inner.mine inner.note inner.pagerOf inner.phone inner.pickerClients inner.post inner.projectLabel inner.projectNo inner.projectText inner.quietField inner.quietText inner.recordWho inner.refresh inner.refuseAt inner.refusedText inner.release inner.reveal inner.route inner.saveFrom inner.saver inner.search inner.settingsOrdered inner.showLink inner.showRecord inner.sitePage inner.sortHead inner.stamp inner.statusLine inner.step inner.submitter inner.t inner.title inner.toast inner.today inner.unsaver inner.valuesOfForm inner.wordOf inner.words inner.workbench - inner.alive( ) says whether the page is still the one shown, inner.main is the node the page is drawn in, inner.arm( ) and inner.release( ) hold and let go of a form that was not stored. A name that is not in these lists is not the core's to give. What else is under Nino.crm is for the core's own scripts.
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
			\Nino\Callbacks::registerCallback( $appData, '/nino/admin/restore', [ self::class, 'callbackRestore' ] );
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

			// What a replacement of a database left on the way is data of an archive and no database: it never gets into a backup, and goes before the copy is made
			\Nino\Modules\Crm\Db::clearLeft( $args['dataDir']. '/'. $name );

			\Nino\Modules\Crm\Db::backup( $args['dataDir']. '/'. $name, $args['staging']. '/'. $name, \Nino\Modules\Crm\Db::held( $appData ) );
		}

		/**
		 *	The restore of the database, done once for a request and asked by whoever needs the answer: the callback of the restore of the core (callbackRestore()) and a package that keeps files below data/ that belong to what the
		 *	database says - it asks in ITS callback of the restore, whatever the order the kernel asks them in, and takes its files out of the staging where the answer is 'kept', so that the files of the archive are not copied over
		 *	what a database that stays refers to. The work is done at the first call; every other call of the request, with the same staging, does nothing and answers the same. It never throws.
		 *	'restored': the live database is now the one of the archive. 'kept': the live database is as it was - the archive holds none, the copy of the archive is damaged, is none of the core's or is from a newer version, the live file
		 *	is held by another process longer than the wait (five seconds), is another application's database or cannot be written, or ext/sqlite3 is not there. A live file that is damaged is overwritten page by page, one that is no database at all is replaced (see THE RESTORE; where either cannot be done the answer is kept) and the answer is restored. In every case the file of the archive and what an open copy leaves beside it are taken out of the staging,
		 *	so that the kernel never copies a database over the live one. The copy is looked at first, whole (Db::archived()), after its mode was set to DELETE - it is the core's alone at that moment - and is written into the live
		 *	database with the online backup of SQLite, page by page under its locks. While the connection to the live file is open nothing opens the file beside it (see THE FILE IS OPENED ONLY BY ITS CONNECTION)
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$args				(reference) dataDir: the live data directory, staging: the folder of the extracted archive, which holds a file of data/ below data/
		 *
		 *	@return 	string										restored or kept
		 */
		public static function restore( array &$appData, array &$args ): string {

			$staging = is_string( $args['staging'] ?? null ) === true ? $args['staging'] : '';
			$data = is_string( $args['dataDir'] ?? null ) === true ? $args['dataDir'] : '';

			if( $staging === '' || $data === '' )
				return 'kept';

			if( isset( $appData['./crm/restore'][$staging] ) === true )
				return $appData['./crm/restore'][$staging]['answer'];

			$name = basename( \Nino\Modules\Crm\Db::FILE );
			$staged = $staging. '/data/'. $name;
			$live = $data. '/'. $name;
			$why = '';

			// What an earlier replacement left on the way goes first, whichever way this one goes
			\Nino\Modules\Crm\Db::clearLeft( $live );
			$gone = static function() use ( $staged ): void {

				foreach( [ '', '-journal', '-wal', '-shm' ] as $suffix )
					if( is_link( $staged. $suffix ) === true || is_file( $staged. $suffix ) === true )
						@unlink( $staged. $suffix );

				if( is_dir( $staged ) === true && is_link( $staged ) === false )
					\Nino\Filesystem::removeDir( $staged );
			};

			if( file_exists( $staged ) === false && is_link( $staged ) === false )
				$why = 'none';
			elseif( is_file( $staged ) === false || is_link( $staged ) === true )
				$why = 'foreign';
			elseif( class_exists( '\SQLite3' ) === false )
				$why = 'extension';
			else {

				// The copy is the core's alone for now: its mode is set where it was made in the mode of the log of writes, which leaves no file beside it
				$bad = '';

				try {
					$copy = new \PDO( 'sqlite:'. $staged, null, null, [ \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION ] );
					$copy->exec( 'PRAGMA journal_mode = DELETE' );
					$copy = null;
				}
				catch( \PDOException $e ) {
					$bad = 'damaged';
				}

				$bad = $bad !== '' ? $bad : \Nino\Modules\Crm\Db::archived( $staged );
				$kind = \Nino\Modules\Crm\Db::kindFor( $appData, $live, $detail );
				$state = $kind === 'ours' ? \Nino\Modules\Crm\Db::damaged( $live ) : 'ok';

				// What the live file is decides the way. One that SQLite takes for a database - whole or damaged, as long as it can be opened - is written page by page under the locks of SQLite, which overwrites every page of it; a lock
				// that another process holds is never a damage, it is "in use". Only a file that is no database for SQLite at all (bytes that are none, a header that is cut off, a text) is replaced by a new file, and then only where the
				// system says that nothing holds it. Another application's database, a folder, a link and a file that cannot be read stay as they are
				if( $bad !== '' )
					$why = $bad;
				elseif( is_link( $live ) === true )
					$why = 'link';
				elseif( $kind === 'foreign' && $detail === 'garbage' )
					$why = \Nino\Modules\Crm\Db::replace( $staged, $live, is_callable( $appData['./crm/restore/at'] ?? null ) === true ? $appData['./crm/restore/at'] : null, is_string( $appData['./crm/restore/locks'] ?? null ) === true ? $appData['./crm/restore/locks'] : null );
				elseif( in_array( $kind, [ 'foreign', 'unreadable' ], true ) === true )
					$why = 'live';
				elseif( $state === 'busy' )
					$why = 'held';
				else {

					$from = null;
					$to = null;

					try {
						$from = new \SQLite3( $staged, SQLITE3_OPEN_READONLY );
						$to = new \SQLite3( $live );
						$from->enableExceptions( true );
						$to->enableExceptions( true );
						$to->busyTimeout( \Nino\Modules\Crm\Db::BUSY );
						$from->backup( $to );

						// A file that was damaged is whole after this or it is not written: the check is asked of the connection that wrote, not of the file
						if( $state === 'damaged' && $to->querySingle( 'PRAGMA integrity_check' ) !== 'ok' )
							$why = 'damagedlive';
					}
					catch( \Throwable $e ) {
						$why = $e instanceof \SQLite3Exception && preg_match( '/lock|busy/i', $e->getMessage() ) === 1 ? 'held' : ( $state === 'damaged' ? 'damagedlive' : 'live' );
					}
					finally {
						$from?->close();
						$to?->close();
					}
				}
			}

			$gone();

			if( $why === '' ) {

				// What this request read of the database was read before it: the next question opens the file anew. The copy is written, and the mode of the file is the one of the copy, which is DELETE; where something is left for the
				// file, the next connection of the core sets it - that is no reason to say the copy is not written
				\Nino\Modules\Crm\Db::close( $appData );
				\Nino\Modules\Crm\Settings::release( $appData );
			}

			if( $why !== '' && $why !== 'none' )
				trigger_error( 'Crm: the database of the archive was not written into the live one ('. $why. '): the live database is as it was', E_USER_WARNING );

			$appData['./crm/restore'][$staging] = [ 'answer' => $why === '' ? 'restored' : 'kept', 'why' => $why ];

			return $appData['./crm/restore'][$staging]['answer'];
		}

		/**
		 *	The callback of the restore of the core ('/nino/admin/restore'): restore() does the work, and where the live database stays - for any reason but that the archive holds none - the callback throws with a sentence that says why and
		 *	holds no path: the kernel names the core in its answer, and since nothing of the database is left in the staging, it copies nothing over the live file
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$args				(reference) dataDir, staging
		 *
		 *	@return 	void
		 *
		 *	@throws		\RuntimeException					The arguments are not what the restore gives, or the live database stays
		 */
		public static function callbackRestore( array &$appData, array &$args ): void {

			if( is_string( $args['dataDir'] ?? null ) === false || is_string( $args['staging'] ?? null ) === false || $args['dataDir'] === '' || $args['staging'] === '' )
				throw new \RuntimeException( 'the restore did not say where the data is and where the copy lies' );

			if( self::restore( $appData, $args ) === 'restored' )
				return;

			$why = (string) ( $appData['./crm/restore'][$args['staging']]['why'] ?? '' );

			if( $why === 'none' )
				return;

			throw new \RuntimeException( match( $why ) {
				'damaged'		=> 'the database of the CRM in the archive is damaged - it was not restored, the live database stays as it is',
				'foreign'		=> 'the database of the CRM in the archive is not the one of the CRM - it was not restored, the live database stays as it is',
				'newer'			=> 'the database of the CRM in the archive is from a newer version of the CRM - it was not restored, the live database stays as it is',
				'held'			=> 'the live database of the CRM is held by another process - the one of the archive was not restored, the live database stays as it is',
				'extension'	=> 'without the PHP extension sqlite3 the CRM cannot restore its database - the live database stays as it is',
				'link'			=> 'the live database of the CRM is a link or no ordinary file - the one of the archive was not restored, the live database stays as it is',
				'unknown'		=> 'whether the live database of the CRM is in use cannot be found out here - the one of the archive was not restored, the live database stays as it is',
				'damagedlive' => 'the live database of the CRM is damaged and could not be overwritten - take the file away and restore the backup once more; the live database stays as it is',
				default			=> 'the live database of the CRM cannot be written - the one of the archive was not restored, the live database stays as it is',
			} );
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
