<?php
declare(strict_types=1);

namespace Nino\Modules\Crm {

	/**
	 *	Company		The texts of the company - the keys below /project/company/, which the PDFs, the mails and the pages of the site show as the owner's own words - as the settings of the CRM edit them. They are read
	 *								and written through the public API of the kernel's texts (\Nino\Text::entries(), saveBatch()), never through the Text panel: text/keys answers the keys below the prefix with their values in every
	 *								language, and text/savebatch writes a batch of them, a batch with another key in it refused as a whole (404 crm_texts_key) and nothing of it written. Both ask for the permission of the Text panel
	 *								besides the CRM's, and a key the account is not allowed to write (the permissions of the panel are scoped by key) says so in its own row of the answer, as the panel's save does.
	 *
	 *	@package						Dape/Nino
	 */
	class Company {

		/**	The permission of the Text panel, and the scope of its permissions to write one key	*/
		public const string PERM = '/_admin/text/manage';
		public const string SCOPE = '/_admin/text/';
		public const string UPDATE_PERM = '/_admin/text/update';

		/**	The keys the actions are for: the company the wizard writes (name, description, address, country, email, phone)	*/
		public const string PREFIX = '/project/company/';

		/**	What a key of the company looks like: the prefix, then words of lower case letters, digits and hyphens between slashes - no dots, no spaces	*/
		private const string KEY = '~^/project/company/[a-z0-9-]+(?:/[a-z0-9-]+)*$~D';

		/**	The most items one batch holds	*/
		public const int MOST = 200;

		/**
		 *	@return 	array											action => [ class, method ]
		 */
		public static function actions(): array {

			return [ 'text/keys' => [ self::class, 'apiKeys' ], 'text/savebatch' => [ self::class, 'apiSaveBatch' ] ];
		}

		/**
		 *	The texts of the company as the settings read them, for an account that holds the permission of the CRM alone: the texts are the site's own words, so reading them is no permission of the Text
		 *	panel - the actions are for editing
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	array											keys ([ { key, global, values } ]), locales ([ { locale, active } ]), native
		 */
		public static function company( array &$appData ): array {

			$keys = [];

			foreach( \Nino\Text::entries( $appData, false ) as $entry )
				if( str_starts_with( $entry['key'], self::PREFIX ) === true )
					$keys[] = [ 'key' => $entry['key'], 'global' => $entry['global'], 'values' => $entry['values'] ];

			return [ 'keys' => $keys, 'locales' => \Nino\Modules\Crm\Values::locales( $appData ), 'native' => \Nino\Locales::getNativeLocale( $appData ) ];
		}

		/**
		 *	text/keys: the keys of the company with their values in every language, each with whether this account may write it, and the languages
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		&$request
		 *
		 *	@return 	void
		 */
		public static function apiKeys( array &$appData, array &$request ): void {

			if( \Nino\Modules\Crm\Values::permit( $appData, $request, self::PERM ) === false )
				return;

			$keys = [];

			foreach( \Nino\Text::entries( $appData, false ) as $entry )
				if( str_starts_with( $entry['key'], self::PREFIX ) === true )
					$keys[] = $entry + [ 'writable' => \Nino\Modules\Crm\Values::scoped( $appData, self::SCOPE, self::UPDATE_PERM. $entry['key'] ) ];

			\Nino\Http::ok( $request, [ 'keys' => $keys, 'locales' => \Nino\Modules\Crm\Values::locales( $appData ), 'native' => \Nino\Locales::getNativeLocale( $appData ) ] );
		}

		/**
		 *	text/savebatch: write the values of keys of the company. A batch with a key that is none of the company's is refused as a whole, before any is written; a value and a language are text, else the
		 *	batch is refused with the code crm_texts_value
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		&$request
		 *
		 *	@return 	void
		 */
		public static function apiSaveBatch( array &$appData, array &$request ): void {

			if( \Nino\Modules\Crm\Values::permit( $appData, $request, self::PERM ) === false )
				return;

			$items = \Nino\Modules\Crm\Values::posted()['items'] ?? [];

			if( is_array( $items ) === false || array_is_list( $items ) === false || count( $items ) > self::MOST ) {
				\Nino\Http::fail( $request, 400, 'the items of the batch are a list of a few', 'crm_texts_value', [], 'items' );
				return;
			}

			foreach( $items as $item ) {

				$key = is_array( $item ) === true && is_string( $item['key'] ?? null ) === true ? $item['key'] : '';

				if( preg_match( self::KEY, $key ) !== 1 ) {
					\Nino\Http::fail( $request, 404, 'these actions are for the keys below '. self::PREFIX, 'crm_texts_key' );
					return;
				}

				// The kernel reads a value and a language as text: a list or an object in their place is refused here, with nothing written
				foreach( [ 'value', 'locale' ] as $field )
					if( array_key_exists( $field, $item ) === true && is_string( $item[$field] ) === false ) {
						\Nino\Http::fail( $request, 400, 'the '. $field. ' of an item is text', 'crm_texts_value', [], 'items' );
						return;
					}
			}

			$allowed = [];
			$results = [];

			// A key this account may not write never reaches the texts, and says so in its own row: the rest of the batch is a legitimate save
			foreach( $items as $item ) {

				if( \Nino\Modules\Crm\Values::scoped( $appData, self::SCOPE, self::UPDATE_PERM. $item['key'] ) === false ) {
					$results[$item['key']] = [ 'ok' => false, 'error' => 'not allowed' ];
					continue;
				}

				$allowed[] = $item;
			}

			\Nino\Http::ok( $request, [ 'results' => $results + \Nino\Text::saveBatch( $appData, $allowed, false ) ] );

			if( $allowed !== [] )
				Api::made( $appData, 'Changed '. count( $allowed ). ' texts of the company' );
		}
	}
}
