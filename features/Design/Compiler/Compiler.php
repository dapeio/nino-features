<?php
declare(strict_types=1);
/**
 *	Nino									A compact filesystembased php framework
 *	Modules\Design\Compiler		see features/Design/Design.php for the feature's
 *												own docblock
 *
 *	@package							Dape/Nino
 *	@author								David Perchermeier <mail@dape.io>
 *	@link									https://github.com/dapeio/nino
 */
namespace Nino\Modules\Design {

	/**
	 *	Nino							A compact filesystembased php framework
	 *	Compiler					Turns a setup into the one stylesheet a project's look is,
	 *										in the order the cascade needs it: the token and role
	 *										layer the feature ships, the chosen frames, the chosen
	 *										part sets, and last the block that says which step of
	 *										every triple is live.
	 *
	 *										A set declares what its three steps *are* rather than a
	 *										value a knob then modifies - +1rem behaves differently on
	 *										a default of 5rem than on 2rem, and some scales are not
	 *										linear at all. So the knob never computes: it picks, and
	 *										what it picks is one selection line per token, gathered
	 *										into a single block at the end. CSS cannot compose a
	 *										variable name, which is why that block exists at all.
	 *
	 *										write() refuses a file it did not write. The delivered
	 *										theme.css is explicitly editable by hand, so a project may
	 *										well have something in it the first time this runs, and
	 *										losing that silently is the mistake nobody forgives.
	 *
	 *	@package					Dape/Nino
	 *	@author						David Perchermeier <mail@dape.io>
	 *	@link								https://github.com/dapeio/nino
	 */
	class Compiler {

		/*	The compiled sheet marks its own sections. "====" rather than the
			"----" the hand-written stylesheets use, because everything below is
			concatenated library css that may contain anything - base.css carries
			the two section markers the delivered theme.css has, and a marker that
			cannot be told from its content is not a marker */
		public const string SECTION = '/* ==== %d. %s ==== */';

		// The file a project's look is. Named by the css bundle the wizard
		// seeds (see \Nino\Install\Setup::BASE_STYLESHEETS), so compiling over
		// it needs no bundle change: the entry is already there
		public const string TARGET = '/assets/theme.css';

		// The marker the header carries. Its value is the sha256 of everything
		// below the header - so a file somebody edited no longer matches its
		// own claim, and write() can tell the two apart
		public const string STAMP = 'nino-design-sha256';

		/**
		 *	The stylesheet a setup produces
		 *
		 *	@param		array 		$setup				A normalised setup (see Setup::normalize())
		 *	@param		string		$libraryDir		The feature's library
		 *	@param		array 		&$notes				(reference) What was missing on the way
		 *
		 *	@return 	string								The complete css, header and all
		 */
		public static function compile( array $setup, string $libraryDir, array &$notes = [] ): string {

			$libraryDir = rtrim( $libraryDir, '/' );
			$parts 			= [];
			$selection 	= [];

			// 1. the tokens and the roles they are assigned to. The feature's own
			// copy: _admin/install/ is gone from a project by the time this runs
			$base = $libraryDir. '/base.css';

			if( is_file( $base ) === false )
				$notes[] = 'the library has no base.css - the compiled sheet carries no tokens at all';
			else
				$parts[] = sprintf( self::SECTION, 1, 'the design tokens and their roles' ). "\n". self::_read( $base );

			// 2. the root size, as the percentage pair Nino.css reads
			$size = Setup::SIZES[ $setup['size'] ?? 'm' ] ?? Setup::SIZES['m'];
			$parts[] = sprintf( self::SECTION, 2, 'the root size' ). "\n"
				. ":root { --nino-base-size: ". $size[0]. "; }\n"
				. "@media (min-width: 768px) { :root { --nino-base-size: ". $size[1]. "; } }";

			// 3. the frames, then 4. the sets - both in PARTS order, which is
			// the order the cascade wants them in
			$number = 3;

			foreach( Setup::PARTS as $part => $kind ) {

				$set = (string) ( $setup['parts'][$part]['set'] ?? '' );

				if( $set === '' )
					continue;

				$file = Setup::file( $libraryDir, $part, $set );

				if( $file === '' ) {
					// A frame without a stylesheet is ordinary - its markup may need
					// none. A set without one is the choice not existing
					if( $kind === 'set' )
						$notes[] = 'no stylesheet for "'. $part. '" set "'. $set. '"';
					continue;
				}

				$css 	= self::_read( $file );
				$step = Setup::step( $setup, $part );

				$parts[] = sprintf( self::SECTION, $number++, $part. ': '. $set. ( $kind === 'set' ? ' ('. $step. ')' : '' ) ). "\n". $css;

				/*	Every --name--less / --name--default / --name--more triple the
					set declares becomes one selection line. Collected across parts
					rather than written beside each set, so the whole knob state is
					one readable block and a project without the feature can still
					move a knob by editing one line */
				// Scanned without comments: a set's own documentation shows the
				// shape of a triple, and an example in a comment is not a
				// declaration - the skeletons every set starts from carry one
				if( $kind === 'set' && preg_match_all( '/--([A-Za-z0-9-]+)--(?:'. implode( '|', Setup::STEPS ). ')\s*:/', self::_uncomment( $css ), $found ) > 0 )
					foreach( array_unique( $found[1] ) as $token )
						$selection[$token] = "\t--". $token. ': var(--'. $token. '--'. $step. ');';
			}

			if( $selection !== [] ) {
				ksort( $selection );
				$parts[] = sprintf( self::SECTION, $number, 'the knob positions' ). "\n:root {\n". implode( "\n", $selection ). "\n}";
			}

			$body = implode( "\n\n", $parts ). "\n";

			return self::_header( $setup, $body ). $body;
		}

		/**
		 *	Write the compiled sheet, unless the file on disk is not one of ours
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$css					What compile() returned
		 *	@param		bool			$force				Overwrite a file this never wrote
		 *
		 *	@return 	true|string							true, or why not
		 */
		public static function write( array &$appData, string $css, bool $force = false ): true|string {

			$path = \Nino\Filesystem::path( $appData, self::TARGET );

			if( $force === false && is_file( $path ) === true && self::stamped( (string) @file_get_contents( $path ) ) === false )
				return 'assets/theme.css was not written by Design - it is the delivered file, or somebody edited it. Nothing was overwritten.';

			return \Nino\Filesystem::putFileContent( $appData, self::TARGET, $css ) === true
				? true
				: 'could not write '. self::TARGET;
		}

		/**
		 *	Whether a stylesheet is one this wrote and nobody has touched since:
		 *	its header claims a digest, and the body still has it
		 *
		 *	@param		string		$css					A stylesheet as it is on disk
		 *
		 *	@return 	bool
		 */
		public static function stamped( string $css ): bool {

			if( preg_match( '/'. preg_quote( self::STAMP, '/' ). ':\s*([a-f0-9]{64})/', $css, $found ) !== 1 )
				return false;

			$at = strpos( $css, "*/\n" );

			return $at !== false && hash( 'sha256', substr( $css, $at + 3 ) ) === $found[1];
		}

		/**
		 *	The header the written file carries: what made it, out of what, and
		 *	the digest that tells a later run whether it is still untouched
		 *
		 *	@param		array 		$setup				A normalised setup
		 *	@param		string		$body					Everything below the header
		 *
		 *	@return 	string
		 */
		private static function _header( array $setup, string $body ): string {

			$lines = [];

			foreach( Setup::PARTS as $part => $kind ) {
				$set = (string) ( $setup['parts'][$part]['set'] ?? '' );
				$lines[] = ' *   '. str_pad( $part, 9 ). ( $set !== '' ? $set : '-' )
					. ( $kind === 'set' ? '  ('. Setup::step( $setup, $part ). ')' : '' );
			}

			return "/*\tGenerated by the Design feature. Do not edit - the next compile\n"
				. " *\trewrites this file, and anything put here is gone.\n"
				. " *\n"
				. " *\tTo change it: the Design panel, or data/design.php and compile again.\n"
				. " *\tTo take it over by hand: delete the line below, and Design will\n"
				. " *\trefuse to overwrite the file from then on.\n"
				. " *\n"
				. " *\t". self::STAMP. ": ". hash( 'sha256', $body ). "\n"
				. " *\n"
				. " *\troot size  ". (string) ( $setup['size'] ?? 'm' ). "\n"
				. " *\tknob       ". (string) ( $setup['step'] ?? 'default' ). "\n"
				. " *\n"
				. implode( "\n", $lines ). "\n"
				. " */\n";
		}

		/**
		 *	Css with its comments taken out, for reading rather than for writing
		 *
		 *	@param		string		$css
		 *
		 *	@return 	string
		 */
		private static function _uncomment( string $css ): string {
			return (string) preg_replace( '~/\*.*?\*/~s', '', $css );
		}

		/**
		 *	A library file, without a trailing newline surprise
		 *
		 *	@param		string		$path
		 *
		 *	@return 	string
		 */
		private static function _read( string $path ): string {
			return rtrim( (string) @file_get_contents( $path ), "\n" ). "\n";
		}

	}
}
