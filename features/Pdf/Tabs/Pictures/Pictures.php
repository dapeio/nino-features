<?php
declare(strict_types=1);
/**
 *	Nino									A compact filesystembased php framework
 *	Modules\Pdf\Tabs\Pictures	see features/Pdf/Admin/Admin.php for the panel's own docblock
 *
 *	@package							Dape/Nino
 *	@author								David Perchermeier <mail@dape.io>
 *	@link									https://github.com/dapeio/nino
 */
namespace Nino\Modules\Pdf\Tabs {

	/**
	 *	Nino							A compact filesystembased php framework
	 *	Tabs\Pictures			The tab of the pictures in the PDF pane: the logo and the signature of the owner, each
	 *										put in and taken away at once. A tab of the kernel's own kind
	 *										(see \Nino\Admin\Panels::collect()): its uri, its label, its
	 *										permission - the panel's - its mounts and its words. It has no
	 *										action of its own: pdf/layout/image stay on the panel, and so does
	 *										the script - the shell asks Nino.admin['pdf-pictures'] for this
	 *										tab's screen, which stands in the file the panel names.
	 *
	 *	@package					Dape/Nino
	 *	@author						David Perchermeier <mail@dape.io>
	 *	@link							https://github.com/dapeio/nino
	 */
	class Pictures {

		// Every action stays on the panel, whichever tab of the pane asks for it
		public static function actions(): array {
			return [];
		}

		// After the panel's own screen; no group is named: a feature's tab sits in 'features' like its panel
		public static function nav(): array {
			return [ 'pdf-pictures', '/_admin/nav/pdf-pictures', 10 ];
		}

		public static function perm(): string {
			return \Nino\Modules\Pdf::PERM;
		}

		public static function panes(): array {
			return [ 'pdf-pictures' ];
		}

		// The script and the stylesheet of the tab are the panel's: one file holds the namespaces of the pane
		public static function assets(): array {
			return \Nino\Modules\Pdf\Admin::assets();
		}

		public static function text(): string {
			return \Nino\Modules\Pdf\Admin::text();
		}
	}
}
