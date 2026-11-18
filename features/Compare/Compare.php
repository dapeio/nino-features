<?php
declare(strict_types=1);
/**
 *	Nino								A compact filesystembased php framework
 *	Modules\Compare			see _nino/Nino/Modules/Modules.php for the
 *											package-level docblock
 *
 *	@package						Dape/Nino
 *	@author							David Perchermeier <mail@dape.io>
 *	@link								https://github.com/dapeio/nino
 */
namespace Nino\Modules {

	/**
	 *	Nino							A compact filesystembased php framework
	 *	Compare						Two pictures of the same thing, one over the other,
	 *										under a divider the visitor moves.
	 *
	 *										The divider is an <input type="range">. Not a <div>
	 *										with pointer handlers on it: a range is draggable with
	 *										a mouse, with a finger, and with the arrow keys; it
	 *										announces itself and its value to a screen reader; and
	 *										it is one line of markup instead of a gesture library
	 *										that would have to be told about all three. What the
	 *										script does is read its value into a custom property -
	 *										the clipping is the standard part's.
	 *
	 *										Without JavaScript the same markup is two captioned
	 *										pictures under one another. That is not a fallback
	 *										bolted on: the layout is what the markup reads like,
	 *										and compare.js adds a class that makes the two into a
	 *										stack. A visitor whose browser never runs it sees both
	 *										pictures and both captions, which is what the pair was
	 *										there to show.
	 *
	 *										The two pictures are picture slots of the project, never
	 *										file names: the renderer asks \Nino\Images for each and
	 *										writes the address it answers, so no name a call holds
	 *										can climb out of the project's images.
	 *
	 *	@package					Dape/Nino
	 *	@author						David Perchermeier <mail@dape.io>
	 *	@link							https://github.com/dapeio/nino
	 */
	class Compare {

		/**
		 *	The renderer of the component compare, [compare before="~before" after="~after" alt="~alt"]
		 *
		 *	Renders nothing where either picture is missing: one half of a
		 *	comparison is a picture nobody asked for, and a divider with
		 *	nothing on one side of it is a control that cannot mean anything.
		 *	The register has made the attributes ready, so each arrives escaped
		 *	and the text of a call has its fills: the two pictures are references
		 *	that are read back to look the slot up, and what is written is made here
		 *	and escaped where it is made.
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			$args					The attributes of the call
		 *	@param		string		$content			Nothing: the component holds nothing
		 *	@param		array			$context			Where the call stands
		 *
		 *	@return 	string								The pair, or '' where a picture is not there
		 */
		public static function componentCompare( array &$appData, array $args, string $content, array $context ): string {

			$before = self::_picture( $appData, $args['before'] );
			$after = self::_picture( $appData, $args['after'] );

			if( $before === null || $after === null )
				return '';

			/*	The description is the alt text of the first picture and the caption under the pair; where the call says
				none, the one stored with the picture is. The second picture is the same thing again and is hidden from a
				screen reader	*/
			return \Nino\Components::fill( $appData, 'compare', $args, $content, [
				'[[alt]]'					=> $args['alt'] !== '' ? $args['alt'] : \Nino\Components::escape( $before['alt'] ),
				'[[beforeSrc]]'		=> \Nino\Components::escape( $before['url'] ),
				'[[afterSrc]]'		=> \Nino\Components::escape( $after['url'] ),
				'[[beforeName]]'	=> $args['beforeLabel'] !== '' ? $args['beforeLabel'] : self::_word( $appData, 'side/before' ),
				'[[afterName]]'		=> $args['afterLabel'] !== '' ? $args['afterLabel'] : self::_word( $appData, 'side/after' ),
			] );
		}

		/**
		 *	The picture a reference names, the slot of the project the register wrote escaped
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$reference		The attribute, escaped
		 *
		 *	@return 	array|null								[ 'url', 'alt' ] as \Nino\Images::picture() answers them, not escaped; null where there is no picture
		 */
		private static function _picture( array &$appData, string $reference ): ?array {
			return \Nino\Images::picture( $appData, html_entity_decode( $reference, ENT_QUOTES | ENT_HTML5, 'UTF-8' ), null );
		}

		/**
		 *	A word of the pair as it is written into html: the text of the key in the language of the page, escaped.
		 *	A key nobody has a text for is the key
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$name					The part and the name below /feature/compare/
		 *
		 *	@return 	string
		 */
		private static function _word( array &$appData, string $name ): string {

			$key = '/feature/compare/'. $name;

			return \Nino\Components::escape( \Nino\Html::resolveTextfill( $appData, $key ) ?? '[['. $key. ']]' );
		}
	}

}
