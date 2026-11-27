<?php
declare(strict_types=1);

namespace Nino\Modules\CrmDocuments {

	use Nino\Modules\Crm\Format;
	use Nino\Modules\Crm\Projects;
	use Nino\Modules\Crm\Surface\Texts;

	/**
	 *	Export		What the list of the documents hands to the accountant: the documents of a year as a CSV, the PDFs of a year as a ZIP, and the PDFs of a choice as a ZIP. For a session with the permission of the CRM (401 for nobody, 403 without it);
	 *							every answer is an attachment that no cache keeps and that a browser does not sniff (Content-Disposition attachment, Cache-Control private, no-store, X-Content-Type-Options nosniff), and the log of the workbench gets one line
	 *							that counts - "Exported ..." - and names no client, no number and no amount.
	 *
	 *							GET /crm/export/documents.csv?year=2026			the documents of the year that are no drafts and have a number and an amount - the offers, the invoices and the credit notes - one line each, the oldest first:
	 *																													number, category, date, client, net, VAT, gross, state, paid on, project. UTF-8 with the byte order mark, a semicolon between the cells, CRLF at the end of a line,
	 *																													the amounts as the German books write them (1234,56, a minus for a credit note, no thousands mark), the days as d.m.Y. A cell of text that begins with = + - @, a tab or a carriage
	 *																													return is led by an apostrophe, so that a spreadsheet reads it as text and never as a formula - the amounts are ours and are not text. A cell that holds a semicolon, a quote or a line
	 *																													break is quoted; a null byte is dropped. The year is four digits or the answer is 400
	 *							GET /crm/export/documents-2026.zip						the PDFs of the year, each as <number>.pdf, and the electronic invoice of a document that has one beside it as <number>.xml: only documents that are finalised and whose file is
	 *																													there and is the file the database has the SHA-256 of (a file that is gone or not the same is left out, as the download of the document refuses it, and named in missing.txt in the ZIP -
	 *																													there only where one was left out; the log counts them: "Exported 13 of 16 documents of 2026 as a zip, 3 left out"). More than MAX_FILES documents is the answer 413 and no ZIP
	 *							GET /crm/export/documents.zip?ids=<id>,<id>		the same for the documents the ids name (at most MAX_FILES): every id has to be a document of that kind, or the answer is 404 for the whole request and no ZIP is made
	 *
	 *							The ZIP is made by ZipArchive (without the extension the answer is 409) as a file of its own below /data/crm-zip, answered as the file the kernel streams and removed when the request ends; a file an interrupted request left is
	 *							swept at the next export. Nothing of the export is stored.
	 *
	 *	@package						Dape/Nino
	 */
	class Export {

		public const string ROUTE = '/crm/export/';

		// The most documents a ZIP takes: a year of a studio is far below it, and the ids of a choice are the query of a link
		public const int MAX_FILES = 500;

		// Where the ZIPs are made below /data, and how long one that was left behind stays
		public const string DIRECTORY = '/data/crm-zip';
		public const int STALE = 3600;

		// The first bytes of a file the spreadsheets read as UTF-8
		private const string BOM = "\xEF\xBB\xBF";

		// What leads a cell a spreadsheet takes for a formula
		private const string FORMULA = "=+-@\t\r";

		// What the module keeps for the request: the ZIPs that are to go when it ends, and whether the function that removes them is registered
		private const string PENDING = './crm-documents/export/pending';
		private const string REGISTERED = './crm-documents/export/registered';

		/**
		 *	Whether ZipArchive is there
		 *
		 *	@return 	bool
		 */
		public static function zipAvailable(): bool {

			return extension_loaded( 'zip' ) === true;
		}

		/**
		 *	GET /crm/export/...: the CSV or the ZIP the address names
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		&$request
		 *
		 *	@return 	void
		 */
		public static function callback( array &$appData, array &$request ): void {

			$user = \Nino\Auth::getCurrentUser( $appData );

			if( $user === false ) {
				Render::answer( $request, 401, Texts::word( $appData, 'docs.session' ) );
				return;
			}

			if( \Nino\Auth::checkPermission( $appData, \Nino\Modules\Crm::MANAGE_PERM ) === false ) {
				Render::answer( $request, 403, Texts::word( $appData, 'error.forbidden' ) );
				return;
			}

			$uri = (string) ( $request['/nino/http/request']['uri'] ?? '' );
			$name = str_starts_with( $uri, self::ROUTE ) === true ? substr( $uri, strlen( self::ROUTE ) ) : '';
			$query = $request['/nino/http/request']['query'] ?? [];
			$query = is_array( $query ) === true ? $query : [];

			try {
				if( $name === 'documents.csv' )
					self::sendCsv( $appData, $request, (string) $user['mail'], $query );
				elseif( preg_match( '/^documents-([0-9]{4})\.zip$/D', $name, $found ) === 1 )
					self::sendZip( $appData, $request, (string) $user['mail'], (int) $found[1], [] );
				elseif( $name === 'documents.zip' )
					self::sendZip( $appData, $request, (string) $user['mail'], null, self::ids( $query ) );
				else
					Render::answer( $request, 404, Texts::word( $appData, 'export.unknown' ) );
			}
			catch( \PDOException $e ) {
				Render::answer( $request, 503, Texts::word( $appData, 'error.crm_database' ) );
			}
		}

		/**
		 *	The CSV of a year as the files of the accountant take it
		 *
		 *	@param		array 		$rows					As Figures::ledger() has them
		 *	@param		array 		$texts				The words of the surface
		 *
		 *	@return 	string										With the byte order mark in front
		 */
		public static function csv( array $rows, array $texts ): string {

			$lines = [ self::line( array_map( static fn( string $key ): string => (string) ( $texts['export.col-'. $key] ?? $key ), [ 'number', 'category', 'date', 'client', 'net', 'vat', 'gross', 'state', 'paid', 'project' ] ) ) ];

			foreach( $rows as $row )
				$lines[] = self::line( [
					self::text( $row['number'] ),
					self::text( (string) ( $texts['category.'. $row['category']] ?? $row['category'] ) ),
					Format::day( $row['date'] ),
					self::text( $row['client_title'] ),
					self::amount( $row['net'] ),
					self::amount( $row['vat'] ),
					self::amount( $row['gross'] ),
					self::text( (string) ( $texts['state.'. $row['state']] ?? $row['state'] ) ),
					Format::day( $row['paid_at'] ),
					self::text( Projects::named( (string) $row['project'], (string) $row['project_title'] ) ),
				] );

			return self::BOM. implode( "\r\n", $lines ). "\r\n";
		}

		/**
		 *	A cell of text made safe: no null byte, led by an apostrophe where a spreadsheet would read a formula. It is not yet quoted - line() does that
		 *
		 *	@param		string		$text
		 *
		 *	@return 	string
		 */
		public static function text( string $text ): string {

			$text = str_replace( "\0", '', $text );

			return $text !== '' && str_contains( self::FORMULA, $text[0] ) === true ? '\''. $text : $text;
		}

		/**
		 *	An amount in cents as the German books write it: 1234,56 - a minus for a credit note, no thousands mark, no currency
		 *
		 *	@param		int				$cents
		 *
		 *	@return 	string
		 */
		public static function amount( int $cents ): string {

			return ( $cents < 0 ? '-' : '' ). intdiv( abs( $cents ), 100 ). ','. sprintf( '%02d', abs( $cents ) % 100 );
		}

		/**
		 *	The cells of a line joined by the semicolon, each quoted where it holds a semicolon, a quote or a line break
		 *
		 *	@param		array 		$cells
		 *
		 *	@return 	string
		 */
		private static function line( array $cells ): string {

			return implode( ';', array_map( static fn( string $cell ): string => preg_match( '/[;"\r\n]/', $cell ) === 1 ? '"'. str_replace( '"', '""', $cell ). '"' : $cell, $cells ) );
		}

		/**
		 *	@param		array 		$query
		 *
		 *	@return 	array											The ids the query names, each once; [] for anything that is no list of ids
		 */
		private static function ids( array $query ): array {

			$asked = is_string( $query['ids'] ?? null ) === true ? array_values( array_unique( explode( ',', $query['ids'] ) ) ) : [];

			return array_filter( $asked, static fn( string $id ): bool => preg_match( '/^[1-9][0-9]{0,9}$/D', $id ) !== 1 ) === [] ? array_map( 'intval', $asked ) : [];
		}

		/**
		 *	@param		array 		&$appData
		 *	@param		array 		&$request
		 *	@param		string		$mail					The account that asked
		 *	@param		array 		$query
		 *
		 *	@return 	void
		 */
		private static function sendCsv( array &$appData, array &$request, string $mail, array $query ): void {

			$texts = Texts::all( $appData );
			$year = is_string( $query['year'] ?? null ) === true && preg_match( '/^2[0-9]{3}$/D', $query['year'] ) === 1 ? (int) $query['year'] : 0;

			if( $year === 0 ) {
				Render::answer( $request, 400, Texts::word( $appData, 'export.year' ) );
				return;
			}

			\Nino\Http::download( $request, self::csv( Figures::ledger( $appData, $year ), $texts ), 'documents-'. $year. '.csv', 'text/csv; charset=utf-8' );

			\Nino\Modules\Crm\Api::record( $appData, $mail, 'Exported the documents of '. $year. ' as a csv' );
		}

		/**
		 *	@param		array 		&$appData
		 *	@param		array 		&$request
		 *	@param		string		$mail					The account that asked
		 *	@param		int|null	$year					The year, or null for a choice
		 *	@param		array 		$ids					The choice
		 *
		 *	@return 	void
		 */
		private static function sendZip( array &$appData, array &$request, string $mail, ?int $year, array $ids ): void {

			if( self::zipAvailable() === false ) {
				Render::answer( $request, 409, Texts::word( $appData, 'export.zip-off' ) );
				return;
			}

			if( $year === null && ( $ids === [] || count( $ids ) > self::MAX_FILES ) ) {
				Render::answer( $request, 400, Texts::word( $appData, 'export.zip-ids' ) );
				return;
			}

			$rows = Figures::ledger( $appData, $year, $ids );

			// Every id has to be a document of the kind: the first that is none refuses the whole request
			if( $year === null && count( $rows ) !== count( $ids ) ) {
				Render::answer( $request, 404, Texts::word( $appData, 'export.zip-unknown' ) );
				return;
			}

			if( count( $rows ) > self::MAX_FILES ) {
				Render::answer( $request, 413, sprintf( Texts::word( $appData, 'export.zip-many' ), self::MAX_FILES ) );
				return;
			}

			[ 'files' => $files, 'left' => $left ] = self::files( $appData, $rows );

			if( $files === [] ) {
				Render::answer( $request, 404, Texts::word( $appData, 'export.zip-none' ) );
				return;
			}

			self::sweep( $appData );

			$directory = \Nino\Filesystem::path( $appData, self::DIRECTORY );

			if( is_dir( $directory ) === false && @mkdir( $directory, 0755, true ) === false && is_dir( $directory ) === false ) {
				Render::answer( $request, 500, Texts::word( $appData, 'export.zip-failed' ) );
				return;
			}

			$target = $directory. '/documents-'. bin2hex( random_bytes( 8 ) ). '.zip';

			self::remember( $appData, $target );

			if( self::pack( $files, $target, $left === [] ? '' : Texts::word( $appData, 'export.left-out' ). "\r\n". implode( "\r\n", $left ). "\r\n" ) === false ) {
				self::cleanup( $appData );
				Render::answer( $request, 500, Texts::word( $appData, 'export.zip-failed' ) );
				return;
			}

			$request['/nino/http/response']['file'] = $target;
			$request['/nino/http/response']['header']['Content-Type'] = 'application/zip';
			$request['/nino/http/response']['header']['Content-Disposition'] = 'attachment; filename="documents-'. ( $year ?? date( 'Y-m-d' ) ). '.zip"';
			self::guard( $request );

			\Nino\Modules\Crm\Api::record( $appData, $mail, $left === [] ? 'Exported '. count( $rows ). ' '. ( count( $rows ) === 1 ? 'document' : 'documents' ). ( $year === null ? '' : ' of '. $year ). ' as a zip'
				: 'Exported '. count( $files ). ' of '. count( $rows ). ' documents'. ( $year === null ? '' : ' of '. $year ). ' as a zip, '. count( $left ). ' left out' );
		}

		/**
		 *	The files of the documents that are there and are the ones the database has the hash of: the PDF of each and - where it has one - its electronic invoice
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		$rows					As Figures::ledger() has them
		 *
		 *	@return 	array											files (each with name - the entry, without the extension - and pdf and xml, the absolute path, '' for none) and left (the numbers of the documents that were left out)
		 */
		private static function files( array &$appData, array $rows ): array {

			Render::settleAll( $appData );

			$files = [];
			$left = [];

			foreach( $rows as $row ) {

				$pdf = Render::file( $appData, $row['pdf_path'] );

				if( $pdf === null || hash_equals( $row['pdf_sha256'], (string) hash_file( 'sha256', $pdf ) ) === false ) {
					$left[] = $row['number'];
					continue;
				}

				$xml = $row['xml_path'] === '' ? null : XRechnung::file( $appData, $row['xml_path'] );
				$xml = $xml !== null && hash_equals( $row['xml_sha256'], (string) hash_file( 'sha256', $xml ) ) === true ? $xml : '';

				$files[] = [ 'name' => (string) preg_replace( '/[^A-Za-z0-9._-]+/', '-', $row['number'] ), 'pdf' => $pdf, 'xml' => (string) $xml ];
			}

			return [ 'files' => $files, 'left' => $left ];
		}

		/**
		 *	The ZIP of the files: <number>.pdf and <number>.xml; a name that is there already - whatever the case - gets -2, -3 ... before its extension
		 *
		 *	@param		array 		$files				As files() has them
		 *	@param		string		$target				The path of the ZIP to make
		 *	@param		string		$left					The text of missing.txt, which names the documents that were left out; '' for none, and then there is no such file
		 *
		 *	@return 	bool											Whether the ZIP is there, whole
		 */
		public static function pack( array $files, string $target, string $left = '' ): bool {

			$zip = new \ZipArchive();

			if( $zip->open( $target, \ZipArchive::CREATE | \ZipArchive::OVERWRITE ) !== true )
				return false;

			$taken = [];

			foreach( $files as $file ) {

				$base = trim( $file['name'], '.-' ) !== '' ? $file['name'] : 'document';
				$name = $base;
				$count = 1;

				while( isset( $taken[mb_strtolower( $name )] ) === true )
					$name = $base. '-'. ++$count;

				$taken[mb_strtolower( $name )] = true;

				foreach( [ 'pdf', 'xml' ] as $kind )
					if( $file[$kind] !== '' && $zip->addFile( $file[$kind], $name. '.'. $kind ) === false ) {
						$zip->close();
						return false;
					}
			}

			if( $left !== '' && $zip->addFromString( 'missing.txt', $left ) === false ) {
				$zip->close();
				return false;
			}

			return $zip->close() === true && is_file( $target ) === true;
		}

		/**
		 *	The headers every answer of the export has
		 *
		 *	@param		array 		&$request
		 *
		 *	@return 	void
		 */
		private static function guard( array &$request ): void {

			$request['/nino/http/response']['header']['Cache-Control'] = 'private, no-store';
			$request['/nino/http/response']['header']['X-Content-Type-Options'] = 'nosniff';
		}

		/**
		 *	Remove what a request that was interrupted left behind: every ZIP of the folder - <name>-<hex>.zip, and the temporary files libzip leaves beside a ZIP it could not finish - that is older than STALE
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	int												How many went
		 */
		public static function sweep( array &$appData ): int {

			$gone = 0;
			$directory = \Nino\Filesystem::path( $appData, self::DIRECTORY );

			foreach( array_merge( glob( $directory. '/documents-*.zip' ) ?: [], glob( $directory. '/documents-*.zip.*' ) ?: [] ) as $zip )
				if( is_file( $zip ) === true && (int) @filemtime( $zip ) < time() - self::STALE && @unlink( $zip ) === true )
					$gone++;

			return $gone;
		}

		/**
		 *	Remove the ZIPs of this request: what the shutdown function does once the response is sent
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	int												How many went
		 */
		public static function cleanup( array &$appData ): int {

			$gone = 0;

			foreach( (array) ( $appData[self::PENDING] ?? [] ) as $path )
				if( is_string( $path ) === true && is_file( $path ) === true && @unlink( $path ) === true )
					$gone++;

			$appData[self::PENDING] = [];

			return $gone;
		}

		/**
		 *	A ZIP that is to be removed when the request ends
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$path
		 *
		 *	@return 	void
		 */
		private static function remember( array &$appData, string $path ): void {

			$appData[self::PENDING][] = $path;

			if( ( $appData[self::REGISTERED] ?? false ) === false ) {
				$appData[self::REGISTERED] = true;
				register_shutdown_function( static function() use ( &$appData ): void {
					self::cleanup( $appData );
				} );
			}
		}
	}
}
