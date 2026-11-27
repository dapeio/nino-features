<?php
declare(strict_types=1);

namespace Nino\Modules\CrmDocuments {

	use Nino\Modules\Crm\Surface\Texts;

	/**
	 *	Preview		GET /crm/preview/<id>: the PDF of a draft as its template would make it now, or the sentence that says why there is none. The document is the owner's: 401 for nobody and 403 without the permission of the CRM; the id
	 *								is a number or the answer is the 404; a document that is no draft is the 409 (its PDF is the stored one, at /.crm/pdf/<id>). Nothing is stored, no number is taken and no file is written.
	 *
	 *	@package						Dape/Nino
	 */
	class Preview {

		public const string ROUTE = '/crm/preview/';

		/**
		 *	@param		array 		&$appData
		 *	@param		array 		&$request
		 *
		 *	@return 	void
		 */
		public static function callback( array &$appData, array &$request ): void {

			if( \Nino\Auth::getCurrentUser( $appData ) === false ) {
				Render::answer( $request, 401, Texts::word( $appData, 'docs.session' ) );
				return;
			}

			if( \Nino\Auth::checkPermission( $appData, \Nino\Modules\Crm::MANAGE_PERM ) === false ) {
				Render::answer( $request, 403, Texts::word( $appData, 'preview.forbidden' ) );
				return;
			}

			$uri = (string) ( $request['/nino/http/request']['uri'] ?? '' );
			$id = str_starts_with( $uri, self::ROUTE ) === true ? substr( $uri, strlen( self::ROUTE ) ) : '';

			if( preg_match( '/^[1-9][0-9]{0,9}$/D', $id ) !== 1 ) {
				Render::answer( $request, 404, Texts::word( $appData, 'error.crm_documents_unknown' ) );
				return;
			}

			try {
				$made = Finalise::preview( $appData, (int) $id );
			}
			catch( \PDOException $e ) {
				Render::answer( $request, 503, Texts::word( $appData, 'error.crm_database' ) );
				return;
			}

			if( is_string( $made ) === true ) {

				[ $kind, $rest ] = array_pad( explode( ':', $made, 2 ), 2, '' );
				$status = match( $kind ) { 'unknown' => 404, 'render' => 500, default => 409 };
				$texts = Texts::all( $appData );
				$text = $texts['preview.'. $kind] ?? $texts['error.crm_documents_'. $kind] ?? Texts::word( $appData, 'error.request' );

				// What the project lacks (a word, a template) is named in the sentence; it is plain text, which the browser shows as it is
				Render::answer( $request, $status, in_array( $kind, [ 'words', 'markup' ], true ) === true ? sprintf( $text, $rest ) : $text );
				return;
			}

			$request['/nino/http/response']['body'] = $made['bytes'];
			$request['/nino/http/response']['raw'] = true;
			$request['/nino/http/response']['header']['Content-Type'] = 'application/pdf';
			$request['/nino/http/response']['header']['Content-Disposition'] = 'inline; filename="'. preg_replace( '/[^A-Za-z0-9._-]/', '-', $made['name'] ). '.pdf"';
			$request['/nino/http/response']['header']['Cache-Control'] = 'private, no-store';
			$request['/nino/http/response']['header']['X-Content-Type-Options'] = 'nosniff';
		}
	}
}
