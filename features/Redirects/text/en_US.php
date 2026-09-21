<?php
// The Redirects feature's own workbench strings, merged into its fills while
// the feature is active (see the panel's text()) - same keys and shape the
// workbench's own text/<locale>.php has. A %s is filled by the script
return [
	'[[/_admin/nav/redirects]]'								=> 'Redirects',

	'[[/_admin/redirects/label/title]]'				=> 'Redirects',
	'[[/_admin/redirects/label/tile]]'				=> 'Addresses with no answer',
	'[[/_admin/redirects/hint/rules]]'				=> 'An old address, where it sends, and whether it moved for good. A rule is only consulted where nothing else answers the address, so it can never take a page off the site.',
	'[[/_admin/redirects/hint/missing]]'			=> 'Addresses somebody asked for that nothing answered - most asked for first. This is where the rules worth writing come from.',
	'[[/_admin/redirects/hint/off]]'					=> 'Remembering is switched off in this feature\'s settings, so nothing new is collected.',
	'[[/_admin/redirects/hint/limit]]'				=> 'At most %s are kept. A scanner finds more in an afternoon than anybody reads.',
	'[[/_admin/redirects/hint/subtree]]'			=> 'Everything below the old address moves with it: what stood after the old path stands after the new one.',

	'[[/_admin/redirects/label/from]]'				=> 'Old address',
	'[[/_admin/redirects/label/to]]'					=> 'Sends to',
	'[[/_admin/redirects/label/status]]'			=> 'Kind',
	'[[/_admin/redirects/label/subtree]]'			=> 'Everything below it too',
	'[[/_admin/redirects/label/hits]]'				=> 'Followed',
	'[[/_admin/redirects/label/last]]'				=> 'Last',
	'[[/_admin/redirects/label/path]]'				=> 'Address',
	'[[/_admin/redirects/label/count]]'				=> 'Asked for',
	'[[/_admin/redirects/label/new]]'					=> 'New redirect',
	'[[/_admin/redirects/label/edit]]'				=> 'Edit',
	'[[/_admin/redirects/label/delete]]'			=> 'Delete',
	'[[/_admin/redirects/label/save]]'				=> 'Save',
	'[[/_admin/redirects/label/cancel]]'			=> 'Cancel',
	'[[/_admin/redirects/label/back]]'				=> 'Back to the rules',
	'[[/_admin/redirects/label/probe]]'				=> 'Try an address',
	'[[/_admin/redirects/label/probe-run]]'		=> 'Check',
	'[[/_admin/redirects/label/forget]]'			=> 'Forget all',
	'[[/_admin/redirects/label/forget-one]]'	=> 'Forget',
	'[[/_admin/redirects/label/make]]'				=> 'Make a rule',
	'[[/_admin/redirects/label/search]]'			=> 'Search',
	'[[/_admin/redirects/label/on]]'					=> 'on',
	'[[/_admin/redirects/label/off]]'					=> 'off',

	'[[/_admin/redirects/status/301]]'				=> 'Moved for good (301)',
	'[[/_admin/redirects/status/302]]'				=> 'Moved for now (302)',

	'[[/_admin/redirects/empty/rules]]'				=> 'No redirect yet.',
	'[[/_admin/redirects/empty/missing]]'			=> 'Nothing has been asked for in vain since this was switched on.',
	'[[/_admin/redirects/empty/nomatch]]'			=> 'Nothing matches.',

	'[[/_admin/redirects/msg/saved]]'					=> 'Saved.',
	'[[/_admin/redirects/msg/deleted]]'				=> 'Deleted.',
	'[[/_admin/redirects/msg/forgotten]]'			=> 'Forgotten.',
	'[[/_admin/redirects/msg/probe-route]]'		=> 'A page answers this address, so no rule is consulted for it.',
	'[[/_admin/redirects/msg/probe-rule]]'		=> '"%s" answers it, and sends to %s.',
	'[[/_admin/redirects/msg/probe-nothing]]'	=> 'Nothing answers it. A visitor gets the 404 page, and the address is remembered as missing.',

	'[[/_admin/redirects/error/from]]'				=> 'An old address is a path of this site, like /old/page.',
	'[[/_admin/redirects/error/to]]'					=> 'A target is a path of this site, or an https address of another one.',
	'[[/_admin/redirects/error/loop]]'				=> 'That rule would send a visitor back into itself: %s',
	'[[/_admin/redirects/error/unknown]]'			=> 'There is no rule for "%s".',
	'[[/_admin/redirects/error/taken]]'				=> 'There is already a rule for "%s". Delete or edit that one first.',
	'[[/_admin/redirects/error/load]]'				=> 'The redirects could not be read.',

	'[[/_admin/redirects/confirm/delete]]'		=> 'Delete the redirect for "%s"?',
	'[[/_admin/redirects/confirm/forget]]'		=> 'Forget every address on this list? The next request for one puts it back.',
	'[[/_admin/redirects/note/incomplete]]'	=> 'A rule without a usable "from" and "to" was dropped.',
	'[[/_admin/redirects/note/duplicate]]'	=> 'A second rule for "%s" was dropped - the first one answers it.',
	'[[/_admin/redirects/note/status]]'			=> 'Rule "%s": %d is not a redirect status, so 301 applies.',
	'[[/_admin/redirects/note/dropped]]'		=> 'Rule "%s" was dropped: %r',
	'[[/_admin/redirects/reason/self]]'			=> 'it sends the address to itself',
	'[[/_admin/redirects/reason/subtree]]'	=> 'it sends everything under it to an address that is under it again',
	'[[/_admin/redirects/error/status]]'		=> 'That is not a redirect status.',
];
