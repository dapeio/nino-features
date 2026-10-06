<?php
// features/Embed/install/elements/privacy.php - this feature's sections of the
// privacy policy, in German and English, written for it. Added to the type
// 'privacy' of Nino's Legal module by \Nino\Features::activate() through the
// unit's 'elements' key, which only ever adds (see \Nino\Elements::seed()): a
// section an editor changed or deleted is not touched again, and a Nino
// without the module ignores the key. There is no 'model' on purpose - the
// type is the module's and never created from here.
//
// 'order' is the position among the module's sections, embedded content stands
// in 700-799. The text is in the form the sanitizer gives a field with
// 'blocks' and carries no '&', no entity and no '['. One section for what
// every provider does, one each for the two the code knows (Embed::PROVIDERS):
// youtube-nocookie.com and Vimeo with dnt=1. A project that uses only one of
// them hides the other section in the Elements panel. The links to the
// providers' policies are the only addresses outside the site.
return [
	'*' => [
		'embed' => [
			'order' => 700,
		],
		'embed-youtube' => [
			'order' => 710,
		],
		'embed-vimeo' => [
			'order' => 720,
		],
	],
	'de_DE' => [
		'embed' => [
			'title' => 'Eingebettete Videos und Karten',
			'text' 	=> '<p>Manche Seiten enthalten Videos oder Karten anderer Anbieter. Davon lädt die Website nichts, bevor Du zustimmst: Erst wenn Du einen solchen Inhalt anklickst oder die Kategorie „Externe Medien“ erlaubst, verbindet sich Dein Browser mit dem Anbieter. Der Anbieter erhält dabei Deine IP-Adresse und technische Angaben zu Gerät und Browser und kann Cookies setzen oder Daten auf Deinem Gerät speichern.</p><p>Rechtsgrundlage ist Deine Einwilligung (Art. 6 Abs. 1 lit. a DSGVO, § 25 Abs. 1 TDDDG). Du kannst sie jederzeit mit Wirkung für die Zukunft widerrufen.</p>',
		],
		'embed-youtube' => [
			'title' => 'YouTube',
			'text' 	=> '<p>Videos von YouTube binden wir im erweiterten Datenschutzmodus ein (youtube-nocookie.com). Anbieter ist die Google Ireland Limited in Dublin, Irland. Sobald ein Video geladen ist, erhält Google die unter „<a href="#privacy-embed">Eingebettete Videos und Karten</a>“ genannten Angaben; dabei können Daten auch an die Google LLC in den USA gelangen. Wie Google mit diesen Daten umgeht und worauf es Übermittlungen in die USA stützt, steht in der Datenschutzerklärung von Google: <a href="https://policies.google.com/privacy">policies.google.com/privacy</a>.</p>',
		],
		'embed-vimeo' => [
			'title' => 'Vimeo',
			'text' 	=> '<p>Videos von Vimeo laden wir mit der Einstellung „Do Not Track“ (dnt=1), die laut Vimeo das Erfassen von Sitzungsdaten einschließlich Cookies unterbindet. Anbieter ist die Vimeo.com, Inc. in New York, USA. Sobald ein Video geladen ist, erhält Vimeo die unter „<a href="#privacy-embed">Eingebettete Videos und Karten</a>“ genannten Angaben. Worauf Vimeo die Übermittlung in die USA stützt, steht in seiner Datenschutzerklärung: <a href="https://vimeo.com/privacy">vimeo.com/privacy</a>.</p>',
		],
	],
	'en_US' => [
		'embed' => [
			'title' => 'Embedded videos and maps',
			'text' 	=> '<p>Some pages contain videos or maps from other providers. The website loads none of this before you agree: only when you click on such content or allow the “External media” category does your browser connect to the provider. The provider then receives your IP address and technical details of your device and browser, and may set cookies or store data on your device.</p><p>The legal basis is your consent (Art. 6(1)(a) GDPR, Section 25(1) TDDDG). You can withdraw it at any time with effect for the future.</p>',
		],
		'embed-youtube' => [
			'title' => 'YouTube',
			'text' 	=> '<p>We embed YouTube videos in privacy-enhanced mode (youtube-nocookie.com). The provider is Google Ireland Limited in Dublin, Ireland. As soon as a video is loaded, Google receives the details named under “<a href="#privacy-embed">Embedded videos and maps</a>”; data may also reach Google LLC in the USA. How Google handles these data and on what basis it transfers them to the USA is described in Google’s privacy policy: <a href="https://policies.google.com/privacy">policies.google.com/privacy</a>.</p>',
		],
		'embed-vimeo' => [
			'title' => 'Vimeo',
			'text' 	=> '<p>We load Vimeo videos with the “Do Not Track” setting (dnt=1), which according to Vimeo prevents session data, including cookies, from being collected. The provider is Vimeo.com, Inc. in New York, USA. As soon as a video is loaded, Vimeo receives the details named under “<a href="#privacy-embed">Embedded videos and maps</a>”. The basis on which Vimeo transfers data to the USA is described in its privacy policy: <a href="https://vimeo.com/privacy">vimeo.com/privacy</a>.</p>',
		],
	],
];
