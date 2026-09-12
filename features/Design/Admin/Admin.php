<?php
declare(strict_types=1);
/**
 *	Nino								A compact filesystembased php framework
 *	Modules\Design\Admin		The /_admin panel of the Design feature - see
 *											features/Design/README.md
 *
 *	@package						Dape/Nino
 *	@author							David Perchermeier <mail@dape.io>
 *	@link								https://github.com/dapeio/nino
 */
namespace Nino\Modules\Design {

	/**
	 *	Nino							A compact filesystembased php framework
	 *	Design\Admin			The "Design" panel: one screen that chooses a variant per
	 *										part of a page, sets the finetune knob and the root size,
	 *										and compiles the result into assets/theme.css.
	 *
	 *										Choosing and compiling are two actions on purpose. A
	 *										setup is a decision and lives in data/design.php whether
	 *										or not it has been applied; the stylesheet on disk is a
	 *										consequence, and the screen says when the two have drifted
	 *										apart rather than hiding it behind an autosave.
	 *
	 *										The first apply in a project meets the theme.css the setup
	 *										wizard delivered, which is not this feature's to overwrite
	 *										(see Compiler::write()). That refusal is a screen state
	 *										here, not an error: the panel says whose file it is and
	 *										offers to take it over, once, deliberately.
	 *
	 *										Beside the selects is what they mean: a preview of the
	 *										current selection, rendered against this project and never
	 *										written anywhere. It is what makes the two-step honest -
	 *										nobody has to compile a design to find out what it is.
	 *
	 *	@package					Dape/Nino
	 *	@author						David Perchermeier <mail@dape.io>
	 *	@link							https://github.com/dapeio/nino
	 */
	class Admin {

		public const string MANAGE_PERM = '/_admin/design/manage';

		/*	The one node in the preview document the screen reaches back into.
			Named here rather than in both halves: the panel's script is told
			which id it is looking for, so the two cannot drift apart */
		public const string PREVIEW_STYLE = 'design-preview-css';

		/*	The framework under the preview, as its own two bundles beside the
			workbench's. Its own rather than the workbench's: /_admin/.cache/script.js
			carries the panels' scripts as well, and a preview is a site, not a tool */
		public const string FRAMEWORK_CSS = '/_admin/.cache/design-preview.css';
		public const string FRAMEWORK_JS 	= '/_admin/.cache/design-preview.js';

		public static function perm(): string {
			return self::MANAGE_PERM;
		}

		public static function actions(): array {
			return [
				'design/list' 	=> [ self::class, 'apiList' ],
				'design/save' 	=> [ self::class, 'apiSave' ],
				'design/apply' 	=> [ self::class, 'apiApply' ],
				'design/preview'=> [ self::class, 'apiPreview' ],
			];
		}

		/*	The group named here is decorative: \Nino\Admin\Admin::_entry() puts
			every panel a feature brought into 'features' regardless, so granting
			that one group stays a bounded grant */
		public static function nav(): array {
			return [ 'design', '/_admin/nav/design', 10, 'structure' ];
		}

		public static function icon(): string {
			return '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-palette-icon lucide-palette"><path d="M12 22a1 1 0 0 1 0-20 10 9 0 0 1 10 9 5 5 0 0 1-5 5h-2.25a1.75 1.75 0 0 0-1.4 2.8l.3.4a1.75 1.75 0 0 1-1.4 2.8z"/><circle cx="13.5" cy="6.5" r=".5" fill="currentColor"/><circle cx="17.5" cy="10.5" r=".5" fill="currentColor"/><circle cx="6.5" cy="12.5" r=".5" fill="currentColor"/><circle cx="8.5" cy="7.5" r=".5" fill="currentColor"/></svg>';
		}

		public static function panes(): array {
			return [ 'design-form' ];
		}

		public static function assets(): array {
			return [
				\Nino\Admin\Panels::relative( dirname( __DIR__ ). '/assets/admin.js' ),
				\Nino\Admin\Panels::relative( dirname( __DIR__ ). '/assets/admin.css' ),
			];
		}

		public static function text(): string {
			return \Nino\Admin\Panels::relative( dirname( __DIR__ ). '/text' );
		}

		public static function log( string $action, array $data ): string {
			return match( $action ) {
				'design/save' 	=> 'Save Design Setup',
				'design/apply' 	=> 'Compile Design'. ( ( $data['force'] ?? false ) === true ? ' (took over assets/theme.css)' : '' ),
				// design/preview writes nothing and happens on every change of
				// a select - a log line per keystroke is noise, not a record
				default	=> '',
			};
		}

		/**
		 *	Everything the screen draws: what can be chosen, what is chosen, and
		 *	what the stylesheet on disk currently is
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$request			(reference) Current server request
		 *
		 *	@return 	void
		 */
		public static function apiList( array &$appData, array &$request ): void {

			if( \Nino\Admin\Admin::guardPerm( $appData, $request, self::MANAGE_PERM ) === false )
				return;

			$library 	= \Nino\Modules\Design::libraryDir();
			$notes 		= [];
			$setup 		= Setup::read( $appData, $library, $notes );
			$parts 		= [];

			foreach( Setup::PARTS as $part => $kind )
				$parts[] = [
					'part' 				=> $part,
					'kind' 				=> $kind,
					'catalogue' 	=> Setup::catalogue( $library, $part ),
					'set' 				=> (string) ( $setup['parts'][$part]['set'] ?? '' ),
					// null is its own state - the part follows the global knob
					'step' 				=> $setup['parts'][$part]['step'] ?? null,
				];

			\Nino\Http::ok( $request, [
				'parts' 	=> $parts,
				'step' 		=> (string) ( $setup['step'] ?? 'default' ),
				'size' 		=> (string) ( $setup['size'] ?? 'm' ),
				'steps' 	=> Setup::STEPS,
				'sizes' 	=> array_keys( Setup::SIZES ),
				'notes' 	=> $notes,
				'target' 	=> Compiler::TARGET,
			] + self::state( $appData, $setup, $library ) );
		}

		/**
		 *	What assets/theme.css currently is, which is the one thing a screen
		 *	about compiling has to be honest about
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		$setup				A normalised setup
		 *	@param		string		$library			The feature's library
		 *
		 *	@return 	array										exists, ours, compiled, current
		 */
		public static function state( array &$appData, array $setup, string $library ): array {

			$path 	= \Nino\Filesystem::path( $appData, Compiler::TARGET );
			$exists = is_file( $path );
			$ours 	= $exists === true && Compiler::stamped( (string) @file_get_contents( $path ) );

			// What this setup would produce right now, against what the setup
			// says was produced last time. Equal means the file answers to the
			// screen; different means somebody changed something since
			$notes 	= [];
			$fresh 	= hash( 'sha256', Compiler::compile( $setup, $library, $notes ) );

			return [
				'exists' 		=> $exists,
				'ours' 			=> $ours,
				'compiled' 	=> (string) ( $setup['compiled']['at'] ?? '' ),
				'current' 	=> $ours === true && (string) ( $setup['compiled']['sha'] ?? '' ) === $fresh,
			];
		}

		/**
		 *	Store the setup without compiling. A decision is not a stylesheet
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$request			(reference) Current server request
		 *
		 *	@return 	void
		 */
		public static function apiSave( array &$appData, array &$request ): void {

			if( \Nino\Admin\Admin::guardPerm( $appData, $request, self::MANAGE_PERM ) === false )
				return;

			$library 	= \Nino\Modules\Design::libraryDir();
			$posted 	= \Nino\Admin\Admin::postData();
			$notes 		= [];

			/*	Normalised against the library rather than trusted: the screen
				offers what is there, but a request is a request. What cannot be
				honoured comes back in `notes` instead of being written */
			$setup = Setup::normalize( [
				'parts' 	=> is_array( $posted['parts'] ?? null ) === true ? $posted['parts'] : [],
				'step' 		=> (string) ( $posted['step'] ?? '' ),
				'size' 		=> (string) ( $posted['size'] ?? '' ),
				// Kept, so saving a choice does not lose what was last compiled
				'compiled'=> Setup::read( $appData, $library )['compiled'] ?? [],
			], $library, $notes );

			if( Setup::write( $appData, $setup ) === false ) {
				\Nino\Http::fail( $request, 500, 'could not write '. Setup::PATH );
				return;
			}

			\Nino\Http::ok( $request, [ 'notes' => $notes ] + self::state( $appData, $setup, $library ) );
		}

		/**
		 *	Compile the stored setup into assets/theme.css.
		 *
		 *	Refusing a file this feature did not write is not an error to log and
		 *	move past - it is the normal first run in a project, where the file
		 *	on disk is the wizard's. The screen turns the refusal into a question
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$request			(reference) Current server request
		 *
		 *	@return 	void
		 */
		public static function apiApply( array &$appData, array &$request ): void {

			if( \Nino\Admin\Admin::guardPerm( $appData, $request, self::MANAGE_PERM ) === false )
				return;

			$force 	= ( \Nino\Admin\Admin::postData()['force'] ?? false ) === true;
			$notes 	= [];
			$done 	= \Nino\Modules\Design::apply( $appData, $notes, $force );

			if( $done !== true ) {
				// 409: the request was fine, the file on disk disagrees
				\Nino\Http::fail( $request, 409, $done );
				return;
			}

			$library 	= \Nino\Modules\Design::libraryDir();
			$setup 		= Setup::read( $appData, $library );

			\Nino\Http::ok( $request, [ 'notes' => $notes, 'forced' => $force ] + self::state( $appData, $setup, $library ) );
		}

		/**
		 *	What the selection on screen would look like - before it is saved,
		 *	and long before it is compiled.
		 *
		 *	The posted setup is the one the selects currently show, not the one
		 *	on disk: looking is what a person does *while* deciding, and a
		 *	preview that could only show a saved decision would make saving the
		 *	way to ask a question. Nothing here writes anything.
		 *
		 *	Rendered against this project, not a built one - its menu, its logo,
		 *	its socialmedia block, its fonts. The design-library harness builds a
		 *	throwaway project instead, because a library checkout has none; both
		 *	assemble the same specimen through the same Preview
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$request			(reference) Current server request
		 *
		 *	@return 	void
		 */
		public static function apiPreview( array &$appData, array &$request ): void {

			if( \Nino\Admin\Admin::guardPerm( $appData, $request, self::MANAGE_PERM ) === false )
				return;

			$library 	= \Nino\Modules\Design::libraryDir();
			$posted 	= \Nino\Admin\Admin::postData();
			$notes 		= [];

			// Same normalisation the save takes, for the same reason: a request
			// is a request, and a set that is not in the library is not shown
			// just because somebody asked for it
			$setup = Setup::normalize( [
				'parts' 	=> is_array( $posted['parts'] ?? null ) === true ? $posted['parts'] : [],
				'step' 		=> (string) ( $posted['step'] ?? '' ),
				'size' 		=> (string) ( $posted['size'] ?? '' ),
			], $library, $notes );

			$css 			= Preview::css( $setup, $library, \Nino\Filesystem::getPublicDir( $appData ), $notes );
			$document = '';

			/*	Seven of the nine parts are a stylesheet and nothing else, and so
				are the knob and the size: for those the screen swaps one <style>
				inside the frame it already has, and neither the markup nor the
				framework below it has to travel again. The two that do bring
				markup - header and footer - rebuild the document, and so does
				the first preview of a visit */
			if( ( $posted['full'] ?? false ) === true )
				$document = self::document( $appData, $library, $setup, $css, $notes );

			\Nino\Http::ok( $request, [
				'css' 			=> $css,
				'document' 	=> $document,
				'style' 		=> self::PREVIEW_STYLE,
				'notes' 		=> $notes,
			] );
		}

		/**
		 *	The whole page the frame shows: the framework, the compiled sheet,
		 *	the specimen with its frames, and the behaviour a header set is half
		 *	made of. One document with everything in it - an iframe's srcdoc
		 *	links nothing, and that is the point: no url of this is reachable,
		 *	nothing of it is written, and it is gone when the screen is
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$library			The feature's library
		 *	@param		array 		$setup				A normalised setup
		 *	@param		string		$css					The compiled sheet, already prefixed
		 *	@param		array 		&$notes				(reference) What was missing on the way
		 *
		 *	@return 	string								A complete html document
		 */
		public static function document( array &$appData, string $library, array $setup, string $css, array &$notes ): string {

			return Preview::document(
				\Nino\Locales::getCurrentLocale( $appData ),
				self::_bundle( $appData, self::FRAMEWORK_CSS, [ '/_nino/Nino.css' ] ). "\n"
					. '<style id="'. self::PREVIEW_STYLE. '">'. $css. '</style>',
				'<main>'. self::_specimen( $appData, $library, $setup, $notes ). '</main>',
				self::_bundle( $appData, self::FRAMEWORK_JS, [ '/_nino/Nino.js', '/_nino/Nino.ui.js' ] )
			);
		}

		/**
		 *	The framework's own two halves, as a tag pointing at a bundle.
		 *
		 *	Not inlined, for two reasons. The workbench sends a
		 *	Content-Security-Policy with `script-src 'self'` and a nonce, and an
		 *	iframe built from srcdoc inherits it - an inline <script> in there is
		 *	refused, a same-origin src is not. And Nino.css and the two scripts
		 *	together are the larger half of this answer while being the half
		 *	that never changes: as a bundle they are written once, hashed, and
		 *	fetched from the browser's cache on every preview after the first.
		 *
		 *	Built by the same Modules\Assets the workbench builds its own two
		 *	bundles with, into the same directory
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$target				The bundle to write
		 *	@param		array 		$sources			What goes into it, in order
		 *
		 *	@return 	string								A <link> or a <script>
		 */
		private static function _bundle( array &$appData, string $target, array $sources ): string {

			foreach( $sources as $source )
				\Nino\Html::addAsset( $appData, $target, $source );

			return \Nino\Html::renderHtml( $appData, '[assets '. $target. ']' );
		}

		/**
		 *	The specimen, rendered through the kernel against this project
		 *
		 *	@param		array 		$appData			Array with current app data - a copy, see below
		 *	@param		string		$library			The feature's library
		 *	@param		array 		$setup				A normalised setup
		 *	@param		array 		&$notes				(reference) What was missing on the way
		 *
		 *	@return 	string								Rendered html
		 */
		private static function _specimen( array $appData, string $library, array $setup, array &$notes ): string {

			/*	By value, and renderHtml() then takes the copy by reference: a
				render registers fills as it goes, and the ones below are this
				preview's rather than the request's. The request answers json and
				writes nothing, so nothing would come of it either way - but a
				function that quietly changes the caller's app data is a function
				somebody will call twice */
			\Nino\Html::addFills( $appData, self::_previewFills( $appData ), '*' );

			return \Nino\Html::renderHtml( $appData, Preview::markup( $library, $setup, $notes ) );
		}

		/**
		 *	The one thing an /_admin request cannot give the frames: a page.
		 *
		 *	They resolve their title through a nested fill,
		 *	[[/webpage[[/nino/http/response/uri]]/title]] - and the inner one is
		 *	'/_admin' here, which is no webpage. The project's own front page
		 *	stands in where there is one; it is the page a header gets judged on
		 *	anyway
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	array										Fills for the render
		 */
		private static function _previewFills( array &$appData ): array {

			$home = (string) ( $appData['/nino/http/routes']['GET://']['uri'] ?? '' );

			if( $home !== '' )
				return [ '/nino/http/response/uri' => $home ];

			$stand = \Nino\Html::renderTextfill( $appData, '/_admin/design/preview/page' );

			return [
				'/nino/http/response/uri' 			=> '/design-preview',
				'/webpage/design-preview/title' => $stand,
				'/webpage/design-preview/name' 	=> $stand,
			];
		}
	}

}
