<?php
declare(strict_types=1);
/**
 *	Nino								A compact filesystembased php framework
 *	Modules\Design			see _nino/Nino/Modules/Modules.php for the
 *											package-level docblock
 *
 *	@package						Dape/Nino
 *	@author							David Perchermeier <mail@dape.io>
 *	@link								https://github.com/dapeio/nino
 */
namespace Nino\Modules {

	/**
	 *	Nino							A compact filesystembased php framework
	 *	Design						The look of a site, chosen per part of a page rather than
	 *										per page, and compiled into the one stylesheet the css
	 *										bundle already names: assets/theme.css.
	 *
	 *										Nino 1.2 delivers that file fixed, out of the setup
	 *										wizard's base unit. This feature writes its own over it,
	 *										from a set per part - ATF, Section, Article, Buttons,
	 *										Forms, Lists & tables, Blocks - plus a header and a footer,
	 *										which are the two parts that bring markup with them.
	 *
	 *										Six pieces, and they are deliberately separable:
	 *
	 *										  Setup			what was chosen (/data/design.php)
	 *										  Colours		the palette half, solved from two colours
	 *										  Compiler	what that produces (assets/theme.css)
	 *										  Previous	the one version before an apply (/data/design-previous.php)
	 *										  Preview		what that would look like, written nowhere
	 *										  Admin			the screen that edits the first two
	 *
	 *										Nothing here runs on a public request. The compiled
	 *										stylesheet is an ordinary file the bundle picks up, so a
	 *										site keeps working with the feature removed - and keeps
	 *										its setup, so installing it again carries on where it
	 *										left off rather than starting over.
	 *
	 *	@package					Dape/Nino
	 *	@author						David Perchermeier <mail@dape.io>
	 *	@link								https://github.com/dapeio/nino
	 */
	class Design {

		public const string KEY = 'design';

		/**
		 *	The sets, frames and the token layer this feature ships
		 *
		 *	@return 	string								An absolute directory
		 */
		public static function libraryDir(): string {
			return __DIR__. '/library';
		}

		/**
		 *	Everything that decides what Compiler::compile() will produce, as
		 *	one hash: the setup's own choices, and the library files those
		 *	choices point at.
		 *
		 *	Written by apply() and compared by the panel's state(), which used
		 *	to answer "does the file on disk still match the screen?" by
		 *	compiling the whole stylesheet again and hashing the result - 43 ms
		 *	of colour solving per list, per save, and once more at the end of
		 *	every apply, which therefore compiled twice.
		 *
		 *	'compiled' is the record of a previous apply rather than a choice,
		 *	so it decides nothing here and is left out.
		 *
		 *	The colour tables are not hashed - they are code - but what they
		 *	produce can change under a setup that did not. Colours::REVISION
		 *	joins the input where Saturation, Contrast or Depth stands off
		 *	position 2, the only place the tables moved: such a project reads
		 *	"saved, not applied" once after the update and applies again, and a
		 *	project with all three at 2 compiles the same bytes as before and
		 *	keeps reading "current".
		 *
		 *	@param		array 		$setup				A normalized setup
		 *	@param		string		$library			The library directory its parts are chosen from
		 *
		 *	@return 	string
		 */
		public static function fingerprint( array $setup, string $library ): string {

			$files = [];

			foreach( array_keys( Design\Setup::PARTS ) as $part ) {
				$set 					= (string) ( $setup['parts'][$part]['set'] ?? '' );
				$file 				= Design\Setup::file( $library, $part, $set );
				$files[$part]	= [ $set, ( $file === '' || is_file( $file ) === false ) ? '' : (string) hash_file( 'sha256', $file ) ];
			}

			unset( $setup['compiled'] );

			$input = [ $setup, $files ];

			// Position 2 is the framework's own, and the one every table in
			// Colours is anchored on
			foreach( [ 'saturation', 'contrast', 'depth' ] as $knob )
				if( ( $setup['colours'][$knob] ?? 2 ) !== 2 ) {
					$input[] = Design\Colours::REVISION;
					break;
				}

			return hash( 'sha256', serialize( $input ) );
		}

		/**
		 *	The /_admin screen this feature brings along - collected by
		 *	Admin::panels() through Modules::collect(), so it appears in the
		 *	workbench exactly while this feature is active
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	array										Panel class names
		 */
		public static function adminPanels( array &$appData ): array {
			return [ \Nino\Modules\Design\Admin::class ];
		}

		/**
		 *	Read the setup, compile it, write it. The one path the panel, the
		 *	upgrade hook and a test all take, so there is one answer to "what
		 *	does applying actually do"
		 *
		 *	What was there is kept first: the three files and the setup they were
		 *	written under go to Previous, unless every file that exists already
		 *	holds the bytes this would write - a second apply of the same thing
		 *	must not replace the one version there is with a copy of the present.
		 *	The setup kept is the one the files answer to (see _applied()), which
		 *	is not always the one on disk: the panel saves a draft before it
		 *	applies, so data/design.php already holds the new choices by now. If
		 *	keeping it fails, nothing is written.
		 *
		 *	A write that fails after that leaves the site as the call found it:
		 *	the files already written are put back from the bytes held before the
		 *	first one (a frame that was not there is removed again), and the slot
		 *	is what it was - the one that was kept written again, or removed where
		 *	this apply made it. The stylesheet never stays over frames it was not
		 *	drawn against, and a version is never kept for a change that did not
		 *	happen.
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$notes				(reference) What was replaced or missing
		 *	@param		bool			$force				Overwrite a theme.css or a frame this never wrote
		 *
		 *	@return 	true|string							true, or why not
		 */
		public static function apply( array &$appData, array &$notes = [], bool $force = false ): true|string {

			$library 	= self::libraryDir();
			$setup 		= Design\Setup::read( $appData, $library, $notes );
			$outputs 	= self::_outputs( $setup, $library, $notes );

			/*	Every file this would write is asked first, before any of them is
				written. The refusal says "Nothing was overwritten", and the
				stylesheet used to go to disk before the header was asked - so a
				project that had taken its header over by hand was left with a new
				stylesheet, its old header, and a message saying neither happened	*/
			foreach( array_keys( $outputs ) as $target ) {

				$refusal = Design\Compiler::refusal( $appData, $target, $force );

				if( $refusal !== '' )
					return $refusal;
			}

			/*	The version before this one, for the panel to restore. Taken
				after the refusals and before the first write, so a refused apply
				leaves the slot alone and a failed snapshot leaves the files	*/
			$before = Design\Previous::capture( $appData, Design\Previous::targets() );
			$before['setup'] = self::_applied( $before['setup'], $before['files'][Design\Compiler::TARGET] );
			$same 	= true;

			foreach( $outputs as $target => $output )
				if( $before['files'][$target] !== null && $before['files'][$target] !== $output['bytes'] )
					$same = false;

			$slot = $same === false ? Design\Previous::read( $appData ) : null;

			if( $same === false && Design\Previous::write( $appData, $before ) === false )
				return 'could not keep the previous version in '. ltrim( Design\Previous::PATH, '/' ). ' - nothing was overwritten';

			/*	The stylesheet first, then the frames - the order of $outputs. A
				write that fails leaves the site as this call found it, not half
				way: the stylesheet used to stay over the old markup of a frame
				the disk refused, and the version kept for a change that did not
				happen stayed behind as the version before. What was written is
				put back from $before, which holds the files as they were before
				the first write (a frame that was not there is taken away again),
				and so is the slot: the one that was there is written again, and
				one this apply made is removed - a file that was no slot counts as
				none, the way read() has it	*/
			$replaced = [];

			foreach( $outputs as $target => $output ) {

				$result = $output['part'] === ''
					? Design\Compiler::write( $appData, $output['bytes'], $force )
					: Design\Compiler::writeFrame( $appData, $output['part'], $output['markup'], $output['set'], $force );

				if( $result !== true ) {

					self::_putBack( $appData, $before, $replaced );

					if( $slot !== null )
						Design\Previous::write( $appData, $slot );
					elseif( $same === false )
						@unlink( \Nino\Filesystem::path( $appData, Design\Previous::PATH ) );

					return $result;
				}

				$replaced[] = $target;
			}

			/*	What was compiled, so the panel can say whether the file on disk
				still answers to the setup beside it - and 'input', what it was
				compiled FROM, so answering that costs a hash instead of a
				second compile. Compiling this library takes 43 ms, and the
				panel asked for the answer on every list, every save and again
				at the end of every apply.

				'setup' is the choices themselves: a draft saved afterwards
				replaces them in data/design.php, and the next apply has to keep
				the ones these files were written under, not the draft	*/
			$applied = $setup;
			unset( $applied['compiled'] );

			$setup['compiled'] = [
				'at' 		=> gmdate( 'c' ),
				'sha'		=> hash( 'sha256', $outputs[Design\Compiler::TARGET]['bytes'] ),
				'input'	=> self::fingerprint( $setup, $library ),
				'setup'	=> $applied,
			];

			return Design\Setup::write( $appData, $setup ) === true ? true : 'compiled, but could not write '. Design\Setup::PATH;
		}

		/**
		 *	What applying would do to every file it writes, without doing any of
		 *	it: whose the file is now, whether the bytes would change, and which
		 *	shortcodes a frame has that the variant replacing it does not.
		 *
		 *	The panel asks this before every apply and puts the answer in the
		 *	confirmation. A shortcode is the whole token - [consent-settings],
		 *	[template /templates/html-footer-nav] - and it is the one thing a
		 *	frame carries that a person puts in by hand and the library cannot
		 *	know about. [[fills]] are not compared: they are text, and a
		 *	template that loses one still renders.
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	array										Per file: target, exists, state (missing, ours, edited, foreign), changes, lost
		 */
		public static function plan( array &$appData ): array {

			$library 	= self::libraryDir();
			$notes 		= [];
			$setup 		= Design\Setup::read( $appData, $library, $notes );
			$plan 		= [];

			foreach( self::_outputs( $setup, $library, $notes ) as $target => $output ) {

				$path 		= \Nino\Filesystem::path( $appData, $target );
				$exists 	= $path !== '' && is_file( $path ) === true;
				$content 	= $exists === true ? (string) @file_get_contents( $path ) : '';
				$lost 		= [];

				if( $exists === true && $output['part'] !== '' )
					$lost = array_values( array_diff( self::_shortcodes( $content ), self::_shortcodes( $output['markup'] ) ) );

				$plan[] = [
					'target' 	=> $target,
					'exists' 	=> $exists,
					'state' 	=> $exists === true ? Design\Compiler::ownership( $content, $output['part'] !== '' ) : 'missing',
					'changes' => $exists === false || $content !== $output['bytes'],
					'lost' 		=> $lost,
				];
			}

			return $plan;
		}

		/**
		 *	Put the previous version back - and the present in its place, so
		 *	restoring is a swap and can itself be undone.
		 *
		 *	All or nothing as far as a failing disk allows: the present is held
		 *	in memory first, each file is written from the slot, and a write
		 *	that fails puts back the ones already replaced and leaves the slot
		 *	as it was. A file the slot does not have (it was not there when the
		 *	version was kept) is left as it is - never deleted, because the page
		 *	includes the frames.
		 *
		 *	The setup goes with the files: the choices they were written under,
		 *	and the record of what was compiled from them. Restoring a stylesheet
		 *	and leaving the record would have the panel say "current" over a site
		 *	that shows an older look. What replaces them in data/design.php is
		 *	whatever it holds at that moment - a draft saved since included - so
		 *	restoring again gives exactly that back.
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$notes				(reference) What the slot did not hold, and a slot that could not be written after the files were back
		 *
		 *	@return 	true|string							true, or why not
		 */
		public static function restore( array &$appData, array &$notes = [] ): true|string {

			$slot = Design\Previous::read( $appData );

			if( $slot === null )
				return 'there is no previous version';

			$present 	= Design\Previous::capture( $appData, Design\Previous::targets() );
			$replaced = [];

			foreach( Design\Previous::targets() as $target ) {

				$bytes = $slot['files'][$target];

				if( $bytes === null ) {
					$notes[] = $target. ' was not there when the previous version was kept - the project keeps the one it has';
					continue;
				}

				if( \Nino\Filesystem::putFileContent( $appData, $target, $bytes ) === false ) {
					self::_putBack( $appData, $present, $replaced );
					return 'could not write '. $target. ' - the previous version was not restored';
				}

				$replaced[] = $target;
			}

			// The setup the files were written under. A version kept before
			// there was one to keep has none, and then only the record of what
			// was compiled goes: the choices on screen stay, the panel says
			// "not applied", which is what is true of them
			if( $slot['setup'] !== null )
				$setup = $slot['setup'];
			else {
				$setup = Design\Setup::read( $appData, self::libraryDir() );
				unset( $setup['compiled'] );
			}

			if( Design\Setup::write( $appData, $setup ) === false ) {
				self::_putBack( $appData, $present, $replaced );
				return 'could not write '. Design\Setup::PATH. ' - the previous version was not restored';
			}

			/*	The site has changed by now. A slot that could not be written is
				not a failed restore: it says so, and the answer is still the new
				state, so the screen redraws what the site shows	*/
			if( Design\Previous::write( $appData, $present ) === false )
				$notes[] = 'restored, but could not keep the version it replaced in '. ltrim( Design\Previous::PATH, '/' ). ' - restoring again will not bring it back';

			return true;
		}

		/**
		 *	The setup the files on disk were written under, out of what
		 *	data/design.php holds now.
		 *
		 *	apply() records its choices in 'compiled'['setup'], so the answer is
		 *	that record, whatever draft has been saved over it since. A record
		 *	from before that was kept has none: where its sha still names the
		 *	stylesheet on disk, data/design.php is kept whole with it - the
		 *	draft cannot be told from the choices, and a restore then reads
		 *	"saved, not applied" rather than losing the record. Where the
		 *	stylesheet is no longer the one the record names (the delivered
		 *	file, or one somebody edited) or nothing was ever applied, no
		 *	choices produced what is on disk, and the answer is null: keeping
		 *	the draft as the version before would restore choices the files
		 *	never answered to.
		 *
		 *	@param		array|null	$raw				What Previous::capture() read from data/design.php
		 *	@param		string|null	$css				The stylesheet on disk, null where there is none
		 *
		 *	@return 	array|null								A setup with its 'compiled' record, or null
		 */
		private static function _applied( ?array $raw, ?string $css ): ?array {

			if( $raw === null || $css === null )
				return null;

			$record = is_array( $raw['compiled'] ?? null ) === true ? $raw['compiled'] : [];

			if( hash( 'sha256', $css ) !== (string) ( $record['sha'] ?? '' ) )
				return null;

			if( is_array( $record['setup'] ?? null ) === true )
				return $record['setup'] + [ 'compiled' => $record ];

			return $raw;
		}

		/**
		 *	Every file apply() writes, with the exact bytes - the stylesheet,
		 *	and each frame that has a template - so that apply() compares and
		 *	writes the same thing plan() reports on
		 *
		 *	@param		array 		$setup				A normalized setup
		 *	@param		string		$library			The library directory
		 *	@param		array 		&$notes				(reference) What was missing on the way
		 *
		 *	@return 	array										target => [ bytes, part ('' for the stylesheet), set, markup (the template.tpl) ]
		 */
		private static function _outputs( array $setup, string $library, array &$notes ): array {

			$css = Design\Compiler::compile( $setup, $library, $notes );

			$outputs = [
				Design\Compiler::TARGET => [ 'bytes' => $css, 'part' => '', 'set' => '', 'markup' => '' ],
			];

			/*	The other half of a frame. A header set is a stylesheet AND the
				markup it was drawn against, so compiling one without writing the
				other is how a page ends up with v3's css over v1's html */
			foreach( Design\Setup::PARTS as $part => $kind ) {

				if( $kind !== 'frame' )
					continue;

				$set 			= (string) ( $setup['parts'][$part]['set'] ?? '' );
				$template = Design\Setup::file( $library, $part, $set, 'template' );

				if( $template === '' ) {
					$notes[] = 'no template for "'. $part. '" set "'. $set. '" - the project keeps the one it has';
					continue;
				}

				$markup = (string) file_get_contents( $template );

				$outputs[ sprintf( Design\Compiler::FRAME_TARGET, $part ) ] = [
					'bytes' 	=> Design\Compiler::frame( $part, $markup, $set ),
					'part' 		=> $part,
					'set' 		=> $set,
					'markup' 	=> $markup,
				];
			}

			return $outputs;
		}

		/**
		 *	The shortcodes in a template: the whole bracketed token, with its
		 *	arguments, a [[fill]] inside one of them included. [[fills]] are not
		 *	one themselves - the lookbehind refuses the second bracket of an
		 *	opening pair, and a fill's inner text is not a name followed by a
		 *	space or a closing bracket
		 *
		 *	@param		string		$markup
		 *
		 *	@return 	array										Distinct tokens, in order of appearance
		 */
		private static function _shortcodes( string $markup ): array {

			preg_match_all( '/(?<!\[)\[[a-z][a-z0-9-]*(?:\s(?:[^\[\]]|\[\[[^\[\]]*\]\])*)?\]/', $markup, $found );

			return array_values( array_unique( $found[0] ) );
		}

		/**
		 *	Put files back as they were held in memory before a restore or an
		 *	apply began. A target that was not there is removed again: only the
		 *	call being undone can have written it
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		$present			Previous::capture()'s answer, taken before the first write
		 *	@param		array 		$replaced			The targets the restore or the apply had already written
		 *
		 *	@return 	void
		 */
		private static function _putBack( array &$appData, array $present, array $replaced ): void {

			foreach( $replaced as $target ) {

				if( $present['files'][$target] !== null )
					\Nino\Filesystem::putFileContent( $appData, $target, $present['files'][$target] );
				else
					@unlink( \Nino\Filesystem::path( $appData, $target ) );
			}
		}

		/**
		 *	A new version of the feature may ship changed sets. Recompiling on
		 *	its own would move a site nobody asked to move, so this only
		 *	refreshes what the setup records - the panel is where the difference
		 *	is then visible, and applying is a decision.
		 *
		 *	Reading and writing it back is also what brings the stored file to
		 *	the shape this version keeps: an earlier one wrote a digest per part
		 *	that nothing ever read, normalize() no longer carries it over, and
		 *	the file stops holding it here. What was compiled from what is in
		 *	'compiled'['input'] - the whole setup and the bytes of every library
		 *	file it points at - which is what the panel compares.
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$from					The version recorded before this one
		 *
		 *	@return 	bool
		 */
		public static function upgrade( array &$appData, string $from ): bool {

			$notes = [];
			$setup = Design\Setup::read( $appData, self::libraryDir(), $notes );

			return Design\Setup::write( $appData, $setup );
		}

	}
}
