<?php
declare(strict_types=1);

/**
 *	Nino features
 *	events-members.php	The second half of events-smoke.php, which runs it in a process of its own: a directory of features that holds Basis, this
 *											feature and a stand-in of the feature Members. It is no test of its own - the file is read by the first half when the
 *											process is told where the features are (NINO_EVENTS_FEATURES) - and asks the feature with Members there and off, which is the
 *											case where the class exists and the feature must not ask it, and with Members on: what a visitor, a member of the group and
 *											a member of another group see, the feed of a member and the token it is asked by, the hook that removes an account.
 */

// What the Nino 1 feature wrote for the feeds and files of the three members and of the visitor of them: the same dates, the same accounts
const EVENTS_MEMBERS_BYTES = [
	'feed.ada' => '0e3924c75445a49905d7994e194b35d0208715481de593a54928f0eb7807962a',
	'feed.bob' => '0ff988136528e77612cbbab3db6627da3d38e4cc08268b05a348073f06f2db68',
	'feed.eve' => '8a1773b9b138d7940179f7c5ea3e4b0f8c902884e2e4500cf5252885b8819eb7',
	'single.board-meeting.ada' => '7a2b2a18e8d0553af346fdd74a858f7ccbe7532a14509b4d90ee44b41d1ad4d4',
	'single.staff-party.bob' => '25cc39b17918d28b9e48ebadbd454c8b3aaaeaf96af819ca008aafd7522f57cf',
];

$appData = eventsBoot( 'events-members' );
$appData['./nino/locales/current'] = 'de_DE';
eventsPlant( $appData );
$upcoming = [ 'running', 'at-now', 'soon', 'all-day', 'later', 'two-day' ];
$publicUids = [ 'past-old', 'past-recent', 'ended-today', 'running', 'just-over', 'at-now', 'soon', 'all-day', 'later', 'two-day' ];
$account = static fn( string $id, string $group, bool $active = true ): array => [ 'id' => $id, 'email' => $id. '@example.org', 'name' => $id, 'group' => $group, 'active' => $active, 'created' => 1, 'login' => 1 ];
$ada = 'aaaaaaaaaaaa';
$bob = 'bbbbbbbbbbbb';
$eve = 'cccccccccccc';
$dan = 'dddddddddddd';
$appData['./events-test/accounts'] = [ $ada => $account( $ada, 'board' ), $bob => $account( $bob, 'staff' ), $eve => $account( $eve, '' ), $dan => $account( $dan, 'board', false ) ];
$signIn = static function( ?string $id ) use ( &$appData ): void {
	unset( $appData['./events-test/signed-in'] );

	if( $id !== null )
		$appData['./events-test/signed-in'] = $id;
};

echo "Members is there and is off: the class is loaded all the same\n";

check( 'the stand-in is a feature of the directory, its class is found, and the feature of the catalogue is off', class_exists( '\\Nino\\Modules\\Members' ) === true && ( \Nino\Features::get( $appData, 'members' )['active'] ?? null ) === false );
$signIn( $ada );
$token = \Nino\Modules\Events\Tokens::forAccount( $appData, $ada );
check( 'the feature does not ask it: nobody is in a group, even where an account is signed in there, and a date of a group is in no list, no link and no file', \Nino\Modules\Events::members( $appData ) === false && \Nino\Modules\Events::viewer( $appData ) === ''
	&& eventsList( $appData ) === $upcoming && in_array( 'board-meeting', eventsList( $appData, 'scope="all"' ), true ) === false && eventsRender( $appData, '[event-ics text="board-meeting"]' ) === '' && eventsServe( $appData, '/.events/board-meeting.ics' )[0] === 404 );
check( '...a token is not looked up: the feed of it is the public feed, and the link to the feed is the public one and makes no token', eventsServe( $appData, '/.events.ics?k='. $token )[4] === eventsServe( $appData, '/.events.ics' )[4] && eventsServe( $appData, '/.events.ics?k='. $token )[2] === 'public, max-age=3600'
	&& str_contains( eventsRender( $appData, '[event-feed]' ), '?k=' ) === false );
check( '...and nothing was asked of Members in all that', ( $appData['./events-test/asked'] ?? 0 ) === 0 );
\Nino\Filesystem::putFileContent( $appData, '/data/events.php', [] );
$signIn( null );

echo "\nWith Members on: what a visitor, a member of the group and a member of another group see\n";

check( 'Members is switched on beside the feature', \Nino\Features::activate( $appData, 'members' ) === true && in_array( '\\Nino\\Modules\\Members', (array) ( \Nino\Filesystem::getFileContent( $appData, '/config.php', [] )['/nino/modules'] ?? [] ), true ) === true );
eventsReload( $appData );
$appData['./nino/locales/current'] = 'de_DE';
check( '...and the feature sees it on', \Nino\Modules\Events::members( $appData ) === true && \Nino\Modules\Events::viewer( $appData ) === '' );
check( 'a visitor sees what he saw before', eventsList( $appData ) === $upcoming );

$signIn( $ada );
check( 'Ada of the board has the group of the board for the viewer', \Nino\Modules\Events::viewer( $appData ) === 'board' && \Nino\Modules\Events::visible( $appData, 'board' ) === true && \Nino\Modules\Events::visible( $appData, 'staff' ) === false && \Nino\Modules\Events::visible( $appData, '' ) === true );
check( '...she sees the dates of the board - Board typed with a capital is the group board - in their place by start, and not those of the staff or the date of a group that makes no slug',
	eventsList( $appData ) === [ 'running', 'at-now', 'soon', 'board-meeting', 'all-day', 'later', 'two-day' ] && eventsList( $appData, 'scope="all" limit="9"' ) === [ 'past-old', 'past-recent', 'ended-today', 'running', 'just-over', 'at-now', 'soon', 'board-meeting', 'all-day' ] );
check( '...limit counts what she sees: the fourth date is the one of her board', eventsList( $appData, 'limit="4"' ) === [ 'running', 'at-now', 'soon', 'board-meeting' ] && eventsList( $appData, 'order="desc" limit="2"' ) === [ 'two-day', 'later' ] );
check( '...the link to the file of the date of her group is hers, and the one of the staff\'s is nobody\'s of hers', count( eventsFind( eventsRender( $appData, '[event-ics text="board-meeting"]' ), '//a[@href="/.events/board-meeting.ics"]' ) ) === 1
	&& eventsRender( $appData, '[event-ics text="staff-party"]' ) === '' && eventsRender( $appData, '[event-ics text="strange"]' ) === '' );
check( '...her chip of a date of the board is drawn, the chip of a date of the staff is not in her list', count( eventsFind( eventsRender( $appData, '[events query="group=Board"][event-when .start][/events]' ), '//time' ) ) === 1 && eventsList( $appData, 'query="group=staff"' ) === [] );

$signIn( $bob );
check( 'Bob sees the staff\'s dates, and not the board\'s', eventsList( $appData ) === [ 'running', 'at-now', 'soon', 'staff-party', 'all-day', 'later', 'two-day' ] && eventsRender( $appData, '[event-ics text="board-meeting"]' ) === '' && eventsRender( $appData, '[event-ics text="staff-party"]' ) !== '' );
$signIn( $eve );
check( 'Eve, who is in no group, sees what a visitor sees', eventsList( $appData ) === $upcoming && \Nino\Modules\Events::viewer( $appData ) === '' );
$signIn( $dan );
check( 'Dan, whose account is switched off, is nobody for Members and so for the feature: a visitor', eventsList( $appData ) === $upcoming && \Nino\Modules\Events::viewer( $appData ) === '' );
$signIn( null );
check( 'and a visitor again sees the public ones', eventsList( $appData ) === $upcoming );

echo "\nThe file of one date of a group\n";

$gone = [ 404, 'text/plain; charset=utf-8', 'no-store', '', 'Not Found' ];
check( 'a date of a group is the same 404 as a date that is not there - for a visitor, for a member of another group and for the staff on a date of the board', eventsServe( $appData, '/.events/board-meeting.ics' ) === $gone
	&& ( static function() use ( &$appData, $signIn, $bob, $gone ): bool { $signIn( $bob ); return eventsServe( $appData, '/.events/board-meeting.ics' ) === $gone; } )() );
$signIn( $ada );
check( '...a date of a group that makes no slug, and one of another group, is that 404 whoever asks', eventsServe( $appData, '/.events/strange.ics' ) === $gone && eventsServe( $appData, '/.events/staff-party.ics' ) === $gone );
$mine = eventsServe( $appData, '/.events/board-meeting.ics' );
check( '...a member of the group gets the file, and a cache keeps it from nobody else', $mine[0] === 200 && $mine[1] === 'text/calendar; charset=utf-8' && $mine[2] === 'private, no-store' && $mine[3] === 'inline; filename="board-meeting.ics"' && eventsUids( $mine[4] ) === [ 'board-meeting' ]
	&& in_array( 'SUMMARY:Vorstand', eventsEvent( $mine[4], 'board-meeting' ), true ) );
check( '...and the bytes are those the Nino 1 feature wrote for her, and for Bob the file of the staff', eventsSame( 'single.board-meeting.ada', $mine[4], EVENTS_MEMBERS_BYTES ) === true
	&& ( static function() use ( &$appData, $signIn, $bob ): bool { $signIn( $bob ); return eventsSame( 'single.staff-party.bob', eventsServe( $appData, '/.events/staff-party.ics' )[4], EVENTS_MEMBERS_BYTES ); } )() );
$signIn( $ada );
check( '...a public date is the same file for her as for everyone', eventsServe( $appData, '/.events/soon.ics' ) === ( static function() use ( &$appData, $signIn ): array { $signIn( null ); return eventsServe( $appData, '/.events/soon.ics' ); } )() );
$signIn( null );

echo "\n[event-feed] and the token of a member\n";

$link = eventsFind( eventsRender( $appData, '[event-feed]' ), '//a' )[0] ?? null;
check( 'a visitor gets the link to the public feed, with the word of the unit, the class and a type, and no token is made', $link !== null && $link->getAttribute( 'href' ) === 'https://example.org/club/.events.ics' && $link->getAttribute( 'class' ) === 'event-feed' && $link->textContent === 'Kalender abonnieren'
	&& $link->getAttribute( 'type' ) === 'text/calendar' && $link->getAttribute( 'rel' ) === 'nofollow' && eventsData( $appData ) === [] );

$signIn( $ada );
$member = eventsFind( eventsRender( $appData, '[event-feed]' ), '//a' )[0] ?? null;
preg_match( '~^https://example\.org/club/\.events\.ics\?k=([a-f0-9]{64})$~', (string) $member?->getAttribute( 'href' ), $found );
$adaToken = $found[1] ?? '';
check( 'a member who is signed in gets the link with a token of 64 hex characters, the word for their calendar', $adaToken !== '' && $member?->textContent === 'Deinen Kalender abonnieren' && $member?->getAttribute( 'class' ) === 'event-feed' );
check( '...the same token at every render after the first, so that a calendar app can be subscribed to it', eventsFind( eventsRender( $appData, '[event-feed]' ), '//a' )[0]?->getAttribute( 'href' ) === $member?->getAttribute( 'href' ) && count( eventsData( $appData )['tokens'] ?? [] ) === 1
	&& \Nino\Modules\Events\Tokens::account( $appData, $adaToken ) === $ada );
$signIn( $bob );
preg_match( '~\?k=([a-f0-9]{64})$~', (string) ( eventsFind( eventsRender( $appData, '[event-feed]' ), '//a' )[0]?->getAttribute( 'href' ) ?? '' ), $found );
$bobToken = $found[1] ?? '';
$signIn( $eve );
preg_match( '~\?k=([a-f0-9]{64})$~', (string) ( eventsFind( eventsRender( $appData, '[event-feed]' ), '//a' )[0]?->getAttribute( 'href' ) ?? '' ), $found );
$eveToken = $found[1] ?? '';
$signIn( null );
check( '...each member has a token of their own', $bobToken !== '' && $eveToken !== '' && count( array_unique( [ $adaToken, $bobToken, $eveToken ] ) ) === 3 && count( eventsData( $appData )['tokens'] ?? [] ) === 3 );

$adaFeed = eventsServe( $appData, '/.events.ics?k='. $adaToken );
check( 'the feed with a token has no session to go by - a calendar app has none - and is the public dates and those of the group of the account: Ada\'s are the board\'s, by start, and not for a shared cache',
	$adaFeed[0] === 200 && eventsUids( $adaFeed[4] ) === [ 'past-old', 'past-recent', 'ended-today', 'running', 'just-over', 'at-now', 'soon', 'board-meeting', 'all-day', 'later', 'two-day' ] && $adaFeed[2] === 'private, max-age=3600' && $adaFeed[1] === 'text/calendar; charset=utf-8' && $adaFeed[3] === 'inline; filename="events.ics"' );
$bobFeed = eventsServe( $appData, '/.events.ics?k='. $bobToken );
$eveFeed = eventsServe( $appData, '/.events.ics?k='. $eveToken );
check( '...Bob\'s are the staff\'s, Eve\'s - who has no group - the public ones, and a date of a group that makes no slug is in nobody\'s', eventsUids( $bobFeed[4] ) === [ 'past-old', 'past-recent', 'ended-today', 'running', 'just-over', 'at-now', 'soon', 'staff-party', 'all-day', 'later', 'two-day' ]
	&& eventsUids( $eveFeed[4] ) === $publicUids && in_array( 'strange', array_merge( eventsUids( $adaFeed[4] ), eventsUids( $bobFeed[4] ), eventsUids( $eveFeed[4] ) ), true ) === false );
check( '...the three files are those of the Nino 1 feature, byte for byte', eventsSame( 'feed.ada', $adaFeed[4], EVENTS_MEMBERS_BYTES ) === true && eventsSame( 'feed.bob', $bobFeed[4], EVENTS_MEMBERS_BYTES ) === true && eventsSame( 'feed.eve', $eveFeed[4], EVENTS_MEMBERS_BYTES ) === true );

$publicBody = eventsServe( $appData, '/.events.ics' );
$bad = [];
ninoWarnings();
foreach( [ '?k='. str_repeat( 'a', 64 ), '?k=abc', '?k=', '?k[]='. $adaToken, '?k='. strtoupper( $adaToken ), '?k='. $adaToken. 'x', '?k=%20'. $adaToken, '?k='. $adaToken. '%0A', '?k=%00'. substr( $adaToken, 1 ), '?other='. $adaToken ] as $query )
	if( eventsServe( $appData, '/.events.ics'. $query ) !== $publicBody )
		$bad[] = $query;
check( 'a token that is none, that nobody holds or that is not written as it was made is the public feed - no word says that it was wrong, and not a warning of php', $bad === [] && $publicBody[2] === 'public, max-age=3600' && ninoWarnings() === [] );

$appData['./events-test/accounts'][$bob]['active'] = false;
check( '...the token of an account that is switched off is the public feed', eventsServe( $appData, '/.events.ics?k='. $bobToken ) === $publicBody );
$appData['./events-test/accounts'][$bob]['active'] = true;
$appData['./events-test/accounts'][$ada]['group'] = 'staff';
$moved = eventsUids( eventsServe( $appData, '/.events.ics?k='. $adaToken )[4] );
check( '...and the group is the account\'s at the moment the feed is read: Ada moved to the staff, her link brings theirs', in_array( 'staff-party', $moved, true ) === true && in_array( 'board-meeting', $moved, true ) === false );
$appData['./events-test/accounts'][$ada]['group'] = 'board';

// The organizer is told the account the file is for: the member signed in for the file of a date, the one of the token for the feed, nobody for a visitor
$seen = [];
eventsOrganizerListen( $appData, [ 'name' => 'Owner' ], $seen );
eventsServe( $appData, '/.events.ics?k='. $adaToken );
eventsServe( $appData, '/.events.ics?k='. $eveToken );
eventsServe( $appData, '/.events.ics' );
eventsServe( $appData, '/.events.ics?k='. $bobToken. 'x' );
$signIn( $ada );
eventsServe( $appData, '/.events/soon.ics' );
$signIn( null );
eventsServe( $appData, '/.events/soon.ics' );
check( 'the listener of /events/organizer is told the account the file is for: the one of the token for the feed, the member signed in for a date, nobody for a visitor or for a token that is none', array_column( $seen, 'account' ) === [ $ada, $eve, null, null, $ada, null ] );
unset( $appData['./nino/callbacks'][\Nino\Modules\Events::ORGANIZER] );

$modules = $appData['/nino/modules'];
$appData['/nino/modules'] = array_values( array_diff( $modules, [ '\\Nino\\Modules\\Members' ] ) );
unset( $appData['./nino/features/all'] );
$signIn( $ada );
$asked = $appData['./events-test/asked'] ?? 0;
check( 'with Members switched off again the token is not looked up, the dates of the board are shown to nobody and the member\'s link is the public one',
	eventsServe( $appData, '/.events.ics?k='. $adaToken ) === $publicBody && in_array( 'board-meeting', eventsList( $appData, 'scope="all"' ), true ) === false
	&& eventsServe( $appData, '/.events/board-meeting.ics' )[0] === 404 && str_contains( eventsRender( $appData, '[event-feed]' ), '?k=' ) === false && ( $appData['./events-test/asked'] ?? 0 ) === $asked );
$appData['/nino/modules'] = $modules;
unset( $appData['./nino/features/all'] );
$signIn( null );

echo "\nAn account that is removed\n";

check( 'an account that is deleted has the public feed: its token stays in the file and opens nothing', ( static function() use ( &$appData, $eve, $eveToken, $publicBody ): bool {
	unset( $appData['./events-test/accounts'][$eve] );
	return eventsServe( $appData, '/.events.ics?k='. $eveToken )[4] === $publicBody[4] && \Nino\Modules\Events\Tokens::account( $appData, $eveToken ) === $eve;
} )() );
$told = [ 'id' => $eve, 'email' => 'cccccccccccc@example.org', 'group' => '' ];
\Nino\Callbacks::doCallbacks( $appData, '/members/removed', $told );
check( '...and when Members says it removed the account, the token goes out of the file and the account has no token any more, the others have theirs', \Nino\Modules\Events\Tokens::account( $appData, $eveToken ) === null
	&& \Nino\Modules\Events\Tokens::account( $appData, $adaToken ) === $ada && \Nino\Modules\Events\Tokens::account( $appData, $bobToken ) === $bob && count( eventsData( $appData )['tokens'] ?? [] ) === 2 );

\Nino\Filesystem::putFileContent( $appData, '/data/events.php', [] );
check( 'the file deleted ends every link at once: the three old tokens open the public feed', array_filter( [ $adaToken, $bobToken, $eveToken ], static fn( string $token ): bool => eventsServe( $appData, '/.events.ics?k='. $token )[4] !== $publicBody[4] ) === [] );

ninoDone( $appData );
