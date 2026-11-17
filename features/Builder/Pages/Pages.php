<?php
declare(strict_types=1);

namespace Nino\Modules\Builder {

	// The pages of the site the Builder lists: the routes that answer with a template of a page, once for each template
	class Pages {

		/**
		 *	The pages that can be opened, in the order of the routes
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$locale				The language of the view: what a page is called, and the address it is shown at
		 *
		 *	@return 	array											[ [ template, name, path, locales ], ... ]
		 */
		public static function all( array &$appData, string $locale ): array {

			$texts = \Nino\Locales::getTextLocales( $appData );
			$pages = [];

			foreach( \Nino\Modules\Routes\Admin::pages( $appData, (array) ( $appData['/nino/http/routes'] ?? [] ), $texts, [] ) as $page ) {

				$template = (string) $page['template'];

				if( isset( $pages[$template] ) === true || ( \Nino\Modules\Builder\Store::named( $template )['frame'] ?? true ) === true || is_file( \Nino\Filesystem::path( $appData, '/templates/'. $template. '.tpl' ) ) === false )
					continue;

				$words = (array) ( $page['text'][$locale] ?? [] );
				$name = trim( (string) ( $words['name'] ?? '' ) ) !== '' ? (string) $words['name'] : ( trim( (string) ( $words['title'] ?? '' ) ) !== '' ? (string) $words['title'] : $template );
				$has = array_keys( (array) $page['httpUris'] );
				$pages[$template] = [
					'template'	=> $template,
					'name'			=> $name,
					'path'			=> (string) ( $page['httpUris'][$locale] ?? $page['httpUris'][''] ?? $page['httpUri'] ),
					'locales'		=> in_array( '', $has, true ) === true ? \Nino\Locales::getAvailableLocales( $appData ) : array_values( array_filter( $has ) ),
				];
			}

			return array_values( $pages );
		}
	}
}
