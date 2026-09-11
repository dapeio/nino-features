# Design-Bibliothek

**Sprache:** [English](README.md) · Deutsch

Zehn Themes für ganze Seiten aus dem Nino-1.1-Assistenten, das archivierte Handbuch des Panels, das sie kompiliert hat, und das Werkzeug, in dem die Part-Sets des Features **Design** entworfen werden.

## Warum das nicht mehr in Nino liegt

Bis Nino 1.1 hat der Setup-Assistent vier Fragen zum Aussehen einer Seite gestellt: ein Theme, einen Header, einen Footer und die daraus kompilierten Design-Werte. Der Katalog unter `_admin/install/library/{themes,header,footer}` ist das, was diese vier Schritte gelesen haben.

Nino 1.2 fragt nicht mehr. Der Assistent installiert eine Seite und hört da auf: Die Base-Einheit liefert ein festes Aussehen aus – `assets/theme.css` und die beiden Templates `theme.header.tpl` und `theme.footer.tpl`, gegen die es gezeichnet ist –, und jedes Projekt startet von derselben Seite. Das ist ein kleinerer Kernel und ein kürzerer Assistent, und es legt das Aussehen dorthin, wo in Nino alles Optionale liegt: in ein Feature, das man installiert, wenn man es will.

Das Feature **Design** ist dieses Feature, und es gibt es inzwischen: [`features/Design/`](../features/Design) kompiliert seine eigene `assets/theme.css` über die mitgelieferte, aus je einem Set pro Bauteil einer Seite statt aus einem Theme für die ganze Seite. Die sechs Header und sieben Footer sind mit ihm in seine Bibliothek gezogen – sie sind das, woraus ein Projekt wählt, also reisen sie mit dem Feature. Hier geblieben ist, was das Feature nicht ausliefert: die zehn Themes, zehn fertige Sätze von Entscheidungen, aus denen man ein Part-Set liest statt sie zu installieren, und das archivierte Handbuch des Panels, das sie kompiliert hat.

**Nichts hiervon ist ein Feature.** `bin/build.php` und `bin/check.sh` sehen ausschließlich unter `features/` nach; dieses Verzeichnis wird nie in ein Archiv gepackt und nie in `catalogue.json` gelistet. Es ist Ausgangsmaterial für ein Feature, das ein Verzeichnis weiter liegt.

## Was hier liegt

### `themes/<key>/`

Ein Aussehen für die ganze Seite. `manifest.php` benennt es, beschreibt es für einen Picker, zeigt auf das Stylesheet, das es mitbringt, nennt die Header- und Footer-Version, gegen die es gezeichnet wurde, und trägt den `design`-Block, von dem die kompilierte Token-Schicht ausgegangen ist:

```php
<?php return [
	'label' 			=> 'Basis',
	'description' => 'Der neutrale Ausgangspunkt – …',
	'preview' 		=> 'preview.svg',
	'stylesheet' 	=> '/assets/style.theme.basis.css',
	'header' 			=> 'v1',
	'footer' 			=> 'v1',
	'design' 			=> [ 'primary' => '#4faae8', 'harmony' => 1, 'temperature' => 3, /* … */ ],
	'files' 			=> [ 'assets', 'fonts' ],
];
```

Jede Einheit ist in sich geschlossen: das Stylesheet, das ihr Manifest nennt, jeder Webfont, den dieses Stylesheet per `@font-face` einbindet, und ein SVG als Vorschau.

| Key | Name | Header | Footer | Was es ist |
| --- | --- | --- | --- | --- |
| `basis` | Basis | v1 | v1 | Der neutrale Ausgangspunkt – eine schlichte weiße Seite, ein blauer Akzent, jede Größe direkt von der Skala des Frameworks. Das Aussehen für den Fall, dass der Inhalt sprechen soll. |
| `bureau` | Bureau | v1 | v3 | Eine Unternehmensseite, die sich auch so liest: eine schlichte Leiste, kühle Grautöne, eckige Kanten und eine zweite Markenfarbe einen Schritt neben der ersten. Für Agenturen, Kanzleien, Verbände und alles, was gesetzt statt neu wirken soll. |
| `chronicle` | Chronicle | v2 | v3 | Serifen im Fließtext auf warmem Papier, eine Grotesk darüber und ein getöntes Band für das, was zitiert statt gesagt wird. Schmales Satzmaß, harte Kanten, keine Schatten. Für Magazine, Journale, lange Texte und Dokumentation, die gelesen und nicht durchsucht wird. |
| `console` | Console | v6 | v2 | Eine Navigationsschiene an der Seite, die kleinste Grundgröße der zehn, kühle Grautöne und ein markenfarbenes Band für Hinweise. Dicht, eckig und breit. Für Dokumentation, Changelogs, APIs und Referenzen mit vielen Einträgen. |
| `gallery` | Gallery | v4 | v1 | Eine Wand, keine Seite: Grautöne ganz ohne Farbstich, ein Overlay-Menü hinter einer Marke, breite Reihen und viel Luft. Die Marke erscheint nur dort, wo man sie hinsetzt. Für Fotografinnen und Fotografen, Portfolios, Ausstellungen und alles, wo das Bild der Inhalt ist. |
| `market` | Market | v5 | v6 | Ein Markenstreifen über den Kopf und ein Band in der Gegenfarbe quer durch die Seite – zwei laute Farben, schmal laufende Display-Schrift, runde Buttons. Für Shops, Events, Kampagnen und Launches. |
| `midnight` | Midnight | v5 | v2 | Dunkel in jedem Licht: Die Seite sitzt auf der tiefsten Fläche, statt der Systemeinstellung der Besucherin zu folgen, mit einer dritten Markenfarbe für das, was sich davon abheben muss. Rund und erhaben. Für Galerien, Produktaufnahmen, Musik und alles, was in einem abgedunkelten Raum gezeigt wird. |
| `platform` | Platform | v3 | v7 | Flächen, die aufeinander liegen, statt Linien, die trennen: eine schwebende Leiste, erhabene Karten, breite Ränder und eine dritte Farbe für das, was gerade gewählt ist. Für Produkte, Apps, SaaS und alles, was aktuell wirken soll. |
| `poster` | Poster | v4 | v4 | Überschriften über die ganze Breite, schwere schwarze Bänder und ein Overlay-Menü – die größte Grundgröße der zehn, eng gesetzt. Eine laute Farbe und keine zweite. Für Studios, Agenturen, Kampagnen und alles, was quer durch den Raum lesbar sein soll. |
| `practice` | Practice | v3 | v5 | Rund, warm und ohne Eile: die größte Grundgröße, sanfter Kontrast, ein markenfarbenes Footer-Panel und eine zweite Farbe einen Schritt neben der ersten. Für Praxen, Studios, lokale Dienstleistungen und alle, deren Besucher Verlässlichkeit statt Neuheit suchen. |

`basis` ist das eine, das Nino 1.2 behalten hat: sein `design`-Block und sein Stylesheet sind zwei der vier Abschnitte, die in `assets/theme.css` der Base-Einheit zusammengeschrieben sind, zusammen mit dem Header und Footer `v1` – heute [`features/Design/library/header/v1`](../features/Design/library/header/v1) und [`footer/v1`](../features/Design/library/footer/v1). Die Seite, die ein frisches Nino installiert, ist `basis` – Byte für Byte das, was der alte Assistent geliefert hat, wenn man viermal auf Weiter gedrückt hat.

### `docs/`

[`docs/appearance.de.md`](docs/appearance.de.md) ([English](docs/appearance.md)) ist das Handbuch des Panels **Design**, wie Nino 1.1 es ausgeliefert hat – hier archiviert aus demselben Grund wie die Einheiten: Die Einstellungen, die es beschreibt, die `--nino-*`-Token, zu denen sie kompilieren, und die Flächen, die diese Token einfärben, sind der Vertrag, an dem sich das Design-Feature messen lassen muss.

[`docs/design-feature.md`](docs/design-feature.md) ist das Konzept, aus dem das Design-Feature gebaut wurde — was es katalogisiert, wie ein Set geschrieben ist, was es kompiliert und was es vorher von Nino gebraucht hat. Es ist das Arbeitspapier, in dem die Diskussion geendet hat, kein Handbuch. Das Handbuch ist [`features/Design/README.md`](../features/Design/README.md).

## Die Vorschau

`preview.php` ist ein Design-Werkzeug für die Part-Sets – nur für die Entwicklung, nie ausgeliefert und nicht Teil des Feature-Archivs. Es startet ein echtes Nino gegen ein Wegwerf-Projekt, wendet die Base-Einheit darauf an, kompiliert die benannten Sets und Rahmen durch `\Nino\Modules\Design\Setup` und `Compiler` des Features selbst und rendert eine Musterseite, die jede Klasse anfasst, die ein Set erreichen kann. Was es zeigt, ist damit das, was ein Projekt bekommt, und keine Annäherung daran.

```bash
git clone https://github.com/dapeio/nino.git ../nino     # oder NINO_ROOT setzen
php -S 127.0.0.1:8080 design-library/preview.php
```

Dann <http://127.0.0.1:8080/> öffnen. Was zu sehen ist, steht im Array am Kopf der Datei:

```php
const PARTS = [
	'header' 	=> 'v1',   // features/Design/library/header/v1
	'footer' 	=> 'v3',   // features/Design/library/footer/v3
	'section' => 'v4',   // features/Design/library/sets/section/v4.css
	'article' => [ 'v2', 'less' ],   // Set v2, auf seiner Stufe „less“
	…
];
```

Das Wegwerf-Projekt wird bei jedem Aufruf neu gebaut – ein geändertes Set oder ein geänderter Rahmen ist also einen Reload entfernt. Und es ist das, was eine echte Seite rendert: Die Rahmen laufen durch `\Nino\Html::renderHtml()`, ihre Textfills, ihre `[template]`-Includes und der Shortcode `[navigation]` lösen also auf wie in einem Projekt – die Musterseite bringt sogar ein fünfteiliges Menü mit, damit ein Header-Set etwas zu setzen hat. Ein Set, das es nicht gibt, ein Rahmen ohne `style.css`, ein Fill, der nicht aufgelöst hat: All das benennt die Leiste am unteren Rand, statt es stillschweigend zu übergehen.

Die Sets und Rahmen selbst gehören dem Feature und liegen in [`features/Design/library/`](../features/Design/library) – in seinem [README](../features/Design/README.md) stehen die zwei Regeln, nach denen eines geschrieben wird. `sets/<part>/v1.css` ist dort der Ausgangspunkt für jedes der sieben Teile: Es erklärt nichts, eine frische Vorschau zeigt also Nino, wie es ist – und listet jede Regel, die das Framework für dieses Teil setzt, auskommentiert und mit den heutigen Werten, als die Griffe, die dieses Set hat. Kopiere es nach `v2.css` und fang dort an.

## Ein Theme heute verwenden

Die Themes sind schlichte Dateien, und kein Werkzeug installiert sie – ein Projekt nimmt sich also von Hand, was es will: das `assets/` und `fonts/` des Themes ins Projekt kopieren, sein Stylesheet an `private/assets/theme.css` anhängen – oder als eigenen Eintrag in das Bundle unter `/nino/html/assets` aufnehmen – und neu laden. Der Header und der Footer, die sein Manifest nennt, liegen inzwischen in der Bibliothek des Features; ein Rahmen ist ein `template.tpl`, das über `private/templates/theme.header.tpl` (bzw. `theme.footer.tpl`) gelegt und dessen `style.css` genauso angehängt wird.

Was von Hand an eine `theme.css` gehängt wird, die das Design-Feature kompiliert hat, nimmt der nächste Compile wieder weg – und `Compiler::write()` verweigert die Datei, sobald sie nicht mehr zu ihrem eigenen Kopf passt. Also entweder die Datei bewusst übernehmen – die Zeile mit der Prüfsumme löschen – oder das Theme in die projekteigene `assets/style.css` legen, die im Bundle nach `theme.css` kommt und niemandem sonst gehört.
