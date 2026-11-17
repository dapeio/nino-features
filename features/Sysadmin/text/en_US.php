<?php
declare(strict_types=1);

// The Sysadmin feature's own workbench words, merged into the workbench's fills while the feature is on
// (see the panel's text()) and never copied into a project. The words of the mail are another matter: they are
// the project's, and come with the install unit (install/text)
return [
	'[[/_admin/sysadmin/nav]]'								=> 'Sysadmin',
	'[[/_admin/sysadmin/hint/intro]]'					=> 'Tells you by mail when something is wrong with the site, and at a fixed rhythm that all is well. A page request sets it off, so it needs no cron job. The recipients, the distance of two problem mails and the rhythm are on the Settings tab.',
	'[[/_admin/sysadmin/hint/none]]'					=> 'There is no address yet, so nothing is sent.',
	'[[/_admin/sysadmin/hint/findings-empty]]'	=> 'The report finds nothing.',
	'[[/_admin/sysadmin/label/settings]]'			=> 'Open the settings',
	'[[/_admin/sysadmin/label/recipients]]'		=> 'Recipients',
	'[[/_admin/sysadmin/label/nobody]]'				=> 'nobody',
	'[[/_admin/sysadmin/label/gap]]'					=> 'Problem mail at most every',
	'[[/_admin/sysadmin/label/hour]]'				=> '1 hour',
	'[[/_admin/sysadmin/label/aborted]]'			=> 'Runs that ended without a word',
	'[[/_admin/sysadmin/label/hours]]'				=> '%s hours',
	'[[/_admin/sysadmin/label/last-problem]]'	=> 'Last problem mail',
	'[[/_admin/sysadmin/label/beat]]'					=> 'Sign of life',
	'[[/_admin/sysadmin/label/last-beat]]'		=> 'Last mail of any kind',
	'[[/_admin/sysadmin/label/next-beat]]'		=> 'Sign of life due',
	'[[/_admin/sysadmin/label/failed]]'				=> 'Last send that failed',
	'[[/_admin/sysadmin/label/failed-value]]'	=> '%s (failed attempts in a row: %s)',
	'[[/_admin/sysadmin/label/never]]'				=> 'never',
	'[[/_admin/sysadmin/label/state]]'					=> 'State',
	'[[/_admin/sysadmin/label/findings]]'			=> 'What the report finds now',
	'[[/_admin/sysadmin/label/send]]'					=> 'Send test mail',
	'[[/_admin/sysadmin/beat/off]]'						=> 'off',
	'[[/_admin/sysadmin/beat/daily]]'					=> 'daily',
	'[[/_admin/sysadmin/beat/weekly]]'				=> 'weekly',
	'[[/_admin/sysadmin/beat/monthly]]'				=> 'monthly',
	'[[/_admin/sysadmin/rank/notice]]'							=> 'Notice',
	'[[/_admin/sysadmin/rank/warning]]'							=> 'Warning',
	'[[/_admin/sysadmin/rank/error]]'							=> 'Error',
	'[[/_admin/sysadmin/msg/sending]]'				=> 'Sending ...',
	'[[/_admin/sysadmin/msg/sent]]'						=> 'Test mail sent (recipients: %s).',
	'[[/_admin/sysadmin/confirm/test]]'				=> 'Send a test mail with the report to %s?',
	'[[/_admin/sysadmin/error/load]]'					=> 'The report could not be loaded.',
	'[[/_admin/sysadmin/error/send]]'					=> 'The test mail could not be sent.',

	// The sentences of the codes the actions answer with
	'[[/_admin/error/sysadmin_none]]'					=> 'There is no address to send to. Enter one on the Settings tab.',
	'[[/_admin/error/sysadmin_wait]]'					=> 'A test mail went out a moment ago. Try again in %s seconds.',
	'[[/_admin/error/sysadmin_ratelimit]]'		=> 'The site has sent as many test mails this hour as the system may. Try again later.',
	'[[/_admin/error/sysadmin_failed]]'				=> 'The mail server did not take the mail. The Dashboard says since when mail fails.',
];
