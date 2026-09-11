# Design-Bibliothek

**Sprache:** [English](README.md) · Deutsch

Zehn Themes, sechs Header und sieben Footer – hier geparkt für das Feature **Design**, das noch nicht geschrieben ist.

## Warum das nicht mehr in Nino liegt

Bis Nino 1.1 hat der Setup-Assistent vier Fragen zum Aussehen einer Seite gestellt: ein Theme, einen Header, einen Footer und die daraus kompilierten Design-Werte. Der Katalog unter `_admin/install/library/{themes,header,footer}` ist das, was diese vier Schritte gelesen haben.

Nino 1.2 fragt nicht mehr. Der Assistent installiert eine Seite und hört da auf: Die Base-Einheit liefert ein festes Aussehen aus – `assets/theme.css` und die beiden Templates `theme.header.tpl` und `theme.footer.tpl`, gegen die es gezeichnet ist –, und jedes Projekt startet von derselben Seite. Das ist ein kleinerer Kernel und ein kürzerer Assistent, und es legt das Aussehen dorthin, wo in Nino alles Optionale liegt: in ein Feature, das man installiert, wenn man es will.

Das Feature **Design** ist dieses Feature. Es wird seine eigene CSS kompilieren und die mitgelieferte ersetzen, und es wird je einen Katalog pro Bauteil einer Seite anbieten – Header, Footer, Sections, Typografie, Artikel – statt eines Themes für die ganze Seite. Deshalb ist dieses Material nicht gelöscht, sondern wartet: Die zehn Themes sind zehn fertige Sätze von Entscheidungen, und die dreizehn Rahmen sind Markup und CSS, die gegen Ninos Grid und sein Scroll-Verhalten bereits funktionieren.

**Nichts hiervon ist ein Feature.** `bin/build.php` und `bin/check.sh` sehen ausschließlich unter `features/` nach; dieses Verzeichnis wird nie in ein Archiv gepackt und nie in `catalogue.json` gelistet. Es ist Ausgangsmaterial für ein Feature, das als Nächstes geschrieben wird.

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

`basis` ist das eine, das Nino 1.2 behalten hat: sein `design`-Block und sein Stylesheet sind zwei der vier Abschnitte, die in `assets/theme.css` der Base-Einheit zusammengeschrieben sind, zusammen mit `header/v1/style.css` und `footer/v1/style.css`. Die Seite, die ein frisches Nino installiert, ist `basis` – Byte für Byte das, was der alte Assistent geliefert hat, wenn man viermal auf Weiter gedrückt hat.

### `header/v<n>/` und `footer/v<n>/`

Je ein Seitenrahmen: `template.tpl` und `style.css`, sonst nichts. Das Template ist das, was `theme.header.tpl` bzw. `theme.footer.tpl` im Projekt wird, eingebunden von `html-header.tpl` über `[template /templates/theme.header]`; das Stylesheet ist das, was mit ihm ins Bundle geht.

| | Varianten | Was sich unterscheidet |
| --- | --- | --- |
| `header/` | v1 – v6 | Eine schlichte Leiste, eine Leiste mit Linie, eine schwebende Leiste, ein Overlay-Menü hinter einer Marke, ein Markenstreifen, eine Schiene an der Seite |
| `footer/` | v1 – v7 | Von einer einzelnen Rechtszeile bis zu einem vollen Spaltenlayout mit Social-Links, Kontaktblock und Sprachumschalter |

Zwei Dinge muss ein Header-Preset einhalten, welches auch immer es ist:

- Die Leiste trägt `nino-scroll-header`. `_nino/Nino.css` blendet sie unter `body.nino-scroll-down` aus, indem es `max-height`, `min-height`, beide vertikalen Paddings und beide horizontalen Rahmenbreiten zurücknimmt – ein Preset darf der Leiste also keine schlichte `height` geben, die nimmt davon nichts zurück. Ein Preset, das *keine* Leiste ist, meldet sich selbst ab: Die Schiene gibt `max-height: none` oberhalb ihres eigenen Breakpoints zurück.
- `footer/v2` bindet `[template /templates/html-socialmedia]` ein. Dieses Template liegt in der Base-Einheit, der Include löst also in jedem Projekt auf – ein Rahmen, der es braucht, muss es nicht selbst mitbringen.

### `docs/`

[`docs/appearance.de.md`](docs/appearance.de.md) ([English](docs/appearance.md)) ist das Handbuch des Panels **Design**, wie Nino 1.1 es ausgeliefert hat – hier archiviert aus demselben Grund wie die Einheiten: Die Einstellungen, die es beschreibt, die `--nino-*`-Token, zu denen sie kompilieren, und die Flächen, die diese Token einfärben, sind der Vertrag, an dem sich das Design-Feature messen lassen muss.

[`docs/design-feature.md`](docs/design-feature.md) ist das Konzept, aus dem das Design-Feature gebaut wird — was es katalogisiert, wie ein Set geschrieben ist, was es kompiliert und was es vorher von Nino braucht.

## Eines davon heute verwenden

Es sind schlichte Dateien, ein Projekt nimmt sich also von Hand, was es will: das `assets/` und `fonts/` des Themes ins Projekt kopieren, das `template.tpl` des Rahmens über `private/templates/theme.header.tpl` (bzw. `theme.footer.tpl`) legen, das `style.css` des Rahmens und das Stylesheet des Themes an `private/assets/theme.css` anhängen – oder als eigene Einträge in das Bundle unter `/nino/html/assets` aufnehmen – und neu laden. Werkzeug gibt es dafür nicht, und genau das ist der Punkt: Das Werkzeug ist das Design-Feature.
