<?php
declare(strict_types=1);
/**
 *	Nino										A compact filesystembased php framework
 *	Modules\Builder\FramesTab	see _nino/Nino/Modules/Modules.php for the
 *														package-level docblock
 *
 *	@package								Dape/Nino
 *	@author									David Perchermeier <mail@dape.io>
 *	@link										https://github.com/dapeio/nino
 */
namespace Nino\Modules\Builder {

	/**
	 *	Nino							A compact filesystembased php framework
	 *	Modules\Builder		The tab "Header/footer" of the pane of the pages (#builder-frames): the header and the footer of the site and the frames the site has beside them - the list of
	 *										them, made, renamed, chosen, taken away and kept as a block, and the editor of one (assets/admin-frames.js). It is a tab of the kernel's own kind (see
	 *										Admin::tabs()): its uri, its word, its two mounts - the list and the sheet of the editor - and its files, under the right of the design of the panel (see perm());
	 *										the actions are the panel's own (builder/frame-<x>, see Admin::actions() and Frames), each of which asks for the door and the step itself
	 *
	 *	@package					Dape/Nino
	 *	@author						David Perchermeier <mail@dape.io>
	 *	@link							https://github.com/dapeio/nino
	 */
	class FramesTab {

		public static function nav(): array {
			return [ 'builder-frames', '/_admin/builder/tab/frames', 10 ];
		}

		// The tab is for the accounts that design: the right of the step - held as the panel holds it, by the scope below. A kernel that knows no scope() of a tab would check the right alone and
		// shut out an account that holds the door only and designs, as the panel counts it: with such a kernel the tab keeps the right of the panel, as before
		public static function perm(): string {
			return is_callable( self::_areaCall() ) === true ? \Nino\Modules\Builder::DESIGN : Admin::perm();
		}

		/**
		 *	The call of the kernel that checks a tab by its area, as class and method
		 *
		 *	@return 	array{string, string}
		 */
		private static function _areaCall(): array {
			return [ '\\Nino\\Admin\\Admin', 'mayUse' ];
		}

		public static function scope(): string {
			return \Nino\Modules\Builder::SCOPE;
		}

		public static function assets(): array {
			return [ \Nino\Admin\Panels::relative( dirname( __DIR__ ). '/assets/admin-frames.js' ) ];
		}

		// The mounts of the tab: the list of the frames and the sheet of the editor of one
		public static function template(): string {
			return \Nino\Admin\Panels::relative( dirname( __DIR__ ). '/templates/frames' );
		}

		// The words of the tab are those of the panel
		public static function text(): string {
			return Admin::text();
		}

		public static function actions(): array {
			return [];
		}

		public static function log( string $action, array $data ): string {
			return '';
		}
	}
}
