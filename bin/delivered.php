<?php
declare(strict_types=1);
/**
 *	Nino features
 *	delivered.php	The features of this repository that the kernel a run reads it
 *								with still delivers itself. A kernel that carries a module of
 *								the name serves \Nino\Modules\<Name> from it before anything
 *								below features/, and refuses a feature directory of that name
 *								("the directory name is taken"): the copy here rests, and
 *								every script that lists, copies or builds features leaves it
 *								out and says so. The question goes to the kernel - is
 *								\Nino\Modules\<Name> a module of the kernel - and not to a
 *								version, so that the answer turns by itself with the kernel
 *								that no longer delivers the folder, and with it the rest.
 *
 *								Required by bin/applicable.php, which names the features to
 *								copy, and by bin/packages.php, which reads and builds them,
 *								after the kernel is loaded. Not run by itself.
 */

// key => directory: the features of this repository that a kernel may deliver as a module
const DELIVERED_FEATURES = [ 'basis' => 'Basis' ];

/**
 *	The features of this repository that the kernel delivers itself
 *
 *	@return 	array										key => directory name below features/, in the order of DELIVERED_FEATURES
 */
function deliveredFeatures(): array {

	return array_filter( DELIVERED_FEATURES, static fn( string $directory ): bool => \Nino\Features::moduleOwner( $directory ) === 'the kernel' );
}

/**
 *	What is wrong with a module of the kernel that has the name of a feature of this repository but is not that feature: a folder with no feature.php, or one that
 *	names another key. The kernel then refuses the copy below features/ and delivers nothing the catalogue can build on, so the scripts stop with the sentence
 *
 *	@return 	array										Sentences, [] where there is nothing wrong
 */
function deliveredProblems(): array {

	$problems = [];

	foreach( deliveredFeatures() as $key => $directory ) {

		$folder = dirname( (string) ( new \ReflectionClass( \Nino\Features::class ) )->getFileName(), 2 ). '/Modules/'. $directory;
		$manifest = is_file( $folder. '/feature.php' ) === true ? ( static fn( string $path ): mixed => include $path )( $folder. '/feature.php' ) : null;

		if( is_array( $manifest ) === false || ( $manifest['key'] ?? '' ) !== $key )
			$problems[] = 'The kernel has a module _nino/Nino/Modules/'. $directory. ' that is no feature "'. $key. '" (a feature.php with that key is not there), so that the feature '. $directory. ' of this repository can neither be used with it nor left to it - use a kernel that delivers it whole or a kernel without the folder';
	}

	return $problems;
}

/**
 *	The kernel reads every folder below features/ whenever it asks which features there are - to see whether a block calls the components of one that is off -
 *	and says of the copy of a feature it delivers that the directory name is taken. That is what this copy is while it rests, so the scripts that read the
 *	repository do not pass the warning on. Every other warning goes the way it would have gone
 *
 *	@return 	void
 */
function ignoreDeliveredWarnings(): void {

	$directories = array_values( deliveredFeatures() );

	set_error_handler( static function( int $level, string $message ) use ( $directories ): bool {

		foreach( $directories as $directory )
			if( str_contains( $message, 'features/'. $directory. '/feature.php: the directory name is taken' ) === true )
				return true;

		return false;
	} );
}
