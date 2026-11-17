<?php
declare(strict_types=1);

namespace Nino\Modules {

	/**
	 *	Hello World - the example a feature takes its shape from: a component
	 *	(components/hello/, drawn by componentHello()), a route and a page made of
	 *	components (install/templates/page-hello.tpl), a file of data a backup carries
	 *	(STORAGE), and a panel of the workbench (Admin/Admin.php, assets/admin.js)
	 */
	class Hello {

		/**	The name the panel stores, with a format key of its own	*/
		public const string STORAGE = '/data/hello.php';

		/**
		 *	Registers the route /hello where the project has not written one of its own
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	void
		 */
		public static function init( array &$appData ): void {

			// A route of the feature's own, only where the address is free
			if( isset( $appData['/nino/http/routes']['GET://hello'] ) === false )
				$appData['/nino/http/routes']['GET://hello'] = [ 'uri' => '/hello', 'body' => '[template /templates/page-hello]' ];
		}

		/**
		 *	The panels this module answers; the workbench reads them while the feature is on
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	array						The classes of the panels
		 */
		public static function adminPanels( array &$appData ): array {
			return [ \Nino\Modules\Hello\Admin::class ];
		}

		/**
		 *	The renderer of the component hello, [hello name="Ada" shout]: the register
		 *	has made the attributes ready, so the name arrives escaped - it is decoded
		 *	to be put into a sentence and escaped again where it is written
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		$args					The attributes of the call
		 *	@param		string		$content			What stands between its tags (nothing: it is a leaf)
		 *	@param		array 		$context			Where the call stands
		 *
		 *	@return 	string									Finished markup
		 */
		public static function componentHello( array &$appData, array $args, string $content, array $context ): string {

			$name = trim( html_entity_decode( $args['name'], ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
			$text = (string) \Nino\Features::setting( $appData, 'hello', 'greeting', 'Hello' ). ', '. ( $name === '' ? self::name( $appData ) : $name ). '!';

			return \Nino\Components::fill( $appData, 'hello', $args, $content, [ '[[text]]' => \Nino\Components::escape( $args['shout'] === '1' ? mb_strtoupper( $text ) : $text ) ] );
		}

		/**
		 *	The name the panel stored, or World
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	string
		 */
		public static function name( array &$appData ): string {

			$stored = \Nino\Filesystem::getFileContent( $appData, self::STORAGE, [] );

			return is_array( $stored ) === true && is_string( $stored['name'] ?? null ) === true && $stored['name'] !== '' ? $stored['name'] : 'World';
		}
	}
}
