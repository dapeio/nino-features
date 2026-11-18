<?php
declare(strict_types=1);

namespace Nino\Modules {

	/**
	 *	Table of Contents - the list is built in the browser, from the headings of the finished page (assets/toc.js): a page is
	 *	assembled from sections, includes and the elements of a loop, and only the finished page knows what its headings are.
	 *	What the server draws is the frame, components/toc/template.tpl, written hidden; what it computes is whether the
	 *	headings get an anchor of their own, which is the setting of the feature, and the title where none is written
	 */
	class Toc {

		/**
		 *	The renderer of the component toc, [toc levels="2" within="#article" title="On this page" sticky]: the register has
		 *	made the attributes ready - they arrive escaped, and a title is the text it names - so the renderer adds the one token
		 *	of its own
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		$args					The attributes of the call
		 *	@param		string		$content			What stands between its tags (nothing: it is a leaf)
		 *	@param		array 		$context			Where the call stands
		 *
		 *	@return 	string									Finished markup
		 */
		public static function componentToc( array &$appData, array $args, string $content, array $context ): string {
			$tokens = [ '[[anchors]]' => self::anchors( $appData ) === true ? '1' : '0' ];

			// No title, or an empty one, is the word of the Text panel; the word is read as the page reads it
			if( trim( $args['title'] ) === '' )
				$tokens['[[title]]'] = \Nino\Html::renderSource( $appData, '[[/feature/toc/list/title]]', $context );

			return \Nino\Components::fill( $appData, 'toc', $args, $content, $tokens );
		}

		/**
		 *	Whether every heading of a page with a list gets a link of its own, as the settings have it
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	bool
		 */
		public static function anchors( array &$appData ): bool {
			return \Nino\Features::setting( $appData, 'toc', 'anchors', true ) === true;
		}
	}
}
