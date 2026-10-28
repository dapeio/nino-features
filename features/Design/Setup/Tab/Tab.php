<?php
declare(strict_types=1);
/**
 *	Nino									A compact filesystembased php framework
 *	Modules\Design\Setup\Tab	see features/Design/Design.php for the feature's
 *												own docblock
 *
 *	@package							Dape/Nino
 *	@author								David Perchermeier <mail@dape.io>
 *	@link									https://github.com/dapeio/nino
 */
namespace Nino\Modules\Design\Setup {

	/**
	 *	Nino							A compact filesystembased php framework
	 *	Setup\Tab					The tab "Design" of the Design pane: the setup in six
	 *										views - the ones Setup::TABS names, Templates, Type,
	 *										Surfaces, Colours, Motion and CSS - with the preview
	 *										beside them and the bar under them, which the panel's own
	 *										screen (the themes) stands on as well (see
	 *										\Nino\Modules\Design\Admin).
	 *
	 *										A tab of the kernel's own kind (see \Nino\Admin\Panels::collect()):
	 *										its uri, its label, its permission - the panel's - its mount
	 *										and its words. It has no action of its own: design/list,
	 *										design/save, design/apply and the rest stay on the panel,
	 *										which holds the setup the three screens of the pane share,
	 *										and so does the script - the shell asks Nino.admin['design-setup']
	 *										for this tab's screen, and that namespace stands in the file the
	 *										panel names, beside Nino.admin.design and the loader's.
	 *
	 *	@package					Dape/Nino
	 *	@author						David Perchermeier <mail@dape.io>
	 *	@link							https://github.com/dapeio/nino
	 */
	class Tab {

		public const string MANAGE_PERM = \Nino\Modules\Design\Admin::MANAGE_PERM;

		// Every action stays on the panel, whichever tab of the pane asks for it
		public static function actions(): array {
			return [];
		}

		// A tab of the Design pane (see \Nino\Modules\Design\Admin::tabs()): the weight puts it first after the panel's
		// own screen, the Settings tab the registry attaches comes last. No group is named: a feature's tab sits in
		// 'features' like its panel
		public static function nav(): array {
			return [ 'design-setup', '/_admin/nav/design-setup', 10 ];
		}

		public static function perm(): string {
			return self::MANAGE_PERM;
		}

		public static function panes(): array {
			return [ 'design-setup-form' ];
		}

		// The script and the stylesheet of the tab are the panel's: one file holds the three namespaces of the pane
		// (a file named twice is bundled once)
		public static function assets(): array {
			return \Nino\Modules\Design\Admin::assets();
		}

		// A tab's words are the feature's words - the same text/ its panel names, said again here so the tab describes itself
		public static function text(): string {
			return \Nino\Modules\Design\Admin::text();
		}
	}
}
