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
	 *											apiRename(), an image slot with
	 *											\Nino\Modules\Images\Slots::apiCreate() - and takes one away
	 *											again with Slots::apiDelete(), where a save that stopped
	 *											has made it for nothing. Those answer a
	 *											request, so they are called with one - the posted data set,
	 *											the request they answer into read back - and they ask for
	 *											the permission of their own panel, which is as it should be:
	 *											an account that may not create keys cannot create them
	 *											through here either - and a save asks for those permissions,
	 *											and checks what it is going to make, before it changes
	 *											anything.
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
		public const string FILE = '/^page-[a-z0-9]+(?:-[a-z0-9]+)*$/';

		// The frames a page may name, and a name for the page
		private const string FRAME = '/^html-(?:header|footer)(?:-[a-z0-9]+)*$/';
		private const string TYPE = '/^[a-z][a-z0-9_-]*$/';
		private const string SLOT = '#^/[a-z][a-z0-9_-]*(?:/[a-z][a-z0-9_-]*)*$#';

		// What a call cannot carry: the shortcode syntax has no escape for a quote, a
		// bracket or a line break in a value, and the kernel reads ' as "
		private const string UNSAFE = '/["\'\[\]\r\n]/';

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
		 *	@return 	array										[ 'components' => name => schema, 'stacks' => name => schema ]
		 */
		public static function schemas( array &$appData ): array {

			$schemas = [ 'components' => \Nino\Modules\Components::components( $appData ), 'stacks' => \Nino\Modules\Components::stacks( $appData ) ];

			foreach( array_keys( $schemas['components'] ) as $name )
				$schemas['components'][$name]['defaults'] = \Nino\Modules\Components::defaults( $appData, (string) $name );

			foreach( array_keys( $schemas['stacks'] ) as $name )
				$schemas['stacks'][$name]['defaults'] = \Nino\Modules\Components::defaults( $appData, (string) $name, true );

			return $schemas;
		}

		/**
		 *	Everything the panel builds its forms from: the components and
		 *	the stacks with their labels in the language of the workbench, the
		 *	types of the Elements panel with their fields, the image slots (each
		 *	with the url of its picture, null where it has none), the frames a
		 *	page may name
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	array										[ 'status' => 200, 'components', 'stacks', 'types', 'fieldTypes', 'slots', 'headers', 'footers' ]
		 */
		public static function registry( array &$appData ): array {

			$locale		= \Nino\Admin\Admin::sessionLocale( $appData );
			$schemas	= self::schemas( $appData );

			foreach( [ 'components', 'stacks' ] as $kind )
				foreach( $schemas[$kind] as $name => $schema ) {

					$schemas[$kind][$name]['label'] = \Nino\Features::localized( $schema['label'], $locale );

					foreach( (array) $schema['attributes'] as $attribute => $declared ) {
						$schemas[$kind][$name]['attributes'][$attribute]['label'] = \Nino\Features::localized( $declared['label'] ?? $attribute, $locale );
						$schemas[$kind][$name]['attributes'][$attribute]['hint'] = \Nino\Features::localized( $declared['hint'] ?? '', $locale );
					}
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
				];

			usort( $slots, static fn( array $a, array $b ): int => strcmp( $a['uri'], $b['uri'] ) );

			return [
				'status'			=> 200,
				'components'	=> $schemas['components'],
				'stacks'			=> $schemas['stacks'],
				'types'				=> $types,
				'fieldTypes'	=> \Nino\Elements::FIELD_TYPES,
				'slots'				=> $slots,
				'headers'			=> self::_frames( $appData, 'html-header' ),
				'footers'			=> self::_frames( $appData, 'html-footer' ),
			];
		}

		/**
		 *	Every page template of the project: what the list screen shows
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	array										[ 'status' => 200, 'templates' => [ file, name, header, footer,
		 *																	sections, foreign, readable, reason, editable, usedBy ] ], by file;
		 *																	reason is the first block the Reader failed at, null where there is none
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
		 *	not rewritten into the canonical form where nobody changed it. A
		 *	block that came back from an edit (edited: true) is read as a
		 *	section again first; a section that carries renamedFrom has the
		 *	keys of its old id moved to the new one - the sources, the text
		 *	keys, and the slots, which are made again under the new name with
		 *	the alt texts of the old one, and hand their file over to it
		 *	(nothing is deleted).
		 *
		 *	Everything a save refuses for is refused before the first change:
		 *	the permissions of the panels that own the keys and the slots, what
		 *	each new key and slot is made of, a target a move would overwrite.
		 *	What is made then is made in an order that can be undone: the keys
		 *	and slots that are new first (nothing in the project points at
		 *	them yet), the keys and the files that move last, just before the
		 *	file is written - and moved back where the write does not happen.
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$file					page-home
		 *	@param		array			$model				The model, see Reader
		 *	@param		string		$hash					The hash load() gave
		 *	@param		bool			$force				Write although the file is not the one the hash is of
		 *
		 *	@return 	array										[ 'status' => 200, 'model', 'hash' ] - the model as it reads back; 400 with
		 *																	'problems', 403, 404, 409 for a file that changed or a key or slot a
		 *																	move would overwrite ('key-exists'), or what a panel that makes a key
		 *																	or a slot answered
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
			$problems	= self::_shape( $model );

			if( $problems !== [] )
				return self::_error( 400, 'the page template is not valid', 'invalid', $problems );

			$model		= self::_again( $model, $schemas );
			$moves		= self::_rename( $model, $file, $schemas );

			// Nothing to write: the model is the file
			$disk = Reader::read( $current, $schemas );
			$disk['file'] = $file;

			if( self::_same( [ 'file' => $file ] + $model, $disk ) === true )
				return [ 'status' => 200, 'model' => $disk, 'hash' => hash( 'sha256', $current ) ];

			$problems = self::_values( $model );

			if( $problems !== [] )
				return self::_error( 400, 'a value cannot stand in a call', 'value', $problems );

			$problems = self::problems( $appData, $model, $schemas );

			if( $problems !== [] )
				return self::_error( 400, 'the page template is not valid', 'invalid', $problems );

			[ $failure, $plan ] = self::_plan( $appData, $model, $moves, $schemas );

			if( $failure !== null )
				return $failure;

			$made			= [];
			$failure	= self::_create( $appData, $plan, $made );

			if( $failure !== null ) {
				self::_drop( $appData, $made );
				return $failure;
			}

			[ $failure, $undo ] = self::_move( $appData, $plan );

			if( $failure !== null ) {
				self::_undo( $appData, $undo );
				self::_drop( $appData, $made );
				return $failure;
			}

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

				self::_undo( $appData, $undo );
				self::_drop( $appData, $made );

				return $conflict === true
					? self::_error( 409, 'the page template changed since it was loaded', 'conflict' )
					: self::_error( 500, 'could not write the page template', 'write' );
			}

			$read = Reader::read( $source, $schemas );
			$read['file'] = $file;

			return [ 'status' => 200, 'model' => $read, 'hash' => hash( 'sha256', $source ) ];
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

			$problems = self::_shape( $model );

			if( $problems !== [] )
				return self::_error( 400, 'the page template is not valid', 'invalid', $problems );

			$schemas	= self::schemas( $appData );
			$model		= self::_again( $model, $schemas );
			$parts		= [];

			// After the blocks that were edited are read: what they came to is looked at, as a save does
			$problems = self::_values( $model );

			if( $problems !== [] )
				return self::_error( 400, 'a value cannot stand in a call', 'value', $problems );

			$head = rtrim( Writer::write( [ 'name' => $model['name'] ?? '', 'vpa' => $model['vpa'] ?? null, 'header' => $model['header'] ?? '', 'blocks' => [] ], $schemas ), "\n" );

			if( $head !== '' )
				$parts[] = [ 'kind' => 'head', 'source' => $head, 'block' => null ];

			foreach( (array) $model['blocks'] as $index => $block )
				$parts[] = [ 'kind' => (string) $block['kind'], 'source' => rtrim( Writer::write( [ 'blocks' => [ $block ] ], $schemas ), "\n" ), 'block' => (int) $index ];

			$foot = rtrim( Writer::write( [ 'footer' => $model['footer'] ?? '', 'blocks' => [] ], $schemas ), "\n" );

			if( $foot !== '' )
				$parts[] = [ 'kind' => 'foot', 'source' => $foot, 'block' => null ];

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

			$slug = self::_slug( $name );

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

			$model		= [ 'file' => $file, 'name' => $name, 'vpa' => null, 'header' => $frames['header'], 'footer' => $frames['footer'], 'blocks' => [] ];
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
		 *	was - the head line of the animation included.
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
			$slug = self::_validName( $name ) === true ? self::_slug( $name ) : '';
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

				// The alt text of a language the site no longer has is left behind
				$alt = is_array( $slot['alt'] ?? null ) === true ? array_intersect_key( $slot['alt'], array_flip( \Nino\Locales::getAvailableLocales( $appData ) ) ) : [];

				if( $alt !== [] && \Nino\Images::setSlotAlt( $appData, (string) $uri, $alt ) === false )
					return self::_error( 500, 'the alt texts of the slot could not be copied', 'slot', [ (string) $uri ] );

				$filename = $slot['filename'] ?? null;

				if( is_string( $filename ) === false || $filename === '' )
					continue;

				$bytes = \Nino\Images::read( $appData, $filename );

				// A slot that names a picture that is not there has none to hand on
				if( is_string( $bytes ) === false )
					continue;

				$base = ltrim( $old, '/' );
				$copy = ltrim( (string) $uri, '/' ). ( str_starts_with( $filename, $base ) === true ? substr( $filename, strlen( $base ) ) : '.'. basename( $filename ) );

				if( \Nino\Images::restore( $appData, $copy, $bytes ) === false )
					return self::_error( 500, 'the image of the slot could not be copied', 'slot', [ (string) $uri ] );

				if( \Nino\Images::setSlotFilename( $appData, (string) $uri, $copy ) === false ) {
					\Nino\Images::delete( $appData, $copy );
					return self::_error( 500, 'the image of the slot could not be copied', 'slot', [ (string) $uri ] );
				}
			}

			return null;
		}

		/**
		 *	One key made again under another name, through the Text Keys' own
		 *	actions: made in the format of the original with its first value in
		 *	every language, the other values written over, its limit and its
		 *	place on the blacklist kept - and read again, every value as the
		 *	original has it, because the panel makes the key without telling
		 *	whether every language file took it
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
			$first	= $values === [] ? '' : (string) reset( $values );

			[ $status, $body ] = self::_api( $appData, 'keys/create', [
				'key'			=> $key,
				'global'	=> $entry['global'] === true,
				'value'		=> $first,
				'format'	=> (string) $entry['format'],
			] );

			if( $status !== 200 )
				return self::_error( $status, (string) ( $body['error'] ?? 'the key could not be created' ), 'key', [ $key ] );

			// From here on the key is the copy's, and goes with it where anything below stops
			$made[] = $key;

			$items = [];

			foreach( $values as $locale => $value )
				if( $entry['global'] === false && $value !== $first )
					$items[] = [ 'key' => $key, 'locale' => (string) $locale, 'value' => $value ];

			if( $items !== [] ) {

				[ $status, $body ] = self::_api( $appData, 'keys/savebatch', [ 'items' => $items ] );

				if( $status !== 200 )
					return self::_error( $status, (string) ( $body['error'] ?? 'the key could not be saved' ), 'key', [ $key ] );
			}

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
			$values = self::_values( $model );

			if( $values !== [] )
				return array_merge( $problems, $values );

			$again	= Reader::read( Writer::write( $model, $schemas ), $schemas );
			$found	= [];
			$seen		= [];

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
		 *	and the href among them) or a custom class - and an animation of the
		 *	template that is no list of the classes of nino-vpa, which would
		 *	leave its comment. The content of a component is no attribute and
		 *	is not looked at
		 *
		 *	@param		array			$model
		 *
		 *	@return 	array										Sentences naming the block, the component and the attribute
		 */
		private static function _values( array $model ): array {

			$problems	= [];
			$unsafe		= static fn( mixed $value ): bool => is_scalar( $value ) === true && preg_match( self::UNSAFE, is_bool( $value ) === true ? '' : (string) $value ) === 1;

			if( is_string( $model['vpa'] ?? null ) === true && Reader::vpaClasses( $model['vpa'] ) === false )
				$problems[] = 'the animation of the template is none of the classes of nino-vpa';

			foreach( (array) ( $model['blocks'] ?? [] ) as $index => $block ) {

				if( is_array( $block ) === false || ( $block['kind'] ?? '' ) !== 'section' )
					continue;

				$at = 'the section "'. (string) ( $block['id'] ?? '' ). '" (block '. ( (int) $index + 1 ). ')';

				foreach( [ 'custom' => 'the custom classes', 'rowCustom' => 'the custom classes of the row' ] as $key => $what )
					if( $unsafe( $block['settings'][$key] ?? null ) === true )
						$problems[] = $at. ': '. $what. ' hold a quote, a bracket or a line break, which a call cannot carry';

				foreach( (array) ( $block['cols'] ?? [] ) as $c => $col ) {

					if( is_array( $col ) === false )
						continue;

					if( $unsafe( $col['custom'] ?? null ) === true )
						$problems[] = $at. ': the custom classes of column '. ( (int) $c + 1 ). ' hold a quote, a bracket or a line break, which a call cannot carry';

					$calls = array_values( (array) ( $col['components'] ?? [] ) );

					if( is_array( $col['stack'] ?? null ) === true )
						$calls[] = $col['stack'];

					foreach( $calls as $call ) {

						if( is_array( $call ) === false )
							continue;

						$values = [ 'source' => $call['source'] ?? null, 'text' => $call['text'] ?? null ] + (array) ( $call['attributes'] ?? [] );

						foreach( $values as $attribute => $value )
							if( $unsafe( $value ) === true )
								$problems[] = $at. ': the attribute "'. $attribute. '" of the component "'. (string) ( $call['name'] ?? '' ). '" holds a quote, a bracket or a line break, which a call cannot carry';
					}
				}
			}

			return $problems;
		}

		/**
		 *	What a section's sources mean where they stand: in a static
		 *	column a text key or nothing, in a stack a key, .id, .uri or a
		 *	field of the type, and a stack's type has to be one the Elements
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

			foreach( (array) ( $section['cols'] ?? [] ) as $col ) {

				$fields = null;

				if( is_array( $col['stack'] ?? null ) === true ) {

					$type = ltrim( (string) ( $col['stack']['source'] ?? '' ), '/' );

					if( preg_match( self::TYPE, $type ) !== 1 || in_array( $type, self::_types( $appData ), true ) === false )
						$problems[] = 'the loop runs over "'. $type. '", which is no element type';
					else
						$fields = (array) ( \Nino\Filesystem::getFileContent( $appData, '/elements/'. $type. '.php', [] )['model'] ?? [] );
				}

				foreach( (array) ( $col['components'] ?? [] ) as $component ) {

					$kind		= (string) ( $schemas['components'][$component['name'] ?? '']['source'] ?? 'text' );
					$source	= (string) ( $component['source'] ?? '' );

					if( $source === '' || in_array( $kind, [ 'none', 'content' ], true ) === true )
						continue;

					if( $source[0] === '/' )
						$valid = $kind === 'image' ? preg_match( self::SLOT, $source ) === 1 : \Nino\Text::isGrammarKey( $source ) === true || str_starts_with( $source, '/_nino/' ) === true || \Nino\Text::entry( $appData, $source ) !== null;
					elseif( $fields === null )
						$valid = $kind === 'href';
					else
						$valid = in_array( $source, [ '.id', '.uri' ], true ) === true || isset( $fields[$source] ) === true || $kind === 'href';

					if( $valid === false )
						$problems[] = 'the source "'. $source. '" of '. ( $component['name'] ?? '' ). ( $fields === null ? ' means nothing in a column without a loop: it is a text key or an image slot' : ' is no field of the loop\'s type' );
				}
			}

			return $problems;
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
					foreach( (array) ( $col['components'] ?? [] ) as $k => $component )
						if( (string) ( $component['source'] ?? '' ) !== '' )
							$model['blocks'][$index]['cols'][$c]['components'][$k]['source'] = $move( (string) $component['source'], self::_kind( $schemas, (string) ( $component['name'] ?? '' ) ) === 'image' ? 'slot' : 'key' );

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
		 *	What a save is going to do to the keys and the slots, and whether
		 *	it may: the keys and slots the model names as new and the ones a
		 *	renamed section takes along, checked - the permission of each
		 *	panel, the key grammar, the format, the size of a slot, a target
		 *	that is there already - before anything is changed
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			$model
		 *	@param		array			$moves				See _rename()
		 *	@param		array			$schemas
		 *
		 *	@return 	array										[ the refusal or null, the plan: 'keys' and 'slots' (source => what it is made with),
		 *																	'keyMoves' and 'slotMoves' (old => new) ]
		 */
		private static function _plan( array &$appData, array $model, array $moves, array $schemas ): array {

			$plan			= [ 'keys' => [], 'slots' => [], 'keyMoves' => [], 'slotMoves' => [] ];
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
					foreach( (array) ( $col['components'] ?? [] ) as $component ) {

						$source = (string) ( $component['source'] ?? '' );

						if( is_array( $component['create'] ?? null ) === false || $source === '' )
							continue;

						if( self::_kind( $schemas, (string) ( $component['name'] ?? '' ) ) === 'image' )
							$plan['slots'][$source] = $component['create'];
						else
							$plan['keys'][$source] = $component['create'] + [ 'format' => (string) ( $component['attributes']['format'] ?? '' ) ];
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

			foreach( $plan['slots'] as $uri => $create ) {

				$size = self::_size( (array) $create );

				if( preg_match( self::SLOT, (string) $uri ) !== 1 )
					$problems[] = 'the image slot "'. $uri. '" is not an uri of a slot';
				elseif( $size[0] * $size[1] > \Nino\Images::MAX_SOURCE_PIXELS )
					$problems[] = 'the image slot "'. $uri. '" is larger than an upload can produce';
			}

			if( $problems !== [] )
				return [ self::_error( 400, 'a key or a slot of the page template cannot be made', 'invalid', $problems ), $plan ];

			$permissions = [];

			if( $plan['keys'] !== [] || $plan['keyMoves'] !== [] )
				$permissions[] = \Nino\Modules\Text\Keys::MANAGE_PERM;

			if( $plan['slots'] !== [] || $plan['slotMoves'] !== [] )
				$permissions[] = \Nino\Modules\Images\Slots::MANAGE_PERM;

			foreach( $permissions as $permission )
				if( \Nino\Auth::checkPermission( $appData, $permission ) === false )
					return [ self::_error( 403, 'this save makes or moves keys or slots, which asks for the permission '. $permission, 'permission', [ $permission ] ), $plan ];

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
		 *	Make what the plan makes new: the keys, the slots, and the slots a
		 *	renamed section moves to, with their alt texts. Nothing in the
		 *	project points at any of them yet, so a save that stops after
		 *	this leaves keys and slots that are used by nobody - and nothing
		 *	that was there changed
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			$plan					See _plan()
		 *	@param		array 		&$made				(reference) The slots a renamed section moves to that this made, for _drop()
		 *
		 *	@return 	array|null								The answer that stopped it, null where all of it was made
		 */
		private static function _create( array &$appData, array $plan, array &$made ): ?array {

			foreach( $plan['keys'] as $source => $create ) {

				[ $status, $body ] = self::_api( $appData, 'keys/create', [
					'key'			=> (string) $source,
					'global'	=> false,
					'value'		=> (string) ( $create['value'] ?? '' ),
					'format'	=> (string) ( $create['format'] ?? '' ),
				] );

				if( in_array( $status, [ 200, 409 ], true ) === false )
					return self::_error( $status, (string) ( $body['error'] ?? 'the key could not be created' ), 'key', [ (string) $source ] );
			}

			// The slots of the page's own components stay where a save stops: nothing refuses the retry for them
			$kept = [];

			foreach( $plan['slots'] as $uri => $create ) {

				$last = (string) substr( (string) $uri, (int) strrpos( (string) $uri, '/' ) + 1 );
				$size = self::_size( (array) $create );

				$failure = self::_slot( $appData, (string) $uri, trim( (string) ( $create['label'] ?? '' ) ) !== '' ? trim( (string) $create['label'] ) : ucfirst( $last ), $size[0], $size[1], $kept );

				if( $failure !== null )
					return $failure;
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
		 *	was written: the same columns, in each the same stack or none, the
		 *	same components with the same sources
		 *
		 *	@param		array			$posted				The section of the model
		 *	@param		array			$read					The section the Reader made of what the Writer wrote
		 *
		 *	@return 	bool
		 */
		private static function _readsBack( array $posted, array $read ): bool {

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

				$components = array_values( (array) ( $col['components'] ?? [] ) );

				if( count( $components ) !== count( $other['components'] ) )
					return false;

				foreach( $components as $at => $component )
					if( (string) ( $component['name'] ?? '' ) !== $other['components'][$at]['name'] || (string) ( $component['source'] ?? '' ) !== $other['components'][$at]['source'] )
						return false;
			}

			return true;
		}

		/**
		 *	What is wrong with the shape of a model before anything in it is
		 *	read: a name that is no text, a block that is no block, a call
		 *	that is no call - what the Writer would take for something else
		 *	or stumble over
		 *
		 *	@param		array			$model
		 *
		 *	@return 	array										Sentences, in English
		 */
		private static function _shape( array $model ): array {

			$problems	= [];
			$text			= static fn( mixed $value ): bool => $value === null || is_string( $value ) === true;

			foreach( [ 'name', 'vpa', 'header', 'footer' ] as $key )
				if( $text( $model[$key] ?? null ) === false )
					$problems[] = 'the '. $key. ' is no text';

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

				if( ( $block['background'] ?? null ) !== null && ( is_array( $block['background'] ) === false || $text( $block['background']['slot'] ?? null ) === false ) )
					$problems[] = $at. ' has a background that is no image slot';

				foreach( is_array( $block['cols'] ?? [] ) === true ? $block['cols'] : [] as $col ) {

					if( is_array( $col ) === false || is_array( $col['components'] ?? [] ) === false || is_array( $col['width'] ?? [] ) === false || is_array( $col['hidden'] ?? [] ) === false ) {
						$problems[] = $at. ' has a column that is none';
						continue;
					}

					$calls = $col['components'] ?? [];

					if( ( $col['stack'] ?? null ) !== null )
						$calls[] = $col['stack'];

					foreach( $calls as $call )
						if( is_array( $call ) === false || $text( $call['name'] ?? null ) === false || $text( $call['source'] ?? null ) === false || $text( $call['text'] ?? null ) === false
							|| is_array( $call['attributes'] ?? [] ) === false || array_filter( (array) ( $call['attributes'] ?? [] ), static fn( mixed $value ): bool => $value !== null && is_scalar( $value ) === false ) !== [] )
							$problems[] = $at. ' has a call that is none';
				}
			}

			return $problems;
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
			return preg_match( '/^[^\r\n<>\[\]]{1,160}$/u', $name ) === 1 && str_contains( $name, '-->' ) === false;
		}

		/**
		 *	The slug of a name: lower case, the letters of the languages the
		 *	workbench speaks written out, everything else a hyphen
		 *
		 *	@param		string		$name
		 *
		 *	@return 	string										'' where nothing is left
		 */
		private static function _slug( string $name ): string {

			$slug = strtr( mb_strtolower( $name, 'UTF-8' ), [ 'ä' => 'ae', 'ö' => 'oe', 'ü' => 'ue', 'ß' => 'ss', 'é' => 'e', 'è' => 'e', 'ê' => 'e', 'à' => 'a', 'á' => 'a', 'â' => 'a', 'í' => 'i', 'ó' => 'o', 'ô' => 'o', 'ú' => 'u', 'ñ' => 'n', 'ç' => 'c' ] );
			$slug = trim( (string) preg_replace( '/[^a-z0-9]+/', '-', $slug ), '-' );

			return rtrim( substr( $slug, 0, 60 ), '-' );
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
