<?php
declare(strict_types=1);

namespace Nino\Modules {

	/**
	 *	Nino							A compact filesystembased php framework
	 *	Modules\Themelab		The blocks of the project: a list of them - those of the project to change, those of the theme in use to look at - and an editor that is the
	 *										editor of the Builder on a sheet of its own, the draft of one block. A block is made of the components the register has, saved as a block of
	 *										\Nino\Blocks - block.json, block.tpl, text/<language>.json below private/blocks/ - after the one rule of the kernel has taken it
	 *
	 *										What a request does is little: one that carries the query of a draft of a block is asked whether the session holds it and the account
	 *										still has the right /_admin/themelab/manage, and then draws it - the draft of a block with the trace, alone on the page and without the head and the foot of the site, or the small picture of a block
	 *										in the list without the trace and without the scripts of the site. Everything else is the API of the workbench (Themelab\Admin)
	 *
	 *	@package					Dape/Nino
	 *	@author						David Perchermeier <mail@dape.io>
	 *	@link							https://github.com/dapeio/nino
	 */
	class Themelab {

		// The one right of the panel: whoever has it sees the panel and may use every action; whoever has not, has neither
		public const string PERM = '/_admin/themelab/manage';

		// What the drafts of the previews are called in \Nino\Preview: the block that is worked on, and the picture of a block in the list
		public const string KIND = 'themelab';
		public const string TILE = 'themelab-tile';

		// The right this panel had as the feature "blocks", and the mark in config.php that the move of it was made: a project that switched on Themelab where Blocks was on
		// keeps who could use the panel
		public const string BEFORE = '/_admin/blocks/manage';
		public const string MOVED = '/nino/themelab/moved';

		public static function init( array &$appData ): void {

			\Nino\Callbacks::registerCallback( $appData, '/nino/http/request', [ self::class, 'request' ] );

			if( ( $appData[self::MOVED] ?? false ) !== true )
				self::move( $appData );
		}

		/**
		 *	What the project that had the feature "blocks" is given once, at the first request after Themelab is on: the accounts and the roles that name the right of the old panel
		 *	- the entry itself, or the entry of all of its rights - have the right of this one in its place, and no one else is given anything: a blanket of the site or of the whole
		 *	panel holds what it held, an account that never had the old right has no new one. What the old feature left in config.php - its class in the list of the modules and the
		 *	record of its version - goes with it. The own blocks, the frames and the variants are files of the project and are not touched. The mark is written with it, so that a
		 *	later request moves nothing
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	void
		 */
		public static function move( array &$appData ): void {

			// Under the lock that config.php, the roles and the accounts are written under: a request that came while another moved the rights finds the mark of it and writes nothing
			if( \Nino\Filesystem::lockFile( $appData, '/config.php' ) === false )
				return;

			try {

				$stored = (array) \Nino\Filesystem::getFileContent( $appData, '/config.php', [] );

				if( ( $stored[self::MOVED] ?? false ) === true ) {
					$appData[self::MOVED] = true;
					return;
				}

				// The roles as the file has them now, not as this request read them at its start
				if( is_array( $stored['/nino/auth/roles'] ?? null ) === true )
					$appData['/nino/auth/roles'] = $stored['/nino/auth/roles'];

				self::_move( $appData );
			}
			finally {
				\Nino\Filesystem::unlockFile( $appData, '/config.php' );
			}
		}

		private static function _move( array &$appData ): void {

			$swap = static function( mixed $perms ): mixed {

				if( is_array( $perms ) === false )
					return $perms;

				$out = [];

				foreach( $perms as $perm ) {

					if( $perm === self::BEFORE )
						$perm = self::PERM;
					else if( $perm === '/_admin/blocks/*' )
						$perm = '/_admin/themelab/*';

					if( in_array( $perm, $out, true ) === false )
						$out[] = $perm;
				}

				return $out;
			};
			$roles = (array) ( $appData['/nino/auth/roles'] ?? [] );
			$users = (array) ( $appData['/nino/auth/user'] ?? [] );

			foreach( $roles as $id => $role )
				if( is_array( $role ) === true && is_array( $role['perms'] ?? null ) === true )
					$appData['/nino/auth/roles'][$id]['perms'] = $swap( $role['perms'] );

			foreach( $users as $mail => $user )
				if( is_array( $user ) === true && is_array( $user['perms'] ?? null ) === true )
					$appData['/nino/auth/user'][$mail]['perms'] = $swap( $user['perms'] );

			if( ( $appData['/nino/auth/roles'] ?? [] ) !== $roles || ( $appData['/nino/auth/user'] ?? [] ) !== $users )
				\Nino\AppData::writeContentData( $appData, [ '/nino/auth/roles', '/nino/auth/user' ] );

			$appData[self::MOVED] = true;

			\Nino\Filesystem::mutate( $appData, '/config.php', static function( array $content ): array {

				$content['/nino/modules'] = array_values( array_filter( (array) ( $content['/nino/modules'] ?? [] ), static fn( mixed $class ): bool => ltrim( (string) $class, '\\' ) !== 'Nino\\Modules\\Blocks' ) );

				if( is_array( $content[\Nino\Features::STATE_KEY] ?? null ) === true )
					unset( $content[\Nino\Features::STATE_KEY]['blocks'] );

				$content[self::MOVED] = true;

				return $content;
			} );
		}

		public static function adminPanels( array &$appData ): array {
			return [ \Nino\Modules\Themelab\Admin::class ];
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
			 *	sheet - the two calls of the frame and the block - and a draft that is anything else is not drawn. The sheet is drawn as it is - so that the marks the drawing leaves in the page
			 *	have the places of the tree of the sheet, which the editor finds its components by - and the two templates that the calls of the frame are, for this request, the two halves of
			 *	the page of the pictures: what comes before the block and what comes after it
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
			$at = \Nino\Modules\Themelab\Sheet::rootAt( $nodes );

			if( $at === null )
				return false;

			[ $head, $foot ] = array_pad( explode( '[[block]]', \Nino\Feature::template( $appData, '/features/Builder/templates', 'tile' ), 2 ), 2, '' );

			\Nino\Modules\Template::swap( $appData, \Nino\Modules\Themelab\Sheet::FRAMES[0], $head );
			\Nino\Modules\Template::swap( $appData, \Nino\Modules\Themelab\Sheet::FRAMES[1], $foot );

			return \Nino\Modules\Builder::draw( $appData, $request, $draft, true, true );
		}
	}
}
