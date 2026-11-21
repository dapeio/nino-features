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
	 *	Modules\Builder		The tab "Header/footer" of the pane of the pages (#builder-frames): the default choice of the site for the header and the footer, as the list of
	 *										the pages draws it from the answer of builder/list (assets/admin-frames.js). It is a tab of the kernel's own kind (see Admin::tabs()): its uri, its word,
	 *										its mount and its files, under the right of the panel; it has no action of its own - the list, the choice and the way back are those of the panel
	 *
	 *	@package					Dape/Nino
	 *	@author						David Perchermeier <mail@dape.io>
	 *	@link							https://github.com/dapeio/nino
	 */
	class FramesTab {

		public static function nav(): array {
			return [ 'builder-frames', '/_admin/builder/tab/frames', 10 ];
		}

		public static function perm(): string {
			return Admin::perm();
		}

		public static function assets(): array {
			return [ \Nino\Admin\Panels::relative( dirname( __DIR__ ). '/assets/admin-frames.js' ) ];
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
