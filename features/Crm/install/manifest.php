<?php
declare(strict_types=1);

// The unit \Nino\Features::activate() applies, add-only (see docs/features.md, "The install unit"): the four templates of the band at the head of the mails - the markup is the project's, so that it can
// look like the project - and the picture place of the logo in them; the element types of the projects and of their phases, with the four phases as elements; the section of the privacy policy of the CRM.
// The four words of the test mail of the wordmark come with the text files. No page and no route: the places of the core are registered by Crm::init(). Nothing is filled at request time as a text key, so
// there is no blacklist
return [
	'templates'		=> [ 'mail/crm-head.tpl', 'mail/crm-head-word.tpl', 'mail/crm-head-domain.tpl', 'mail/crm-head-sign.tpl' ],
	'elementTypes'	=> [ 'crm-projects.php', 'crm-phases.php' ],
	'elements'		=> [ 'crm-phases' => 'elements/crm-phases.php', 'privacy' => 'elements/privacy.php' ],
	// 220 px wide in the mail, a wordmark of the shape of the logo slots, doubled in size for the screens that are sharper than that; empty until the owner uploads one, and the mails show nothing until then
	'imageSlots'	=> [
		'/logo/mail' => [ 'label' => [ 'en_US' => 'Logo in mails', 'de_DE' => 'Logo in E-Mails' ], 'width' => 440, 'height' => 88 ],
		// The icon of each app of the surface, a square of 512 pixels the panel cuts the picture to; the app draws its own motif until the owner uploads one
		'/icon/crm' => [ 'label' => [ 'en_US' => 'App icon CRM', 'de_DE' => 'App-Icon CRM' ], 'width' => 512, 'height' => 512 ],
		'/icon/crm-tasks' => [ 'label' => [ 'en_US' => 'App icon tasks', 'de_DE' => 'App-Icon Aufgaben' ], 'width' => 512, 'height' => 512 ],
	],
];
