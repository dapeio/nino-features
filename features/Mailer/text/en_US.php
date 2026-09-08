<?php
// The Mailer feature's own workbench strings, merged into its fills while
// the feature is active (see the panel's text()) - same keys and shape the
// workbench's own text/<locale>.php has. The two "mail/*" keys are the test
// mail's subject and body - the only mail this feature ever composes itself.
return [
	'[[/_admin/nav/mailer]]'							=> 'Mailer',
	'[[/_admin/mailer/label/title]]'			=> 'SMTP mail delivery',
	'[[/_admin/mailer/hint/intro]]'				=> 'Sends a test mail through the settings configured for this feature in the Features panel, over the same transport and the same per-ip limit (5 per hour) every other mail on the site goes through.',
	'[[/_admin/mailer/label/status]]'		=> 'Sending through %host:%port (%encryption).',
	'[[/_admin/mailer/label/unconfigured]]'	=> 'Not configured yet - mail is still going out through the server\'s own mail(). Set a host in the Features panel first.',
	'[[/_admin/mailer/label/to]]'					=> 'Send a test mail to',
	'[[/_admin/mailer/label/send]]'				=> 'Send test mail',
	'[[/_admin/mailer/msg/loading]]'			=> 'Loading …',
	'[[/_admin/mailer/msg/sending]]'			=> 'Sending …',
	'[[/_admin/mailer/msg/sent]]'					=> 'Test mail sent.',
	'[[/_admin/mailer/error/load]]'				=> 'Failed to load the status.',
	'[[/_admin/mailer/error/send]]'				=> 'Failed to send the test mail.',
	'[[/_admin/mailer/mail/subject]]'		=> 'Nino test mail',
	'[[/_admin/mailer/mail/body]]'				=> '<p>This is a test mail from your Nino installation, sent through the Mailer feature\'s SMTP settings.</p><p>If it arrived, the configured host, port, encryption and credentials all work.</p>',
];
