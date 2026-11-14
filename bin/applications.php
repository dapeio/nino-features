<?php
declare(strict_types=1);
/**
 *	Nino features
 *	applications.php	The applications of the catalogue: applications/<key>.php,
 *										one array each - a named set of features that work
 *										together, and the place to start in. Read and held to the
 *										rules of the index by bin/build.php, which publishes them
 *										as "applications" in catalogue.json, and shown by
 *										bin/catalogue.php. The kernel decides which of them an
 *										installation offers; nothing here asks what a kernel can
 *										run.
 *
 *										A field of an entry:
 *
 *										key						a slug, the file name without .php
 *										name					a string per locale, de_DE and en_US among them
 *										description	the same
 *										features			keys of features of this catalogue, at least one,
 *																	none twice, in the order the file gives them
 *										next					a path in the project with an optional anchor
 *																	("/_admin/#builder"), no scheme, no "//", or null
 *										start					who provides the start page: "starter" (the
 *																	starter pages of the kernel), "own" (a feature of
 *																	the application) or null (no statement)
 *										menu					null, or "folded": the workbench opens on the
 *																	application's place to start in with its menu folded.
 *																	The index carries the field only where it is
 *																	"folded"; an entry without it has no key for it
 *
 *										The index lists them by key, the one with the most features
 *										last.
 *
 *										Not run by itself: required by bin/build.php and
 *										bin/catalogue.php.
 */

const APPLICATIONS_LOCALES		= [ 'de_DE', 'en_US' ];
const APPLICATIONS_STARTS			= [ 'starter', 'own' ];
const APPLICATIONS_MENU				= 'folded';
const APPLICATIONS_FIELDS			= [ 'key', 'name', 'description', 'features', 'next', 'start', 'menu' ];

/**
 *	The reason a text per locale is not one, '' where it is
 *
 *	@param		mixed			$value
 *
 *	@return 	string
 */
function applicationTextProblem( mixed $value ): string {

	if( is_array( $value ) === false )
		return 'is not a map of locale => text';

	foreach( APPLICATIONS_LOCALES as $locale )
		if( is_string( $value[$locale] ?? null ) === false || trim( $value[$locale] ) === '' )
			return 'has no text for '. $locale;

	foreach( $value as $locale => $text )
		if( preg_match( '/^[a-z]{2,3}_[A-Z]{2}$/D', (string) $locale ) !== 1 || is_string( $text ) === false || trim( $text ) === '' )
			return 'holds "'. $locale. '", which is no locale with a text';

	return '';
}

/**
 *	Whether a path is one the project can reach: absolute, no scheme, no
 *	"//", no dot segments, an optional anchor after one "#"
 *
 *	@param		string		$next
 *
 *	@return 	bool
 */
function applicationNextIsPath( string $next ): bool {

	if( preg_match( '~^(?:/[A-Za-z0-9._%-]*)+(?:\#[A-Za-z0-9._%/-]+)?$~D', $next ) !== 1 || str_contains( $next, '//' ) === true )
		return false;

	foreach( explode( '/', explode( '#', $next )[0] ) as $segment )
		if( $segment === '.' || $segment === '..' )
			return false;

	return true;
}

/**
 *	Read every applications/<key>.php of a directory and hold it to the rules
 *
 *	@param		string		$directory		The applications/ directory; one that is not there holds none
 *	@param		array			$featureKeys	The keys of the features of this catalogue
 *
 *	@return 	array										[ applications sorted by key, the one with the most features last, problems ] - the problems are lines naming the file
 */
function readApplications( string $directory, array $featureKeys ): array {

	$applications	= [];
	$problems			= [];

	if( is_dir( $directory ) === false )
		return [ [], [] ];

	foreach( scandir( $directory ) ?: [] as $file ) {

		if( $file[0] === '.' )
			continue;

		$label = 'applications/'. $file;

		if( is_file( $directory. '/'. $file ) === false || str_ends_with( $file, '.php' ) === false ) {
			$problems[] = $label. ' is not an application - this directory holds <key>.php files and nothing else';
			continue;
		}

		$key = substr( $file, 0, -4 );

		ob_start();

		try {
			$entry = ( static fn( string $path ): mixed => include $path )( $directory. '/'. $file );
		}
		catch( \Throwable $e ) {
			ob_end_clean();
			$problems[] = $label. ' cannot be read: '. $e->getMessage();
			continue;
		}

		if( (string) ob_get_clean() !== '' ) {
			$problems[] = $label. ' prints something - it only returns an array';
			continue;
		}

		if( is_array( $entry ) === false ) {
			$problems[] = $label. ' must return an array';
			continue;
		}

		$before = count( $problems );

		foreach( array_diff( array_keys( $entry ), APPLICATIONS_FIELDS ) as $field )
			$problems[] = $label. ' has a field "'. $field. '" that applications do not have';

		foreach( array_diff( APPLICATIONS_FIELDS, array_keys( $entry ) ) as $field )
			$problems[] = $label. ' has no "'. $field. '" - every field is written, null where there is nothing';

		if( count( $problems ) > $before )
			continue;

		if( is_string( $entry['key'] ) === false || preg_match( '/^[a-z][a-z0-9-]*$/D', $entry['key'] ) !== 1 )
			$problems[] = $label. ': key must be a slug (a-z, 0-9, hyphen, starting with a letter)';
		elseif( $entry['key'] !== $key )
			$problems[] = $label. ': key "'. $entry['key']. '" is not the file name "'. $key. '"';

		foreach( [ 'name', 'description' ] as $field )
			if( ( $problem = applicationTextProblem( $entry[$field] ) ) !== '' )
				$problems[] = $label. ': '. $field. ' '. $problem;

		if( is_array( $entry['features'] ) === false || array_is_list( $entry['features'] ) === false || $entry['features'] === [] )
			$problems[] = $label. ': features must be a list of at least one feature key';
		else {
			$seen = [];

			foreach( $entry['features'] as $feature ) {

				$shown = is_string( $feature ) === true ? $feature : gettype( $feature );

				if( is_string( $feature ) === false || in_array( $feature, $featureKeys, true ) === false )
					$problems[] = $label. ': feature "'. $shown. '" is not a feature of this catalogue';
				elseif( isset( $seen[$feature] ) === true )
					$problems[] = $label. ': feature "'. $shown. '" is listed twice';

				if( is_string( $feature ) === true )
					$seen[$feature] = true;
			}
		}

		if( $entry['next'] !== null && ( is_string( $entry['next'] ) === false || applicationNextIsPath( $entry['next'] ) === false ) )
			$problems[] = $label. ': next must be null or a path in the project with an optional anchor, such as "/_admin/#builder" - no scheme, no "//"';

		if( $entry['start'] !== null && in_array( $entry['start'], APPLICATIONS_STARTS, true ) === false )
			$problems[] = $label. ': start must be "'. implode( '" or "', APPLICATIONS_STARTS ). '" or null';

		if( $entry['menu'] !== null && $entry['menu'] !== APPLICATIONS_MENU )
			$problems[] = $label. ': menu must be "'. APPLICATIONS_MENU. '" or null';

		if( count( $problems ) === $before ) {

			$applications[$key] = [
				'key'					=> $entry['key'],
				'name'				=> $entry['name'],
				'description'	=> $entry['description'],
				'features'		=> $entry['features'],
				'next'				=> $entry['next'],
				'start'				=> $entry['start'],
			];

			// Only where it says something: an entry without a menu has no key for it
			if( $entry['menu'] === APPLICATIONS_MENU )
				$applications[$key]['menu'] = APPLICATIONS_MENU;
		}
	}

	// No two applications under one name in a locale, case ignored: the
	// assistant shows them by it
	$names = [];

	foreach( $applications as $key => $entry )
		foreach( APPLICATIONS_LOCALES as $locale ) {

			$name = mb_strtolower( trim( $entry['name'][$locale] ) );

			if( isset( $names[$locale][$name] ) === false ) {
				$names[$locale][$name] = $key;
				continue;
			}

			$problems[] = 'applications/'. $names[$locale][$name]. '.php and applications/'. $key. '.php are both named "'. trim( $entry['name'][$locale] ). '" in '. $locale. ' - an application is shown by its name, so no two may carry one';
		}

	// By key, the application with the most features last (the later key where
	// two have as many): the assistant shows the set that holds the others at the end
	ksort( $applications, SORT_STRING );

	$last = null;

	foreach( $applications as $key => $entry )
		if( $last === null || count( $entry['features'] ) >= count( $applications[$last]['features'] ) )
			$last = $key;

	if( $last !== null ) {
		$entry = $applications[$last];
		unset( $applications[$last] );
		$applications[$last] = $entry;
	}

	return [ array_values( $applications ), $problems ];
}
