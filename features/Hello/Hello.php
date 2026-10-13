<?php
declare(strict_types=1);

namespace Nino\Modules {

	class Hello {

		public const string STORAGE = '/data/hello.php';

		public static function init( array &$appData ): void {

			// false: the manifest's component owns the name and renders it through doShortcode()
			\Nino\Feature::shortcode( $appData, 'hello', [ self::class, 'doShortcode' ] );

			// A route of the feature's own, only where the address is free
			if( isset( $appData['/nino/http/routes']['GET://hello'] ) === false )
				$appData['/nino/http/routes']['GET://hello'] = [ 'uri' => '/hello', 'body' => '[template /templates/page-hello]' ];
		}

		public static function adminPanels( array &$appData ): array {
			return [ \Nino\Modules\Hello\Admin::class ];
		}

		// 1.0.0 stored the name alone; from 1.1.0 the file carries a format key
		public static function upgrade( array &$appData, string $fromVersion ): bool {

			if( version_compare( $fromVersion, '1.1.0', '>=' ) === true || \Nino\Filesystem::fileExists( $appData, self::STORAGE ) === false )
				return true;

			return \Nino\Filesystem::mutate( $appData, self::STORAGE, static function( mixed $state ): array {

				$state = is_array( $state ) === true ? $state : [];

				return [ 'format' => 1, 'name' => (string) ( $state['name'] ?? '' ) ];
			} );
		}

		public static function doShortcode( array &$appData, array $args ): string {

			$name = trim( (string) ( $args['name'] ?? '' ) );
			$text = \Nino\Features::setting( $appData, 'hello', 'greeting', 'Hello' ). ', '. ( $name === '' ? self::name( $appData ) : $name ). '!';

			return str_replace(
				[ '[[classes]]', '[[text]]' ],
				[ \Nino\Feature::classes( $args ), \Nino\Modules\Components::escape( \Nino\Feature::flag( $args, 'shout' ) === true ? mb_strtoupper( $text ) : $text ) ],
				\Nino\Feature::template( $appData, '/features/Hello/templates', 'hello' )
			);
		}

		public static function name( array &$appData ): string {

			$stored = \Nino\Filesystem::getFileContent( $appData, self::STORAGE, [] );

			return is_array( $stored ) === true && is_string( $stored['name'] ?? null ) === true && $stored['name'] !== '' ? $stored['name'] : 'World';
		}
	}
}
