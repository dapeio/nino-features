<?php
declare(strict_types=1);

namespace Nino\Modules {

	/**
	 *	Nino							A compact filesystembased php framework
	 *	Modules\Blocks		The blocks of the project: a list of them - those of the project to change, those of the theme in use to look at - and an editor that is the
	 *										editor of the Builder on a sheet of its own, the draft of one block. A block is made of the components the register has, saved as a block of
	 *										\Nino\Blocks - block.json, block.tpl, text/<language>.json below private/blocks/ - after the one rule of the kernel has taken it
	 *
	 *										What a request does is little: one that carries the query of a draft of a block is asked whether the session holds it and the account
	 *										still has the right /_admin/blocks/manage, and then draws it - the draft of a block with the trace, alone on the page and without the head and the foot of the site, or the small picture of a block
	 *										in the list without the trace and without the scripts of the site. Everything else is the API of the workbench (Blocks\Admin)
	 *
	 *	@package					Dape/Nino
	 *	@author						David Perchermeier <mail@dape.io>
	 *	@link							https://github.com/dapeio/nino
	 */
	class Blocks {

		// The one right of the panel: whoever has it sees the panel and may use every action; whoever has not, has neither
		public const string PERM = '/_admin/blocks/manage';

		// What the drafts of the previews are called in \Nino\Preview: the block that is worked on, and the picture of a block in the list
		public const string KIND = 'blocks';
		public const string TILE = 'blocks-tile';

		public static function init( array &$appData ): void {
			\Nino\Callbacks::registerCallback( $appData, '/nino/http/request', [ self::class, 'request' ] );
		}

		public static function adminPanels( array &$appData ): array {
			return [ \Nino\Modules\Blocks\Admin::class ];
		}

		/**
		 *	The first stage of a request: does it ask for the draft of a block, and may it have it. A request without the query is not looked at; one with it that may not
		 *	have the draft - another session, an account without the right, a token that ran out - is the page as the visitors have it
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			&$request			(reference) The request
		 *
		 *	@return 	void
		 */
		public static function request( array &$appData, array &$request ): void {

			if( isset( $request['/nino/http/request']['query'][\Nino\Preview::QUERY] ) === false )
				return;

			$held = \Nino\Preview::held( $appData, $request, self::KIND, self::PERM );
			$tile = false;

			if( $held === null ) {

				$held = \Nino\Preview::held( $appData, $request, self::TILE, self::PERM );
				$tile = true;
			}

			if( $held === null )
				return;

			// The right was checked where the draft was held: the token is the session's and the account still has the permission
			if( $tile === true )
				\Nino\Modules\Builder::draw( $appData, $request, $held['data'], true, false );
			else
				self::alone( $appData, $request, $held['data'] );
		}

		/**
		 *	Draw the draft of a block alone: on the page of the Builder's pictures - the stylesheet of the site and a main - without the head and the foot of the site. The draft is the
		 *	sheet - the two calls of the frame and the block - and a draft that is anything else is not drawn; only the block of it is put on the page
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			&$request			(reference) The request
		 *	@param		array			$draft				template, source, fills, locale as \Nino\Modules\Builder::draw() takes them
		 *
		 *	@return 	bool											Whether it was drawn
		 */
		public static function alone( array &$appData, array &$request, array $draft ): bool {

			if( is_string( $draft['source'] ?? null ) === false )
				return false;

			$nodes = \Nino\Modules\Builder\Tree::read( $appData, $draft['source'] );
			$at = \Nino\Modules\Blocks\Sheet::rootAt( $nodes );

			if( $at === null )
				return false;

			$page = \Nino\Feature::template( $appData, '/features/Builder/templates', 'tile' );
			$draft['source'] = str_replace( '[[block]]', rtrim( \Nino\Markup::write( [ $nodes[$at] ], \Nino\Components::containers( $appData ) ) ), $page );

			return \Nino\Modules\Builder::draw( $appData, $request, $draft, true, true );
		}
	}
}
