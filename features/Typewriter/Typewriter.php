<?php
declare(strict_types=1);

namespace Nino\Modules {

	// The component typewriter has one thing to compute: the lines. A text of several lines is one line of the typewriter
	// each, and the template cannot loop. The source is a text in the format lines, because that is the one a tool lets
	// a person break at the end of a line (the Builder takes Enter for it; a plain text is one line there). What the
	// register hands over is html - <br> between the lines, the inline tags a person may have used - so each line is
	// made text again: the tags are taken away, the entities read as the characters they stand for and escaped once.
	// The markup of the container is the template's. The typing itself is run in the browser (assets/typewriter.js)
	class Typewriter {

		/**
		 *	[typewriter ~lines tag="h2" speed="45" hold="1600" loop]
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			$args					What the register made ready: the value is the text, as html of the format lines
		 *	@param		string		$content			Not used: a typewriter holds no component
		 *	@param		array			$context			The context of the drawing
		 *
		 *	@return 	string
		 */
		public static function componentTypewriter( array &$appData, array $args, string $content, array $context ): string {

			$lines = '';
			$text = html_entity_decode( strip_tags( preg_replace( '#<br\s*/?>#i', "\n", $args['value'] ) ?? '' ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );

			foreach( preg_split( '/\R/u', $text ) ?: [] as $line ) {

				$line = trim( $line );

				if( $line !== '' )
					$lines .= '<span class="typewriter-line">'. \Nino\Components::escape( $line ). '</span>';
			}

			if( $lines === '' )
				return '';

			return \Nino\Components::fill( $appData, 'typewriter', $args, $content, [ '[[lines]]' => $lines ] );
		}
	}
}
