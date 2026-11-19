<?php
declare(strict_types=1);

namespace Nino\Modules\Blocks {

	use Nino\Modules\Builder\Library;
	use Nino\Modules\Builder\Store;
	use Nino\Modules\Builder\Tree;

	// The frames of the site - the header and the footer every page has round its blocks - as the editor of the blocks has them. A frame is a template like a page's, and the
	// panel of the pages has none for anybody: the editor of the blocks passes each request for one on to the code of the Builder after it held its own right, and marks the
	// request in its memory - a mark nothing a request brings can set - which is all the code of the Builder needs to go the whole way down to the markup of a frame
	class Frames {

		/**
		 *	The frames the site has: the header and the footer, each as the file of its template stands
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	array											[ [ key, template ] ]
		 */
		public static function listed( array &$appData ): array {

			$frames = [];

			foreach( Store::FRAMES as $key => $template )
				if( is_file( \Nino\Filesystem::path( $appData, '/templates/'. $template. '.tpl' ) ) === true )
					$frames[] = [ 'key' => $key, 'template' => $template ];

			return $frames;
		}

		/**
		 *	The door of every request for a frame: the right of this panel, and a template that is a frame of the site - nothing else
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			&$request			(reference) The request
		 *
		 *	@return 	bool											false after the answer that says why not
		 */
		public static function guard( array &$appData, array &$request ): bool {

			if( \Nino\Admin\Admin::guardPerm( $appData, $request, \Nino\Modules\Blocks::PERM ) === false )
				return false;

			if( in_array( \Nino\Admin\Admin::postString( \Nino\Admin\Admin::postData(), 'template' ), Store::FRAMES, true ) === false ) {
				\Nino\Http::fail( $request, 400, 'the request names no frame of the site', 'blocks_frame', [], 'template' );
				return false;
			}

			return true;
		}

		/**
		 *	One request of the editor of a frame, done by the code of the Builder: only for the account that holds the right of this panel, only for a frame, and only for the
		 *	length of this call
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			&$request			(reference) The request
		 *	@param		string		$action				The method of \Nino\Modules\Builder\Admin that answers
		 *
		 *	@return 	void
		 */
		public static function pass( array &$appData, array &$request, string $action ): void {

			if( self::guard( $appData, $request ) === false )
				return;

			$appData[\Nino\Modules\Builder::AS] = true;

			try {
				call_user_func_array( [ \Nino\Modules\Builder\Admin::class, $action ], [ &$appData, &$request ] );
			}
			finally {
				unset( $appData[\Nino\Modules\Builder::AS] );
			}
		}
	}
}
