<?php
// features/Forms/install/elements/privacy.php - this feature's section of the
// privacy policy, in German and English, written for it. Added to the type
// 'privacy' of Nino's Legal module by \Nino\Features::activate() through the
// unit's 'elements' key, which only ever adds (see \Nino\Elements::seed()): a
// section an editor changed or deleted is not touched again, and a Nino
// without the module ignores the key. There is no 'model' on purpose - the
// type is the module's and never created from here.
//
// 'order' is the position among the module's sections, contact, forms and
// mail stand in 500-599. The text is in the form the sanitizer gives a field
// with 'blocks' and carries no '&', no entity and no '['. It names what the
// code does, with the defaults of the settings (three months for what the
// kernel keeps of a submission, a setting; the hour of the spam counter is
// Forms::WINDOW): whoever changes one changes the section with it. The link
// goes to the module's own section on the contact form.
return [
	'*' => [
		'forms' => [
			'order' => 520,
		],
	],
	'de_DE' => [
		'forms' => [
			'title' => 'Weitere Formulare',
			'text' 	=> '<p>Neben dem Kontaktformular gibt es auf dieser Website weitere Formulare. Für sie gilt, was unter „<a href="#privacy-contact-form">Kontaktformular</a>“ steht: Wir speichern Deine Eingaben mit Datum, Uhrzeit und IP-Adresse, um Dein Anliegen zu bearbeiten, löschen sie nach drei Monaten automatisch und stützen uns auf dieselben Rechtsgrundlagen.</p><p>Zum Schutz vor Spam zählt die Website eine Stunde lang die Absendungen je Absender. Dafür verwendet sie einen Hashwert Deiner IP-Adresse, nicht die Adresse selbst.</p>',
		],
	],
	'en_US' => [
		'forms' => [
			'title' => 'Other forms',
			'text' 	=> '<p>Besides the contact form, this website has further forms. What is said under “<a href="#privacy-contact-form">Contact form</a>” applies to them: we store your entries with the date, the time and your IP address to deal with your request, delete them automatically after three months and rely on the same legal bases.</p><p>To protect against spam, the website counts submissions per sender for one hour. It uses a hash of your IP address for this, not the address itself.</p>',
		],
	],
];
