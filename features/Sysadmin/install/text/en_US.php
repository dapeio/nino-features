<?php
// The words of the mail Sysadmin sends, merged into the project's texts add-only: the project may change them.
// The mail is written in the native language of the site, with a %s where a number, a time or a name goes in.
return [
	'[[/feature/sysadmin/mail/kind-problem]]'		=> 'Problem',
	'[[/feature/sysadmin/mail/kind-beat]]'			=> 'Sign of life',
	'[[/feature/sysadmin/mail/kind-test]]'			=> 'Test mail',
	'[[/feature/sysadmin/mail/subject-problem]]'	=> '%s - Problem (new findings: %s)',
	'[[/feature/sysadmin/mail/subject-beat]]'		=> '%s - Sign of life',
	'[[/feature/sysadmin/mail/subject-test]]'		=> '%s - Test mail',
	'[[/feature/sysadmin/mail/findings]]'			=> 'Findings (%s)',
	'[[/feature/sysadmin/mail/new]]'					=> 'new',
	'[[/feature/sysadmin/mail/none]]'					=> 'Nothing to report.',
	'[[/feature/sysadmin/mail/backup]]'				=> 'Backup',
	'[[/feature/sysadmin/mail/log]]'					=> 'Error log since %s',
	'[[/feature/sysadmin/mail/operation]]'			=> 'Operation',
	'[[/feature/sysadmin/mail/server]]'				=> 'Server',
	'[[/feature/sysadmin/mail/workbench]]'			=> 'Workbench:',

	'[[/feature/sysadmin/rank/notice]]'						=> 'Notice',
	'[[/feature/sysadmin/rank/warning]]'						=> 'Warning',
	'[[/feature/sysadmin/rank/error]]'						=> 'Error',

	'[[/feature/sysadmin/finding/backup-failed]]'		=> 'The backup failed: %s',
	'[[/feature/sysadmin/finding/manifest-active]]'	=> 'The feature "%s" is switched on and its manifest is refused: %s',
	'[[/feature/sysadmin/finding/manifest-off]]'		=> 'The feature in "%s" is refused: %s',
	'[[/feature/sysadmin/finding/form-cap-hour]]'		=> 'The form cap of the hour is reached: submissions are refused.',
	'[[/feature/sysadmin/finding/form-cap-month]]'	=> 'The form cap of the month is reached: no more submissions are recorded.',
	'[[/feature/sysadmin/finding/disk]]'				=> 'The disk is nearly full: %s free of %s (%s).',
	'[[/feature/sysadmin/finding/log-errors]]'		=> '%s errors in the log since %s.',

	'[[/feature/sysadmin/finding/run-aborted]]'		=> 'A run of this feature ended without a word (count: %s), by a time or memory limit, say.',
	'[[/feature/sysadmin/finding/backup-stopped]]'	=> 'A backup run that began at %s was ended before it was over (%s).',
	'[[/feature/sysadmin/backup/stopped]]'			=> 'A run that began at %s did not end (%s).',
	'[[/feature/sysadmin/operation/aborted]]'		=> 'Runs of this feature that ended without a word: %s',
	'[[/feature/sysadmin/cause/time]]'				=> 'time limit',
	'[[/feature/sysadmin/cause/memory]]'				=> 'memory limit',
	'[[/feature/sysadmin/cause/error]]'				=> 'error',
	'[[/feature/sysadmin/cause/none]]'				=> 'cause unknown',
	'[[/feature/sysadmin/word/silent]]'				=> 'no reason given',

	'[[/feature/sysadmin/backup/off]]'				=> 'The daily backup is switched off.',
	'[[/feature/sysadmin/backup/never]]'			=> 'No backup has been made yet.',
	'[[/feature/sysadmin/backup/last]]'				=> 'Last backup: %s, age in days: %s, size: %s',
	'[[/feature/sysadmin/backup/failed]]'			=> 'The last attempt failed: %s',

	'[[/feature/sysadmin/log/counts]]'				=> 'Errors: %s, warnings: %s',

	'[[/feature/sysadmin/operation/mail-ok]]'			=> 'Mail delivery: no failed send.',
	'[[/feature/sysadmin/operation/mail-failing]]'	=> 'Mail delivery fails since %s (failed attempts: %s).',
	'[[/feature/sysadmin/operation/cap-ok]]'			=> 'Form caps: not reached.',
	'[[/feature/sysadmin/operation/cap-hour]]'		=> 'Form caps: the cap of the hour is reached.',
	'[[/feature/sysadmin/operation/cap-month]]'		=> 'Form caps: the cap of the month is reached.',
	'[[/feature/sysadmin/operation/locked]]'			=> 'Accounts locked out: %s',
	'[[/feature/sysadmin/operation/refused-none]]'	=> 'Refused feature manifests: none.',
	'[[/feature/sysadmin/operation/refused]]'		=> 'Refused feature manifests: %s',
	'[[/feature/sysadmin/operation/send-failed]]'	=> 'This report could not be sent at %s (failed attempts: %s).',

	'[[/feature/sysadmin/server/disk]]'				=> 'Disk: %s free of %s (%s)',
	'[[/feature/sysadmin/server/data]]'				=> 'Size of private/data: %s',
	'[[/feature/sysadmin/server/versions]]'		=> 'PHP %s, Nino %s',
];
