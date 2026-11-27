<?php
declare(strict_types=1);
/**
 *	Nino								A compact filesystembased php framework
 *	Modules\CrmDocuments		see _nino/Nino/Modules/Modules.php for the
 *											package-level docblock
 *
 *	@package						Dape/Nino
 *	@author							David Perchermeier <mail@dape.io>
 *	@link								https://github.com/dapeio/nino
 */
namespace Nino\Modules {

	use Nino\Modules\Crm\Db;
	use Nino\Modules\Crm\Hosts;
	use Nino\Modules\Crm\Sequences;
	use Nino\Modules\Crm\Surface;
	use Nino\Modules\CrmDocuments\Api;
	use Nino\Modules\CrmDocuments\Export;
	use Nino\Modules\CrmDocuments\Hooks;
	use Nino\Modules\CrmDocuments\Preview;
	use Nino\Modules\CrmDocuments\Render;
	use Nino\Modules\CrmDocuments\Rules;
	use Nino\Modules\CrmDocuments\Status;
	use Nino\Modules\CrmDocuments\Templates;
	use Nino\Modules\CrmDocuments\XRechnung;

	/**
	 *	Nino							A compact filesystembased php framework
	 *	CrmDocuments			The documents of the CRM: offers, invoices and credit notes with lines, totals in cents and numbers that run without a gap, and the contracts, privacy documents and information sheets that have
	 *											no amount. A document is a draft until it is finalised: then it has its number, its frozen client and its PDF file - made by the templates of the feature Pdf, written once below /data/crm/pdf/<year>/
	 *											and never replaced - and, for the invoice of a business client, its electronic invoice beside it; no action of the package changes what it says or deletes it again (see Documents, Finalise and Api); only the test phase takes the documents with their files, and the removal of the data of a removed package takes the tables and leaves the files.
	 *
	 *											The package docks to the core the way every package does (see Crm): its tables are a schema of its own in the database of the core, its numbers are three sequences of the core, its
	 *											settings are keys of the settings of the core, its actions are entered at the endpoint, what it holds of a client enters the deletion of the client and the test phase, and the lines of its
	 *											status are lines of the status of the core. What it needs of the feature Pdf is the one it asks - the templates, the inputs, the render - and what it answers when Pdf asks it - which
	 *											templates a document names, and which tokens a template may use. The package knows no portal and no Sign: the release of a document to a client, the mails of it, the order of an offer and the
	 *											request for a signature are the packages that bring them, which enter themselves here and are asked by nobody in this one.
	 *
	 *											init() registers behaviour and writes nothing: the database is opened on first use, never in an init(). It begins with the question every package begins with, whether the core is on.
	 *
	 *	@package					Dape/Nino
	 */
	class CrmDocuments {

		/**	The key of the package	*/
		public const string KEY = 'crm-documents';

		/**
		 *	Register what the package enters in the core, the routes of its files and the hooks it answers
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	void
		 */
		public static function init( array &$appData ): void {

			if( Crm::featureOn( $appData, 'crm' ) === false )
				return;

			self::schema( $appData );

			// The numbers of the three kinds of document, and the settings that format and start them
			foreach( \Nino\Modules\CrmDocuments\Numbers::CATEGORIES as $category )
				Sequences::add( $appData, [ 'category' => $category, 'package' => self::KEY ] );

			Rules::register( $appData );

			// The files of the books and the owner's links to them are the owner's on every host: the route decides by the session
			Hosts::share( $appData, [ '/.crm/pdf/', '/.crm/xml/' ], [], [ 'package' => self::KEY ] );

			Api::register( $appData );
			\Nino\Modules\Crm\Values::addType( $appData, [ 'package' => self::KEY, 'type' => 'crm-items', 'screen' => 'values', 'rank' => 300 ] );
			Hooks::register( $appData );
			\Nino\Callbacks::registerCallback( $appData, '/nino/admin/restore', [ Hooks::class, 'callbackRestore' ] );
			Status::register( $appData );
			Surface::addTexts( $appData, __DIR__. '/Surface/text', [ 'package' => self::KEY ] );
			\Nino\Modules\Crm\Surface\Data::add( $appData, [ self::class, 'pageData' ], [ 'package' => self::KEY ] );

			// What Pdf asks of the documents: which templates they name, which tokens a template may use
			\Nino\Callbacks::registerCallback( $appData, '/pdf/template/uses', [ Templates::class, 'callbackUses' ] );
			\Nino\Callbacks::registerCallback( $appData, '/pdf/vocabulary', [ Templates::class, 'callbackVocabulary' ] );

			// The files of the books: the PDF and the electronic invoice of a finalised document, the draft as it would be made now, and what goes to the accountant
			$appData['/nino/http/routes']['GET://.crm/pdf/*'] = [ 'uri' => '/.crm/pdf/*' ];
			$appData['/nino/http/routes']['GET://.crm/xml/*'] = [ 'uri' => '/.crm/xml/*' ];
			$appData['/nino/http/routes']['GET://crm/preview/*'] = [ 'uri' => '/crm/preview/*' ];
			$appData['/nino/http/routes']['GET://crm/export/*'] = [ 'uri' => '/crm/export/*' ];

			\Nino\Callbacks::registerCallback( $appData, '/nino/http/response/GET://.crm/pdf/*', [ Render::class, 'callbackDownload' ] );
			\Nino\Callbacks::registerCallback( $appData, '/nino/http/response/GET://.crm/xml/*', [ XRechnung::class, 'callbackDownload' ] );
			\Nino\Callbacks::registerCallback( $appData, '/nino/http/response/GET://crm/preview/*', [ Preview::class, 'callback' ] );
			\Nino\Callbacks::registerCallback( $appData, '/nino/http/response/GET://crm/export/*', [ Export::class, 'callback' ] );
		}

		/**
		 *	Raise the tables of the package to the schema of this version. The schema is entered first - an update does not run init() before it -, and the database is made and raised in a transaction of its own; a file that cannot be
		 *	raised refuses the update, and the kernel asks again at the next activation. Idempotent
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$fromVersion	The version the project had
		 *
		 *	@return 	bool											false refuses the update
		 */
		public static function upgrade( array &$appData, string $fromVersion ): bool {

			if( Crm::featureOn( $appData, 'crm' ) === false )
				return true;

			self::schema( $appData );

			try {
				Db::ensure( $appData );
			}
			catch( \PDOException $e ) {
				trigger_error( 'CrmDocuments: the database could not be raised to the schema of this version', E_USER_WARNING );

				return false;
			}

			return true;
		}

		/**
		 *	The data of the package in the data block of the page of the surface: whether Pdf is there and the account may look at its templates, the most documents a ZIP takes and whether the ZIP can be made
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	array
		 */
		public static function pageData( array &$appData ): array {

			return [ 'pdf' => [ 'on' => Crm::featureOn( $appData, 'pdf' ), 'allowed' => \Nino\Auth::checkPermission( $appData, '/_admin/pdf/manage' ) ], 'export' => [ 'zip' => Export::zipAvailable(), 'limit' => Export::MAX_FILES ] ];
		}

		/**
		 *	Enter the two tables of the package in the file of the core
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	bool
		 */
		private static function schema( array &$appData ): bool {

			return Db::addSchema( $appData, [ 'package' => self::KEY, 'version' => 1, 'tables' => [ 'documents', 'document_lines' ], 'file' => __DIR__. '/schema.php' ] );
		}
	}
}
