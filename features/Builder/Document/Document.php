<?php
declare(strict_types=1);
/**
 *	Nino									A compact filesystembased php framework
 *	Modules\Builder\Document	see features/Builder/Builder.php for the feature's own
 *												docblock
 *
 *	@package							Dape/Nino
 *	@author								David Perchermeier <mail@dape.io>
 *	@link									https://github.com/dapeio/nino
 */
namespace Nino\Modules\Builder {

	/**
	 *	Nino								A compact filesystembased php framework
	 *	Document						The Reader and the Writer joined to the project: the page
	 *											templates it has, one of them loaded with its hash, saved
	 *											when the hash is still the file's, created, copied under
	 *											a new name with its keys and slots, deleted - and
	 *											the registry the panel builds its forms from. No request
	 *											and no response in here: every method answers an array
	 *											with a 'status' of what the panel would answer (200, 400,
	 *											403, 404, 409, 500) and what goes with it, so the tests drive
	 *											it directly and Admin only has to say it.
	 *
	 *											The files are the project's page-*.tpl in /templates, the
	 *											name validated before a path is built from it, and the path
	 *											built by \Nino\Filesystem. A save is a mutation of the file
	 *											(\Nino\Filesystem::mutate()): the hash is compared inside
	 *											the lock, so two windows cannot overwrite each other.
	 *
	 *											What a save creates besides the file, it creates with the
	 *											code of the panels that own it, never with a copy of it:
	 *											a text key with \Nino\Modules\Text\Keys::apiCreate() and
	 *											apiRename(), the texts typed for a key with its
	 *											apiSaveBatch(), an image slot with
	 *											\Nino\Modules\Images\Slots::apiCreate() - and takes them away
	 *											again with Slots::apiDelete() and Keys::apiDelete(), where a
	 *											save that stopped has made them for nothing, and writes the
	 *											texts it changed back as they were. Those answer a
	 *											request, so they are called with one - the posted data set,
	 *											the request they answer into read back - and they ask for
	 *											the permission of their own panel, which is as it should be:
	 *											an account that may not create keys cannot create them
	 *											through here either - and a save asks for those permissions,
	 *											and checks what it is going to make, before it changes
	 *											anything. The alt texts a new slot is made with, and the picture
	 *											and the alt texts a copy of a slot takes over, are written
	 *											with \Nino\Images itself, which asks for no permission where
	 *											the Images panel's images/alt asks for its own: so the save
	 *											asks whether the account has that one, writes them where it
	 *											has and leaves them out where it has not - the save goes
	 *											through either way, and the slot is made without them.
	 *
	 *	@package						Dape/Nino
	 *	@author							David Perchermeier <mail@dape.io>
	 *	@link								https://github.com/dapeio/nino
	 */
	class Document {

		// Where the page templates are, and what a file name has to look like
		// before a path is made from it - the name is also the category of the
		// text keys the page owns
		public const string TEMPLATES = '/templates';
		public const string FILE = '/^page-[a-z0-9]+(?:-[a-z0-9]+)*$/D';

		// The frames a page may name, and a name for the page
		private const string FRAME = '/^html-(?:header|footer)(?:-[a-z0-9]+)*$/D';
		private const string TYPE = '/^[a-z][a-z0-9_-]*$/D';
		private const string SLOT = '#^/[a-z][a-z0-9_-]*(?:/[a-z][a-z0-9_-]*)*$#D';

		// What a call cannot carry: the shortcode syntax has no escape for a quote, a
		// bracket or a line break in a value, and the kernel reads ' as "
		private const string UNSAFE = '/["\'\[\]\r\n]/';

		// The longest alt text the Images panel keeps of a picture
		private const int ALT_MAX = 250;

		// The names of the components whose text is a line of free text with fields in it, which schemas() marks - 'fields' => true - so that the Reader and the panel have no name:
		// the kernel does not mark them (yet). The stack whose cell is a whole section, the one whose cell is a row and the one whose cell is its columns are the registry's ('cell'),
		// and a column is a component that stands in a place a loop holds only (schemas() marks it 'columnar')
		private const array LINES = [ 'line', 'col' ];

		/**
		 *	Whether a name may be a page template's: the file is page-<slug>
		 *
		 *	@param		string		$file					Without .tpl
		 *
		 *	@return 	bool
		 */
		public static function validFile( string $file ): bool {
			return preg_match( self::FILE, $file ) === 1;
		}

		/**
		 *	The components and the stacks of the Components module as the
		 *	Reader and the Writer take them: the schemas, each with the
		 *	defaults of its attributes
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	array										[ 'components' => name => schema, 'stacks' => name => schema ]; a component that stands
		 *																	in the places a loop holds only says 'columnar' => true, a component whose text is a line of
		 *																	fields 'fields' => true, and one that is a surface says 'surface' => its kind (see
		 *																	Reader::surface()), which the panel offers the radius of the page for
		 */
		public static function schemas( array &$appData ): array {

			$schemas = [ 'components' => \Nino\Modules\Components::components( $appData ), 'stacks' => \Nino\Modules\Components::stacks( $appData ) ];

			foreach( array_keys( $schemas['components'] ) as $name )
				$schemas['components'][$name]['defaults'] = \Nino\Modules\Components::defaults( $appData, (string) $name );

			// A component that belongs in the places a loop holds and in no other is a column of that loop: the registry says the places in 'holds' of the stacks and in 'in' of the components
			$held = array_values( array_filter( array_map( static fn( mixed $schema ): string => is_array( $schema ) === true ? Reader::placeOf( $schema ) : '', $schemas['stacks'] ), static fn( string $place ): bool => $place !== '' ) );

			foreach( array_keys( $schemas['components'] ) as $name ) {

				$in = $schemas['components'][$name]['in'] ?? null;

				if( is_array( $in ) === true && $in !== [] && array_diff( $in, $held ) === [] )
					$schemas['components'][$name]['columnar'] = true;
			}

			// The kind of surface is read from what the registry says of a component, and told to the panel, which knows no component by name
			// The first argument of a column is the text key of its title: for the panel and for the model it is a component with a text source
			foreach( array_keys( $schemas['components'] ) as $name ) {

				if( Reader::columnar( $schemas['components'][$name] ) === true )
					$schemas['components'][$name]['source'] = 'text';

				if( in_array( $name, self::LINES, true ) === true )
					$schemas['components'][$name]['fields'] = true;
			}

			foreach( array_keys( $schemas['components'] ) as $name ) {

				$surface = Reader::surface( $schemas['components'][$name], $schemas['components'] );

				if( $surface !== '' )
					$schemas['components'][$name]['surface'] = $surface;
			}

			foreach( array_keys( $schemas['stacks'] ) as $name ) {

				$schemas['stacks'][$name]['defaults'] = \Nino\Modules\Components::defaults( $appData, (string) $name, true );
			}

			return $schemas;
		}

		/**
		 *	Everything the panel builds its forms from: the components and
		 *	the stacks with their labels in the language of the workbench, and with
		 *	them the words of the options an attribute takes from a method of its
		 *	feature (optionLabels, value => label, in the same language) - and
		 *	the rest of the schema as the kernel registered it, the icon a component
		 *	is shown by (icon), where it belongs (in), what it draws (draws), what
		 *	it holds (holds), whether it is a link (link), who registered it (by)
		 *	and the group a tool offers it under (group) among it, and its label in
		 *	every language of the site (labels, by locale: what the alt text of a new
		 *	picture is made with in each, and what an empty text field of the component
		 *	shows in the language it is typed in) -, the groups the kernel's and a project's
		 *	components are offered in, in the order they are listed in (groups, the
		 *	words of \Nino\Modules\Components::GROUPS), the names of the features
		 *	that registered a component (features, by their key),
		 *	the types of the Elements panel with their fields, the image slots (each
		 *	with the url of its picture, null where it has none, and its alt
		 *	texts by language), the frames a page may name - and the languages
		 *	of the site, by the name the project gives them, with the native
		 *	one, which is the language the panel starts in
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	array										[ 'status' => 200, 'components', 'stacks', 'groups', 'features' => key => name, 'types', 'fieldTypes', 'slots', 'headers', 'footers', 'locales' => [ [ locale, name ], ... ], 'native', 'radii' => kind => strength ]
		 */
		public static function registry( array &$appData ): array {

			$locale		= \Nino\Admin\Admin::sessionLocale( $appData );
			$schemas	= self::schemas( $appData );

			$available = \Nino\Locales::getAvailableLocales( $appData );

			foreach( [ 'components', 'stacks' ] as $kind )
				foreach( $schemas[$kind] as $name => $schema ) {

					$schemas[$kind][$name]['label'] = \Nino\Features::localized( $schema['label'], $locale );

					// A component is called in each language of the site what the registry calls it there: the placeholder of a new one is its label
					if( $kind === 'components' )
						foreach( $available as $each )
							$schemas[$kind][$name]['labels'][(string) $each] = \Nino\Features::localized( $schema['label'], (string) $each );

					foreach( (array) $schema['attributes'] as $attribute => $declared ) {
						$schemas[$kind][$name]['attributes'][$attribute]['label'] = \Nino\Features::localized( $declared['label'] ?? $attribute, $locale );
						$schemas[$kind][$name]['attributes'][$attribute]['hint'] = \Nino\Features::localized( $declared['hint'] ?? '', $locale );

						// The words of the options a method of the feature answered: each label in the language of the workbench, the way the label of the attribute is
						if( is_array( $declared['optionLabels'] ?? null ) === true )
							$schemas[$kind][$name]['attributes'][$attribute]['optionLabels'] = array_map( static fn( mixed $label ): string => \Nino\Features::localized( $label, $locale ), $declared['optionLabels'] );
					}
				}

			// The picker names the features that brought a component, in the language of the workbench: the key stands where a feature has no name
			$features = [];

			foreach( $schemas['components'] as $schema ) {

				$by = (string) ( $schema['by'] ?? 'nino' );

				if( $by === 'nino' || isset( $features[$by] ) === true )
					continue;

				$name = trim( \Nino\Features::localized( \Nino\Features::get( $appData, $by )['name'] ?? '', $locale ) );

				$features[$by] = $name !== '' ? $name : $by;
			}

			$types = [];

			foreach( self::_types( $appData ) as $type ) {

				$data		= (array) \Nino\Filesystem::getFileContent( $appData, '/elements/'. $type. '.php', [] );
				$fields	= [];

				foreach( (array) ( $data['model'] ?? [] ) as $field => $declared )
					if( is_array( $declared ) === true && in_array( $declared['type'] ?? '', \Nino\Elements::FIELD_TYPES, true ) === true )
						$fields[$field] = array_intersect_key( $declared, array_flip( [ 'type', 'locale', 'html', 'blocks', 'breaks', 'options' ] ) );

				$types[] = [ 'uri' => '/'. $type, 'title' => (string) ( $data['title'] ?? $type ), 'fields' => $fields ];
			}

			$slots = [];

			foreach( \Nino\Images::getSlots( $appData ) as $uri => $slot )
				$slots[] = [
					'uri'				=> (string) $uri,
					'label'			=> (string) ( $slot['label'] ?? $uri ),
					'width'			=> (int) ( $slot['width'] ?? 0 ),
					'height'		=> (int) ( $slot['height'] ?? 0 ),
					'hasImage'	=> ( $slot['filename'] ?? null ) !== null,
					'url'				=> ( $slot['filename'] ?? null ) !== null ? \Nino\Images::getUrl( $appData, (string) $slot['filename'] ) : null,
					'alt'				=> is_array( $slot['alt'] ?? null ) === true ? $slot['alt'] : [],
				];

			usort( $slots, static fn( array $a, array $b ): int => strcmp( $a['uri'], $b['uri'] ) );

			$locales = [];

			foreach( $available as $each ) {

				// The name of a language is the project's to give, and the code stands where it gave none
				$name = \Nino\Html::renderTextfill( $appData, '/_nino/locale/'. $each. '/name' );

				$locales[] = [ 'locale' => (string) $each, 'name' => $name !== '' ? $name : (string) $each ];
			}

			return [
				'status'			=> 200,
				'components'	=> $schemas['components'],
				'stacks'			=> $schemas['stacks'],
				'groups'			=> \Nino\Modules\Components::GROUPS,
				'features'		=> $features,
				'types'				=> $types,
				'fieldTypes'	=> \Nino\Elements::FIELD_TYPES,
				'slots'				=> $slots,
				'headers'			=> self::_frames( $appData, 'html-header' ),
				'footers'			=> self::_frames( $appData, 'html-footer' ),
				'locales'			=> $locales,
				'native'			=> \Nino\Locales::getNativeLocale( $appData ),
				'radii'				=> self::radii( $appData ),
			];
		}

		/**
		 *	The strength each kind of surface takes where an element names none: the plain word of the marker --radius-<kind>-default that the
		 *	stylesheets of the site declare, in the order of the style bundle so that the last declaration is the one in force - Nino.css has
		 *	them, all standard, and the Design writes them with its setup. The workbench loads none of the site's stylesheets, so the panel
		 *	cannot ask a computed style for the marker: this is its answer, and it reads the same files the visitor's browser does. A kind no
		 *	stylesheet says anything of takes the standard strength. The files are read as text and not as a cascade: the last declaration of
		 *	a marker anywhere in the bundle counts, as if it stood at :root, whatever its selector, its media query or its importance.
		 *	Nino.css and the Design declare the markers at :root and nowhere else; a stylesheet of a project that sets one elsewhere would
		 *	mislead the toggle
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	array										kind => 'standard' or 'alternative', a value of Reader::SURFACES each, in that order
		 */
		public static function radii( array &$appData ): array {

			$radii = array_fill_keys( Reader::SURFACES, Reader::RADIUS[0] );

			foreach( \Nino\Html::getAssets( $appData, '/.cache/style.css' ) as $file ) {

				// A marker named in a comment is no declaration
				$css = (string) preg_replace( '~/\*.*?\*/~s', '', (string) \Nino\Filesystem::getFileContent( $appData, (string) $file, '' ) );

				if( preg_match_all( '/--radius-([a-z]+)-default\s*:\s*([a-z]+)\s*[;}]/', $css, $found, PREG_SET_ORDER ) < 1 )
					continue;

				foreach( $found as $declaration )
					if( isset( $radii[$declaration[1]] ) === true && in_array( $declaration[2], Reader::RADIUS, true ) === true )
						$radii[$declaration[1]] = $declaration[2];
			}

			return $radii;
		}

		/**
		 *	Every page template of the project: what the list screen shows
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	array										[ 'status' => 200, 'templates' => [ file, name, animate, vpa, vpaSpeed, wrapClass,
		 *																	header, footer, sections, foreign, readable, reason, editable, usedBy ] ], by file;
		 *																	the animation of the template as the Reader has it from its wrap, reason the
		 *																	first block the Reader failed at, null where there is none
		 */
		public static function list( array &$appData ): array {

			$schemas		= self::schemas( $appData );
			$templates	= [];

			foreach( glob( \Nino\Filesystem::path( $appData, self::TEMPLATES ). '/page-*.tpl' ) ?: [] as $path ) {

				$file		= basename( $path, '.tpl' );
				$model	= Reader::read( (string) \Nino\Filesystem::getFileContent( $appData, self::TEMPLATES. '/'. $file. '.tpl', '' ), $schemas );
				$kinds	= array_count_values( array_column( $model['blocks'], 'kind' ) );
				$failed	= array_filter( $model['blocks'], static fn( array $block ): bool => ( $block['kind'] ?? '' ) === 'html' && is_array( $block['reason'] ?? null ) === true );

				$templates[] = [
					'file'			=> $file,
					'name'			=> $model['name'],
					'animate'		=> $model['animate'],
					'vpa'				=> $model['vpa'],
					'vpaSpeed'	=> $model['vpaSpeed'],
					'wrapClass'	=> $model['wrapClass'],
					'header'		=> $model['header'],
					'footer'		=> $model['footer'],
					'sections'	=> (int) ( $kinds['section'] ?? 0 ),
					'foreign'		=> (int) ( $kinds['html'] ?? 0 ),
					'readable'	=> $failed === [],
					'reason'		=> $failed === [] ? null : reset( $failed )['reason'],
					'editable'	=> self::validFile( $file ),
					'usedBy'		=> self::usedBy( $appData, $file ),
				];
			}

			usort( $templates, static fn( array $a, array $b ): int => strcmp( $a['file'], $b['file'] ) );

			return [ 'status' => 200, 'templates' => $templates ];
		}

		/**
		 *	The routes whose body names the page template
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$file					page-home
		 *
		 *	@return 	array										[ [ route, uri ], ... ]
		 */
		public static function usedBy( array &$appData, string $file ): array {

			$routes = [];

			foreach( (array) ( $appData['/nino/http/routes'] ?? [] ) as $route => $definition )
				if( is_array( $definition ) === true && preg_match( '~\[template /templates/'. preg_quote( $file, '~' ). '\]~', (string) ( $definition['body'] ?? '' ) ) === 1 )
					$routes[] = [ 'route' => (string) $route, 'uri' => (string) ( $definition['uri'] ?? '' ) ];

			return $routes;
		}

		/**
		 *	One page template: its model, the hash of the file as it is now
		 *	and the file itself
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$file					page-home, without .tpl
		 *
		 *	@return 	array										[ 'status' => 200, 'model', 'hash', 'source' ], 400 for a name
		 *																	that is none, 404 for a file that is not there
		 */
		public static function load( array &$appData, string $file ): array {

			if( self::validFile( $file ) === false )
				return self::_error( 400, 'a page template is called page- and a slug of lower case letters, digits and hyphens', 'file' );

			$source = \Nino\Filesystem::getFileContent( $appData, self::TEMPLATES. '/'. $file. '.tpl', false );

			if( is_string( $source ) === false )
				return self::_error( 404, 'there is no such page template', 'missing' );

			$model = Reader::read( $source, self::schemas( $appData ) );
			$model['file'] = $file;

			return [ 'status' => 200, 'model' => $model, 'hash' => hash( 'sha256', $source ), 'source' => $source ];
		}

		/**
		 *	Write a model into its page template - when the file is still the
		 *	one the model was loaded from, and the keys and slots it names as
		 *	new can be made
		 *
		 *	The model is written, read again and compared with itself: a
		 *	section that does not read back is a problem the panel is told of
		 *	with the Reader's reason, and nothing is written. A model that is
		 *	what the file reads as already is not written at all - the file is
		 *	not rewritten into the canonical form where nobody changed it - but
		 *	for a file that says the hover of a picture behind a class the Writer
		 *	puts after it (see Reader::hoverBehind()), which a save writes anew.
		 *	A block that came back from an edit (edited: true) is read as a
		 *	section again first; a section that carries renamedFrom has the
		 *	keys of its old id moved to the new one - the sources, the text
		 *	keys, and the slots, which are made again under the new name with
		 *	the alt texts of the old one, and hand their file over to it
		 *	(nothing is deleted).
		 *
		 *	The model also carries what the file does not: the texts typed for
		 *	the key of a component (values, by language - for a key that is
		 *	new and for one that is there alike) and the alt texts a new slot
		 *	is made with (in its create - or, for the slot an image attribute
		 *	of a component names, in its slots). The texts are written with the Text
		 *	Keys' own action, each in the language it is for, once the keys are
		 *	there - made, or moved to their new name - and every one is read
		 *	again to see that the language files took it. A model that is the
		 *	file but for its texts writes the texts and leaves the file alone.
		 *	A language the site does not have is refused (400, value).
		 *
		 *	Everything a save refuses for is refused before the first change:
		 *	the permissions of the panels that own the keys and the slots, what
		 *	each new key and slot is made of, a target a move would overwrite,
		 *	a text for a key that is not there. The Images panel's permission
		 *	refuses nothing: for an account that has not got it the slots are
		 *	made without alt texts and without what a copy takes over.
		 *
		 *	What is made then is made in an order that can be undone: the keys
		 *	and slots that are new first (nothing in the project points at them
		 *	yet), then the keys and the files that move, then the texts, just
		 *	before the file is written - and where it is not written the texts
		 *	are put back as they were (read before they were changed), the moves
		 *	are undone, and the keys this save made are taken away again.
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$file					page-home
		 *	@param		array			$model				The model, see Reader
		 *	@param		string		$hash					The hash load() gave
		 *	@param		bool			$force				Write although the file is not the one the hash is of
		 *
		 *	@return 	array										[ 'status' => 200, 'model', 'hash' ] - the model as it reads back; 400 with
		 *																	'problems', 403, 404, 409 for a file that changed, a key or slot a
		 *																	move would overwrite ('key-exists') or a static section that has the
		 *																	id a loop of sections makes for one of its own ('section-id-taken',
		 *																	with the ids), or what a panel that makes a key or a slot or writes
		 *																	a text answered
		 */
		public static function save( array &$appData, string $file, array $model, string $hash, bool $force = false ): array {

			if( self::validFile( $file ) === false )
				return self::_error( 400, 'a page template is called page- and a slug of lower case letters, digits and hyphens', 'file' );

			$path = self::TEMPLATES. '/'. $file. '.tpl';

			if( \Nino\Filesystem::fileExists( $appData, $path ) === false )
				return self::_error( 404, 'there is no such page template', 'missing' );

			$current = (string) \Nino\Filesystem::getFileContent( $appData, $path, '' );

			// Early, so that nothing is made for a save that is going to be refused
			if( $force === false && hash_equals( hash( 'sha256', $current ), $hash ) === false )
				return self::_error( 409, 'the page template changed since it was loaded', 'conflict' );

			$schemas	= self::schemas( $appData );
			$problems	= self::_shape( $model, $schemas );

			if( $problems !== [] )
				return self::_error( 400, 'the page template is not valid', 'invalid', $problems );

			// A loop of sections makes the ids of its sections of its own id and a number: a section that stands in the file by such an id is the id of two
			// sections of the page. What was saved so, the Reader reads as it always did
			$taken		= self::_loopIds( $model );

			if( $taken !== [] )
				return self::_error( 409, 'a section has an id that a loop of sections makes for its own sections', 'section-id-taken', $taken );

			$model		= self::_again( $model, $schemas );
			$moves		= self::_rename( $model, $file, $schemas );

			// A text is for a language of the site, whatever else the model holds
			$problems = self::_languages( $appData, $model );

			if( $problems !== [] )
				return self::_error( 400, 'a text is for a language the site does not have', 'value', $problems );

			[ $texts, $problems ] = self::_texts( $model, $schemas );

			if( $problems !== [] )
				return self::_error( 400, 'the page template is not valid', 'invalid', $problems );

			// Nothing to write: the model is the file, and has no text to go to a key - and the file does not say the hover of a picture behind a class that the Writer puts after it,
			// which reads as the model of the call the Writer writes and which a save writes anew, even where nothing else changed
			$disk = Reader::read( $current, $schemas );
			$disk['file'] = $file;

			$same = self::_same( [ 'file' => $file ] + self::_unstaged( $model ), $disk ) === true && Reader::hoverBehind( $current, $schemas ) === false;

			if( $same === true && $texts === [] )
				return [ 'status' => 200, 'model' => $disk, 'hash' => hash( 'sha256', $current ) ];

			$problems = self::_values( $model, $schemas );

			if( $problems !== [] )
				return self::_error( 400, 'a value cannot stand in a call', 'value', $problems );

			$problems = self::problems( $appData, $model, $schemas );

			if( $problems !== [] )
				return self::_error( 400, 'the page template is not valid', 'invalid', $problems );

			[ $failure, $plan ] = self::_plan( $appData, $model, $moves, $schemas, $texts );

			if( $failure !== null )
				return $failure;

			$made			= [];
			$keys			= [];
			$failure	= self::_create( $appData, $plan, $made, $keys );

			if( $failure !== null ) {
				self::_rollback( $appData, [], [], $made, $keys );
				return $failure;
			}

			[ $failure, $undo ] = self::_move( $appData, $plan );

			if( $failure !== null ) {
				self::_rollback( $appData, [], $undo, $made, $keys );
				return $failure;
			}

			[ $failure, $before ] = self::_write( $appData, $plan['values'], $keys );

			if( $failure !== null ) {
				self::_rollback( $appData, $before, $undo, $made, $keys );
				return $failure;
			}

			// The file says what the model says already: the texts were all there was to write
			if( $same === true )
				return [ 'status' => 200, 'model' => $disk, 'hash' => hash( 'sha256', $current ) ];

			$source = Writer::write( $model, $schemas );

			$conflict = false;
			$written	= \Nino\Filesystem::mutate( $appData, $path, static function( mixed $current ) use ( $hash, $force, $source, &$conflict ): ?string {

				if( $force === false && hash_equals( hash( 'sha256', (string) $current ), $hash ) === false ) {
					$conflict = true;
					return null;
				}

				return $source;
			}, '' );

			if( $written === false ) {

				self::_rollback( $appData, $before, $undo, $made, $keys );

				return $conflict === true
					? self::_error( 409, 'the page template changed since it was loaded', 'conflict' )
					: self::_error( 500, 'could not write the page template', 'write' );
			}

			$read = Reader::read( $source, $schemas );
			$read['file'] = $file;

			return [ 'status' => 200, 'model' => $read, 'hash' => hash( 'sha256', $source ) ];
		}

		/**
		 *	The ids of the sections that stand where a loop of sections makes its own: a loop on the section "news"
		 *	makes news-1, news-2 and so on, and a section that is none of a loop and is called news-2 would be the id
		 *	of two sections of the page
		 *
		 *	@param		array			$model
		 *
		 *	@return 	array										The ids, in the order of the sections; [] for a model with none
		 */
		private static function _loopIds( array $model ): array {

			$loops	= [];
			$taken	= [];

			foreach( (array) ( $model['blocks'] ?? [] ) as $block )
				if( is_array( $block ) === true && ( $block['kind'] ?? '' ) === 'section' && is_array( $block['stack'] ?? null ) === true )
					$loops[] = (string) ( $block['id'] ?? '' );

			foreach( (array) ( $model['blocks'] ?? [] ) as $block ) {

				if( is_array( $block ) === false || ( $block['kind'] ?? '' ) !== 'section' || is_array( $block['stack'] ?? null ) === true )
					continue;

				foreach( $loops as $loop )
					if( $loop !== '' && preg_match( '/^'. preg_quote( $loop, '/' ). '-[0-9]+$/D', (string) ( $block['id'] ?? '' ) ) === 1 )
						$taken[] = (string) $block['id'];
			}

			return $taken;
		}

		/**
		 *	What a model would be written as, in the parts the editor shows: the
		 *	whole file as it would be saved, and the head, each block and the
		 *	foot as the Writer writes them - joined by a blank line they are the
		 *	file. A block that came back from an edit is read as a section again
		 *	first, as a save does, and the model that comes of it is answered,
		 *	so the panel learns at once whether an edited block is a section now.
		 *	Nothing is written and nothing is made
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			$model				The model, see Reader
		 *
		 *	@return 	array										[ 'status' => 200, 'model', 'source', 'parts' => [ [ kind, source, block ], ... ] ], kind
		 *																	being head, section, html or foot and block the index in the model of the
		 *																	block; 400 for a model that is not valid
		 */
		public static function source( array &$appData, array $model ): array {

			$schemas	= self::schemas( $appData );
			$problems	= self::_shape( $model, $schemas );

			if( $problems !== [] )
				return self::_error( 400, 'the page template is not valid', 'invalid', $problems );

			$model		= self::_again( $model, $schemas );
			$parts		= [];

			// After the blocks that were edited are read: what they came to is looked at, as a save does
			$problems = self::_values( $model, $schemas );

			if( $problems !== [] )
				return self::_error( 400, 'a value cannot stand in a call', 'value', $problems );

			$parts[] = [ 'kind' => 'head', 'source' => Writer::head( $model ), 'block' => null ];

			foreach( (array) $model['blocks'] as $index => $block )
				$parts[] = [ 'kind' => (string) $block['kind'], 'source' => Writer::block( $block, $schemas ), 'block' => (int) $index ];

			$parts[] = [ 'kind' => 'foot', 'source' => Writer::foot( $model ), 'block' => null ];

			return [ 'status' => 200, 'model' => $model, 'source' => Writer::write( $model, $schemas ), 'parts' => $parts ];
		}

		/**
		 *	A new, empty page template
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$name					What it is called; the file is page-<the name as a slug>
		 *	@param		string|null	$header			The frame above it, null for html-header where the project has one
		 *	@param		string|null	$footer			...and below it
		 *
		 *	@return 	array										[ 'status' => 200, 'model', 'hash' ]; 400, 409 for a file that is there
		 */
		public static function create( array &$appData, string $name, ?string $header = null, ?string $footer = null ): array {

			$name = trim( $name );

			if( self::_validName( $name ) === false )
				return self::_error( 400, 'a name is one line of 1 to 160 characters without <, > or brackets', 'name' );

			$slug = \Nino\Feature::slug( $name );

			if( $slug === '' )
				return self::_error( 400, 'a name needs letters or digits to make a file name from', 'name' );

			$file = 'page-'. $slug;

			if( self::validFile( $file ) === false )
				return self::_error( 400, 'a name needs letters or digits to make a file name from', 'name' );

			$frames = [];

			foreach( [ 'header' => $header, 'footer' => $footer ] as $place => $given ) {

				$frame = $given ?? 'html-'. $place;

				// A name is checked before a path is built from it
				if( $given !== null && $frame !== '' && ( preg_match( self::FRAME, $frame ) !== 1 || str_starts_with( $frame, 'html-'. $place ) === false ) )
					return self::_error( 400, 'there is no such '. $place. ' template', $place );

				$exists = $frame !== '' && \Nino\Filesystem::fileExists( $appData, self::TEMPLATES. '/'. $frame. '.tpl' ) === true;

				// A frame nobody named is the project's html-header or html-footer, where it has one
				if( $given === null )
					$frame = $exists === true ? $frame : '';
				elseif( $frame !== '' && $exists === false )
					return self::_error( 400, 'there is no such '. $place. ' template', $place );

				$frames[$place] = $frame;
			}

			$model		= [ 'file' => $file, 'name' => $name ] + Reader::wrapDefaults() + [ 'header' => $frames['header'], 'footer' => $frames['footer'], 'blocks' => [] ];
			$source		= Writer::write( $model, self::schemas( $appData ) );
			$exists		= false;

			$written = \Nino\Filesystem::mutate( $appData, self::TEMPLATES. '/'. $file. '.tpl', static function( mixed $current ) use ( $source, &$exists ): ?string {

				if( (string) $current !== '' ) {
					$exists = true;
					return null;
				}

				return $source;
			}, '' );

			if( $written === false )
				return $exists === true ? self::_error( 409, 'a page template of this name is there already', 'exists' ) : self::_error( 500, 'could not write the page template', 'write' );

			return [ 'status' => 200, 'model' => $model, 'hash' => hash( 'sha256', $source ) ];
		}

		/**
		 *	A page template copied under a new name: the file, with every
		 *	/template/<file>/ in it - the sources of the sections and the blocks
		 *	of html alike - made /template/<copy>/ and the name of the template
		 *	the new one; every text key under /template/<file>/ made again under
		 *	/template/<copy>/ with the values of every language, and every image
		 *	slot made again with its label, its size, its alt texts and a copy of
		 *	its picture, so that taking one of the two away leaves the other
		 *	as it is. The file is read as text and written as text: nothing the
		 *	Reader does not read is touched, and what it does read stays what it
		 *	was - the wrap included.
		 *
		 *	It is made like a save: what is refused is refused before the first
		 *	change - a name, a file that is there, a key or a slot of the copy
		 *	that is there, the permissions of the panels that own them - and what
		 *	is made then is made in an order that can be undone. A key is made
		 *	with the Text Keys' own actions, a slot with the Slots tab's, and what
		 *	one of them could not make takes everything made so far away again.
		 *	What was made is what the panels answered 200 for: a key or a slot that
		 *	another request made in the meantime - the panel answers 409 - is none
		 *	of the copy's, it stops the copy and is left as it is.
		 *	A key under /template/<file>/ that is no key of the grammar is not
		 *	copied: no panel could make it
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$file					page-home, the template to copy
		 *	@param		string		$name					What the copy is called; its file is page-<the name as a slug>
		 *
		 *	@return 	array										[ 'status' => 200, 'model', 'hash' ] of the copy; 400 for a name or a file that is none,
		 *																	403 for a missing permission, 404 for a template that is not there, 409 for a copy
		 *																	that is there ('exists') or keys or slots of it that are ('key-exists', with 'keys' and 'slots':
		 *																	the bare uris in the way), or what a panel that makes a key or a slot answered
		 */
		public static function duplicate( array &$appData, string $file, string $name ): array {

			if( self::validFile( $file ) === false )
				return self::_error( 400, 'a page template is called page- and a slug of lower case letters, digits and hyphens', 'file' );

			$source = \Nino\Filesystem::getFileContent( $appData, self::TEMPLATES. '/'. $file. '.tpl', false );

			if( is_string( $source ) === false )
				return self::_error( 404, 'there is no such page template', 'missing' );

			$name = trim( $name );
			$slug = self::_validName( $name ) === true ? \Nino\Feature::slug( $name ) : '';
			$copy = 'page-'. $slug;

			if( $slug === '' || self::validFile( $copy ) === false )
				return self::_error( 400, 'a name is one line of 1 to 160 characters without <, > or brackets, and has letters or digits to make a file name from', 'name' );

			$path = self::TEMPLATES. '/'. $copy. '.tpl';

			if( \Nino\Filesystem::fileExists( $appData, $path ) === true )
				return self::_error( 409, 'a page template of this name is there already', 'exists' );

			$from = '/template/'. $file. '/';
			$to		= '/template/'. $copy. '/';

			[ $failure, $plan ] = self::_planCopy( $appData, $from, $to );

			if( $failure !== null )
				return $failure;

			$made		= [ 'keys' => [], 'slots' => [] ];
			$failure	= self::_makeCopy( $appData, $plan, $made );

			if( $failure !== null ) {
				self::_unmake( $appData, $made );
				return $failure;
			}

			$text = str_replace( $from, $to, $source );
			$text = (string) preg_replace_callback( '~\A(\s*<!--[\t ]*nino:template-name[\t ]+)[^\r\n<>]+?([\t ]*-->)~', static fn( array $match ): string => $match[1]. $name. $match[2], $text, 1, $named );

			// A file without a name line is given one, first, where the Reader looks for it
			if( $named === 0 )
				$text = '<!-- nino:template-name '. $name. " -->\n". $text;

			$exists		= false;
			$written	= \Nino\Filesystem::mutate( $appData, $path, static function( mixed $current ) use ( $text, &$exists ): ?string {

				if( (string) $current !== '' ) {
					$exists = true;
					return null;
				}

				return $text;
			}, '' );

			if( $written === false ) {

				self::_unmake( $appData, $made );

				return $exists === true ? self::_error( 409, 'a page template of this name is there already', 'exists' ) : self::_error( 500, 'could not write the page template', 'write' );
			}

			$model = Reader::read( $text, self::schemas( $appData ) );
			$model['file'] = $copy;

			return [ 'status' => 200, 'model' => $model, 'hash' => hash( 'sha256', $text ) ];
		}

		/**
		 *	What a copy is going to make, and whether it may: the keys and the
		 *	slots under the old file's prefix, each with the name it gets under
		 *	the new one, checked - none of them is there already, the
		 *	permission of each panel - before anything is made
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$from					/template/page-home/
		 *	@param		string		$to						/template/page-copy/
		 *
		 *	@return 	array										[ the refusal or null, the plan: 'keys' (new key => the entry it is copied from) and
		 *																	'slots' (new uri => [ the slot it is copied from, its old uri ]) ]. What is there already is a 409
		 *																	('key-exists') with 'keys' and 'slots' besides the usual: the bare uris of the copy that are in the
		 *																	way, for the panel to name in its own words
		 */
		private static function _planCopy( array &$appData, string $from, string $to ): array {

			$plan			= [ 'keys' => [], 'slots' => [] ];
			$inTheWay	= [ 'keys' => [], 'slots' => [] ];
			$known		= array_column( \Nino\Text::entries( $appData ), null, 'key' );

			foreach( $known as $key => $entry ) {

				if( str_starts_with( (string) $key, $from ) === false )
					continue;

				$new = $to. substr( (string) $key, strlen( $from ) );

				if( \Nino\Text::isGrammarKey( $new ) === false )
					continue;

				if( isset( $known[$new] ) === true )
					$inTheWay['keys'][] = $new;
				else
					$plan['keys'][$new] = $entry;
			}

			foreach( \Nino\Images::getSlots( $appData ) as $uri => $slot ) {

				if( str_starts_with( (string) $uri, $from ) === false || is_array( $slot ) === false )
					continue;

				$new = $to. substr( (string) $uri, strlen( $from ) );

				if( \Nino\Images::getSlot( $appData, $new ) !== false )
					$inTheWay['slots'][] = $new;
				else
					$plan['slots'][$new] = [ $slot, (string) $uri ];
			}

			if( $inTheWay['keys'] !== [] || $inTheWay['slots'] !== [] )
				return [ self::_error( 409, 'a key or a slot of the copy is there already', 'key-exists' ) + $inTheWay, $plan ];

			$permissions = [];

			if( $plan['keys'] !== [] )
				$permissions[] = \Nino\Modules\Text\Keys::MANAGE_PERM;

			if( $plan['slots'] !== [] )
				$permissions[] = \Nino\Modules\Images\Slots::MANAGE_PERM;

			foreach( $permissions as $permission )
				if( \Nino\Auth::checkPermission( $appData, $permission ) === false )
					return [ self::_error( 403, 'a copy makes keys or slots, which asks for the permission '. $permission, 'permission', [ $permission ] ), $plan ];

			return [ null, $plan ];
		}

		/**
		 *	Make what the plan makes: the keys, then the slots. What is made is
		 *	noted in $made once the panel has answered 200 for it, so that a key
		 *	or a slot that stopped half way is taken away with the rest - and one
		 *	that another request made in the meantime (409) is not noted, and so
		 *	not taken away: it stops the copy and stays
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			$plan					See _planCopy()
		 *	@param		array 		&$made				(reference) [ 'keys' => [ key, ... ], 'slots' => [ uri, ... ] ]
		 *
		 *	@return 	array|null								The answer that stopped it, null where all of it was made
		 */
		private static function _makeCopy( array &$appData, array $plan, array &$made ): ?array {

			foreach( $plan['keys'] as $key => $entry ) {

				$failure = self::_copyKey( $appData, (string) $key, $entry, $made['keys'] );

				if( $failure !== null )
					return $failure;
			}

			foreach( $plan['slots'] as $uri => [ $slot, $old ] ) {

				$last			= (string) substr( (string) $uri, (int) strrpos( (string) $uri, '/' ) + 1 );
				$label		= trim( (string) ( $slot['label'] ?? '' ) );

				// A slot that is there already is another request's: no alt text and no picture is written into it
				$failure	= self::_slot( $appData, (string) $uri, $label !== '' ? $label : ucfirst( $last ), (int) ( $slot['width'] ?? 0 ), (int) ( $slot['height'] ?? 0 ), $made['slots'], false );

				if( $failure !== null )
					return $failure;

				$failure	= self::_copyPicture( $appData, (string) $uri, $old, $slot );

				if( $failure !== null )
					return $failure;
			}

			return null;
		}

		/**
		 *	The alt texts and the picture of a slot handed on to the slot that is a copy of it, made a moment ago and
		 *	holding none: the alt texts of the languages the site has, and the file stored again under the name of the
		 *	new slot - through the Images module's own functions, which know how a slot's file is kept - and named
		 *	by it. A copy of a template makes it for each of its slots, and a save for the slot of a picture that was
		 *	copied with the column or the card it stands in
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$uri					The new slot, which is there
		 *	@param		string		$old					The slot it is a copy of
		 *	@param		array			$slot					The old slot as \Nino\Images::getSlot() has it
		 *
		 *	@return 	array|null								The answer that stopped it, null where all of it was handed on - or there was none of it
		 */
		private static function _copyPicture( array &$appData, string $uri, string $old, array $slot ): ?array {

			// The alt text of a language the site no longer has is left behind
			$alt = is_array( $slot['alt'] ?? null ) === true ? array_intersect_key( $slot['alt'], array_flip( \Nino\Locales::getAvailableLocales( $appData ) ) ) : [];

			if( $alt !== [] && \Nino\Images::setSlotAlt( $appData, $uri, $alt ) === false )
				return self::_error( 500, 'the alt texts of the slot could not be copied', 'slot', [ $uri ] );

			$filename = $slot['filename'] ?? null;

			if( is_string( $filename ) === false || $filename === '' )
				return null;

			$bytes = \Nino\Images::read( $appData, $filename );

			// A slot that names a picture that is not there has none to hand on
			if( is_string( $bytes ) === false )
				return null;

			$base = ltrim( $old, '/' );
			$copy = ltrim( $uri, '/' ). ( str_starts_with( $filename, $base ) === true ? substr( $filename, strlen( $base ) ) : '.'. basename( $filename ) );

			if( \Nino\Images::restore( $appData, $copy, $bytes ) === false )
				return self::_error( 500, 'the image of the slot could not be copied', 'slot', [ $uri ] );

			if( \Nino\Images::setSlotFilename( $appData, $uri, $copy ) === false ) {
				\Nino\Images::delete( $appData, $copy );
				return self::_error( 500, 'the image of the slot could not be copied', 'slot', [ $uri ] );
			}

			return null;
		}

		/**
		 *	One key made again under another name, through the Text Keys' own
		 *	actions: made in the format of the original with the text of each
		 *	language as the original has it - the action takes them as one list,
		 *	and a key of every language made with one text would hold it in the
		 *	language the person works in alone -, its limit and its place on the
		 *	blacklist kept - and read again, every value as the original has it,
		 *	because the panel makes the key without telling whether every language
		 *	file took it
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$key					The new key
		 *	@param		array			$entry				The key it is copied from, as \Nino\Text::entries() has it
		 *	@param		array 		&$made				(reference) Where the key is added once keys/create answered 200 - not for a 409, which is another request's key
		 *
		 *	@return 	array|null
		 */
		private static function _copyKey( array &$appData, string $key, array $entry, array &$made ): ?array {

			$values	= array_filter( (array) $entry['values'], 'is_string' );

			// A key of every language is made with the text of each, as the original has them: a key made with one text holds it in the language the person works in and nothing in the others
			[ $status, $body ] = self::_api( $appData, 'keys/create', [
				'key'			=> $key,
				'global'	=> $entry['global'] === true,
				'format'	=> (string) $entry['format'],
			] + ( $entry['global'] === true ? [ 'value' => $values === [] ? '' : (string) reset( $values ) ] : [ 'values' => $values ] ) );

			if( $status !== 200 )
				return self::_error( $status, (string) ( $body['error'] ?? 'the key could not be created' ), 'key', [ $key ] );

			// From here on the key is the copy's, and goes with it where anything below stops
			$made[] = $key;

			// The key is made in the original's format, so that no value loses
			// its markup on the way in; a format nobody had chosen is forgotten
			// again, it follows the values as the original's does
			if( $entry['formatSet'] === false || $entry['maxlengthSet'] === true || $entry['blacklisted'] === true ) {

				[ $status, $body ] = self::_api( $appData, 'keys/save', [ 'key' => $key, 'global' => $entry['global'] === true, 'blacklisted' => $entry['blacklisted'] === true ]
					+ ( $entry['formatSet'] === false ? [ 'format' => 'auto' ] : [] )
					+ ( $entry['maxlengthSet'] === true ? [ 'maxlength' => (int) $entry['maxlength'] ] : [] ) );

				if( $status !== 200 )
					return self::_error( $status, (string) ( $body['error'] ?? 'the key could not be saved' ), 'key', [ $key ] );
			}

			$stored = \Nino\Text::entry( $appData, $key );

			foreach( $values as $locale => $value )
				if( $stored === null || ( $stored['values'][$locale] ?? null ) !== \Nino\Text::sanitizeValue( $value, (string) $entry['format'] ) )
					return self::_error( 500, 'the key could not be written in every language', 'key', [ $key ] );

			return null;
		}

		/**
		 *	Take away what a copy made - the slots first, with the pictures made
		 *	for them, then the keys - by the same actions that made them
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			$made					As _makeCopy() noted it
		 *
		 *	@return 	void
		 */
		private static function _unmake( array &$appData, array $made ): void {

			foreach( array_reverse( $made['slots'] ) as $uri )
				self::_api( $appData, 'slots/delete', [ 'uri' => $uri ] );

			foreach( array_reverse( $made['keys'] ) as $key )
				self::_api( $appData, 'keys/delete', [ 'key' => $key ] );
		}

		/**
		 *	Delete a page template - not one that a route renders. Its text
		 *	keys and its slots stay: the scan of the Text panel finds them
		 *	as orphans
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$file					page-home
		 *
		 *	@return 	array										[ 'status' => 200, 'file' ]; 400, 404, 409 with the routes in 'usedBy'
		 */
		public static function delete( array &$appData, string $file ): array {

			if( self::validFile( $file ) === false )
				return self::_error( 400, 'a page template is called page- and a slug of lower case letters, digits and hyphens', 'file' );

			if( \Nino\Filesystem::fileExists( $appData, self::TEMPLATES. '/'. $file. '.tpl' ) === false )
				return self::_error( 404, 'there is no such page template', 'missing' );

			$usedBy = self::usedBy( $appData, $file );

			if( $usedBy !== [] )
				return self::_error( 409, 'a route renders this page template', 'in-use' ) + [ 'usedBy' => $usedBy ];

			$path = \Nino\Filesystem::path( $appData, self::TEMPLATES. '/'. $file. '.tpl' );

			if( @unlink( $path ) === false )
				return self::_error( 500, 'could not delete the page template', 'write' );

			clearstatcache( true, $path );

			return [ 'status' => 200, 'file' => $file ];
		}

		/**
		 *	What is wrong with a model that the Writer could not say with a
		 *	file: the frames and the name, every section that does not read
		 *	back as itself, and a source that means nothing where it stands
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			$model
		 *	@param		array			$schemas			As schemas() answers
		 *
		 *	@return 	array										Sentences, in English; [] for a model that can be written
		 */
		public static function problems( array &$appData, array $model, array $schemas ): array {

			$problems = [];
			$name = trim( (string) ( $model['name'] ?? '' ) );

			if( $name !== '' && self::_validName( $name ) === false )
				$problems[] = 'the name is one line of 1 to 160 characters without <, > or brackets';

			foreach( [ 'header', 'footer' ] as $place ) {

				$frame = (string) ( $model[$place] ?? '' );

				if( $frame !== '' && ( preg_match( self::FRAME, $frame ) !== 1 || str_starts_with( $frame, 'html-'. $place ) === false ) )
					$problems[] = 'the '. $place. ' is no html-'. $place. ' template';
			}

			// The Writer refuses these, so nothing is written to be read again
			$values = self::_values( $model, $schemas );

			if( $values !== [] )
				return array_merge( $problems, $values );

			$again	= Reader::read( Writer::write( $model, $schemas ), $schemas );
			$found	= [];
			$seen		= [];

			// A class of the wrap that is none of the wrap's own - one of the known ones twice, one that cannot be kept - would read back as another
			$wrap = static fn( array $of ): array => [ (bool) ( $of['animate'] ?? false ), (string) ( $of['vpa'] ?? '' ), (string) ( $of['vpaSpeed'] ?? '' ), array_values( array_unique( preg_split( '/\s+/', trim( (string) ( $of['wrapClass'] ?? '' ) ), -1, PREG_SPLIT_NO_EMPTY ) ?: [] ) ) ];

			if( $wrap( $model ) !== $wrap( $again ) )
				$problems[] = 'the classes of the wrap do not read back as they were written: one of them is the wrap\'s own or has characters the builder does not keep';

			foreach( $again['blocks'] as $block )
				if( $block['kind'] === 'section' )
					$found[$block['id']] = $block;

			foreach( (array) ( $model['blocks'] ?? [] ) as $index => $block ) {

				if( ( $block['kind'] ?? '' ) !== 'section' ) {

					if( ( $block['kind'] ?? '' ) !== 'html' || is_string( $block['source'] ?? null ) === false || trim( $block['source'] ) === '' )
						$problems[] = 'block '. ( $index + 1 ). ' is neither a section nor a block of html';

					continue;
				}

				$id = (string) ( $block['id'] ?? '' );

				if( isset( $seen[$id] ) === true ) {
					$problems[] = 'the id "'. $id. '" is the id of two sections';
					continue;
				}

				$seen[$id] = true;

				if( isset( $found[$id] ) === false ) {

					$reason = 'it does not read back';

					foreach( $again['blocks'] as $other )
						if( $other['kind'] === 'html' && is_array( $other['reason'] ) === true && str_contains( $other['source'], 'id="'. $id. '"' ) === true )
							$reason = 'line '. $other['reason']['line']. ': '. $other['reason']['text'];

					$problems[] = 'the section "'. $id. '" is not valid: '. $reason;

					continue;
				}

				if( self::_readsBack( $block, $found[$id] ) === false ) {
					$problems[] = 'the section "'. $id. '" does not read back as it was written: its columns, its loops or its components are not what the grammar makes of them';
					continue;
				}

				foreach( self::_sources( $appData, $block, $schemas ) as $problem )
					$problems[] = 'the section "'. $id. '": '. $problem;
			}

			// A foreign block that holds the end of its own marker would end
			// where nobody put it
			foreach( (array) ( $model['blocks'] ?? [] ) as $index => $block )
				if( ( $block['kind'] ?? '' ) === 'html' && is_array( $block['reason'] ?? null ) === false && preg_match( '~<!--[\t ]*/nino:html[\t ]*-->~', (string) ( $block['source'] ?? '' ) ) === 1 )
					$problems[] = 'block '. ( $index + 1 ). ' contains the end marker of a block of html';

			return $problems;
		}

		/**
		 *	The values of a model that no call can carry: a quote, a bracket
		 *	or a line break in a fixed text, a source, an attribute (the class
		 *	and the href among them) or a custom class - the classes of the wrap
		 *	among them - and an effect or a speed of the wrap that Nino.css has
		 *	none of, which would be a class of nothing. The content of a
		 *	component is no attribute and is not looked at. The address of a link is the one
		 *	value that may hold brackets, the fill of a page of the site with a sub-path
		 *	after it (see Reader::address())
		 *
		 *	@param		array			$model
		 *	@param		array			$schemas					The registry
		 *
		 *	@return 	array										Sentences naming the block, the component and the attribute
		 */
		private static function _values( array $model, array $schemas ): array {

			$problems	= [];
			// The address of a link may hold the brackets of the fill of a page of the site, with a sub-path after it (see Reader::address()), and nothing else may
			$unsafe		= static fn( mixed $value, bool $link = false ): bool => is_scalar( $value ) === true && ( $link === false || is_string( $value ) === false || Reader::address( $value ) === null )
				&& preg_match( self::UNSAFE, is_bool( $value ) === true ? '' : (string) $value ) === 1;

			if( in_array( (string) ( $model['vpa'] ?? '' ), array_merge( [ '' ], Reader::EFFECTS ), true ) === false )
				$problems[] = 'the effect of the animation of the template is none that Nino.css has';

			if( in_array( (string) ( $model['vpaSpeed'] ?? '' ), array_merge( [ '' ], Reader::SPEEDS ), true ) === false )
				$problems[] = 'the speed of the animation of the template is none that Nino.css has';

			if( $unsafe( $model['wrapClass'] ?? null ) === true )
				$problems[] = 'the custom classes of the wrap hold a quote, a bracket or a line break, which a call cannot carry';

			foreach( (array) ( $model['blocks'] ?? [] ) as $index => $block ) {

				if( is_array( $block ) === false || ( $block['kind'] ?? '' ) !== 'section' )
					continue;

				$at = 'the section "'. (string) ( $block['id'] ?? '' ). '" (block '. ( (int) $index + 1 ). ')';

				foreach( [ 'custom' => 'the custom classes', 'rowCustom' => 'the custom classes of the row' ] as $key => $what )
					if( $unsafe( $block['settings'][$key] ?? null ) === true )
						$problems[] = $at. ': '. $what. ' hold a quote, a bracket or a line break, which a call cannot carry';

				// The loop of a section is a call as the loop of a column is
				foreach( is_array( $block['stack'] ?? null ) === true ? [ $block['stack'] ] : [] as $call ) {

					$values = [ 'source' => $call['source'] ?? null ] + (array) ( $call['attributes'] ?? [] );

					// The text of a loop holds the fills of the element, and no other bracket
					if( is_string( $values[Reader::STACK_TEXT] ?? null ) === true && Reader::lineText( $values[Reader::STACK_TEXT] ) === true )
						$values[Reader::STACK_TEXT] = null;

					foreach( $values as $attribute => $value )
						if( $unsafe( $value ) === true )
							$problems[] = $at. ': the attribute "'. $attribute. '" of the loop "'. (string) ( $call['name'] ?? '' ). '" holds a quote, a bracket or a line break, which a call cannot carry';
				}

				foreach( (array) ( $block['cols'] ?? [] ) as $c => $col ) {

					if( is_array( $col ) === false )
						continue;

					if( $unsafe( $col['custom'] ?? null ) === true )
						$problems[] = $at. ': the custom classes of column '. ( (int) $c + 1 ). ' hold a quote, a bracket or a line break, which a call cannot carry';

					$calls = self::_flat( (array) ( $col['components'] ?? [] ) );

					if( is_array( $col['stack'] ?? null ) === true )
						$calls[] = $col['stack'];

					foreach( $calls as $call ) {

						if( is_array( $call ) === false )
							continue;

						$schema = (array) ( $schemas['components'][$call['name'] ?? ''] ?? $schemas['stacks'][$call['name'] ?? ''] ?? [] );
						$values = [ 'source' => $call['source'] ?? null, 'text' => $call['text'] ?? null ] + (array) ( $call['attributes'] ?? [] );

						// A loop has no fixed text but its attribute of that name, which holds the fills of the element, and no other bracket
						if( isset( $schemas['stacks'][$call['name'] ?? ''] ) === true ) {
							$values[Reader::STACK_TEXT] = Reader::lineText( (string) ( $call['attributes'][Reader::STACK_TEXT] ?? '' ) ) === true ? null : $call['attributes'][Reader::STACK_TEXT];
						}

						// The text of a line holds the fills of the element, and no other bracket
						if( Reader::fielded( $schema ) === true && is_string( $values['text'] ) === true && Reader::lineText( $values['text'] ) === true )
							$values['text'] = null;

						foreach( $values as $attribute => $value )
							if( $unsafe( $value, $attribute === 'source' ? ( $schema['source'] ?? '' ) === 'href' : in_array( (string) $attribute, Reader::addresses( $schema ), true ) === true ) === true )
								$problems[] = $at. ': the '. ( $attribute === 'text' && Reader::fielded( $schema ) === true ? 'text' : 'attribute "'. $attribute. '"' ). ' of the component "'. (string) ( $call['name'] ?? '' ). '" holds '. ( $attribute === 'text' && Reader::fielded( $schema ) === true ? 'a line break or a [[ that opens no field' : 'a quote, a bracket or a line break' ). ', which a call cannot carry';
					}
				}
			}

			return $problems;
		}

		/**
		 *	What a section's sources mean where they stand: in a static
		 *	column a text key or nothing, in a stack a key, .id, .uri, .slug or
		 *	a field of the type, the fills in the sub-path of a link the ones
		 *	_fills() lists, and a stack's type has to be one the Elements
		 *	panel knows. A key or a slot that is new has to be one that can
		 *	be made
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			$section
		 *	@param		array			$schemas
		 *
		 *	@return 	array										Sentences
		 */
		private static function _sources( array &$appData, array $section, array $schemas ): array {

			$problems = [];

			if( is_array( $section['background'] ?? null ) === true && preg_match( self::SLOT, (string) ( $section['background']['slot'] ?? '' ) ) !== 1 )
				$problems[] = 'the background is not an image slot';

			// The cells of a loop of sections are its columns: the fields they name are the type's, as in a loop of a column
			$cell = is_array( $section['stack'] ?? null ) === true ? self::_fields( $appData, $section['stack'], $problems ) : null;

			if( is_array( $section['stack'] ?? null ) === true )
				array_push( $problems, ...self::_loopValues( $section['stack'], $schemas, $cell ) );

			foreach( (array) ( $section['cols'] ?? [] ) as $c => $col ) {

				$fields = $cell;

				// The picture behind a column is a slot, or - in a loop of sections, whose cell the column is - an image field of the element
				if( is_array( $col['background'] ?? null ) === true ) {

					$field = (string) ( $col['background']['field'] ?? '' );

					if( $field === '' ) {

						if( preg_match( self::SLOT, (string) ( $col['background']['slot'] ?? '' ) ) !== 1 )
							$problems[] = 'the background of column '. ( (int) $c + 1 ). ' is not an image slot';
					}
					elseif( $cell === null )
						$problems[] = 'the background "'. $field. '" of column '. ( (int) $c + 1 ). ' means nothing in a column without a loop: it is an image slot';
					elseif( ( $cell[$field]['type'] ?? '' ) !== 'image' )
						$problems[] = 'the background "'. $field. '" of column '. ( (int) $c + 1 ). ' is no image field of the loop\'s type';
				}

				if( is_array( $col['stack'] ?? null ) === true ) {
					$fields = self::_fields( $appData, $col['stack'], $problems );
					array_push( $problems, ...self::_loopValues( $col['stack'], $schemas, $fields ) );
				}

				$cells = self::_flat( (array) ( $col['components'] ?? [] ) );

				foreach( $cells as $component ) {

					$called = (array) ( $schemas['components'][$component['name'] ?? ''] ?? [] );

					// What a column shows is a field of the type, and its title a text key and never a field. That it shows a field or a line of text, one of the two, that
					// it stands in a table and that a table has one are the Reader's to say: a section that does not read back as it is written is no section
					if( Reader::columnar( $called ) === true ) {

						$field = (string) ( $component['attributes']['field'] ?? '' );

						if( $field !== '' && $fields !== null && in_array( $field, [ '.id', '.uri', '.slug' ], true ) === false && isset( $fields[$field] ) === false )
							$problems[] = 'the field "'. $field. '" of a column of column '. ( (int) $c + 1 ). ' is no field of the loop\'s type';

						if( (string) ( $component['source'] ?? '' ) !== '' && $component['source'][0] !== '/' )
							$problems[] = 'the title of a column of column '. ( (int) $c + 1 ). ' is a text key, not "'. $component['source']. '"';
					}

					// The fills of a line of text are fields of the element, and only there
					if( Reader::fielded( $called ) === true && is_string( $component['text'] ?? null ) === true )
						foreach( Reader::lineFills( $component['text'] ) as $fill )
							if( $fill[0] === '/' )
								continue;
							elseif( $fields === null )
								$problems[] = 'the fill [['. $fill. ']] of '. ( $component['name'] ?? '' ). ' means nothing in a column without a loop';
							elseif( ( $fields[$fill]['type'] ?? '' ) === 'image' )
								$problems[] = 'the fill [['. $fill. ']] of '. ( $component['name'] ?? '' ). ' is an image field, which a line cannot print';
							elseif( in_array( $fill, [ '.id', '.uri', '.slug' ], true ) === false && isset( $fields[$fill] ) === false )
								$problems[] = 'the fill [['. $fill. ']] of '. ( $component['name'] ?? '' ). ' is no field of the loop\'s type';
				}

				foreach( $cells as $component ) {

					$kind		= (string) ( $schemas['components'][$component['name'] ?? '']['source'] ?? 'text' );
					$source	= (string) ( $component['source'] ?? '' );

					// The picture an image attribute names stands where the source of a picture does: a slot, or in a loop a field of the type
					foreach( self::_pictures( $schemas, (string) ( $component['name'] ?? '' ) ) as $attribute ) {

						$picture = (string) ( $component['attributes'][$attribute] ?? '' );

						if( $picture === '' || ( $picture[0] === '/' ? preg_match( self::SLOT, $picture ) === 1 : ( $fields !== null && isset( $fields[$picture] ) === true ) ) )
							continue;

						$problems[] = 'the picture "'. $picture. '" of '. ( $component['name'] ?? '' ). ( $picture[0] === '/' ? ' is no image slot' : ( $fields === null ? ' means nothing in a column without a loop: it is an image slot' : ' is no field of the loop\'s type' ) );
					}

					// A link that goes on from a page of the site holds the fills of the element only in a loop, and only the ones the dialog of the Builder offers there
					$addressed = [ 'the source' => $kind === 'href' ? $source : '' ];

					foreach( Reader::addresses( (array) ( $schemas['components'][$component['name'] ?? ''] ?? [] ) ) as $attribute )
						$addressed['the attribute "'. $attribute. '"'] = (string) ( $component['attributes'][$attribute] ?? '' );

					foreach( $addressed as $what => $address ) {

						preg_match_all( '~\[\[([^\[\]]+)\]\]~', (string) ( Reader::address( $address )['postfix'] ?? '' ), $found );

						foreach( array_unique( $found[1] ) as $fill )
							if( $fields === null || in_array( $fill, self::_fills( $fields ), true ) === false )
								$problems[] = 'the sub-path of '. $what. ' of '. ( $component['name'] ?? '' ). ' holds the fill [['. $fill. ']], which '. ( $fields === null ? 'means nothing in a column without a loop' : ( isset( $fields[$fill] ) === true ? 'is a field of the loop\'s type that is no line of text or number' : 'is no field of the loop\'s type' ) );
					}

					if( $source === '' || in_array( $kind, [ 'none', 'content' ], true ) === true )
						continue;

					if( $source[0] === '/' )
						$valid = $kind === 'image' ? preg_match( self::SLOT, $source ) === 1 : \Nino\Text::isGrammarKey( $source ) === true || str_starts_with( $source, '/_nino/' ) === true || \Nino\Text::entry( $appData, $source ) !== null;
					elseif( $fields === null )
						$valid = $kind === 'href';
					else
						$valid = in_array( $source, [ '.id', '.uri', '.slug' ], true ) === true || isset( $fields[$source] ) === true || $kind === 'href';

					if( $valid === false )
						$problems[] = 'the source "'. $source. '" of '. ( $component['name'] ?? '' ). ( $fields === null ? ' means nothing in a column without a loop: it is a text key or an image slot' : ' is no field of the loop\'s type' );
				}
			}

			return $problems;
		}

		/**
		 *	What the attributes of a loop mean against the type it runs over: an attribute of the type field is a field of the type that holds a text or a number - no
		 *	picture -, and the text of a loop has the fields of the type for its fills (a site text and the number, the uri and the slug of the element as well)
		 *
		 *	@param		array			$stack				The loop of the model: name, source, attributes
		 *	@param		array			$schemas
		 *	@param		array|null	$fields			The fields of the type, null where the type is none (told already)
		 *
		 *	@return 	array										Sentences
		 */
		private static function _loopValues( array $stack, array $schemas, ?array $fields ): array {

			$problems	= [];
			$name			= (string) ( $stack['name'] ?? '' );

			if( $fields === null )
				return $problems;

			foreach( (array) ( $schemas['stacks'][$name]['attributes'] ?? [] ) as $attribute => $declared ) {

				$value = (string) ( $stack['attributes'][$attribute] ?? '' );

				if( $value === '' || is_array( $declared ) === false )
					continue;

				if( ( $declared['type'] ?? '' ) === 'field' && ( isset( $fields[$value] ) === false || ( $fields[$value]['type'] ?? '' ) === 'image' ) )
					$problems[] = 'the attribute "'. $attribute. '" of the loop "'. $name. '" is "'. $value. '", which is no field of the loop\'s type that a loop can print';

				if( $attribute === Reader::STACK_TEXT && ( $declared['type'] ?? '' ) === 'string' )
					foreach( Reader::lineFills( $value ) as $fill )
						if( $fill[0] === '/' || in_array( $fill, [ '.id', '.uri', '.slug' ], true ) === true )
							continue;
						elseif( ( $fields[$fill]['type'] ?? '' ) === 'image' )
							$problems[] = 'the fill [['. $fill. ']] of the loop "'. $name. '" is an image field, which a line cannot print';
						elseif( isset( $fields[$fill] ) === false )
							$problems[] = 'the fill [['. $fill. ']] of the loop "'. $name. '" is no field of the loop\'s type';
			}

			return $problems;
		}

		/**
		 *	The fills of the element of a loop that the sub-path of a link may hold, exactly the ones the dialog of the Builder lists (_fillsOf() in admin.js): the slug of
		 *	the element - the last segment of its uri - its uri and its number in the loop, then the fields of the type that are a line of text or a number, not the ones that
		 *	hold markup - no picture, no body, no date, no list of tags. A name an address cannot hold is none the Reader reads as a fill, so that it never gets here
		 *
		 *	@param		array			$fields				The model of the type the loop runs over
		 *
		 *	@return 	array										The names without brackets: [ '.slug', '.uri', '.id', 'title', ... ]
		 */
		private static function _fills( array $fields ): array {

			$names = array_filter( array_map( 'strval', array_keys( $fields ) ), static fn( string $name ): bool => is_array( $fields[$name] ?? null ) === true
				&& in_array( $fields[$name]['type'] ?? '', [ 'string', 'integer', 'double' ], true ) === true
				&& ( $fields[$name]['html'] ?? false ) !== true );

			return array_merge( [ '.slug', '.uri', '.id' ], array_values( $names ) );
		}

		/**
		 *	The fields of the element type a loop runs over - the model of its file in /elements
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			$stack				The loop of the model: name, source, attributes
		 *	@param		array 		&$problems		(reference) A sentence is added where the type is none the Elements panel has
		 *
		 *	@return 	array|null								The fields, null where the type is none
		 */
		private static function _fields( array &$appData, array $stack, array &$problems ): ?array {

			$type = ltrim( (string) ( $stack['source'] ?? '' ), '/' );

			if( preg_match( self::TYPE, $type ) !== 1 || in_array( $type, self::_types( $appData ), true ) === false ) {
				$problems[] = 'the loop runs over "'. $type. '", which is no element type';
				return null;
			}

			return (array) ( \Nino\Filesystem::getFileContent( $appData, '/elements/'. $type. '.php', [] )['model'] ?? [] );
		}

		/**
		 *	Blocks that were edited read as a section again, where they can
		 *
		 *	@param		array			$model
		 *	@param		array			$schemas
		 *
		 *	@return 	array										The model, a block that reads now a section
		 */
		private static function _again( array $model, array $schemas ): array {

			$taken = [];

			foreach( (array) ( $model['blocks'] ?? [] ) as $block )
				if( ( $block['kind'] ?? '' ) === 'section' )
					$taken[(string) ( $block['id'] ?? '' )] = true;

			foreach( (array) ( $model['blocks'] ?? [] ) as $index => $block ) {

				if( ( $block['kind'] ?? '' ) !== 'html' || ( $block['edited'] ?? false ) !== true )
					continue;

				[ $section, $reason ] = Reader::readSection( (string) ( $block['source'] ?? '' ), $schemas, $taken );

				if( $section !== null ) {
					$taken[$section['id']] = true;
					$model['blocks'][$index] = $section;
					continue;
				}

				// A block in its markers stays in them; one that was read as a
				// section is told why it still is none
				$model['blocks'][$index] = [ 'kind' => 'html', 'source' => $block['source'], 'reason' => is_array( $block['reason'] ?? null ) === true ? $reason : null ];
			}

			$model['blocks'] = array_values( (array) ( $model['blocks'] ?? [] ) );

			return $model;
		}

		/**
		 *	The keys of a section that has a new id: every source that reads
		 *	/template/<file>/<old id>/... is written /template/<file>/<new id>/...
		 *
		 *	@param		array 		&$model				The model, its sources rewritten
		 *	@param		string		$file
		 *	@param		array			$schemas			As schemas() answers
		 *
		 *	@return 	array										[ [ old, new, 'key'|'slot' ], ... ] - what the stores have to follow
		 */
		private static function _rename( array &$model, string $file, array $schemas ): array {

			$moves = [];

			foreach( (array) ( $model['blocks'] ?? [] ) as $index => $block ) {

				if( ( $block['kind'] ?? '' ) !== 'section' )
					continue;

				$old = (string) ( $block['renamedFrom'] ?? '' );

				// Said and not meant: it is no change, and the section is not different for it
				if( $old === '' || $old === ( $block['id'] ?? '' ) ) {
					unset( $model['blocks'][$index]['renamedFrom'] );
					continue;
				}

				$from		= '/template/'. $file. '/'. $old. '/';
				$to			= '/template/'. $file. '/'. $block['id']. '/';
				$move		= static function( string $source, string $kind ) use ( $from, $to, &$moves ): string {

					if( str_starts_with( $source, $from ) === false )
						return $source;

					$moves[] = [ $source, $to. substr( $source, strlen( $from ) ), $kind ];

					return $to. substr( $source, strlen( $from ) );
				};

				if( is_array( $block['background'] ?? null ) === true )
					$model['blocks'][$index]['background']['slot'] = $move( (string) $block['background']['slot'], 'slot' );

				foreach( (array) ( $block['cols'] ?? [] ) as $c => $col )
					if( is_array( $col['background'] ?? null ) === true && (string) ( $col['background']['field'] ?? '' ) === '' )
						$model['blocks'][$index]['cols'][$c]['background']['slot'] = $move( (string) $col['background']['slot'], 'slot' );

				// A source follows the section, and so does the picture an image attribute names - in the children of a component that holds components as well
				$follow = static function( array &$components ) use ( &$follow, $move, $schemas ): void {

					foreach( $components as $k => $component ) {

						$name = (string) ( $component['name'] ?? '' );

						if( (string) ( $component['source'] ?? '' ) !== '' )
							$components[$k]['source'] = $move( (string) $component['source'], self::_kind( $schemas, $name ) === 'image' ? 'slot' : 'key' );

						foreach( self::_pictures( $schemas, $name ) as $attribute )
							if( (string) ( $component['attributes'][$attribute] ?? '' ) !== '' )
								$components[$k]['attributes'][$attribute] = $move( (string) $component['attributes'][$attribute], 'slot' );

						if( is_array( $component['components'] ?? null ) === true )
							$follow( $components[$k]['components'] );
					}
				};

				foreach( (array) ( $block['cols'] ?? [] ) as $c => $col )
					if( is_array( $col['components'] ?? null ) === true )
						$follow( $model['blocks'][$index]['cols'][$c]['components'] );

				unset( $model['blocks'][$index]['renamedFrom'] );
			}

			return $moves;
		}

		/**
		 *	What a component's source is, by its schema: text, image, href,
		 *	content, none
		 *
		 *	@param		array			$schemas
		 *	@param		string		$name					The component
		 *
		 *	@return 	string
		 */
		private static function _kind( array $schemas, string $name ): string {
			return (string) ( $schemas['components'][$name]['source'] ?? 'text' );
		}

		/**
		 *	The languages the texts of a model are for: the texts typed for the
		 *	key of a component and the alt texts a new slot is made with. Each
		 *	has to be a language the site has
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			$model
		 *
		 *	@return 	array										Sentences naming the section and what the text is of, in English
		 */
		private static function _languages( array &$appData, array $model ): array {

			$problems = [];

			foreach( (array) ( $model['blocks'] ?? [] ) as $index => $block ) {

				if( ( $block['kind'] ?? '' ) !== 'section' )
					continue;

				$at			= 'the section "'. (string) ( $block['id'] ?? '' ). '" (block '. ( (int) $index + 1 ). ')';
				$given	= [ [ 'the alt text of the background', $block['background']['create']['alt'] ?? null ] ];

				foreach( (array) ( $block['cols'] ?? [] ) as $c => $col )
					$given[] = [ 'the alt text of the background of column '. ( (int) $c + 1 ), $col['background']['create']['alt'] ?? null ];

				foreach( (array) ( $block['cols'] ?? [] ) as $col )
					foreach( self::_flat( (array) ( $col['components'] ?? [] ) ) as $component ) {

						$name = (string) ( $component['name'] ?? '' );

						$given[] = [ 'the text of the component "'. $name. '"', $component['values'] ?? null ];
						$given[] = [ 'the alt text of the component "'. $name. '"', is_array( $component['create'] ?? null ) === true ? ( $component['create']['alt'] ?? null ) : null ];

						foreach( (array) ( $component['slots'] ?? [] ) as $attribute => $mark )
							$given[] = [ 'the alt text of the picture "'. $attribute. '" of the component "'. $name. '"', is_array( $mark ) === true ? ( $mark['alt'] ?? null ) : null ];
					}

				foreach( $given as [ $what, $texts ] )
					foreach( is_array( $texts ) === true ? array_keys( $texts ) : [] as $locale )
						if( is_string( $locale ) === false || \Nino\Locales::verifyLocale( $appData, $locale ) === false )
							$problems[] = $at. ': '. $what. ' is for "'. $locale. '", a language the site does not have';
			}

			return $problems;
		}

		/**
		 *	The texts a model carries for the keys of its components, by key and
		 *	language: what was typed into the field of a component whose source
		 *	is a text key. A component that has texts and no such source - a
		 *	fixed text, the field of a loop, a picture - is a problem: a text
		 *	with no key to go to is not written to whatever key is nearest
		 *
		 *	@param		array			$model
		 *	@param		array			$schemas
		 *
		 *	@return 	array										[ key => [ language => text ], the problems as sentences ]; where
		 *																	two components name a key, the later text of a language stands
		 */
		private static function _texts( array $model, array $schemas ): array {

			$texts		= [];
			$problems	= [];

			foreach( (array) ( $model['blocks'] ?? [] ) as $index => $block ) {

				if( ( $block['kind'] ?? '' ) !== 'section' )
					continue;

				foreach( (array) ( $block['cols'] ?? [] ) as $col )
					foreach( self::_flat( (array) ( $col['components'] ?? [] ) ) as $component ) {

						$given = (array) ( $component['values'] ?? [] );

						if( $given === [] )
							continue;

						$name		= (string) ( $component['name'] ?? '' );
						$source	= (string) ( $component['source'] ?? '' );

						// The title of a column is a key whatever line the column shows
						if( self::_kind( $schemas, $name ) !== 'text' || $source === '' || $source[0] !== '/' || ( is_string( $component['text'] ?? null ) === true && Reader::columnar( (array) ( $schemas['components'][$name] ?? [] ) ) === false ) ) {
							$problems[] = 'the section "'. (string) ( $block['id'] ?? '' ). '" (block '. ( (int) $index + 1 ). '): the component "'. $name. '" has a text, and its source is no text key';
							continue;
						}

						foreach( $given as $locale => $text )
							$texts[$source][(string) $locale] = (string) $text;
					}
			}

			return [ $texts, $problems ];
		}

		/**
		 *	The model as the file holds it: without the texts its components
		 *	carry for their keys, which are no part of the file
		 *
		 *	@param		array			$model
		 *
		 *	@return 	array
		 */
		private static function _unstaged( array $model ): array {

			foreach( (array) ( $model['blocks'] ?? [] ) as $b => $block )
				foreach( (array) ( $block['cols'] ?? [] ) as $c => $col )
					if( is_array( $col['components'] ?? null ) === true )
						$model['blocks'][$b]['cols'][$c]['components'] = self::_unstage( $col['components'] );

			return $model;
		}

		/**
		 *	Components without the texts they carry for their keys, the children of a component that holds
		 *	components among them
		 *
		 *	@param		array			$components
		 *
		 *	@return 	array
		 */
		private static function _unstage( array $components ): array {

			foreach( $components as $k => $component ) {

				unset( $components[$k]['values'] );

				if( is_array( $component['components'] ?? null ) === true )
					$components[$k]['components'] = self::_unstage( $component['components'] );
			}

			return $components;
		}

		/**
		 *	What a save is going to do to the keys and the slots, and whether
		 *	it may: the keys and slots the model names as new and the ones a
		 *	renamed section takes along, the texts that are to go to keys -
		 *	checked: the permission of the panel that owns each, the key grammar,
		 *	the format, the size of a slot and the alt texts it is made with, a
		 *	target that is there already, a key a text has none of - before
		 *	anything is changed. Where the account may not manage images the plan
		 *	it answers has no alt texts and no slot to copy in its slots: the
		 *	save makes them empty
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			$model
		 *	@param		array			$moves				See _rename()
		 *	@param		array			$schemas
		 *	@param		array			$texts				See _texts()
		 *
		 *	@return 	array										[ the refusal or null, the plan: 'keys' and 'slots' (source => what it is made with),
		 *																	'keyMoves' and 'slotMoves' (old => new), 'values' (key => language => text) ]
		 */
		private static function _plan( array &$appData, array $model, array $moves, array $schemas, array $texts = [] ): array {

			$plan			= [ 'keys' => [], 'slots' => [], 'keyMoves' => [], 'slotMoves' => [], 'values' => [] ];
			$exists		= [];
			$problems	= [];

			foreach( $moves as [ $old, $new, $kind ] ) {

				if( $kind === 'key' ) {

					if( \Nino\Text::entry( $appData, $old ) === null )
						continue;

					if( \Nino\Text::entry( $appData, $new ) !== null )
						$exists[$new] = 'the key "'. $new. '" is there already';
					else
						$plan['keyMoves'][$old] = $new;

					continue;
				}

				if( \Nino\Images::getSlot( $appData, $old ) === false )
					continue;

				if( \Nino\Images::getSlot( $appData, $new ) !== false )
					$exists[$new] = 'the image slot "'. $new. '" is there already';
				else
					$plan['slotMoves'][$old] = $new;
			}

			if( $exists !== [] )
				return [ self::_error( 409, 'a key or a slot that a renamed section would take over is there already', 'key-exists', array_values( $exists ) ), $plan ];

			foreach( (array) ( $model['blocks'] ?? [] ) as $block ) {

				if( ( $block['kind'] ?? '' ) !== 'section' )
					continue;

				$background = (array) ( $block['background'] ?? [] );

				if( is_array( $background['create'] ?? null ) === true )
					$plan['slots'][(string) $background['slot']] = $background['create'];

				foreach( (array) ( $block['cols'] ?? [] ) as $col )
					if( is_array( $col['background']['create'] ?? null ) === true )
						$plan['slots'][(string) $col['background']['slot']] = $col['background']['create'];

				foreach( (array) ( $block['cols'] ?? [] ) as $col )
					foreach( self::_flat( (array) ( $col['components'] ?? [] ) ) as $component ) {

						$source = (string) ( $component['source'] ?? '' );

						// The slot an image attribute names is made like the one behind a column, from the mark the panel sent for the attribute
						foreach( self::_pictures( $schemas, (string) ( $component['name'] ?? '' ) ) as $attribute )
							if( (string) ( $component['attributes'][$attribute] ?? '' ) !== '' && is_array( $component['slots'][$attribute] ?? null ) === true )
								$plan['slots'][(string) $component['attributes'][$attribute]] = $component['slots'][$attribute];

						if( is_array( $component['create'] ?? null ) === false || $source === '' )
							continue;

						if( self::_kind( $schemas, (string) ( $component['name'] ?? '' ) ) === 'image' ) {
							$plan['slots'][$source] = $component['create'];
							continue;
						}

						// A text key is made in the format its component asks for - the format attribute, where it has one - and plain where it asks for none
						$asked = (string) ( $component['attributes']['format'] ?? '' );

						$plan['keys'][$source] = $component['create'] + [ 'format' => $asked !== '' ? $asked : 'plain' ];
					}
			}

			// What is there already is left alone, what a move makes is not made twice
			foreach( array_keys( $plan['keys'] ) as $source )
				if( \Nino\Text::entry( $appData, (string) $source ) !== null || in_array( $source, $plan['keyMoves'], true ) === true )
					unset( $plan['keys'][$source] );

			foreach( array_keys( $plan['slots'] ) as $uri )
				if( \Nino\Images::getSlot( $appData, (string) $uri ) !== false || in_array( $uri, $plan['slotMoves'], true ) === true )
					unset( $plan['slots'][$uri] );

			foreach( $plan['keys'] as $source => $create ) {

				$format = (string) ( $create['format'] ?? '' );

				if( \Nino\Text::isGrammarKey( (string) $source ) === false )
					$problems[] = 'the key "'. $source. '" is not a text key';
				elseif( in_array( $format, array_merge( [ '', 'auto' ], \Nino\Html::FORMATS ), true ) === false )
					$problems[] = 'the key "'. $source. '" has the format "'. $format. '", which is none of the formats of a text';
			}

			// The alt texts a slot is made with are written by the save itself, and so are the picture and the alt texts a copy takes over, which are the old slot's:
			// the permission to write them is the one the Images panel's images/alt asks for. An account that has not got it is not refused for it - its slots are
			// made as a slot was made without them, the save goes through - and so they are dropped before they are read, so that nothing of them is refused either
			if( $plan['slots'] !== [] && \Nino\Auth::checkPermission( $appData, \Nino\Modules\Images\Admin::MANAGE_PERM ) === false )
				foreach( array_keys( $plan['slots'] ) as $uri )
					unset( $plan['slots'][$uri]['alt'], $plan['slots'][$uri]['from'] );

			foreach( $plan['slots'] as $uri => $create ) {

				$size = self::_size( (array) $create );
				$from = $create['from'] ?? null;

				if( preg_match( self::SLOT, (string) $uri ) !== 1 )
					$problems[] = 'the image slot "'. $uri. '" is not an uri of a slot';
				elseif( $size[0] * $size[1] > \Nino\Images::MAX_SOURCE_PIXELS )
					$problems[] = 'the image slot "'. $uri. '" is larger than an upload can produce';

				// A slot made as the copy of another takes its picture and its alt texts: it says which
				if( $from !== null && ( is_string( $from ) === false || preg_match( self::SLOT, $from ) !== 1 ) )
					$problems[] = 'the image slot "'. $uri. '" is a copy of something that is no image slot';

				// What the Images panel holds an alt text to: one short line of text
				foreach( (array) ( $create['alt'] ?? [] ) as $alt ) {

					$clean = is_string( $alt ) === true ? \Nino\Images::cleanAlt( $alt ) : false;

					if( $clean === false )
						$problems[] = 'an alt text of the image slot "'. $uri. '" is not text';
					elseif( mb_strlen( $clean ) > self::ALT_MAX )
						$problems[] = 'an alt text of the image slot "'. $uri. '" is longer than '. self::ALT_MAX. ' characters';
				}
			}

			// A text goes to a key that is there, or one this save makes or moves
			$known = $texts === [] ? [] : array_column( \Nino\Text::entries( $appData ), null, 'key' );

			foreach( $texts as $key => $given ) {

				if( isset( $known[$key] ) === true || isset( $plan['keys'][$key] ) === true || in_array( $key, $plan['keyMoves'], true ) === true )
					$plan['values'][$key] = $given;
				else
					$problems[] = 'the text for the key "'. $key. '" has no key to go to: it is not there';
			}

			if( $problems !== [] )
				return [ self::_error( 400, 'a key, a slot or a text of the page template cannot be made', 'invalid', $problems ), $plan ];

			$permissions = [];

			if( $plan['keys'] !== [] || $plan['keyMoves'] !== [] || $plan['values'] !== [] )
				$permissions[] = \Nino\Modules\Text\Keys::MANAGE_PERM;

			if( $plan['slots'] !== [] || $plan['slotMoves'] !== [] )
				$permissions[] = \Nino\Modules\Images\Slots::MANAGE_PERM;

			foreach( $permissions as $permission )
				if( \Nino\Auth::checkPermission( $appData, $permission ) === false )
					return [ self::_error( 403, 'this save makes or moves keys or slots, or writes texts, which asks for the permission '. $permission, 'permission', [ $permission ] ), $plan ];

			return [ null, $plan ];
		}

		/**
		 *	The size a slot is made in
		 *
		 *	@param		array			$create				What the model says: width, height
		 *
		 *	@return 	array										[ width, height ], 1 or more each
		 */
		private static function _size( array $create ): array {
			return [ max( 1, (int) ( $create['width'] ?? 1600 ) ), max( 1, (int) ( $create['height'] ?? 900 ) ) ];
		}

		/**
		 *	Make what the plan makes new: the keys, the slots with the alt texts
		 *	their create names - a slot that is a copy (create.from) with the
		 *	picture and the alt texts of the slot it is a copy of; the plan has none
		 *	of the two for an account that may not manage images -, and the slots
		 *	a renamed section moves to, with the alt texts of the old ones. Nothing in the project points
		 *	at any of them yet, so a save that stops after this leaves keys and
		 *	slots that are used by nobody - and nothing that was there changed.
		 *	The keys it made itself are told, so that a save that stops takes
		 *	them away: they hold the texts it wrote
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			$plan					See _plan()
		 *	@param		array 		&$made				(reference) The slots a renamed section moves to that this made, for _drop()
		 *	@param		array 		&$keys				(reference) The keys this made - not one that was there already (409) - for _rollback()
		 *
		 *	@return 	array|null								The answer that stopped it, null where all of it was made
		 */
		private static function _create( array &$appData, array $plan, array &$made, array &$keys = [] ): ?array {

			foreach( $plan['keys'] as $source => $create ) {

				// With the text typed for it in each language and none in the others - an empty text, for which a page shows the native language's: a placeholder is no text
				[ $status, $body ] = self::_api( $appData, 'keys/create', [
					'key'			=> (string) $source,
					'global'	=> false,
					'values'	=> $plan['values'][$source] ?? [],
					'format'	=> (string) ( $create['format'] ?? '' ),
				] );

				if( in_array( $status, [ 200, 409 ], true ) === false )
					return self::_error( $status, (string) ( $body['error'] ?? 'the key could not be created' ), 'key', [ (string) $source ] );

				// From here on the key is this save's; one that another request made in the meantime (409) is not
				if( $status === 200 )
					$keys[] = (string) $source;
			}

			// The slots of the page's own components stay where a save stops: nothing refuses the retry for them
			$kept = [];

			foreach( $plan['slots'] as $uri => $create ) {

				$last = (string) substr( (string) $uri, (int) strrpos( (string) $uri, '/' ) + 1 );
				$size = self::_size( (array) $create );

				$failure = self::_slot( $appData, (string) $uri, trim( (string) ( $create['label'] ?? '' ) ) !== '' ? trim( (string) $create['label'] ) : ucfirst( $last ), $size[0], $size[1], $kept );

				if( $failure !== null )
					return $failure;

				// A slot that is there already is another request's: nothing is written into it, neither the picture of the old slot nor the alt texts. A copy
				// of a slot that is not there - one that this save makes, or that was taken away - has none to hand on
				$old = is_string( $create['from'] ?? null ) === true ? \Nino\Images::getSlot( $appData, $create['from'] ) : false;

				if( is_array( $old ) === true && in_array( (string) $uri, $kept, true ) === true ) {

					$failure = self::_copyPicture( $appData, (string) $uri, (string) $create['from'], $old );

					if( $failure !== null )
						return $failure;
				}

				// The alt texts that were typed for the slot are written over those it was copied with
				$alt = is_array( $create['alt'] ?? null ) === true ? $create['alt'] : [];

				if( $alt !== [] && in_array( (string) $uri, $kept, true ) === true && \Nino\Images::setSlotAlt( $appData, (string) $uri, $alt ) === false )
					return self::_error( 500, 'the alt texts of the slot could not be written', 'slot', [ (string) $uri ] );
			}

			foreach( $plan['slotMoves'] as $old => $new ) {

				$slot = \Nino\Images::getSlot( $appData, (string) $old );

				if( $slot === false )
					continue;

				$failure = self::_slot( $appData, $new, (string) ( $slot['label'] ?? $new ), (int) ( $slot['width'] ?? 0 ), (int) ( $slot['height'] ?? 0 ), $made );

				if( $failure !== null )
					return $failure;

				// The alt texts are the slot's, and the page shows them
				if( is_array( $slot['alt'] ?? null ) === true && $slot['alt'] !== [] )
					\Nino\Images::setSlotAlt( $appData, $new, $slot['alt'] );
			}

			return null;
		}

		/**
		 *	Move what the plan moves: the text keys to their new names, and
		 *	the files of the old slots to the new ones - the old slot is left
		 *	without a file, so that deleting it or uploading into it cannot
		 *	take away the image the renamed section shows
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			$plan					See _plan()
		 *
		 *	@return 	array										[ the answer that stopped it or null, what was moved so far for _undo() ]
		 */
		private static function _move( array &$appData, array $plan ): array {

			$undo = [];

			foreach( $plan['keyMoves'] as $old => $new ) {

				[ $status, $body ] = self::_api( $appData, 'keys/rename', [ 'key' => (string) $old, 'newKey' => $new ] );

				if( $status !== 200 )
					return [ self::_error( $status, (string) ( $body['error'] ?? 'the key could not be renamed' ), 'key', [ (string) $old ] ), $undo ];

				$undo[] = [ 'key', (string) $old, $new ];
			}

			foreach( $plan['slotMoves'] as $old => $new ) {

				$filename = ( \Nino\Images::getSlot( $appData, (string) $old ) ?: [] )['filename'] ?? null;

				if( $filename === null )
					continue;

				if( \Nino\Images::setSlotFilename( $appData, $new, $filename ) === false )
					return [ self::_error( 500, 'the image of the slot could not be handed over', 'slot', [ (string) $old ] ), $undo ];

				$undo[] = [ 'slot', (string) $old, $new, $filename ];

				if( \Nino\Images::setSlotFilename( $appData, (string) $old, null ) === false )
					return [ self::_error( 500, 'the image of the slot could not be handed over', 'slot', [ (string) $old ] ), $undo ];
			}

			return [ null, $undo ];
		}

		/**
		 *	Put back what _move() moved, the last one first - for a save that
		 *	did not get as far as the file
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			$undo					As _move() answers
		 *
		 *	@return 	void
		 */
		private static function _undo( array &$appData, array $undo ): void {

			foreach( array_reverse( $undo ) as $step ) {

				if( $step[0] === 'key' ) {
					self::_api( $appData, 'keys/rename', [ 'key' => $step[2], 'newKey' => $step[1] ] );
					continue;
				}

				\Nino\Images::setSlotFilename( $appData, $step[1], $step[3] );
				\Nino\Images::setSlotFilename( $appData, $step[2], null );
			}
		}

		/**
		 *	Take the slots away that _create() made for the moves of a save
		 *	that stopped - after _undo() they hold no file, and nobody made
		 *	them but this save, which a retry would find in its way. One that
		 *	still holds a file is left, whatever _undo() did not manage
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			$made					The uris _create() made
		 *
		 *	@return 	void
		 */
		private static function _drop( array &$appData, array $made ): void {

			foreach( array_reverse( $made ) as $uri ) {

				// A slot that holds a file was given it back by nobody but a move that was not undone: it is not deleted
				if( ( ( \Nino\Images::getSlot( $appData, $uri ) ?: [] )['filename'] ?? null ) !== null )
					continue;

				self::_api( $appData, 'slots/delete', [ 'uri' => $uri ] );
			}
		}

		/**
		 *	Write the texts a save carries, all of them in one batch of the Text
		 *	Keys' own action. What each key holds in each language is read
		 *	first, to be put back by _restore() - except for a key this save
		 *	made, which is taken away with the rest where the save stops. The
		 *	action says which keys it could not write and not whether each
		 *	language file took them, so what it did is looked at: every text is
		 *	read again and has to be what the key's format makes of it. A key
		 *	every language shares has one text, the last one given
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			$texts				See _plan(): key => language => text
		 *	@param		array			$made					The keys this save made
		 *
		 *	@return 	array										[ the answer that stopped it or null, what the keys held, for _restore() ] - the second
		 *																	is what to put back also where the first stopped half way
		 */
		private static function _write( array &$appData, array $texts, array $made ): array {

			if( $texts === [] )
				return [ null, [] ];

			$known	= array_column( \Nino\Text::entries( $appData ), null, 'key' );
			$items	= [];
			$before	= [];

			foreach( $texts as $key => $given ) {

				$entry = $known[$key] ?? null;

				// The key was made, or moved here, a moment ago: it is there, unless the panel that made it did not say that it could not
				if( $entry === null )
					return [ self::_error( 500, 'the key could not be written', 'key', [ (string) $key ] ), $before ];

				if( $entry['global'] === true )
					$given = array_slice( $given, -1, 1, true );

				foreach( $given as $locale => $text ) {

					$items[] = [ 'key' => (string) $key, 'locale' => (string) $locale, 'value' => $text ];

					if( in_array( (string) $key, $made, true ) === false )
						$before[] = [ 'key' => (string) $key, 'locale' => (string) $locale, 'value' => (string) ( $entry['global'] === true ? ( $entry['values']['*'] ?? '' ) : ( $entry['values'][$locale] ?? '' ) ) ];
				}
			}

			[ $status, $body ] = self::_api( $appData, 'keys/savebatch', [ 'items' => $items ] );

			if( $status !== 200 )
				return [ self::_error( $status, (string) ( $body['error'] ?? 'the texts could not be saved' ), 'key', array_map( 'strval', array_keys( $texts ) ) ), $before ];

			$stored = array_column( \Nino\Text::entries( $appData ), null, 'key' );

			foreach( $items as $item ) {

				$wanted	= \Nino\Text::sanitizeValue( $item['value'], (string) $known[$item['key']]['format'] );
				$held		= $stored[$item['key']] ?? null;

				if( $held !== null )
					$held = $held['global'] === true ? ( $held['values']['*'] ?? null ) : ( $held['values'][$item['locale']] ?? null );

				if( $held !== $wanted )
					return [ self::_error( 500, 'the key could not be written in every language', 'key', [ $item['key'] ] ), $before ];
			}

			return [ null, $before ];
		}

		/**
		 *	Put the texts back that _write() changed: each key in each language
		 *	as it was read before. A language a key had no text in is written
		 *	empty - the Text Keys' action writes a text and does not take one
		 *	away - which a component renders as it renders no text at all
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			$before				As _write() answers
		 *
		 *	@return 	void
		 */
		private static function _restore( array &$appData, array $before ): void {

			if( $before !== [] )
				self::_api( $appData, 'keys/savebatch', [ 'items' => $before ] );
		}

		/**
		 *	Undo what a save did before it stopped, the last thing first: the
		 *	texts put back, the moves undone, the slots made for them taken
		 *	away, the keys it made taken away. Each part is told what it has to
		 *	undo, so a save that stopped early passes what it has so far
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			$before				As _write() answers
		 *	@param		array			$undo					As _move() answers
		 *	@param		array			$made					The slots _create() made for the moves, for _drop()
		 *	@param		array			$keys					The keys _create() made
		 *
		 *	@return 	void
		 */
		private static function _rollback( array &$appData, array $before, array $undo, array $made, array $keys ): void {

			self::_restore( $appData, $before );
			self::_undo( $appData, $undo );
			self::_drop( $appData, $made );
			self::_unmake( $appData, [ 'keys' => $keys, 'slots' => [] ] );
		}

		/**
		 *	One slot, through the Slots tab's own action
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$uri
		 *	@param		string		$label
		 *	@param		int				$width
		 *	@param		int				$height
		 *	@param		array 		&$made				(reference) Where the uri is added when the slot was made here
		 *	@param		bool			$mayExist			Whether a slot that is there already (409) is the slot to use, as a save has it - or a failure, as
		 *																	a copy has it: a copy writes alt texts and a picture into its slots, and not into one it did not make
		 *
		 *	@return 	array|null
		 */
		private static function _slot( array &$appData, string $uri, string $label, int $width, int $height, array &$made, bool $mayExist = true ): ?array {

			[ $status, $body ] = self::_api( $appData, 'slots/create', [ 'uri' => $uri, 'label' => $label, 'width' => $width, 'height' => $height ] );

			if( $status === 200 )
				$made[] = $uri;

			if( $status === 200 || ( $status === 409 && $mayExist === true ) )
				return null;

			return self::_error( $status, (string) ( $body['error'] ?? 'the slot could not be created' ), 'slot', [ $uri ] );
		}

		/**
		 *	An action of another panel, called the way the workbench calls it:
		 *	the data posted, the request answered into - and the posted data
		 *	of this request put back
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$action				keys/create, keys/save, keys/savebatch, keys/rename, keys/delete, slots/create or slots/delete
		 *	@param		array			$data
		 *
		 *	@return 	array										[ status, body ]
		 */
		private static function _api( array &$appData, string $action, array $data ): array {

			$posted		= $_POST;
			$request	= [ '/nino/http/response' => [ 'statusCode' => 200 ] ];

			$_POST['data'] = (string) json_encode( $data );

			try {

				match( $action ) {
					'keys/create'		=> \Nino\Modules\Text\Keys::apiCreate( $appData, $request ),
					'keys/save'			=> \Nino\Modules\Text\Keys::apiSave( $appData, $request ),
					'keys/savebatch'	=> \Nino\Modules\Text\Keys::apiSaveBatch( $appData, $request ),
					'keys/rename'		=> \Nino\Modules\Text\Keys::apiRename( $appData, $request ),
					'keys/delete'		=> \Nino\Modules\Text\Keys::apiDelete( $appData, $request ),
					'slots/delete'		=> \Nino\Modules\Images\Slots::apiDelete( $appData, $request ),
					default					=> \Nino\Modules\Images\Slots::apiCreate( $appData, $request ),
				};
			}
			finally {
				$_POST = $posted;
			}

			$body = $request['/nino/http/response']['body'] ?? [];

			return [ (int) ( $request['/nino/http/response']['statusCode'] ?? 0 ), is_array( $body ) === true ? $body : (array) json_decode( (string) $body, true ) ];
		}

		/**
		 *	Whether two values are the same, a map by what it holds and not
		 *	by the order of its keys
		 *
		 *	@param		mixed			$a
		 *	@param		mixed			$b
		 *
		 *	@return 	bool
		 */
		private static function _same( mixed $a, mixed $b ): bool {

			if( is_array( $a ) === false || is_array( $b ) === false )
				return $a === $b;

			if( count( $a ) !== count( $b ) )
				return false;

			foreach( $a as $key => $value )
				if( array_key_exists( $key, $b ) === false || self::_same( $value, $b[$key] ) === false )
					return false;

			return true;
		}

		/**
		 *	Whether a section that was written and read again is the one that
		 *	was written: the same loop of sections or none, the same columns, in
		 *	each the same stack or none, the same components with the same sources
		 *
		 *	@param		array			$posted				The section of the model
		 *	@param		array			$read					The section the Reader made of what the Writer wrote
		 *
		 *	@return 	bool
		 */
		private static function _readsBack( array $posted, array $read ): bool {

			$loop = $posted['stack'] ?? null;

			if( is_array( $loop ) !== is_array( $read['stack'] ?? null ) )
				return false;

			if( is_array( $loop ) === true && ( (string) ( $loop['name'] ?? '' ) !== $read['stack']['name'] || (string) ( $loop['source'] ?? '' ) !== $read['stack']['source'] ) )
				return false;

			$cols = array_values( (array) ( $posted['cols'] ?? [] ) );

			if( count( $cols ) !== count( $read['cols'] ) )
				return false;

			foreach( $cols as $index => $col ) {

				$other = $read['cols'][$index];
				$stack = $col['stack'] ?? null;

				if( is_array( $stack ) !== is_array( $other['stack'] ) )
					return false;

				if( is_array( $stack ) === true && ( (string) ( $stack['name'] ?? '' ) !== $other['stack']['name'] || (string) ( $stack['source'] ?? '' ) !== $other['stack']['source'] ) )
					return false;

				if( self::_sameCalls( (array) ( $col['components'] ?? [] ), $other['components'] ) === false )
					return false;
			}

			return true;
		}

		/**
		 *	Whether the components that were written and read again are the ones that were written: the
		 *	same names with the same sources, and in a component that holds components the same children
		 *
		 *	@param		array			$posted				The components of the model
		 *	@param		array			$read					The components the Reader made of what the Writer wrote
		 *
		 *	@return 	bool
		 */
		private static function _sameCalls( array $posted, array $read ): bool {

			$posted = array_values( $posted );

			if( count( $posted ) !== count( $read ) )
				return false;

			foreach( $posted as $at => $component ) {

				if( (string) ( $component['name'] ?? '' ) !== $read[$at]['name'] || (string) ( $component['source'] ?? '' ) !== $read[$at]['source'] )
					return false;

				if( self::_sameCalls( (array) ( $component['components'] ?? [] ), (array) ( $read[$at]['components'] ?? [] ) ) === false )
					return false;
			}

			return true;
		}

		/**
		 *	What is wrong with the shape of a model before anything in it is
		 *	read: a name that is no text, a block that is no block, a switch
		 *	that is neither on nor off (the animation of the template, the
		 *	brand colour of the border, the parallax, the dimming, the padding
		 *	of a cover, the background inside and the arrow of a section, the dimming of
		 *	a column - one left out is off), a border that is no strength, a
		 *	cover that is neither none nor a whole number from 1 to 9999, the
		 *	ground of a column that is none of Nino.css's, the width, the place
		 *	and the alignment of a row, the place of the content, the whitespace
		 *	and the alignment of a text or of a stack that are none of its words,
		 *	a loop of
		 *	sections that is no such loop or has a cover with a height - which its
		 *	call cannot carry - or a loop in one of its columns, a background that is no
		 *	slot or whose focus is none of the nine places, a call that is no
		 *	call, texts that are no text for each language, the padding of a
		 *	row, the gap between its columns, the gap between the components of a column and the six sides of the spacing of a section, a column or a
		 *	component that are none of the steps 0 to 6, how far in from its sides the picture behind a row, a
		 *	column or a card starts where that is none of the steps 0 to 3 - or
		 *	is asked of a component that is no card -, the hover of a component that
		 *	is neither empty nor on - or is asked of a component whose source is no
		 *	image -, a
		 *	component that holds components with a content or with children
		 *	that are no calls - or inside another -, and children of a
		 *	component that holds none or of a loop - what the Writer would
		 *	take for something else or stumble over
		 *
		 *	@param		array			$model
		 *	@param		array			$schemas			As schemas() answers
		 *
		 *	@return 	array										Sentences, in English
		 */
		private static function _shape( array $model, array $schemas ): array {

			$problems	= [];
			$text			= static fn( mixed $value ): bool => $value === null || is_string( $value ) === true;
			// The focus of a picture is none or one of the nine places: the Writer casts anything else to a number
			$place		= static fn( mixed $value ): bool => $value === null || ( is_int( $value ) === true && $value >= 1 && $value <= 9 );
			// The height of a cover is none or a whole number of percent, up to the four digits the Reader takes from the attribute: the Writer casts anything else to a number
			$height		= static fn( mixed $value ): bool => $value === null || ( is_int( $value ) === true && $value >= 1 && $value <= 9999 );

			foreach( [ 'name', 'vpa', 'vpaSpeed', 'wrapClass', 'header', 'footer' ] as $key )
				if( $text( $model[$key] ?? null ) === false )
					$problems[] = 'the '. $key. ' is no text';

			if( is_bool( $model['animate'] ?? false ) === false )
				$problems[] = 'the animate is neither on nor off';

			if( is_array( $model['blocks'] ?? [] ) === false )
				return array_merge( $problems, [ 'the blocks are no list' ] );

			foreach( (array) ( $model['blocks'] ?? [] ) as $index => $block ) {

				$at = 'block '. ( (int) $index + 1 );

				if( is_array( $block ) === false ) {
					$problems[] = $at. ' is no block';
					continue;
				}

				if( ( $block['kind'] ?? '' ) === 'html' ) {

					if( $text( $block['source'] ?? null ) === false )
						$problems[] = $at. ' has a source that is no text';

					if( ( $block['reason'] ?? null ) !== null && is_array( $block['reason'] ) === false )
						$problems[] = $at. ' has a reason that is neither none nor a reason';

					continue;
				}

				if( ( $block['kind'] ?? '' ) !== 'section' )
					continue;

				foreach( [ 'id', 'renamedFrom' ] as $key )
					if( $text( $block[$key] ?? null ) === false )
						$problems[] = 'the '. $key. ' of '. $at. ' is no text';

				foreach( [ 'settings', 'cols' ] as $key )
					if( is_array( $block[$key] ?? [] ) === false )
						$problems[] = 'the '. $key. ' of '. $at. ' are no list';

				// What is no switch would be left out of the classes, and read back as off
				foreach( [ 'borderPrimary', 'parallax', 'dim', 'padded', 'inner', 'tiles', 'arrow' ] as $key )
					if( is_bool( $block['settings'][$key] ?? false ) === false )
						$problems[] = 'the '. $key. ' of '. $at. ' is neither on nor off';

				// The border is a strength, and the brand colour is the switch beside it: what is none of the three would be a class of its own
				if( in_array( $block['settings']['border'] ?? '', array_merge( [ '' ], Reader::BORDER ), true ) === false )
					$problems[] = 'the border of '. $at. ' is none of the strengths';

				if( $height( $block['settings']['cover'] ?? null ) === false )
					$problems[] = 'the cover of '. $at. ' is neither none nor a height in percent';

				// The order of the columns is a switch for each of the three viewports: what is none would be left out of the classes, and read back as off
				if( is_array( $block['settings']['reverse'] ?? [] ) === false || count( array_filter( (array) ( $block['settings']['reverse'] ?? [] ), static fn( mixed $on, mixed $viewport ): bool => in_array( $viewport, Reader::VIEWPORTS, true ) === false || is_bool( $on ) === false, ARRAY_FILTER_USE_BOTH ) ) > 0 )
					$problems[] = 'the reverse of '. $at. ' is no switch for each viewport';

				// The width of the row, where it stands and where it puts its columns, where its content stands in a cover or a parallax and how the text is aligned: a word of Nino.css or none
				foreach( [ 'row' => Reader::ROW, 'rowPlace' => Reader::ROW_PLACE, 'rowAlign' => Reader::ROW_ALIGN, 'contentPos' => Reader::CONTENT_POS, 'ratio' => Reader::RATIO, 'whitespace' => Reader::WHITESPACE, 'text' => Reader::TEXT, 'radius' => Reader::RADIUS, 'rowRadius' => Reader::RADIUS ] as $key => $words )
					if( in_array( $block['settings'][$key] ?? '', array_merge( [ '' ], $words ), true ) === false )
						$problems[] = 'the '. $key. ' of '. $at. ' is none that Nino.css has';

				// The spacing of the section on its six sides and the padding of the row on each of its four are steps of the space scale: what is none of them would be a class of nothing
				foreach( Reader::SPACING as $key )
					if( in_array( $block['settings'][$key] ?? '', array_merge( [ '' ], Reader::STEPS ), true ) === false )
						$problems[] = 'the '. $key. ' of '. $at. ' is none of the steps 0 to 6';

				foreach( Reader::ROW_SPACING as $key )
					if( in_array( $block['settings'][$key] ?? '', array_merge( [ '' ], Reader::STEPS ), true ) === false )
						$problems[] = 'the '. $key. ' of '. $at. ' is none of the steps 0 to 6';

				// The gap between the columns of the row is a step of the space scale too
				if( in_array( $block['settings']['rowGap'] ?? '', array_merge( [ '' ], Reader::STEPS ), true ) === false )
					$problems[] = 'the rowGap of '. $at. ' is none of the steps 0 to 6';

				// So is how far in from either side the picture behind the row starts, up to the third step
				foreach( Reader::ROW_BG_SPACING as $key )
					if( in_array( $block['settings'][$key] ?? '', array_merge( [ '' ], Reader::BG_STEPS ), true ) === false )
						$problems[] = 'the '. $key. ' of '. $at. ' is none of the steps 0 to 3';

				// A loop of sections: the call of the stack that makes them - which has no attribute of a tag for what a section has one for
				if( ( $block['stack'] ?? null ) !== null ) {

					$wrong = self::_callWrong( $block['stack'] );

					if( $wrong !== '' )
						$problems[] = $at. ' has '. $wrong;
					elseif( in_array( (string) ( $block['stack']['name'] ?? '' ), Reader::loopsSections( $schemas ), true ) === false )
						$problems[] = $at. ' has the loop "'. (string) ( $block['stack']['name'] ?? '' ). '", which makes no sections';
					elseif( array_key_exists( 'components', $block['stack'] ) === true )
						$problems[] = $at. ' has the loop "'. (string) ( $block['stack']['name'] ?? '' ). '" with components, and it holds none';

					if( ( $block['settings']['cover'] ?? null ) !== null && (int) $block['settings']['cover'] !== 90 )
						$problems[] = 'the cover of '. $at. ' has a height, and the call of a loop of sections has no attribute for one: a cover is as high as the page makes it';
				}

				if( ( $block['background'] ?? null ) !== null && ( is_array( $block['background'] ) === false || $text( $block['background']['slot'] ?? null ) === false ) )
					$problems[] = $at. ' has a background that is no image slot';

				if( is_array( $block['background'] ?? null ) === true && ( $block['background']['field'] ?? '' ) !== '' )
					$problems[] = $at. ' has a background that names a field, which only the background of a column in a loop of sections may';

				if( is_array( $block['background'] ?? null ) === true && self::_texted( [ 'create' => $block['background']['create'] ?? null ] ) === false )
					$problems[] = $at. ' has a background with an alt text that is none';

				if( is_array( $block['background'] ?? null ) === true && $place( $block['background']['focus'] ?? null ) === false )
					$problems[] = $at. ' has a background with a focus that is no place from 1 to 9';

				foreach( is_array( $block['cols'] ?? [] ) === true ? $block['cols'] : [] as $c => $col ) {

					if( is_array( $col ) === false || is_array( $col['components'] ?? [] ) === false || is_array( $col['width'] ?? [] ) === false || is_array( $col['hidden'] ?? [] ) === false ) {
						$problems[] = $at. ' has a column that is none';
						continue;
					}

					$of = 'column '. ( (int) $c + 1 ). ' of '. $at;

					if( in_array( $col['color'] ?? '', array_merge( [ '' ], Reader::COL_COLOR ), true ) === false )
						$problems[] = 'the color of '. $of. ' is none that Nino.css has';

					// How the text of the column and the components of its stack are aligned: a word of Nino.css or none
					foreach( [ 'text' => Reader::TEXT, 'stackAlign' => Reader::STACK_ALIGN, 'radius' => Reader::RADIUS ] as $key => $words )
						if( in_array( $col[$key] ?? '', array_merge( [ '' ], $words ), true ) === false )
							$problems[] = 'the '. $key. ' of '. $of. ' is none that Nino.css has';

					foreach( [ 'dim', 'linked' ] as $key )
						if( is_bool( $col[$key] ?? false ) === false )
							$problems[] = 'the '. $key. ' of '. $of. ' is neither on nor off';

					// The six sides of the spacing are steps of the space scale: what is none of them would be a class of nothing
					foreach( Reader::SPACING as $key )
						if( in_array( $col[$key] ?? '', array_merge( [ '' ], Reader::STEPS ), true ) === false )
							$problems[] = 'the '. $key. ' of '. $of. ' is none of the steps 0 to 6';

					// So is the gap between the components of its stack
					if( in_array( $col['stackGap'] ?? '', array_merge( [ '' ], Reader::STEPS ), true ) === false )
						$problems[] = 'the stackGap of '. $of. ' is none of the steps 0 to 6';

					// So is how far in from either side the picture behind the column starts, up to the third step
					foreach( Reader::BG_SPACING as $key )
						if( in_array( $col[$key] ?? '', array_merge( [ '' ], Reader::BG_STEPS ), true ) === false )
							$problems[] = 'the '. $key. ' of '. $of. ' is none of the steps 0 to 3';

					if( ( $col['background'] ?? null ) !== null && ( is_array( $col['background'] ) === false || $text( $col['background']['slot'] ?? null ) === false ) )
						$problems[] = $of. ' has a background that is no image slot';

					// A field of the element is the picture of a column in a loop of sections: a name, and no slot with it
					if( is_array( $col['background'] ?? null ) === true && array_key_exists( 'field', $col['background'] ) === true && $col['background']['field'] !== '' ) {

						if( is_string( $col['background']['field'] ) === false || preg_match( '/^[A-Za-z0-9_-]+$/D', $col['background']['field'] ) !== 1 )
							$problems[] = $of. ' has a background with a field that is no name';
						elseif( ( $block['stack'] ?? null ) === null )
							$problems[] = $of. ' has a background that names a field of the element, and the section runs no loop';
						elseif( (string) ( $col['background']['slot'] ?? '' ) !== '' || array_key_exists( 'create', $col['background'] ) === true )
							$problems[] = $of. ' has a background that names a field and a slot';
					}

					if( is_array( $col['background'] ?? null ) === true && self::_texted( [ 'create' => $col['background']['create'] ?? null ] ) === false )
						$problems[] = $of. ' has a background with an alt text that is none';

					if( is_array( $col['background'] ?? null ) === true && $place( $col['background']['focus'] ?? null ) === false )
						$problems[] = $of. ' has a background with a focus that is no place from 1 to 9';

					$calls = $col['components'] ?? [];

					if( ( $col['stack'] ?? null ) !== null )
						$calls[] = $col['stack'];

					// The cell of a loop of sections is its columns': a loop of their own would be a loop inside a loop
					if( ( $col['stack'] ?? null ) !== null && ( $block['stack'] ?? null ) !== null )
						$problems[] = $of. ' has a loop, and it is a column of a loop of sections';

					foreach( $calls as $call ) {

						$wrong = self::_callWrong( $call );

						if( $wrong !== '' )
							$problems[] = $at. ' has '. $wrong;
					}

					// What a component holds: the calls of its children, in a list, and no content - and a component that holds none has no list
					foreach( $col['components'] ?? [] as $component )
						$problems = array_merge( $problems, self::_shapeHeld( $component, $schemas, $at, false ), self::_shapeRadius( $component, $schemas, $at ), self::_shapeHover( $component, $schemas, $at ), self::_shapeGround( $component, $schemas, $at ), self::_shapeSpacing( $component, $at ) );

					// A loop holds none: its cells are the components of its column, and the Writer would leave a list of its own out
					if( is_array( $col['stack'] ?? null ) === true && self::_callWrong( $col['stack'] ) === '' && array_key_exists( 'components', $col['stack'] ) === true )
						$problems[] = $at. ' has the loop "'. (string) ( $col['stack']['name'] ?? '' ). '" with components, and it holds none';
				}
			}

			return $problems;
		}

		/**
		 *	What is wrong with a call of the model - a component or a loop - in its shape alone
		 *
		 *	@param		mixed			$call
		 *
		 *	@return 	string										'' for a call that will do, else what the block has: a call that is none, or a text that is none
		 */
		private static function _callWrong( mixed $call ): string {

			$text = static fn( mixed $value ): bool => $value === null || is_string( $value ) === true;

			// The marks the panel sends for the image attributes are a map of maps
			if( is_array( $call ) === false || $text( $call['name'] ?? null ) === false || $text( $call['source'] ?? null ) === false || $text( $call['text'] ?? null ) === false
				|| is_array( $call['attributes'] ?? [] ) === false || array_filter( (array) ( $call['attributes'] ?? [] ), static fn( mixed $value ): bool => $value !== null && is_scalar( $value ) === false ) !== []
				|| is_array( $call['slots'] ?? [] ) === false || array_filter( (array) ( $call['slots'] ?? [] ), static fn( mixed $mark ): bool => is_array( $mark ) === false ) !== [] )
				return 'a call that is none';

			return self::_texted( $call ) === false ? 'a text that is none' : '';
		}

		/**
		 *	What is wrong with the radius of a component and of the children it holds: a word that is none of the two strengths of the page, or a radius on a
		 *	component that is no surface (see Reader::surface()) - which the Writer would write as a class of nothing, and the Reader read back as a custom one
		 *
		 *	@param		mixed			$call					A component of a column, or a child of one
		 *	@param		array			$schemas
		 *	@param		string		$at						The block, as the sentences name it: block 2
		 *
		 *	@return 	array										Sentences, in English
		 */
		private static function _shapeRadius( mixed $call, array $schemas, string $at ): array {

			// A call that is none has been told already
			if( is_array( $call ) === false || self::_callWrong( $call ) !== '' )
				return [];

			$name				= (string) ( $call['name'] ?? '' );
			$radius			= $call['radius'] ?? '';
			$problems		= [];

			if( in_array( $radius, array_merge( [ '' ], Reader::RADIUS ), true ) === false )
				$problems[] = $at. ' has the component "'. $name. '" with a radius that is none of the two strengths';
			elseif( $radius !== '' && Reader::surface( (array) ( $schemas['components'][$name] ?? [] ), (array) ( $schemas['components'] ?? [] ) ) === '' )
				$problems[] = $at. ' has the component "'. $name. '" with a radius, and it is no surface';

			foreach( is_array( $call['components'] ?? null ) === true ? $call['components'] : [] as $child )
				$problems = array_merge( $problems, self::_shapeRadius( $child, $schemas, $at ) );

			return $problems;
		}

		/**
		 *	What is wrong with the hover of a component and of the children it holds: a word that is neither empty nor on, or a hover on a component whose schema does
		 *	not say that its source is an image - which the Writer would write as a class of nothing, and the Reader read back as a custom one
		 *
		 *	@param		mixed			$call					A component of a column, or a child of one
		 *	@param		array			$schemas
		 *	@param		string		$at						The block, as the sentences name it: block 2
		 *
		 *	@return 	array										Sentences, in English
		 */
		private static function _shapeHover( mixed $call, array $schemas, string $at ): array {

			// A call that is none has been told already
			if( is_array( $call ) === false || self::_callWrong( $call ) !== '' )
				return [];

			$name				= (string) ( $call['name'] ?? '' );
			$hover			= $call['hover'] ?? '';
			$problems		= [];

			if( in_array( $hover, [ '', Reader::HOVER ], true ) === false )
				$problems[] = $at. ' has the component "'. $name. '" with a hover that is neither empty nor on';
			elseif( $hover !== '' && ( $schemas['components'][$name]['source'] ?? '' ) !== 'image' )
				$problems[] = $at. ' has the component "'. $name. '" with a hover, and it is no picture';

			foreach( is_array( $call['components'] ?? null ) === true ? $call['components'] : [] as $child )
				$problems = array_merge( $problems, self::_shapeHover( $child, $schemas, $at ) );

			return $problems;
		}

		/**
		 *	What is wrong with how far in from either side the picture behind a component starts, and of the children it holds: a step that is none of
		 *	0 to 3, or one on a component that is no card (see Reader::surface()) - which the Writer would write as a class of nothing, and the Reader read
		 *	back as a custom one
		 *
		 *	@param		mixed			$call					A component of a column, or a child of one
		 *	@param		array			$schemas
		 *	@param		string		$at						The block, as the sentences name it: block 2
		 *
		 *	@return 	array										Sentences, in English
		 */
		private static function _shapeGround( mixed $call, array $schemas, string $at ): array {

			// A call that is none has been told already
			if( is_array( $call ) === false || self::_callWrong( $call ) !== '' )
				return [];

			$name				= (string) ( $call['name'] ?? '' );
			$card				= Reader::surface( (array) ( $schemas['components'][$name] ?? [] ), (array) ( $schemas['components'] ?? [] ) ) === 'card';
			$problems		= [];

			foreach( Reader::BG_SPACING as $key ) {

				$step = $call[$key] ?? '';

				if( in_array( $step, array_merge( [ '' ], Reader::BG_STEPS ), true ) === false )
					$problems[] = $at. ' has the component "'. $name. '" with a '. $key. ' that is none of the steps 0 to 3';
				elseif( $step !== '' && $card === false )
					$problems[] = $at. ' has the component "'. $name. '" with a '. $key. ', and it is no card';
			}

			foreach( is_array( $call['components'] ?? null ) === true ? $call['components'] : [] as $child )
				$problems = array_merge( $problems, self::_shapeGround( $child, $schemas, $at ) );

			return $problems;
		}

		/**
		 *	What is wrong with the six sides of the spacing of a component and of the children it holds: a value that is none of the steps 0 to 6, which the Writer would
		 *	write as a class of nothing and the Reader read back as a custom one. Every component has the six, a surface or not
		 *
		 *	@param		mixed			$call					A component of a column, or a child of one
		 *	@param		string		$at						The block, as the sentences name it: block 2
		 *
		 *	@return 	array										Sentences, in English
		 */
		private static function _shapeSpacing( mixed $call, string $at ): array {

			// A call that is none has been told already
			if( is_array( $call ) === false || self::_callWrong( $call ) !== '' )
				return [];

			$problems = [];

			foreach( Reader::SPACING as $key )
				if( in_array( $call[$key] ?? '', array_merge( [ '' ], Reader::STEPS ), true ) === false )
					$problems[] = $at. ' has the component "'. (string) ( $call['name'] ?? '' ). '" with a '. $key. ' that is none of the steps 0 to 6';

			foreach( is_array( $call['components'] ?? null ) === true ? $call['components'] : [] as $child )
				$problems = array_merge( $problems, self::_shapeSpacing( $child, $at ) );

			return $problems;
		}

		/**
		 *	What is wrong with the way a component holds components. One that does holds the calls of its
		 *	children in a list and has no content, and is not itself held by another; one that does not
		 *	has no list of children
		 *
		 *	@param		mixed			$call					A component of a column, or a child of one
		 *	@param		array			$schemas
		 *	@param		string		$at						The block, as the sentences name it: block 2
		 *	@param		bool			$inside				Whether the call is a child of a component that holds components
		 *
		 *	@return 	array										Sentences, in English
		 */
		private static function _shapeHeld( mixed $call, array $schemas, string $at, bool $inside ): array {

			// A call that is none has been told already
			if( is_array( $call ) === false || self::_callWrong( $call ) !== '' )
				return [];

			$name = (string) ( $call['name'] ?? '' );

			if( Reader::holds( (array) ( $schemas['components'][$name] ?? [] ) ) === false )
				return array_key_exists( 'components', $call ) === true ? [ $at. ' has the component "'. $name. '" with components, and it holds none' ] : [];

			if( $inside === true )
				return [ $at. ' has the component "'. $name. '" inside another that holds components' ];

			$problems = [];

			if( array_key_exists( 'content', $call ) === true )
				$problems[] = $at. ' has the component "'. $name. '" with a content, and it holds components';

			if( is_array( $call['components'] ?? [] ) === false || array_is_list( (array) ( $call['components'] ?? [] ) ) === false )
				return array_merge( $problems, [ $at. ' has the component "'. $name. '" with children that are no list' ] );

			foreach( (array) ( $call['components'] ?? [] ) as $child ) {

				$wrong = self::_callWrong( $child );

				if( $wrong !== '' )
					$problems[] = $at. ' has '. $wrong;
				else
					$problems = array_merge( $problems, self::_shapeHeld( $child, $schemas, $at, true ) );
			}

			return $problems;
		}

		/**
		 *	The components of a column in the order of the file, each followed by the children it holds
		 *
		 *	@param		array			$components
		 *
		 *	@return 	array
		 */
		private static function _flat( array $components ): array {

			$flat = [];

			foreach( array_values( $components ) as $component ) {

				$flat[] = $component;

				if( is_array( $component ) === true && is_array( $component['components'] ?? null ) === true )
					$flat = array_merge( $flat, self::_flat( $component['components'] ) );
			}

			return $flat;
		}

		/**
		 *	The attributes of a component whose type is image: each names a slot, or in a loop a field of the
		 *	element. The schema says so, whatever the component is
		 *
		 *	@param		array			$schemas
		 *	@param		string		$name					The component
		 *
		 *	@return 	array										Their names, in the order of the schema
		 */
		private static function _pictures( array $schemas, string $name ): array {

			$pictures = [];

			foreach( (array) ( $schemas['components'][$name]['attributes'] ?? [] ) as $attribute => $declared )
				if( is_array( $declared ) === true && ( $declared['type'] ?? '' ) === 'image' )
					$pictures[] = (string) $attribute;

			return $pictures;
		}

		/**
		 *	Whether the texts a call carries are a text for each language: those
		 *	typed for its key (values) and the alt texts of the slot its create
		 *	makes, and of the slots its marks for the image attributes make. What
		 *	a language is is looked at by _languages(), which asks the site
		 *
		 *	@param		array			$call
		 *
		 *	@return 	bool
		 */
		private static function _texted( array $call ): bool {

			$given = [ $call['values'] ?? null, is_array( $call['create'] ?? null ) === true ? ( $call['create']['alt'] ?? null ) : null ];

			foreach( (array) ( $call['slots'] ?? [] ) as $mark )
				$given[] = is_array( $mark ) === true ? ( $mark['alt'] ?? null ) : null;

			foreach( $given as $texts )
				if( $texts !== null && ( is_array( $texts ) === false || array_filter( $texts, static fn( mixed $text ): bool => is_string( $text ) === false ) !== [] ) )
					return false;

			return true;
		}

		/**
		 *	The element types of the project, by their uri without the slash
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	array
		 */
		private static function _types( array &$appData ): array {

			$types = [];

			foreach( glob( \Nino\Filesystem::path( $appData, '/elements' ). '/*.php' ) ?: [] as $file )
				if( preg_match( self::TYPE, basename( $file, '.php' ) ) === 1 )
					$types[] = basename( $file, '.php' );

			sort( $types );

			return $types;
		}

		/**
		 *	The templates of the project that can be a page's header or footer
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$prefix				html-header or html-footer
		 *
		 *	@return 	array										Names without .tpl
		 */
		private static function _frames( array &$appData, string $prefix ): array {

			$frames = [];

			foreach( glob( \Nino\Filesystem::path( $appData, self::TEMPLATES ). '/'. $prefix. '*.tpl' ) ?: [] as $file )
				if( preg_match( self::FRAME, basename( $file, '.tpl' ) ) === 1 )
					$frames[] = basename( $file, '.tpl' );

			sort( $frames );

			return $frames;
		}

		/**
		 *	@param		string		$name
		 *
		 *	@return 	bool										Whether it can be the name a template calls itself
		 */
		private static function _validName( string $name ): bool {
			return preg_match( '/^[^\r\n<>\[\]]{1,160}$/uD', $name ) === 1 && str_contains( $name, '-->' ) === false;
		}

		/**
		 *	An answer that is a refusal
		 *
		 *	@param		int				$status
		 *	@param		string		$error				The sentence, in English
		 *	@param		string		$code					What the panel looks at
		 *	@param		array			$problems			What is wrong, one sentence each
		 *
		 *	@return 	array
		 */
		private static function _error( int $status, string $error, string $code, array $problems = [] ): array {
			return [ 'status' => $status, 'error' => $error, 'code' => $code, 'problems' => $problems ];
		}
	}

}
