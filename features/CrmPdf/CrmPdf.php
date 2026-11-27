<?php
declare(strict_types=1);
/**
 *	Nino								A compact filesystembased php framework
 *	Modules\CrmPdf			The screens of the PDF feature inside the CRM
 *
 *	@package						Dape/Nino
 *	@author							David Perchermeier <mail@dape.io>
 *	@link								https://github.com/dapeio/nino
 */
namespace Nino\Modules {

	/**
	 *	Nino							A compact filesystembased php framework
	 *	CrmPdf						The screens Templates, Text blocks, Layout and Design of the feature PDF, inside the surface of the CRM (/crm#pdf), and the fifteen actions crm-pdf/...
	 *										behind them. The package owns no data and no table: every read is one of \Nino\Modules\Pdf (the library, the templates, the layout, preview(), uses(),
	 *										vocabulary()) and of \Nino\Modules\Pdf\View, every write is one of the eight writers of \Nino\Modules\Pdf\Store, and nothing is written past them. It knows no
	 *										other package: which tokens, slots and sample values a template may use is what the listeners of /pdf/vocabulary say, which templates are in use is what the
	 *										listeners of /pdf/template/uses say (and the deletion of a template is refused by the Store while one of them counts a use or gave no answer), and a package that
	 *										wants to send a person to the editor asks \Nino\Modules\Pdf::editorAddress(), which this package answers for the surface of the CRM.
	 *
	 *										RIGHTS. The endpoint of the CRM lets a request through that holds the permission of the CRM (Crm::MANAGE_PERM); every action here asks it again and asks the permission
	 *										of the PDF panel (Pdf::PERM) on top, and answers 403 where either is missing - a request that holds one of them alone changes and reads nothing. The page carries
	 *										whether the account holds the second one, and shows the entry of the rail and the screens only then; that is a convenience, the actions are the rule.
	 *
	 *										ONE FEATURE IS ENOUGH TO STAND ON. init() begins with the question whether the CRM and the PDF feature are on (Crm::featureOn()): the manifest requires both, so the
	 *										kernel does not switch the package on without them and does not switch either of them off while it is on, but a project whose configuration was edited by hand can
	 *										list the package and not the PDF feature - and then the package enters nothing: no action (the endpoint answers 404 for a name it does not know), no script, no entry of
	 *										the rail, no listener.
	 *
	 *										THE PREVIEW writes nothing: the bytes of the PDF go back in the answer, as base64, and the answer is never cached (the endpoint says no-store). Its tokens are the sample
	 *										values of the vocabulary and nothing of a client.
	 *
	 *	@package					Dape/Nino
	 *	@author						David Perchermeier <mail@dape.io>
	 *	@link							https://github.com/dapeio/nino
	 */
	class CrmPdf {

		/**	The key of the package, the name every call of it makes itself by	*/
		public const string KEY = 'crm-pdf';

		/**	The most a picture may weigh, in bytes: what the screens read of a file and what the endpoint takes of a request for it (as base64, a third more)	*/
		public const int PICTURE_MAX = 4194304;

		/**	How much of the head of a body is read to find its action, in bytes	*/
		private const int HEAD = 4096;

		/**	The action that takes a picture; its request is the only one that is turned away by its length before the endpoint reads it	*/
		public const string PICTURE_ACTION = 'crm-pdf/layout-image';

		/**
		 *	Enter the places of the surface the package docks to, while the CRM and the PDF feature are on. Nothing is read or written
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	void
		 */
		public static function init( array &$appData ): void {

			if( \Nino\Modules\Crm::featureOn( $appData, 'crm' ) === false || \Nino\Modules\Crm::featureOn( $appData, 'pdf' ) === false )
				return;

			$package = [ 'package' => self::KEY ];
			$folder = __DIR__. '/Surface';

			\Nino\Modules\Crm\Api::add( $appData, \Nino\Modules\CrmPdf\Api::actions(), $package );
			\Nino\Modules\Crm\Surface\Texts::add( $appData, $folder. '/text', $package );
			\Nino\Modules\Crm\Surface\Scripts::addStyle( $appData, 'crm-pdf.css', $folder. '/assets/crm-pdf.css', $package );
			\Nino\Modules\Crm\Surface\Scripts::add( $appData, 'crm-pdf.js', $folder. '/assets/crm-pdf.js', $package );
			\Nino\Modules\Crm\Surface\Data::add( $appData, [ self::class, 'pageData' ], $package );

			\Nino\Callbacks::registerCallback( $appData, \Nino\Modules\Pdf::EDITOR, [ self::class, 'callbackEditor' ] );
			\Nino\Callbacks::registerCallback( $appData, '/nino/http/request', [ self::class, 'callbackRequest' ] );
		}

		/**
		 *	Turn a request for a picture away by its length before the endpoint reads it: the endpoint decodes the whole body as json, and a body of megabytes is held in memory several times over. The request for the action that takes
		 *	a picture, whose Content-Length is more than a picture of PICTURE_MAX weighs as base64 and a little more, is answered with a body that says so - the action, the kind and the word oversized, nothing of the picture - and goes
		 *	the way of any request: the session, the token and the rights are asked of it as of any other, and the action answers 413 once they are. Every other request is left as it is.
		 *
		 *	What does not come this way: a request that says no length (chunked) is read and decoded by the endpoint before the action can say 413, and so is one whose action stands behind more than HEAD bytes of other keys.
		 *	The limit holds for them after the decoding only
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$request			(reference) Current request and its response
		 *
		 *	@return 	void
		 */
		public static function callbackRequest( array &$appData, array &$request ): void {

			$asked = $request['/nino/http/request'] ?? [];
			$length = $asked['header']['Content-Length'] ?? '';
			$body = $asked['body'] ?? '';

			if( ( $asked['method'] ?? '' ) !== 'POST' || ( $asked['uri'] ?? '' ) !== \Nino\Modules\Crm\Api::ROUTE || is_string( $length ) === false || ctype_digit( $length ) === false || is_string( $body ) === false )
				return;

			if( (int) $length <= intdiv( self::PICTURE_MAX * 4, 3 ) + 4096 || self::headAction( substr( $body, 0, self::HEAD ) ) !== self::PICTURE_ACTION )
				return;

			$request['/nino/http/request']['body'] = json_encode( [ 'action' => self::PICTURE_ACTION, 'data' => [ 'oversized' => true ] ] );
		}

		/**
		 *	What the page carries for the script of the package: whether the account holds the permission of the PDF panel, which decides whether the entry of the rail and the screens are drawn
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	array											allowed
		 */
		public static function pageData( array &$appData ): array {

			return [ 'allowed' => \Nino\Auth::checkPermission( $appData, \Nino\Modules\Pdf::PERM ) === true ];
		}

		/**
		 *	Where the editor of a template or a block is, for a screen of the CRM that asks (/pdf/editor): a hash of the page /crm that opens inside it, for an account that holds the
		 *	permission of the CRM and that of the PDF panel. Nothing else is answered - a question of another surface, an account without both rights, a part the screens have no
		 *	hash for - and the asking feature then has the address of the panel of the workbench, which is where such an account is let in
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$asked				(reference) surface, part, key, tag, href, inside
		 *
		 *	@return 	void
		 */
		public static function callbackEditor( array &$appData, array &$asked ): void {

			if( ( $asked['surface'] ?? null ) !== 'crm' || is_string( $asked['part'] ?? null ) === false )
				return;

			if( \Nino\Modules\Crm::featureOn( $appData, 'crm' ) === false || \Nino\Modules\Crm::featureOn( $appData, 'pdf' ) === false )
				return;

			if( \Nino\Auth::checkPermission( $appData, \Nino\Modules\Crm::MANAGE_PERM ) === false || \Nino\Auth::checkPermission( $appData, \Nino\Modules\Pdf::PERM ) === false )
				return;

			$key = is_string( $asked['key'] ?? null ) === true && preg_match( \Nino\Modules\Pdf::SLUG, $asked['key'] ) === 1 ? $asked['key'] : '';
			$tag = is_string( $asked['tag'] ?? null ) === true && preg_match( \Nino\Modules\Pdf::SLUG, $asked['tag'] ) === 1 ? $asked['tag'] : '';

			$hash = match( $asked['part'] ) {
				'templates'	=> $key !== '' ? 'pdf-template-'. $key : ( $tag !== '' ? 'pdf-new-template-'. $tag : 'pdf' ),
				'blocks'		=> $key !== '' ? 'pdf-block-'. $key : 'pdf-blocks',
				'pictures',
				'layout'		=> 'pdf-layout',
				'design'		=> 'pdf-design',
				default			=> '',
			};

			if( $hash === '' )
				return;

			$asked['href'] = '#'. $hash;
			$asked['inside'] = true;
		}

		/**
		 *	The action a json body names at its top level, read from the head of the body alone: the keys of the outermost object are walked in order - blanks between them, strings with their escapes (a slash written \/
		 *	is a slash), objects and lists skipped whole - and the last key action that holds a text wins, as json_decode() takes a key that is there twice. A key of that name inside another value is none of it, and a head that
		 *	ends before the object does is read as far as it goes
		 *
		 *	@param		string		$head					The start of the body
		 *
		 *	@return 	string										The action; '' where the head names none
		 */
		public static function headAction( string $head ): string {

			$length = strlen( $head );
			$at = 0;
			$found = '';

			// A text from its opening quote: the decoded text, and the position after the closing quote; null for one that is not closed in the head
			$text = static function( int $from ) use ( $head, $length ): ?array {

				for( $end = $from + 1; $end < $length; $end++ ) {

					if( $head[$end] === '\\' )
						$end++;
					else if( $head[$end] === '"' ) {
						$decoded = json_decode( substr( $head, $from, $end - $from + 1 ) );

						return is_string( $decoded ) === true ? [ $decoded, $end + 1 ] : null;
					}
				}

				return null;
			};

			$blank = static function( int $from ) use ( $head, $length ): int {

				while( $from < $length && strpos( " \t\r\n", $head[$from] ) !== false )
					$from++;

				return $from;
			};

			$at = $blank( 0 );

			if( ( $head[$at] ?? '' ) !== '{' )
				return '';

			$at++;

			while( $at < $length ) {

				$at = $blank( $at );

				if( ( $head[$at] ?? '' ) === ',' ) {
					$at = $blank( $at + 1 );
				}

				if( ( $head[$at] ?? '' ) !== '"' )
					return $found;

				$key = $text( $at );

				if( $key === null )
					return $found;

				$at = $blank( $key[1] );

				if( ( $head[$at] ?? '' ) !== ':' )
					return $found;

				$at = $blank( $at + 1 );
				$first = $head[$at] ?? '';

				if( $first === '"' ) {

					$value = $text( $at );

					if( $value === null )
						return $found;

					if( $key[0] === 'action' )
						$found = $value[0];

					$at = $value[1];
				}
				else if( $first === '{' || $first === '[' ) {

					// Skipped whole: the strings in it are walked past, so that a brace in a text does not count
					$depth = 0;

					for( ; $at < $length; $at++ ) {

						if( $head[$at] === '"' ) {

							$inner = $text( $at );

							if( $inner === null )
								return $found;

							$at = $inner[1] - 1;
						}
						else if( $head[$at] === '{' || $head[$at] === '[' )
							$depth++;
						else if( ( $head[$at] === '}' || $head[$at] === ']' ) && --$depth === 0 ) {
							$at++;
							break;
						}
					}

					if( $depth !== 0 )
						return $found;

					if( $key[0] === 'action' )
						$found = '';
				}
				else {

					while( $at < $length && strpos( ",}", $head[$at] ) === false )
						$at++;

					if( $key[0] === 'action' )
						$found = '';
				}
			}

			return $found;
		}
	}
}
