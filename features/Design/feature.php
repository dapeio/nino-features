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
				'en_US' => 'The two frames (the header and the footer bring markup of their own) and the animation of the page - effect, strength and speed of the viewport animations, written as one rule on main. Empty means what Nino.css does by itself. The root size and the width of the page are two strips of five positions, -2 to +2, the middle the delivered one (see the next entry). The templates of the content, the buttons and the components, one stylesheet each, are chosen on their own tabs.',
				'de_DE' => 'Die beiden Rahmen (Header und Footer bringen Markup mit) und die Animation der Seite – Effekt, Stärke und Tempo der Viewport-Animationen, als eine Regel an main geschrieben. Leer heißt: was Nino.css von sich aus tut. Grundgröße und Breite der Seite sind zwei Leisten mit fünf Stellungen, -2 bis +2, die Mitte die gelieferte (siehe den nächsten Eintrag). Die Vorlagen für Inhalte, Buttons und Komponenten, je ein Stylesheet, werden auf ihren eigenen Tabs gewählt.',
			],
			'Root size and width' => [
				'en_US' => 'The root size is 70, 85, 100, 115 or 130 % of the browser\'s own size below 768 px and 1.125 times that from it, never a fixed pixel value; the middle is the delivered size. The width is the widest the content may be and the space at its sides: -2 a reading column of 48 rem, -1 a contained page of 64 rem, 0 the 120 rem Nino.css has, +1 no maximum, with the kernel\'s small space at the sides at every width, and +2 no maximum and, from 768 px, no space at the sides but the padding a section keeps of its own (a phone keeps its space). In a preview of 1280 px every one of the five is another row: 792, 1080, 1136, 1172 and 1208 px of text.',
				'de_DE' => 'Die Grundgröße ist 70, 85, 100, 115 oder 130 % der Browser-Größe unter 768 px und das 1,125-Fache davon ab dort, nie als feste Pixelzahl; die Mitte ist die gelieferte Größe. Die Breite ist das Maximum der Inhaltsbreite und der Abstand zu den Seiten: -2 eine Lesespalte von 48 rem, -1 eine eingefasste Seite von 64 rem, 0 die 120 rem, die Nino.css hat, +1 ohne Maximum und mit dem kleinen Abstand des Kernels zu den Seiten bei jeder Breite, +2 ohne Maximum und ab 768 px ohne Abstand zu den Seiten außer dem Padding, das eine Section selbst behält (ein Handy behält seinen Abstand). In einer Vorschau von 1280 px ist jede der fünf eine andere Zeile: 792, 1080, 1136, 1172 und 1208 px Text.',
			],
			'Fonts' => [
				'en_US' => 'A table of the five roles Nino.css reads - title, subtitle, text, button, code - with a family, a weight (light, normal, bold, mapped to the nearest weight the family has), an uppercase switch and a size. The size is typed in rem, within the bounds of the role - the title from 1 to 4, the subtitle from 0.8 to 2.5, the text from 0.8 to 1.5, the buttons from 0.6 to 1.5 and the code from 0.6 to 1.3 - and a value outside them is set to the nearer bound. Only what is set is written; the families are the two the setup wizard delivers and three system stacks. The code has no case switch.',
				'de_DE' => 'Eine Tabelle der fünf Rollen, die Nino.css liest – Titel, Untertitel, Text, Buttons, Code – mit Schriftart, Gewicht (leicht, normal, fett, auf das nächstliegende Gewicht der Familie abgebildet), einem Uppercase-Schalter und einer Größe. Die Größe wird in rem getippt, innerhalb der Grenzen der Rolle – Titel 1 bis 4, Untertitel 0,8 bis 2,5, Text 0,8 bis 1,5, Buttons 0,6 bis 1,5 und Code 0,6 bis 1,3 –, ein Wert außerhalb wird auf die nähere Grenze gesetzt. Geschrieben wird nur, was gesetzt ist; die Familien sind die beiden, die der Einrichtungsassistent mitbringt, und drei Systemschriften. Der Code hat keinen Schalter für die Schreibung.',
			],
			'Size of a role' => [
				'en_US' => 'The number is rounded to three decimals (a typed 1.25 stays 1.25). The field steps by a tenth, which every bound is on, and shows and takes the decimal sign of the browser\'s language (a German one shows 1,6) while the setup keeps 1.6. It shows the size in force as its value - the one that was chosen, else the one Nino.css gives the role - so that the spinner counts from it, and a size equal to the one Nino.css gives is stored empty, so that the role goes on following its default.',
				'de_DE' => 'Die Zahl wird auf drei Dezimalstellen gerundet (ein getipptes 1,25 bleibt 1,25). Das Feld zählt in Zehnteln, auf denen jede Grenze liegt, und zeigt und nimmt das Dezimalzeichen der Browsersprache (deutsch: 1,6), das Setup behält 1.6. Es zeigt die wirksame Größe als Wert – die gewählte, sonst die, die Nino.css der Rolle gibt –, damit der Spinner von ihr aus zählt, und eine Größe, die der von Nino.css entspricht, wird leer gespeichert, damit die Rolle weiter ihrer Vorgabe folgt.',
			],
			'Template of a tab' => [
				'en_US' => 'Content, Buttons and Components each begin with the select of their template; the header and the footer are chosen on Global. The description of the chosen template stands under the select, and choosing another one draws the rows of the tab again - with the focus still on the select, so the arrow keys go on through the list.',
				'de_DE' => 'Inhalte, Buttons und Komponenten beginnen je mit der Auswahl ihrer Vorlage; Header und Footer werden unter Global gewählt. Unter der Auswahl steht die Beschreibung der gewählten Vorlage, und eine andere Vorlage zeichnet die Zeilen des Tabs neu – der Fokus bleibt dabei auf der Auswahl, die Pfeiltasten laufen also weiter durch die Liste.',
			],
			'Content, Buttons, Components' => [
				'en_US' => 'One strip for each named knob of the tab - hero title and text, quiet, loud, section space, card space, corners, border and shadow for the content; size, spread, space and corners for the buttons; scale, space, corners, border, zebra and labels for the components. The label names the element first (Card, corners - Button, padding) and the line under it says which way the strip goes (sharp ↔ round). A scale has five positions, -2 to +2, the middle the template as it is; a choice, zebra and labels, has three, in the words it has for them. The size of the buttons is the size of the normal button - its font size and padding, and its radius where the template scales one - and the spread is how far the small and the big button stand from it. When another template is chosen, only the knobs it answers to keep their position; the others go back to the middle.',
				'de_DE' => 'Eine Leiste je benanntem Regler des Tabs – Hero-Titel und -Text, leise, laut, Abstand der Sections, Abstand der Karten, Ecken, Linie und Schatten für die Inhalte; Größe, Änderung Klein/Groß, Innenabstand und Ecken für die Buttons; Textgröße, Abstände, Ecken, Linien, Streifen und Beschriftung für die Komponenten. Das Label nennt zuerst das Element (Karte, Ecken – Button, Innenabstand), die Zeile darunter sagt, wohin die Leiste geht (scharf ↔ rund). Eine Skala hat fünf Stellungen, -2 bis +2, die Mitte die Vorlage, wie sie ist; eine Auswahl, Streifen und Beschriftung, hat drei, in den Wörtern, die sie dafür hat. Die Größe der Buttons ist die des normalen Buttons – Schriftgröße und Padding, Radius, wo die Vorlage ihn skaliert –, die Änderung Klein/Groß, wie weit der kleine und der große davon abstehen. Wird eine andere Vorlage gewählt, behalten nur die Regler ihre Stellung, auf die sie hört; die übrigen stehen wieder in der Mitte.',
			],
			'Knobs of a template' => [
				'en_US' => 'A template publishes a scale by declaring its five values (--content-corners--least, --less, --default, --more and --most) and a choice by declaring its three (--less, --default and --more); a knob with another count is not published, and one the template does not answer to is greyed out with a line saying so. Choosing the earlier template again does not bring back a position a change of template reset. A hero title or subtitle that carries the base class and a variant has the size of the base class from 768 px - that is a rule of Nino.css - so quiet and loud reach a hero below 768 px only.',
				'de_DE' => 'Eine Vorlage veröffentlicht eine Skala, indem sie ihre fünf Werte deklariert (--content-corners--least, --less, --default, --more und --most), und eine Auswahl, indem sie ihre drei deklariert (--less, --default und --more); ein Regler mit einer anderen Anzahl wird nicht veröffentlicht, und einer, auf den die Vorlage nicht hört, steht ausgegraut da, mit einer Zeile, die das sagt. Mit der früheren Vorlage kehrt eine Stellung nicht zurück, die ein Wechsel der Vorlage zurückgesetzt hat. Ein Hero-Titel oder -Untertitel mit der Basisklasse und einer Variante hat ab 768 px die Größe der Basisklasse – das ist eine Regel von Nino.css –, deshalb erreichen „leise“ und „laut“ einen Hero nur darunter.',
			],
			'Colours' => [
				'en_US' => 'A brand colour, a second colour (or Harmony, which derives it), temperature, saturation, contrast, depth and the mode: harmony and temperature are named alternatives, saturation, contrast and depth strips of five positions, -2 to +2, the middle the palette Nino.css has. The mode is automatic, which follows the visitor, light only or dark only; the last two write the palette of one mode without a media query, which leaves the Modeswitch feature without effect. The palette has eleven surfaces of eight values each; the unsafe second colour is no surface of its own any more, since nothing in Nino.css reads it - its -safe variant is the ink. Hover and active values are gone for the same reason, and the shadow is one alpha for each mode, whatever the depth.',
				'de_DE' => 'Eine Markenfarbe, eine zweite Farbe (oder Harmonie, die sie ableitet), Temperatur, Sättigung, Kontrast, Tiefe und der Modus: Harmonie und Temperatur sind benannte Möglichkeiten, Sättigung, Kontrast und Tiefe Leisten mit fünf Stellungen, -2 bis +2, die Mitte die Palette, die Nino.css hat. Der Modus ist automatisch, das folgt der Besucherin, nur hell oder nur dunkel; die beiden letzten schreiben die Palette eines Modus ohne Media Query, was das Feature Modeswitch ohne Wirkung lässt. Die Palette hat elf Flächen mit je acht Werten; die ungesicherte zweite Farbe ist keine eigene Fläche mehr, weil Nino.css sie nirgends liest – ihre -safe-Variante ist die Schrift. Hover- und Active-Werte sind aus demselben Grund weg, und der Schatten ist ein Alphawert je Modus, ganz gleich welche Tiefe.',
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
			'Frame and action bar' => [
				'en_US' => 'The frame fills the window down to the action bar: as tall as the window is from the top of the frame to the bar, less a gap, never under 16 rem, and fitted again when the window is resized or another tab is opened. Where the preview stands under the controls and the window leaves less than that below it, the frame is as tall as it would be with the preview scrolled to the top of the window. Nothing stands under the frame: what a save, an apply, a restore or a reset did, or why not, is said in the line of the action bar - one line, cut off with dots where it is longer than the room, its full text the title - and the state of the applied file in one word on the chip beside it.',
				'de_DE' => 'Der Frame füllt das Fenster bis zur Aktionsleiste: Er ist so hoch, wie das Fenster von seiner Oberkante bis zur Leiste misst, abzüglich eines Abstands, nie unter 16 rem, und wird bei jeder Größenänderung des Fensters und beim Öffnen eines anderen Tabs neu eingepasst. Wo die Vorschau unter den Reglern steht und das Fenster darunter weniger Platz lässt, ist er so hoch, wie er wäre, wenn die Vorschau an den oberen Rand des Fensters gescrollt wäre. Unter dem Frame steht nichts: Was ein Speichern, Anwenden, Wiederherstellen oder Zurücksetzen getan hat oder warum nicht, steht in der Zeile der Aktionsleiste – eine Zeile, mit Punkten abgeschnitten, wo sie länger ist als der Platz, ihr voller Text der Titel – und der Zustand der angewendeten Datei in einem Wort auf dem Chip daneben.',
			],
			'The delivered look' => [
				'en_US' => 'The theme.css the setup wizard delivers is what this compiler writes at its defaults - twelve sections, every template the first one, every knob in the middle - from the end of its header on, byte for byte. The header is the kernel\'s own, and the kernel regenerates the file with each release (the next one carries the five values of each knob), so that the look a project starts from and the look Design compiles have one source. The border of each ground is assigned in base.css (--color-border-<ground>), and so in the delivered file, from the border solved for that surface: a line, a badge or a marker on an alt, tint, dark, black or brand ground takes it. The faces are named Source Sans 3 and League Spartan in both files. The delivered file has no stamp of the compiler\'s, so the panel calls it "Not written by Design" and the first apply asks before it writes over it.',
				'de_DE' => 'Die theme.css, die der Einrichtungsassistent mitbringt, ist das, was dieser Compiler an den Vorgaben schreibt – zwölf Abschnitte, jede Vorlage die erste, jeder Regler in der Mitte –, vom Ende ihres Kopfes an Byte für Byte. Der Kopf ist der des Kernels, und der Kernel erzeugt die Datei mit jeder Version neu (die nächste bringt die fünf Werte je Regler mit), damit das Aussehen, mit dem ein Projekt beginnt, und das, was Design kompiliert, eine Quelle haben. Der Rahmen jeder Fläche wird in der base.css (--color-border-<fläche>) und damit in der ausgelieferten Datei aus dem für diese Fläche gelösten zugewiesen: Linie, Badge und Markierung auf einer Alt-, Tint-, Dunkel-, Schwarz- oder Markenfläche übernehmen ihn. Die Schriften heißen in beiden Dateien Source Sans 3 und League Spartan. Die ausgelieferte Datei hat keinen Stempel des Compilers, deshalb nennt das Panel sie „Nicht von Design geschrieben“, und das erste Anwenden fragt nach, bevor es sie überschreibt.',
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
