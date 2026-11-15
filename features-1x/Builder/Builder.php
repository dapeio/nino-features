<?php
declare(strict_types=1);
/**
 *	Nino							A compact filesystembased php framework
 *	Modules\Builder		see _nino/Nino/Modules/Modules.php for the
 *										package-level docblock
 *
 *	@package					Dape/Nino
 *	@author						David Perchermeier <mail@dape.io>
 *	@link							https://github.com/dapeio/nino
 */
namespace Nino\Modules {

	/**
	 *	Nino						A compact filesystembased php framework
	 *	Builder					Reads a page template - a page-*.tpl of the project - into a
	 *									model of sections, columns and components, and writes the
	 *									model back. The file is the truth: the markup of a section
	 *									is plain classes of Nino.css, its content shortcode calls
	 *									of the components the Components module registers, and what
	 *									the builder does not read it leaves byte for byte. A
	 *									project without this feature renders every such file the
	 *									same, since nothing in it is the builder's.
	 *
	 *									The feature has no part in a request to the site: init()
	 *									registers nothing. What it has is the workbench panel,
	 *									and under it three classes that know nothing of the panel
	 *									- Reader and Writer, which are pure functions of a source
	 *									and a model, and Document, which joins them to the
	 *									project's files, its text keys and its image slots. The
	 *									grammar and the model are described in Reader.
	 *
	 *	@package				Dape/Nino
	 *	@author					David Perchermeier <mail@dape.io>
	 *	@link						https://github.com/dapeio/nino
	 */
	class Builder {

		/**
		 *	The /_admin screen this feature brings along
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	array										Panel class names
		 */
		public static function adminPanels( array &$appData ): array {
			return [ \Nino\Modules\Builder\Admin::class ];
		}

		/**
		 *	Nothing to register: the site renders what the Components module
		 *	registers, and the builder is only the workbench's way of writing it
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	void
		 */
		public static function init( array &$appData ): void {
		}
	}

}
