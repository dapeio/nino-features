<?php
// features/Mailer/install/elements/privacy.php - this feature's section of the
// privacy policy, in German and English, written for it. Added to the type
// 'privacy' of Nino's Legal module by \Nino\Features::activate() through the
// unit's 'elements' key, which only ever adds (see \Nino\Elements::seed()): a
// section an editor changed or deleted is not touched again, and a Nino
// without the module ignores the key. There is no 'model' on purpose - the
// type is the module's and never created from here.
//
// 'order' is the position among the module's sections, contact, forms and
// mail stand in 500-599. The text is in the form the sanitizer gives a field
// with 'blocks' and carries no '&', no entity and no '['. The provider is a
// category here, not a name: the project says who it is, and the section
// has to be completed with it.
return [
	'*' => [
		'mailer' => [
			'order' => 540,
		],
	],
	'de_DE' => [
		'mailer' => [
			'title' => 'E-Mail-Versand',
			'text' 	=> '<p>E-Mails dieser Website, etwa Bestätigungen, verschicken wir über den Mailserver eines E-Mail-Dienstleisters. Er erhält dafür die Empfängeradresse, den Inhalt der Nachricht und technische Angaben zum Versand. Mit ihm haben wir vereinbart, dass er diese Daten nur in unserem Auftrag und nach unseren Weisungen verarbeitet (Art. 28 DSGVO). Die Rechtsgrundlage ist dieselbe wie für den Anlass der E-Mail, zum Beispiel Deine Anfrage oder Deine Anmeldung zum Newsletter.</p>',
		],
	],
	'en_US' => [
		'mailer' => [
			'title' => 'Sending emails',
			'text' 	=> '<p>Emails from this website, such as confirmations, are sent through the mail server of an email service provider. For this, the provider receives the recipient address, the content of the message and technical details of the delivery. We have agreed with the provider that it processes these data only on our behalf and on our instructions (Art. 28 GDPR). The legal basis is the same as for the reason for the email, for example your request or your newsletter sign-up.</p>',
		],
	],
];
