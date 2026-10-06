<?php return [
	// page-newsletter.tpl serves /.newsletter (confirm/unsubscribe/invalid) and
	// page-newsletter-unsubscribe.tpl serves /.newsletter/unsubscribe, the form
	// that asks for the address - the module registers both routes itself (see
	// Newsletter::init()), so unlike a "page" bundle this needs no config.php
	// route entry, just the template files copied into place
	'templates' 	=> [ 'mail-newsletter-confirm.tpl', 'mail-newsletter-unsubscribe.tpl', 'page-newsletter.tpl', 'page-newsletter-unsubscribe.tpl', 'mail-header.tpl', 'mail-footer.tpl' ],
	// The feature's section of the privacy policy, added to the Legal module's
	// type and never replacing a section - see \Nino\Elements::seed(). A Nino
	// without the module reads no such key
	'elements' 		=> [ 'privacy' => 'elements/privacy.php' ],
	'blacklist' => [
		// Filled by the class at request time - the confirmation link, the
		// unsubscribe link, the page's title and text for the outcome at hand
		// and the unsubscribe form's error line (see Newsletter::
		// callbackAction(), _sendConfirmMail(), _requestUnsubscribe() and
		// callbackUnsubscribeRequest()) - and answered by no text file, so the
		// Text panel's scan would report them as gaps forever
		'/feature/newsletter/confirm/url',
		'/feature/newsletter/unsubscribe/url',
		'/feature/newsletter/unsubscribe/error',
		'/feature/newsletter/page/title',
		'/feature/newsletter/page/text',
	],
];
