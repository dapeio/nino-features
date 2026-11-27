<?php
declare(strict_types=1);

namespace Nino\Modules\CrmPdf {

	use Nino\Modules\Pdf as Feature;
	use Nino\Modules\Pdf\Store;
	use Nino\Modules\Pdf\View;

	/**
	 *	Api		The fifteen actions of the screens of the PDF feature in the CRM, entered with Crm\Api::add() as crm-pdf/<name>. Each one begins with guard(): the permission of the CRM and the permission of the PDF panel, both
	 *					asked of the account of the request, and a 403 where one is missing - nothing is read or written then. What an action reads is a read of \Nino\Modules\Pdf or of \Nino\Modules\Pdf\View, what it writes is
	 *					a writer of \Nino\Modules\Pdf\Store, and the answer is what the screen is drawn from again (the Store answers [ ok, ... ] or [ fail ], the arguments of \Nino\Http::fail(), which are passed on as they are).
	 *					The line of the activity log of an action says what was done and to which key of a template or a block; the key is written only where it is a slug.
	 *
	 *					layout-get, layout-save, layout-image, layout-image-delete, layout-preview   the layout, its two pictures and the preview of the layout as saved
	 *					blocks-list, block-save, block-delete                                           the library of text blocks
	 *					templates-list, template-get, template-save, template-duplicate, template-delete   the templates
	 *					preview                                                                       the bytes of the template the editor holds, saved or not
	 *					context                                                                       what the screens show beside the lists of the feature: who uses a template, the day it was changed, which
	 *					                                                                              templates use a block, the texts of the site a template may place, the vocabulary of the callers
	 *
	 *					A picture is posted as base64 in the data of the action (kind, data): the endpoint takes json, and the token of the page is the page's. A picture is at most CrmPdf::PICTURE_MAX (4 MB); a request that is longer
	 *					than that is turned away by its length before the endpoint reads it (CrmPdf::callbackRequest()), and the action answers 413 for it.
	 *
	 *	@package						Dape/Nino
	 */
	class Api {

		// The texts of the site a template may place: the company of the site (the words of the wizard's company step)
		private const string SITE_KEY = '~^/project/company/[a-z0-9_-]+/[a-z0-9_-]+$~D';

		/**
		 *	The actions of the package: action => [ class, method ]
		 *
		 *	@return 	array
		 */
		public static function actions(): array {

			$class = self::class;

			return [
				'crm-pdf/context'							=> [ $class, 'apiContext' ],
				'crm-pdf/layout-get'					=> [ $class, 'apiLayoutGet' ],
				'crm-pdf/layout-save'					=> [ $class, 'apiLayoutSave' ],
				'crm-pdf/layout-image'				=> [ $class, 'apiLayoutImage' ],
				'crm-pdf/layout-image-delete'	=> [ $class, 'apiLayoutImageDelete' ],
				'crm-pdf/layout-preview'			=> [ $class, 'apiLayoutPreview' ],
				'crm-pdf/blocks-list'					=> [ $class, 'apiBlocksList' ],
				'crm-pdf/block-save'					=> [ $class, 'apiBlockSave' ],
				'crm-pdf/block-delete'				=> [ $class, 'apiBlockDelete' ],
				'crm-pdf/templates-list'			=> [ $class, 'apiTemplatesList' ],
				'crm-pdf/template-get'				=> [ $class, 'apiTemplateGet' ],
				'crm-pdf/template-save'				=> [ $class, 'apiTemplateSave' ],
				'crm-pdf/template-duplicate'	=> [ $class, 'apiTemplateDuplicate' ],
				'crm-pdf/template-delete'			=> [ $class, 'apiTemplateDelete' ],
				'crm-pdf/preview'							=> [ $class, 'apiPreview' ],
			];
		}

		/**
		 *	The rights every action asks: a signed-in account that holds the permission of the CRM and the permission of the PDF panel. The endpoint asked the first already; it is asked again here, so that no way to a
		 *	method of this class passes without it
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		&$request
		 *
		 *	@return 	bool											false after the answer that says why: 401 without a session, 403 for a right that is missing
		 */
		private static function _guard( array &$appData, array &$request ): bool {

			if( \Nino\Admin\Admin::guardPerm( $appData, $request, Feature::PERM ) === false )
				return false;

			if( \Nino\Auth::checkPermission( $appData, \Nino\Modules\Crm::MANAGE_PERM ) === false ) {
				\Nino\Http::fail( $request, 403, 'not allowed' );
				return false;
			}

			return true;
		}

		/**
		 *	The answer of a Store method: its refusal as a failure, else what the screen is drawn from now
		 *
		 *	@param		array 		&$request
		 *	@param		array 		$result				What the Store answered
		 *	@param		callable	$body					Makes the body of a success
		 *
		 *	@return 	void
		 */
		private static function _answer( array &$request, array $result, callable $body ): void {

			if( isset( $result['fail'] ) === true )
				\Nino\Http::fail( $request, ...$result['fail'] );
			else
				\Nino\Http::ok( $request, $body() );
		}

		/**
		 *	The key a line of the log names: the key of a template or a block where it is a slug, else nothing
		 *
		 *	@param		array 		$data
		 *
		 *	@return 	string
		 */
		private static function _named( array $data ): string {

			$key = \Nino\Admin\Admin::postString( $data, 'key' );

			return preg_match( Feature::SLUG, $key ) === 1 ? $key : '';
		}

		/**
		 *	The sample values of the vocabulary as the tokens a preview takes: a key with slashes flat, a dotted name nested; a name that would stand under a value that is no list is left out
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	array											tokens
		 */
		private static function _tokens( array &$appData ): array {

			$tokens = [];

			foreach( Feature::vocabulary( $appData )['sample'] as $token => $text ) {

				$token = (string) $token;

				if( $token[0] === '/' ) {
					$tokens[$token] = $text;
					continue;
				}

				$parts = explode( '.', $token );
				$last = array_pop( $parts );
				$here = &$tokens;

				foreach( $parts as $part ) {

					if( isset( $here[$part] ) === false )
						$here[$part] = [];

					if( is_array( $here[$part] ) === false )
						continue 2;

					$here = &$here[$part];
				}

				if( isset( $here[$last] ) === false )
					$here[$last] = $text;

				unset( $here );
			}

			return $tokens;
		}

		/**
		 *	What the screens show beside the lists of the feature
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		&$request
		 *
		 *	@return 	void
		 */
		public static function apiContext( array &$appData, array &$request ): void {

			if( self::_guard( $appData, $request ) === false )
				return;

			$asked = Feature::uses( $appData );
			$templates = Feature::templates( $appData );
			$used = [];
			$by = [];
			$blocks = [];

			foreach( $asked['uses'] as $key => $packages ) {

				$used[$key] = array_sum( $packages );
				$by[$key] = $packages;
			}

			// A listener that failed as a whole says nothing about any template: every one of them is unsure
			$unsure = $asked['failed'] !== [] ? array_keys( $templates ) : array_keys( $asked['unknown'] );

			foreach( array_keys( $templates ) as $key )
				foreach( Feature::template( $appData, $key )['blocks'] ?? [] as $block )
					if( isset( $block['block'] ) === true && is_string( $block['block'] ) === true && in_array( $templates[$key]['name'], $blocks[$block['block']] ?? [], true ) === false )
						$blocks[$block['block']][] = $templates[$key]['name'];

			$site = [];

			foreach( array_keys( \Nino\Html::getFills( $appData ) ) as $fill )
				if( preg_match( '~^\[\[(/[^\]]+)\]\]$~D', (string) $fill, $found ) === 1 && preg_match( self::SITE_KEY, $found[1] ) === 1 )
					$site[] = $found[1];

			sort( $site );

			$vocabulary = Feature::vocabulary( $appData );
			$locale = \Nino\Modules\Crm\Surface\Texts::locale( $appData );
			$groups = [];

			foreach( $vocabulary['groups'] as $group )
				$groups[] = [ 'key' => $group['key'], 'label' => self::_label( $group['label'], $locale ), 'tokens' => array_map( static fn( array $token ): array => [ 'token' => $token['token'], 'label' => self::_label( $token['label'], $locale ) ], $group['tokens'] ) ];

			\Nino\Http::ok( $request, [
				'used'				=> $used,
				'by'					=> $by,
				'unsure'			=> $unsure,
				'changed'			=> array_map( static fn( array $template ): string => $template['changed'], $templates ),
				'blocks'			=> $blocks,
				'site'				=> $site,
				'groups'			=> $groups,
				'slots'				=> array_map( static fn( array $slot ): array => [ 'name' => $slot['name'], 'label' => self::_label( $slot['label'], $locale ) ], $vocabulary['slots'] ),
			] );
		}

		/**
		 *	The text of a label that is a map of locale => text: the one of the language of the page, else English, else the first there is
		 *
		 *	@param		array 		$label
		 *	@param		string		$locale
		 *
		 *	@return 	string
		 */
		private static function _label( array $label, string $locale ): string {

			return (string) ( $label[$locale] ?? $label['en_US'] ?? reset( $label ) );
		}

		public static function apiLayoutGet( array &$appData, array &$request ): void {

			if( self::_guard( $appData, $request ) === false )
				return;

			\Nino\Http::ok( $request, View::layout( $appData ) );
		}

		public static function apiLayoutSave( array &$appData, array &$request ): void {

			if( self::_guard( $appData, $request ) === false )
				return;

			\Nino\Modules\Crm\Api::made( $appData, 'Save the PDF layout' );
			self::_answer( $request, Store::saveLayout( $appData, \Nino\Admin\Admin::postData() ), static fn(): array => View::layout( $appData ) );
		}

		/**
		 *	Take a picture in as the logo or the owner's signature: its bytes as base64 in data, which is decoded only where it is no larger than the kernel takes of a picture
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		&$request
		 *
		 *	@return 	void
		 */
		public static function apiLayoutImage( array &$appData, array &$request ): void {

			if( self::_guard( $appData, $request ) === false )
				return;

			$data = \Nino\Admin\Admin::postData();
			$kind = \Nino\Admin\Admin::postString( $data, 'kind' );
			$text = is_string( $data['data'] ?? null ) === true ? $data['data'] : '';
			$most = \Nino\Modules\CrmPdf::PICTURE_MAX;

			if( ( $data['oversized'] ?? false ) === true || strlen( $text ) > intdiv( $most * 4, 3 ) + 8 ) {
				\Nino\Http::fail( $request, 413, 'the file is larger than the screens take ('. intdiv( $most, 1048576 ). ' MB)', 'image_too_large', [ intdiv( $most, 1048576 ) ], 'file' );
				return;
			}

			$bytes = $text === '' ? false : base64_decode( $text, true );

			if( $bytes === false || $bytes === '' ) {
				\Nino\Http::fail( $request, 400, 'no file uploaded', 'upload_missing', [], 'file' );
				return;
			}

			\Nino\Modules\Crm\Api::made( $appData, 'Set the PDF '. ( $kind === 'logo' || $kind === 'signature' ? $kind : 'picture' ) );
			self::_answer( $request, Store::saveImage( $appData, $kind, $bytes ), static fn(): array => View::layout( $appData ) );
		}

		public static function apiLayoutImageDelete( array &$appData, array &$request ): void {

			if( self::_guard( $appData, $request ) === false )
				return;

			$kind = \Nino\Admin\Admin::postString( \Nino\Admin\Admin::postData(), 'kind' );

			\Nino\Modules\Crm\Api::made( $appData, 'Remove the PDF '. ( $kind === 'logo' || $kind === 'signature' ? $kind : 'picture' ) );
			self::_answer( $request, Store::deleteImage( $appData, $kind ), static fn(): array => View::layout( $appData ) );
		}

		/**
		 *	A PDF of the sample text with the layout and the design as they are saved, as base64 in the answer
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		&$request
		 *
		 *	@return 	void
		 */
		public static function apiLayoutPreview( array &$appData, array &$request ): void {

			if( self::_guard( $appData, $request ) === false )
				return;

			$previewed = Feature::previewLayout( $appData, [ 'tokens' => self::_tokens( $appData ) ] );

			if( isset( $previewed['fail'] ) === true )
				\Nino\Http::fail( $request, ...$previewed['fail'] );
			else
				\Nino\Http::ok( $request, [ 'pdf' => base64_encode( $previewed['bytes'] ), 'file' => 'design.pdf' ] );
		}

		public static function apiBlocksList( array &$appData, array &$request ): void {

			if( self::_guard( $appData, $request ) === false )
				return;

			\Nino\Http::ok( $request, View::blocks( $appData ) );
		}

		public static function apiBlockSave( array &$appData, array &$request ): void {

			if( self::_guard( $appData, $request ) === false )
				return;

			$data = \Nino\Admin\Admin::postData();

			\Nino\Modules\Crm\Api::made( $appData, 'Save PDF block "'. self::_named( $data ). '"' );
			self::_answer( $request, Store::saveBlock( $appData, $data ), static fn(): array => View::blocks( $appData ) );
		}

		public static function apiBlockDelete( array &$appData, array &$request ): void {

			if( self::_guard( $appData, $request ) === false )
				return;

			$data = \Nino\Admin\Admin::postData();

			\Nino\Modules\Crm\Api::made( $appData, 'Delete PDF block "'. self::_named( $data ). '"' );
			self::_answer( $request, Store::deleteBlock( $appData, \Nino\Admin\Admin::postString( $data, 'key' ) ), static fn(): array => View::blocks( $appData ) );
		}

		public static function apiTemplatesList( array &$appData, array &$request ): void {

			if( self::_guard( $appData, $request ) === false )
				return;

			\Nino\Http::ok( $request, View::templates( $appData ) );
		}

		/**
		 *	One template for the editor, with what the editor offers: the tags, the library, the paper
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		&$request
		 *
		 *	@return 	void
		 */
		public static function apiTemplateGet( array &$appData, array &$request ): void {

			if( self::_guard( $appData, $request ) === false )
				return;

			$key = \Nino\Admin\Admin::postString( \Nino\Admin\Admin::postData(), 'key' );
			$template = Feature::template( $appData, $key );

			if( $template === null )
				\Nino\Http::fail( $request, 404, 'there is no such template', 'pdf_template_missing' );
			else
				\Nino\Http::ok( $request, [ 'key' => $key, 'template' => $template ] + View::offer( $appData ) );
		}

		public static function apiTemplateSave( array &$appData, array &$request ): void {

			if( self::_guard( $appData, $request ) === false )
				return;

			$data = \Nino\Admin\Admin::postData();
			$saved = Store::saveTemplate( $appData, $data );

			\Nino\Modules\Crm\Api::made( $appData, 'Save PDF template "'. self::_named( $data ). '"' );
			self::_answer( $request, $saved, static fn(): array => [ 'key' => $saved['key'] ?? '' ] + View::templates( $appData ) );
		}

		public static function apiTemplateDuplicate( array &$appData, array &$request ): void {

			if( self::_guard( $appData, $request ) === false )
				return;

			$data = \Nino\Admin\Admin::postData();
			$made = Store::duplicateTemplate( $appData, \Nino\Admin\Admin::postString( $data, 'key' ), \Nino\Admin\Admin::postString( $data, 'name' ) );
			$copy = is_string( $made['key'] ?? null ) === true ? $made['key'] : '';

			\Nino\Modules\Crm\Api::made( $appData, 'Duplicate PDF template "'. self::_named( $data ). '" as "'. $copy. '"' );
			self::_answer( $request, $made, static fn(): array => [ 'key' => $copy ] + View::templates( $appData ) );
		}

		/**
		 *	Delete a template: the Store asks the callers of the feature whether anything uses it (/pdf/template/uses) and refuses with 409 while one counts a use or gave no valid answer
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		&$request
		 *
		 *	@return 	void
		 */
		public static function apiTemplateDelete( array &$appData, array &$request ): void {

			if( self::_guard( $appData, $request ) === false )
				return;

			$data = \Nino\Admin\Admin::postData();
			$deleted = Store::deleteTemplate( $appData, \Nino\Admin\Admin::postString( $data, 'key' ) );

			// A refusal for what a listener got wrong is no 200 and so no line of the log's own: it is written here, without the names of the listeners
			if( ( $deleted['fail'][2] ?? '' ) === 'pdf_template_unknown' ) {

				$user = \Nino\Auth::getCurrentUser( $appData );

				if( $user !== false )
					\Nino\Modules\Crm\Api::record( $appData, (string) $user['mail'], 'Refused to delete PDF template "'. self::_named( $data ). '": no valid answer about its use' );
			}

			\Nino\Modules\Crm\Api::made( $appData, 'Delete PDF template "'. self::_named( $data ). '"' );
			// The listeners are named by class; the owner is told the key of the package, and nothing else of them
			if( ( $deleted['fail'][2] ?? '' ) === 'pdf_template_unknown' )
				$deleted['fail'][3] = [ self::_keys( $appData, (string) ( $deleted['fail'][3][0] ?? '' ) ) ];

			self::_answer( $request, $deleted, static fn(): array => View::templates( $appData ) );
		}

		/**
		 *	The keys of the packages the classes of listeners belong to, as a list for a sentence
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$names				Class names, separated by a comma and a blank
		 *
		 *	@return 	string										The keys, separated by a comma and a blank; a dash for what is no package of a feature
		 */
		private static function _keys( array &$appData, string $names ): string {

			$modules = [];

			foreach( \Nino\Features::all( $appData ) as $feature )
				$modules[ ltrim( (string) $feature['module'], '\\' ) ] = (string) $feature['key'];

			$keys = [];

			foreach( explode( ', ', $names ) as $name )
				if( isset( $modules[ ltrim( $name, '\\' ) ] ) === true )
					$keys[] = $modules[ ltrim( $name, '\\' ) ];

			return $keys === [] ? '–' : implode( ', ', array_unique( $keys ) );
		}

		/**
		 *	A PDF of what the editor holds - saved or not - with the sample values of the vocabulary, as base64 in the answer. It is written nowhere
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		&$request
		 *
		 *	@return 	void
		 */
		public static function apiPreview( array &$appData, array &$request ): void {

			if( self::_guard( $appData, $request ) === false )
				return;

			$previewed = Feature::preview( $appData, \Nino\Admin\Admin::postData(), [ 'tokens' => self::_tokens( $appData ) ] );

			if( isset( $previewed['fail'] ) === true )
				\Nino\Http::fail( $request, ...$previewed['fail'] );
			else
				\Nino\Http::ok( $request, [ 'pdf' => base64_encode( $previewed['bytes'] ), 'file' => 'preview.pdf' ] );
		}
	}
}
