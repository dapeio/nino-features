<?php
declare(strict_types=1);
/**
 *	Nino									A compact filesystembased php framework
 *	Modules\Hello\Admin		see features/Hello/Hello.php for the feature's own
 *												docblock
 *
 *	@package							Dape/Nino
 *	@author								David Perchermeier <mail@dape.io>
 *	@link									https://github.com/dapeio/nino
 */
namespace Nino\Modules\Hello {

	/**
	 *	Nino						A compact filesystembased php framework
	 *	Admin						The feature's /_admin screen: one field, one button.
	 *
	 *									A panel is a class of static methods and nothing else -
	 *									no base class, no registration file. The kernel collects
	 *									it through Hello::adminPanels(), reads the methods below
	 *									to find out what to draw and what to route, and hands
	 *									every action a request to answer.
	 *
	 *									Everything above apiList() is declaration. The two
	 *									methods below it are the screen: one that says what is
	 *									there, one that takes what came back. Both guard
	 *									themselves - the routing does not, and must not be
	 *									trusted to.
	 *
	 *									The frontend is assets/admin.js, which draws into the
	 *									pane named in panes() and talks to the actions named in
	 *									actions(). Its words come from text/<locale>.php beside
	 *									it, never from strings in the script.
	 *
	 *	@package				Dape/Nino
	 *	@author					David Perchermeier <mail@dape.io>
	 *	@link						https://github.com/dapeio/nino
	 */
	class Admin {

		/*	The permission every action here checks, and the one a role has to
			hold for the panel to be drawn at all. One per panel, named after
			it. A panel a role does not hold is not rendered, and its actions
			answer 403 regardless - so the guard and the menu never disagree	*/
		public const string MANAGE_PERM = '/_admin/hello/manage';

		public static function perm(): string {
			return self::MANAGE_PERM;
		}

		/*	The actions this panel answers, as name => callable. The name is
			what assets/admin.js posts; everything before the slash is by
			convention the panel's own prefix, and nothing enforces it beyond
			two panels being unable to claim the same name	*/
		public static function actions(): array {
			return [
				'hello/list' => [ self::class, 'apiList' ],
				'hello/save' => [ self::class, 'apiSave' ],
			];
		}

		/*	The menu entry: uri fragment, the text fill for its label, where it
			sorts, and a group. The group is decorative for a feature's panel -
			every one of them lands under "features" regardless, so that
			granting that group stays a bounded grant	*/
		public static function nav(): array {
			return [ 'hello', '/_admin/hello/nav', 90, 'features' ];
		}

		// The menu icon, inline. 24x24, currentColor, no fill - the workbench
		// colours it with the rest of the rail
		public static function icon(): string {
			return '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3a9 9 0 0 1 9 9 9 9 0 0 1-9 9H3l2.2-2.2A9 9 0 0 1 12 3Z"/><path d="M8.5 12h.01M12 12h.01M15.5 12h.01"/></svg>';
		}

		/*	The ids assets/admin.js draws into. The workbench renders one empty
			<div> per name and shows whichever the uri names; a panel with one
			screen names one	*/
		public static function panes(): array {
			return [ 'hello-form' ];
		}

		/*	The panel's own script and stylesheet, loaded exactly while the
			panel is. Panels::relative() turns a __DIR__ path into the
			project-relative one the workbench bundles	*/
		public static function assets(): array {
			return [
				\Nino\Admin\Panels::relative( dirname( __DIR__ ). '/assets/admin.js' ),
				\Nino\Admin\Panels::relative( dirname( __DIR__ ). '/assets/admin.css' ),
			];
		}

		// ...and its words, one <locale>.php per interface language. Not the
		// same directory as install/text/: those are the site's words, these
		// are the workbench's
		public static function text(): string {
			return \Nino\Admin\Panels::relative( dirname( __DIR__ ). '/text' );
		}

		/*	What the activity log records, per action. An empty string is "do
			not log this" - right for anything that only reads, wrong for
			anything that changes something somebody may later have to explain	*/
		public static function log( string $action, array $data ): string {
			return match( $action ) {
				'hello/save'	=> 'Change Hello Name',
				default				=> '',
			};
		}

		/**
		 *	Everything the screen draws.
		 *
		 *	One json answer, not html: the workbench renders a pane and the
		 *	panel's own script fills it, so a panel never writes markup on the
		 *	server. \Nino\Http::ok() is the json-and-200 for that.
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$request			(reference) Current server request
		 *
		 *	@return 	void
		 */
		public static function apiList( array &$appData, array &$request ): void {

			/*	Every action guards itself, first line, no exceptions. The
				routing does not do it, and a panel that is not rendered is not a
				panel that cannot be posted to: without an account this answers
				401, without the permission 403	*/
			if( \Nino\Admin\Admin::guardPerm( $appData, $request, self::MANAGE_PERM ) === false )
				return;

			\Nino\Http::ok( $request, [
				'name' 			=> \Nino\Modules\Hello::name( $appData ),
				// Sent so the screen can say what it falls back to, rather than
				// the script carrying a copy of a value that lives in php
				'fallback'	=> \Nino\Modules\Hello::DEFAULT_NAME,
				// ...and the setting the Features panel owns, read-only here: the
				// screen shows the whole greeting, and points at where the other
				// half of it is edited
				'greeting'	=> \Nino\Modules\Hello::greeting( $appData ),
			] );
		}

		/**
		 *	...and what comes back.
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$request			(reference) Current server request
		 *
		 *	@return 	void
		 */
		public static function apiSave( array &$appData, array &$request ): void {

			if( \Nino\Admin\Admin::guardPerm( $appData, $request, self::MANAGE_PERM ) === false )
				return;

			// The posted json, already decoded. Never $_POST directly: the
			// workbench posts one 'data' field and this is what unpacks it
			$data = \Nino\Admin\Admin::postData();
			$name = trim( (string) ( $data['name'] ?? '' ) );

			/*	Validate here rather than in the script. The screen checks too,
				because an error that arrives before the request is a better
				error - but a request is a request, and the only check that
				counts is the one on this side	*/
			if( mb_strlen( $name ) > 60 ) {
				\Nino\Http::fail( $request, 400, 'that is longer than a name' );
				return;
			}

			if( \Nino\Modules\Hello::setName( $appData, $name ) === false ) {
				\Nino\Http::fail( $request, 500, 'could not write '. \Nino\Modules\Hello::PATH );
				return;
			}

			// Answer with the state the screen should now show, so a save needs
			// no second request to redraw from
			\Nino\Http::ok( $request, [ 'name' => \Nino\Modules\Hello::name( $appData ) ] );
		}
	}

}
