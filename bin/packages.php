<?php
declare(strict_types=1);
/**
 *	Nino features
 *	packages.php	What the catalogue publishes, read through the kernel of a
 *								Nino checkout: the features below features/, the themes below
 *								themes/<key>/ and the packages of blocks below blocks/<key>/.
 *								Required by bin/build.php, which builds the archives and the
 *								signed catalogue.json (format 2) from it, and by
 *								bin/catalogue.php, which prints the entries without them.
 *
 *								A package is held to the rules the kernel installs it by, with
 *								the same function: \Nino\Packages::check() over the files of
 *								the folder, against the register of the components of every
 *								feature of this repository, so that every group a theme names
 *								is read for what it is. What the entry says is computed from
 *								the files and never written down by hand:
 *
 *								requires.features		a feature: what its manifest requires. A theme:
 *																		themes, the feature of every group of its parts
 *																		and the features whose components its blocks call.
 *																		A package of blocks: the features whose
 *																		components its blocks call
 *								requires.fonts			the fonts the brand of theme.json suggests, the
 *																		stacks of the system left out
 *								requires.nino				the kernel the package was checked against:
 *																		^<major>.<minor> of its version
 *								parts								each group of a theme with the contract of its
 *																		part.json - the feature and the constraint, for
 *																		the page kernel@<constraint>
 *								blocks							the keys of the blocks of a theme; of a package
 *																		of blocks the key and the category of each
 *
 *								Beyond what the kernel installs, the build refuses a svg whose
 *								rebuild by \Nino\Images::cleanSvg() loses an element or an
 *								attribute - what the kernel writes is the rebuild, and an
 *								author is to see what is left of the picture - and a group of
 *								a part that no feature of the repository brings.
 *
 *								Basis is a feature of this repository like the others. A kernel
 *								that carries a Basis of its own is not one the catalogue is
 *								read with: kernelBasisProblem() says so in a sentence.
 *
 *								Nothing here exits or prints: a function answers its problems
 *								as sentences that name the file, and the script that required
 *								this decides.
 *
 *								Not run by itself.
 */

/**
 *	What is wrong with the kernel the scripts run against: a Basis of its own (_nino/Nino/Modules/Basis). Basis is a feature of this catalogue, and a kernel that
 *	carries it as a module serves that one before anything below features/ and refuses a directory of the name
 *
 *	@param		string		$root			The checkout, as the script was given it
 *
 *	@return 	string										A sentence, '' where there is nothing wrong
 */
function kernelBasisProblem( string $root ): string {

	return is_dir( $root. '/_nino/Nino/Modules/Basis' ) === true
		? 'The Nino at '. $root. ' ('. \Nino\VERSION. ') carries a Basis of its own (_nino/Nino/Modules/Basis): this catalogue needs a Nino without a Basis of its own, because Basis is a feature here'
		: '';
}

// Left out of every archive: what git, an editor or a desktop leaves behind. A pattern is matched against every path segment
const PACKAGES_EXCLUDED			= [ '.git*', '.DS_Store', 'Thumbs.db', '.idea', '.vscode', '*~', '*.swp', '*.swo', '*.swx', '.#*', '#*#', '*.orig', '*.rej', '*.bak' ];

// Where the sources of the other kinds live, below the root of this repository
const PACKAGES_FOLDERS			= [ \Nino\Packages::THEME => 'themes', \Nino\Packages::BLOCKS => 'blocks' ];

// The file a theme or a package of blocks says what it is in, and the picture of its tile
const PACKAGES_IDENTITY			= [ \Nino\Packages::THEME => 'theme.json', \Nino\Packages::BLOCKS => 'pack.json' ];
const PACKAGES_PREVIEW			= 'preview.webp';

/**
 *	Whether a path relative to the folder of a package stays out of its archive
 *
 *	@param		string		$relative			eg. 'tests/search-smoke.php'
 *	@param		string		$kind					One of \Nino\Packages::KINDS: the tests of a feature are not published, a theme has none
 *
 *	@return 	bool
 */
function packageExcluded( string $relative, string $kind ): bool {

	$segments = explode( '/', $relative );

	if( $kind === \Nino\Packages::FEATURE && $segments[0] === 'tests' )
		return true;

	foreach( $segments as $segment )
		foreach( PACKAGES_EXCLUDED as $pattern )
			if( fnmatch( $pattern, $segment, FNM_PERIOD ) === true || fnmatch( $pattern, $segment ) === true )
				return true;

	return false;
}

/**
 *	Every file and directory below the folder of a package that goes into its archive, sorted - so two builds of the same tree add the same entries
 *	in the same order. A symlink or anything else that is neither a file nor a directory is a problem, because the kernel refuses it on the other end
 *
 *	@param		string		$source				The folder
 *	@param		string		$kind					One of \Nino\Packages::KINDS
 *	@param		string		$label				What the folder is called in a sentence, 'themes/craft'
 *
 *	@return 	array										[ relative path => whether it is a directory, relative path of a file => its bytes, problems ]
 */
function packageCollect( string $source, string $kind, string $label ): array {

	$entries	= [];
	$files		= [];
	$problems	= [];

	$iterator = new \RecursiveIteratorIterator(
		new \RecursiveDirectoryIterator( $source, \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::UNIX_PATHS ),
		\RecursiveIteratorIterator::SELF_FIRST
	);

	foreach( $iterator as $file ) {

		$relative = substr( (string) $file->getPathname(), strlen( $source ) + 1 );

		if( packageExcluded( $relative, $kind ) === true )
			continue;

		if( $file->isLink() === true || ( $file->isFile() === false && $file->isDir() === false ) ) {
			$problems[] = $label. '/'. $relative. ' is neither a file nor a directory - an archive holds nothing else';
			continue;
		}

		if( $file->isFile() === true ) {

			$bytes = @file_get_contents( (string) $file->getPathname() );

			if( $bytes === false ) {
				$problems[] = $label. '/'. $relative. ' is not readable';
				continue;
			}

			$files[$relative] = $bytes;
		}

		$entries[$relative] = $file->isDir();
	}

	ksort( $entries, SORT_STRING );
	ksort( $files, SORT_STRING );

	return [ $entries, $files, $problems ];
}

/**
 *	The name two packages of a kind share, '' where they share none. A name is compared per locale, and one given as a plain string is that name in every
 *	locale, so a string collides with any string a map holds. Case is ignored: two rows reading "Seo" and "SEO" are as hard to tell apart as two reading the
 *	same, and the panel sorts by name rather than by spelling
 *
 *	@param		array|string	$one				A name
 *	@param		array|string	$other			Another name
 *
 *	@return 	string											The shared name, '' where there is none
 */
function sharedName( array|string $one, array|string $other ): string {

	$perLocale = static fn( array|string $name ): array => is_array( $name ) === true ? $name : [ '*' => $name ];

	foreach( $perLocale( $one ) as $locale => $name )
		foreach( $perLocale( $other ) as $otherLocale => $otherName )
			if( ( $locale === $otherLocale || $locale === '*' || $otherLocale === '*' )
				&& mb_strtolower( trim( $name ) ) === mb_strtolower( trim( $otherName ) ) )
				return trim( $name );

	return '';
}

/**
 *	The features below a directory, through the kernel: every folder is a feature the kernel reads, filed under one of its categories, with a key and a
 *	name of its own
 *
 *	@param		string		$directory		This repository's features/
 *
 *	@return 	array										[ key => manifest, problems ]
 */
function readFeatures( string $directory ): array {

	$warnings = [];

	set_error_handler( static function( int $level, string $message ) use ( &$warnings ): bool {

		// Silenced with @ by the code itself: not a warning anyone meant to see
		if( ( error_reporting() & $level ) !== 0 )
			$warnings[] = $message;

		return true;
	} );

	$features		= [];
	$problems		= [];

	foreach( scandir( $directory ) ?: [] as $entry ) {

		if( $entry[0] === '.' || is_dir( $directory. '/'. $entry ) === false )
			continue;

		$manifest = \Nino\Features::manifest( $directory. '/'. $entry );

		if( $manifest === null ) {
			$problems[] = 'features/'. $entry. ' does not validate:'. "\n  ". implode( "\n  ", $warnings );
			$warnings = [];
			continue;
		}

		if( isset( $features[ $manifest['key'] ] ) === true ) {
			$problems[] = 'features/'. $entry. ' claims the key "'. $manifest['key']. '" that features/'. basename( $features[ $manifest['key'] ]['dir'] ). ' already holds';
			continue;
		}

		// The kernel takes any slug, so that an older Nino can read a catalogue filing a feature under a category it predates; a typo would travel all
		// the way to a project's Features panel as a heading of its own. This is where a published feature is held to the kernel's vocabulary
		if( in_array( $manifest['category'], \Nino\Features::CATEGORIES, true ) === false ) {
			$problems[] = 'features/'. $entry. ' is filed under '. ( $manifest['category'] === '' ? 'no category' : '"'. $manifest['category']. '"' ). ' - a published feature names one of: '. implode( ', ', \Nino\Features::CATEGORIES );
			continue;
		}

		$features[ $manifest['key'] ] = $manifest;
	}

	restore_error_handler();

	if( $warnings !== [] )
		$problems[] = 'Not every feature validates:'. "\n  ". implode( "\n  ", $warnings );

	ksort( $features );

	// What a feature requires is a feature of this repository: a key that is neither is a typo, or a feature that is not published
	foreach( $features as $manifest )
		foreach( $manifest['requires'] as $required )
			if( isset( $features[$required] ) === false )
				$problems[] = 'features/'. basename( $manifest['dir'] ). ' requires the feature "'. $required. '", which this repository does not have';

	// The name is what a row in the Features panel says - the key is never on screen there, and neither is the directory - so two features carrying one
	// name are two rows a person cannot tell apart, in a list they are choosing from. Refused here, where it is one line to fix and nothing is published
	// yet; the kernel cannot refuse it, since the two would arrive from two catalogues it has no say over
	foreach( $features as $key => $manifest )
		foreach( $features as $otherKey => $other ) {

			if( strcmp( $key, $otherKey ) >= 0 )
				continue;

			$shared = sharedName( $manifest['name'], $other['name'] );

			if( $shared !== '' )
				$problems[] = 'features/'. basename( $manifest['dir'] ). ' and features/'. basename( $other['dir'] ). ' are both named "'. $shared. '" - a feature is shown by its name, so no two may carry one';
		}

	return [ $features, $problems ];
}

/**
 *	The project every package is read in: the features of the kernel and every feature of this repository on, so that the register holds every component
 *	and every group there is to be found in the catalogue. Nothing is written - the cache of the register is off
 *
 *	@param		array			$features			key => manifest, as readFeatures() answers
 *
 *	@return 	array										[ app data, problems ]
 */
function packageRegister( array $features ): array {

	$appData	= [ \Nino\Html::TREES_SWITCH => false, '/nino/modules' => [] ];
	$warnings	= [];

	foreach( $features as $manifest )
		$appData['/nino/modules'][] = '\\'. ltrim( (string) $manifest['module'], '\\' );

	set_error_handler( static function( int $level, string $message ) use ( &$warnings ): bool {

		// Silenced with @ by the code itself: not a warning anyone meant to see
		if( ( error_reporting() & $level ) !== 0 )
			$warnings[] = $message;

		return true;
	} );

	\Nino\Features::registerComponents( $appData );

	restore_error_handler();

	return [ $appData, $warnings === [] ? [] : [ 'The components of the features do not register:'. "\n  ". implode( "\n  ", $warnings ) ] ];
}

/**
 *	The entry a feature has in the catalogue, but for the archive: what its manifest says
 *
 *	@param		array			$manifest
 *
 *	@return 	array
 */
function featureEntry( array $manifest ): array {

	$entry = [
		'kind'					=> \Nino\Packages::FEATURE,
		'key'						=> $manifest['key'],
		'version'				=> $manifest['version'],
		'name'					=> $manifest['name'],
		'description'		=> $manifest['description'],
		'category'			=> $manifest['category'],
		'maturity'			=> $manifest['maturity'] ?? '',
		'directory'			=> basename( $manifest['dir'] ),
		'requires'			=> [ 'nino' => $manifest['nino'], 'php' => array_values( $manifest['php']['ext'] ), 'features' => array_values( $manifest['requires'] ) ],
	];

	// The kernel refuses an empty description where it accepts a missing one
	if( is_string( $entry['description'] ) === true && trim( $entry['description'] ) === '' )
		unset( $entry['description'] );

	// Optional by design: most features carry no badge
	if( $entry['maturity'] === '' )
		unset( $entry['maturity'] );

	return $entry;
}

/**
 *	The folders of one kind of package below this repository: themes/<key>/ or blocks/<key>/. What stands beside them - a .gitkeep - is no package
 *
 *	@param		string		$root					The root of this repository
 *	@param		string		$kind					THEME or BLOCKS
 *
 *	@return 	array										folder name => absolute path
 */
function packageFolders( string $root, string $kind ): array {

	$parent		= $root. '/'. PACKAGES_FOLDERS[$kind];
	$folders	= [];

	foreach( is_dir( $parent ) === true ? (array) scandir( $parent ) : [] as $name ) {

		if( is_string( $name ) === false || $name === '' || $name[0] === '.' || is_dir( $parent. '/'. $name ) === false )
			continue;

		$folders[$name] = $parent. '/'. $name;
	}

	return $folders;
}

/**
 *	The blocks a folder of a theme or of a package of blocks holds, as the files of it say
 *
 *	@param		array			$files				path => bytes
 *	@param		string		$prefix				'blocks/' for a theme, '' for a package of blocks
 *
 *	@return 	array										block key => [ 'json', 'tpl', 'texts', 'pictures' ]
 */
function packageBlocks( array $files, string $prefix ): array {

	$blocks = [];

	foreach( $files as $path => $bytes ) {

		if( str_starts_with( $path, $prefix ) === false )
			continue;

		$rest = explode( '/', substr( $path, strlen( $prefix ) ) );

		if( count( $rest ) < 2 )
			continue;

		[ $block, $name ] = [ $rest[0], implode( '/', array_slice( $rest, 1 ) ) ];

		if( $name === 'block.json' )
			$blocks[$block]['json'] = $bytes;
		else if( $name === 'block.tpl' )
			$blocks[$block]['tpl'] = $bytes;
		else if( count( $rest ) === 3 && $rest[1] === 'text' && str_ends_with( $rest[2], '.json' ) === true )
			$blocks[$block]['texts'][substr( $rest[2], 0, -5 )] = $bytes;
		else if( count( $rest ) === 3 && $rest[1] === 'images' )
			$blocks[$block]['pictures'][$rest[2]] = $bytes;
	}

	ksort( $blocks, SORT_STRING );

	return $blocks;
}

/**
 *	What the rebuild of a svg loses: the elements and the attributes that stand in the picture and not in what cleanSvg() makes of it
 *
 *	@param		string		$svg
 *
 *	@return 	string									'' where the rebuild has all of it; else what is lost, or that the picture is refused as a whole
 */
function packageSvgLoss( string $svg ): string {

	$clean = \Nino\Images::cleanSvg( $svg );

	if( $clean === null )
		return 'the picture is refused: it is no svg of the shapes, the size and the form the kernel keeps (no document type, an svg in the svg namespace, at least one shape)';

	$count = static function( string $xml ): array {

		$previous	= libxml_use_internal_errors( true );
		$document	= new \DOMDocument();
		$loaded		= $document->loadXML( $xml, LIBXML_NONET | LIBXML_NOBLANKS | LIBXML_NOCDATA );
		$elements	= 0;
		$attributes	= 0;

		libxml_clear_errors();
		libxml_use_internal_errors( $previous );

		if( $loaded === false )
			return [ 0, 0 ];

		foreach( $document->getElementsByTagName( '*' ) as $element ) {
			$elements++;
			$attributes += $element->attributes === null ? 0 : $element->attributes->length;
		}

		return [ $elements, $attributes ];
	};

	[ $elements, $attributes ]	= $count( $svg );
	[ $kept, $keptAttributes ]	= $count( $clean );

	if( $kept >= $elements && $keptAttributes >= $attributes )
		return '';

	return 'the rebuild of the picture by the kernel loses '. ( $elements - $kept ). ' of its '. $elements. ' elements and '. ( $attributes - $keptAttributes ). ' of its '. $attributes. ' attributes - what the kernel writes is the rebuild';
}

/**
 *	The kernel a package is checked against, as the constraint it asks for: ^<major>.<minor>
 *
 *	@return 	string
 */
function packageNino(): string {

	return preg_match( '/^(\d+)\.(\d+)\./', \Nino\VERSION, $found ) === 1 ? '^'. $found[1]. '.'. $found[2] : '*';
}

/**
 *	One theme or package of blocks, read from the files of its folder: held to the rules the kernel installs it by and to the build's own, and the entry
 *	of the catalogue worked out of it
 *
 *	@param		array			&$appData			The register, as packageRegister() answers
 *	@param		string		$kind					THEME or BLOCKS
 *	@param		string		$key					The name of its folder
 *	@param		array			$files				path => bytes, as the archive will hold them
 *	@param		array			$features			key => manifest, the features of this repository
 *
 *	@return 	array										[ the entry without archive, sha256, size, released and preview - or null, problems ]
 */
function packageEntry( array &$appData, string $kind, string $key, array $files, array $features ): array {

	$label		= PACKAGES_FOLDERS[$kind]. '/'. $key;
	$theme		= $kind === \Nino\Packages::THEME;
	$identity	= PACKAGES_IDENTITY[$kind];
	$problem	= \Nino\Packages::check( $appData, $kind, $files );

	if( $problem !== null )
		return [ null, [ $label. ( ( $problem['path'] ?? '' ) === '' ? ': ' : '/' ). \Nino\Packages::why( $problem ) ] ];

	$says = \Nino\Packages::identity( $kind, $files );

	if( $says === null || $says['key'] !== $key )
		return [ null, [ $label. '/'. $identity. ': "key" is "'. (string) ( $says['key'] ?? '' ). '" and the folder is "'. $key. '" - a package is installed under its key' ] ];

	if( isset( $files[PACKAGES_PREVIEW] ) === false )
		return [ null, [ $label. ': has no '. PACKAGES_PREVIEW. ' - the catalogue shows every '. ( $theme ? 'theme' : 'package of blocks' ). ' by a picture' ] ];

	$problems = [];

	foreach( $files as $path => $bytes )
		if( str_ends_with( $path, '.svg' ) === true && ( $loss = packageSvgLoss( $bytes ) ) !== '' )
			$problems[] = $label. '/'. $path. ': '. $loss;

	$json			= (array) json_decode( $files[$identity], true );
	$required	= $theme === true ? [ 'themes' ] : [];
	$blocks		= [];

	foreach( packageBlocks( $files, $theme === true ? 'blocks/' : '' ) as $block => $parts ) {

		$read = \Nino\Blocks::validate( $appData, (string) $block, [ 'json' => (string) ( $parts['json'] ?? '' ), 'tpl' => (string) ( $parts['tpl'] ?? '' ), 'texts' => $parts['texts'] ?? [], 'pictures' => $parts['pictures'] ?? [] ] );

		if( is_string( $read ) === true ) {
			$problems[] = $label. '/'. ( $theme === true ? 'blocks/' : '' ). $block. ': '. $read;
			continue;
		}

		if( $read['missing'] !== [] ) {
			$problems[] = $label. '/'. ( $theme === true ? 'blocks/' : '' ). $block. ': calls components of the features '. implode( ', ', $read['missing'] ). ', which this repository does not have';
			continue;
		}

		$required	= array_merge( $required, $read['features'] );
		$blocks[]	= $theme === true ? $block : [ 'key' => $block, 'category' => $read['category'] ];
	}

	$entry = [
		'kind'					=> $kind,
		'key'						=> $key,
		'version'				=> $says['version'],
		'name'					=> $json['name'],
		'description'		=> $json['description'] ?? '',
	];

	if( is_string( $entry['description'] ) === true && trim( $entry['description'] ) === '' )
		unset( $entry['description'] );

	$requires = [ 'nino' => packageNino() ];

	if( $theme === true ) {

		$parts = [];

		foreach( $files as $path => $bytes ) {

			if( preg_match( '#^parts/([^/]+)/part\.json$#D', $path, $found ) !== 1 )
				continue;

			$group = $found[1];
			$contract = (string) ( ( (array) json_decode( $bytes, true ) )['contract'] ?? '' );

			if( $group === 'page' ) {
				$parts[$group] = [ 'contract' => $contract ];
				continue;
			}

			$owner = \Nino\Components::groups( $appData )[$group]['feature'] ?? null;

			if( $owner === null ) {
				$problems[] = $label. '/parts/'. $group. ': no feature of this repository brings the group "'. $group. '" - a part is read for the components of its group';
				continue;
			}

			$required[]		= $owner;
			$parts[$group]	= [ 'feature' => $owner, 'contract' => substr( $contract, strlen( $owner ) + 1 ) ];
		}

		ksort( $parts, SORT_STRING );

		// The stacks of the system are no fonts of a repository: which they are, the feature that draws the brand says
		$fonts = [];

		foreach( [ 'font-title', 'font-text', 'font-button' ] as $role ) {

			$font = ( (array) ( $json['brand'] ?? [] ) )[$role] ?? null;

			if( is_string( $font ) === false || in_array( $font, $fonts, true ) === true )
				continue;

			if( class_exists( '\\Nino\\Modules\\Themes\\Brand' ) === false ) {
				$problems[] = $label. '/theme.json: names the font "'. $font. '" and this repository has no feature Themes, which says what a font of the system is';
				break;
			}

			if( isset( \Nino\Modules\Themes\Brand::SYSTEM[$font] ) === false )
				$fonts[] = $font;
		}

		sort( $fonts );
	}

	$required = array_values( array_unique( $required ) );
	sort( $required );

	foreach( $required as $feature )
		if( isset( $features[$feature] ) === false && \Nino\Features::get( $appData, $feature ) === null )
			$problems[] = $label. ': requires the feature "'. $feature. '", which neither this repository nor the kernel has';

	$requires['features'] = $required;

	if( $theme === true )
		$requires['fonts'] = $fonts;

	$entry['requires']	= $requires;

	if( $theme === true )
		$entry['parts'] = $parts;

	$entry['blocks'] = $blocks;

	return [ $problems === [] ? $entry : null, $problems ];
}
