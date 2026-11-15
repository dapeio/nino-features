<?php
// features/ProtectedArea/install/elements/privacy.php - this feature's section
// of the privacy policy, in German and English, written for it. Added to the
// type 'privacy' of Nino's Legal module by \Nino\Features::activate() through
// the unit's 'elements' key, which only ever adds (see \Nino\Elements::seed()):
// a section an editor changed or deleted is not touched again, and a Nino
// without the module ignores the key. There is no 'model' on purpose - the
// type is the module's and never created from here.
//
// 'order' is the position among the module's sections, further features stand
// in 800-899. The text is in the form the sanitizer gives a field with
// 'blocks' and carries no '&', no entity and no '['. It names what the code
// does: a session flag and a counter of attempts per ip address and hour
// (ProtectedArea::WINDOW).
return [
	'*' => [
		'protected' => [
			'order' => 800,
		],
	],
	'de_DE' => [
		'protected' => [
			'title' => 'Geschützter Bereich',
			'text' 	=> '<p>Einige Seiten sind mit einem Passwort geschützt. Gibst Du es richtig ein, merkt sich die Website das in Deiner Sitzung; dafür setzt sie ein Sitzungs-Cookie, das beim Schließen des Browsers gelöscht wird. Um das Erraten des Passworts zu erschweren, zählt sie Fehlversuche je IP-Adresse eine Stunde lang.</p><p>Rechtsgrundlage ist unser berechtigtes Interesse, den Bereich nur Berechtigten zu öffnen und vor Angriffen zu schützen (Art. 6 Abs. 1 lit. f DSGVO), für das Cookie § 25 Abs. 2 Nr. 2 TDDDG.</p>',
		],
	],
	'en_US' => [
		'protected' => [
			'title' => 'Protected area',
			'text' 	=> '<p>Some pages are protected by a password. If you enter it correctly, the website remembers this in your session; for this it sets a session cookie that is deleted when you close your browser. To make guessing the password harder, it counts failed attempts per IP address for one hour.</p><p>The legal basis is our legitimate interest in opening the area only to authorised people and protecting it against attacks (Art. 6(1)(f) GDPR), and for the cookie Section 25(2) no. 2 TDDDG.</p>',
		],
	],
];
