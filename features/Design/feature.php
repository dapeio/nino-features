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
				'en_US' => 'The two frames (the header and the footer bring markup of their own), the root size - very small, smaller, default, larger or very large: 70, 85, 100, 115 or 130 % of the browser\'s own size below 768 px and 1.125 times that from it, the default being the delivered size - the width of the page (narrow keeps the content at 56 rem, normal at 120 rem, which is what Nino.css does, wide lets it run to the edge of the window - no maximum and no space at its sides, but for the padding a section keeps of its own) and the animation of the page - effect, strength and speed of the viewport animations, written as one rule on main. Empty means what Nino.css does by itself. The templates of the content, the buttons and the components, one stylesheet each, are chosen on their own tabs.',
				'de_DE' => 'Die beiden Rahmen (Header und Footer bringen Markup mit), die Grundgröße – sehr klein, kleiner, normal, größer oder sehr groß: 70, 85, 100, 115 oder 130 % der Browser-Größe unter 768 px und das 1,125-Fache davon ab dort, normal ist die gelieferte Größe –, die Breite der Seite (schmal hält den Inhalt bei 56 rem, normal bei 120 rem, wie Nino.css es tut, breit lässt ihn bis an den Rand des Fensters laufen – ohne Maximum und ohne Abstand zu den Seiten, nur das eigene Padding einer Section bleibt) und die Animation der Seite – Effekt, Stärke und Tempo der Viewport-Animationen, als eine Regel an main geschrieben. Leer heißt: was Nino.css von sich aus tut. Die Vorlagen für Inhalte, Buttons und Komponenten, je ein Stylesheet, werden auf ihren eigenen Tabs gewählt.',
			],
			'Fonts' => [
				'en_US' => 'A table of the five roles Nino.css reads - title, subtitle, text, button, code - with a family, a weight (light, normal, bold, mapped to the nearest weight the family has), an uppercase switch and a size. The size is typed in rem, within the bounds of the role - the title from 1 to 4, the subtitle from 0.8 to 2.5, the text from 0.8 to 1.5, the buttons from 0.6 to 1.5 and the code from 0.6 to 1.3 - and a value outside them is set to the nearer bound, and the number is rounded to three decimals (a typed 1.25 stays 1.25); the field steps by a tenth, which every bound is on, and shows and takes the decimal sign of the browser\'s language (a German one shows 1,6) while the setup keeps 1.6; an empty field is the size Nino.css gives the role, which the field shows. Only what is set is written; the families are the two the setup wizard delivers and three system stacks. The code has no case switch.',
				'de_DE' => 'Eine Tabelle der fünf Rollen, die Nino.css liest – Titel, Untertitel, Text, Buttons, Code – mit Schriftart, Gewicht (leicht, normal, fett, auf das nächstliegende Gewicht der Familie abgebildet), einem Uppercase-Schalter und einer Größe. Die Größe wird in rem getippt, innerhalb der Grenzen der Rolle – Titel 1 bis 4, Untertitel 0,8 bis 2,5, Text 0,8 bis 1,5, Buttons 0,6 bis 1,5 und Code 0,6 bis 1,3 –, ein Wert außerhalb wird auf die nähere Grenze gesetzt, und die Zahl wird auf drei Dezimalstellen gerundet (ein getipptes 1,25 bleibt 1,25); das Feld zählt in Zehnteln, auf denen jede Grenze liegt, und zeigt und nimmt das Dezimalzeichen der Browsersprache (deutsch: 1,6), das Setup behält 1.6; ein leeres Feld ist die Größe, die Nino.css der Rolle gibt, und das Feld zeigt sie. Geschrieben wird nur, was gesetzt ist; die Familien sind die beiden, die der Einrichtungsassistent mitbringt, und drei Systemschriften. Der Code hat keinen Schalter für die Schreibung.',
			],
			'Template of a tab' => [
				'en_US' => 'Content, Buttons and Components each begin with the select of their template; the header and the footer are chosen on Global. The description of the chosen template stands under the select, and choosing another one draws the rows of the tab again - with the focus still on the select, so the arrow keys go on through the list.',
				'de_DE' => 'Inhalte, Buttons und Komponenten beginnen je mit der Auswahl ihrer Vorlage; Header und Footer werden unter Global gewählt. Unter der Auswahl steht die Beschreibung der gewählten Vorlage, und eine andere Vorlage zeichnet die Zeilen des Tabs neu – der Fokus bleibt dabei auf der Auswahl, die Pfeiltasten laufen also weiter durch die Liste.',
			],
			'Content, Buttons, Components' => [
				'en_US' => 'One row for each named knob of the tab - hero title and text, quiet, loud, section space, card space, corners, border and shadow for the content; small, big, space and corners for the buttons; scale, space, corners, border, zebra and labels for the components - at one step less, as the template has it, or one step more. A template publishes a knob by declaring its three values; one it does not answer to is greyed out with a line saying so. When another template is chosen, only the knobs it answers to keep their position; the others go back to the middle, and choosing the earlier template again does not bring them back. A hero title or subtitle that carries the base class and a variant has the size of the base class from 768 px - that is a rule of Nino.css - so quiet and loud reach a hero below 768 px only.',
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
				'en_US' => 'One slot: the three files as they were before the last apply. Restoring swaps the slot with the present, so it can be undone by restoring again. The button stands in the action bar beside Reset, and only while there is such a version; its title gives the date of it.',
				'de_DE' => 'Ein Platz: die drei Dateien, wie sie vor dem letzten Anwenden waren. Wiederherstellen tauscht den Platz mit dem Gegenwärtigen, lässt sich also durch erneutes Wiederherstellen rückgängig machen. Der Knopf steht in der Aktionsleiste neben Zurücksetzen, und nur, solange es eine solche Version gibt; sein Titel nennt ihr Datum.',
			],
			'Preview' => [
				'en_US' => 'One specimen page against this project - its menu, logo and fonts - under the stylesheet the selection on screen compiles to. Three icon buttons over it, as in the Builder, show it at phone, tablet or desktop width; the switch "Preview follows" lets the page jump, and while it is off the frame never scrolls by itself (it is on at every visit and stored nowhere). The page has an anchor for each tab and scrolls there - below the fixed header - when a tab is opened and after a change; for a knob of Content it goes to where the knob shows, to the hero for the title and the text of the hero and to the cards for the other knobs. The panel scrolls the preview frame\'s own window (same origin: no message, no hash), smoothly, but at once after a rebuilt page and for a visitor who asks for less motion. A change of the animation plays it again on what stands in view.',
				'de_DE' => 'Eine Musterseite gegen dieses Projekt – Menü, Logo und Schriften – unter dem Stylesheet, das die Auswahl auf dem Bildschirm ergibt. Drei Icon-Knöpfe darüber zeigen sie, wie im Builder, in Handy-, Tablet- oder Desktopbreite; der Schalter „Vorschau springt mit“ lässt die Seite springen, und solange er aus ist, scrollt der Frame nie von sich aus (er ist bei jedem Besuch an und nirgends gespeichert). Die Seite hat einen Anker je Tab und scrollt beim Öffnen eines Tabs und nach einer Änderung dorthin – unterhalb des festen Headers; bei einem Regler von Inhalte geht sie dahin, wo er wirkt: zum Hero für Titel und Text des Heros, zu den Karten für die übrigen Regler. Das Panel scrollt das eigene Fenster des Vorschau-Frames (Same Origin: keine Nachricht, kein Hash), weich, aber sofort nach einer neu aufgebauten Seite und für eine Besucherin, die weniger Bewegung will. Eine Änderung der Animation spielt sie auf dem, was im Bild steht, noch einmal ab.',
			],
			'The delivered look' => [
				'en_US' => 'The theme.css the setup wizard delivers is what this compiler writes at its defaults - twelve sections, every template the first one, every knob in the middle - from the end of its header on, byte for byte. The header is the kernel\'s own, and the kernel regenerates the file with each release, so that the look a project starts from and the look Design compiles have one source. The border of each ground is assigned in base.css (--color-border-<ground>), and so in the delivered file, from the border solved for that surface: a line, a badge or a marker on an alt, tint, dark, black or brand ground takes it. The faces are named Source Sans 3 and League Spartan in both files. The delivered file has no stamp of the compiler\'s, so the panel calls it "Not written by Design" and the first apply asks before it writes over it.',
				'de_DE' => 'Die theme.css, die der Einrichtungsassistent mitbringt, ist das, was dieser Compiler an den Vorgaben schreibt – zwölf Abschnitte, jede Vorlage die erste, jeder Regler in der Mitte –, vom Ende ihres Kopfes an Byte für Byte. Der Kopf ist der des Kernels, und der Kernel erzeugt die Datei mit jeder Version neu, damit das Aussehen, mit dem ein Projekt beginnt, und das, was Design kompiliert, eine Quelle haben. Der Rahmen jeder Fläche wird in der base.css (--color-border-<fläche>) und damit in der ausgelieferten Datei aus dem Rahmen zugewiesen, der für diese Fläche gelöst ist: Eine Linie, ein Badge oder eine Markierung auf einer Alt-, Tint-, Dunkel-, Schwarz- oder Markenfläche übernimmt ihn. Die Schriften heißen in beiden Dateien Source Sans 3 und League Spartan. Die ausgelieferte Datei hat keinen Stempel des Compilers, deshalb nennt das Panel sie „Nicht von Design geschrieben“, und das erste Anwenden fragt nach, bevor es sie überschreibt.',
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
		the first that names only a kernel which does both. And the highest came
		with 1.7, which renamed the classes of the hero to nino-hero and kept
		no alias, gave the font roles, the grounds and the counter ground
		their custom properties - the content templates, the phone rule of
		header v5, the font roles the compiler writes and the specimen are
		written for them - and called the card one - nino-card and its tokens, and kept
		no alias for the old class - and whose delivered theme.css is the body
		this compiler writes of a library with those names: the content sets,
		base.css and the specimen are written for them, so on a 1.6 they style
		nothing and the compile is not the delivered file. */
	'nino'				=> '^1.7',
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
