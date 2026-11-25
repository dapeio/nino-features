<?php
declare(strict_types=1);

namespace Nino\Modules\Crm {

	/**
	 *	Register		The one validator behind every add() of the core: a package enters a list of descriptions - the keys of its settings, a number sequence,
	 *							a line of the status, a schema - and the list is taken whole or not at all. The common form is `X::add( array &$appData, array $spec ): bool`:
	 *							a field the shape does not have, a key another package holds, a default of the wrong type or a callable that is none gives false with an
	 *							E_USER_WARNING that names the register and the reason - never a value of the description - and nothing is entered; the same description
	 *							again is as good as the first time, so a package may enter itself twice (a request that boots a module twice, a test). The entries of a
	 *							register stand in $appData, never in a static field, so that a second request in the same process starts from what it registered itself.
	 *
	 *							A shape is
	 *								id			the field that names an entry
	 *								idRule	a regular expression the id has to match, or a callable ( string $id ): bool
	 *								fields	field => default; a field that has REQUIRED as its default has to be given
	 *								rules		field => callable ( mixed $value, array $spec, array $known ): bool, asked of every value that is given
	 *								refuse	a callable ( array $spec, array $known ): string that answers the reason a whole description is refused for, '' for none
	 *								ignore	fields that no description is told apart by: the same kind from two packages is one
	 *
	 *	@package						Dape/Nino
	 */
	class Register {

		/**	The default of a field that has none: the description has to give it	*/
		public const string REQUIRED = "\0required";

		/**	The field of an entry that says in which order it was entered	*/
		public const string SEQ = 'seq';

		/**	The field every description of a package carries: the key of the feature that enters it	*/
		public const string PACKAGE = 'package';

		/**
		 *	Enter a list of descriptions in a register
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$register			The key of $appData that holds the entries, id => description
		 *	@param		string		$label				What the register is of, for the warning: 'settings', 'number sequence'
		 *	@param		array 		$list					A list of descriptions
		 *	@param		array 		$shape				See the class
		 *
		 *	@return 	bool											false, with a warning, where a description is refused - none of the list is entered then
		 */
		public static function add( array &$appData, string $register, string $label, array $list, array $shape ): bool {

			$known = is_array( $appData[$register] ?? null ) === true ? $appData[$register] : [];
			$found = [];
			$bad = '';

			foreach( $list as $spec ) {

				$bad = self::refusal( $spec, $known, $found, $shape );

				if( $bad !== '' )
					break;

				$spec = self::complete( (array) $spec, $shape );
				$id = (string) $spec[$shape['id']];

				// The same description again is entered already
				if( isset( $known[$id] ) === true && self::same( $known[$id], $spec, $shape['ignore'] ?? [] ) === true )
					continue;

				$found[$id] = $spec;
			}

			if( $bad !== '' ) {
				trigger_error( 'Crm: the '. $label. ' of a package were refused ('. $bad. ')', E_USER_WARNING );

				return false;
			}

			$next = count( $known );

			foreach( $found as $id => $spec )
				$appData[$register][$id] = $spec + [ self::SEQ => $next++ ];

			return true;
		}

		/**
		 *	Why a description is refused
		 *
		 *	@param		mixed			$spec
		 *	@param		array 		$known				What the register holds
		 *	@param		array 		$found				What this list has entered so far
		 *	@param		array 		$shape
		 *
		 *	@return 	string										'' where it is good
		 */
		private static function refusal( mixed $spec, array $known, array $found, array $shape ): string {

			$id = $shape['id'];

			if( is_array( $spec ) === false || array_is_list( $spec ) === true && $spec !== [] )
				return 'description';

			if( array_diff_key( $spec, $shape['fields'] + [ $id => 1 ] ) !== [] )
				return 'field';

			if( is_string( $spec[$id] ?? null ) === false || self::idMatches( $spec[$id], $shape['idRule'] ) === false )
				return $id;

			$full = self::complete( $spec, $shape );

			foreach( $shape['fields'] as $field => $default )
				if( $default === self::REQUIRED && array_key_exists( $field, $spec ) === false )
					return $field;

			foreach( $shape['rules'] ?? [] as $field => $rule )
				if( array_key_exists( $field, $spec ) === true && $rule( $spec[$field], $full, $known ) !== true )
					return $field;

			$taken = $known[$spec[$id]] ?? $found[$spec[$id]] ?? null;

			if( $taken !== null && self::same( $taken, $full, $shape['ignore'] ?? [] ) === false )
				return $id. ' taken';

			if( isset( $shape['refuse'] ) === true && is_callable( $shape['refuse'] ) === true ) {

				$reason = ( $shape['refuse'] )( $full, $known + $found );

				if( $reason !== '' )
					return $reason;
			}

			return '';
		}

		/**
		 *	A description with the defaults of the shape put in
		 *
		 *	@param		array 		$spec
		 *	@param		array 		$shape
		 *
		 *	@return 	array
		 */
		private static function complete( array $spec, array $shape ): array {

			$defaults = array_filter( $shape['fields'], static fn( mixed $default ): bool => $default !== self::REQUIRED );

			return $spec + $defaults;
		}

		/**
		 *	Whether two descriptions say the same: the order of their entering is no part of it, and neither are the fields the shape says to ignore
		 *
		 *	@param		array 		$one
		 *	@param		array 		$other
		 *
		 *	@return 	bool
		 */
		private static function same( array $one, array $other, array $ignore ): bool {

			$one = array_diff_key( $one, array_flip( $ignore ) );
			$other = array_diff_key( $other, array_flip( $ignore ) );
			unset( $one[self::SEQ], $other[self::SEQ] );
			ksort( $one );
			ksort( $other );

			return $one === $other;
		}

		/**
		 *	@param		string		$id
		 *	@param		mixed			$rule					A regular expression or a callable
		 *
		 *	@return 	bool
		 */
		private static function idMatches( string $id, mixed $rule ): bool {

			return is_string( $rule ) === true ? preg_match( $rule, $id ) === 1 : $rule( $id ) === true;
		}

		/**
		 *	The entries of a register in the order they were entered in
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$register
		 *
		 *	@return 	array											id => description, with the field seq
		 */
		public static function all( array &$appData, string $register ): array {

			$entries = is_array( $appData[$register] ?? null ) === true ? $appData[$register] : [];

			uasort( $entries, static fn( array $a, array $b ): int => ( $a[self::SEQ] ?? 0 ) <=> ( $b[self::SEQ] ?? 0 ) );

			return $entries;
		}

		/**
		 *	The class and the method of a value that is written as [ class, method ], as text and nothing else: the class is a name (a leading backslash is taken off), none of self, static and parent, and the method is a plain name -
		 *	no :: in it, so it cannot lead to the method of another class. PHP is not asked anything: no class is loaded
		 *
		 *	@param		mixed			$value
		 *
		 *	@return 	array|null								[ class, method ]; null for anything else
		 */
		private static function written( mixed $value ): ?array {

			if( is_array( $value ) === false || array_is_list( $value ) === false || count( $value ) !== 2 || is_string( $value[0] ) === false || is_string( $value[1] ) === false
				|| preg_match( '/^\\\\?[A-Za-z_][A-Za-z0-9_]*(?:\\\\[A-Za-z_][A-Za-z0-9_]*)*$/D', $value[0] ) !== 1 || preg_match( '/^[A-Za-z_][A-Za-z0-9_]*$/D', $value[1] ) !== 1 )
				return null;

			// self, static and parent name a class only inside of one: PHP warns that they are deprecated when it is asked about them, so they are refused before it is asked
			if( in_array( strtolower( ltrim( $value[0], '\\' ) ), [ 'self', 'static', 'parent' ], true ) === true )
				return null;

			return [ ltrim( $value[0], '\\' ), $value[1] ];
		}

		/**
		 *	Whether a value is a callable as the registers take it: [ class, method ] as two strings - no closure, nothing that cannot be written down - of a public static method that the class has under that name, see written().
		 *	Everything else is refused before PHP is asked about it
		 *
		 *	@param		mixed			$value
		 *
		 *	@return 	bool
		 */
		public static function callable( mixed $value ): bool {

			$written = self::written( $value );

			if( $written === null )
				return false;

			try {
				$method = new \ReflectionMethod( $written[0], $written[1] );
			}
			catch( \ReflectionException $e ) {
				return false;
			}

			return $method->isPublic() === true && $method->isStatic() === true && $method->isAbstract() === false;
		}

		/**
		 *	Whether a value is a callable of a package: [ class, method ] or 'class::method', as callable() takes it, and the class lies in the namespace of the feature that enters it - the class the kernel has for that feature or one
		 *	below it - and the class and the method are both written in the folder of that feature. A closure, a function and a method of a class outside it are refused: what a package registers to be run for it is its own code, whatever
		 *	package it names. The name of the class is read as text against the namespace first: a class of another namespace is not loaded to be asked about
		 *
		 *	@param		array 		&$appData
		 *	@param		mixed			$package			The key of the package that enters it
		 *	@param		mixed			$value
		 *
		 *	@return 	bool
		 */
		public static function owned( array &$appData, mixed $package, mixed $value ): bool {

			if( is_string( $value ) === true && preg_match( '/^([^:]+)::([^:]+)$/D', $value, $match ) === 1 )
				$value = [ $match[1], $match[2] ];

			$feature = self::package( $appData, $package );
			$written = self::written( $value );

			if( $feature === null || $written === null )
				return false;

			// The namespace is what a class says of itself, and it is read from the name before the class is asked for
			if( strcasecmp( $written[0], $feature['space'] ) !== 0 && strncasecmp( $written[0], $feature['space']. '\\', strlen( $feature['space'] ) + 1 ) !== 0 )
				return false;

			if( self::callable( $written ) === false )
				return false;

			// The file is where it was written: the class and the method both lie in the folder of the package - a method that is inherited, comes from a trait or is the kernel's own (no file) does not
			try {
				$class = ( new \ReflectionClass( $written[0] ) )->getFileName();
				$method = ( new \ReflectionMethod( $written[0], $written[1] ) )->getFileName();
			}
			catch( \ReflectionException $e ) {
				return false;
			}

			return $class !== false && $method !== false && self::within( $class, $feature['folder'] ) === true && self::within( $method, $feature['folder'] ) === true;
		}

		/**
		 *	The one place that says who a package is. Every add of the core names the package that enters (the field or the option package) and asks this: a key is a package where it is a slug, no first word
		 *	of the core's own and the key of a feature that is on. What the package may bring - files, routes, classes - is what the kernel says of that feature: its folder and its namespace are read from
		 *	the feature, never put together from the key
		 *
		 *	@param		array 		&$appData
		 *	@param		mixed			$key
		 *
		 *	@return 	array|null							key, folder (the real path of the folder of the feature, without a slash at its end), space (\Nino\Modules\<Directory> without the leading backslash); null for a key that is no package
		 */
		public static function package( array &$appData, mixed $key ): ?array {

			if( self::word( $key ) === false || in_array( $key, Api::reserved(), true ) === true || \Nino\Modules\Crm::featureOn( $appData, (string) $key ) === false )
				return null;

			$feature = \Nino\Features::get( $appData, (string) $key );
			$folder = $feature === null ? false : realpath( (string) $feature['dir'] );

			return $folder === false ? null : [ 'key' => (string) $key, 'folder' => rtrim( $folder, '/' ), 'space' => ltrim( (string) $feature['module'], '\\' ) ];
		}

		/**
		 *	The name of the folder the kernel has for a feature, as the kernel sees it: the last part of the directory it lists, not of the path that directory leads to - a folder that is a link is named by the link
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$key					The key of a feature
		 *
		 *	@return 	string										'' where the kernel knows no feature of that key
		 */
		public static function directory( array &$appData, string $key ): string {

			$feature = \Nino\Features::get( $appData, $key );

			return $feature === null ? '' : basename( (string) $feature['dir'] );
		}

		/**
		 *	The one question whether a package is removed - asked by everything that gives up, skips or drops something for want of a package: the deletion of a client (its journal), the data of a package that is gone,
		 *	the button that takes them away. Nothing here is derived from a name: a package is removed only where the whole of the features directory is accounted for and the package is not in it. True only where all of it holds:
		 *	the features directory can be read; the kernel knows no feature of the key; the kernel refuses no directory at all (a manifest that does not parse, is not there or is not accepted may be the package that is looked for,
		 *	and so may a feature that is switched on and has no directory); every entry of the features directory that is no regular file - a folder, a link wherever it leads or if it leads nowhere, a hidden one, one that cannot be
		 *	told - is the directory of a feature the kernel knows, on or off (a regular file is no entry: a package is a directory for the kernel, and .htaccess, .DS_Store or a README are files); and the folder the package was seen in is not there under any name or kind, not even as a file. Any doubt is no
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$key					The key of the package
		 *	@param		string		$folder				The name of its folder as the kernel saw it when the package was seen (a directory name, no path), see directory(); '' where none was kept - then nothing is removed
		 *
		 *	@return 	bool
		 */
		public static function gone( array &$appData, string $key, string $folder = '' ): bool {

			if( self::word( $key ) === false || preg_match( \Nino\Features::DIRECTORY_PATTERN, $folder ) !== 1 )
				return false;

			$dir = \Nino\Features::dir();
			$names = is_dir( $dir ) === true && is_readable( $dir ) === true ? scandir( $dir ) : false;

			if( $names === false || \Nino\Features::get( $appData, $key ) !== null || \Nino\Features::rejected( $appData ) !== [] )
				return false;

			$known = array_map( static fn( array $feature ): string => basename( (string) $feature['dir'] ), \Nino\Features::all( $appData ) );

			foreach( $names as $name ) {

				if( $name === '.' || $name === '..' )
					continue;

				// The folder of the package in any form and under any case, a file too
				if( strcasecmp( (string) $name, $folder ) === 0 )
					return false;

				// A package is a directory for the kernel: a regular file (.htaccess, .DS_Store, a README) is none, and a link, however it ends, is not a regular file
				if( is_file( $dir. '/'. $name ) === true && is_link( $dir. '/'. $name ) === false )
					continue;

				if( in_array( (string) $name, $known, true ) === false )
					return false;
			}

			return file_exists( $dir. '/'. $folder ) === false && is_link( $dir. '/'. $folder ) === false;
		}

		/**
		 *	The rule of a description for its field package: the key of a package, see package()
		 *
		 *	@param		array 		&$appData
		 *	@param		bool			$core					True for the entries the core makes of itself, which are of the package crm and of none other
		 *
		 *	@return 	callable
		 */
		public static function packageRule( array &$appData, bool $core = false ): callable {

			return $core === true ? static fn( mixed $package ): bool => $package === Api::CORE : static fn( mixed $package ): bool => self::package( $appData, $package ) !== null;
		}

		/**
		 *	Whether a path lies below a folder: symlinks resolved, no climb, no null byte, and the folder is followed by its separator - features/Foo does not take features/FooBar/x
		 *
		 *	@param		string		$path					Absolute
		 *	@param		string		$folder				The real path of a folder
		 *
		 *	@return 	bool
		 */
		public static function within( string $path, string $folder ): bool {

			if( $folder === '' || $path === '' || str_contains( $path, "\0" ) === true )
				return false;

			$real = realpath( $path );

			return $real !== false && str_starts_with( $real, rtrim( $folder, '/' ). '/' ) === true;
		}

		/**
		 *	Whether a value is a word of lower case letters and digits, joined by the joiner, beginning with a letter: a key of a setting, the slug of a feature
		 *
		 *	@param		mixed			$value
		 *	@param		int				$most					The most characters
		 *	@param		string		$joiner				'-' or '_'
		 *
		 *	@return 	bool
		 */
		public static function word( mixed $value, int $most = 40, string $joiner = '-' ): bool {

			return is_string( $value ) === true && strlen( $value ) <= $most && preg_match( '/^[a-z][a-z0-9]*(?:'. preg_quote( $joiner, '/' ). '[a-z0-9]+)*$/D', $value ) === 1;
		}
	}
}
