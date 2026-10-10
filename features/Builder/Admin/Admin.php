<?php
declare(strict_types=1);
/**
 *	Nino									A compact filesystembased php framework
 *	Modules\Builder\Admin	The /_admin panel of the Builder feature
 *
 *	@package							Dape/Nino
 *	@author								David Perchermeier <mail@dape.io>
 *	@link									https://github.com/dapeio/nino
 */
namespace Nino\Modules\Builder {

	/**
	 *	Nino							A compact filesystembased php framework
	 *	Admin							The Builder's workbench panel: a workspace, the whole pane
	 *										with the rail folded away, and the API its scripts work
	 *										through. Every action is a sentence to Document, which has
	 *										no request in it, and Admin says the answer: the status
	 *										Document gave, and what goes with it. A developer surface -
	 *										a save writes a template of the project and may make text
	 *										keys and image slots - so every action, the reads among
	 *										them, asks for MANAGE_PERM; the keys and the slots are made
	 *										by the panels that own them, which ask for their own.
	 *
	 *	@package					Dape/Nino
	 *	@author						David Perchermeier <mail@dape.io>
	 *	@link							https://github.com/dapeio/nino
	 */
	class Admin {

		public const string MANAGE_PERM = '/_admin/builder/manage';

		public static function perm(): string {
			return self::MANAGE_PERM;
		}

		public static function actions(): array {
			return [
				'builder/list'			=> [ self::class, 'apiList' ],
				'builder/create'		=> [ self::class, 'apiCreate' ],
				'builder/duplicate'	=> [ self::class, 'apiDuplicate' ],
				'builder/load'			=> [ self::class, 'apiLoad' ],
				'builder/save'			=> [ self::class, 'apiSave' ],
				'builder/delete'		=> [ self::class, 'apiDelete' ],
				'builder/source'		=> [ self::class, 'apiSource' ],
				'builder/registry'	=> [ self::class, 'apiRegistry' ],
			];
		}

		// A feature's panel sits in the group its nav() names, and in the
		// features group only where it names none (see \Nino\Admin\Panels):
		// the Builder names structure, beside Routes
		public static function nav(): array {
			return [ 'builder', '/_admin/builder/label/nav', 2, 'structure' ];
		}

		// The panel renders its own regions (see templates/panel.tpl) rather
		// than mount points a script fills
		public static function template(): string {
			return \Nino\Admin\Panels::relative( dirname( __DIR__ ). '/templates/panel' );
		}

		// The editor needs the whole width: the rail folds to its icons and
		// the pane loses its reading-width ceiling
		public static function layout(): string {
			return 'workspace';
		}

		public static function icon(): string {
			return '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="3" rx="2"/><path d="M3 9h18"/><path d="M9 21V9"/></svg>';
		}

		public static function assets(): array {
			return [
				\Nino\Admin\Panels::relative( dirname( __DIR__ ). '/assets/admin.js' ),
				\Nino\Admin\Panels::relative( dirname( __DIR__ ). '/assets/admin.css' ),
				// The editor of the blocks profile, which the content of an [html] component is written in
				'/_admin/assets/html-editor.js',
			];
		}

		public static function text(): string {
			return \Nino\Admin\Panels::relative( dirname( __DIR__ ). '/text' );
		}

		/*	What the activity log records, per action - a read says nothing	*/
		public static function log( string $action, array $data ): string {
			return match( $action ) {
				'builder/create'	=> 'Create Builder Template "'. (string) ( $data['name'] ?? '' ). '"',
				'builder/duplicate'	=> 'Duplicate Builder Template '. (string) ( $data['file'] ?? '' ). ' as "'. (string) ( $data['name'] ?? '' ). '"',
				'builder/save'		=> 'Save Builder Template '. (string) ( $data['file'] ?? '' ),
				'builder/delete'	=> 'Delete Builder Template '. (string) ( $data['file'] ?? '' ),
				default						=> '',
			};
		}

		/**
		 *	The page templates of the project
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$request			(reference) Current server request
		 *
		 *	@return 	void
		 */
		public static function apiList( array &$appData, array &$request ): void {

			if( \Nino\Admin\Admin::guardPerm( $appData, $request, self::MANAGE_PERM ) === false )
				return;

			self::_answer( $request, Document::list( $appData ) );
		}

		/**
		 *	A new, empty page template: name, and the frames above and below it
		 *	where the posted data names them
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$request			(reference) Current server request
		 *
		 *	@return 	void
		 */
		public static function apiCreate( array &$appData, array &$request ): void {

			if( \Nino\Admin\Admin::guardPerm( $appData, $request, self::MANAGE_PERM ) === false )
				return;

			$data = \Nino\Admin\Admin::postData();

			self::_answer( $request, Document::create(
				$appData,
				(string) ( $data['name'] ?? '' ),
				is_string( $data['header'] ?? null ) === true ? $data['header'] : null,
				is_string( $data['footer'] ?? null ) === true ? $data['footer'] : null
			) );
		}

		/**
		 *	A copy of a page template under a new name, with its text keys and
		 *	its image slots
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$request			(reference) Current server request
		 *
		 *	@return 	void
		 */
		public static function apiDuplicate( array &$appData, array &$request ): void {

			if( \Nino\Admin\Admin::guardPerm( $appData, $request, self::MANAGE_PERM ) === false )
				return;

			$data = \Nino\Admin\Admin::postData();

			self::_answer( $request, Document::duplicate( $appData, (string) ( $data['file'] ?? '' ), (string) ( $data['name'] ?? '' ) ) );
		}

		/**
		 *	One page template as its model, with the hash of the file
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$request			(reference) Current server request
		 *
		 *	@return 	void
		 */
		public static function apiLoad( array &$appData, array &$request ): void {

			if( \Nino\Admin\Admin::guardPerm( $appData, $request, self::MANAGE_PERM ) === false )
				return;

			self::_answer( $request, Document::load( $appData, (string) ( \Nino\Admin\Admin::postData()['file'] ?? '' ) ) );
		}

		/**
		 *	The model, written into the file - 409 where the file is no longer
		 *	the one the hash is of, unless the posted data says force
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$request			(reference) Current server request
		 *
		 *	@return 	void
		 */
		public static function apiSave( array &$appData, array &$request ): void {

			if( \Nino\Admin\Admin::guardPerm( $appData, $request, self::MANAGE_PERM ) === false )
				return;

			$data = \Nino\Admin\Admin::postData();

			if( is_array( $data['model'] ?? null ) === false ) {
				\Nino\Http::fail( $request, 400, 'there is no model to save', 'builder_model' );
				return;
			}

			self::_answer( $request, Document::save(
				$appData,
				(string) ( $data['file'] ?? '' ),
				$data['model'],
				(string) ( $data['hash'] ?? '' ),
				( $data['force'] ?? false ) === true
			) );
		}

		/**
		 *	The model as the file it would be written as, part by part - what
		 *	the source view shows and what a section is edited as HTML+ from.
		 *	Nothing is written
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$request			(reference) Current server request
		 *
		 *	@return 	void
		 */
		public static function apiSource( array &$appData, array &$request ): void {

			if( \Nino\Admin\Admin::guardPerm( $appData, $request, self::MANAGE_PERM ) === false )
				return;

			$data = \Nino\Admin\Admin::postData();

			if( is_array( $data['model'] ?? null ) === false ) {
				\Nino\Http::fail( $request, 400, 'there is no model to show', 'builder_model' );
				return;
			}

			self::_answer( $request, Document::source( $appData, $data['model'] ) );
		}

		/**
		 *	Delete a page template - not one a route renders
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$request			(reference) Current server request
		 *
		 *	@return 	void
		 */
		public static function apiDelete( array &$appData, array &$request ): void {

			if( \Nino\Admin\Admin::guardPerm( $appData, $request, self::MANAGE_PERM ) === false )
				return;

			self::_answer( $request, Document::delete( $appData, (string) ( \Nino\Admin\Admin::postData()['file'] ?? '' ) ) );
		}

		/**
		 *	What the editor's forms are built from: components, stacks, element
		 *	types with their fields, image slots, headers and footers
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$request			(reference) Current server request
		 *
		 *	@return 	void
		 */
		public static function apiRegistry( array &$appData, array &$request ): void {

			if( \Nino\Admin\Admin::guardPerm( $appData, $request, self::MANAGE_PERM ) === false )
				return;

			self::_answer( $request, Document::registry( $appData ) );
		}

		/**
		 *	An answer of Document as the workbench's: the body without its
		 *	status for a 200, an error with the status for the rest - what goes
		 *	with it in its params, the code for the script
		 *
		 *	@param		array 		&$request			(reference) Current server request
		 *	@param		array			$answer				What Document answered
		 *
		 *	@return 	void
		 */
		private static function _answer( array &$request, array $answer ): void {

			$status = (int) $answer['status'];

			if( $status === 200 ) {
				unset( $answer['status'] );
				\Nino\Http::ok( $request, $answer );
				return;
			}

			\Nino\Http::fail(
				$request,
				$status,
				(string) ( $answer['error'] ?? '' ),
				'builder_'. str_replace( '-', '_', (string) ( $answer['code'] ?? 'error' ) ),
				self::_params( $answer )
			);
		}

		/**
		 *	What goes with an error in its params: the problems of a refusal, else
		 *	the bare uris that the panel names in its own words - the keys of the
		 *	routes that render a template (it shows their addresses) or, for a
		 *	copy that finds keys or slots in its way, those two lists in that order
		 *
		 *	@param		array			$answer				What Document answered
		 *
		 *	@return 	array
		 */
		private static function _params( array $answer ): array {

			if( ( $answer['problems'] ?? [] ) !== [] )
				return (array) $answer['problems'];

			if( isset( $answer['keys'] ) === true || isset( $answer['slots'] ) === true )
				return [ array_values( (array) ( $answer['keys'] ?? [] ) ), array_values( (array) ( $answer['slots'] ?? [] ) ) ];

			return array_column( (array) ( $answer['usedBy'] ?? [] ), 'route' );
		}
	}

}
