<?php
// The Newsletter feature's own workbench strings, merged into its fills
// while the feature is active (see the panel's text()) - same keys and
// shape the editor's own text/<locale>.php has
return [
	'[[/_admin/nav/newsletter]]'							=> 'Newsletter',
	'[[/_admin/dashboard/label/newsletter]]'	=> 'Newsletter subscribers',

	'[[/_admin/newsletter/error/load]]'				=> 'The subscribers could not be loaded.',
	'[[/_admin/newsletter/error/delete]]'			=> 'The address could not be deleted.',
	'[[/_admin/error/newsletter_copy]]'				=> 'The BCC line could not be copied. Select the addresses and copy them with the keyboard.',
	'[[/_admin/newsletter/empty]]'						=> 'No signups yet.',
	'[[/_admin/newsletter/emptyfilter]]'			=> 'No signup has this status.',
	'[[/_admin/newsletter/nomatch]]'					=> 'No subscriber matches this search.',

	'[[/_admin/newsletter/label/subscribers]]'	=> 'Subscribers',
	'[[/_admin/newsletter/label/search]]'			=> 'Search subscribers',
	'[[/_admin/newsletter/label/mail]]'				=> 'Email',
	'[[/_admin/newsletter/label/status]]'			=> 'Status',
	'[[/_admin/newsletter/label/date]]'				=> 'Date',
	'[[/_admin/newsletter/label/copy-bcc]]'		=> 'Copy BCC line',
	'[[/_admin/newsletter/label/export]]'			=> 'Export as CSV',
	'[[/_admin/newsletter/label/filename]]'		=> 'newsletter.csv',
	'[[/_admin/newsletter/status/subscribed]]'	=> 'Confirmed',
	'[[/_admin/newsletter/status/pending]]'		=> 'Pending',

	'[[/_admin/newsletter/confirm/delete-title]]'	=> 'Delete subscriber',
	'[[/_admin/newsletter/confirm/delete]]'		=> 'Delete %s from the newsletter? The address stays off the list, even when a backup is restored.',
	'[[/_admin/newsletter/msg/bcc-one]]'			=> 'BCC line with 1 address copied.',
	'[[/_admin/newsletter/msg/bcc-many]]'			=> 'BCC line with %s addresses copied.',
	'[[/_admin/newsletter/hint/unsubscribe]]'	=> 'Subscribers without a personal link ask for one at this address. Put it into every BCC mail you send: %s',

	// The sentence of the code newsletter_unknown (Admin::apiDelete())
	'[[/_admin/error/newsletter_unknown]]'		=> 'That address is not on the list.',
];
