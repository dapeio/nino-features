<?php
declare(strict_types=1);

/**
 *	Nino features
 *	declarations.php	What a feature tells the tests of the catalogue about itself: features/<Name>/tests/catalogue.php returns an array, and
 *										the tests that go over every feature (legal-smoke, keys-smoke, panels-smoke) read the arrays of all of them from here.
 *										The file is the one place a feature says it, so that a new feature needs no change in a file the others share; it lies
 *										in tests/, which no package carries, and bin/check.sh does not start it, since its name is no *-smoke.php.
 *
 *										The keys of the array:
 *										  legal    [ first, last ]  the positions (order) of the feature's sections of the privacy policy
 *										  pages    uri => file  a page of /_nino/webpage<uri>/... that the feature makes with a route of its own, and the file of the
 *										           feature in which the route is made: it holds 'uri' => '<uri>'. An address that is built names the constant of the file
 *										           that holds it - [ file, CONSTANT ] - or, with a format, [ file, CONSTANT, [ file, CONSTANT ] ] for sprintf()
 *										  surface  reason  assets/admin.js and assets/admin.css are a surface: the rules of the look of a panel do not hold for it
 *										  reading  reason  the panel only reads: its script says nothing with say()
 *										  surface and reading are exceptions: a feature may say one only where tests/panels-smoke.php has released it
 *
 *										Nothing runs from here: the file is required by a test, which then calls the functions.
 */

/**	Where a feature's declarations stand below its folder	*/
const DECLARATIONS_FILE = 'tests/catalogue.php';

/**	The keys a test reads; any other key is a typo or a declaration nobody holds the feature to	*/
const DECLARATIONS_KEYS = [ 'legal', 'pages', 'surface', 'reading' ];

/**
 *	What is wrong with the form of one declaration file
 *
 *	@param		string		$file					The path of the file below the repository, for the sentence
 *	@param		mixed			$declared			What the file returned
 *
 *	@return		array<int, array{0: string, 1: string}>		[ key ('' for the file), sentence ]
 */
function declarationsShape( string $file, mixed $declared ): array {

	if( is_array( $declared ) === false )
		return [ [ '*', $file. ' returns no array' ] ];

	$found = [];

	foreach( array_diff( array_keys( $declared ), DECLARATIONS_KEYS ) as $key )
		$found[] = [ '', $file. ' has the key "'. $key. '", which no test reads (it is one of '. implode( ', ', DECLARATIONS_KEYS ). ')' ];

	if( isset( $declared['legal'] ) === true && ( is_array( $declared['legal'] ) === false || array_keys( $declared['legal'] ) !== [ 0, 1 ] || is_int( $declared['legal'][0] ) === false || is_int( $declared['legal'][1] ) === false || $declared['legal'][0] > $declared['legal'][1] ) )
		$found[] = [ 'legal', $file. ': "legal" is [ first, last ], two whole numbers in that order' ];

	if( isset( $declared['pages'] ) === true ) {

		$ok = is_array( $declared['pages'] ) === true && $declared['pages'] !== [];

		foreach( $ok === true ? $declared['pages'] : [] as $uri => $evidence )
			$ok = $ok === true && preg_match( '#^/[^\s]*$#D', (string) $uri ) === 1 && declarationsEvidence( $evidence ) !== null;

		if( $ok === false )
			$found[] = [ 'pages', $file. ': "pages" is uri => evidence, a uri that begins with "/" and the file of the feature that makes its route - "Hello.php" - or, for an address that is built, [ file, CONSTANT ] and [ file, CONSTANT, [ file, CONSTANT ] ]' ];
	}

	foreach( [ 'surface', 'reading' ] as $key )
		if( isset( $declared[$key] ) === true && ( is_string( $declared[$key] ) === false || trim( $declared[$key] ) === '' ) )
			$found[] = [ $key, $file. ': "'. $key. '" is the reason, a sentence' ];

	if( isset( $declared['surface'], $declared['reading'] ) === true )
		$found[] = [ 'surface', $file. ': a panel is a surface or one that only reads, not both' ];

	return $found;
}

/**
 *	The declarations of every feature below a repository
 *
 *	@param		string		$repo					The repository: features/ is below it
 *
 *	@return		array<string, array>		directory => [ 'file' => path below the repository, 'present' => bool, 'declared' => array, 'problems' => [ [ key, sentence ], ... ] ]
 */
function declarationsRead( string $repo ): array {

	$all = [];

	foreach( (array) scandir( $repo. '/features' ) as $directory ) {

		if( $directory[0] === '.' || is_dir( $repo. '/features/'. $directory ) === false )
			continue;

		$file = 'features/'. $directory. '/'. DECLARATIONS_FILE;
		$present = is_file( $repo. '/'. $file );
		$declared = $present === true ? ( static fn( string $path ): mixed => include $path )( $repo. '/'. $file ) : [];
		$problems = $present === true ? declarationsShape( $file, $declared ) : [];

		$all[$directory] = [ 'file' => $file, 'present' => $present, 'declared' => is_array( $declared ) === true ? $declared : [], 'problems' => $problems ];
	}

	return $all;
}

/**
 *	What the features declare under one key, of the declarations that are well formed
 *
 *	@param		array<string, array>		$all		From declarationsRead()
 *	@param		string									$key
 *
 *	@return		array<string, mixed>		directory => the value
 */
function declarationsOf( array $all, string $key ): array {

	$found = [];

	foreach( $all as $directory => $feature )
		if( isset( $feature['declared'][$key] ) === true && array_filter( $feature['problems'], static fn( array $problem ): bool => $problem[0] === $key ) === [] )
			$found[$directory] = $feature['declared'][$key];

	return $found;
}

/**
 *	The sentences about the form of the declarations of some keys (and of the file as a whole)
 *
 *	@param		array<string, array>		$all		From declarationsRead()
 *	@param		array<int, string>			$keys		The keys the caller reads, and a file that is no array; [] for all, unknown keys included
 *
 *	@return		array<int, string>
 */
function declarationsProblems( array $all, array $keys = [] ): array {

	$found = [];

	foreach( $all as $feature )
		foreach( $feature['problems'] as [ $key, $sentence ] )
			if( $keys === [] || $key === '*' || in_array( $key, $keys, true ) === true )
				$found[] = $sentence;

	return $found;
}

/**
 *	The evidence of a page, in the one form the tests read
 *
 *	@param		mixed			$evidence			A file, [ file ], [ file, CONSTANT ] or [ file, CONSTANT, [ file, CONSTANT ] ]
 *
 *	@return		array|null								[ [ file, constant|null ], [ file, constant ]|null ], or null where it is none of them
 */
function declarationsEvidence( mixed $evidence ): ?array {

	$evidence = is_string( $evidence ) === true ? [ $evidence ] : $evidence;
	$file = static fn( mixed $path ): bool => is_string( $path ) === true && preg_match( '#^(?!/)(?!.*(?:^|/)\.\.?(?:/|$))[A-Za-z0-9_./-]+$#D', $path ) === 1;
	$constant = static fn( mixed $name ): bool => is_string( $name ) === true && preg_match( '/^[A-Z][A-Z0-9_]*$/D', $name ) === 1;

	if( is_array( $evidence ) === false || array_is_list( $evidence ) === false || count( $evidence ) < 1 || count( $evidence ) > 3 || $file( $evidence[0] ) === false )
		return null;

	if( count( $evidence ) === 1 )
		return [ [ $evidence[0], null ], null ];

	if( $constant( $evidence[1] ) === false )
		return null;

	if( count( $evidence ) === 2 )
		return [ [ $evidence[0], $evidence[1] ], null ];

	$with = $evidence[2];

	return is_array( $with ) === true && array_is_list( $with ) === true && count( $with ) === 2 && $file( $with[0] ) === true && $constant( $with[1] ) === true ? [ [ $evidence[0], $evidence[1] ], [ $with[0], $with[1] ] ] : null;
}

/**
 *	The code of a php file as the tokens that are code: no comment, no white space, no docblock, no inline html
 *
 *	@param		string		$source
 *
 *	@return		array<int, string|array{0: int, 1: string}>
 */
function declarationsTokens( string $source ): array {

	return array_values( array_filter( token_get_all( $source ), static fn( mixed $token ): bool => is_array( $token ) === false || in_array( $token[0], [ T_COMMENT, T_DOC_COMMENT, T_WHITESPACE, T_INLINE_HTML, T_OPEN_TAG, T_OPEN_TAG_WITH_ECHO, T_CLOSE_TAG ], true ) === false ) );
}

/**
 *	The text of a string token that is a plain string
 *
 *	@param		mixed			$token
 *
 *	@return		string|null
 */
function declarationsString( mixed $token ): ?string {

	if( is_array( $token ) === false || $token[0] !== T_CONSTANT_ENCAPSED_STRING )
		return null;

	return stripcslashes( substr( $token[1], 1, -1 ) );
}

/**
 *	Whether the route of a page is made where the feature says: the file is a php file of the feature's own folder - not tests/ and not feature.php, the
 *	file that makes the route is the one named - (no way out of the folder, no link to another place), and its code - the comments are no code - holds
 *	'uri' => '<uri>', or a constant defined there with const that is the address, or a format with %s that the other constant fills. What the tests ask is
 *	made of the address here, not of a pattern the feature brings
 *
 *	@param		string		$dir					The folder of the feature
 *	@param		string		$uri					The page, /hello
 *	@param		mixed			$evidence
 *
 *	@return		string|null								What is wrong, or null
 */
function declarationsRoute( string $dir, string $uri, mixed $evidence ): ?string {

	$read = declarationsEvidence( $evidence );

	if( $read === null )
		return 'the evidence of '. $uri. ' is none the tests read';

	$source = static function( string $relative ) use ( $dir ): ?array {

		if( str_ends_with( $relative, '.php' ) === false || $relative === 'feature.php' || str_starts_with( $relative, 'tests/' ) === true )
			return null;

		$real = realpath( $dir. '/'. $relative );
		$root = realpath( $dir );

		return $real !== false && $root !== false && is_file( $real ) === true && str_starts_with( $real, $root. DIRECTORY_SEPARATOR ) === true ? declarationsTokens( (string) file_get_contents( $real ) ) : null;
	};
	// const NAME = 'value' (with a type in between, as php 8.3 writes it): the value of the string that follows the = of the definition
	$value = static function( array $tokens, string $name ): ?string {

		foreach( $tokens as $at => $token )
			if( is_array( $token ) === true && $token[0] === T_CONST )
				for( $i = $at + 1; $i < min( $at + 5, count( $tokens ) ); $i++ )
					if( is_array( $tokens[$i] ) === true && $tokens[$i][0] === T_STRING && $tokens[$i][1] === $name && ( $tokens[$i + 1] ?? null ) === '=' )
						return declarationsString( $tokens[$i + 2] ?? null );

		return null;
	};

	[ [ $file, $name ], $with ] = $read;
	$code = $source( $file );

	if( $code === null )
		return $file. ' is no php file of the feature that makes a route (not feature.php, not tests/, no way out of the folder)';

	if( $name === null ) {

		foreach( $code as $at => $token )
			if( declarationsString( $token ) === 'uri' && ( $code[$at + 1] ?? null ) !== null && is_array( $code[$at + 1] ) === true && $code[$at + 1][0] === T_DOUBLE_ARROW && declarationsString( $code[$at + 2] ?? null ) === $uri )
				return null;

		return $file. ' has no route with \'uri\' => \''. $uri. '\' in its code';
	}

	$made = $value( $code, $name );

	if( $with !== null ) {

		$other = $source( $with[0] );
		$part = $other === null ? null : $value( $other, $with[1] );
		$made = $made === null || $part === null ? null : sprintf( $made, $part );
	}

	return $made === $uri ? null : $file. ' has no const '. $name. ( $with === null ? '' : ' with '. $with[1] ). ' that is '. $uri;
}

/**
 *	Files written below a folder: the folders are made as they are needed
 *
 *	@param		string		$base
 *	@param		array			$files				path below the base => content
 *
 *	@return		void
 */
function declarationsWrite( string $base, array $files ): void {

	foreach( $files as $path => $content ) {

		is_dir( dirname( $base. '/'. $path ) ) === true || mkdir( dirname( $base. '/'. $path ), 0755, true );
		file_put_contents( $base. '/'. $path, $content );
	}
}

/**
 *	A folder of a run's own taken away with what is in it, a link as the link it is
 *
 *	@param		string		$path
 *
 *	@return		void
 */
function declarationsRemove( string $path ): void {

	if( is_dir( $path ) === true && is_link( $path ) === false ) {

		foreach( array_diff( (array) scandir( $path ), [ '.', '..' ] ) as $entry )
			declarationsRemove( $path. '/'. $entry );

		rmdir( $path );
	}
	elseif( is_link( $path ) === true || is_file( $path ) === true )
		unlink( $path );
}

/**
 *	The example of a document: the php block that follows the line "<!-- catalogue-example -->", read as the array it returns
 *
 *	@param		string		$document			The text of a README or of AGENTS.md
 *
 *	@return		array|null								null where there is no such block or it returns no array
 */
function declarationsExample( string $document ): ?array {

	if( preg_match( '/<!-- catalogue-example -->\s*```php\n(.*?)\n```/s', $document, $found ) !== 1 )
		return null;

	$code = preg_replace( '/^\s*<\?php\s*(?:declare\(strict_types=1\);)?/', '', $found[1] );
	$result = eval( (string) $code );

	return is_array( $result ) === true ? $result : null;
}
