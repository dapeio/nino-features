<?php
declare(strict_types=1);
/**
 *	Nino								A compact filesystembased php framework
 *	Modules\Pdf\Layout\Allow	The HTML a document may hold: the tags and attributes that stay, and what becomes of the rest
 *
 *	@package						Dape/Nino
 *	@author							David Perchermeier <mail@dape.io>
 *	@link								https://github.com/dapeio/nino
 */
namespace Nino\Modules\Pdf\Layout {

	/**
	 *	Nino							A compact filesystembased php framework
	 *	Allow							The allowlist of the HTML a PDF is made of. The engine understands a
	 *										small subset (see Html); this is the subset, written once, and it is
	 *										held to twice: when a person saves a text - a block of the library, a
	 *										text of a template's own, the header, the footer - and again when a
	 *										document is laid out, so that what a caller hands in as a slot, and
	 *										the two sample texts of the preview, are held to the same list.
	 *
	 *										Kept: h1, h2, h3, p, div, br, strong, b, em, i, ul, ol, li, a, hr,
	 *										table, thead, tbody, tfoot, tr, th, td, img and span - the tags the
	 *										engine draws, which the kernel's sanitizer for blocks (paragraphs,
	 *										lists, links) would flatten: a heading, a table or a rule is the
	 *										point of a document. And of their attributes, only what the engine
	 *										reads, each in the one shape it reads it in:
	 *
	 *										  width    a th or td: a percentage (the column); an img: a number of mm
	 *										  align    a p, div, h1, h2, h3, th or td: left, right or center
	 *										  style    a p, div, h1, h2, h3, th or td, reduced to its text-align and
	 *										           font-size declarations, a p and a div also to margin (one to four
	 *										           lengths in pt, px, mm, em or rem, up to 200 mm), a th and a td also to background, a tr
	 *										           to background alone (#rrggbb, six hex digits and nothing else);
	 *										           a li and a span, to font-size: a number
	 *										           with pt, px, mm, em, rem or % (0.5 to 72 pt for the absolute
	 *										           units, a factor of 0.1 to 8 or 10 to 800 % for the others)
	 *										  class    a p or div: address, and no other class (the rules of
	 *										           the sheet's .address)
	 *										  border   a table: a whole number, 0 for none
	 *										  src      an img, and only pdf:logo - any other img goes
	 *
	 *										The href of a link is not kept: the engine draws the text of a link
	 *										underlined and makes no annotation of it, so the address would only
	 *										be something to carry along (a javascript: address included).
	 *
	 *										The rest: a tag that is not listed is unwrapped, what it holds stays
	 *										as it was; a script, a style, a head, a title and a template go with
	 *										their content, which is no text of a document; a comment and a
	 *										processing instruction go; every attribute not named above is removed
	 *										(onclick, a class that is not address, id, a style without a text-align).
	 *
	 *										body() is the entry for the engine: the text parsed and cleaned, as a
	 *										DOM. html() is the entry for a save: the same body written out as a
	 *										string, tags and attributes as listed, text escaped, so that what is
	 *										stored is what the engine will read - and what an editor shows after a
	 *										save. The parse is the engine's: the text is handed to libxml as
	 *										numeric entities, which is the way a UTF-8 string reaches its HTML
	 *										parser unchanged without an envelope of markup around it, and the
	 *										document is what the parser puts in the body.
	 *
	 *										The string written out is data, not a view: it is the tags of the
	 *										list round the text the person typed.
	 *
	 *	@package					Dape/Nino
	 *	@author						David Perchermeier <mail@dape.io>
	 *	@link							https://github.com/dapeio/nino
	 */
	class Allow {

		// The tags that stay, each with the attributes it may keep, in the order they are written
		public const array TAGS = [
			'h1'			=> [ 'align', 'style' ],
			'h2'			=> [ 'align', 'style' ],
			'h3'			=> [ 'align', 'style' ],
			'p'				=> [ 'align', 'style', 'class' ],
			'div'			=> [ 'align', 'style', 'class' ],
			'br'			=> [],
			'strong'	=> [],
			'b'				=> [],
			'em'			=> [],
			'i'				=> [],
			'ul'			=> [],
			'ol'			=> [],
			'li'			=> [ 'style' ],
			'a'				=> [],
			'hr'			=> [],
			'table'		=> [ 'border' ],
			'thead'		=> [],
			'tbody'		=> [],
			'tfoot'		=> [],
			'tr'			=> [ 'style' ],
			'th'			=> [ 'width', 'align', 'style' ],
			'td'			=> [ 'width', 'align', 'style' ],
			'img'			=> [ 'src', 'width' ],
			'span'		=> [ 'style' ],
		];

		// Elements that go with what they hold: it is no text of the document
		private const array DROPPED = [ 'script', 'style', 'head', 'title', 'template' ];

		// Elements with no content and no closing tag
		private const array VOID = [ 'br', 'hr', 'img' ];

		/**
		 *	The body of a text, parsed and cleaned
		 *
		 *	@param		string		$html
		 *
		 *	@return 	\DOMElement|null						Null for a text with nothing in it
		 */
		public static function body( string $html ): ?\DOMElement {

			$html = trim( $html );

			if( $html === '' )
				return null;

			// A byte that is not UTF-8 would end the text node at it (libxml 2.13): it becomes the replacement character first
			if( preg_match( '//u', $html ) !== 1 ) {
				$substitute = mb_substitute_character();
				mb_substitute_character( 0xFFFD );
				$html = (string) mb_convert_encoding( $html, 'UTF-8', 'UTF-8' );
				mb_substitute_character( $substitute );
			}

			$previous = libxml_use_internal_errors( true );
			$doc = new \DOMDocument();
			$doc->loadHTML( (string) mb_encode_numericentity( $html, [ 0x80, 0x10FFFF, 0, 0x1FFFFF ], 'UTF-8' ), LIBXML_NOERROR | LIBXML_NOWARNING | LIBXML_HTML_NODEFDTD );
			libxml_clear_errors();
			libxml_use_internal_errors( $previous );

			$body = $doc->getElementsByTagName( 'body' )->item( 0 );

			if( $body instanceof \DOMElement === false )
				return null;

			self::_clean( $body );

			return $body;
		}

		/**
		 *	A text held to the list, as the string a save stores
		 *
		 *	@param		string		$html
		 *
		 *	@return 	string
		 */
		public static function html( string $html ): string {

			$body = self::body( $html );

			return $body === null ? '' : trim( self::_write( $body ) );
		}

		/**
		 *	The children of a node held to the list: what is not listed is unwrapped or removed, in place
		 *
		 *	@param		\DOMNode	$parent
		 *
		 *	@return 	void
		 */
		private static function _clean( \DOMNode $parent ): void {

			foreach( iterator_to_array( $parent->childNodes ) as $child ) {

				// Text stays; a comment, a processing instruction and the like go
				if( $child instanceof \DOMText )
					continue;

				if( $child instanceof \DOMElement === false ) {
					$parent->removeChild( $child );
					continue;
				}

				$tag = strtolower( $child->nodeName );

				// An img that is not the logo is nothing the engine draws
				if( in_array( $tag, self::DROPPED, true ) === true || ( $tag === 'img' && self::_value( 'src', $child->getAttribute( 'src' ) ) === null ) ) {
					$parent->removeChild( $child );
					continue;
				}

				self::_clean( $child );

				if( isset( self::TAGS[$tag] ) === false ) {

					while( $child->firstChild !== null )
						$parent->insertBefore( $child->firstChild, $child );

					$parent->removeChild( $child );
					continue;
				}

				self::_attributes( $child, $tag );
			}
		}

		/**
		 *	The attributes of an element held to the list: those it may keep, in their shape, in the order of the list
		 *
		 *	@param		\DOMElement	$element
		 *	@param		string			$tag
		 *
		 *	@return 	void
		 */
		private static function _attributes( \DOMElement $element, string $tag ): void {

			$kept = [];

			foreach( self::TAGS[$tag] as $name ) {

				$value = $element->hasAttribute( $name ) === true ? self::_value( $name, $element->getAttribute( $name ), $tag ) : null;

				if( $value !== null )
					$kept[$name] = $value;
			}

			foreach( iterator_to_array( $element->attributes ) as $attribute )
				$element->removeAttributeNode( $attribute );

			foreach( $kept as $name => $value )
				$element->setAttribute( $name, $value );
		}

		/**
		 *	The value of an attribute in the one shape the engine reads, or null where it is none
		 *
		 *	@param		string		$name
		 *	@param		string		$value
		 *	@param		string		$tag
		 *
		 *	@return 	string|null
		 */
		private static function _value( string $name, string $value, string $tag = 'img' ): ?string {

			$value = trim( $value );

			switch( $name ) {

				case 'align':
					return in_array( strtolower( $value ), [ 'left', 'right', 'center' ], true ) === true ? strtolower( $value ) : null;

				case 'style':
					return self::_alignment( $value, in_array( $tag, [ 'p', 'div', 'h1', 'h2', 'h3', 'th', 'td' ], true ), $tag );

				case 'class':
					return strtolower( $value ) === 'address' ? 'address' : null;

				case 'border':
					return preg_match( '/^\d{1,2}$/D', $value ) === 1 ? $value : null;

				case 'src':
					return strtolower( $value ) === 'pdf:logo' ? 'pdf:logo' : null;

				case 'width':
					if( $tag === 'img' )
						return preg_match( '/^(\d{1,4}(?:\.\d{1,2})?)\s*(mm)?$/Di', $value, $found ) === 1 ? $found[1]. strtolower( $found[2] ?? '' ) : null;

					return preg_match( '/^\d{1,3}(?:\.\d{1,2})?%$/D', $value ) === 1 ? $value : null;
			}

			return null;
		}

		/**
		 *	The text-align, font-size and background declarations of a style attribute, and nothing else of it: the last valid one of each, as a cascade has it
		 *
		 *	@param		string		$style
		 *	@param		bool			$align				Whether text-align is read where the style stands
		 *	@param		string		$tag					The element: a th, a td and a tr read a background (a tr nothing else), every other element none
		 *
		 *	@return 	string|null								e.g. text-align:right;font-size:12pt;background:#f3f5f7
		 */
		private static function _alignment( string $style, bool $align = true, string $tag = '' ): ?string {

			$kept = [ 'text-align' => null, 'font-size' => null, 'margin' => null, 'background' => null ];

			foreach( explode( ';', $style ) as $declaration ) {

				$pair = explode( ':', $declaration, 2 );

				if( count( $pair ) !== 2 )
					continue;

				$property = strtolower( trim( $pair[0] ) );
				$word = (string) preg_replace( '/\s*!important$/Di', '', trim( $pair[1] ) );

				if( $property === 'text-align' && $align === true && preg_match( '/^(left|right|center)$/Di', $word ) === 1 )
					$kept[$property] = strtolower( $word );
				else if( $property === 'font-size' && $tag !== 'tr' && preg_match( '/^(\d{1,3}(?:\.\d{1,3})?)(pt|px|mm|em|rem|%)$/Di', $word, $found ) === 1 && self::_size( (float) $found[1], strtolower( $found[2] ) ) === true )
					$kept[$property] = $found[1]. strtolower( $found[2] );
				else if( $property === 'margin' && in_array( $tag, [ 'p', 'div' ], true ) === true && ( $margin = self::_margin( $word ) ) !== null )
					$kept[$property] = $margin;
				else if( $property === 'background' && in_array( $tag, [ 'th', 'td', 'tr' ], true ) === true && preg_match( '/^#([0-9a-f]{6})$/Di', $word, $found ) === 1 )
					$kept[$property] = '#'. strtolower( $found[1] );
			}

			$out = [];

			foreach( $kept as $property => $value )
				if( $value !== null )
					$out[] = $property. ':'. $value;

			return $out === [] ? null : implode( ';', $out );
		}

		/**
		 *	A margin of one to four lengths in the shape the engine reads it, or null: pt, px, mm, em or rem (0 needs no unit), none above 200 mm
		 *	or 20 em/rem, no sign, no keyword, no function
		 *
		 *	@param		string		$word
		 *
		 *	@return 	string|null
		 */
		private static function _margin( string $word ): ?string {

			$values = preg_split( '/\s+/', $word ) ?: [];

			if( count( $values ) < 1 || count( $values ) > 4 )
				return null;

			foreach( $values as $at => $value ) {

				if( preg_match( '/^(\d{1,3}(?:\.\d{1,3})?)(pt|px|mm|em|rem)?$/Di', $value, $found ) !== 1 )
					return null;

				$unit = strtolower( $found[2] ?? '' );
				$number = (float) $found[1];

				if( $unit === '' ? $number !== 0.0 : $number > match( $unit ) { 'pt' => 200 * 72 / 25.4, 'px' => 200 * 96 / 25.4, 'mm' => 200.0, default => 20.0 } )
					return null;

				$values[$at] = $found[1]. $unit;
			}

			return implode( ' ', $values );
		}

		/**
		 *	Whether a number in a unit is a size the engine takes: 0.5 to 72 points for pt, px and mm; a factor of 0.1 to 8, or 10 to 800 percent, for the relative ones
		 *
		 *	@param		float			$number
		 *	@param		string		$unit
		 *
		 *	@return 	bool
		 */
		private static function _size( float $number, string $unit ): bool {

			return match( $unit ) {
				'pt'			=> $number >= 0.5 && $number <= 72.0,
				'px'			=> $number >= 0.5 / 0.75 && $number <= 72.0 / 0.75,
				'mm'			=> $number >= 0.5 * 25.4 / 72 && $number <= 72.0 * 25.4 / 72,
				'%'				=> $number >= 10.0 && $number <= 800.0,
				default		=> $number >= 0.1 && $number <= 8.0,
			};
		}

		/**
		 *	The children of a node as a string: the elements that are left with the attributes that are left, the text escaped
		 *
		 *	@param		\DOMNode	$parent
		 *
		 *	@return 	string
		 */
		private static function _write( \DOMNode $parent ): string {

			$out = '';

			foreach( $parent->childNodes as $node ) {

				if( $node instanceof \DOMText ) {
					$out .= str_replace( "\u{A0}", '&nbsp;', htmlspecialchars( $node->data, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' ) );
					continue;
				}

				if( $node instanceof \DOMElement === false )
					continue;

				$tag = strtolower( $node->nodeName );
				$attributes = '';

				foreach( $node->attributes as $attribute )
					$attributes .= ' '. $attribute->name. '="'. htmlspecialchars( $attribute->value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' ). '"';

				$out .= '<'. $tag. $attributes. '>'. ( in_array( $tag, self::VOID, true ) === true ? '' : self::_write( $node ). '</'. $tag. '>' );
			}

			return $out;
		}
	}
}
