<?php
declare(strict_types=1);

namespace Nino\Modules\CrmDates {

	/**
	 *	Api		The actions of the dates behind the endpoint of the CRM (/crm/api, entered with Api::add() under the key of the package): what the page Dates, the tab of the card of a client and the tile
	 *						of the overview read, and the things the owner does with a date.
	 *
	 *						crm-dates/list		the dates of all clients, or of one (client: its id), a number of days ahead (days, 60 by default): upcoming, past and unreadable (the dates whose start is no moment), and
	 *															where all clients are asked for the elements that carry the group of a client and are nobody's (unassigned), the state of the type, the zone the times are read in,
	 *															the moment of the site and what the account may do (can)
	 *						crm-dates/get			one date of a client with its place, its words as plain lines and its link (id: the name of the element)
	 *						crm-dates/save		make a date (no id) or change one (id): client (the id of a client), title, start, end (Y-m-dTH:i; end is '' for none), place and text (plain lines; left out, the words
	 *															of the date stay as they are). Only what differs from what the date holds in the native language is written, and where nothing does nothing is
	 *						crm-dates/delete	delete a date (id)
	 *						crm-dates/adopt		dates that carry the group of a client and are nobody's, for one group (group): deleted (mode delete; confirm is the group, typed by the owner and compared here - nothing fills it in) or given to the
	 *															client that has the group today (mode assign; client is that client's number and count the number of dates, as the dialog the owner confirmed named them, and they are
	 *															compared here: 409 crm_dates_changed where they are not what is there; a client that is switched off gets none). Deleting names the number of dates too (count), and it is
	 *															compared the same way. The group is compared without the difference of capitals
	 *						crm-dates/tidy		take the rows away whose element is not there (older than ten minutes; count is how many the owner was shown, compared here: 409 crm_dates_changed). Nothing takes them by itself
	 *
	 *						A date is one of a client and only so: it is a row of the table of the package that names it for that client AND an element whose group is the group of that client. A name that is
	 *						the element of another group, of none, of a client that is not there or of one whose row is another's answers 404 as a name that is not there does - the actions are no editor for the
	 *						type, and the Elements panel stays the place of every other date. The elements are written through the kernel (\Nino\Elements), under the permission of the panel and the scoped rights
	 *						it holds for the type (\Nino\Modules\Elements\Admin::mayInsert(), mayUpdate(), mayDelete()), field by field, so that an account that may not add, change a field or delete is refused here as
	 *						it is there; what is written is what the panel writes. Where the type is not whole (state()) nothing is read, written or deleted and the answer says why, in a code of its own for each state.
	 *
	 *	@package						Dape/Nino
	 */
	class Api {

		/**	The key of the package: the actions are named by it	*/
		public const string KEY = 'crm-dates';

		/**	The permission of the panel the elements are written through	*/
		public const string ELEMENTS = '/_admin/elements/manage';

		// The fields this package writes; the right to change each is asked as the panel asks it
		private const array FIELDS = [ 'title', 'start', 'end', 'place', 'text', 'group' ];

		/**
		 *	@return 	array											action => [ class, method ]
		 */
		public static function actions(): array {

			return [
				self::KEY. '/list' => [ self::class, 'apiList' ],
				self::KEY. '/get' => [ self::class, 'apiGet' ],
				self::KEY. '/save' => [ self::class, 'apiSave' ],
				self::KEY. '/delete' => [ self::class, 'apiDelete' ],
				self::KEY. '/adopt' => [ self::class, 'apiAdopt' ],
				self::KEY. '/tidy' => [ self::class, 'apiTidy' ],
			];
		}

		/**
		 *	The dates, by the filters: client (the id of a client) and days (how many days ahead, 1 to 3660, 60 by default)
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		&$request
		 *
		 *	@return 	void
		 */
		public static function apiList( array &$appData, array &$request ): void {

			if( self::guard( $appData, $request, false ) === false )
				return;

			$data = \Nino\Admin\Admin::postData();
			$days = $data['days'] ?? Dates::AHEAD;

			if( ( array_key_exists( 'client', $data ) === true && is_string( $data['client'] ) === false ) || is_int( $days ) === false || $days < 1 || $days > Dates::MOST ) {
				\Nino\Http::fail( $request, 400, 'the filter is not valid', 'crm_dates_format' );
				return;
			}

			self::run( $request, static function() use ( &$appData, &$request, $data, $days ): void {

				$client = null;
				$found = null;

				// The dates of all the clients are asked for by leaving the filter out: a client that is named is one, and an empty name is none
				if( array_key_exists( 'client', $data ) === true ) {

					$found = preg_match( '/^[1-9][0-9]{0,9}$/D', $data['client'] ) === 1 ? \Nino\Modules\Crm\Clients::get( $appData, (int) $data['client'] ) : null;

					if( $found === null ) {
						\Nino\Http::fail( $request, 404, 'there is no such client', 'crm_dates_client_unknown', [], 'client' );
						return;
					}

					$client = (int) $found['id'];
				}

				$list = Dates::list( $appData, $client, $days );
				$unassigned = $client === null && is_string( $list ) === false ? Dates::unassigned( $appData ) : [];
				$lonely = $client === null && is_string( $list ) === false ? Dates::lonely( $appData ) : [];

				\Nino\Http::ok( $request, self::context( $appData ) + ( is_string( $list ) === true ? [ 'upcoming' => [], 'past' => [], 'unreadable' => [] ] : $list ) + [ 'unassigned' => is_string( $unassigned ) === true ? [] : array_map( static function( array $one ) use ( &$appData ): array {

					$owner = \Nino\Modules\Crm\Clients::ofGroup( $appData, $one[0] );

					return [ 'group' => $one[0], 'count' => count( $one[1] ), 'client' => $owner === null ? null : [ 'id' => (int) $owner['id'], 'number' => (string) $owner['number'], 'title' => (string) $owner['title'], 'active' => $owner['active'] === true ] ];
				}, $unassigned ), 'lonely' => is_array( $lonely ) === true ? count( $lonely ) : 0 ] );
			} );
		}

		/**
		 *	One date of a client: id (the name of the element)
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		&$request
		 *
		 *	@return 	void
		 */
		public static function apiGet( array &$appData, array &$request ): void {

			if( self::guard( $appData, $request, false ) === false )
				return;

			$data = \Nino\Admin\Admin::postData();

			if( is_string( $data['id'] ?? null ) === false ) {
				\Nino\Http::fail( $request, 400, 'the name of the date is not valid', 'crm_dates_format', [], 'id' );
				return;
			}

			self::run( $request, static function() use ( &$appData, &$request, $data ): void {

				$date = Dates::get( $appData, $data['id'] );

				if( is_string( $date ) === true ) {
					self::unreadable( $request, $date );
					return;
				}

				if( $date === null ) {
					\Nino\Http::fail( $request, 404, 'there is no such date', 'crm_dates_unknown', [], 'id' );
					return;
				}

				\Nino\Http::ok( $request, self::context( $appData ) + [ 'date' => $date ] );
			} );
		}

		/**
		 *	Make a date, or change one of a client. The fields are checked before anything is written; the group of the date is the group of the client chosen.
		 *
		 *	Against the deletion of a client the result is always one of two orders, and the code tells which by what the key on the client and the lock of the type say:
		 *	- A date made, or given to another client (the row is written first, with a key on the client): the deletion of that client came first when the key refuses the row - nothing was touched, the answer is 409 and
		 *	  the date is as it was (crm_dates_client_gone for a date that was not made, crm_dates_client_gone_changed for one that stays where it was). Otherwise the row stands and the deletion of the client comes
		 *	  after it, takes the row in its transaction - it reads its names there, so it has this one - and the element after it: the date was the client's and goes with it. The write of the element is made under
		 *	  the lock of the type after a look at the file; where the element is gone the answer is 409 crm_dates_client_gone_removed when the client is gone, and 404 where it is not (another hand deleted it); where it
		 *	  was written and the client is gone, the element is deleted here if the deletion has not taken it (crm_dates_client_gone_removed, or crm_dates_client_gone for a date that was made).
		 *	- A date changed where it is (no row is written): the deletion came first when the look at the file under the lock finds no element - 409 crm_dates_client_gone_removed where the client is gone -, or after the write -
		 *	  the same answer, and the element is deleted here if it is still there. A date that another hand deleted is 404 and nothing is written: the write of the kernel makes an element that is not there, and the
		 *	  lock is let go by the write itself, so nothing is held after it and nothing is taken back once it has been written.
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		&$request
		 *
		 *	@return 	void
		 */
		public static function apiSave( array &$appData, array &$request ): void {

			if( self::guard( $appData, $request, true ) === false )
				return;

			$read = self::read( $request, \Nino\Admin\Admin::postData() );

			if( $read === null )
				return;

			self::run( $request, static function() use ( &$appData, &$request, $read ): void {

				$state = Dates::state( $appData );

				if( $state !== 'ok' ) {
					self::unreadable( $request, $state );
					return;
				}

				$id = $read['id'];
				$before = $id === '' ? null : Dates::get( $appData, $id );

				if( is_string( $before ) === true ) {
					self::unreadable( $request, $before );
					return;
				}

				if( $id !== '' && $before === null ) {
					\Nino\Http::fail( $request, 404, 'there is no such date', 'crm_dates_unknown', [], 'id' );
					return;
				}

				$client = preg_match( '/^[1-9][0-9]{0,9}$/D', $read['client'] ) === 1 ? \Nino\Modules\Crm\Clients::get( $appData, (int) $read['client'] ) : null;

				if( $client === null ) {
					\Nino\Http::fail( $request, 400, 'the client is none', 'crm_dates_client_unknown', [], 'client' );
					return;
				}

				$group = Dates::groupOf( $client );
				$moves = $before === null || $before['client_id'] !== (int) $client['id'];

				// A date is made for a client that is on, and moved to one; one that stays with the client it has is changed whether or not the client is switched on
				if( $moves === true && $client['active'] !== true ) {
					\Nino\Http::fail( $request, 400, 'the client is switched off', 'crm_dates_client_off', [], 'client' );
					return;
				}

				if( self::moments( $appData, $request, $read ) === null )
					return;

				// What differs from what the date holds in the native language, the language the form shows and this writes: the rest is not written, and a date that changes nothing is not touched
				$new = [ 'title' => $read['title'], 'start' => $read['start'], 'end' => $read['end'], 'place' => $read['place'] ];
				$fields = $before === null ? $new : array_filter( $new, static fn( string $value, string $field ): bool => $before[$field] !== $value, ARRAY_FILTER_USE_BOTH );

				if( $read['text'] !== null ) {

					$text = \Nino\Html::sanitizeHtml( Dates::paragraphs( $appData, $read['text'] ), \Nino\Html::fieldFormat( \Nino\Elements::getElementModel( $appData, Dates::TYPE )['text'] ?? [] ) );

					if( $before === null || Dates::plain( $text ) !== $before['text'] )
						$fields['text'] = $text;
				}

				if( $moves === true )
					$fields['group'] = $group;

				if( $before !== null && $fields === [] ) {
					\Nino\Http::ok( $request, self::context( $appData ) + [ 'date' => $before ] );
					return;
				}

				// The panel asks the right to add and then the right to change each field that is posted, for a new element as for one that is changed
				if( ( $before === null && \Nino\Modules\Elements\Admin::mayInsert( $appData, ltrim( Dates::TYPE, '/' ) ) === false ) || self::denied( $appData, array_keys( $fields ) ) !== [] ) {
					\Nino\Http::fail( $request, 403, 'not allowed to change the date', 'crm_dates_denied' );
					return;
				}

				$native = \Nino\Locales::getNativeLocale( $appData );
				$uri = $before === null ? self::name( $appData, $read['start'] ) : $id;

				if( $uri === '' ) {
					\Nino\Http::fail( $request, 500, 'the date could not be written', 'crm_dates_failed' );
					return;
				}

				// The row first: an element that is written with no row is nobody's, a row with no element is nothing
				try {
					if( $moves === true )
						\Nino\Modules\Crm\Db::exec( $appData, 'INSERT INTO '. Dates::TABLE. ' ( name, client_id, created ) VALUES ( :name, :client, :now ) ON CONFLICT( name ) DO UPDATE SET client_id = excluded.client_id', [ 'now' => time(), 'name' => $uri, 'client' => (int) $client['id'] ] );
				}
				catch( \PDOException $e ) {

					// The key on the client refuses a row for a client that was deleted since it was read
					if( \Nino\Modules\Crm\Clients::get( $appData, (int) $client['id'] ) !== null )
						throw $e;

					// The deletion of the client came first: nothing was touched, the date is as it was
					\Nino\Http::fail( $request, 409, 'the client is not there any more', $before === null ? 'crm_dates_client_gone' : 'crm_dates_client_gone_changed', [], 'client' );
					return;
				}

				// A date that is changed is written under the lock of the type, after a look at the file under it: the write of the kernel makes an element that is not there, so a date that another request deleted
				// since it was read is not written at all
				$stored = $before === null ? true : Dates::stored( $appData, $uri );

				if( $stored !== true ) {

					if( $moves === true )
						self::restore( $appData, $uri, null );

					// The element is gone: with the client it was given to - whose deletion took the row this save made and the element after it - or by another hand
					if( $stored === false && \Nino\Modules\Crm\Clients::get( $appData, (int) $client['id'] ) === null )
						\Nino\Http::fail( $request, 409, 'the client was deleted, and the date with it', 'crm_dates_client_gone_removed', [], 'client' );
					elseif( $stored === null )
						\Nino\Http::fail( $request, 500, 'the date could not be written', 'crm_dates_failed' );
					else
						\Nino\Http::fail( $request, 404, 'there is no such date', 'crm_dates_unknown', [], 'id' );

					return;
				}

				$sentence = null;
				set_error_handler( static function( int $errno, string $message ) use ( &$sentence ): bool {
					$sentence = $message;
					return true;
				} );

				try {
					$written = $before === null ? \Nino\Elements::insertElement( $appData, Dates::TYPE. '/'. $uri, $fields, $native ) : \Nino\Elements::updateElement( $appData, Dates::TYPE. '/'. $uri, $fields, $native );
				}
				finally {
					restore_error_handler();
				}

				if( is_array( $written ) === false ) {

					if( $moves === true )
						self::restore( $appData, $uri, $before );

					$refused = \Nino\Elements::refusals( $appData );

					if( $refused !== [] )
						\Nino\Http::fail( $request, 409, (string) $refused[0]['error'], 'crm_dates_vetoed', [ (string) $refused[0]['error'] ] );
					else
						\Nino\Http::fail( $request, 500, $sentence ?? 'the date could not be written', 'crm_dates_failed' );

					return;
				}

				$date = Dates::get( $appData, $uri );

				if( is_array( $date ) === false ) {

					// The client was deleted after the row was made: the date was the client's and goes with it. The deletion takes the element after its database step, and where it has not yet, this deletes it
					if( \Nino\Modules\Crm\Clients::get( $appData, (int) $client['id'] ) === null ) {
						self::gone( $appData, $request, $uri, $before === null );
						return;
					}

					\Nino\Http::fail( $request, 500, 'the date could not be read again', 'crm_dates_failed' );
					return;
				}

				\Nino\Modules\Crm\Api::made( $appData, ( $before === null ? 'Made' : 'Changed' ). ' the date '. $uri. ' of client '. $client['number'] );
				\Nino\Http::ok( $request, self::context( $appData ) + [ 'date' => $date ] );
			} );
		}

		/**
		 *	Delete a date of a client: id
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		&$request
		 *
		 *	@return 	void
		 */
		public static function apiDelete( array &$appData, array &$request ): void {

			if( self::guard( $appData, $request, true ) === false )
				return;

			$data = \Nino\Admin\Admin::postData();

			if( is_string( $data['id'] ?? null ) === false ) {
				\Nino\Http::fail( $request, 400, 'the name of the date is not valid', 'crm_dates_format', [], 'id' );
				return;
			}

			self::run( $request, static function() use ( &$appData, &$request, $data ): void {

				$date = Dates::get( $appData, $data['id'] );

				if( is_string( $date ) === true ) {
					self::unreadable( $request, $date );
					return;
				}

				if( $date === null ) {
					\Nino\Http::fail( $request, 404, 'there is no such date', 'crm_dates_unknown', [], 'id' );
					return;
				}

				if( \Nino\Modules\Elements\Admin::mayDelete( $appData, ltrim( Dates::TYPE, '/' ) ) === false ) {
					\Nino\Http::fail( $request, 403, 'not allowed to delete the date', 'crm_dates_denied' );
					return;
				}

				if( self::erase( $appData, $request, $date['id'] ) === false )
					return;

				\Nino\Modules\Crm\Api::made( $appData, 'Deleted the date '. $date['id']. ' of client '. $date['client'] );
				\Nino\Http::ok( $request, self::context( $appData ) );
			} );
		}

		/**
		 *	The elements of a group that carry the group of a client and are nobody's, given to the client that has the group today, or deleted: group, mode (assign or delete) and confirm, which has to be the group
		 *	again. Never by itself, never for a group that is not asked by name, and no element that is a client's is touched
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		&$request
		 *
		 *	@return 	void
		 */
		public static function apiAdopt( array &$appData, array &$request ): void {

			if( self::guard( $appData, $request, true ) === false )
				return;

			$data = \Nino\Admin\Admin::postData();
			$group = $data['group'] ?? null;
			$mode = $data['mode'] ?? null;

			if( is_string( $group ) === false || is_string( $mode ) === false || in_array( $mode, [ 'assign', 'delete' ], true ) === false
				|| ( $mode === 'delete' && ( is_string( $data['confirm'] ?? null ) === false || is_int( $data['count'] ?? null ) === false ) ) || ( $mode === 'assign' && ( is_string( $data['client'] ?? null ) === false || is_int( $data['count'] ?? null ) === false ) ) ) {
				\Nino\Http::fail( $request, 400, 'the group and what is done with it are not valid', 'crm_dates_format', [], 'group' );
				return;
			}

			// Deleting is said by typing the group, in capitals or not: the server compares, nothing fills it in
			if( preg_match( \Nino\Modules\Crm\Clients::GROUP, $group ) !== 1 || ( $mode === 'delete' && mb_strtolower( $data['confirm'], 'UTF-8' ) !== $group ) ) {
				\Nino\Http::fail( $request, 400, 'the group is not said as it is', 'crm_dates_confirm', [ $group ], 'group' );
				return;
			}

			self::run( $request, static function() use ( &$appData, &$request, $group, $mode, $data ): void {

				$list = Dates::unassigned( $appData );

				if( is_string( $list ) === true ) {
					self::unreadable( $request, $list );
					return;
				}

				$names = null;

				foreach( $list as [ $one, $found ] )
					if( $one === $group )
						$names = $found;

				if( $names === null ) {
					\Nino\Http::fail( $request, 404, 'there are no such dates', 'crm_dates_group_unknown', [], 'group' );
					return;
				}

				$type = ltrim( Dates::TYPE, '/' );

				if( $mode === 'assign' ) {

					$client = \Nino\Modules\Crm\Clients::ofGroup( $appData, $group );

					if( $client === null ) {
						\Nino\Http::fail( $request, 404, 'there is no client of this group', 'crm_dates_client_unknown', [], 'group' );
						return;
					}

					// A date is not given to a client that is switched off, as a save does not make one for it
					if( $client['active'] !== true ) {
						\Nino\Http::fail( $request, 400, 'the client is switched off', 'crm_dates_client_off', [], 'group' );
						return;
					}

					// What the owner was asked is what is done: the client that has the group today and the number of dates, as the dialog said them
					if( $data['client'] !== (string) $client['number'] || $data['count'] !== count( $names ) ) {
						\Nino\Http::fail( $request, 409, 'the dates or the client are not what the owner was shown', 'crm_dates_changed', [], 'group' );
						return;
					}

					if( \Nino\Modules\Elements\Admin::mayUpdate( $appData, $type, 'group' ) === false ) {
						\Nino\Http::fail( $request, 403, 'not allowed to change the group of a date', 'crm_dates_denied' );
						return;
					}

					// One statement for all of them: a client that is deleted while it runs refuses it by the key, and no date is given to anybody - the dates stay as they were, nobody's
					$marks = [];
					$values = [ 'now' => time(), 'client' => (int) $client['id'] ];

					foreach( array_values( $names ) as $at => $name ) {
						$marks[] = '( :n'. $at. ', :client, :now )';
						$values['n'. $at] = $name;
					}

					try {
						\Nino\Modules\Crm\Db::exec( $appData, 'INSERT INTO '. Dates::TABLE. ' ( name, client_id, created ) VALUES '. implode( ', ', $marks ). ' ON CONFLICT( name ) DO UPDATE SET client_id = excluded.client_id', $values );
					}
					catch( \PDOException $e ) {

						if( \Nino\Modules\Crm\Clients::get( $appData, (int) $client['id'] ) !== null )
							throw $e;

						\Nino\Http::fail( $request, 409, 'the client is not there any more', 'crm_dates_assign_gone', [], 'group' );
						return;
					}

					\Nino\Modules\Crm\Api::made( $appData, 'Gave '. count( $names ). ' dates of the group '. $group. ' to client '. $client['number'] );
				}
				else {

					if( $data['count'] !== count( $names ) ) {
						\Nino\Http::fail( $request, 409, 'the dates are not what the owner was shown', 'crm_dates_changed', [], 'group' );
						return;
					}

					if( \Nino\Modules\Elements\Admin::mayDelete( $appData, $type ) === false ) {
						\Nino\Http::fail( $request, 403, 'not allowed to delete the dates', 'crm_dates_denied' );
						return;
					}

					foreach( $names as $name )
						if( self::erase( $appData, $request, $name ) === false )
							return;

					\Nino\Modules\Crm\Api::made( $appData, 'Deleted '. count( $names ). ' dates of the group '. $group );
				}

				\Nino\Http::ok( $request, self::context( $appData ) + [ 'adopted' => count( $names ), 'mode' => $mode ] );
			} );
		}

		/**
		 *	Take the rows away that name no element: only on the word of the owner, who is shown how many they are
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		&$request
		 *
		 *	@return 	void
		 */
		public static function apiTidy( array &$appData, array &$request ): void {

			if( self::guard( $appData, $request, true ) === false )
				return;

			$data = \Nino\Admin\Admin::postData();

			if( is_int( $data['count'] ?? null ) === false || $data['count'] < 1 ) {
				\Nino\Http::fail( $request, 400, 'the number of rows is not valid', 'crm_dates_format', [], 'count' );
				return;
			}

			self::run( $request, static function() use ( &$appData, &$request, $data ): void {

				// What the surface shows the button for: the right to delete the dates of the type
				if( \Nino\Modules\Elements\Admin::mayDelete( $appData, ltrim( Dates::TYPE, '/' ) ) === false ) {
					\Nino\Http::fail( $request, 403, 'not allowed to delete dates', 'crm_dates_denied' );
					return;
				}

				$done = Dates::tidy( $appData, $data['count'] );

				if( $done === 'changed' ) {
					\Nino\Http::fail( $request, 409, 'the rows are not what the owner was shown', 'crm_dates_changed', [], 'count' );
					return;
				}

				if( is_string( $done ) === true ) {
					self::unreadable( $request, $done );
					return;
				}

				\Nino\Modules\Crm\Api::made( $appData, 'Took '. $done. ' rows of dates that are not there away' );
				\Nino\Http::ok( $request, self::context( $appData ) + [ 'tidied' => $done ] );
			} );
		}

		/**
		 *	Delete an element and its row: the element first, so that a row that stays names nothing
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		&$request
		 *	@param		string		$name
		 *
		 *	@return 	bool											False after the answer that says why not
		 */
		private static function erase( array &$appData, array &$request, string $name ): bool {

			$sentence = null;
			set_error_handler( static function( int $errno, string $message ) use ( &$sentence ): bool {
				$sentence = $message;
				return true;
			} );

			try {
				$deleted = \Nino\Elements::deleteElement( $appData, Dates::TYPE. '/'. $name, '*' );
			}
			finally {
				restore_error_handler();
			}

			// A listener that holds a date back answers false and gives no sentence; an error the kernel raised is a failure
			if( $deleted !== true || $sentence !== null ) {

				if( $sentence !== null )
					\Nino\Http::fail( $request, 500, $sentence, 'crm_dates_failed' );
				else
					\Nino\Http::fail( $request, 409, 'the date is held back', 'crm_dates_kept' );

				return false;
			}

			\Nino\Modules\Crm\Db::exec( $appData, 'DELETE FROM '. Dates::TABLE. ' WHERE name = :name', [ 'name' => $name ] );

			return true;
		}

		/**
		 *	Put a row back as it was where the element could not be written: the row of a date that was made is taken away, the row of one that was moved names the client it had
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$name
		 *	@param		array|null	$before				The date as it was; null for one that was made
		 *
		 *	@return 	void
		 */
		private static function restore( array &$appData, string $name, ?array $before ): void {

			if( $before === null ) {
				\Nino\Modules\Crm\Db::exec( $appData, 'DELETE FROM '. Dates::TABLE. ' WHERE name = :name', [ 'name' => $name ] );
				return;
			}

			// The row may be gone with the client it was moved to; the client it had may be gone too, and then the key refuses a row for it and there is none to put back
			try {
				\Nino\Modules\Crm\Db::exec( $appData, 'INSERT INTO '. Dates::TABLE. ' ( name, client_id, created ) VALUES ( :name, :client, :now ) ON CONFLICT( name ) DO UPDATE SET client_id = excluded.client_id', [ 'now' => time(), 'name' => $name, 'client' => $before['client_id'] ] );
			}
			catch( \PDOException $e ) {

				if( \Nino\Modules\Crm\Clients::get( $appData, (int) $before['client_id'] ) !== null )
					throw $e;
			}
		}

		/**
		 *	The answer of a save whose client was deleted after the row of the date was made: the date was the client's, and it goes with the client - one that was made and one that was changed alike. It is never written
		 *	back and never made anew. Where the element is there the deletion of the client has not yet taken it, and it is deleted here, under the lock of the type; where that does not work the answer is a 500 that
		 *	names the date, with a warning: the date stands with the group of the client that is gone, so it is nobody's and is not public
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		&$request
		 *	@param		string		$uri
		 *	@param		bool			$made					Whether the save made the date
		 *
		 *	@return 	void
		 */
		private static function gone( array &$appData, array &$request, string $uri, bool $made ): void {

			$outcome = self::takeBack( $appData, $uri );

			// The rows of the client went with it; a row of this date that is left names nothing
			if( $outcome === 'removed' )
				self::restore( $appData, $uri, null );

			if( $outcome === 'stuck' ) {

				trigger_error( 'CrmDates: the date '. $uri. ' of a client that is gone could not be deleted: it stands as the save left it', E_USER_WARNING );
				\Nino\Http::fail( $request, 500, 'the client is not there any more and the date could not be deleted', 'crm_dates_client_gone_stuck', [ $uri ], 'client' );

				return;
			}

			\Nino\Http::fail( $request, 409, 'the client is not there any more', $made === true ? 'crm_dates_client_gone' : 'crm_dates_client_gone_removed', [], 'client' );
		}

		/**
		 *	Delete the element of a date, under the lock of the type: the look at the file and the deletion are one hold of the lock, and what the look finds is what is deleted
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$uri
		 *
		 *	@return 	string										removed (no element is left) or stuck (an element is left that could not be deleted)
		 */
		private static function takeBack( array &$appData, string $uri ): string {

			// What is in the file under the lock, not what this request has read: the request that deleted the client may have taken the element in the meantime
			if( Dates::stored( $appData, $uri ) === false )
				return 'removed';

			// What the kernel says of a write that fails is of no use here: the answer is the state the file is read in afterwards
			set_error_handler( static fn(): bool => true );

			try {
				$deleted = \Nino\Elements::deleteElement( $appData, Dates::TYPE. '/'. $uri, '*' );
			}
			finally {
				restore_error_handler();
			}

			return $deleted === true && Dates::stored( $appData, $uri ) === false ? 'removed' : 'stuck';
		}

		/**
		 *	The guard of every action: the permission of the CRM and, for what writes, the one of the Elements panel - each 403 as the workbench gives it
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		&$request
		 *	@param		bool			$write
		 *
		 *	@return 	bool
		 */
		private static function guard( array &$appData, array &$request, bool $write ): bool {

			if( \Nino\Admin\Admin::guardPerm( $appData, $request, \Nino\Modules\Crm::MANAGE_PERM ) === false )
				return false;

			return $write === false || \Nino\Admin\Admin::guardPerm( $appData, $request, self::ELEMENTS ) === true;
		}

		/**
		 *	Run an action over the database: a failure of it is a 500 with the code crm_database
		 *
		 *	@param		array 		&$request
		 *	@param		callable	$run
		 *
		 *	@return 	void
		 */
		private static function run( array &$request, callable $run ): void {

			try {
				$run();
			}
			catch( \PDOException $e ) {
				\Nino\Http::fail( $request, 500, 'the database failed', 'crm_database' );
			}
		}

		/**
		 *	What every answer carries: the state of the type, the language the words are written in, the moment of the site and the zone the times are read in, and what the account may do
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	array
		 */
		private static function context( array &$appData ): array {

			$state = Dates::state( $appData );

			return [
				'state'		=> $state,
				'native'	=> \Nino\Locales::getNativeLocale( $appData ),
				'now'			=> $state === 'ok' ? \Nino\Modules\Events::now( $appData )->format( 'Y-m-d\TH:i' ) : '',
				'zone'		=> $state === 'ok' ? \Nino\Modules\Events::zone( $appData )->getName() : '',
				'can'			=> self::can( $appData ),
			];
		}

		/**
		 *	What the account may do with the dates: it holds the permission of the Elements panel and, where that panel is described in detail for it, the scoped right for each thing
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	array											insert (the right to add and to change every field a new date has), update (every field this package writes) and delete, each a bool
		 */
		public static function can( array &$appData ): array {

			if( \Nino\Auth::checkPermission( $appData, self::ELEMENTS ) === false )
				return [ 'insert' => false, 'update' => false, 'delete' => false ];

			$type = ltrim( Dates::TYPE, '/' );
			$update = self::denied( $appData, self::FIELDS ) === [];

			return [ 'insert' => $update === true && \Nino\Modules\Elements\Admin::mayInsert( $appData, $type ), 'update' => $update, 'delete' => \Nino\Modules\Elements\Admin::mayDelete( $appData, $type ) ];
		}

		/**
		 *	The fields of a write that the scoped rights of the Elements panel refuse
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		$fields
		 *
		 *	@return 	array											The fields that are refused
		 */
		private static function denied( array &$appData, array $fields ): array {

			$denied = [];

			foreach( $fields as $field )
				if( \Nino\Modules\Elements\Admin::mayUpdate( $appData, ltrim( Dates::TYPE, '/' ), (string) $field ) === false )
					$denied[] = (string) $field;

			return $denied;
		}

		/**
		 *	What a post of a save says, checked for its shape and for the text of the fields
		 *
		 *	@param		array 		&$request
		 *	@param		array 		$data
		 *
		 *	@return 	array|null								id, client, title, start, end, place, text (null where it is left out); null after the 400 that says what is wrong
		 */
		private static function read( array &$request, array $data ): ?array {

			foreach( [ 'client', 'title', 'start', 'end', 'place' ] as $field )
				if( is_string( $data[$field] ?? null ) === false ) {
					\Nino\Http::fail( $request, 400, 'the field '. $field. ' is not text', 'crm_dates_format', [], $field );
					return null;
				}

			foreach( [ 'id', 'text' ] as $field )
				if( array_key_exists( $field, $data ) === true && $data[$field] !== null && is_string( $data[$field] ) === false ) {
					\Nino\Http::fail( $request, 400, 'the field '. $field. ' is not text', 'crm_dates_format', [], $field );
					return null;
				}

			$read = [ 'id' => (string) ( $data['id'] ?? '' ), 'client' => $data['client'], 'title' => trim( $data['title'] ), 'start' => $data['start'], 'end' => $data['end'], 'place' => trim( $data['place'] ), 'text' => isset( $data['text'] ) === true ? $data['text'] : null ];

			if( $read['id'] !== '' && preg_match( Dates::ID, $read['id'] ) !== 1 ) {
				\Nino\Http::fail( $request, 404, 'there is no such date', 'crm_dates_unknown', [], 'id' );
				return null;
			}

			if( $read['title'] === '' || mb_strlen( $read['title'], 'UTF-8' ) > Dates::TITLE || Dates::line( $read['title'] ) === false ) {
				\Nino\Http::fail( $request, 400, 'the title is one line of at most '. Dates::TITLE. ' characters', 'crm_dates_title', [ Dates::TITLE ], 'title' );
				return null;
			}

			if( mb_strlen( $read['place'], 'UTF-8' ) > Dates::PLACE || Dates::line( $read['place'] ) === false ) {
				\Nino\Http::fail( $request, 400, 'the place is one line of at most '. Dates::PLACE. ' characters', 'crm_dates_place', [ Dates::PLACE ], 'place' );
				return null;
			}

			if( $read['text'] !== null && ( mb_strlen( $read['text'], 'UTF-8' ) > Dates::TEXT || preg_match( '//u', $read['text'] ) !== 1 || preg_match( '/[\x{0}-\x{8}\x{B}\x{C}\x{E}-\x{1F}\x{7F}-\x{9F}\x{2028}\x{2029}\x{202A}-\x{202E}\x{2066}-\x{2069}]/u', $read['text'] ) === 1 ) ) {
				\Nino\Http::fail( $request, 400, 'the words are at most '. Dates::TEXT. ' characters', 'crm_dates_text', [ Dates::TEXT ], 'text' );
				return null;
			}

			return $read;
		}

		/**
		 *	The moments of a save, read in the zone of the site: the start is a moment that exists, so is the end where there is one, and the end is after the start
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		&$request
		 *	@param		array 		$read
		 *
		 *	@return 	array|null								start and end (\DateTimeImmutable; the end null for none); null after the 400 that says what is wrong
		 */
		private static function moments( array &$appData, array &$request, array $read ): ?array {

			$start = Dates::moment( $appData, $read['start'] );

			if( is_string( $start ) === true ) {
				\Nino\Http::fail( $request, 400, $start === 'gap' ? 'the time does not exist in the zone of the site' : 'the start is no moment', $start === 'gap' ? 'crm_dates_gap' : 'crm_dates_start', [], 'start-time' );
				return null;
			}

			if( $read['end'] === '' )
				return [ 'start' => $start, 'end' => null ];

			$end = Dates::moment( $appData, $read['end'] );

			if( is_string( $end ) === true ) {
				\Nino\Http::fail( $request, 400, $end === 'gap' ? 'the time does not exist in the zone of the site' : 'the end is no moment', $end === 'gap' ? 'crm_dates_gap' : 'crm_dates_end', [], 'end-time' );
				return null;
			}

			if( $end <= $start ) {
				\Nino\Http::fail( $request, 400, 'the end is not after the start', 'crm_dates_end', [], 'end-time' );
				return null;
			}

			return [ 'start' => $start, 'end' => $end ];
		}

		/**
		 *	The name a new date is made under: on, the day it begins and a few letters at random - what the feed serves it by (lower-case letters, digits and hyphens) - that no element has
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$start				Y-m-dTH:i
		 *
		 *	@return 	string										'' where no free name was found
		 */
		private static function name( array &$appData, string $start ): string {

			for( $try = 0; $try < 5; $try++ ) {

				$rest = '';

				while( strlen( $rest ) < 6 )
					$rest .= base_convert( (string) random_int( 0, 35 ), 10, 36 );

				$name = 'on-'. substr( $start, 0, 4 ). substr( $start, 5, 2 ). substr( $start, 8, 2 ). '-'. $rest;

				if( \Nino\Elements::getElement( $appData, Dates::TYPE. '/'. $name, '*' ) === false )
					return $name;
			}

			return '';
		}


		/**
		 *	The answer for dates that cannot be trusted: 409, in the code of the state
		 *
		 *	@param		array 		&$request
		 *	@param		string		$state				off, missing or broken
		 *
		 *	@return 	void
		 */
		private static function unreadable( array &$request, string $state ): void {

			\Nino\Http::fail( $request, 409, 'the dates cannot be read: '. $state, 'crm_dates_'. $state );
		}
	}
}
