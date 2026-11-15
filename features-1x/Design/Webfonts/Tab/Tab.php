<?php
declare(strict_types=1);
/**
 *	Nino										A compact filesystembased php framework
 *	Modules\Design\Webfonts\Tab	see features/Design/Design.php for the feature's
 *													own docblock
 *
 *	@package								Dape/Nino
 *	@author									David Perchermeier <mail@dape.io>
 *	@link										https://github.com/dapeio/nino
 */
namespace Nino\Modules\Design\Webfonts {

	/**
	 *	Nino							A compact filesystembased php framework
	 *	Webfonts\Tab			The tab "Schriftarten" of the Design pane: the loader of the
	 *										typefaces - two cards, the families the project has installed
	 *										and the signed list of those it can install (see Webfonts),
	 *										read when its button is pressed - with no preview and no bar:
	 *										an install or a removal happens when it is pressed and is no
	 *										part of the draft.
	 *
	 *										A tab of the kernel's own kind (see \Nino\Admin\Panels::collect()):
	 *										its uri, its label, its permission - the panel's - its mount
	 *										and its words. It has no action of its own: design/webfonts/*
	 *										stay on the panel (see \Nino\Modules\Design\Admin::actions()),
	 *										and so does the script - the shell asks
	 *										Nino.admin['design-typefaces'] for this tab's screen, which
	 *										stands in the file the panel names. The two settings of the
	 *										loader, the address of the repository and its public key,
	 *										stay under the Settings tab the registry attaches.
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

		// The tab of the Design pane (see \Nino\Modules\Design\Admin::tabs()): after the panel's own screen, before the Settings tab. No
		// group is named: a feature's tab sits in 'features' like its panel
		public static function nav(): array {
			return [ 'design-typefaces', '/_admin/nav/design-typefaces', 20 ];
		}

		public static function perm(): string {
			return self::MANAGE_PERM;
		}

		public static function panes(): array {
			return [ 'design-typefaces-form' ];
		}

		// The script and the stylesheet of the tab are the panel's: one file holds the namespaces of the pane
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
