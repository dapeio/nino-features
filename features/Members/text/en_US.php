<?php
declare(strict_types=1);

// The panel's own words, one file per interface language (see Admin::text()): merged into the workbench's fills while the feature is
// active, never into the project's text files. The site's words are the other set, in features/Members/install/text/
return [

	// The rail entry, named by Admin::nav() - and the name the head over the panel's screen shows
	'[[/_admin/members/nav]]'					=> 'Members',

	// The Dashboard's tile (Admin::summary())
	'[[/_admin/members/label/count]]'			=> 'Members',

	// The directory: what narrows it, what its columns are called, what it says where it is empty
	'[[/_admin/members/label/search]]'			=> 'Search members',
	'[[/_admin/members/label/filter]]'			=> 'Group',
	'[[/_admin/members/filter/all]]'			=> 'All groups',
	'[[/_admin/members/filter/none]]'			=> 'No group',
	'[[/_admin/members/label/name]]'			=> 'Name',
	'[[/_admin/members/label/state]]'			=> 'State',
	'[[/_admin/members/label/active]]'			=> 'Active',
	'[[/_admin/members/label/login]]'			=> 'Last sign-in',
	'[[/_admin/members/state/active]]'			=> 'Active',
	'[[/_admin/members/state/inactive]]'		=> 'Inactive',
	'[[/_admin/members/empty]]'					=> 'No members yet.',
	'[[/_admin/members/empty/filter]]'			=> 'No members found.',

	// The bar of the list and the buttons of a row
	'[[/_admin/members/label/new]]'				=> 'New member',
	'[[/_admin/members/label/signout]]'			=> 'Sign everybody out',
	'[[/_admin/members/label/link]]'			=> 'Send link',

	// The dialog of a new member
	'[[/_admin/members/dialog/new]]'			=> 'Create member',
	'[[/_admin/members/label/create]]'			=> 'Create member',
	'[[/_admin/members/label/email]]'			=> 'Email address',
	'[[/_admin/members/label/group]]'			=> 'Group',
	'[[/_admin/members/hint/group]]'			=> 'One word, eg. premium. Leave empty for no group.',
	'[[/_admin/members/label/invite]]'			=> 'Invitation',
	'[[/_admin/members/hint/invite]]'			=> 'The member gets a link to sign in for the first time. Made without a mail, the link is shown to you once and kept nowhere.',
	'[[/_admin/members/invite/mail]]'			=> 'Send by email',
	'[[/_admin/members/invite/link]]'			=> 'Make the link, send no email',
	'[[/_admin/members/invite/none]]'			=> 'No invitation',
	'[[/_admin/members/msg/added]]'				=> 'Member created.',
	'[[/_admin/members/msg/invited]]'			=> 'Member created, invitation sent.',
	'[[/_admin/members/msg/invited-quiet]]'		=> 'Member created, invitation made without an email.',

	// The dialog of a change
	'[[/_admin/members/dialog/edit]]'			=> 'Change member',
	'[[/_admin/members/hint/active]]'			=> 'A member who is not active cannot sign in, and is signed out at once.',

	// A link to sign in, sent from a row, and the link that is shown once
	'[[/_admin/members/dialog/link-send]]'		=> 'Send link',
	'[[/_admin/members/hint/notify]]'			=> 'The link works once and for the time of a code. Made without a mail, it is shown to you once and kept nowhere; whoever has it signs in as this member.',
	'[[/_admin/members/invite/link-only]]'		=> 'Make the link, send no email',
	'[[/_admin/members/msg/sent]]'				=> 'Link sent.',
	'[[/_admin/members/msg/sent-quiet]]'		=> 'Link made without an email.',
	'[[/_admin/members/dialog/link]]'			=> 'Link to sign in',
	'[[/_admin/members/label/the-link]]'		=> 'Link',
	'[[/_admin/members/hint/link-once]]'		=> 'This link is shown now only and kept nowhere. Whoever has it signs in as this member, so hand it over yourself.',
	'[[/_admin/members/label/copy]]'			=> 'Copy link',
	'[[/_admin/members/msg/copied]]'			=> 'Link copied.',

	// The questions
	'[[/_admin/members/confirm/delete-title]]'	=> 'Delete member',
	'[[/_admin/members/confirm/delete]]'		=> 'Delete %s? The account is gone for good.',
	'[[/_admin/members/confirm/signout]]'		=> 'Every member is signed out, the devices that stay signed in among them, and has to ask for a new link or code.',
	'[[/_admin/members/msg/signedout]]'			=> 'Everybody is signed out.',

	'[[/_admin/members/loading]]'				=> 'Members are loading ...',
	'[[/_admin/members/state/all]]'				=> 'All states',
	'[[/_admin/members/row/open]]'				=> 'Open %s',
	'[[/_admin/members/row/link]]'				=> 'Send a link to %s',
	'[[/_admin/members/row/delete]]'			=> 'Delete %s',
	'[[/_admin/members/select/page]]'			=> 'Choose the members of this page',
	'[[/_admin/members/select/row]]'			=> 'Choose %s',
	'[[/_admin/members/confirm/delete-many-title]]'	=> 'Delete members',
	'[[/_admin/members/sort/by]]'				=> 'Sort members by',
	'[[/_admin/members/sort/none]]'				=> 'Order of the members',
	'[[/_admin/members/sort/up]]'				=> '%s, ascending',
	'[[/_admin/members/sort/down]]'				=> '%s, descending',
	'[[/_admin/members/confirm/delete-many]]'	=> 'Delete %s members? The accounts are gone for good.',
	'[[/_admin/members/msg/deleted-many]]'		=> '%s members deleted.',
	'[[/_admin/members/label/signout-one]]'		=> 'Sign out everywhere',
	'[[/_admin/members/confirm/signout-one]]'	=> '%s is signed out of every device and has to ask for a new link or code.',
	'[[/_admin/members/msg/signedout-one]]'		=> 'The member is signed out.',
	'[[/_admin/members/missing]]'				=> 'This member does not exist any more.',

	// What the panel says of a request the server gave no word for
	'[[/_admin/members/error/load]]'			=> 'The members could not be loaded.',
	'[[/_admin/members/error/add]]'				=> 'The member could not be created.',
	'[[/_admin/members/error/save]]'			=> 'The change could not be saved.',
	'[[/_admin/members/error/link]]'			=> 'The link could not be sent.',
	'[[/_admin/members/error/delete]]'			=> 'The member could not be deleted.',
	'[[/_admin/members/error/signout]]'			=> 'Nobody could be signed out.',

	// The sentences of the codes the panel answers with (Admin::_failed(), apiAdd(), apiLink())
	'[[/_admin/error/members_invalid]]'			=> 'That is not a valid email address.',
	'[[/_admin/error/members_taken]]'			=> 'A member with this address exists already.',
	'[[/_admin/error/members_long]]'			=> 'The name may have up to %s characters.',
	'[[/_admin/error/members_unknown]]'			=> 'There is no such member any more.',
	'[[/_admin/error/members_inactive]]'		=> 'A member who is not active cannot be sent a link.',
	'[[/_admin/error/members_mail]]'			=> 'The mail could not be sent. Check the mail settings and the address of the website, then try again.',
	'[[/_admin/error/members_ratelimit]]'		=> 'The cap of mails an hour from this address is reached. Try again later.',
];
