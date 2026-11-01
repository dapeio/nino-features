<?php return [
	// page-newsletter.tpl serves /.newsletter (the page a link opens, with its
	// button, and the outcome and invalid pages) and the answer of
	// /.newsletter/action; newsletter-action.tpl is the form with the button,
	// which the page draws where a link has just been opened; and
	// page-newsletter-unsubscribe.tpl serves /.newsletter/unsubscribe, the form
	// that asks for the address - the module registers the routes itself (see
	// Newsletter::init()), so unlike a "page" bundle this needs no config.php
	// route entry, just the template files copied into place
	'templates' 	=> [ 'mail-newsletter-confirm.tpl', 'mail-newsletter-unsubscribe.tpl', 'page-newsletter.tpl', 'newsletter-action.tpl', 'page-newsletter-unsubscribe.tpl', 'mail-header.tpl', 'mail-footer.tpl' ],
	// The feature's section of the privacy policy, added to the Legal module's
	// type and never replacing a section - see \Nino\Elements::seed(). A Nino
	// without the module reads no such key
	'elements' 		=> [ 'privacy' => 'elements/privacy.php' ],
	'blacklist' => [
		// Filled by the class at request time - the confirmation link, the
		// unsubscribe link, the page's title, text and form for the outcome at
		// hand, the form's field, token and button, and the unsubscribe form's
		// error line (see Newsletter::callbackLink(), callbackPerform(),
		// _sendConfirmMail(), _requestUnsubscribe() and
		// callbackUnsubscribeRequest()) - and answered by no text file, so the
		// Text panel's scan would report them as gaps forever
		'/feature/newsletter/confirm/url',
		'/feature/newsletter/unsubscribe/url',
		'/feature/newsletter/unsubscribe/error',
		'/feature/newsletter/page/title',
		'/feature/newsletter/page/text',
		'/feature/newsletter/page/form',
		'/feature/newsletter/action/field',
		'/feature/newsletter/action/token',
		'/feature/newsletter/action/button',
	],
];
