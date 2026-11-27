<?php
declare(strict_types=1);

namespace Nino\Modules\CrmDocuments {

	use Nino\Modules\Crm\Db;
	use Nino\Modules\Crm\Surface\Texts;

	/**
	 *	Status		The lines of the status of the CRM that are the documents': Pdf is there and on (pdf), has the tags the documents ask its templates by (pdf-tags), has a template for an offer and for an invoice (templates-offer,
	 *								templates-invoice), the folder of the files can be written (pdf-dir), the type of the items is there (items), and what the files of the books weigh in the daily backup (backup-size, which is a hint and no fault). A line
	 *								says its label and its sentence itself, in the language of the person who looks, from the words of the surface.
	 *
	 *								Two lines have a fix, and both mend what the owner would do by hand in the panel of Pdf - the tags and the templates - and nothing else: the tags are added to the list of Pdf (\Nino\Features::saveSettings(), which Pdf validates),
	 *								the templates, the text blocks and the layout of the six kinds of document are installed add-only (\Nino\Modules\Pdf::install(), which replaces nothing that is there). Neither deletes anything.
	 *
	 *	@package						Dape/Nino
	 */
	class Status {

		// How many names of the files the line of the status tells
		private const int NAMES = 10;

		/**	The size of a logo above which the status says that every PDF carries it: bytes	*/
		public const int LOGO = 102400;

		/**
		 *	Enter the lines
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	bool											Whether every line was taken
		 */
		public static function register( array &$appData ): bool {

			$taken = true;

			foreach( [
				[ 'pdf', 100, 'pdf', false, null ], [ 'pdf-tags', 110, 'tags', false, 'fixTags' ], [ 'templates-offer', 120, 'offer', false, 'fixTemplates' ], [ 'templates-invoice', 130, 'invoice', false, 'fixTemplates' ],
				[ 'pdf-dir', 140, 'directory', false, null ], [ 'pdf-files', 145, 'files', true, null ], [ 'items', 150, 'items', false, null ], [ 'backup-size', 160, 'backup', true, null ],
			] as [ $key, $rank, $method, $hint, $fix ] )
				$taken = \Nino\Modules\Crm\Status::add( $appData, [ 'package' => 'crm-documents', 'key' => $key, 'rank' => $rank, 'state' => [ self::class, $method ], 'hint' => $hint ] + ( $fix === null ? [] : [ 'fix' => [ self::class, $fix ] ] ) ) === true && $taken === true;

			return $taken;
		}

		/**
		 *	Pdf: there, on and without a problem
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	array											state ok, off (installed, not active), missing (not installed), problem
		 */
		public static function pdf( array &$appData ): array {

			$feature = \Nino\Features::get( $appData, 'pdf' );
			$state = match( true ) {
				$feature === null            => 'missing',
				$feature['problems'] !== []  => 'problem',
				$feature['active'] === false => 'off',
				default                      => 'ok',
			};

			return self::line( $appData, 'pdf', $state );
		}

		/**
		 *	The tags of Pdf the documents need
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	array											state ok, missing (with tags: the ones that are not on the list), off (Pdf is not on: there is nothing to say)
		 */
		public static function tags( array &$appData ): array {

			if( \Nino\Modules\Crm::featureOn( $appData, 'pdf' ) === false )
				return self::line( $appData, 'pdf-tags', 'off' ) + [ 'fixable' => false ];

			$missing = array_values( array_diff( Templates::TAGS, \Nino\Modules\Pdf::tags( $appData ) ) );

			return self::line( $appData, 'pdf-tags', $missing === [] ? 'ok' : 'missing', [ 'tags' => $missing ], [ implode( ', ', $missing ) ] );
		}

		/**
		 *	Whether Pdf has a template for the tag offer
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	array											state ok, none, off
		 */
		public static function offer( array &$appData ): array {

			return self::templates( $appData, 'offer' );
		}

		/**
		 *	Whether Pdf has a template for the tag invoice
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	array											state ok, none, off
		 */
		public static function invoice( array &$appData ): array {

			return self::templates( $appData, 'invoice' );
		}

		/**
		 *	@param		array 		&$appData
		 *	@param		string		$tag
		 *
		 *	@return 	array
		 */
		private static function templates( array &$appData, string $tag ): array {

			// Without Pdf there are no templates to miss: the line about Pdf says it
			if( \Nino\Modules\Crm::featureOn( $appData, 'pdf' ) === false )
				return self::line( $appData, 'templates-'. $tag, 'off' ) + [ 'fixable' => false ];

			return self::line( $appData, 'templates-'. $tag, \Nino\Modules\Pdf::templates( $appData, $tag ) !== [] ? 'ok' : 'none' );
		}

		/**
		 *	The folder the files are kept in can be written
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	array											state ok, unwritable
		 */
		public static function directory( array &$appData ): array {

			$dir = \Nino\Filesystem::path( $appData, Render::DIR );

			// The nearest directory there is must be writable, and nothing that is no directory may lie in the way
			while( $dir !== '' && is_dir( $dir ) === false && file_exists( $dir ) === false && dirname( $dir ) !== $dir )
				$dir = dirname( $dir );

			return self::line( $appData, 'pdf-dir', is_dir( $dir ) === true && is_writable( $dir ) === true ? 'ok' : 'unwritable' );
		}

		/**
		 *	The files of the books against the rows: a file that a process left when it died after its commit is given its name first (Render::settleAll()). Then the line tells, as a hint and no fault, what is not in order:
		 *	foreign - a document names a file and what lies at its name is no file of the books (a link, a folder, a file of no bytes; it is never replaced, the owner takes it away by hand). The status does not read the files - it asks the file system once for each - so that it stays cheap with many documents: a file of another CONTENT is not told here, it shows when the file is downloaded (409) or made again (409); missing - a document names a file that is not there (and no pending file has its hash); stray - a file with the name of a document that no row names, which is what a backup that was restored older than the files looks like:
		 *	the numbers are given away, and finalising is refused for each with the file in the way; pending - files under a pending name that no row has settled, what a process left that died before its commit.
		 *	Nothing is deleted or moved here: the files are the books, and what to do with them is for the owner
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	array											state ok, foreign, missing, stray, pending (the worst of them); files (stray), pending, missing and foreign: how many; names of each (at most ten)
		 */
		public static function files( array &$appData ): array {

			Render::settleAll( $appData );

			$known = [];
			$missing = [];
			$foreign = [];

			foreach( Db::rows( $appData, 'SELECT pdf_path, xml_path FROM documents WHERE pdf_path <> :none OR xml_path <> :none', [ 'none' => '' ] ) as $row )
				foreach( [ 'pdf_path', 'xml_path' ] as $column ) {

					if( $row[$column] === '' )
						continue;

					$file = \Nino\Filesystem::path( $appData, (string) $row[$column] );
					$known[$file] = true;
					$lies = Render::looks( $appData, (string) $row[$column] );

					if( $lies === 'none' )
						$missing[] = basename( $file );
					elseif( $lies === 'other' )
						$foreign[] = basename( $file );
				}

			$stray = [];
			$pending = [];

			foreach( glob( \Nino\Filesystem::path( $appData, Render::DIR ). '/[0-9][0-9][0-9][0-9]/*' ) ?: [] as $file ) {

				if( is_file( $file ) === false )
					continue;

				if( preg_match( '/\.(pdf|xml)'. preg_quote( Render::PENDING, '/' ). '[0-9a-f]{12}$/D', $file ) === 1 )
					$pending[] = basename( dirname( $file ) ). '/'. basename( $file );
				elseif( isset( $known[$file] ) === false )
					$stray[] = basename( $file );
			}

			sort( $missing );
			sort( $foreign );
			sort( $stray );
			sort( $pending );

			$names = static fn( array $list ): string => implode( ', ', array_slice( $list, 0, self::NAMES ) ). ( count( $list ) > self::NAMES ? ', ...' : '' );

			// The numbers a stray file gives away are what its name reads as: the name without the end that tells the kind of the file
			$numbers = array_values( array_unique( array_map( static fn( string $name ): string => (string) preg_replace( '/\.(pdf|xml)$/D', '', $name ), $stray ) ) );
			$parts = [ 'foreign' => $foreign, 'missing' => $missing, 'stray' => $numbers, 'pending' => $pending ];
			$present = array_keys( array_filter( $parts, static fn( array $list ): bool => $list !== [] ) );
			$state = $present === [] ? 'ok' : $present[0];
			$line = self::line( $appData, 'pdf-files', $state, [ 'files' => count( $stray ), 'numbers' => array_slice( $numbers, 0, self::NAMES ), 'pending' => count( $pending ), 'missing' => count( $missing ), 'foreign' => count( $foreign ), 'foreign_names' => array_slice( $foreign, 0, self::NAMES ), 'names' => array_slice( $stray, 0, self::NAMES ), 'pending_names' => array_slice( $pending, 0, self::NAMES ), 'missing_names' => array_slice( $missing, 0, self::NAMES ) ],
				$state === 'ok' ? [] : [ (string) count( $parts[$state] ), $names( $parts[$state] ) ] );

			// What else is the matter is told after the worst
			foreach( array_slice( $present, 1 ) as $other )
				$line['detail'] .= ' '. vsprintf( Texts::word( $appData, 'docs.detail-pdf-files-'. $other ), [ (string) count( $parts[$other] ), $names( $parts[$other] ) ] );

			return $line;
		}

		/**
		 *	The element type of the items is there
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	array											state ok, missing
		 */
		public static function items( array &$appData ): array {

			return self::line( $appData, 'items', Items::missing( $appData ) === true ? 'missing' : 'ok' );
		}

		/**
		 *	What the files of the books weigh in the daily backup: the PDFs and the electronic invoices are in it (the manifest names the folder), and the disk the archive is made on needs room for the whole of it. A hint and no fault
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	array											state ok; short (the disk that packs has not the room for the backup as it is now, megabytes needed and free); logo (a logo that every PDF carries is large); files and megabytes (what the folder holds)
		 */
		public static function backup( array &$appData ): array {

			$files = 0;
			$bytes = 0;
			$directory = \Nino\Filesystem::path( $appData, Render::DIR );

			foreach( is_dir( $directory ) === true ? ( glob( $directory. '/[0-9][0-9][0-9][0-9]/*' ) ?: [] ) : [] as $file ) {

				if( is_file( $file ) === false || is_link( $file ) === true )
					continue;

				$files++;
				$bytes += (int) @filesize( $file );
			}

			$whole = 0;
			$all = \Nino\Backup::manifest( $appData );

			foreach( array_keys( $all ) as $file )
				$whole += (int) @filesize( (string) $file );

			$short = \Nino\Backup::spaceShort( $appData, $whole, count( $all ), null );
			$logo = self::logo( $appData );
			$megabytes = (int) ceil( $bytes / 1048576 );

			if( $short !== null )
				return self::line( $appData, 'backup-size', 'short', [ 'files' => $files, 'megabytes' => $megabytes, 'need' => $short[0], 'free' => $short[1] ], [ (string) $short[0], (string) $short[1] ] );

			if( $logo > self::LOGO && $files > 0 )
				return self::line( $appData, 'backup-size', 'logo', [ 'files' => $files, 'megabytes' => $megabytes, 'logo' => $logo ], [ (string) (int) ceil( $logo / 1024 ) ] );

			return self::line( $appData, 'backup-size', 'ok', [ 'files' => $files, 'megabytes' => $megabytes ], [ (string) $files, (string) $megabytes ] );
		}

		/**
		 *	The size of the logo of Pdf where a PDF of a document carries it: the header, the footer or a block of a template says img with the src pdf:logo. A logo that no text names is in no PDF and weighs nothing in the books
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	int												Bytes; 0 for none
		 */
		private static function logo( array &$appData ): int {

			if( \Nino\Modules\Crm::featureOn( $appData, 'pdf' ) === false )
				return 0;

			$layout = \Nino\Modules\Pdf::layout( $appData );
			$texts = [ (string) $layout['header'], (string) $layout['footer'] ];

			foreach( \Nino\Modules\Pdf::keys( $appData ) as $key )
				foreach( (array) ( \Nino\Modules\Pdf::template( $appData, (string) $key )['blocks'] ?? [] ) as $block )
					$texts[] = (string) ( $block['html'] ?? '' );

			$named = false;

			foreach( $texts as $text )
				if( str_contains( strtolower( $text ), 'pdf:logo' ) === true )
					$named = true;

			return $named === true ? strlen( \Nino\Modules\Pdf::picture( $appData, 'logo' ) ) : 0;
		}

		/**
		 *	Add the tags the documents ask the templates by to the list of Pdf, and keep what is on it
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	true|string								true where the tags are on the list; else the reason
		 */
		public static function fixTags( array &$appData ): true|string {

			if( \Nino\Modules\Crm::featureOn( $appData, 'pdf' ) === false )
				return 'off';

			$tags = \Nino\Modules\Pdf::tags( $appData );
			$problems = [];

			if( array_diff( Templates::TAGS, $tags ) === [] )
				return true;

			return \Nino\Features::saveSettings( $appData, 'pdf', [ 'tags' => array_values( array_unique( array_merge( $tags, Templates::TAGS ) ) ) ], $problems ) === [] ? true : 'failed';
		}

		/**
		 *	Install the templates, the text blocks and the layout of the six kinds of document add-only: a piece that is there stays, and a piece that Pdf refuses is a failure
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	true|string								true where nothing was refused; else the reason
		 */
		public static function fixTemplates( array &$appData ): true|string {

			if( \Nino\Modules\Crm::featureOn( $appData, 'pdf' ) === false )
				return 'off';

			// The tags are the list of Pdf's: a template whose tag is not on it is refused
			$tags = self::fixTags( $appData );

			if( $tags !== true )
				return $tags;

			$seed = Templates::seed( $appData );
			$done = \Nino\Modules\Pdf::install( $appData, (array) $seed['templates'], (array) $seed['blocks'], (array) $seed['layout'], false );

			return array_filter( (array) ( $done['refused'] ?? [] ) ) === [] ? true : 'failed';
		}

		/**
		 *	One line as the status draws it: the state, its label and its sentence in the language of the person who looks
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$key					The key of the line
		 *	@param		string		$state
		 *	@param		array 		$values				More values the screens may use
		 *	@param		array 		$params				The values of the sentence
		 *
		 *	@return 	array
		 */
		private static function line( array &$appData, string $key, string $state, array $values = [], array $params = [] ): array {

			$sentence = Texts::word( $appData, 'docs.detail-'. $key. '-'. $state );

			return [ 'state' => $state, 'label' => Texts::word( $appData, 'docs.line-'. $key ), 'detail' => $params === [] ? $sentence : vsprintf( $sentence, $params ) ] + $values;
		}
	}
}
