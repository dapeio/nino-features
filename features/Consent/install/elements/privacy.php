<?php
// features/Consent/install/elements/privacy.php - this feature's section of the
// privacy policy, in German and English, written for it. Added to the type
// 'privacy' of Nino's Legal module by \Nino\Features::activate() through the
// unit's 'elements' key, which only ever adds (see \Nino\Elements::seed()): a
// section an editor changed or deleted is not touched again, and a Nino
// without the module ignores the key. There is no 'model' on purpose - the
// type is the module's and never created from here.
//
// 'order' is the position among the module's sections, cookies and consent
// stand in 400-499. The text is in the form the sanitizer gives a field with
// 'blocks' and carries no '&', no entity and no '['. It names what the code
// does, with the defaults of the settings (the cookie 'nino_consent', 180
// days): whoever changes a setting changes the section with it. The button at
// the end is added by Consent::callbackLegalSection(), not written here.
return [
	'*' => [
		'consent' => [
			'order' => 420,
		],
	],
	'de_DE' => [
		'consent' => [
			'title' => 'Einwilligung und Cookie-Einstellungen',
			'text' 	=> '<p>Beim ersten Besuch fragt Dich ein Hinweis, welchen Diensten Du zustimmst. Deine Auswahl speichert die Website 180 Tage lang im Cookie „nino_consent“; es enthält nichts außer dieser Auswahl. So musst Du nicht auf jeder Seite neu entscheiden, und wir können nachweisen, dass Du eingewilligt hast.</p><p>Rechtsgrundlage für das Speichern Deiner Auswahl ist § 25 Abs. 2 Nr. 2 TDDDG. Zum Nachweis Deiner Einwilligung verpflichtet uns Art. 7 Abs. 1 DSGVO (Art. 6 Abs. 1 lit. c DSGVO).</p><p>Deine Auswahl kannst Du jederzeit ändern oder widerrufen, über die Schaltfläche „Cookie-Einstellungen“ am Ende dieses Abschnitts.</p>',
		],
	],
	'en_US' => [
		'consent' => [
			'title' => 'Consent and cookie settings',
			'text' 	=> '<p>On your first visit, a notice asks which services you agree to. The website stores your choice for 180 days in the cookie “nino_consent”; it contains nothing but this choice. This way you do not have to decide again on every page, and we can demonstrate that you have given consent.</p><p>The legal basis for storing your choice is Section 25(2) no. 2 TDDDG. Art. 7(1) GDPR obliges us to be able to demonstrate your consent (Art. 6(1)(c) GDPR).</p><p>You can change or withdraw your choice at any time using the “Cookie settings” button at the end of this section.</p>',
		],
	],
];
