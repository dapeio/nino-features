<?php
declare(strict_types=1);
/**
 *	Nino								A compact filesystembased php framework
 *	Modules\Design\Admin		The /_admin panel of the Design feature
 *
 *	@package						Dape/Nino
 *	@author							David Perchermeier <mail@dape.io>
 *	@link								https://github.com/dapeio/nino
 */
namespace Nino\Modules\Design {

	/**
	 *	Nino							A compact filesystembased php framework
	 *	Design\Admin			The "Design" panel: seven tabs, six of them over one setup -
	 *										Global, with the two frames, the root size, the width and the
	 *										animation of the page; Fonts, with the five font roles;
	 *										Content, Buttons and Components, each with the choice of its
	 *										template and one named knob for each thing the template lets
	 *										go of; and Colours, with the two colours, the five knobs and
	 *										the mode the palette is solved from - compiled together into
	 *										assets/theme.css. The seventh, Typefaces, is the loader: the
	 *										typefaces a project has installed and the signed list of
	 *										those it can install (see Webfonts), which belongs to the
	 *										setup only as far as the selects of Fonts list what is
	 *										installed. Installing and removing happen when they are
	 *										pressed - they are not a draft.
	 *
	 *										Choosing and applying are two actions on purpose. A
	 *										setup is a draft and lives in data/design.php whether
	 *										or not it has been applied; the files on disk are a
	 *										consequence, and the screen says when the two have drifted
	 *										apart rather than hiding it behind an autosave.
	 *
	 *										Applying asks first. The first apply in a project meets
	 *										the theme.css and the two frame templates the setup wizard
	 *										delivered, which are not this feature's to overwrite (see
	 *										Compiler::refusal()), and a later one may meet a header
	 *										somebody added a shortcode to. design/plan says what
	 *										would happen to every file - whose it is, and what a frame
	 *										would lose - the screen puts that in a confirmation, and
	 *										only a yes sends the apply with `force`. What was there is
	 *										kept as the previous version (see Previous) and
	 *										design/restore swaps it back.
	 *
	 *										Beside the controls is what they mean: one specimen page
	 *										of the selection, rendered against this project and never
	 *										written anywhere, with an anchor for each tab that the
	 *										frame scrolls to. It is what makes the two-step honest -
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

		/*	The two tags this panel puts around what Preview hands it, as one-line
			fragments with their fills - the shape \Nino\Modules\Navigation::$html
			and Modules\Assets use, and the one AGENTS.md allows markup in php at
			all. They are the page's, not the design's: the specimen and the
			document around it are templates (see Preview::TEMPLATES), and a <main>
			wrapper is not a thing anybody edits to change how a preview looks */
		private static array $_html = [
			'style'	=> '<style id="[[id]]">[[css]]</style>',
			'main'		=> '<main>[[body]]</main>',
		];

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
				'design/plan' 	=> [ self::class, 'apiPlan' ],
				'design/restore'=> [ self::class, 'apiRestore' ],
				'design/preview'=> [ self::class, 'apiPreview' ],
				'design/webfonts/list' 		=> [ self::class, 'apiWebfontsList' ],
				'design/webfonts/install' => [ self::class, 'apiWebfontsInstall' ],
				'design/webfonts/remove' 	=> [ self::class, 'apiWebfontsRemove' ],
			];
		}

		/*	No group is named here: \Nino\Admin\Panels puts every panel a feature
			brought into 'features' whatever it would name, so granting that one
			group stays a bounded grant */
		public static function nav(): array {
			return [ 'design', '/_admin/nav/design', 10 ];
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
				'design/apply' 	=> 'Apply Design'. ( ( $data['force'] ?? false ) === true ? ' (took over files Design had not written)' : '' ),
				'design/restore'=> 'Restore previous Design version',
				'design/webfonts/install' => 'Install webfont '. self::_slug( $data ),
				'design/webfonts/remove' 	=> 'Remove webfont '. self::_slug( $data ),
				// design/preview writes nothing and happens on every change of
				// a select - a log line per keystroke is noise, not a record.
				// design/plan is the same: it only reads. The list of typefaces
				// is what a repository answered, kept as a cache: no change to
				// the project, and a log line for it would be noise as well
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

			foreach( Setup::PARTS as $part => $kind ) {

				$catalogue = Setup::catalogue( $library, $part );

				/*	Which knobs each template answers to - out of the stylesheet rather
					than out of a list here, so declaring a ladder is what publishes a
					knob. For every template and not only the chosen one: choosing
					another changes which rows of its tab are greyed out, and the
					screen does that before anything is asked of the server. A frame has
					none	*/
				if( $kind === 'set' )
					foreach( array_keys( $catalogue ) as $set )
						$catalogue[$set]['knobs'] = Setup::knobs( $library, $part, $set );

				$parts[] = [
					'part' 				=> $part,
					'kind' 				=> $kind,
					'catalogue' 	=> $catalogue,
					'set' 				=> (string) ( $setup['parts'][$part] ?? '' ),
				];
			}

			$families = [];

			foreach( Fonts::families( $appData ) as $slug => $family )
				$families[$slug] = [ 'name' => $family['name'], 'category' => $family['category'], 'system' => $family['files'] === [] ];

			$options = [];

			foreach( Fonts::ROLES as $role )
				$options[$role] = Fonts::options( $appData, $role );

			\Nino\Http::ok( $request, [
				'parts' 	=> $parts,
				// Where every knob stands, per tab, and which knobs a tab has and of what
				// kind - a scale of five positions or a choice of three, by name: the
				// screen draws a row for each of them, and the parts above say which
				// of the rows its template answers to. The five words are the steps a
				// choice is named by, and the size and the width are positions as well
				'knobs' 	=> $setup['knobs'],
				'tabs' 		=> Setup::KNOBS,
				'steps' 	=> Setup::STEPS,
				'size' 		=> (int) ( $setup['size'] ?? 0 ),
				'width' 	=> (int) ( $setup['width'] ?? 0 ),
				// The animation of the page, and what it can be: the effects and
				// strengths and speeds of Nino.css's viewport animations
				'motion' 	=> (array) ( $setup['motion'] ?? [] ),
				'effects' => array_keys( Setup::EFFECTS ),
				'strengths' => Setup::STRENGTHS,
				'speeds' 	=> array_keys( Setup::SPEEDS ),
				/*	The five font roles: what each has, the families to choose from
					(the ones the project installed among them, and which of them each
					role may be given), the three weights and the bounds a typed size is
					held inside of - with the size Nino.css gives each role, which the
					field of a role that chose none shows as its value. The frontend
					carries none of these tables */
				'roles' 		=> Fonts::ROLES,
				'fonts' 		=> (array) ( $setup['fonts'] ?? [] ),
				'families' 	=> $families,
				'options' 	=> $options,
				'weights' 	=> Fonts::WEIGHTS,
				'bounds' 		=> Fonts::BOUNDS,
				'inherited' => Fonts::INHERITED,
				/*	The palette half: where it stands, the vocabulary to draw it
					with, and whether the picked brand colour is one text survives
					on. The frontend carries no copy of the knob tables - it draws
					whatever choices() hands it, so a knob added in Colours
					appears here without this file or the script gaining a line.
					Its words are the one thing it does need: text keys under
					'/_admin/design/colour/<knob>/', in every locale */
				'colours'	=> (array) ( $setup['colours'] ?? [] ),
				'palette'	=> Colours::choices(),
				'brand' 	=> Colours::brand( (array) ( $setup['colours'] ?? [] ) ),
				// The second colour as it will be compiled - the one somebody
				// named, or the one Harmony derived. The swatch standing for it
				// shows this rather than the primary standing in for it
				'accent'	=> Colours::accent( (array) ( $setup['colours'] ?? [] ) ),
				// Whether the repository of typefaces has an address and a key to be read with:
				// the tab offers the list only where both are set
				'webfonts'	=> [ 'url' => Webfonts::url( $appData ) !== '', 'key' => Webfonts::key( $appData ) !== '' ],
				'notes' 	=> $notes,
				'target' 	=> Compiler::TARGET,
			] + self::state( $appData, $setup, $library ) );
		}

		/**
		 *	What the three files Design writes currently are, and the version it
		 *	keeps before them - the thing a screen about applying has to be
		 *	honest about
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		$setup				A normalised setup
		 *	@param		string		$library			The feature's library
		 *
		 *	@return 	array										exists, ours, compiled, current, files, previous
		 */
		public static function state( array &$appData, array $setup, string $library ): array {

			$path 	= \Nino\Filesystem::path( $appData, Compiler::TARGET );
			$exists = is_file( $path );
			$ours 	= $exists === true && Compiler::stamped( (string) @file_get_contents( $path ) );

			/*	What this setup would compile from right now, against what the
				last apply recorded it had compiled from. Equal means the file
				answers to the screen; different means somebody changed
				something since.

				A fingerprint rather than a compile: this used to build the
				whole stylesheet again and hash it, which is 43 ms of colour
				solving to answer one boolean - on every list, on every save,
				and once more at the end of every apply, so an apply compiled
				twice.

				A setup written before that fingerprint existed carries the
				compiled sha alone, and there the old answer is still the only
				one there is - once, until the next apply records the new
				one	*/
			$recorded = (string) ( $setup['compiled']['input'] ?? '' );

			if( $recorded !== '' )
				$current = $recorded === \Nino\Modules\Design::fingerprint( $appData, $setup, $library );
			else {
				$notes		= [];
				$current	= (string) ( $setup['compiled']['sha'] ?? '' ) === hash( 'sha256', Compiler::compile( $appData, $setup, $library, $notes ) );
			}

			/*	Whose every file Design writes is - the stylesheet above says it
				for itself, and the two frames are the same question. 'files' is
				what the screen reads to say "foreign", since a stylesheet that
				is ours beside a header somebody edited is not an up to date
				design either	*/
			$files = [];

			foreach( Previous::targets() as $target ) {

				$file 		= \Nino\Filesystem::path( $appData, $target );
				$present 	= $file !== '' && is_file( $file ) === true;

				$files[] = [
					'target' 	=> $target,
					'exists' 	=> $present,
					'state' 	=> $present === false ? 'missing'
						: Compiler::ownership( (string) @file_get_contents( $file ), $target !== Compiler::TARGET ),
				];
			}

			$previous = Previous::read( $appData );

			return [
				'exists' 		=> $exists,
				'ours' 			=> $ours,
				'compiled' 	=> (string) ( $setup['compiled']['at'] ?? '' ),
				'current' 	=> $ours === true && $current === true,
				'files' 		=> $files,
				// What restore would put back, and when it was kept - not the
				// bytes, which are nobody's business on a screen
				'previous' 	=> $previous === null ? null : [
					'at' 		=> $previous['at'],
					'files' => array_keys( array_filter( $previous['files'], static fn( ?string $bytes ): bool => $bytes !== null ) ),
				],
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
			$setup = self::_selection( $appData, $posted, $library, $notes );

			// Kept, so saving a choice does not lose what was last compiled
			$setup['compiled'] = Setup::read( $appData, $library )['compiled'] ?? [];

			if( Setup::write( $appData, $setup ) === false ) {
				\Nino\Http::fail( $request, 500, 'could not write '. Setup::PATH );
				return;
			}

			\Nino\Http::ok( $request, [ 'notes' => $notes ] + self::state( $appData, $setup, $library ) );
		}

		/**
		 *	Apply the stored setup: write assets/theme.css and the frames.
		 *
		 *	Refusing a file this feature did not write is not an error to log and
		 *	move past - it is the normal first run in a project, where the file
		 *	on disk is the wizard's. The screen asks (apiPlan) before it applies
		 *	and sends 'force' where the answer was yes
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
				// 409: the request was fine, the file on disk disagrees - and
				// 500 where the server could not write what it was asked to,
				// which is every message of Design::apply() that says 'could
				// not' (the slot or a file that could not be written)
				\Nino\Http::fail( $request, str_contains( $done, 'could not' ) === true ? 500 : 409, $done );
				return;
			}

			$library 	= \Nino\Modules\Design::libraryDir();
			$setup 		= Setup::read( $appData, $library );

			\Nino\Http::ok( $request, [ 'notes' => $notes ] + self::state( $appData, $setup, $library ) );
		}

		/**
		 *	What applying would do to each file, before it does anything: the
		 *	screen turns the answer into the question it asks
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$request			(reference) Current server request
		 *
		 *	@return 	void
		 */
		public static function apiPlan( array &$appData, array &$request ): void {

			if( \Nino\Admin\Admin::guardPerm( $appData, $request, self::MANAGE_PERM ) === false )
				return;

			\Nino\Http::ok( $request, [ 'files' => \Nino\Modules\Design::plan( $appData ) ] );
		}

		/**
		 *	Swap the previous version and the present one. The screen has asked
		 *	already; the answer is the new state, including what the slot now
		 *	holds - which is the version that was just replaced
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$request			(reference) Current server request
		 *
		 *	@return 	void
		 */
		public static function apiRestore( array &$appData, array &$request ): void {

			if( \Nino\Admin\Admin::guardPerm( $appData, $request, self::MANAGE_PERM ) === false )
				return;

			if( Previous::read( $appData ) === null ) {
				\Nino\Http::fail( $request, 404, 'there is no previous version' );
				return;
			}

			$notes 	= [];
			$done 	= \Nino\Modules\Design::restore( $appData, $notes );

			if( $done !== true ) {
				\Nino\Http::fail( $request, 500, $done );
				return;
			}

			$library 	= \Nino\Modules\Design::libraryDir();
			$setup 		= Setup::read( $appData, $library );

			\Nino\Http::ok( $request, [ 'notes' => $notes ] + self::state( $appData, $setup, $library ) );
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
		 *	Rendered against this project - its menu, its logo, its social
		 *	links, its fonts - and assembled through Preview, which is where the
		 *	specimen and the frames around it live
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
			$setup = self::_selection( $appData, $posted, $library, $notes );

			$css 			= Preview::css( $appData, $setup, $library, \Nino\Filesystem::getPublicDir( $appData ), $notes );
			$document = '';

			/*	Three of the five parts are a stylesheet and nothing else, and so
				are the knobs, the size, the width, the animation, the fonts and
				the palette: for those the screen swaps one <style> inside the
				frame it already has, and neither the markup nor the framework
				below it has to travel again. The two that do bring markup -
				header and footer - rebuild the document, and so does the first
				preview of a visit */
			if( ( $posted['full'] ?? false ) === true )
				$document = self::document( $appData, $library, $setup, $css, $notes );

			/*	Both of these move with every colour knob, and both are drawn
				beside the controls that moved them: the derived second colour in
				its swatch, the brand's measured ratio in the line under it. They
				ride the preview rather than a request of their own - the screen
				is asking for a new stylesheet at that moment anyway	*/
			$colours = (array) ( $setup['colours'] ?? [] );

			\Nino\Http::ok( $request, [
				'css' 			=> $css,
				'document' 	=> $document,
				'style' 		=> self::PREVIEW_STYLE,
				'accent' 		=> Colours::accent( $colours ),
				'brand' 		=> Colours::brand( $colours ),
				'notes' 		=> $notes,
			] );
		}

		/**
		 *	The typefaces: what the repository offers, as far as it has been loaded,
		 *	and what the project has installed - both halves of the tab from one
		 *	answer. It reads the list that was kept (see Webfonts::cached()) and
		 *	asks the repository only where the screen says `refresh`, which is the
		 *	button of the tab and nothing else: no request leaves the server on
		 *	its own.
		 *
		 *	The list that could not be loaded is a refusal in the answer, with its
		 *	reason and what it is about (see Webfonts::REFUSALS), and not an error of
		 *	the request: the tab says it where the list would be, in its own words, and
		 *	the installed half stays.
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$request			(reference) Current server request
		 *
		 *	@return 	void
		 */
		public static function apiWebfontsList( array &$appData, array &$request ): void {

			if( \Nino\Admin\Admin::guardPerm( $appData, $request, self::MANAGE_PERM ) === false )
				return;

			$refusal 	= '';
			$kind 		= '';
			$list 		= null;

			if( ( \Nino\Admin\Admin::postData()['refresh'] ?? false ) === true ) {

				$fetched = Webfonts::fetch( $appData, $kind );

				// The time that is kept with the list is the time the screen shows: the next read says the same
				if( is_string( $fetched ) === true )
					$refusal = $fetched;
				else
					$list = Webfonts::cached( $appData ) ?? [ 'fetched' => time(), 'families' => $fetched ];
			}
			else
				$list = Webfonts::cached( $appData );

			\Nino\Http::ok( $request, self::_webfonts( $appData, $list, $refusal, $kind ) );
		}

		/**
		 *	Install a family of the list, or update it: the whole family, from the
		 *	archive the signed list names. The answer is the one the list gives, after
		 *	the change, with the sentence for the action bar. The screen asks design/list
		 *	again, for the selects of the Fonts tab
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$request			(reference) Current server request
		 *
		 *	@return 	void
		 */
		public static function apiWebfontsInstall( array &$appData, array &$request ): void {

			if( \Nino\Admin\Admin::guardPerm( $appData, $request, self::MANAGE_PERM ) === false )
				return;

			$slug = self::_slug( \Nino\Admin\Admin::postData() );
			$was 	= Fonts::installed( $appData )[$slug]['installed']['version'] ?? null;
			$done = Webfonts::install( $appData, $slug );

			if( $done !== true ) {
				\Nino\Http::fail( $request, str_starts_with( $done, 'could not' ) === true ? 500 : 409, $done );
				return;
			}

			$family = Fonts::installed( $appData )[$slug] ?? [ 'name' => $slug, 'installed' => [ 'version' => '' ] ];

			\Nino\Http::ok( $request, [
				'msg' => self::_text( $appData, $was === null ? 'typefaces/msg/installed' : 'typefaces/msg/updated', $family['name'], $family['installed']['version'] ),
			] + self::_webfonts( $appData, Webfonts::cached( $appData ), '' ) );
		}

		/**
		 *	Remove an installed family - not while a role of the saved setup uses it
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$request			(reference) Current server request
		 *
		 *	@return 	void
		 */
		public static function apiWebfontsRemove( array &$appData, array &$request ): void {

			if( \Nino\Admin\Admin::guardPerm( $appData, $request, self::MANAGE_PERM ) === false )
				return;

			$slug = self::_slug( \Nino\Admin\Admin::postData() );
			$name = Fonts::installed( $appData )[$slug]['name'] ?? $slug;
			$done = Webfonts::remove( $appData, $slug );

			if( $done !== true ) {
				// A family a role still uses is refused with the roles as a code and its params, so the tab can say
				// it in the words of the workbench; any other refusal is told in the server's own words
				$roles = str_starts_with( $done, 'used by ' ) === true ? Webfonts::uses( $appData, $slug ) : [];
				\Nino\Http::fail( $request, str_starts_with( $done, 'could not' ) === true ? 500 : 409, $done, $roles !== [] ? 'used' : '', $roles );
				return;
			}

			\Nino\Http::ok( $request, [
				'msg' => self::_text( $appData, 'typefaces/msg/removed', $name ),
			] + self::_webfonts( $appData, Webfonts::cached( $appData ), '' ) );
		}

		/**
		 *	The whole page the frame shows: the framework, the compiled sheet,
		 *	the specimen with its frames, and the behaviour a header set is half
		 *	made of. The compiled sheet is inline, under PREVIEW_STYLE's id, so
		 *	the panel can swap it in a frame that is already standing and no
		 *	url of this design is written or reachable; the framework, the
		 *	same for every design, is linked from its two bundles (see
		 *	_bundle() for why it is not inlined)
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

			$style = str_replace( [ '[[id]]', '[[css]]' ], [ self::PREVIEW_STYLE, $css ], self::$_html['style'] );
			$main	= str_replace( '[[body]]', self::_specimen( $appData, $library, $setup, $notes ), self::$_html['main'] );

			return Preview::document(
				$appData,
				\Nino\Locales::getCurrentLocale( $appData ),
				self::_bundle( $appData, self::FRAMEWORK_CSS, [ '/_nino/Nino.css' ] ). "\n". $style,
				$main,
				self::_bundle( $appData, self::FRAMEWORK_JS, [ '/_nino/Nino.js', '/_nino/Nino.ui.js' ] )
			);
		}

		/**
		 *	The selection a request carries, normalised against the library.
		 *
		 *	Only the keys of the model are taken and each is held to the type it
		 *	has - the posted json is a request, and a cast of an array raises a
		 *	warning the runtime treats as fatal. A knob's position belongs to the
		 *	template that answers to it: one the chosen template does not declare
		 *	is back in the middle, so a setup does not carry the positions of a
		 *	template somebody moved away from long ago - normalize() cannot do
		 *	this, since it holds no library file open.
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		$posted				What the request carried
		 *	@param		string		$library			The feature's library
		 *	@param		array 		&$notes				(reference) What had to be replaced or dropped
		 *
		 *	@return 	array										A normalised setup, without a record of what was compiled
		 */
		private static function _selection( array &$appData, array $posted, string $library, array &$notes ): array {

			$taken = [
				'size' 		=> $posted['size'] ?? 0,
				'width' 	=> $posted['width'] ?? 0,
			];

			foreach( [ 'motion', 'parts', 'knobs', 'fonts', 'colours' ] as $key )
				$taken[$key] = is_array( $posted[$key] ?? null ) === true ? $posted[$key] : [];

			$setup = Setup::normalize( $appData, $taken, $library, $notes );

			foreach( Setup::KNOBS as $tab => $kinds ) {

				$answered = Setup::knobs( $library, $tab, (string) ( $setup['parts'][$tab] ?? '' ) );

				foreach( array_keys( $kinds ) as $knob )
					if( in_array( $knob, $answered, true ) === false )
						$setup['knobs'][$tab][$knob] = 0;
			}

			return $setup;
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

			return \Nino\Html::renderHtml( $appData, Preview::markup( $appData, $library, $setup, $notes ) );
		}

		/**
		 *	What the tab of the typefaces is drawn from, the same after every action
		 *	of it: the list as far as there is one, and the families the project has
		 *	installed - with what each is set in its own face from, who uses it, and
		 *	the directories that are none. The slugs of the families the feature brings
		 *	are in it as well, so that a row of the list can say that it is one: what
		 *	design/list says of the families has the installed ones among them, and says
		 *	it as long as the screen has not asked again.
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array|null	$list					{ fetched, families } - the cached list or the one just fetched; null for none
		 *	@param		string		$refusal			Why the list could not be loaded, '' where it could
		 *	@param		string		$kind					What the refusal is about, one of Webfonts::REFUSALS; '' where there is none
		 *
		 *	@return 	array										families (the list's entries), fetched (unix time, 0 for none), url ('' for none), key (whether there is one), builtin, installed, broken, uses, refusal, refusalKind
		 */
		private static function _webfonts( array &$appData, ?array $list, string $refusal, string $kind = '' ): array {

			$public 	= \Nino\Filesystem::getPublicDir( $appData );
			$usage 		= Webfonts::usage( $appData );
			$installed = [];
			$uses 		= [];

			foreach( Fonts::installed( $appData ) as $slug => $family ) {

				// The name is set in the first file of the normal style, the one the preview of the
				// list draws it from: the files are listed with the normal style first
				$face = $family['files'][0];

				foreach( $family['files'] as $file )
					if( $file['style'] === 'normal' ) {
						$face = $file;
						break;
					}

				$installed[$slug] = [
					'name' 			=> $family['name'],
					'category' 	=> $family['category'],
					'version' 	=> $family['installed']['version'],
					'license' 	=> $family['installed']['license'],
					'source' 		=> $family['installed']['source'],
					'weights' 	=> $family['weights'],
					'axis' 			=> $family['axis'],
					'face' 			=> str_replace( '[[/nino/public]]', $public, $face['url'] ),
				];
				$uses[$slug] = $usage[$slug] ?? [];
			}

			return [
				'families' 		=> $list['families'] ?? [],
				'fetched' 		=> $list['fetched'] ?? 0,
				'url' 			=> Webfonts::url( $appData ),
				'key' 			=> Webfonts::key( $appData ) !== '',
				'builtin' 		=> array_keys( Fonts::builtin() ),
				'installed' 	=> $installed,
				'broken' 		=> Fonts::broken( $appData ),
				'uses' 			=> $uses,
				'refusal' 		=> $refusal,
				'refusalKind' 	=> $kind,
			];
		}

		/**
		 *	The slug a request names: one that can be the name of a family's directory,
		 *	else nothing - so that what is posted never reaches a path, a message or a line of the log as it is
		 *
		 *	@param		array 		$data					What the request posted
		 *
		 *	@return 	string
		 */
		private static function _slug( array $data ): string {

			$slug = $data['slug'] ?? '';

			return is_string( $slug ) === true && preg_match( Webfonts::SLUG, $slug ) === 1 ? $slug : '';
		}

		/**
		 *	A sentence of the panel's texts, as the screen's own _text() fills it: the
		 *	%s of it, in turn, with the values
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$key					Under /_admin/design/, eg. 'typefaces/msg/removed'
		 *	@param		string		...$values
		 *
		 *	@return 	string
		 */
		private static function _text( array &$appData, string $key, string ...$values ): string {

			$text = \Nino\Html::renderTextfill( $appData, '/_admin/design/'. $key );

			foreach( $values as $value )
				$text = (string) preg_replace_callback( '/%s/', static fn(): string => $value, $text, 1 );

			return $text;
		}

		/**
		 *	The one thing an /_admin request cannot give the frames: a page.
		 *
		 *	They resolve their title through a nested fill,
		 *	[[/_nino/webpage[[/nino/http/response/uri]]/title]] - and the inner one is
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
				'/_nino/webpage/design-preview/title' => $stand,
				'/_nino/webpage/design-preview/name' 	=> $stand,
			];
		}
	}

}
