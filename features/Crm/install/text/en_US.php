<?php
declare(strict_types=1);

// The words the site says - merged into the project's own text/en_US.php on every activation, add-only: a key the project already has stays. From then on an
// editor keeps them current in the Text panel and never opens a feature directory. The panel's own words are a different set, in features/Crm/text/.
return [

	// The test mail of the wordmark, mail-brand-test.tpl
	'[[/feature/crm/mail-brand/subject]]'	=> 'Test mail',
	'[[/feature/crm/mail-brand/title]]'		=> 'Test mail',
	'[[/feature/crm/mail-brand/intro]]'		=> 'This mail shows the banner with your wordmark the way your customers see it in their mail program. Open it in the mail programs you want to check.',
	'[[/feature/crm/mail-brand/notice]]'	=> 'You triggered this mail yourself. It goes to the contact address of the company.',
];
