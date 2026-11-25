<?php
declare(strict_types=1);
/**
 *	Nino								A compact filesystembased php framework
 *	Modules\Pdf					A PDF builder and the render API of the Pdf feature
 *
 *	@package						Dape/Nino
 *	@author							David Perchermeier <mail@dape.io>
 *	@link								https://github.com/dapeio/nino
 */
namespace Nino\Modules {

	use Nino\Modules\Pdf\Document\Document;
	use Nino\Modules\Pdf\Images\Image;
	use Nino\Modules\Pdf\Layout\Engine;
	use Nino\Modules\Pdf\Layout\Sheet;
	use Nino\Modules\Pdf\Layout\Tokens;
	use Nino\Modules\Pdf\Store;

	/**
	 *	Nino							A compact filesystembased php framework
	 *	Pdf								PDF files written by the feature itself - no library: a layout
	 *										the owner sets once, a library of text blocks and templates
	 *										composed of them, and render(), which turns a template and what
	 *										a caller knows into the bytes of a PDF.
	 *
	 *										The data is the project's, below /data/pdf/: layout.php (the
	 *										paper, the margins, the font and size, the header and footer
	 *										HTML, the owner's design rules in CSS, the file names of the logo
	 *										and of the owner's signature),
	 *										blocks.php (key => title and html), templates/<key>.php (a name,
	 *										a tag of the project's list and the blocks of the template, see
	 *										template()) and images/ (the logo and the signature).
	 *
	 *										The API, all of it static:
	 *
	 *										  render( $appData, $template, $data, $options )   the bytes, or null
	 *										  templates( $appData, $tag )                      key => name, tag, changed (the day, Y-m-d)
	 *										  fields( $appData, $template )                    the signature blocks
	 *										  inputs( $appData, $template )                    the input blocks
	 *										  tags( $appData )                                 the project's tags
	 *										  layout( $appData )                               the layout, defaults in
	 *										  design( $appData )                               the rules a PDF is set with: the feature's, the owner's over them
	 *										  sheet( $appData )                                the feature's own rules as CSS text
	 *										  install( $appData, $templates, $blocks, $layout ) add what a caller brings, add-only
	 *
	 *										What a package that brings a screen of its own may build on - the public interface, which
	 *										the panel of the workbench uses itself:
	 *
	 *										  \Nino\Modules\Pdf\Store    the eight writers: saveLayout, saveImage, deleteImage, saveBlock,
	 *										                            deleteBlock, saveTemplate, duplicateTemplate, deleteTemplate - each
	 *										                            answers [ ok, ... ] or [ fail ], the arguments of \Nino\Http::fail()
	 *										  \Nino\Modules\Pdf\View     layout, blocks, templates, offer: the data of the screens, read only
	 *										  preview( $appData, $posted, $data )     the bytes of an unsaved template, or fail
	 *										  previewLayout( $appData, $data )        the bytes of the layout's own preview
	 *										  PERM                                    the permission of the panel and of every screen of it
	 *										  uses( $appData )                        who uses which template - asks /pdf/template/uses
	 *										  vocabulary( $appData )                  the tokens, slots and sample data of the callers - asks /pdf/vocabulary
	 *										  editorAddress( $appData, $about )       where the editor of a template or a block is - asks /pdf/editor
	 *
	 *										Three callbacks are asked, each listener on its own with a fresh argument, in the
	 *										order they are registered; one that throws or answers what is no answer is named in a
	 *										warning and its answer is dropped.
	 *
	 *										  /pdf/template/uses   [ 'uses' => [] ]; a listener answers [ 'uses' => [ template key => [ package => count ] ] ],
	 *										                       a package a slug, a count a whole number from 0. Deleting a template is refused
	 *										                       while a count is above nought, and while a listener answered what is no answer
	 *										  /pdf/vocabulary      [ 'groups' => [], 'slots' => [], 'sample' => [] ]; a listener answers the same: groups
	 *										                       [ key, label, tokens: [ [ token, label ] ] ], slots [ name, label ], sample token => text,
	 *										                       a label a map of locale => text; what is invalid is left out, the rest stays
	 *										  /pdf/editor          [ surface, part, key, tag, href, inside ]; a listener answers href - a path of the
	 *										                       site or a hash, never a scheme or a host - and inside, whether it opens in the screen that asked
	 *
	 *										A document is laid out by \Nino\Modules\Pdf\Layout\Engine from the
	 *										HTML of its blocks - a small subset, see \Nino\Modules\Pdf\Layout\Html -
	 *										and written by \Nino\Modules\Pdf\Document\Document, with the
	 *										standard fonts and no library. Two renders of the same template
	 *										with the same arguments are the same bytes.
	 *
	 *										The one thing init() registers is a callback that lets the
	 *										workbench's own responses frame a blob: address, which is how the
	 *										panel shows a preview. Nothing of it reaches a visitor, and the
	 *										feature has no route and puts nothing on the site.
	 *
	 *	@package					Dape/Nino
	 *	@author						David Perchermeier <mail@dape.io>
	 *	@link							https://github.com/dapeio/nino
	 */
	class Pdf {

		public const string KEY = 'pdf';

		// What the feature keeps, below /data/ (the manifest names the directory)
		public const string DIR = '/data/pdf';
		public const string LAYOUT = '/data/pdf/layout.php';
		public const string BLOCKS = '/data/pdf/blocks.php';
		public const string TEMPLATES = '/data/pdf/templates';
		public const string IMAGES = '/data/pdf/images';

		// A key of a template or of a block, and a tag: a slug of 2 to 40 characters
		public const string SLUG = '/^[a-z][a-z0-9-]{1,39}$/D';

		// The permission of the panel and of every screen a package draws of the feature
		public const string PERM = '/_admin/pdf/manage';

		// The callbacks the feature asks, see the docblock of the class
		public const string USES = '/pdf/template/uses';
		public const string VOCABULARY = '/pdf/vocabulary';
		public const string EDITOR = '/pdf/editor';

		// A token as [[token]] reads it - a key with slashes, or a name with dots: TOKEN is the whole, anchored pattern a package may match a text with, TOKEN_PART its fragment, which the engine builds its own pattern from
		public const string TOKEN_PART = '\/[A-Za-z0-9_.\/-]{1,200}|[A-Za-z][A-Za-z0-9_-]*(?:\.[A-Za-z0-9_-]+)*';
		public const string TOKEN = '/^(?:'. self::TOKEN_PART. ')$/D';

		// What an answer to /pdf/vocabulary may hold: groups, tokens of a group, slots, sample values, and the characters of a sample value
		public const int MAX_GROUPS = 20;
		public const int MAX_GROUP_TOKENS = 100;
		public const int MAX_SLOTS = 20;
		public const int MAX_SAMPLES = 500;
		public const int MAX_SAMPLE = 500;

		// An address a listener of /pdf/editor may give: a path of the site or a hash, up to this many characters
		public const int MAX_ADDRESS = 500;
		public const string ADDRESS = '~^(?:/(?![/\\\\])|\#)[A-Za-z0-9\-._\~!$&()*+,;=:@/?#%]*$~D';

		// What the tokens of a preview may weigh: the length of their texts and keys, and 32 for each entry - the largest sample a vocabulary may hold (MAX_SAMPLES of them, a key of 200 characters and a text of MAX_SAMPLE each) with a thousand for the nodes around it
		public const int MAX_DATA = self::MAX_SAMPLES * ( 32 + 200 + self::MAX_SAMPLE ) + 1000;

		// What a layout holds when nothing is saved: the paper, the margins in mm (top, right, bottom, left), the font, the base size in pt, the owner's rules over the feature's sheet (CSS, see Sheet)
		public const array DEFAULTS = [
			'format'		=> 'a4',
			'margins'		=> [ 20, 20, 20, 20 ],
			'font'			=> 'helvetica',
			'size'			=> 10,
			'header'		=> '',
			'footer'		=> '',
			'design'		=> '',
			'logo'			=> '',
			'signature'	=> '',
		];

		public const int MARGIN_MIN = 5;
		public const int MARGIN_MAX = 50;
		public const int SIZE_MIN = 8;
		public const int SIZE_MAX = 14;
		public const int MAX_BAND = 4000;
		public const int MAX_DESIGN = 8000;
		public const int MAX_TITLE = 120;
		public const int MAX_HTML = 20000;
		public const int MAX_LABEL = 120;
		public const int MAX_BLOCKS = 60;

		// The types of a template's blocks
		public const array TYPES = [ 'html', 'slot', 'input', 'signature', 'pagebreak' ];

		// The file a picture of the layout is kept in: logo.png, signature.jpg
		public const string PICTURE = '/^(logo|signature)\.(png|jpg)$/D';

		/**
		 *	Registers what the workbench needs to show a preview, and nothing else
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	void
		 */
		public static function init( array &$appData ): void {

			\Nino\Callbacks::registerCallback( $appData, '/nino/http/output', [ self::class, 'callbackOutput' ] );
		}

		/**
		 *	Let the workbench frame the blob: address a preview is shown at. The policy of the workbench
		 *	has no frame-src, so its default-src 'self' refuses the frame - a browser does not count
		 *	blob: as 'self' - and the preview stays empty. frame-src is widened by that one scheme, for
		 *	the responses of /_admin and below alone; a page of the site keeps its policy byte for byte
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$request			(reference) Current request and its response
		 *
		 *	@return 	void
		 */
		public static function callbackOutput( array &$appData, array &$request ): void {

			foreach( [ $request['/nino/http/request']['uri'] ?? '', $request['/nino/http/response']['uri'] ?? '' ] as $uri )
				if( is_string( $uri ) === true && ( $uri === '/_admin' || str_starts_with( $uri, '/_admin/' ) === true ) ) {
					\Nino\Http::allowSource( $request, 'frame-src', 'blob:' );
					return;
				}
		}

		/**
		 *	The panel, while the feature is active
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	array										Panel class names
		 */
		public static function adminPanels( array &$appData ): array {
			return [ \Nino\Modules\Pdf\Admin::class ];
		}

		/**
		 *	The bytes of a PDF
		 *
		 *	$data: tokens (name => string, nested arrays allowed, what [[name]] and [[a.b]] stand for -
		 *	text, whatever it holds; and the slash keys of the caller, flat - '/customer/name' => 'Ada' -
		 *	which [[/customer/name]] stands for before a text of the site of the same key does), slots (name => html, what a slot block holds - held to the list of
		 *	\Nino\Modules\Pdf\Layout\Allow when the document is laid out, as a text a person saved is),
		 *	inputs (name => text, what an input block holds). $options: signatures (name => the bytes
		 *	of a PNG or JPEG or the absolute path of one, drawn in the box of the signature block of that
		 *	name), title, date (Y-m-d: the only date the file names), append (HTML, held to the list of
		 *	\Nino\Modules\Pdf\Layout\Allow like a slot: laid out after the last block on a page of its
		 *	own, with the header and the footer of the layout, and counted by [[pages]]; the signature
		 *	fields stay on the pages of the template - the last page is the last before it - and
		 *	nothing is added where it is empty or holds nothing to draw).
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$template			The key of a template
		 *	@param		array 		$data
		 *	@param		array 		$options
		 *
		 *	@return 	string|null									Null, with an E_USER_WARNING that says why, for a template that is not there
		 */
		public static function render( array &$appData, string $template, array $data = [], array $options = [] ): ?string {

			$loaded = self::template( $appData, $template );

			if( $loaded === null ) {
				trigger_error( 'Pdf::render(): there is no template "'. $template. '"', E_USER_WARNING );
				return null;
			}

			return self::compose( $appData, $loaded, $data, $options, false );
		}

		/**
		 *	The templates, by key, sorted by name
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string|null	$tag					Only the templates of this tag
		 *
		 *	@return 	array											key => [ name, tag, changed ], changed the day (Y-m-d, UTC) the file of the template was written, '' where the file has none
		 */
		public static function templates( array &$appData, ?string $tag = null ): array {

			$list = [];

			foreach( self::keys( $appData ) as $key ) {

				$template = self::template( $appData, $key );

				if( $template !== null && ( $tag === null || $template['tag'] === $tag ) )
					$list[$key] = [ 'name' => $template['name'], 'tag' => $template['tag'], 'changed' => self::_day( @filemtime( \Nino\Filesystem::path( $appData, self::TEMPLATES. '/'. $key. '.php' ) ) ) ];
			}

			uksort( $list, static fn( string $a, string $b ): int => [ mb_strtolower( $list[$a]['name'] ), $a ] <=> [ mb_strtolower( $list[$b]['name'] ), $b ] );

			return $list;
		}

		/**
		 *	The signature blocks of a template, in order
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$template
		 *
		 *	@return 	array											name, party, label, page, x, y, w, h, line each; [] for a template that is not there
		 */
		public static function fields( array &$appData, string $template ): array {

			$fields = [];

			foreach( self::template( $appData, $template )['blocks'] ?? [] as $block )
				if( $block['type'] === 'signature' )
					$fields[] = array_diff_key( $block, [ 'type' => 0 ] );

			return $fields;
		}

		/**
		 *	The input blocks of a template, in order
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$template
		 *
		 *	@return 	array											name, label, multiline, required each; [] for a template that is not there
		 */
		public static function inputs( array &$appData, string $template ): array {

			$inputs = [];

			foreach( self::template( $appData, $template )['blocks'] ?? [] as $block )
				if( $block['type'] === 'input' )
					$inputs[] = array_diff_key( $block, [ 'type' => 0 ] );

			return $inputs;
		}

		/**
		 *	The tags of the project, which the setting lists - the lines that are slugs
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	array											A list of slugs
		 */
		public static function tags( array &$appData ): array {

			return array_values( array_filter( (array) \Nino\Features::setting( $appData, self::KEY, 'tags', [] ), static fn( mixed $tag ): bool => is_string( $tag ) === true && preg_match( self::SLUG, $tag ) === 1 ) );
		}

		/**
		 *	The layout with its defaults filled in: what is saved where it is valid, the default where it is not
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	array											format, margins, font, size, header, footer, logo, signature
		 */
		public static function layout( array &$appData ): array {

			\Nino\Modules\Pdf\Fonts\Metrics::load( $appData );
			$stored = \Nino\Filesystem::getFileContent( $appData, self::LAYOUT, [] );
			$stored = is_array( $stored ) === true ? $stored : [];
			$layout = self::DEFAULTS;

			if( in_array( $stored['format'] ?? null, array_keys( Engine::PAPER ), true ) === true )
				$layout['format'] = $stored['format'];

			if( in_array( $stored['font'] ?? null, \Nino\Modules\Pdf\Fonts\Metrics::families(), true ) === true )
				$layout['font'] = $stored['font'];

			if( is_int( $stored['size'] ?? null ) === true && $stored['size'] >= self::SIZE_MIN && $stored['size'] <= self::SIZE_MAX )
				$layout['size'] = $stored['size'];

			if( is_array( $stored['margins'] ?? null ) === true && count( $stored['margins'] ) === 4 && array_is_list( $stored['margins'] ) === true
				&& count( array_filter( $stored['margins'], static fn( mixed $mm ): bool => is_int( $mm ) === true && $mm >= self::MARGIN_MIN && $mm <= self::MARGIN_MAX ) ) === 4 )
				$layout['margins'] = $stored['margins'];

			foreach( [ 'header', 'footer' ] as $band )
				if( is_string( $stored[$band] ?? null ) === true && mb_strlen( $stored[$band] ) <= self::MAX_BAND )
					$layout[$band] = $stored[$band];

			// The text as it is stored, a text that is no sheet too: the owner sees it and why it is not read (see design()), and a save does not overwrite it unseen
			if( is_string( $stored['design'] ?? null ) === true )
				$layout['design'] = $stored['design'];

			foreach( [ 'logo', 'signature' ] as $kind )
				if( is_string( $stored[$kind] ?? null ) === true && preg_match( self::PICTURE, $stored[$kind], $found ) === 1 && $found[1] === $kind )
					$layout[$kind] = $stored[$kind];

			return $layout;
		}

		/**
		 *	The rules a PDF is set with: the feature's own (see Sheet::defaults(), set for the layout's font and size)
		 *	with the owner's design laid over them, property by property. A body size in em is a factor of the layout's size. A stored
	 *	design that does not parse is not read: the feature's rules stand alone
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	array											selector => property => value, see Sheet
		 */
		public static function design( array &$appData ): array {

			return self::_rules( self::layout( $appData ) );
		}

		/**
		 *	The feature's own rules as CSS text, for the layout's font and size: what the panel shows beside the owner's
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	string
		 */
		public static function sheet( array &$appData ): string {

			$layout = self::layout( $appData );

			return Sheet::text( Sheet::defaults( (string) $layout['font'], (int) $layout['size'] ) );
		}

		/**
		 *	Add what a caller brings - the templates, the blocks of the library, the header, the footer and the
		 *	design of the layout - to the project, and nothing it has: a key of a block or of a template that
		 *	exists stays, so does a field of the layout that holds a text, and the pictures are no part of it. With
		 *	$overwrite what exists is replaced. A piece that is refused is left out and named.
		 *
		 *	  $templates   key => [ name, tag (one of the project's), blocks (see template()) ]
		 *	  $blocks      key => [ title, html ]
		 *	  $layout      header, footer, design => text
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		$templates
		 *	@param		array 		$blocks
		 *	@param		array 		$layout
		 *	@param		bool			$overwrite
		 *
		 *	@return 	array											added, existing, refused - each blocks, templates, layout: the keys (the fields) written, the ones that were there, and key => code of the ones refused
		 */
		public static function install( array &$appData, array $templates, array $blocks, array $layout, bool $overwrite = false ): array {

			\Nino\Modules\Pdf\Fonts\Metrics::load( $appData );

			return \Nino\Modules\Pdf\Store::install( $appData, $templates, $blocks, $layout, $overwrite );
		}

		/**
		 *	The bytes of a template that is not saved - what an editor holds - with sample data: [[name]] for a token a caller has no
		 *	value for, the label for an input, a table for a slot, the sample text for a template with no body. Written nowhere
		 *
		 *	$posted is what the editor posts - name, tag, blocks - and is checked as a save would check it, with the tag not held to
		 *	the project's list; $data is what render() takes (tokens), so that the tokens of a vocabulary's sample are filled in.
		 *
		 *	It checks no right: a caller that draws a screen checks Pdf::PERM first. What it answers shows what the layout holds - the
		 *	signature, the logo and the text blocks of the library, whatever the template names - to whoever receives the bytes. $data's
		 *	tokens - the only part of it a preview reads - are held to MAX_DATA (the text of their strings and keys and a weight for each
		 *	entry) and refused above it; what they hold is otherwise a caller's own.
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		$posted				name, tag, blocks
		 *	@param		array 		$data					See render()
		 *
		 *	@return 	array											[ bytes ], or [ fail ] - the arguments of \Nino\Http::fail(), as Store answers a refusal
		 */
		public static function preview( array &$appData, array $posted, array $data = [] ): array {

			if( self::_weighs( $data['tokens'] ?? [] ) === false )
				return [ 'fail' => [ 413, 'the data of a preview is too large', 'pdf_data_large', [ self::MAX_DATA ], '' ] ];

			$checked = Store::template( $appData, $posted, false );

			if( isset( $checked['fail'] ) === true )
				return [ 'fail' => $checked['fail'] ];

			return [ 'bytes' => self::compose( $appData, $checked['template'], $data, [ 'title' => $checked['template']['name'] ], true ) ];
		}

		/**
		 *	The bytes of the sample text laid out with the layout and the design as they are saved. It checks no right - a caller
		 *	that draws a screen checks Pdf::PERM first - and what it answers shows the layout: the logo, the signature, the header
		 *	and the footer. The tokens of $data are held to MAX_DATA as preview() holds them
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		$data					See render()
		 *
		 *	@return 	array											[ bytes ], or [ fail ] for data that is too large
		 */
		public static function previewLayout( array &$appData, array $data = [] ): array {

			if( self::_weighs( $data['tokens'] ?? [] ) === false )
				return [ 'fail' => [ 413, 'the data of a preview is too large', 'pdf_data_large', [ self::MAX_DATA ], '' ] ];

			return [ 'bytes' => self::compose( $appData, [ 'name' => 'Design', 'tag' => '', 'blocks' => [] ], $data, [ 'title' => 'Design' ], true ) ];
		}

		/**
		 *	Who uses which template, as the callers say it: the callback /pdf/template/uses is asked of each listener on its own, with
		 *	[ 'uses' => [] ], and a listener answers [ 'uses' => [ template key => [ package => count ] ] ] - by returning it, or by
		 *	filling the argument. A package is a slug, a count a whole number from 0 or a text of digits alone (what a database gives
		 *	for COUNT), a key a slug of a template. The counts of a package that two listeners give for one template add up.
		 *
		 *	What is wrong is put on what it is wrong for. A listener that throws, answers what is no array or has no list under 'uses',
		 *	or has a key that is no slug - a list instead of a map, 'Shared', 'shared ', a number - is in 'failed': nothing can be said
		 *	for it. A listener whose key is readable but whose entry is not - packages that are no map, a package that is no slug, a
		 *	count that is no whole number from 0 - is in 'unknown' under that key alone. What it gave that is valid stands. A caller
		 *	that has to be sure that nothing uses a template - the deletion - treats a key in 'unknown' and every listener in 'failed' as unknown.
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	array											uses: template key => package => count, only counts above nought, both sorted; unknown: template key => the listeners; failed: the listeners
		 */
		public static function uses( array &$appData ): array {

			$uses = [];
			$unknown = [];
			$failed = [];

			foreach( self::_ask( $appData, self::USES, [ 'uses' => [] ] ) as $heard ) {

				$list = $heard['answer']['uses'] ?? null;

				if( is_array( $list ) === false ) {
					$failed[] = self::_named( self::USES, $heard, 'gave no list of uses' );
					continue;
				}

				foreach( $list as $key => $packages ) {

					if( is_string( $key ) === false || preg_match( self::SLUG, $key ) !== 1 ) {
						$failed[] = self::_named( self::USES, $heard, 'gave a key that is no template key' );
						continue;
					}

					if( is_array( $packages ) === false ) {
						$unknown[$key][] = self::_named( self::USES, $heard, 'gave no list of packages for "'. $key. '"' );
						continue;
					}

					foreach( $packages as $package => $given ) {

						$count = self::_count( $given );

						if( is_string( $package ) === false || preg_match( self::SLUG, $package ) !== 1 || $count === null ) {
							$unknown[$key][] = self::_named( self::USES, $heard, 'gave a package that is no slug or a count that is no whole number for "'. $key. '"' );
							continue;
						}

						if( $count > 0 ) {
							$have = $uses[$key][$package] ?? 0;
							$uses[$key][$package] = $count > PHP_INT_MAX - $have ? PHP_INT_MAX : $have + $count;
						}
					}
				}
			}

			ksort( $uses );
			ksort( $unknown );

			foreach( $uses as &$packages )
				ksort( $packages );

			return [ 'uses' => $uses, 'unknown' => array_map( static fn( array $who ): array => array_values( array_unique( $who ) ), $unknown ), 'failed' => array_values( array_unique( $failed ) ) ];
		}

		/**
		 *	What the callers' templates may use, as the callers say it: the callback /pdf/vocabulary is asked of each listener on its own,
		 *	with [ 'groups' => [], 'slots' => [], 'sample' => [] ], and a listener answers the same -
		 *
		 *	  groups   [ key (a slug), label, tokens: [ [ token, label ] ] ] - a token as [[token]] reads it, without the brackets
		 *	  slots    [ name (a slug), label ] - the names of the slot blocks the caller fills
		 *	  sample   token => text (or a number) - what a preview shows for the token; a dotted token is nested by the caller that hands it to preview()
		 *	  label    a map of locale => text, such as [ 'en_US' => 'Name', 'de_DE' => 'Name' ], the locales those of the site ('/nino/locales/available')
		 *
		 *	What is not valid - a key, a token or a name that is no slug or token or is over 200 characters, a label that is no map of texts of
		 *	1 to MAX_LABEL characters without a control character, a part over its number - is left out with what hangs on it (a token without a valid
		 *	label is no token), and the rest of the answer stands. The numbers are held where an answer is read: of each list the first entries up to its
		 *	number are looked at and no more, so that an answer of any size costs what a full one does. A text is a text: markup and [[...]] in a label
		 *	or a sample are kept as they are, to be written as text by whoever draws them. The answers add up: a group of the same key is one group
		 *	- of at most MAX_GROUP_TOKENS tokens whoever gave them - the label of the first listener stands, a token or a slot or a sample of a name that is taken is left out.
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	array											groups, slots, sample
		 */
		public static function vocabulary( array &$appData ): array {

			$locales = \Nino\Locales::getAvailableLocales( $appData );
			$groups = [];
			$slots = [];
			$sample = [];
			$seen = [];

			foreach( self::_ask( $appData, self::VOCABULARY, [ 'groups' => [], 'slots' => [], 'sample' => [] ] ) as $heard ) {

				foreach( is_array( $heard['answer']['groups'] ?? null ) === true ? array_slice( $heard['answer']['groups'], 0, self::MAX_GROUPS ) : [] as $group ) {

					$key = is_array( $group ) === true ? ( $group['key'] ?? null ) : null;
					$label = is_array( $group ) === true ? self::_label( $group['label'] ?? null, $locales ) : null;

					if( is_string( $key ) === false || preg_match( self::SLUG, $key ) !== 1 || ( isset( $groups[$key] ) === false && ( $label === null || count( $groups ) >= self::MAX_GROUPS ) ) )
						continue;

					$groups[$key] = $groups[$key] ?? [ 'key' => $key, 'label' => $label, 'tokens' => [] ];

					foreach( is_array( $group['tokens'] ?? null ) === true ? array_slice( $group['tokens'], 0, self::MAX_GROUP_TOKENS ) : [] as $token ) {

						if( count( $groups[$key]['tokens'] ) >= self::MAX_GROUP_TOKENS )
							break;

						$name = is_array( $token ) === true ? ( $token['token'] ?? null ) : null;
						$text = is_array( $token ) === true ? self::_label( $token['label'] ?? null, $locales ) : null;

						if( self::_isToken( $name ) === false || $text === null || isset( $seen[$name] ) === true )
							continue;

						$seen[$name] = true;
						$groups[$key]['tokens'][] = [ 'token' => $name, 'label' => $text ];
					}
				}

				foreach( is_array( $heard['answer']['slots'] ?? null ) === true ? array_slice( $heard['answer']['slots'], 0, self::MAX_SLOTS ) : [] as $slot ) {

					$name = is_array( $slot ) === true ? ( $slot['name'] ?? null ) : null;
					$text = is_array( $slot ) === true ? self::_label( $slot['label'] ?? null, $locales ) : null;

					if( is_string( $name ) === true && preg_match( self::SLUG, $name ) === 1 && $text !== null && isset( $slots[$name] ) === false && count( $slots ) < self::MAX_SLOTS )
						$slots[$name] = [ 'name' => $name, 'label' => $text ];
				}

				foreach( is_array( $heard['answer']['sample'] ?? null ) === true ? array_slice( $heard['answer']['sample'], 0, self::MAX_SAMPLES, true ) : [] as $token => $value ) {

					if( count( $sample ) >= self::MAX_SAMPLES )
						break;

					if( self::_isToken( $token ) === true && isset( $sample[$token] ) === false
						&& ( is_string( $value ) === true || is_int( $value ) === true || is_float( $value ) === true ) && self::_text( (string) $value, self::MAX_SAMPLE, true, true ) !== null )
						$sample[$token] = (string) $value;
				}
			}

			return [ 'groups' => array_values( array_filter( $groups, static fn( array $group ): bool => $group['tokens'] !== [] ) ), 'slots' => array_values( $slots ), 'sample' => $sample ];
		}

		/**
		 *	Where the editor of a template or of a block is: the callback /pdf/editor is asked of each listener on its own, with
		 *	[ surface, part, key, tag, href, inside ], href being the address of the panel of the workbench - a path, below the
		 *	directory the project is served from - and inside false. A listener answers by returning an array or by changing its
		 *	argument; one that returns nothing and changes nothing, or gives no href of its own (inside alone, or the very address it was
		 *	handed), has no answer and hides no later listener. The first answer with a valid href - a path of the site that starts with one slash, or a hash,
		 *	of the characters RFC 3986 allows in a path, a query and a fragment, at most MAX_ADDRESS of them, so no space, quote, bracket,
		 *	backslash, scheme or host - and, where it gives one, an inside that is a boolean, decides; an answer that has an href that is
		 *	none is named in a warning and left out.
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		$about				surface (what asks: 'crm'), part ('templates', 'blocks', 'pictures', 'layout' or 'design'), key (a template or a block), tag (of a template to be made); each a text of at most 200 characters, what is none is ''
		 *
		 *	@return 	array											href, inside
		 */
		public static function editorAddress( array &$appData, array $about ): array {

			$asked = [];

			foreach( [ 'surface', 'part', 'key', 'tag' ] as $name )
				$asked[$name] = is_string( $about[$name] ?? null ) === true && mb_strlen( $about[$name] ) <= 200 ? $about[$name] : '';

			$tab = [ 'templates' => 'pdf-templates', 'blocks' => 'pdf-blocks', 'pictures' => 'pdf-pictures' ][ $asked['part'] ] ?? '';
			$parts = in_array( $asked['part'], [ 'templates', 'blocks' ], true ) === true && preg_match( self::SLUG, $asked['key'] ) === 1 ? [ $asked['key'] ] : [];
			$asked['href'] = rtrim( \Nino\Filesystem::getDir( $appData ), '/' ). '/_admin'. \Nino\AdminAddress::of( 'pdf', $parts, $tab );
			$asked['inside'] = false;

			foreach( self::_ask( $appData, self::EDITOR, $asked ) as $heard ) {

				$href = $heard['answer']['href'] ?? null;
				$inside = $heard['answer']['inside'] ?? false;

				// Nothing answered, or an answer with no address of its own: the next listener is heard
				if( $href === null || $href === $asked['href'] )
					continue;

				if( is_string( $href ) === true && mb_strlen( $href ) <= self::MAX_ADDRESS && preg_match( self::ADDRESS, $href ) === 1 && is_bool( $inside ) === true )
					return [ 'href' => $href, 'inside' => $inside ];

				self::_named( self::EDITOR, $heard, 'answered no address of this site' );
			}

			return [ 'href' => $asked['href'], 'inside' => false ];
		}

		/**
		 *	Ask a callback of each listener on its own: every listener gets a fresh $asked and answers by returning an array or by
		 *	changing the argument; what one does cannot reach another. A listener that throws is named in a warning
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$hook
		 *	@param		array 		$asked
		 *
		 *	@return 	array											owner (the class, or the word for a function), answer (an array, or null for a listener that threw or answered what is no array), in the order of the listeners
		 */
		private static function _ask( array &$appData, string $hook, array $asked ): array {

			$heard = [];

			foreach( \Nino\Callbacks::registered( $appData, $hook ) as $callback ) {

				$owner = \Nino\Callbacks::owner( $callback ) ?? self::_function( $appData );
				$given = $asked;

				try {
					$replaced = call_user_func_array( $callback, [ &$appData, &$given ] );
				} catch( \Throwable $thrown ) {
					trigger_error( 'Pdf: the listener of '. $hook. ' ('. $owner. ') threw '. $thrown::class. ': '. $thrown->getMessage(), E_USER_WARNING );
					$heard[] = [ 'owner' => $owner, 'answer' => null ];
					continue;
				}

				$answer = $replaced ?? $given;

				if( is_array( $answer ) === false )
					trigger_error( 'Pdf: the listener of '. $hook. ' ('. $owner. ') answered no list', E_USER_WARNING );

				$heard[] = [ 'owner' => $owner, 'answer' => is_array( $answer ) === true ? $answer : null ];
			}

			return $heard;
		}

		/**
		 *	The word a listener that is no class is named by, in the language of the workbench's session
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	string
		 */
		private static function _function( array &$appData ): string {

			$word = \Nino\Admin\Admin::textFills( $appData, '/features/Pdf/text', \Nino\Admin\Admin::sessionLocale( $appData ) )['[[/_admin/pdf/label/function]]'] ?? '';

			return is_string( $word ) === true && $word !== '' ? $word : 'function';
		}

		/**
		 *	Name a listener that gave no valid answer: a warning, unless _ask() has said so already
		 *
		 *	@param		string		$hook
		 *	@param		array 		$heard				One entry of _ask()
		 *	@param		string		$why
		 *
		 *	@return 	string										The owner
		 */
		private static function _named( string $hook, array $heard, string $why ): string {

			if( $heard['answer'] !== null )
				trigger_error( 'Pdf: the listener of '. $hook. ' ('. $heard['owner']. ') '. $why, E_USER_WARNING );

			return $heard['owner'];
		}

		/**
		 *	The day a file was written, in UTC, as the project's clock and zone make no part of it
		 *
		 *	@param		int|false	$time					A modification time
		 *
		 *	@return 	string										Y-m-d, '' for a time that is none
		 */
		private static function _day( int|false $time ): string {

			return $time === false ? '' : gmdate( 'Y-m-d', $time );
		}

		/**
		 *	A count as a listener gives it: a whole number from 0, or a text of digits alone (up to 19 of them)
		 *
		 *	@param		mixed			$given
		 *
		 *	@return 	int|null									Null for anything else
		 */
		private static function _count( mixed $given ): ?int {

			if( is_int( $given ) === true )
				return $given >= 0 ? $given : null;

			return is_string( $given ) === true && preg_match( '/^[0-9]{1,19}$/D', $given ) === 1 ? (int) $given : null;
		}

		/**
		 *	Whether a value is a token as [[token]] reads it, of at most 200 characters
		 *
		 *	@param		mixed			$token
		 *
		 *	@return 	bool
		 */
		private static function _isToken( mixed $token ): bool {

			return is_string( $token ) === true && strlen( $token ) <= 200 && preg_match( self::TOKEN, $token ) === 1;
		}

		/**
		 *	Whether $data is within MAX_DATA: the length of every string and key, and a weight of 32 for every entry, counted as the
		 *	tree is read, which stops at the first entry that goes over
		 *
		 *	@param		mixed			$data
		 *
		 *	@return 	bool
		 */
		private static function _weighs( mixed $data ): bool {

			$left = self::MAX_DATA;

			return self::_spend( $data, $left, 0 );
		}

		/**
		 *	One step of _weighs()
		 *
		 *	@param		mixed			$value
		 *	@param		int				$left					What is left to spend
		 *	@param		int				$depth
		 *
		 *	@return 	bool
		 */
		private static function _spend( mixed $value, int &$left, int $depth ): bool {

			$left -= 32 + ( is_string( $value ) === true ? strlen( $value ) : 0 );

			if( $left < 0 || $depth > 8 )
				return false;

			if( is_array( $value ) === true )
				foreach( $value as $key => $one ) {

					$left -= is_string( $key ) === true ? strlen( $key ) : 0;

					if( $left < 0 || self::_spend( $one, $left, $depth + 1 ) === false )
						return false;
				}

			return true;
		}

		/**
		 *	A label as a map of locale => text: the texts that are valid, of 1 to MAX_LABEL characters, in a locale the site has
		 *
		 *	@param		mixed			$label
		 *	@param		array 		$locales			The locales of the site
		 *
		 *	@return 	array|null								Null where no text is valid
		 */
		private static function _label( mixed $label, array $locales ): ?array {

			$texts = [];

			if( is_array( $label ) === false )
				return null;

			// The languages of the site are walked and looked up, so that no more is read than the site has languages, wherever the label lists them
			foreach( $locales as $locale )
				if( is_string( $locale ) === true && is_string( $label[$locale] ?? null ) === true && self::_text( $label[$locale], self::MAX_LABEL, false, false ) !== null )
					$texts[$locale] = $label[$locale];

			return $texts === [] ? null : $texts;
		}

		/**
		 *	A text a caller gave: valid UTF-8, no control character - a line feed, a carriage return and a tab only where $lines says they may be -,
		 *	of at most $max characters, empty only where $empty says it may be
		 *
		 *	@param		string		$text
		 *	@param		int				$max
		 *	@param		bool			$empty
		 *	@param		bool			$lines
		 *
		 *	@return 	string|null
		 */
		private static function _text( string $text, int $max, bool $empty, bool $lines ): ?string {

			if( ( $text === '' && $empty === false ) || strlen( $text ) > 4 * $max || mb_strlen( $text ) > $max || mb_check_encoding( $text, 'UTF-8' ) === false || preg_match( $lines === true ? '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/' : '/[\x00-\x1F\x7F]/', $text ) === 1 )
				return null;

			return $text;
		}

		/**
		 *	The merged rules of a layout
		 *
		 *	@param		array 		$layout				See layout()
		 *
		 *	@return 	array
		 */
		private static function _rules( array $layout ): array {

			$rules = Sheet::merge( Sheet::defaults( (string) $layout['font'], (int) $layout['size'] ), Sheet::check( (string) $layout['design'] )['rules'] ?? [] );

			// The body's size is in points, whatever the owner wrote: em is a factor of the size of the layout
			$rules['body']['font-size'] = [ 'pt', Sheet::size( $rules['body'], (float) $layout['size'] ) ];

			return $rules;
		}

		/**
		 *	The text blocks of the library
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	array											key => [ title, html ], in the order of the file
		 */
		public static function blocks( array &$appData ): array {

			$stored = \Nino\Filesystem::getFileContent( $appData, self::BLOCKS, [] );
			$blocks = [];

			foreach( is_array( $stored ) === true ? $stored : [] as $key => $block )
				if( is_string( $key ) === true && preg_match( self::SLUG, $key ) === 1 && is_array( $block ) === true && is_string( $block['title'] ?? null ) === true && is_string( $block['html'] ?? null ) === true )
					$blocks[$key] = [ 'title' => $block['title'], 'html' => $block['html'] ];

			return $blocks;
		}

		/**
		 *	The keys of the templates there are, sorted
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	array
		 */
		public static function keys( array &$appData ): array {

			$dir = \Nino\Filesystem::path( $appData, self::TEMPLATES );
			$keys = [];

			foreach( is_dir( $dir ) === true ? ( @scandir( $dir ) ?: [] ) : [] as $file )
				if( str_ends_with( $file, '.php' ) === true && preg_match( self::SLUG, substr( $file, 0, -4 ) ) === 1 && is_file( $dir. '/'. $file ) === true && is_link( $dir. '/'. $file ) === false )
					$keys[] = substr( $file, 0, -4 );

			return $keys;
		}

		/**
		 *	One template as the file holds it, each block rebuilt from what it may hold.
		 *
		 *	The template is [ name, tag, blocks ]. A block is [ type => ... ] of one of TYPES:
		 *
		 *	  html       block (the key of a block of the library) or html (a text of its own) - never both
		 *	  slot       name
		 *	  input      name, label, multiline, required
		 *	  signature  name, party (owner or member), label, page (last, or a number from 1),
		 *	             x, y, w, h (mm, one decimal), line
		 *	  pagebreak
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$key
		 *
		 *	@return 	array|null								Null for a key that is none or a file that is not a template
		 */
		public static function template( array &$appData, string $key ): ?array {

			if( preg_match( self::SLUG, $key ) !== 1 )
				return null;

			$path = \Nino\Filesystem::path( $appData, self::TEMPLATES. '/'. $key. '.php' );

			// is_file() first: reading a .php file through the Filesystem is an include
			if( $path === '' || is_file( $path ) === false || is_link( $path ) === true )
				return null;

			$raw = \Nino\Filesystem::getFileContent( $appData, self::TEMPLATES. '/'. $key. '.php', null );

			if( is_array( $raw ) === false || is_string( $raw['name'] ?? null ) === false || is_string( $raw['tag'] ?? null ) === false )
				return null;

			$blocks = [];

			foreach( is_array( $raw['blocks'] ?? null ) === true ? $raw['blocks'] : [] as $block ) {

				$clean = \Nino\Modules\Pdf\Store::block( $block );

				if( is_array( $clean ) === true )
					$blocks[] = $clean;
			}

			return [ 'name' => $raw['name'], 'tag' => $raw['tag'], 'blocks' => $blocks ];
		}

		/**
		 *	The bytes of a layout picture, '' where there is none
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$kind					logo or signature
		 *
		 *	@return 	string
		 */
		public static function picture( array &$appData, string $kind ): string {

			$file = self::layout( $appData )[$kind] ?? '';

			return is_string( $file ) === true && $file !== '' ? (string) \Nino\Filesystem::getFileContent( $appData, self::IMAGES. '/'. $file, '' ) : '';
		}

		/**
		 *	A template, as it stands, made into a PDF
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		$template			See template()
		 *	@param		array 		$data					See render()
		 *	@param		array 		$options			See render()
		 *	@param		bool			$preview			Sample data where the caller has none: [name] for a token, the label for an input, a table for a slot, the sample text for a document without a body
		 *
		 *	@return 	string
		 */
		public static function compose( array &$appData, array $template, array $data, array $options, bool $preview ): string {

			$layout = self::layout( $appData );
			$library = self::blocks( $appData );
			$tokens = is_array( $data['tokens'] ?? null ) === true ? $data['tokens'] : [];
			$slots = is_array( $data['slots'] ?? null ) === true ? $data['slots'] : [];
			$inputs = is_array( $data['inputs'] ?? null ) === true ? $data['inputs'] : [];
			$signatures = is_array( $options['signatures'] ?? null ) === true ? $options['signatures'] : [];
			$parts = [];
			$fields = [];
			$owner = false;

			foreach( $template['blocks'] as $block ) {

				switch( $block['type'] ) {

					case 'html':
						$html = isset( $block['block'] ) === true ? (string) ( $library[ $block['block'] ]['html'] ?? '' ) : (string) ( $block['html'] ?? '' );
						$parts[] = [ 'k' => 'html', 'html' => Tokens::fill( $appData, $html, $tokens, $preview ) ];
						break;

					case 'slot':
						$html = $preview === true ? \Nino\Feature::template( $appData, '/features/Pdf/templates', 'preview-slot' ) : ( is_string( $slots[ $block['name'] ] ?? null ) === true ? $slots[ $block['name'] ] : '' );
						$parts[] = [ 'k' => 'html', 'html' => $html ];
						break;

					case 'input':
						$text = is_string( $inputs[ $block['name'] ] ?? null ) === true ? $inputs[ $block['name'] ] : '';
						$parts[] = [ 'k' => 'text', 'text' => $preview === true ? '['. $block['label']. ']' : $text, 'multiline' => $block['multiline'] ];
						break;

					case 'signature':
						$image = self::_picture( $signatures[ $block['name'] ] ?? null );

						// The owner's signature is decoded once, however many fields of the owner there are
						if( $image === null && $block['party'] === 'owner' ) {
							$owner = $owner === false ? Image::fromBytes( self::picture( $appData, 'signature' ) ) : $owner;
							$image = $owner;
						}

						if( $block['page'] === 'last' )
							$parts[] = [ 'k' => 'mark' ];

						$fields[] = [ 'page' => $block['page'], 'x' => $block['x'], 'y' => $block['y'], 'w' => $block['w'], 'h' => $block['h'], 'line' => $block['line'], 'label' => $block['label'], 'image' => $image ];
						break;

					case 'pagebreak':
						$parts[] = [ 'k' => 'break' ];
						break;
				}
			}

			// A preview of a template with no body shows the layout around some text
			if( $preview === true && array_filter( $parts, static fn( array $part ): bool => $part['k'] !== 'break' ) === [] )
				$parts[] = [ 'k' => 'html', 'html' => \Nino\Feature::template( $appData, '/features/Pdf/templates', 'preview-sample' ) ];

			// The part a caller appends: its own page after the last block, whatever the blocks were
			if( is_string( $options['append'] ?? null ) === true && trim( $options['append'] ) !== '' )
				$parts[] = [ 'k' => 'append', 'html' => $options['append'] ];

			$date = is_string( $options['date'] ?? null ) === true ? $options['date'] : '';
			$parsed = $date === '' ? false : \DateTimeImmutable::createFromFormat( '!Y-m-d', $date );
			$date = $parsed !== false && $parsed->format( 'Y-m-d' ) === $date ? $date : '';

			$header = Tokens::fill( $appData, $layout['header'], $tokens, $preview, true );
			$footer = Tokens::fill( $appData, $layout['footer'], $tokens, $preview, true );

			// The logo is decoded when some HTML of the document names it
			$logo = null;

			foreach( array_merge( [ $header, $footer ], array_column( $parts, 'html' ) ) as $html )
				if( str_contains( strtolower( $html ), 'pdf:logo' ) === true ) {
					$logo = Image::fromBytes( self::picture( $appData, 'logo' ) );
					break;
				}

			$document = new Document( self::_producer(), is_string( $options['title'] ?? null ) === true ? $options['title'] : '', $date );

			( new Engine( $document, $layout, self::_rules( $layout ), $logo ) )->run( $parts, $header, $footer, $fields );

			return $document->output();
		}

		/**
		 *	A picture a caller handed in: the bytes of a PNG or a JPEG, or the absolute path of a file
		 *
		 *	@param		mixed			$given
		 *
		 *	@return 	Image|null
		 */
		private static function _picture( mixed $given ): ?Image {

			if( is_string( $given ) === false || $given === '' )
				return null;

			if( str_starts_with( $given, "\x89PNG" ) === false && str_starts_with( $given, "\xFF\xD8" ) === false ) {

				if( preg_match( '#^(/|[A-Za-z]:[/\\\\])#', $given ) !== 1 || str_contains( $given, "\0" ) === true || is_file( $given ) === false || (int) @filesize( $given ) > \Nino\Images::MAX_UPLOAD_BYTES )
					return null;

				$given = (string) @file_get_contents( $given );
			}

			return Image::fromBytes( $given );
		}

		/**
		 *	The /Producer of a file: the framework and the feature, with no version - a file made again after an update
		 *	of the feature is the same bytes as before it, so that a document kept with its hash can be checked
		 *
		 *	@return 	string
		 */
		private static function _producer(): string {

			return 'Nino PDF';
		}
	}
}
