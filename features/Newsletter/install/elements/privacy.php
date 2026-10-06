<?php
// features/Newsletter/install/elements/privacy.php - this feature's section of
// the privacy policy, in German and English, written for it. Added to the type
// 'privacy' of Nino's Legal module by \Nino\Features::activate() through the
// unit's 'elements' key, which only ever adds (see \Nino\Elements::seed()): a
// section an editor changed or deleted is not touched again, and a Nino
// without the module ignores the key. There is no 'model' on purpose - the
// type is the module's and never created from here.
//
// 'order' is the position among the module's sections, contact, forms and
// mail stand in 500-599. The text is in the form the sanitizer gives a field
// with 'blocks' and carries no '&', no entity and no '['. It names what the
// code does, with the default of the setting (seven days for an unconfirmed
// sign-up, /nino/newsletter/pending-days): whoever changes it changes the
// section with it.
return [
	'*' => [
		'newsletter' => [
			'order' => 530,
		],
	],
	'de_DE' => [
		'newsletter' => [
			'title' => 'Newsletter',
			'text' 	=> '<p>Für den Newsletter brauchen wir nur Deine E-Mail-Adresse. Nach der Anmeldung schicken wir Dir eine E-Mail mit einem Bestätigungslink; eingetragen bist Du erst, wenn Du ihn aufrufst (Double-Opt-in). Bestätigst Du nicht, verfällt die Anmeldung nach sieben Tagen und wird gelöscht, sobald sich das nächste Mal jemand anmeldet.</p><p>Als Nachweis Deiner Einwilligung speichern wir den Zeitpunkt Deiner Bestätigung und die IP-Adresse, von der aus Du Dich angemeldet hast. Rechtsgrundlage ist Deine Einwilligung (Art. 6 Abs. 1 lit. a DSGVO).</p><p>Abmelden kannst Du Dich jederzeit über den Link in jeder Ausgabe. Danach löschen wir Deine Adresse und behalten nur einen Hashwert davon, damit eine ältere Datensicherung sie nicht wieder in die Liste bringt.</p>',
		],
	],
	'en_US' => [
		'newsletter' => [
			'title' => 'Newsletter',
			'text' 	=> '<p>For the newsletter we only need your email address. After you sign up, we send you an email with a confirmation link; you are only subscribed once you open it (double opt-in). If you do not confirm, the sign-up expires after seven days and is deleted the next time someone signs up.</p><p>As proof of your consent we store the time of your confirmation and the IP address you signed up from. The legal basis is your consent (Art. 6(1)(a) GDPR).</p><p>You can unsubscribe at any time using the link in every issue. We then delete your address and keep only a hash of it, so that an older backup cannot put it back on the list.</p>',
		],
	],
];
