<?php return [
	'label' 			=> 'Forms',
	'moduleClass' => '\\Nino\\Modules\\Forms',
	'requiresModules' => [ ],
	// The two mail templates this feature renders by default, plus the frame
	// they include. Copied into /templates/ add-only, like every unit: a
	// project that already has a mail-header.tpl keeps its own, and the two
	// mail-form-*.tpl carry names of their own so they can never displace
	// the mail-owner.tpl/mail-user.tpl a project wrote against the kernel's
	// contact form - a form may point at those instead, see README.md
	'templates' 	=> [ 'mail-form-owner.tpl', 'mail-form-user.tpl', 'mail-header.tpl', 'mail-footer.tpl' ],
	'blacklist' => [
		'/mail/style/color/primary',
		'/mail/style/color/text',
		'/mail/style/color/background',
		'/mail/style/color/border',
		'/mail/style/color/section/alt/bg',
		'/mail/style/typography/line-height',
		'/mail/style/typography/font-small',
		'/mail/style/typography/font-big',
		'/mail/style/spacing/1',
		'/mail/style/spacing/2',
		'/mail/style/spacing/3',
	],
];
