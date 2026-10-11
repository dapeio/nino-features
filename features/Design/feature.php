<?php
// features/Design/feature.php - what the Features panel reads. The class is
// not declared here: features/Design/ can only ever serve \Nino\Modules\Design.
return [
	'key'					=> 'design',
	'name'				=> [ 'en_US' => 'Design', 'de_DE' => 'Design' ],
	'description'	=> [
		'en_US' => 'The look of the whole site over one model: five templates (header, footer, content, buttons, components), a root size, the width and the animation of the page, the five font roles, named knobs for each template and the palette - six tabs, one preview page, compiled into one stylesheet.',
		'de_DE' => 'Das Aussehen der ganzen Website über ein Modell: fünf Vorlagen (Header, Footer, Inhalte, Buttons, Komponenten), eine Grundgröße, Breite und Animation der Seite, die fünf Schriftrollen, benannte Regler je Vorlage und die Palette – sechs Tabs, eine Vorschauseite, in ein Stylesheet kompiliert.',
	],
	'manual'			=> [
		'shortcodes' => [],
		'markup' => [],
		'routes' => [],
		'panel' => [
			'Design' => [
				'en_US' => 'Six tabs over one setup - Global, Fonts, Content, Buttons, Components, Colours - and a preview beside them that scrolls to the open tab. An apply that asks first writes assets/theme.css and the two frame templates, and keeps the previous version to restore.',
				'de_DE' => 'Sechs Tabs über einem Setup – Global, Schriften, Inhalte, Buttons, Komponenten, Farben – und eine Vorschau daneben, die zum offenen Tab scrollt. Ein Anwenden, das vorher nachfragt, schreibt assets/theme.css und die beiden Rahmen-Templates und hebt die Vorversion zum Wiederherstellen auf.',
			],
			'Global' => [
				'en_US' => 'The five templates (the header and footer bring markup, content, buttons and components are one stylesheet each), the root size s, m or l, the width of the page (narrow 96 rem, normal 120 rem, wide 144 rem) and the animation of the page - effect, strength and speed of the viewport animations, written as one rule on main. Empty means what Nino.css does by itself.',
				'de_DE' => 'Die fünf Vorlagen (Header und Footer bringen Markup mit, Inhalte, Buttons und Komponenten sind je ein Stylesheet), die Grundgröße s, m oder l, die Breite der Seite (schmal 96 rem, normal 120 rem, breit 144 rem) und die Animation der Seite – Effekt, Stärke und Tempo der Viewport-Animationen, als eine Regel an main geschrieben. Leer heißt: was Nino.css von sich aus tut.',
			],
			'Fonts' => [
				'en_US' => 'A table of the five roles Nino.css reads - title, subtitle, text, button, code - with a family, a weight (light, normal, bold, mapped to the nearest weight the family has), an uppercase switch and a size in rem. Only what is set is written; the families are the two the setup wizard delivers and three system stacks. The code has no case switch.',
				'de_DE' => 'Eine Tabelle der fünf Rollen, die Nino.css liest – Titel, Untertitel, Text, Buttons, Code – mit Schriftart, Gewicht (leicht, normal, fett, auf das nächstliegende Gewicht der Familie abgebildet), einem Uppercase-Schalter und einer Größe in rem. Geschrieben wird nur, was gesetzt ist; die Familien sind die beiden, die der Einrichtungsassistent mitbringt, und drei Systemschriften. Der Code hat keinen Schalter für die Schreibung.',
			],
			'Content, Buttons, Components' => [
				'en_US' => 'One row for each named knob of the tab - hero title and text, quiet, loud, section space, article space, corners, border and shadow for the content; small, big, space and corners for the buttons; scale, space, corners, border, zebra and labels for the components - at one step less, as the template has it, or one step more. A template publishes a knob by declaring its three values; one it does not answer to is greyed out with a line saying so. When another template is chosen, only the knobs it answers to keep their position; the others go back to the middle, and choosing the earlier template again does not bring them back. A hero title or subtitle that carries the base class and a variant has the size of the base class from 768 px - that is a rule of Nino.css - so quiet and loud reach a hero below 768 px only.',
				'de_DE' => 'Eine Zeile je benanntem Regler des Tabs – Hero-Titel und -Text, leise, laut, Abstand der Sections, Abstand der Karten, Ecken, Linie und Schatten für die Inhalte; klein, groß, Abstand und Ecken für die Buttons; Schriftgröße, Abstände, Ecken, Linien, Streifen und Beschriftung für die Komponenten – auf eine Stufe weniger, wie die Vorlage es hat, oder eine mehr. Eine Vorlage veröffentlicht einen Regler, indem sie seine drei Werte deklariert; einer, auf den sie nicht hört, steht ausgegraut da, mit einer Zeile, die das sagt. Wird eine andere Vorlage gewählt, behalten nur die Regler ihre Stellung, auf die sie hört; die übrigen stehen wieder in der Mitte, und mit der früheren Vorlage kehren ihre Stellungen nicht zurück. Ein Hero-Titel oder -Untertitel mit der Basisklasse und einer Variante hat ab 768 px die Größe der Basisklasse – das ist eine Regel von Nino.css –, deshalb erreichen „leise“ und „laut“ einen Hero nur darunter.',
			],
			'Colours' => [
				'en_US' => 'A brand colour, a second colour (or Harmony, which derives it), temperature, saturation, contrast, depth and the mode: automatic follows the visitor, light only and dark only write the palette of one mode without a media query - which leaves the Modeswitch feature without effect. The palette has eleven surfaces of eight values each; the unsafe second colour is no surface of its own any more, since nothing in Nino.css reads it - its -safe variant is the ink. Hover and active values are gone for the same reason, and the shadow is one alpha for each mode, whatever the depth.',
				'de_DE' => 'Eine Markenfarbe, eine zweite Farbe (oder Harmonie, die sie ableitet), Temperatur, Sättigung, Kontrast, Tiefe und der Modus: automatisch folgt der Besucherin, nur hell und nur dunkel schreiben die Palette eines Modus ohne Media Query – das Feature Modeswitch bleibt dann ohne Wirkung. Die Palette hat elf Flächen mit je acht Werten; die ungesicherte zweite Farbe ist keine eigene Fläche mehr, weil Nino.css sie nirgends liest – ihre -safe-Variante ist die Schrift. Hover- und Active-Werte sind aus demselben Grund weg, und der Schatten ist ein Alphawert je Modus, ganz gleich welche Tiefe.',
			],
			'Save draft / Apply to website / Reset' => [
				'en_US' => 'Save draft writes data/design.php and nothing else. Apply asks first - it lists which files are replaced and which shortcodes of a frame would be lost - then writes the three files. Reset goes back to what was saved. A data/design.php of another format is not read: the defaults stand in until the next save, and the panel says so.',
				'de_DE' => 'Entwurf speichern schreibt nur data/design.php. Anwenden fragt vorher nach – es nennt, welche Dateien ersetzt werden und welche Shortcodes eines Rahmens verloren gingen – und schreibt dann die drei Dateien. Zurücksetzen geht zurück auf das Gespeicherte. Eine data/design.php in einem anderen Format wird nicht gelesen: Bis zum nächsten Speichern stehen die Vorgaben an ihrer Stelle, und das Panel sagt es.',
			],
			'Restore previous version' => [
				'en_US' => 'One slot: the three files as they were before the last apply. Restoring swaps the slot with the present, so it can be undone by restoring again.',
				'de_DE' => 'Ein Platz: die drei Dateien, wie sie vor dem letzten Anwenden waren. Wiederherstellen tauscht den Platz mit dem Gegenwärtigen, lässt sich also durch erneutes Wiederherstellen rückgängig machen.',
			],
			'Preview' => [
				'en_US' => 'One specimen page against this project - its menu, logo and fonts - under the stylesheet the selection on screen compiles to, at phone, tablet or desktop width. It has an anchor for each tab, and the page scrolls there - below the fixed header - when a tab is opened and after every change. The panel scrolls the preview frame\'s own window to the anchor of the open tab (same origin: no message, no hash), smoothly, but at once after a rebuilt page and for a visitor who asks for less motion. A change of the animation plays it again on what stands in view.',
				'de_DE' => 'Eine Musterseite gegen dieses Projekt – Menü, Logo und Schriften – unter dem Stylesheet, das die Auswahl auf dem Bildschirm ergibt, in Handy-, Tablet- oder Desktopbreite. Sie hat einen Anker je Tab, und die Seite scrollt dorthin – unterhalb des festen Headers –, wenn ein Tab geöffnet wird und nach jeder Änderung. Das Panel scrollt das eigene Fenster des Vorschau-Frames zum Anker des offenen Tabs (Same Origin: keine Nachricht, kein Hash), weich, aber sofort nach einer neu aufgebauten Seite und für eine Besucherin, die weniger Bewegung will. Eine Änderung der Animation spielt sie auf dem, was im Bild steht, noch einmal ab.',
			],
			'The delivered look' => [
				'en_US' => 'Compiled at its defaults, the stylesheet looks like the theme.css the setup wizard delivers: the same roles on the same tokens, the same values wherever a token is kept, and the same faces - named Source Sans 3 and League Spartan here where the delivered file says Text and Title, with a fallback that begins at -apple-system instead of system-ui. The border colour of each ground is not assigned yet, so a line on an alt, tint, dark, black or brand ground has the border colour of the page, as Nino.css gives it and as it is in the delivered file. That file is not made by this compiler yet: the next kernel release regenerates it from the compiler at the defaults, so that both have one source, and an update of this feature, released at the same time, assigns the borders of the grounds in base.css (--color-border-<ground>), for both.',
				'de_DE' => 'An den Vorgaben kompiliert, sieht das Stylesheet aus wie die theme.css, die der Einrichtungsassistent mitbringt: dieselben Rollen auf denselben Tokens, dieselben Werte, wo ein Token bleibt, und dieselben Schriften – hier als Source Sans 3 und League Spartan benannt, wo die ausgelieferte Datei Text und Title sagt, mit einem Fallback, der bei -apple-system statt bei system-ui beginnt. Die Rahmenfarbe jeder Fläche ist noch nicht zugewiesen, eine Linie auf einer Alt-, Tint-, Dunkel-, Schwarz- oder Markenfläche hat also die Rahmenfarbe der Seite, wie Nino.css sie vorgibt und wie in der ausgelieferten Datei. Diese Datei stammt noch nicht aus diesem Compiler: Die nächste Kernel-Version erzeugt sie aus dem Compiler an den Vorgaben, damit beide eine Quelle haben, und eine Aktualisierung dieses Features, die zusammen mit dieser Version erscheint, weist die Rahmen der Flächen in der base.css zu (--color-border-<fläche>), für beide.',
			],
		],
		'callbacks' => [],
		'install' => [],
	],
	'category'		=> 'ui',
	'version'			=> '2.0.0',
	/*	Two floors, and the higher one wins. 1.2 is where the wizard stopped
		asking about the look and the base unit started delivering
		assets/theme.css as one file; everything here writes over that file, so
		a kernel that still spread the look over style.design.css,
		style.theme.*.css and two frame stylesheets would be compiled for a
		bundle it does not have. And the sectioned 'manual' map below is only
		read by a kernel newer than the v1.2.0-beta tag - on that one
		Features::manifest() refuses this file outright - so a constraint
		admitting 1.2 offered a feature that could not be installed. ^1.3 is
		the first that names only a kernel which does both. A higher one came
		with 1.6, which renamed the classes of the hero to nino-hero and kept
		no alias, and gave the font roles, the grounds and the counter ground
		their custom properties: the content templates, the phone rule of
		header v5, the font roles the compiler writes and the specimen are
		written for them, so on a 1.5 they style nothing. */
	'nino'				=> '^1.6',
	'requires'		=> [],
	// The whole setup: which template per part, the knob positions of the three
	// template tabs, the size, width and animation, the font roles, the palette's
	// colours and knobs and mode, and the fingerprint of what was last
	// compiled. Small, and the one thing that cannot be derived again if it is
	// lost. And the one version before the last apply - the three files it
	// replaced and the setup that went with them - which a backup carries too
	// and a restore of one may simply replace
	'data'				=> [ '/data/design.php', '/data/design-previous.php' ],
	'settings'		=> [],
];
