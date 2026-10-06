<?php return [
	'label' 			=> 'Newsletter',
	'moduleClass' => '\\Nino\\Modules\\Newsletter',
	'requiresModules' => [ ],
	// page-newsletter.tpl serves /.newsletter (confirm/unsubscribe/invalid) and
	// page-newsletter-unsubscribe.tpl serves /.newsletter/unsubscribe, the form
	// that asks for the address - the module registers both routes itself (see
	// Newsletter::init()), so unlike a "page" bundle this needs no config.php
	// route entry, just the template files copied into place
	'templates' 	=> [ 'mail-newsletter-confirm.tpl', 'mail-newsletter-unsubscribe.tpl', 'page-newsletter.tpl', 'page-newsletter-unsubscribe.tpl', 'mail-header.tpl', 'mail-footer.tpl' ],
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
		// Filled by the class at request time - the confirmation link, the
		// unsubscribe link, the page's title and text for the outcome at hand
		// and the unsubscribe form's error line (see Newsletter::
		// callbackAction(), _sendConfirmMail(), _requestUnsubscribe() and
		// callbackUnsubscribeRequest()) - and answered by no text file, so the
		// Text panel's scan would report them as gaps forever
		'/newsletter/confirm/url',
		'/newsletter/unsubscribe/url',
		'/newsletter/unsubscribe/error',
		'/newsletter/page/title',
		'/newsletter/page/text',
	],
];
