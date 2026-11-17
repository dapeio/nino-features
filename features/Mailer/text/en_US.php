<?php
// The Mailer feature's own workbench strings, merged into its fills while
// the feature is active (see the panel's text()) - same keys and shape the
// workbench's own text/<locale>.php has. The two "mail/*" keys are the test
// mail's subject and body - the only mail this feature ever composes itself.
return [
	'[[/_admin/nav/mailer]]'							=> 'Mailer',
	'[[/_admin/mailer/hint/intro]]'				=> 'Sends a test mail through the settings of this feature, over the same transport and the same per-ip limit every other mail on the site goes through.',
	'[[/_admin/mailer/label/status]]'		=> 'Sending through %s:%s (%s).',
	'[[/_admin/mailer/label/unconfigured]]'	=> 'Not configured yet - mail is still going out through the server\'s own mail(). Set a host in the settings of this feature first.',
	'[[/_admin/mailer/label/to]]'					=> 'Recipient',
	'[[/_admin/mailer/label/test]]'				=> 'Test mail',
	'[[/_admin/mailer/help/to]]'				=> 'The address the test mail goes to. It begins with the address of your own account.',
	'[[/_admin/mailer/label/send]]'				=> 'Send test mail',
	'[[/_admin/mailer/msg/sending]]'			=> 'Sending ...',
	'[[/_admin/mailer/msg/sent]]'					=> 'Test mail sent.',
	'[[/_admin/mailer/label/errors]]'				=> 'Last errors',
	'[[/_admin/mailer/hint/errors-empty]]'			=> 'No failed sends recorded.',
	'[[/_admin/mailer/error/load]]'				=> 'Failed to load the status.',
	'[[/_admin/mailer/encryption/starttls]]'	=> 'STARTTLS',
	'[[/_admin/mailer/encryption/tls]]'	=> 'TLS from the start',
	'[[/_admin/mailer/encryption/none]]'	=> 'None',
	'[[/_admin/mailer/error/send-reason]]'	=> 'The mail could not be sent: %s',
	'[[/_admin/mailer/error/send]]'				=> 'Failed to send the test mail.',
	'[[/_admin/mailer/mail/subject]]'		=> 'Nino test mail',
	'[[/_admin/mailer/mail/body]]'				=> '<p>This is a test mail from your Nino installation, sent through the Mailer feature\'s SMTP settings.</p><p>If it arrived, the configured host, port, encryption and credentials all work.</p>',

	// The sentences of the codes the test mail answers with (Admin::apiSendTest())
	'[[/_admin/error/mailer_address]]'		=> 'That is not an email address.',
	'[[/_admin/error/mailer_ratelimit]]'	=> 'The limit of mails an hour from this address is reached. Try again later.',
];
