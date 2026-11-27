<?php
declare(strict_types=1);
/**
 *	Nino								A compact filesystembased php framework
 *	Modules\CrmDates		see _nino/Nino/Modules/Modules.php for the
 *											package-level docblock
 *
 *	@package						Dape/Nino
 *	@author							David Perchermeier <mail@dape.io>
 *	@link								https://github.com/dapeio/nino
 */
namespace Nino\Modules {

	/**
	 *	Nino							A compact filesystembased php framework
	 *	CrmDates					The dates of the clients in the surface of the CRM. A date is an element of the type /events of the feature Events - the one place that feature keeps its dates, which its feed, its list
	 *										and its chip read - filed under the group of its client (the client's number as a slug, k-0003), and the package lists, makes, changes and deletes the dates of a client and no other
	 *										element of the type: the elements of no group and of the groups of Members stay in the Elements panel. What it keeps of its own is one table in the database of the CRM, the name of each
	 *										element and the id of its client (schema.php): a date is a client's only where the table and the group of the element agree, so that the package being off or removed is a fact the core
	 *										sees - it keeps the deletion of a client and the test phase from going on while rows are there - and a number that is given to another client inherits nothing.
	 *
	 *										The package docks to the core with the places the core offers and names no other package: the actions crm-dates/list, get, save and delete (Api), the line of the status for the type of
	 *										Events (Status), what goes with a client that is deleted and with the test phase (Erasure), the part of the overview (Overview), its script, style sheet and words of the surface, and its
	 *										part of the data of the page. It listens to /events/organizer, so that a calendar file says whom the dates are with (Organizer). Where the type of Events is missing or broken the package
	 *										reads, writes and deletes nothing, says so, and holds back the deletion of a client, whose dates it cannot find.
	 *
	 *										The page of the dates in the portal of the customer is the half that waits for the feature crm-portal: this feature stands without it and has no page and no route of its own.
	 *
	 *	@package					Dape/Nino
	 *	@author						David Perchermeier <mail@dape.io>
	 *	@link							https://github.com/dapeio/nino
	 */
	class CrmDates {

		/**	The key of the feature	*/
		public const string KEY = 'crm-dates';

		/**
		 *	Dock to the core: the actions, the line of the status, the steps of the deletion of a client and of the test phase, and the words, the script and the data of the surface. A package begins with the
		 *	question whether the core is on. Nothing is read or written here
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	void
		 */
		public static function init( array &$appData ): void {

			// The core is on, and so is this package: where a package it requires is not installed it has a problem and is on for nobody, and a call to the places of the core would be refused with a warning for each request
			if( \Nino\Modules\Crm::featureOn( $appData, 'crm' ) === false || \Nino\Modules\Crm::featureOn( $appData, self::KEY ) === false )
				return;

			$package = [ 'package' => self::KEY ];

			\Nino\Modules\Crm\Api::add( $appData, \Nino\Modules\CrmDates\Api::actions(), $package );

			\Nino\Modules\Crm\Db::addSchema( $appData, $package + [ 'version' => 1, 'tables' => [ \Nino\Modules\CrmDates\Dates::TABLE ], 'file' => __DIR__. '/schema.php' ] );

			\Nino\Modules\Crm\Status::add( $appData, $package + [ 'key' => \Nino\Modules\CrmDates\Status::KEY, 'rank' => 520, 'state' => [ \Nino\Modules\CrmDates\Status::class, 'state' ] ] );
			\Nino\Modules\Crm\Status::add( $appData, $package + [ 'key' => \Nino\Modules\CrmDates\Status::UNASSIGNED, 'rank' => 521, 'hint' => true, 'state' => [ \Nino\Modules\CrmDates\Status::class, 'unassigned' ] ] );

			\Nino\Modules\Crm\Erasure::add( $appData, $package + [ 'key' => \Nino\Modules\CrmDates\Erasure::KEY, 'count' => [ \Nino\Modules\CrmDates\Erasure::class, 'count' ], 'gather' => [ \Nino\Modules\CrmDates\Erasure::class, 'gather' ],
				'rows' => [ \Nino\Modules\CrmDates\Erasure::class, 'rows' ], 'finish' => [ \Nino\Modules\CrmDates\Erasure::class, 'finish' ], 'perm' => \Nino\Modules\CrmDates\Api::ELEMENTS ] );
			\Nino\Modules\Crm\Wipe::add( $appData, $package + [ 'key' => \Nino\Modules\CrmDates\Erasure::KEY, 'count' => true, 'rows_before' => 'clients', 'rows' => [ \Nino\Modules\CrmDates\Erasure::class, 'rowsAll' ],
				'gather' => [ \Nino\Modules\CrmDates\Erasure::class, 'gatherAll' ], 'leftover' => [ \Nino\Modules\CrmDates\Erasure::class, 'leftover' ], 'perm' => \Nino\Modules\CrmDates\Api::ELEMENTS ] );

			\Nino\Modules\Crm\Surface\Texts::add( $appData, __DIR__. '/Surface/text', $package );
			\Nino\Modules\Crm\Surface\Scripts::addStyle( $appData, 'crm-dates.css', __DIR__. '/Surface/assets/crm-dates.css', $package );
			\Nino\Modules\Crm\Surface\Scripts::add( $appData, 'crm-dates.js', __DIR__. '/Surface/assets/crm-dates.js', $package );
			\Nino\Modules\Crm\Surface\Data::add( $appData, [ \Nino\Modules\CrmDates\Surface::class, 'data' ], $package );

			\Nino\Callbacks::registerCallback( $appData, '/crm/overview', [ \Nino\Modules\CrmDates\Overview::class, 'callbackOverview' ] );
			\Nino\Callbacks::registerCallback( $appData, '/crm/client/holds', [ \Nino\Modules\CrmDates\Erasure::class, 'callbackHolds' ] );
			\Nino\Callbacks::registerCallback( $appData, '/events/organizer', [ \Nino\Modules\CrmDates\Organizer::class, 'callbackOrganizer' ] );
		}
	}
}
