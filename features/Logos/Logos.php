<?php
declare(strict_types=1);
/**
 *	Nino								A compact filesystembased php framework
 *	Modules\Logos				see _nino/Nino/Modules/Modules.php for the
 *											package-level docblock
 *
 *	@package						Dape/Nino
 *	@author							David Perchermeier <mail@dape.io>
 *	@link								https://github.com/dapeio/nino
 */
namespace Nino\Modules {

	/**
	 *	Nino							A compact filesystembased php framework
	 *	Logos							A row of logos - the customers a site is proud of - drawn
	 *										from the elements of one type.
	 *
	 *										The row is a loop of the Components module, a stack of the
	 *										cell none: the data (the type, the sort, the limit, the
	 *										query) are the kernel's, as they are in [elements], and the
	 *										call names the fields of the type that make a logo - its
	 *										picture, its address, its name and the picture for a dark
	 *										ground - as the Builder offers them. The feature writes no
	 *										markup of the picture and reads no file of its own: each
	 *										picture is the kernel's own [image] with an image field of
	 *										the element as its source.
	 *
	 *										A logo can have a second picture for a dark ground. It is
	 *										written beside the first in the kernel's logo set, the
	 *										two classes Nino.css shows by the ground the set stands
	 *										on, so nothing in this class knows a ground.
	 *
	 *										The class has no init(): the manifest declares the stack
	 *										and the Components module registers it.
	 *
	 *										The list is written twice: once to be read, and once more
	 *										as a copy a screen reader skips and a stylesheet shows
	 *										only where the look is a moving band. The look is the
	 *										Design feature's to choose, after the page is written,
	 *										so the markup carries the copy for every look and the
	 *										stylesheet hides it where nothing moves.
	 *
	 *	@package					Dape/Nino
	 *	@author						David Perchermeier <mail@dape.io>
	 *	@link							https://github.com/dapeio/nino
	 */
	class Logos {

		// Where this feature's own templates are, as \Nino\Filesystem resolves
		// them: /features is the installed features directory, wherever
		// NINO_FEATURES_DIR put it
		public const string TEMPLATES = '/features/Logos/templates';

		/**
		 *	[logos /logo image="image" link="url"] - the elements of a type as a row of logos.
		 *
		 *	The call names the fields: image the picture, link the address, name the text of the
		 *	picture and dark the picture for a dark ground. A logo without a picture is left out,
		 *	and so is one whose image is no image field of the type. A link, a name or a dark that
		 *	is no field of the type is nothing - no link, no alt text, no second picture - and no
		 *	other field is read in its place.
		 *
		 *	A logo with an address is a link where the address is an https, http or mailto address
		 *	or a path of the site, and where the call does not say links="0". Anything else - a
		 *	javascript: address, a protocol-relative one - is drawn without the link, not with a
		 *	changed one.
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			$args					What the Components module made ready: the type as source, the loop's
		 *																	arguments, image, link, name, dark, height, links and class
		 *
		 *	@return 	string								The row, or '' where there is nothing to draw
		 */
		public static function stackLogos( array &$appData, array $args ): string {

			$links	= \Nino\Feature::flag( $args, 'links', true );
			$items	= [];
			$copy		= [];

			// One run of the loop makes both lists: each logo is written twice, as an item and as its copy
			\Nino\Modules\Components::renderStack( $appData, $args, static function( string $inner, array $element ) use ( &$appData, $args, $links, &$items, &$copy ): string {

				$model		= (array) ( \Nino\Modules\Components::element( $appData )['model'] ?? [] );
				$pictures	= self::_pictures( $appData, $args, $element, $model );

				if( $pictures === '' )
					return '';

				$href = $links === true ? self::href( self::_field( $element, $model, (string) $args['link'] ) ) : '';

				$items[]	= self::_item( $appData, $pictures, $href, false );
				$copy[]		= self::_item( $appData, $pictures, $href, true );

				return '';
			} );

			if( $items === [] )
				return '';

			return str_replace(
				[ '[[classes]]', '[[height]]', '[[count]]', '[[items]]', '[[copy]]' ],
				[ \Nino\Feature::classes( $args ), (string) $args['height'], (string) count( $items ), implode( '', $items ), implode( '', $copy ) ],
				\Nino\Feature::template( $appData, self::TEMPLATES, 'logos' )
			);
		}

		/**
		 *	The pictures of one logo: the field image as the light one and, where the call names one,
		 *	the field dark as the picture for a dark ground, each through the kernel's [image] - the
		 *	size of the file it stored, the alt text the name gives
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			$args					The arguments of the stack
		 *	@param		array			$element			The element
		 *	@param		array			$model				The model of its type
		 *
		 *	@return 	string								'' where the logo has no picture of the field image
		 */
		private static function _pictures( array &$appData, array $args, array $element, array $model ): string {

			if( self::_isImage( $model, (string) $args['image'] ) === false )
				return '';

			$alt			= self::_name( $element, $model, (string) $args['name'] );
			$pictures	= '';

			foreach( [ [ 'image', 'logos-picture' ], [ 'dark', 'logos-picture-dark' ] ] as [ $attribute, $template ] ) {

				// The second picture is another field than the first: a dark that names the field of image is no second picture
				if( self::_isImage( $model, (string) $args[$attribute] ) === false || ( $attribute === 'dark' && $args['dark'] === $args['image'] ) )
					continue;

				$pictures .= \Nino\Modules\Components::componentImage( $appData, [
					'source'	=> $args[$attribute],
					'alt'			=> $alt,
					'content'	=> \Nino\Feature::template( $appData, self::TEMPLATES, $template ),
					'focus'		=> '',
					'ratio'		=> '',
					'class'		=> '',
				] );
			}

			return $pictures;
		}

		/**
		 *	Whether a name is a field of the type that holds a picture: [image] reads any other
		 *	name as the uri of a slot, which is no logo of the element
		 *
		 *	@param		array			$model				The model of the type
		 *	@param		string		$field
		 *
		 *	@return 	bool
		 */
		private static function _isImage( array $model, string $field ): bool {
			return $field !== '' && ( $model[$field]['type'] ?? '' ) === 'image';
		}

		/**
		 *	The value of a field of the element that is no picture, as it is stored: '' for a name that is
		 *	no field of the type, for a picture and for a value that is not a scalar
		 *
		 *	@param		array			$element			The element
		 *	@param		array			$model				The model of its type
		 *	@param		string		$field
		 *
		 *	@return 	string
		 */
		private static function _field( array $element, array $model, string $field ): string {

			if( $field === '' || isset( $model[$field] ) === false || self::_isImage( $model, $field ) === true || is_scalar( $element[$field] ?? null ) === false )
				return '';

			return (string) $element[$field];
		}

		/**
		 *	The alt text of a logo, as a line of text and not yet escaped: the picture escapes it once. A field
		 *	that keeps html is read as the text it says - the tags dropped, a block's end and a break a space and the
		 *	entities the characters they stand for - as the kernel reads a field in the line of a row of a table;
		 *	the kernel keeps that reading private, so it is made here of the helpers it makes public
		 *
		 *	@param		array			$element			The element
		 *	@param		array			$model				The model of its type
		 *	@param		string		$field				The field the call names for the name
		 *
		 *	@return 	string
		 */
		private static function _name( array $element, array $model, string $field ): string {

			$value = self::_field( $element, $model, $field );

			if( in_array( \Nino\Html::fieldFormat( $model[$field] ?? [] ), [ 'inline', 'blocks' ], true ) === false )
				return $value;

			return trim( (string) preg_replace( '/\s+/', ' ', html_entity_decode( strip_tags( \Nino\Html::breaksToNewlines( $value ) ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ) ) );
		}

		/**
		 *	One logo in its item. The copy is the same logo with its link taken out of
		 *	the order of the Tab key: it is hidden from a screen reader, and a link a
		 *	keyboard can reach and a screen reader cannot see is a stop nobody can name
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$pictures			The pictures of the logo
		 *	@param		string		$href					The address it links to, escaped, '' for none
		 *	@param		bool			$copy					Whether this is the copy
		 *
		 *	@return 	string
		 */
		private static function _item( array &$appData, string $pictures, string $href, bool $copy ): string {

			$inner = str_replace( '[[inner]]', $pictures, \Nino\Feature::template( $appData, self::TEMPLATES, 'logos-set' ) );

			if( $href !== '' )
				$inner = str_replace(
					[ '[[href]]', '[[inner]]' ],
					[ $href, $inner ],
					\Nino\Feature::template( $appData, self::TEMPLATES, $copy === true ? 'logos-link-copy' : 'logos-link' )
				);

			return str_replace( '[[inner]]', $inner, \Nino\Feature::template( $appData, self::TEMPLATES, 'logos-item' ) );
		}

		/**
		 *	An address a logo may link to, escaped for an attribute: https, http and
		 *	mailto addresses and a path of the site, with no space or control
		 *	character anywhere in it. A browser drops a control character in front of a
		 *	scheme before it reads the address, so none is allowed to stand in it
		 *
		 *	@param		string		$url
		 *
		 *	@return 	string										'' for an address no logo may link to
		 */
		public static function href( string $url ): string {

			$url = trim( $url );

			if( $url === '' || preg_match( '/[\x00-\x20\x7F]/', $url ) === 1 )
				return '';

			if( preg_match( '#^(?:https?://|mailto:)[^/\s]#i', $url ) !== 1 && preg_match( '#^/(?![/\\\\])#', $url ) !== 1 )
				return '';

			return \Nino\Modules\Components::escape( $url );
		}
	}

}
