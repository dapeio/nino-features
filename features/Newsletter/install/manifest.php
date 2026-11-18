<?php return [
	// page-newsletter.tpl serves /.newsletter - the page a link opens, with its button, and the outcome and invalid
	// pages - and the answer of /.newsletter/action; page-newsletter-unsubscribe.tpl serves
	// /.newsletter/unsubscribe, the form that asks for the address. Both are made of components of this feature
	// and the frame. The module registers the routes itself (see Newsletter::init()), so unlike a "page" bundle
	// this needs no config.php route entry, just the template files copied into place
	'templates' 	=> [ 'mail-newsletter-confirm.tpl', 'mail-newsletter-unsubscribe.tpl', 'page-newsletter.tpl', 'page-newsletter-unsubscribe.tpl', 'mail-header.tpl', 'mail-footer.tpl' ],
	// The feature's section of the privacy policy, added to the Legal module's
	// type and never replacing a section - see \Nino\Elements::seed(). A Nino
	// without the module reads no such key
	'elements' 		=> [ 'privacy' => 'elements/privacy.php' ],
	'blacklist' => [
		// Filled by the class at request time - the confirmation link and the unsubscribe link of a mail (see
		// Newsletter::_sendConfirmMail() and _requestUnsubscribe()) - and answered by no text file, so the
		// Text panel's scan would report them as gaps forever
		'/feature/newsletter/confirm/url',
		'/feature/newsletter/unsubscribe/url',
	],
];
