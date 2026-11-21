<?php
declare(strict_types=1);
/**
 *	Nino								A compact filesystembased php framework
 *	Modules\Themes\Mix	see _nino/Nino/Modules/Modules.php for the
 *											package-level docblock
 *
 *	@package						Dape/Nino
 *	@author							David Perchermeier <mail@dape.io>
 *	@link								https://github.com/dapeio/nino
 */
namespace Nino\Modules\Themes {

	/**
	 *	Nino							A compact filesystembased php framework
	 *	Modules\Themes		The mix: which theme each group takes its part from, which variants stand where a page names none and
	 *										which step each knob is on - and the free css of the developer
	 *
	 *										{ "name": "Lindner", "css": "...", "parts": {
	 *											"page":    { "from": "nino@2.0.0", "values": { "radius": 1 } },
	 *											"section": { "from": "craft@1.0.0", "standard": { "section-wrap": "soft" }, "values": { "section-wrap.space": 2 } },
	 *											"button":  { "from": "standard" } } }
	 *
	 *										A step is -2 to +2 and 0 is the middle one, which is left out; the brand is not in it (M5). Where a
	 *										group changes its part, the steps of knobs of the same name stay and every other knob begins at its
	 *										middle step: clean() keeps what the part has a knob for and drops the rest.
	 *
	 *	@package					Dape/Nino
	 *	@author						David Perchermeier <mail@dape.io>
	 *	@link							https://github.com/dapeio/nino
	 */
	class Mix {

		// What a part is taken from: the standard part of the group, an installed theme or a version of one the project holds a copy of, or an own theme - own:<key> -
		// which holds its parts in full
		private const string FROM = '/^(?:standard|own:([a-z0-9]+(?:-[a-z0-9]+)*)|([a-z0-9]+(?:-[a-z0-9]+)*)(?:@(\d{1,4}\.\d{1,4}\.\d{1,4}(?:-[A-Za-z0-9.]{1,20})?))?)$/D';
		private const string KNOB = '/^[a-z][a-z0-9]*(?:-[a-z0-9]+)*(?:\.[a-z][a-z0-9]*(?:-[a-z0-9]+)*)?$/D';

		// How much a mix may say: the longest name of an own theme, the longest css, the most groups
		public const int NAME_LENGTH = 60;
		public const int CSS_LENGTH = 65536;
		private const int GROUPS = 40;

		// What is left in the folder of the own themes by a change that was broken off, as a name: .<key>.new-<8 hex> a folder that was being written, .<key>.old a folder that was put
		// aside, .<key>.gone-<8 hex> one that was being taken away - the key is one a theme has, a slug that begins with a letter. The one pattern of what is made whole (heal()) and of what
		// the request that lists the themes looks for (leftovers())
		public const string LEFT = '/^\.([a-z][a-z0-9]*(?:-[a-z0-9]+)*)\.(new-[0-9a-f]{8}|old-[0-9a-f]{8}|gone-[0-9a-f]{8})$/D';

		// What a copy of a folder takes beyond the files the mix writes - the files of the form of a package that the mix does not say: the licence, the readme, the blocks of the folder
		// (what the kernel reads of a block), and where the folder is the one that is saved over its picture - and how much of it
		private const string BLOCK = '~^[a-z][a-z0-9]*(?:-[a-z0-9]+)*$~D';
		private const int CARRY_FILES = 255;
		private const int CARRY_FOLDERS = 64;
		private const int CARRY_BYTES = 2097152;

		// How many knobs and components of a group a mix may speak of, and how many bytes it may come to: well below what the store reads (Store::MAX_FILE)
		private const int ENTRIES = 40;
		public const int BYTES = 262144;

		/**
		 *	The mix a theme is: its part for the page and for every group of the register that it has one for - the standard part
		 *	for the rest (F5) - each at the steps the theme is delivered at, which are the middle step where its part says none: a
		 *	step moves no ladder, the middle of the scale stays the middle. The css of the theme as a whole, where it has one and
		 *	the rule holds it, is the css of the mix - after the parts, where an own theme made of the mix keeps it to change
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$key					The key of an installed theme
		 *
		 *	@return 	array|null								null where the theme is not installed
		 */
		public static function of( array &$appData, string $key ): ?array {

			$theme = Reader::read( $appData )['themes'][$key] ?? null;

			if( $theme === null )
				return null;

			$parts = [];

			foreach( array_merge( [ 'page' ], array_keys( \Nino\Components::groups( $appData ) ) ) as $group ) {

				$parts[$group] = [ 'from' => isset( $theme['parts'][$group] ) === true ? $key. '@'. $theme['version'] : 'standard' ];
				$delivered = (array) ( $theme['parts'][$group]['part']['values'] ?? [] );

				if( $delivered !== [] )
					$parts[$group]['values'] = $delivered;
			}

			ksort( $parts );

			return [ 'parts' => $parts ] + ( ( $theme['css'] ?? '' ) !== '' ? [ 'css' => (string) $theme['css'] ] : [] );
		}

		/**
		 *	The mix an own theme is, read from its folder: the name, the css - the text of theme.css, also where it breaks the rule, so that it can be corrected - and for each
		 *	group the part it holds, at the steps and the variants its part.json says. Where the part is the same part an installed theme has - the same part.json but for the steps and the
		 *	variants, the same part.css - the group is told from that theme, 'key@version', which is only what a person reads of it; for a part that is no installed one's it is
		 *	'own:<key>'. A part that is no more than a contract, steps and variants is the standard part of its group
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$key					The key of the own theme
		 *	@param		bool			$told					Whether a part that is an installed theme's is told from it - for what a person sees; false for what is built, which stands on the folder alone
		 *
		 *	@return 	array|null								null where there is no such theme
		 */
		public static function ofOwn( array &$appData, string $key, bool $told = false ): ?array {

			$theme = Reader::own( $appData )['themes'][$key] ?? null;

			if( $theme === null )
				return null;

			$installed = $told === true ? Reader::read( $appData )['themes'] : [];
			$parts = [];

			foreach( array_merge( [ 'page' ], array_keys( \Nino\Components::groups( $appData ) ) ) as $group ) {

				$part = $theme['parts'][$group] ?? null;

				if( $part === null || ( $part['ok'] === true && $part['bare'] === true ) )
					$parts[$group] = [ 'from' => 'standard' ];
				else {

					$parts[$group] = [ 'from' => 'own:'. $key ];

					foreach( $installed as $other => $offered )
						if( $part['code'] !== '' && ( $offered['parts'][$group]['code'] ?? '' ) === $part['code'] ) {
							$parts[$group]['from'] = $other. '@'. $offered['version'];
							break;
						}
				}

				$values = (array) ( $part['part']['values'] ?? [] );
				$standard = (array) ( $part['part']['standard'] ?? [] );

				if( $standard !== [] )
					$parts[$group]['standard'] = $standard;

				if( $values !== [] )
					$parts[$group]['values'] = $values;
			}

			ksort( $parts );

			return [ 'name' => $theme['label'], 'parts' => $parts ] + ( ( $theme['css'] ?: $theme['cssRaw'] ) !== '' ? [ 'css' => (string) ( $theme['css'] ?: $theme['cssRaw'] ) ] : [] );
		}

		/**
		 *	What the folder of an own theme is made of, before anything is written: every file by its path in the folder. The mix says for each group where its part is taken from and
		 *	at which steps and variants it stands; the part is the copy of the one it is taken from, as the files of that theme have it, with the steps and the variants of the mix
		 *	in its part.json. Nothing is made up that the reader would not read back: every part is checked as the reader checks it, and the theme is read once more
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$key					The key of the theme, which is the name of its folder
		 *	@param		array			$mix					As clean() answers it, with the name
		 *	@param		array|null								$base					The theme this one is made of or made over, as the reader has read it: its version, its description and what it suggests for the brand are kept
		 *	@param		bool			$over					Whether the theme is the one that is saved over - its version and description stay - and not another that is made of it
		 *
		 *	@return 	array|string							path => text, or what is wrong
		 */
		public static function files( array &$appData, string $key, array $mix, ?array $base = null, bool $over = false ): array|string {

			$groups = \Nino\Components::groups( $appData );
			$files = [];
			$brand = [];

			foreach( array_merge( [ 'page' ], array_keys( $groups ) ) as $group ) {

				$entry = (array) ( $mix['parts'][$group] ?? [ 'from' => 'standard' ] );
				$values = (array) ( $entry['values'] ?? [] );
				$standard = (array) ( $entry['standard'] ?? [] );
				$from = (string) ( $entry['from'] ?? 'standard' );
				$resolved = $from === 'standard' ? null : Reader::resolve( $appData, $from, $group );
				$json = null;
				$text = null;
				$css = '';

				if( $resolved !== null && $resolved['part'] !== null && $resolved['problem'] === '' ) {

					[ $text, $css ] = Reader::partFiles( $resolved['dir'], $group );
					$json = $text === null ? null : json_decode( $text, false, 16 );

					if( is_object( $json ) === false )
						return $group. ': the part of '. $from. ' cannot be read';

					if( $group === 'page' )
						$brand = $over === true && $base !== null && ( $base['parts']['page']['code'] ?? '' ) === $resolved['code'] ? (array) $base['brand'] : (array) $resolved['brand'];
				}
				// A part of the theme's own folder that rests is the theme's to keep, as it was written - what a person put there by hand is not made away by a save of the rest
				else if( $over === true && $resolved !== null && $from === 'own:'. $key && $resolved['part'] === null && $resolved['problem'] !== '' ) {

					[ $kept, $keptCss ] = Reader::partFiles( $resolved['dir'], $group );

					if( is_string( $kept ) === true ) {
						$files['parts/'. $group. '/part.json'] = $kept;

						if( trim( $keptCss ) !== '' )
							$files['parts/'. $group. '/part.css'] = $keptCss;
					}

					continue;
				}
				// The standard part has no folder of its own - unless steps or variants are said for it, which the part.json of a part that is no more than a contract keeps
				else if( $group !== 'page' && ( $values !== [] || $standard !== [] ) ) {

					$feature = \Nino\Features::get( $appData, $groups[$group]['feature'] );

					if( $feature === null )
						continue;

					$parts = explode( '.', (string) $feature['version'] );
					$json = (object) [ 'contract' => $groups[$group]['feature']. '@^'. $parts[0]. '.'. ( $parts[1] ?? '0' ) ];
				}

				if( $json === null )
					continue;

				$now = (array) ( $json->standard ?? [] );
				$set = array_replace( $now, $standard );
				$json->values = (object) $values;
				$json->standard = (object) $set;

				if( $values === [] )
					unset( $json->values );

				if( $set === [] )
					unset( $json->standard );

				// A part that is the part it was made of, steps and variants as it has them, is copied as the bytes it is: what is not changed is not written anew
				$files['parts/'. $group. '/part.json'] = is_string( $text ) === true && json_decode( $text, true ) == json_decode( (string) json_encode( $json ), true ) ? $text : self::_json( $json );

				if( trim( $css ) !== '' )
					$files['parts/'. $group. '/part.css'] = $css;

				$raw = [ 'json' => json_decode( (string) json_encode( $json ), true ), 'css' => $css, 'problem' => '' ];
				$checked = Reader::check( $appData, $group, $raw );

				if( $checked['ok'] !== true )
					return $group. ': '. $checked['problem'];
			}

			$theme = [ 'key' => $key, 'version' => $over === true && $base !== null ? (string) $base['version'] : '1.0.0', 'name' => $over === true && $base !== null && is_array( $base['name'] ?? null ) === true && (string) $mix['name'] === (string) ( $base['label'] ?? '' ) ? $base['name'] : (string) $mix['name'], 'description' => $over === true && $base !== null ? $base['description'] : (string) $mix['name'] ];

			if( $brand !== [] )
				$theme['brand'] = $brand;

			$files['theme.json'] = self::_json( (object) $theme );

			if( trim( (string) ( $mix['css'] ?? '' ) ) !== '' )
				$files['theme.css'] = trim( (string) $mix['css'] ). "\n";

			return $files;
		}

		/**
		 *	A JSON file as it is written: one entry to a line, the text as it is
		 */
		private static function _json( object $value ): string {
			return json_encode( $value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ). "\n";
		}

		/**
		 *	Write the folder of an own theme: in a folder next to it - private/themes/.<key>.new-<random> - and then in its place, so that a request that is broken off leaves
		 *	the folder as it was or the folder as it is made, and never one that is half. Where the theme is saved over, the folder it had is put aside as .<key>.old
		 *	until the store says, with the lock still held, whether the change holds (settle()); what is about to move is written down before it moves. What the old folder has and no mix says - a licence, a readme, a picture, the blocks, a part of a group no feature of
		 *	this project brings - is copied into the new one; for a theme that is made of another, what that theme has of the same, but not its picture
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$key					The key of the theme
		 *	@param		array			$mix					As clean() answers it, with the name
		 *	@param		array|null								$base					The theme it is made of or made over, as the reader has read it
		 *	@param		bool			$over					Whether the folder of the theme is replaced
		 *
		 *	@return 	bool|string								true, or what is wrong
		 */
		public static function write( array &$appData, string $key, array $mix, ?array $base = null, bool $over = false ): bool|string {

			if( preg_match( \Nino\Components::NAME, $key ) !== 1 || strlen( $key ) > \Nino\Components::NAME_LENGTH )
				return 'the key of the theme is a slug';

			$files = self::files( $appData, $key, $mix, $base, $over );

			if( is_string( $files ) === true )
				return $files;

			\Nino\Filesystem::forceDir( $appData, \Nino\Modules\Themes::OWN );

			$dir = Reader::ownDir( $appData );
			$final = $dir. '/'. $key;
			$id = bin2hex( random_bytes( 4 ) );
			$new = $dir. '/.'. $key. '.new-'. $id;
			$old = $dir. '/.'. $key. '.old-'. $id;

			if( ( $over === false && file_exists( $final ) === true ) || is_link( $final ) === true )
				return 'there is a folder of that name';

			if( @mkdir( $new, 0755 ) === false )
				return 'the folder of the theme could not be made';

			if( $base !== null && is_string( $base['dir'] ?? null ) === true )
				self::_carry( $base['dir'], $new, $over === true );

			// What is carried of the folder of a group the register has is not carried: the part of such a group is what the mix says and the files that are written for it - a file in
			// it that nobody named is none of the theme's. The parts of groups no feature here brings are the theme's to keep
			foreach( array_merge( [ 'page' ], array_keys( \Nino\Components::groups( $appData ) ) ) as $group )
				\Nino\Filesystem::removeDir( $new. '/parts/'. $group );

			foreach( $files as $path => $text ) {

				if( is_dir( dirname( $new. '/'. $path ) ) === false )
					@mkdir( dirname( $new. '/'. $path ), 0755, true );

				if( @file_put_contents( $new. '/'. $path, $text ) === false ) {
					\Nino\Filesystem::removeDir( $new );
					return 'the folder of the theme could not be written';
				}
			}

			$had = $over === true && is_dir( $final ) === true;

			// What is about to move is written down first, with the lock held: the identity of the change - the number that is in the names of the folders it makes - and the inodes of the folder
			// that is replaced and of the one that replaces it, by which a request that is broken off from here on is told, by the next one, which folder is the change's and which is not (heal())
			if( self::_journal( $appData, [ 'key' => $key, 'id' => $id, 'op' => 'write', 'orig' => $had === true ? self::_inode( $final ) : null, 'ino' => self::_inode( $new ), 'kept' => null ] ) === false ) {
				\Nino\Filesystem::removeDir( $new );
				return 'the folder of the theme could not be replaced';
			}

			if( $had === true ) {

				if( @rename( $final, $old ) === false ) {
					\Nino\Filesystem::removeDir( $new );
					self::_forget( $appData );
					return 'the folder of the theme could not be replaced';
				}
			}

			if( @rename( $new, $final ) === false ) {

				if( $had === true && file_exists( $final ) === false )
					@rename( $old, $final );

				\Nino\Filesystem::removeDir( $new );
				self::_forget( $appData );

				return 'the folder of the theme could not be replaced';
			}

			unset( $appData['./nino/themes/own'] );

			return true;
		}

		/**
		 *	What a change of the folder of an own theme came to, said by the store with the lock still held: kept, the folder that was put aside is taken away; not kept, the folder is what it
		 *	was - or, for a theme that was made, none. The outcome is written down before anything moves, so that a request that is broken off in the middle is made whole to the same outcome
		 *	by the next one (heal()). Nothing where no folder was changed
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		bool			$kept
		 *
		 *	@return 	void
		 */
		public static function settle( array &$appData, bool $kept ): void {

			$journal = self::_pending( $appData );

			if( $journal === null )
				return;

			// An outcome that was decided - the state of the website was swapped - is not turned round by what comes after
			$journal['kept'] = $journal['kept'] === true ? true : $kept;

			if( self::_journal( $appData, $journal ) === true )
				self::_apply( $appData, $journal );
		}

		/**
		 *	The store says that the change holds: the state is in its place. Said before the rest of the change is carried out, so that a request that is broken off between the state and the
		 *	folder is made whole to the state - a theme that is on the website and the folder it was copied from agree. Nothing where no folder was changed
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	void
		 */
		public static function decide( array &$appData ): void {

			$journal = self::_pending( $appData );

			if( $journal !== null && $journal['kept'] === null ) {
				$journal['kept'] = true;
				self::_journal( $appData, $journal );
			}
		}

		/**
		 *	Take the folder of an own theme away: put aside as .<key>.old-<number>, where settle() takes it away for good - or puts it back where the change that asked for it is refused
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$key
		 *
		 *	@return 	bool|string								true, or what is wrong
		 */
		public static function drop( array &$appData, string $key ): bool|string {

			$dir = Reader::ownDir( $appData );

			if( preg_match( \Nino\Components::NAME, $key ) !== 1 || is_dir( $dir. '/'. $key ) === false || is_link( $dir. '/'. $key ) === true )
				return 'there is no such folder';

			$id = bin2hex( random_bytes( 4 ) );

			if( self::_journal( $appData, [ 'key' => $key, 'id' => $id, 'op' => 'drop', 'orig' => self::_inode( $dir. '/'. $key ), 'ino' => null, 'kept' => null ] ) === false )
				return 'the folder of the theme could not be taken away';

			if( @rename( $dir. '/'. $key, $dir. '/.'. $key. '.old-'. $id ) === false ) {
				self::_forget( $appData );
				return 'the folder of the theme could not be taken away';
			}

			unset( $appData['./nino/themes/own'] );

			return true;
		}

		/**
		 *	What a change that was broken off left in the folder of the own themes, as names: see LEFT. A link is none, and none is touched
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	array											The names; where the journal of a change is there, the file of it is named too
		 */
		public static function leftovers( array &$appData ): array {

			$dir = Reader::ownDir( $appData );
			$left = [];

			foreach( is_dir( $dir ) === true ? (array) scandir( $dir ) : [] as $entry )
				if( preg_match( self::LEFT, (string) $entry ) === 1 && is_link( $dir. '/'. $entry ) === false )
					$left[] = (string) $entry;

			return is_file( \Nino\Filesystem::path( $appData, \Nino\Modules\Themes::DIR. '/'. self::JOURNAL ) ) === true || is_link( \Nino\Filesystem::path( $appData, \Nino\Modules\Themes::DIR. '/'. self::JOURNAL ) ) === true ? array_merge( [ self::JOURNAL ], $left ) : $left;
		}

		/**
		 *	Make whole what a change that was broken off left in the folder of the own themes, with the lock held - and only then: it is what the store does at the start of a change and
		 *	what a request that finds something left does after taking the lock itself. A change that was written down is finished as its outcome says - not said: it was not done -; of
		 *	the rest: a folder that was being written is taken away, a folder that was put aside is back where the theme's folder is not there, and is taken away where it is
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	void
		 */
		public static function heal( array &$appData ): void {

			$dir = Reader::ownDir( $appData );
			$journal = self::_pending( $appData );

			// A journal that is not one - written by hand, left by a version that wrote others, or kept in a backup from the time of a change - touches nothing: it is thrown away
			if( $journal !== null )
				self::_apply( $appData, $journal );
			else
				self::_forget( $appData );

			foreach( self::leftovers( $appData ) as $entry ) {

				if( preg_match( self::LEFT, $entry, $found ) !== 1 )
					continue;

				$path = $dir. '/'. $entry;

				if( str_starts_with( $found[2], 'old-' ) === true && file_exists( $dir. '/'. $found[1] ) === false )
					@rename( $path, $dir. '/'. $found[1] );
				else
					\Nino\Filesystem::removeDir( $path );
			}

			unset( $appData['./nino/themes/own'] );
		}

		// The name of the journal, in the folder of the state: what a change of an own folder is about to do, and its outcome once the store knows it
		private const string JOURNAL = 'own.pending.json';

		/**
		 *	The journal of a change that is under way or was broken off: what it says is taken only where it is whole - a key a theme can have, the number of the change, what it did,
		 *	the inodes, and an outcome that is true, false or not said - and is a file, not a link
		 *
		 *	@return 	array|null								[ 'key', 'id', 'op' ('write' or 'drop'), 'orig' (the inode of the folder that was replaced or taken away, or null), 'ino' (the inode of the folder that was written, or null), 'kept' ]
		 */
		private static function _pending( array &$appData ): ?array {

			$file = \Nino\Filesystem::path( $appData, \Nino\Modules\Themes::DIR. '/'. self::JOURNAL );
			$text = is_file( $file ) === true && is_link( $file ) === false ? Store::quietRead( $file ) : false;
			$json = $text === false ? null : json_decode( $text, true );

			if( is_array( $json ) === false || array_keys( $json ) !== [ 'key', 'id', 'op', 'orig', 'ino', 'kept' ] )
				return null;

			if( is_string( $json['key'] ) === false || preg_match( \Nino\Components::NAME, $json['key'] ) !== 1 || strlen( $json['key'] ) > \Nino\Components::NAME_LENGTH || is_string( $json['id'] ) === false || preg_match( '/^[0-9a-f]{8}$/D', $json['id'] ) !== 1 )
				return null;

			if( in_array( $json['op'], [ 'write', 'drop' ], true ) === false || ( $json['orig'] !== null && is_int( $json['orig'] ) === false ) || ( $json['ino'] !== null && is_int( $json['ino'] ) === false ) || ( $json['kept'] !== null && is_bool( $json['kept'] ) === false ) )
				return null;

			return $json;
		}

		private static function _journal( array &$appData, array $journal ): bool {
			return \Nino\Filesystem::putFileContent( $appData, \Nino\Modules\Themes::DIR. '/'. self::JOURNAL, $journal );
		}

		private static function _forget( array &$appData ): void {
			@unlink( \Nino\Filesystem::path( $appData, \Nino\Modules\Themes::DIR. '/'. self::JOURNAL ) );
		}

		/**
		 *	Carry out what a journal says, as far as the folders show that they are the change's: a folder is the change's where it has the inode that the journal names and the name has the
		 *	number of the change. Kept, the folder put aside goes. Any other outcome - refused, or not said - takes away the folder that was written and puts the one that was put aside
		 *	back where nothing else is. A folder that is not the change's is left where it is, whatever the journal says: the folder of the theme is never taken away on a guess. Every step can
		 *	be done again, so that a break in it is mended by the same call
		 */
		private static function _apply( array &$appData, array $journal ): void {

			$dir = Reader::ownDir( $appData );
			$final = $dir. '/'. $journal['key'];
			$old = $dir. '/.'. $journal['key']. '.old-'. $journal['id'];
			$new = $dir. '/.'. $journal['key']. '.new-'. $journal['id'];
			$ours = static fn( string $path, ?int $inode ): bool => $inode !== null && is_dir( $path ) === true && is_link( $path ) === false && self::_inode( $path ) === $inode;

			if( $journal['kept'] === true ) {

				// The folder that was written is in its place where the change was past its swap; a break between the two renames leaves it beside, and it is put in place
				if( $ours( $old, $journal['orig'] ) === true ) {

					if( file_exists( $final ) === false && is_link( $final ) === false && $ours( $new, $journal['ino'] ) === true )
						@rename( $new, $final );

					\Nino\Filesystem::removeDir( $old );
				}

				if( $ours( $new, $journal['ino'] ) === true )
					\Nino\Filesystem::removeDir( $new );
			}
			else {

				// Only where the folder that was put aside is there - the proof that the swap was begun - is the folder in its place the change's to take away: a folder of the name
				// that no change put aside is the theme's, whatever the journal says of it, and stays
				if( $ours( $old, $journal['orig'] ) === true ) {

					if( $journal['op'] === 'write' && $ours( $final, $journal['ino'] ) === true )
						\Nino\Filesystem::removeDir( $final );

					if( file_exists( $final ) === false && is_link( $final ) === false )
						@rename( $old, $final );
				}

				if( $ours( $new, $journal['ino'] ) === true )
					\Nino\Filesystem::removeDir( $new );
			}

			self::_forget( $appData );
			unset( $appData['./nino/themes/own'] );
		}

		/**
		 *	The inode of a folder, as a number: what is the same folder after it was renamed
		 *
		 *	@return 	int|null									null where there is no such folder
		 */
		private static function _inode( string $path ): ?int {

			clearstatcache( true, $path );
			$stat = @stat( $path );

			return $stat === false ? null : (int) $stat['ino'];
		}

		/**
		 *	What a folder has that no mix says, copied: the licence, the readme, the blocks and - where the theme is the same - the picture and the parts of groups that no feature here brings.
		 *	Only these, by name: a file of another name is none of the form of a package and is not taken, nor is a folder that is a link, a file that is a link or longer than 2 MB, more than
		 *	255 files or 64 blocks. What a block may hold is what the kernel reads of one
		 *
		 *	@param		string		$from					The real path of the folder
		 *	@param		string		$to						The path of the new folder, which is there
		 *	@param		bool			$same					Whether the new folder is the old one made new
		 *
		 *	@return 	void
		 */
		private static function _carry( string $from, string $to, bool $same ): void {

			$names = [ 'LICENSE.txt', 'README.md' ];
			$folders = [ 'blocks' => 0, 'parts' => 0 ];

			if( $same === true )
				$names[] = 'preview.webp';

			// The folders that hold files by name: a block, with its two files, its texts and its pictures; a part, with its two
			foreach( [ 'blocks' => [ 'block.json', 'block.tpl' ], 'parts' => $same === true ? [ 'part.json', 'part.css' ] : [] ] as $folder => $inside ) {

				if( is_dir( $from. '/'. $folder ) === false || is_link( $from. '/'. $folder ) === true )
					continue;

				foreach( (array) scandir( $from. '/'. $folder ) as $entry ) {

					if( preg_match( self::BLOCK, (string) $entry ) !== 1 || is_dir( $from. '/'. $folder. '/'. $entry ) === false || is_link( $from. '/'. $folder. '/'. $entry ) === true || strlen( (string) $entry ) > \Nino\Components::NAME_LENGTH || ++$folders[$folder] > self::CARRY_FOLDERS )
						continue;

					foreach( $inside as $name )
						$names[] = $folder. '/'. $entry. '/'. $name;

					if( $folder !== 'blocks' )
						continue;

					foreach( [ 'text' => '/^[a-z]{2}_[A-Z]{2}\.json$/D', 'images' => '/^[a-z0-9]+(?:-[a-z0-9]+)*\.(?:svg|webp|png|jpg)$/D' ] as $kind => $pattern )
						if( is_dir( $from. '/blocks/'. $entry. '/'. $kind ) === true && is_link( $from. '/blocks/'. $entry. '/'. $kind ) === false )
							foreach( array_slice( (array) scandir( $from. '/blocks/'. $entry. '/'. $kind ), 0, 40 ) as $file )
								if( preg_match( $pattern, (string) $file ) === 1 )
									$names[] = 'blocks/'. $entry. '/'. $kind. '/'. $file;
				}
			}

			$count = 0;

			foreach( $names as $name ) {

				if( is_file( $from. '/'. $name ) === false || is_link( $from. '/'. $name ) === true || filesize( $from. '/'. $name ) > self::CARRY_BYTES || ++$count > self::CARRY_FILES )
					continue;

				if( is_dir( dirname( $to. '/'. $name ) ) === false && @mkdir( dirname( $to. '/'. $name ), 0755, true ) === false )
					continue;

				@copy( $from. '/'. $name, $to. '/'. $name );
			}
		}

		/**
		 *	Check what a person says of a mix and make it whole: a name where it is an own theme's, the css, and for each group a
		 *	part from a theme that is installed or has a copy, variants that part has, steps of its knobs
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		mixed			$input
		 *	@param		bool			$named				Whether a name is needed, as for an own theme
		 *
		 *	@return 	array|string							The clean mix, or what is wrong, as 'field: reason'
		 */
		public static function clean( array &$appData, mixed $input, bool $named ): array|string {

			if( is_array( $input ) === false || array_diff( array_keys( $input ), [ 'name', 'css', 'parts' ] ) !== [] )
				return 'mix: a mix is a name, a css and parts';

			$clean = [];

			if( $named === true ) {

				$name = $input['name'] ?? null;

				if( is_string( $name ) === false || trim( $name ) === '' || mb_strlen( $name ) > self::NAME_LENGTH || preg_match( '/[\x00-\x1F\x7F]/', $name ) === 1 || mb_check_encoding( $name, 'UTF-8' ) === false )
					return 'name: a name is text of 1 to '. self::NAME_LENGTH. ' characters, in one line';

				$clean['name'] = trim( $name );
			}

			$css = self::css( $input['css'] ?? '' );

			if( $css === null )
				return 'css: the css is text of at most '. self::CSS_LENGTH. ' bytes, with no null byte';

			$clean['css'] = $css;
			$parts = $input['parts'] ?? [];

			if( is_array( $parts ) === false || count( $parts ) > self::GROUPS )
				return 'parts: a map of at most '. self::GROUPS. ' groups';

			$groups = \Nino\Components::groups( $appData );
			$clean['parts'] = [];

			foreach( $parts as $group => $entry ) {

				if( is_string( $group ) === false || ( $group !== 'page' && preg_match( \Nino\Components::NAME, $group ) !== 1 ) || strlen( $group ) > \Nino\Components::NAME_LENGTH )
					return 'parts: a group is a slug';

				if( is_array( $entry ) === false || array_diff( array_keys( $entry ), [ 'from', 'standard', 'values' ] ) !== [] )
					return $group. ': a part is from, standard and values';

				// What neither the register nor the page has is not kept: a mix does not grow by what nothing reads
				if( $group !== 'page' && isset( $groups[$group] ) === false )
					continue;

				$from = $entry['from'] ?? 'standard';

				if( is_string( $from ) === false || preg_match( self::FROM, $from, $found ) !== 1 )
					return $group. '.from: standard, or a theme as key@version';

				// A bare key is the version that is installed, which is what a copy is made of
				if( $from !== 'standard' && ( $found[1] ?? '' ) === '' && ( $found[3] ?? '' ) === '' ) {

					$theme = Reader::read( $appData )['themes'][$found[2]] ?? null;

					if( $theme === null )
						return $group. '.from: the theme "'. $found[2]. '" is not installed';

					$from = $found[2]. '@'. $theme['version'];
				}

				$resolved = $from === 'standard' ? null : Reader::resolve( $appData, $from, $group );

				if( $resolved !== null && $resolved['origin'] === '' )
					return $group. '.from: '. $resolved['problem'];

				$mix = [ 'from' => $from ];
				$known = self::_knobs( $groups, $group, $resolved );
				$variants = self::_variants( $appData, $groups, $group, $resolved );

				$values = $entry['values'] ?? [];

				if( is_array( $values ) === false || count( $values ) > self::ENTRIES )
					return $group. '.values: a map of at most '. self::ENTRIES. ' knobs to steps';

				foreach( $values as $knob => $step ) {

					if( is_string( $knob ) === false || preg_match( self::KNOB, $knob ) !== 1 || strlen( $knob ) > 2 * \Nino\Components::NAME_LENGTH + 1 )
						return $group. '.values: a knob is <component>.<name>, or a name for the page';

					if( is_int( $step ) === false || $step < -2 || $step > 2 )
						return $group. '.values.'. $knob. ': a step is a whole number from -2 to 2';

					if( $step !== 0 && isset( $known[$knob] ) === true )
						$mix['values'][$knob] = $step;
				}

				$standard = $entry['standard'] ?? [];

				if( is_array( $standard ) === false || count( $standard ) > self::ENTRIES )
					return $group. '.standard: a map of at most '. self::ENTRIES. ' components to variants';

				foreach( $standard as $component => $variant ) {

					if( is_string( $component ) === false || preg_match( \Nino\Components::NAME, $component ) !== 1 || is_string( $variant ) === false || preg_match( \Nino\Components::NAME, $variant ) !== 1 )
						return $group. '.standard: a component and one of its variants, as slugs';

					if( in_array( $variant, $variants[$component] ?? [], true ) === true )
						$mix['standard'][$component] = $variant;
				}

				foreach( [ 'standard', 'values' ] as $key ) {

					if( isset( $mix[$key] ) === false )
						continue;

					ksort( $mix[$key] );
					$ordered = $mix[$key];
					unset( $mix[$key] );
					$mix[$key] = $ordered;
				}

				$clean['parts'][$group] = $mix;
			}

			ksort( $clean['parts'] );

			// Nothing is written that the store would not read back
			if( strlen( (string) json_encode( $clean ) ) > self::BYTES )
				return 'mix: a mix is at most '. self::BYTES. ' bytes';

			return $clean;
		}

		/**
		 *	The group a refusal of clean() is about: the first word of 'group.field: reason' and of 'group: a part is ...', where that word names a part
		 *	of the mix and not the mix, its name, its css or its list of parts
		 *
		 *	@param		string		$reason				What clean() answered
		 *
		 *	@return 	string										The key of the group, 'page' for the page; '' where the refusal is about no part
		 */
		public static function where( string $reason ): string {

			if( preg_match( '/^([a-z][a-z0-9-]*)(\.[a-z0-9.\-]+)?: (.*)$/Ds', $reason, $found ) !== 1 )
				return '';

			return $found[2] !== '' || str_starts_with( $found[3], 'a part is from' ) === true ? $found[1] : '';
		}

		/**
		 *	What the css of a theme does against the rule of a theme - the rule of \Nino\Components::themeProblems(), which a package is held to: every part of a selector is a
		 *	class, no element, id, universal or attribute selector, no url(), @import, @font-face, @property or !important, no token of the brand or the page set. The same rule
		 *	for the css a person writes in the panel as for the css a package brings
		 *
		 *	@param		string		$css					The css as it is stored
		 *
		 *	@return 	array											One sentence for each thing that is wrong, with its line; [] where it keeps the rule or there is none
		 */
		public static function cssRule( string $css ): array {
			return trim( $css ) === '' ? [] : \Nino\Components::themeProblems( $css );
		}

		/**
		 *	The css of the developer as it is stored: text, one line ending, a null byte is none, a length is the limit
		 *
		 *	@param		mixed			$css
		 *
		 *	@return 	string|null								null where it is none
		 */
		public static function css( mixed $css ): ?string {

			if( is_string( $css ) === false || strlen( $css ) > self::CSS_LENGTH || str_contains( $css, "\0" ) === true || mb_check_encoding( $css, 'UTF-8' ) === false )
				return null;

			return trim( str_replace( [ "\r\n", "\r" ], "\n", $css ) );
		}

		/**
		 *	The knobs a group has with a part: those of the part where it holds, else those of the group; the page has those of its part
		 *
		 *	@param		array			$groups				The register's groups
		 *	@param		string		$group
		 *	@param		array|null								$resolved			What Reader::resolve() answered, null for the standard part
		 *
		 *	@return 	array											Knob key => true
		 */
		private static function _knobs( array $groups, string $group, ?array $resolved ): array {

			$part = $resolved !== null && $resolved['problem'] === '' ? $resolved['part'] : null;

			if( $group === 'page' )
				return $part !== null ? array_fill_keys( array_keys( $part['knobs'] ), true ) : [];

			return array_fill_keys( array_keys( $part !== null ? $part['knobs'] : $groups[$group]['knobs'] ), true );
		}

		/**
		 *	The variants each component of a group has with a part: those of its schema and those of the part
		 *
		 *	@return 	array											component => names
		 */
		private static function _variants( array &$appData, array $groups, string $group, ?array $resolved ): array {

			if( $group === 'page' )
				return [];

			$part = $resolved !== null && $resolved['problem'] === '' ? $resolved['part'] : null;
			$schemas = \Nino\Components::components( $appData );
			$variants = [];

			foreach( $groups[$group]['components'] as $component )
				$variants[$component] = array_merge( (array) ( $schemas[$component]['variants'] ?? [ 'regular' ] ), array_keys( $part['variants'][$component] ?? [] ) );

			return $variants;
		}
	}
}
