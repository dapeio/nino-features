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
	 *	Design\Admin			The "Design" panel: a pane of three tabs - Themes, Typefaces
	 *										and, last, the Settings tab the registry attaches - over
	 *										one setup, which has one preview and one bar.
	 *										The panel's own screen is the one the strip names Themes
	 *										(see tab()), and it is the one screen of the setup, in two
	 *										modes: the library of the themes, where the whole setup is
	 *										kept under a name (see Themes) - one row for each theme, with
	 *										what can be done with it: load, duplicate, export, delete and
	 *										edit, and a pencil that renames a theme of the project in its
	 *										row - and, reached by Edit on a row and left by Back to list,
	 *										the setup by its objects, over the same preview and bar -
	 *										a tab for each object - the page, the header, the footer, a section, a
	 *										card, the buttons, the lists and the tables, and each feature that brings
	 *										variants - and under it a block for each aspect the object has a control
	 *										on: Base, the choice of a set - and under it a select
	 *										for each style the set brings (see Setup::styles()); Text, Surface, Colour
	 *										and Motion - with the controls
	 *										of that object: in Colour, the two colours, the five knobs and
	 *										the mode the palette is solved from, which are the page's, and
	 *										the dimming of a section, the tone of the shadow of a card and
	 *										the stripes of a table, and in Motion the animation of the page,
	 *										the hover of its pictures and what a card and a button do
	 *										under the pointer - and CSS, a view with no objects: the css
	 *										of the theme in a field of its own, compiled with the rest
	 *										together into assets/theme.css. Where a control stands is
	 *										Setup::LAYOUT's and nobody else's: the list hands the table
	 *										over and the screen is drawn from it. The loader, the tab
	 *										Typefaces (see Webfonts\Tab), has neither preview nor bar and
	 *										shows the typefaces a project has installed
	 *										and the signed list of those it can install (see Webfonts),
	 *										which belongs to the setup only as far as the selects of
	 *										the roles list what is installed. Installing and removing
	 *										happen when they are pressed - they are not a draft.
	 *
	 *										The tab brings no action of its own: every design/* action
	 *										stays on this panel, whichever tab asks for it, and the tab
	 *										shares its permission.
	 *
	 *										A theme is a draft too, until it is saved: design/theme/load
	 *										answers what the screen takes over and writes nothing, and
	 *										what the other theme actions write is a file of data/themes/
	 *										and, where the active theme is deleted, the slug in the setup
	 *										- never the stylesheet. The css of a request, of the setup or
	 *										of a theme is judged by Themes::refusal() at the doors a
	 *										request comes through, once, with a code.
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
	 *										written anywhere, with an anchor for each tab and each
	 *										object that the frame scrolls to. It is what makes the
	 *										two-step honest - nobody has to compile a design to find
	 *										out what it is.
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

		/*	The slug a theme action made, by action: the one thing a line of the log cannot
			work out from what was posted - a copy is made under the first slug that is free
			when it is made, and under another one once it exists. Written by the action,
			read by log() in the same request	*/
		private static array $_made = [];

		/*	The framework under the preview, as its own two bundles beside the
			workbench's, and the stylesheets of the active features after it, as a third.
			Their own rather than the workbench's: /_admin/.cache/script.js
			carries the panels' scripts as well, and a preview is a site, not a tool */
		public const string FRAMEWORK_CSS = '/_admin/.cache/design-preview.css';
		public const string FRAMEWORK_JS 	= '/_admin/.cache/design-preview.js';
		public const string FEATURES_CSS 	= '/_admin/.cache/design-preview-features.css';

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
				'design/theme/load' 			=> [ self::class, 'apiThemeLoad' ],
				'design/theme/save' 			=> [ self::class, 'apiThemeSave' ],
				'design/theme/rename' 		=> [ self::class, 'apiThemeRename' ],
				'design/theme/duplicate' 	=> [ self::class, 'apiThemeDuplicate' ],
				'design/theme/delete' 		=> [ self::class, 'apiThemeDelete' ],
				'design/theme/export' 		=> [ self::class, 'apiThemeExport' ],
				'design/theme/import' 		=> [ self::class, 'apiThemeImport' ],
			];
		}

		/*	No group is named here: \Nino\Admin\Panels puts every panel a feature
			brought into 'features' whatever it would name, so granting that one
			group stays a bounded grant */
		public static function nav(): array {
			return [ 'design', '/_admin/nav/design', 10 ];
		}

		/*	What the strip on top of the pane calls the panel's own screen: the library of the themes - and the setup
			behind its Edit - which is Themes where the rail says Design. The kernel draws the panel's own screen
			first, whatever the weights of the tabs are (see \Nino\Admin\Panels::collect()), so the pane opens on it */
		public static function tab(): string {
			return '/_admin/nav/themes';
		}

		/*	The one tab the panel brings: the loader of the typefaces. The Settings tab the registry attaches comes after it.
			It is a panel of the kernel's own kind, with its uri, its words, its script and its mount - and no actions:
			they are this panel's. The themes and the setup by its objects are the panel's own screen, not a tab */
		public static function tabs(): array {
			return [ Webfonts\Tab::class ];
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
				'design/theme/save' 			=> 'Save Design theme '. self::_slug( $data, Themes::SLUG ),
				'design/theme/rename' 		=> 'Rename Design theme '. self::_slug( $data, Themes::SLUG ),
				'design/theme/duplicate' 	=> 'Duplicate Design theme '. self::_slug( $data, Themes::SLUG ). ' as '. self::_made( $action, $data ),
				'design/theme/delete' 		=> 'Delete Design theme '. self::_slug( $data, Themes::SLUG ),
				'design/theme/import' 		=> 'Import Design theme '. self::_made( $action, $data ),
				// design/theme/load and design/theme/export only read, as design/preview does
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
			// The language of the workbench - the one its texts are chosen by - so that a
			// template is listed under the name its file gives it in that language
			$locale 	= \Nino\Locales::getCurrentLocale( $appData );

			foreach( Setup::PARTS as $part => $kind ) {

				$catalogue = Setup::catalogue( $library, $part, $locale );

				/*	Which knobs each template answers to - out of the stylesheet rather
					than out of a list here, so declaring a ladder is what publishes a
					knob. For every template and not only the chosen one: choosing
					another changes which rows of its tab are greyed out, and the
					screen does that before anything is asked of the server. A frame is
					asked as a set is: its style.css declares the ladders	*/
				foreach( array_keys( $catalogue ) as $set )
					$catalogue[$set]['knobs'] = Setup::knobs( $library, $part, $set );

				/*	The styles each template brings, for the same reason: the select of a style stands under the select of the
					template, and choosing another template draws the ones of that. Each with its label and its options - the
					name and the description - in the language of the workbench, and not the path of a file. What a template
					does not offer and the notes name is told once, however many times it is asked	*/
				foreach( array_keys( $catalogue ) as $set ) {

					$found = [];

					foreach( Setup::styles( $library, $part, $set, $locale, $found ) as $style => $declared )
						$catalogue[$set]['styles'][$style] = [
							'label' 	=> $declared['label'],
							'options' => array_map( static fn( array $option ): array => [ 'name' => $option['name'], 'description' => $option['description'] ], $declared['options'] ),
						];

					$catalogue[$set]['styles'] ??= [];

					foreach( $found as $note )
						if( in_array( $note, $notes, true ) === false )
							$notes[] = $note;
				}

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
				// Where every knob stands, per part, and which knobs a part has and of what
				// kind - a scale of five positions, a choice of three or a toggle of two, by
				// name: the screen draws a row for each of them - a strip, or a switch for a
				// toggle -, and the parts above say which of the rows its template answers
				// to. The five words are the steps a choice is named by, and the size and
				// the width are positions as well
				'knobs' 	=> $setup['knobs'],
				// The styles chosen for the templates, per part: style => option, an empty list for a part that chose none. What each template offers is in its entry of the catalogue above
				'styles' 	=> $setup['styles'],
				'kinds' 	=> Setup::KNOBS,
				/*	The features that bring variants of their own, each with the variants it brings - their names, their
					descriptions and the knobs their heads declare -, the anchor of the feature's block on the specimen ('' for a
					feature with none) and with what the setup chose for it: the variant
					and the value of every knob of it, none where it chose nothing. A choice kept for a feature that is
					not there rides along with the flag that says so, since a save has to carry it on	*/
				'features' => self::_features( $appData, $setup ),
				// Where the panel puts what it sets: the six aspects, the nine objects - and one more for each feature that
				// brings variants - and the table that says, for every control of the setup, its aspect, its object and its place
				// in the row, the rows of the features among them - the screen draws an object from it and from nothing else
				'layout' 	=> [ 'tabs' => Setup::TABS, 'objects' => Setup::objects( $appData ), 'controls' => Setup::layout( $appData ) ],
				/*	The setup a fresh install starts from, cut to the keys that choose something (see Themes::snapshot()): what the head of a
					closed row is compared with, so that it names only the controls that stand elsewhere than there	*/
				'defaults' 	=> Themes::snapshot( Setup::defaults( $library ) ),
				'steps' 	=> Setup::STEPS,
				'size' 		=> (int) ( $setup['size'] ?? 0 ),
				'width' 	=> (int) ( $setup['width'] ?? 0 ),
				'whitespace' => (int) ( $setup['whitespace'] ?? 0 ),
				/*	The radius of the surfaces: the position of each of the two strengths, the strength each
					kind of surface takes - the keys are the kinds there are - and the two words a kind is set to	*/
				'radius' 	=> (int) ( $setup['radius'] ?? 0 ),
				'radius-alt' => (int) ( $setup['radius-alt'] ?? 1 ),
				'radii' 	=> (array) ( $setup['radii'] ?? [] ),
				'radiusWords' => Setup::RADIUS_WORDS,
				/*	The list of the themes: the slug of the theme the stored setup was taken from and
					its own css, the list of the themes there are - the shipped ones first -
					and the active one as its file holds it, normalised against the library
					and cut to the keys that choose something - no colours where the
					file names none - which the screen compares the draft with. null where there is none	*/
				'theme' 	=> (string) ( $setup['theme'] ?? '' ),
				'css' 		=> (string) ( $setup['css'] ?? '' ),
				// The theme the website runs: the one the setup was taken from when it was last applied, which is not
				// the one of the draft that was loaded since - the screen tells the two apart
				'running' => is_string( $setup['compiled']['setup']['theme'] ?? null ) === true ? $setup['compiled']['setup']['theme'] : '',
				'themes' 	=> Themes::all( $appData, $library, $locale ),
				'active' 	=> self::_active( $appData, $library, $locale, (string) ( $setup['theme'] ?? '' ) ),
				// The animation of the page and the hover of its pictures, and what they can be: the effects and
				// strengths and speeds of Nino.css's viewport animations, the kinds of hover - which take the same strengths
				'motion' 	=> (array) ( $setup['motion'] ?? [] ),
				'effects' => array_keys( Setup::EFFECTS ),
				'hovers' 	=> array_keys( Setup::HOVERS ),
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
					whatever choices() hands it, in the places Setup::LAYOUT gives
					the colours, so a knob added in Colours appears here with a
					line in that table and none in the script. Its words are the
					one thing it does need: text keys under
					'/_admin/design/colour/<knob>/', in every locale */
				'colours'	=> (array) ( $setup['colours'] ?? [] ),
				'palette'	=> Colours::choices(),
				'brand' 	=> Colours::brand( (array) ( $setup['colours'] ?? [] ) ),
				// The second colour as it will be compiled - the one somebody
				// named, or the one Harmony derived. The swatch standing for it
				// shows this rather than the primary standing in for it
				'accent'	=> Colours::accent( (array) ( $setup['colours'] ?? [] ) ),
				/*	The words of the two choices a feature may declare out of the palette's vocabulary: the surfaces of a ground, in the
					palette's order, and the tones of an ink. And what the colours the features were given measure on the page, as the
					brand does	*/
				'grounds'	=> Colours::SURFACES,
				'inks'		=> array_keys( Setup::INKS ),
				'contrasts' => self::_contrasts( $appData, $setup ),
				// Whether the repository of typefaces has an address and a key to be read with:
				// the tab offers the list only where both are set
				'webfonts'	=> [ 'url' => Webfonts::url( $appData ) !== '', 'key' => Webfonts::key( $appData ) !== '' ],
				'notes' 	=> $notes,
				'target' 	=> Compiler::TARGET,
			] + self::state( $appData, $setup, $library ) );
		}

		/**
		 *	The features as the screen draws them: the catalogue of Setup::features() without the path of
		 *	the feature and the names of the ladders - nothing the screen reads - and, beside the variants,
		 *	what the setup chose for the feature. A variant keeps whole what its head says: its name, its
		 *	description and its knobs; the feature has its anchor, the id of its block on the specimen, '' where
		 *	it has none, and the styles it offers for all its variants (`styles`, as a template's are in its entry of
		 *	the catalogue: style => label, options of name and description) and those chosen (`picked`: style => option). A feature
		 *	the setup has an entry for and that is not in the catalogue follows them with its entry as it was kept, flagged as not
		 *	active and without an anchor
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		$setup				A normalised setup
		 *
		 *	@return 	array										key => name, anchor, active, default, catalogue (variant => name, description, knobs), styles, set, knobs, picked
		 */
		private static function _features( array &$appData, array $setup ): array {

			$chosen = (array) ( $setup['features'] ?? [] );
			$out 		= [];

			foreach( Setup::features( $appData ) as $key => $feature ) {

				$catalogue = [];

				foreach( $feature['catalogue'] as $variant => $one ) {

					foreach( array_keys( $one['knobs'] ) as $knob )
						unset( $one['knobs'][$knob]['ladder'] );

					$catalogue[$variant] = $one;
				}

				// What the feature offers as styles, for all its variants: the label and the options with their names and descriptions - not the path of a file
				$styles = [];

				foreach( $feature['styles'] as $style => $declared )
					$styles[$style] = [
						'label' 	=> $declared['label'],
						'options' => array_map( static fn( array $option ): array => [ 'name' => $option['name'], 'description' => $option['description'] ], $declared['options'] ),
					];

				$out[$key] = [
					'name' 				=> $feature['name'],
					'anchor' 			=> $feature['anchor'],
					'active' 			=> true,
					'default' 		=> $feature['default'],
					'catalogue' 	=> $catalogue,
					'styles' 			=> $styles,
					'set' 				=> (string) ( $chosen[$key]['set'] ?? '' ),
					'knobs' 			=> (array) ( $chosen[$key]['knobs'] ?? [] ),
					'picked' 			=> (array) ( $chosen[$key]['styles'] ?? [] ),
				];
			}

			foreach( $chosen as $key => $entry )
				if( isset( $out[$key] ) === false )
					$out[$key] = [ 'name' => (string) $key, 'anchor' => '', 'active' => false, 'default' => '', 'catalogue' => [], 'styles' => [], 'set' => (string) $entry['set'], 'knobs' => (array) $entry['knobs'], 'picked' => (array) $entry['styles'] ];

			return $out;
		}

		/**
		 *	What the colours the features bring measure: for each colour knob of the variant a feature was given, the ratio of each of its
		 *	two halves against the ground of the page in the mode of the half, the ratio a mark on a page has to reach (Colours::TARGET_UI)
		 *	and whether it does - the line under the brand's colour, said of the colours of a feature. Nothing refuses a colour: the
		 *	screen says it
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		$setup				A normalised setup
		 *
		 *	@return 	array										key => knob => light|dark => ratio, target, safe
		 */
		private static function _contrasts( array &$appData, array $setup ): array {

			$colours 		= Colours::normalize( (array) ( $setup['colours'] ?? [] ) );
			$ground 		= [ 'light' => Colours::palette( $colours, 'light' )['default']['bg'], 'dark' => Colours::palette( $colours, 'dark' )['default']['bg'] ];
			$catalogue 	= Setup::features( $appData );
			$out 				= [];

			foreach( (array) ( $setup['features'] ?? [] ) as $key => $entry )
				foreach( (array) ( $catalogue[$key]['catalogue'][ $entry['set'] ]['knobs'] ?? [] ) as $knob => $meta ) {

					$pair = $entry['knobs'][$knob] ?? null;

					if( $meta['kind'] !== 'colour' || is_array( $pair ) === false )
						continue;

					foreach( [ 'light', 'dark' ] as $at => $mode ) {
						$ratio = round( Colours::contrast( (string) $pair[$at], $ground[$mode] ), 2 );
						$out[$key][$knob][$mode] = [ 'ratio' => $ratio, 'target' => Colours::TARGET_UI, 'safe' => $ratio >= Colours::TARGET_UI ];
					}
				}

			return $out;
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

			// Before anything is read of the rest: a css that is refused is never stored
			if( is_string( $posted['css'] ?? null ) === true && self::_refused( $request, $posted['css'] ) === true )
				return;

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
		 *	Rendered against this project - its menu, its logo, its fonts - and assembled
		 *	through Preview, which is where the specimen and the frames around it live
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

			// ...and a css that is refused is never compiled: the screen keeps the sheet it has
			if( is_string( $posted['css'] ?? null ) === true && self::_refused( $request, $posted['css'] ) === true )
				return;

			// Same normalisation the save takes, for the same reason: a request
			// is a request, and a set that is not in the library is not shown
			// just because somebody asked for it
			$setup = self::_selection( $appData, $posted, $library, $notes );

			$css 			= Preview::css( $appData, $setup, $library, \Nino\Filesystem::getPublicDir( $appData ), $notes );
			$document = '';

			/*	Six of the eight parts are a stylesheet and nothing else, and so
				are the knobs, the size, the width, the animation, the hover of the
				pictures, the fonts and the palette: for those the screen swaps
				one <style> inside the frame it already has, and neither the
				markup nor the framework below it has to travel again. The two
				that do bring markup - header and footer - rebuild the document,
				and so does the first preview of a visit */
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
				'contrasts' => self::_contrasts( $appData, $setup ),
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
		 *	the change, with the sentence the loader says it in. The screen asks design/list
		 *	again, for the selects of the roles in Text
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
		 *	A theme, to put on the screen: its setup as the screen holds one - normalised
		 *	against the library, except the families of the fonts (see _fromFile()), and with
		 *	the choices for the features the theme's own and not what the screen held - its
		 *	css, its name and its description in the language of the workbench, and the
		 *	families it names that this project has not installed. Nothing is written:
		 *	loading is looking, and what the screen takes over stays a draft until it is
		 *	saved. A file of the project that somebody edited by hand holds its css to the
		 *	same rules as a request does
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$request			(reference) Current server request
		 *
		 *	@return 	void
		 */
		public static function apiThemeLoad( array &$appData, array &$request ): void {

			if( \Nino\Admin\Admin::guardPerm( $appData, $request, self::MANAGE_PERM ) === false )
				return;

			$library 	= \Nino\Modules\Design::libraryDir();
			$slug 		= self::_slug( \Nino\Admin\Admin::postData(), Themes::SLUG );
			$raw 			= Themes::read( $appData, $library, $slug );

			if( $raw === null ) {
				\Nino\Http::fail( $request, 404, 'there is no such theme', 'design_theme' );
				return;
			}

			$css = Themes::css( $raw );

			if( self::_refused( $request, $css ) === true )
				return;

			$notes 		= [];
			$missing 	= [];
			$setup 		= self::_fromFile( $appData, Themes::setup( $raw ), $library, $notes, $missing );

			\Nino\Http::ok( $request, Themes::entry( $slug, $raw, \Nino\Locales::getCurrentLocale( $appData ), Themes::isShipped( $library, $slug ) ) + [
				'setup' 	=> Themes::snapshot( $setup ),
				'css' 		=> $css,
				'missing' => array_values( $missing ),
				'notes' 	=> $notes,
			] );
		}

		/**
		 *	Put the screen into a theme of the project: its setup and its css, from the
		 *	selection a request carries. Its name and its description stay what the file has
		 *	them - the lines in other languages among them, which this record keeps as they
		 *	are, so that a save never turns a name-de into a plain name: a rename is the
		 *	other action (see apiThemeRename()). The file is read and written back under
		 *	one lock (see Themes::change()). The slug stays what it is, which the setup
		 *	and the exports refer to
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$request			(reference) Current server request
		 *
		 *	@return 	void
		 */
		public static function apiThemeSave( array &$appData, array &$request ): void {

			if( \Nino\Admin\Admin::guardPerm( $appData, $request, self::MANAGE_PERM ) === false )
				return;

			$library 	= \Nino\Modules\Design::libraryDir();
			$posted 	= \Nino\Admin\Admin::postData();
			$locale 	= \Nino\Locales::getCurrentLocale( $appData );
			$slug 		= self::_slug( $posted, Themes::SLUG );
			if( Themes::read( $appData, $library, $slug ) === null ) {
				\Nino\Http::fail( $request, 404, 'there is no such theme', 'design_theme' );
				return;
			}

			if( Themes::isShipped( $library, $slug ) === true ) {
				\Nino\Http::fail( $request, 409, 'a shipped theme cannot be changed or deleted - duplicate it', 'design_shipped' );
				return;
			}

			if( is_string( $posted['css'] ?? null ) === true && self::_refused( $request, $posted['css'] ) === true )
				return;

			$notes 	= [];
			$setup 	= self::_selection( $appData, $posted, $library, $notes );

			// Read again under the lock the write takes: what the file has by then is what is kept of it
			$changed = Themes::change( $appData, $slug, static fn( array $file ): array => Themes::record(
				is_string( $file['name'] ?? null ) === true ? $file['name'] : '', is_string( $file['description'] ?? null ) === true ? $file['description'] : '', $setup, $setup['css'], Themes::lines( $file ) ) );

			if( $changed === false ) {
				\Nino\Http::fail( $request, 500, 'could not write '. Themes::DIR. '/'. $slug. '.php' );
				return;
			}

			\Nino\Http::ok( $request, [
				'themes' 	=> Themes::all( $appData, $library, $locale ),
				'active' 	=> self::_active( $appData, $library, $locale, $slug ),
			] );
		}

		/**
		 *	Give a theme of the project another name and description - and nothing else: its
		 *	setup and its css are the file's own, read and written back as they are under one lock. The lines
		 *	in other languages go (a name is a name, the one that was typed), and the slug
		 *	stays what it is, which the setup and the exports refer to. The answer is the
		 *	list and the theme as design/list would give it
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$request			(reference) Current server request
		 *
		 *	@return 	void
		 */
		public static function apiThemeRename( array &$appData, array &$request ): void {

			if( \Nino\Admin\Admin::guardPerm( $appData, $request, self::MANAGE_PERM ) === false )
				return;

			$library 	= \Nino\Modules\Design::libraryDir();
			$posted 	= \Nino\Admin\Admin::postData();
			$locale 	= \Nino\Locales::getCurrentLocale( $appData );
			$slug 		= self::_slug( $posted, Themes::SLUG );
			if( Themes::read( $appData, $library, $slug ) === null ) {
				\Nino\Http::fail( $request, 404, 'there is no such theme', 'design_theme' );
				return;
			}

			if( Themes::isShipped( $library, $slug ) === true ) {
				\Nino\Http::fail( $request, 409, 'a shipped theme cannot be changed or deleted - duplicate it', 'design_shipped' );
				return;
			}

			$name = Themes::name( \Nino\Admin\Admin::postString( $posted, 'name' ) );

			if( Themes::slug( $name ) === '' ) {
				\Nino\Http::fail( $request, 400, 'the name has no letter or digit in it', 'design_name' );
				return;
			}

			$description = Themes::description( \Nino\Admin\Admin::postString( $posted, 'description' ) );

			// Read again under the lock the write takes: its setup and its css are the file's as they are by then
			$changed = Themes::change( $appData, $slug, static fn( array $file ): array => Themes::record( $name, $description, Themes::setup( $file ), Themes::css( $file ) ) );

			if( $changed === false ) {
				\Nino\Http::fail( $request, 500, 'could not write '. Themes::DIR. '/'. $slug. '.php' );
				return;
			}

			\Nino\Http::ok( $request, [
				'themes' 	=> Themes::all( $appData, $library, $locale ),
				'active' 	=> self::_active( $appData, $library, $locale, $slug ),
			] );
		}

		/**
		 *	Copy a theme - a shipped one too, which is how it is changed - into a theme
		 *	of the project: under its name in the language of the workbench and the word
		 *	for a copy, and the first slug of that name that is free
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$request			(reference) Current server request
		 *
		 *	@return 	void
		 */
		public static function apiThemeDuplicate( array &$appData, array &$request ): void {

			if( \Nino\Admin\Admin::guardPerm( $appData, $request, self::MANAGE_PERM ) === false )
				return;

			$library 	= \Nino\Modules\Design::libraryDir();
			$locale 	= \Nino\Locales::getCurrentLocale( $appData );
			$slug 		= self::_slug( \Nino\Admin\Admin::postData(), Themes::SLUG );
			$raw 			= Themes::read( $appData, $library, $slug );

			if( $raw === null ) {
				\Nino\Http::fail( $request, 404, 'there is no such theme', 'design_theme' );
				return;
			}

			$shown 	= Themes::describe( $raw, $locale );
			$name 	= Themes::name( self::_text( $appData, 'theme/copy', $shown['name'] !== '' ? $shown['name'] : $slug ) );
			$made 	= Themes::free( $appData, $library, Themes::slug( $name ) );
			$copy 	= Themes::record( $name, $shown['description'], Themes::setup( $raw ), Themes::css( $raw ) );

			if( $made === '' || Themes::write( $appData, $made, $copy ) === false ) {
				\Nino\Http::fail( $request, 500, 'could not write '. Themes::DIR. '/'. $made. '.php' );
				return;
			}

			self::$_made['design/theme/duplicate'] = $made;

			\Nino\Http::ok( $request, [
				'slug' 		=> $made,
				'themes' 	=> Themes::all( $appData, $library, $locale ),
			] );
		}

		/**
		 *	Delete a theme of the project, and with it the slug the setup keeps where it
		 *	is the active one: a deleted theme is none. The record of what was compiled
		 *	is kept, as a save keeps it. A shipped theme is a file of the feature and is
		 *	not this action's to remove
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$request			(reference) Current server request
		 *
		 *	@return 	void
		 */
		public static function apiThemeDelete( array &$appData, array &$request ): void {

			if( \Nino\Admin\Admin::guardPerm( $appData, $request, self::MANAGE_PERM ) === false )
				return;

			$library 	= \Nino\Modules\Design::libraryDir();
			$slug 		= self::_slug( \Nino\Admin\Admin::postData(), Themes::SLUG );

			if( Themes::read( $appData, $library, $slug ) === null ) {
				\Nino\Http::fail( $request, 404, 'there is no such theme', 'design_theme' );
				return;
			}

			if( Themes::isShipped( $library, $slug ) === true ) {
				\Nino\Http::fail( $request, 409, 'a shipped theme cannot be changed or deleted - duplicate it', 'design_shipped' );
				return;
			}

			// Asked while the file is still there: once it is gone the setup reads its slug as none
			$active = Setup::read( $appData, $library )['theme'] ?? '';

			if( Themes::remove( $appData, $slug ) === false ) {
				\Nino\Http::fail( $request, 500, 'could not remove '. Themes::DIR. '/'. $slug. '.php' );
				return;
			}

			if( $active === $slug && Setup::write( $appData, Setup::read( $appData, $library ) ) === false ) {
				\Nino\Http::fail( $request, 500, 'could not write '. Setup::PATH );
				return;
			}

			\Nino\Http::ok( $request, [
				'themes' 	=> Themes::all( $appData, $library, \Nino\Locales::getCurrentLocale( $appData ) ),
				'theme' 	=> $active === $slug ? '' : $active,
			] );
		}

		/**
		 *	A theme as the text of a file to download: the file of the theme as it is, a
		 *	shipped one with its lines in the other languages
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$request			(reference) Current server request
		 *
		 *	@return 	void
		 */
		public static function apiThemeExport( array &$appData, array &$request ): void {

			if( \Nino\Admin\Admin::guardPerm( $appData, $request, self::MANAGE_PERM ) === false )
				return;

			$slug = self::_slug( \Nino\Admin\Admin::postData(), Themes::SLUG );
			$raw 	= Themes::read( $appData, \Nino\Modules\Design::libraryDir(), $slug );

			if( $raw === null ) {
				\Nino\Http::fail( $request, 404, 'there is no such theme', 'design_theme' );
				return;
			}

			$json = Themes::json( $raw );

			if( $json === '' ) {
				\Nino\Http::fail( $request, 500, 'could not make a file of the theme' );
				return;
			}

			\Nino\Http::ok( $request, [ 'json' => $json, 'file' => 'nino-theme-'. $slug. '.json' ] );
		}

		/**
		 *	Take a theme file in as a theme of the project: the text of an export, under
		 *	the slug of its name, which is refused where it is a theme already. What the
		 *	file holds is held to what a request is - its setup is normalised, its css
		 *	judged by Themes::refusal() - and its lines in other languages are kept
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$request			(reference) Current server request
		 *
		 *	@return 	void
		 */
		public static function apiThemeImport( array &$appData, array &$request ): void {

			if( \Nino\Admin\Admin::guardPerm( $appData, $request, self::MANAGE_PERM ) === false )
				return;

			$library 	= \Nino\Modules\Design::libraryDir();
			$locale 	= \Nino\Locales::getCurrentLocale( $appData );
			$data 		= Themes::fromJson( \Nino\Admin\Admin::postString( \Nino\Admin\Admin::postData(), 'json' ) );

			if( is_string( $data ) === true ) {
				\Nino\Http::fail( $request, 400, 'that is no theme file of this feature', $data );
				return;
			}

			$name = Themes::name( \Nino\Admin\Admin::postString( $data, 'name' ) );
			$slug = Themes::slug( $name );

			if( $slug === '' ) {
				\Nino\Http::fail( $request, 400, 'the name has no letter or digit in it', 'design_name' );
				return;
			}

			if( Themes::exists( $appData, $library, $slug ) === true ) {
				\Nino\Http::fail( $request, 409, 'a theme with the slug "'. $slug. '" exists already', 'design_taken', [ $slug ] );
				return;
			}

			if( is_string( $data['css'] ?? null ) === true && self::_refused( $request, $data['css'] ) === true )
				return;

			$notes 		= [];
			$missing 	= [];
			$setup 		= self::_fromFile( $appData, Themes::setup( $data ), $library, $notes, $missing, true );
			$theme 		= Themes::record( $name, \Nino\Admin\Admin::postString( $data, 'description' ), $setup, Themes::css( $data ), Themes::lines( $data ) );

			if( Themes::write( $appData, $slug, $theme ) === false ) {
				\Nino\Http::fail( $request, 500, 'could not write '. Themes::DIR. '/'. $slug. '.php' );
				return;
			}

			self::$_made['design/theme/import'] = $slug;

			\Nino\Http::ok( $request, [
				'slug' 		=> $slug,
				'themes' 	=> Themes::all( $appData, $library, $locale ),
			] );
		}

		/**
		 *	The whole page the frame shows: the framework, the compiled sheet, the
		 *	stylesheets of the active features, the specimen with its frames, and
		 *	the behaviour a header set is half made of - the site as a visitor sees
		 *	it, in the language a visitor of this browser is served
		 *	(\Nino\Locales::getVisitorLocale()), which is not the language the
		 *	workbench speaks: the texts of the frames, the lang of the document.
		 *	The compiled sheet is inline, under PREVIEW_STYLE's id, so the panel can
		 *	swap it in a frame that is already standing and no url of this design
		 *	is written or reachable; the framework, the same for every design, is
		 *	linked from its two bundles (see _bundle() for why it is not inlined).
		 *
		 *	The stylesheets of the features follow the compiled sheet, linked from
		 *	a third bundle, FEATURES_CSS: the order of the site, Nino.css, then
		 *	theme.css - which the compiled sheet stands for - and then the features'
		 *	sheets, so that the :where() defaults of a feature's stylesheet lose to
		 *	the :root the compiled sheet writes, as they do on the site. Which sheets
		 *	they are is the site's own bundle list, '/.cache/style.css', that the
		 *	manifests fill at every boot (\Nino\Features::registerAssets()): its
		 *	paths below /features/, in its order - the theme and the project's own
		 *	style.css are not the preview's, a preview shows a design and not what a
		 *	project added to it. No list is kept here, and with no such path in the
		 *	list there is no link, since a bundle over nothing is never written
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
			$head	= self::_bundle( $appData, self::FRAMEWORK_CSS, [ '/_nino/Nino.css' ] ). "\n". $style;
			$sheets = array_values( array_filter(
				array_map( 'strval', \Nino\Html::getAssets( $appData, '/.cache/style.css' ) ),
				static fn( string $path ): bool => str_starts_with( $path, '/features/' ) === true
			) );

			if( $sheets !== [] )
				$head .= "\n". self::_bundle( $appData, self::FEATURES_CSS, $sheets );

			return Preview::document(
				$appData,
				\Nino\Locales::getVisitorLocale( $appData ),
				$head,
				$main,
				self::_bundle( $appData, self::FRAMEWORK_JS, [ '/_nino/Nino.js', '/_nino/Nino.ui.js' ] )
			);
		}

		/**
		 *	The selection a request carries, normalised against the library.
		 *
		 *	Only the keys of the model are taken and each is held to the type it
		 *	has - the posted json is a request, and a cast of an array raises a
		 *	warning the runtime treats as fatal - the theme and the css are a string
		 *	each, and what is none is ''. A knob's position belongs to the
		 *	template that answers to it: one the chosen template does not declare
		 *	is back where the defaults put it, so a setup does not carry the positions of a
		 *	template somebody moved away from long ago - normalize() cannot do
		 *	this, since it holds no library file open. A style belongs to the template that
		 *	declares it in the same way, and normalize() does hold the library open for
		 *	it: only the styles the chosen template declares, with an option that has a
		 *	file, are taken, and the rest is dropped and named.
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
				'theme' 	=> is_string( $posted['theme'] ?? null ) === true ? $posted['theme'] : '',
				'size' 		=> $posted['size'] ?? 0,
				'width' 	=> $posted['width'] ?? 0,
				'whitespace' => $posted['whitespace'] ?? 0,
				'radius' 	=> $posted['radius'] ?? 0,
				'radius-alt' => $posted['radius-alt'] ?? 1,
				'css' 		=> is_string( $posted['css'] ?? null ) === true ? $posted['css'] : '',
			];

			foreach( [ 'radii', 'motion', 'parts', 'knobs', 'styles', 'features', 'fonts', 'colours' ] as $key )
				$taken[$key] = is_array( $posted[$key] ?? null ) === true ? $posted[$key] : [];

			$setup = Setup::normalize( $appData, $taken, $library, $notes );
			$normal = Setup::defaults( $library );

			foreach( Setup::KNOBS as $part => $kinds ) {

				$answered = Setup::knobs( $library, $part, (string) ( $setup['parts'][$part] ?? '' ) );

				foreach( array_keys( $kinds ) as $knob )
					if( in_array( $knob, $answered, true ) === false )
						$setup['knobs'][$part][$knob] = $normal['knobs'][$part][$knob];
			}

			return $setup;
		}

		/**
		 *	A bundle of the preview, as a tag pointing at it: the framework's own
		 *	two halves, and the stylesheets of the active features.
		 *
		 *	Not inlined, for two reasons. The workbench sends a
		 *	Content-Security-Policy with `script-src 'self'` and a nonce, and an
		 *	iframe built from srcdoc inherits it - an inline <script> in there is
		 *	refused, a same-origin src is not. And Nino.css and the two scripts
		 *	together are the larger half of this answer while being the half
		 *	that never changes, and the sheets of the features change only with a
		 *	feature or one of its files: as a bundle they are written once, hashed,
		 *	and fetched from the browser's cache on every preview after the first.
		 *
		 *	Built by the same Modules\Assets the workbench builds its own two
		 *	bundles with, into the same directory. It hashes the path, the size and
		 *	the time of change of every source, so that a feature switched on or off
		 *	or a stylesheet edited writes the bundle again at the next preview, and
		 *	the address carries the new hash
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
		 *	The specimen and its frames, rendered through the kernel against this
		 *	project - each of the three on its own, see Preview::render() - in the
		 *	language a visitor of the site is served: \Nino\Locales::useLocale(), as
		 *	\Nino\Form uses it for a mail, on the copy of the app data this works on -
		 *	the copy goes with the function, and the request keeps the language of
		 *	the workbench
		 *
		 *	@param		array 		$appData			Array with current app data - a copy, see below
		 *	@param		string		$library			The feature's library
		 *	@param		array 		$setup				A normalised setup
		 *	@param		array 		&$notes				(reference) What was missing on the way
		 *
		 *	@return 	string								Rendered html
		 */
		private static function _specimen( array $appData, string $library, array $setup, array &$notes ): string {

			/*	By value, and Preview::render() then takes the copy by reference: a
				render registers fills as it goes, and the ones below are this
				preview's rather than the request's. The request answers json and
				writes nothing, so nothing would come of it either way - but a
				function that quietly changes the caller's app data is a function
				somebody will call twice */
			\Nino\Html::addFills( $appData, self::_previewFills( $appData ), '*' );

			/*	The blocks of the features are made before the language changes: the name a block is labelled with is
				the feature's in the language of the workbench (Setup::features()), and Preview::render() is handed them */
			$blocks = Preview::blocks( $appData, $notes );

			/*	Drawn in the language of a visitor, which is not the workbench's: the
				texts of the frames are what a visitor reads there. The fills above
				were asked for before, in the workbench's own - the name of the
				stand-in page is one of its words. The names and descriptions of the
				themes and the sets stay in the workbench's language: they are for
				the person at the workbench and no part of the page */
			\Nino\Locales::useLocale( $appData, \Nino\Locales::getVisitorLocale( $appData ) );

			return Preview::render( $appData, $library, $setup, $notes, $blocks );
		}

		/**
		 *	What the loader is drawn from, the same after every action
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
		 *	The slug a request names: one that can be the name of a family's directory or of
		 *	a theme's file, else nothing - so that what is posted never reaches a path, a
		 *	message or a line of the log as it is
		 *
		 *	@param		array 		$data					What the request posted
		 *	@param		string		$pattern			Webfonts::SLUG for a typeface, Themes::SLUG for a theme
		 *
		 *	@return 	string
		 */
		private static function _slug( array $data, string $pattern = Webfonts::SLUG ): string {

			$slug = $data['slug'] ?? '';

			return is_string( $slug ) === true && preg_match( $pattern, $slug ) === 1 ? $slug : '';
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
		 *	Refuse a css that Themes::refusal() names a code for: a 400 with the code, no
		 *	params and the field the css is in - the failure a person can cause, which
		 *	the screen says in its own words (see /_admin/error/<code>)
		 *
		 *	@param		array 		&$request			(reference) Current server request
		 *	@param		string		$css					As it was posted, comments and all
		 *
		 *	@return 	bool										Whether it was refused - the answer is set then
		 */
		private static function _refused( array &$request, string $css ): bool {

			$code = Themes::refusal( $css );

			if( $code === '' )
				return false;

			\Nino\Http::fail( $request, 400, Themes::REFUSALS[$code], $code, [], 'css' );

			return true;
		}

		/**
		 *	The active theme as the screen compares the draft with it: the row of the
		 *	list, the setup of its file normalised the way the screen's selection is
		 *	(a save of the screen is the thing to compare with) and cut to the keys
		 *	that choose something - no colours where the file names none - and its css - a
		 *	family the project has not installed
		 *	as '', the way the screen takes one over. What normalising replaced is not
		 *	told: the file is read on every list, and a note would repeat on every one
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$library			The feature's library
		 *	@param		string		$locale				The language of the workbench
		 *	@param		string		$slug					The slug of the setup, '' for none
		 *
		 *	@return 	array|null								slug, name, description, shipped, setup, css - null where there is none
		 */
		private static function _active( array &$appData, string $library, string $locale, string $slug ): ?array {

			$raw = $slug === '' ? null : Themes::read( $appData, $library, $slug );

			if( $raw === null )
				return null;

			$notes 		= [];
			$missing 	= [];
			$setup 		= self::_fromFile( $appData, Themes::setup( $raw ), $library, $notes, $missing );

			/*	A family the project has not installed is '' here: it is what the screen
				takes over for it on a load that installs nothing (see _takeTheme() in
				admin.js), and a record that said the face of the role instead would
				read "changed" against a screen nobody changed, once the panel is
				opened again	*/
			foreach( Fonts::ROLES as $role )
				if( isset( $missing[ (string) ( $setup['fonts'][$role]['family'] ?? '' ) ] ) === true )
					$setup['fonts'][$role]['family'] = '';

			return Themes::entry( $slug, $raw, $locale, Themes::isShipped( $library, $slug ) ) + [
				'setup' 	=> Themes::snapshot( $setup ),
				'css' 		=> Themes::css( $raw ),
			];
		}

		/**
		 *	The setup of a theme's file, normalised against the library the way a
		 *	selection is - except for the families of the fonts. A family a role is
		 *	given has to be one its select offers, and normalising puts the face of the
		 *	role in the place of one that is not installed here, which is the one case a
		 *	theme is made for: the file's own string stays where it can be the slug of a
		 *	family (Webfonts::SLUG), so that the screen can offer to install it before it
		 *	takes the theme over. The families that stay unoffered are told in $missing,
		 *	one entry each: slug, the name of the list's entry (the slug where the list
		 *	has none) and whether the list of the loader has it at all.
		 *
		 *	And except for the colours: a file that names none - the theme the feature ships -
		 *	chooses no brand colour, so the setup has none either, and what takes the setup
		 *	over keeps the colours it has. A file that names them (a theme of the project, which
		 *	a save writes with them) has them normalised as a selection's are.
		 *
		 *	The choices for the features are the theme's, whatever the file says: a file that names
		 *	none has the empty choice, which puts every feature back to its own look, and a feature
		 *	it names gets that variant and those knobs. An entry for a feature that is not in the
		 *	catalogue - not active, or without variants - is dropped and said in $notes: nothing is
		 *	compiled for it, and the theme is taken over for the features the project has.
		 *	$keep says it is not taken over but written down - the import of a file - and keeps
		 *	such an entry as the setup keeps one, for the day the feature is back.
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		$wanted				The setup of a file, see Themes::setup()
		 *	@param		string		$library			The feature's library
		 *	@param		array 		&$notes				(reference) What had to be replaced or dropped
		 *	@param		array 		&$missing			(reference) slug => [ slug, name, inList ]
		 *	@param		bool			$keep					Whether the choices for features that are not there stay
		 *
		 *	@return 	array										A normalised setup, the families of the file's roles as they are, and no colours where the file names none
		 */
		private static function _fromFile( array &$appData, array $wanted, string $library, array &$notes, array &$missing, bool $keep = false ): array {

			$given = is_array( $wanted['features'] ?? null ) === true ? $wanted['features'] : [];

			if( $keep === false ) {

				$catalogue = Setup::features( $appData );

				foreach( $given as $key => $entry )
					if( is_array( $entry ) === true && preg_match( \Nino\Features::KEY_PATTERN, (string) $key ) === 1 && isset( $catalogue[(string) $key] ) === false ) {
						$notes[] = 'the feature "'. Setup::shown( $key ). '" is not active, or brings no variants - its choice in the theme is dropped';
						unset( $given[$key] );
					}
			}

			$wanted['features'] = $given;
			$setup 	= self::_selection( $appData, $wanted, $library, $notes );

			if( is_array( $wanted['colours'] ?? null ) === false )
				unset( $setup['colours'] );

			$fonts 	= is_array( $wanted['fonts'] ?? null ) === true ? $wanted['fonts'] : [];
			$names 	= array_column( Webfonts::cached( $appData )['families'] ?? [], 'family', 'slug' );
			$known 	= Fonts::families( $appData );

			foreach( Fonts::ROLES as $role ) {

				$family = is_array( $fonts[$role] ?? null ) === true ? ( $fonts[$role]['family'] ?? null ) : null;

				// One the select offers was kept, and an installed family a role is not offered
				// (a face that is no monospace for the code) is not one to install again
				if( is_string( $family ) === false || preg_match( Webfonts::SLUG, $family ) !== 1 || isset( $known[$family] ) === true || in_array( $family, Fonts::options( $appData, $role ), true ) === true )
					continue;

				$setup['fonts'][$role]['family'] = $family;
				$missing[$family] = [ 'slug' => $family, 'name' => (string) ( $names[$family] ?? $family ), 'inList' => isset( $names[$family] ) ];
			}

			return $setup;
		}

		/**
		 *	The slug a theme action made, for its line of the log: the one it wrote down
		 *	when it ran, or - where the line is asked for on its own - what the posted data
		 *	alone says: the slug of the name of the file an import was given
		 *
		 *	@param		string		$action				A theme action that makes a theme
		 *	@param		array 		$data					What the request posted
		 *
		 *	@return 	string
		 */
		private static function _made( string $action, array $data ): string {

			if( isset( self::$_made[$action] ) === true )
				return self::$_made[$action];

			$file = $action === 'design/theme/import' ? Themes::fromJson( \Nino\Admin\Admin::postString( $data, 'json' ) ) : null;

			return is_array( $file ) === true ? Themes::slug( Themes::name( \Nino\Admin\Admin::postString( $file, 'name' ) ) ) : '';
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
