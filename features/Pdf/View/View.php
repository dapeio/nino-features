<?php
declare(strict_types=1);
/**
 *	Nino								A compact filesystembased php framework
 *	Modules\Pdf\View		What a screen of the Pdf feature draws, as data
 *
 *	@package						Dape/Nino
 *	@author							David Perchermeier <mail@dape.io>
 *	@link								https://github.com/dapeio/nino
 */
namespace Nino\Modules\Pdf {

	use Nino\Modules\Pdf as Feature;

	/**
	 *	Nino							A compact filesystembased php framework
	 *	Pdf\View					The four answers a screen of the layout, of the text blocks and of the
	 *										templates is drawn from. They are the public interface of the feature
	 *										to a package that brings a screen of its own: the panel of the workbench
	 *										answers its actions with them, and so does such a package, so that both
	 *										draw the same data. Each is read only, takes the app data and answers an
	 *										array that is fit for JSON.
	 *
	 *										None of them checks a right: a caller that draws a screen of them checks
	 *										\Nino\Modules\Pdf::PERM first. What they hold is the whole of the layout and
	 *										the library - layout() carries the logo and the signature as data addresses
	 *										and the header and the footer, blocks() every text block of the library - and
	 *										is for the account that holds that permission alone.
	 *
	 *										  layout( $appData )      the layout, the pictures as data addresses, the papers, the fonts, the feature's rules, the ranges
	 *										  blocks( $appData )      the library as a list, and the ranges of a block
	 *										  templates( $appData )   the templates as a list, with what the editor offers
	 *										  offer( $appData )       tags, library, paper and ranges of the template editor
	 *
	 *	@package					Dape/Nino
	 *	@author						David Perchermeier <mail@dape.io>
	 *	@link							https://github.com/dapeio/nino
	 */
	class View {

		/**
		 *	The layout and what the screen needs to edit it: the pictures as data addresses (a picture of
		 *	a megabyte or less), the papers, the fonts, the feature's rules as CSS, and the ranges the server holds the numbers to
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	array
		 */
		public static function layout( array &$appData ): array {

			$layout = Feature::layout( $appData );
			$owned = \Nino\Modules\Pdf\Fonts\Metrics::owned();
			$ignored = \Nino\Modules\Pdf\Fonts\Metrics::ignored();
			$images = [];

			foreach( [ 'logo', 'signature' ] as $kind ) {

				$bytes = Feature::picture( $appData, $kind );
				$images[$kind] = $bytes !== '' && strlen( $bytes ) <= 1048576 ? 'data:image/'. ( str_ends_with( $layout[$kind], '.png' ) === true ? 'png' : 'jpeg' ). ';base64,'. base64_encode( $bytes ) : '';
			}

			// The fonts of the owner's folder are in the answer only where there are some, and the files left out where there are any
			return [
				'layout'	=> $layout,
				'sheet'		=> Feature::sheet( $appData ),
				'refusal'	=> self::_refusal( $layout['design'] ),
				'images'	=> $images,
				'papers'	=> array_keys( \Nino\Modules\Pdf\Layout\Engine::PAPER ),
				'fonts'		=> \Nino\Modules\Pdf\Fonts\Metrics::FAMILIES,
				'ranges'	=> [ 'margin' => [ Feature::MARGIN_MIN, Feature::MARGIN_MAX ], 'size' => [ Feature::SIZE_MIN, Feature::SIZE_MAX ], 'band' => Feature::MAX_BAND, 'design' => Feature::MAX_DESIGN ],
			] + ( $owned === [] ? [] : [ 'owned' => $owned ] ) + ( $ignored === [] ? [] : [ 'ignored' => array_slice( array_keys( $ignored ), 0, 20 ) ] );
		}

		/**
		 *	Why a stored design is not read: the code and the params of the refusal, as a save refuses the same text - the panel says
		 *	them in its own language - and null where the text is a sheet
		 *
		 *	@param		string		$design
		 *
		 *	@return 	array|null
		 */
		private static function _refusal( string $design ): ?array {

			$read = \Nino\Modules\Pdf\Layout\Sheet::check( $design );

			return isset( $read['rules'] ) === true ? null : Store::refusal( $read );
		}

		/**
		 *	The library, as a list for the screen
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	array
		 */
		public static function blocks( array &$appData ): array {

			$blocks = [];

			foreach( Feature::blocks( $appData ) as $key => $block )
				$blocks[] = [ 'key' => $key, 'title' => $block['title'], 'html' => $block['html'] ];

			return [ 'blocks' => $blocks, 'ranges' => [ 'title' => Feature::MAX_TITLE, 'html' => Feature::MAX_HTML ] ];
		}

		/**
		 *	The templates, as a list for the screen: key, name, tag, how many blocks, and whether the tag
		 *	is still one of the project's
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	array
		 */
		public static function templates( array &$appData ): array {

			$tags = Feature::tags( $appData );
			$list = [];

			foreach( Feature::templates( $appData ) as $key => $template )
				$list[] = [ 'key' => $key, 'name' => $template['name'], 'tag' => $template['tag'], 'blocks' => count( Feature::template( $appData, $key )['blocks'] ?? [] ), 'known' => $template['tag'] === '' || in_array( $template['tag'], $tags, true ) ];

			return [ 'templates' => $list ] + self::offer( $appData );
		}

		/**
		 *	What the template editor offers: the tags, the blocks of the library, the papers' size in mm
		 *	and the limits of the fields
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	array
		 */
		public static function offer( array &$appData ): array {

			$library = [];

			foreach( Feature::blocks( $appData ) as $key => $block )
				$library[] = [ 'key' => $key, 'title' => $block['title'] ];

			return [
				'tags'		=> Feature::tags( $appData ),
				'library'	=> $library,
				'paper'		=> \Nino\Modules\Pdf\Layout\Engine::PAPER[ Feature::layout( $appData )['format'] ],
				'ranges'	=> [ 'title' => Feature::MAX_TITLE, 'html' => Feature::MAX_HTML, 'label' => Feature::MAX_LABEL, 'blocks' => Feature::MAX_BLOCKS ],
			];
		}
	}
}
