<?php
declare(strict_types=1);

namespace Nino\Modules\Crm\Surface {

	/**
	 *	Surface\Menu		The entries a feature adds to the rail of /crm from PHP: a link to a page of its own below /crm, which the rail shows between the entries of the core.
	 *											A feature tells the rail in its init() with add(), as it tells the devices that stay signed in with Devices::allow(); the surface names
	 *											no feature. The rail is drawn by the script of the surface from one list of entries - the core's, those of the scripts of the features
	 *											(Nino.crm.menu.add) and these, which the page hands the script in its data block as entries with an address. The entry is a plain
	 *											link (a page of its own, not a screen of the page): it has the key of the page in data-page and no data-r, so that the script of the page -
	 *											the marking of the screen that is open, the keys - does not touch it.
	 */
	class Menu {

		// The key of the entries a feature has added, in the app data of the request
		private const string ENTRIES = './crm/surface/menu/entries';

		// The rank an entry of a feature has in the script (Nino.crm.menu.add): after the projects and before the settings
		public const int RANK = 500;

		/**
		 *	The entries of the core, as the script registers them (registerCore() in crm-boot.js, which a test holds equal to this list): key => the rank, the word (a word of the page), the hash of the page, whether the entry has a
		 *	count. The page draws the rail from it and from the entries of the features, so that it stands before the script has run
		 */
		public const array CORE = [
			'overview' => [ 10, 'nav.overview', 'overview', false ],
			'clients' => [ 30, 'nav.clients', 'clients', true ],
			'projects' => [ 40, 'nav.projects', 'projects', false ],
			'settings' => [ 900, 'nav.settings', 'settings', false ],
			'values' => [ 950, 'nav.values', 'values', false ],
		];

		/**
		 *	Add an entry to the rail. The first entry of an address or of a key stays; a second one with the same address or the same key is refused
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$route				The address of the page below the directory of the project, as Devices::allow() takes it: /crm/<name>
		 *	@param		string		$word					What the rail says, not escaped; a word of one or more characters
		 *	@param		string		$key					Lower case letters, digits and hyphens, one to forty, and none of the core's; the data-page of the link
		 *	@param		array 		$opts					package (required): the key of the package that adds the entry, see Register::package(); the route is the package's from here on
		 *
		 *	@return 	bool											Whether the entry is on the list now; false, with a warning, where it was refused
		 */
		public static function add( array &$appData, string $route, string $word, string $key, array $opts = [] ): bool {

			$bad = match( true ) {
				array_diff_key( $opts, [ 'package' => 1 ] ) !== [] => 'option',
				\Nino\Modules\Crm\Register::package( $appData, $opts['package'] ?? null ) === null => 'package',
				Devices::isRoute( $route ) === false => 'route',
				trim( $word ) === '' => 'word',
				preg_match( '/^[a-z0-9-]{1,40}$/D', $key ) !== 1 => 'key',
				// The keys of the core are its own: an entry of a feature with one would be dropped by the script
				isset( self::CORE[$key] ) === true => 'key of the core',
				array_filter( (array) ( $appData[self::ENTRIES] ?? [] ), static fn( array $entry ): bool => $entry['route'] === $route || $entry['key'] === $key ) !== [] => 'entry taken',
				// The route is the package's from here on (Devices::allow())
				Devices::claim( $appData, $route, (string) $opts['package'] ) === false => 'route taken',
				default => '',
			};

			if( $bad !== '' ) {
				trigger_error( 'Crm: the menu entry was not added ('. $bad. ')', E_USER_WARNING );

				return false;
			}

			$appData[self::ENTRIES][] = [ 'route' => $route, 'word' => $word, 'key' => $key, 'package' => (string) $opts['package'] ];

			return true;
		}

		/**
		 *	The entries as the rail shows them
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	array											route, word and key, in the order they were added
		 */
		public static function entries( array &$appData ): array {

			return array_values( (array) ( $appData[self::ENTRIES] ?? [] ) );
		}

		/**
		 *	The entries for the script of the surface, which draws the rail: the key, the word and the address of each, below the directory the project is served from
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$dir					The directory the project is served from, without the closing slash
		 *
		 *	@return 	array											key, word and href of each entry, in the order they were added
		 */
		public static function items( array &$appData, string $dir ): array {

			return array_map( static fn( array $entry ): array => [ 'key' => $entry['key'], 'word' => $entry['word'], 'href' => $dir. $entry['route'] ], self::entries( $appData ) );
		}

		/**
		 *	The links of the rail: the entries of the core and of the features by rank, and for the same rank in the order they were added, as the script draws them
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$dir					The directory the project is served from, without the closing slash
		 *	@param		array 		$texts				The words of the page
		 *
		 *	@return 	string										One anchor on a line of its own, escaped
		 */
		public static function markup( array &$appData, string $dir, array $texts ): string {

			$entries = [];

			// An entry of the core is a screen of the page (data-r), an entry of a feature a page of its own (data-page)
			foreach( self::CORE as $key => $core )
				$entries[] = [ $core[0], \Nino\Modules\Crm\Surface::fragment( $appData, 'link-core', [ 'hash' => $core[2], 'key' => $key, 'word' => \Nino\Modules\Crm\Surface::text( (string) ( $texts[$core[1]] ?? $core[1] ) ),
					'count' => $core[3] === true ? \Nino\Modules\Crm\Surface::fragment( $appData, 'link-count', [ 'key' => $key ] ) : '' ] ) ];

			foreach( self::entries( $appData ) as $entry )
				$entries[] = [ self::RANK, \Nino\Modules\Crm\Surface::fragment( $appData, 'link-page', [ 'href' => \Nino\Modules\Crm\Surface::text( $dir. $entry['route'] ), 'key' => $entry['key'], 'word' => \Nino\Modules\Crm\Surface::text( $entry['word'] ) ] ) ];

			// A stable sort: the order they were added stays for the same rank
			usort( $entries, static fn( array $a, array $b ): int => $a[0] <=> $b[0] );

			return "\n\t\t\t\t". implode( "\n\t\t\t\t", array_column( $entries, 1 ) ). "\n\t\t\t";
		}
	}
}
