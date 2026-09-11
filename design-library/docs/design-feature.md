# Das Design-Feature — Konzept

**Arbeitsstand, deutsch.** Dies ist kein Handbuch, sondern der Entwurf, auf den wir uns
verständigt haben, bevor Code entsteht. Eine englische Fassung bekommt er, wenn das
Feature gebaut ist und daraus eine Referenz wird — ein bewegliches Ziel zweimal zu
pflegen kostet mehr, als es hier einbringt.

Er gehört hierher, weil in [`design-library/`](../README.de.md) das Material liegt, das
auf dieses Feature wartet: zehn Themes, sechs Header, sieben Footer und das archivierte
Handbuch des Design-Panels, wie Nino 1.1 es hatte.

## 1. Was das Feature ist

Nino 1.2 liefert ein festes Aussehen aus: `assets/theme.css` plus die beiden
Frame-Templates, geschrieben von der Base-Einheit des Assistenten. Das Design-Feature
**ersetzt diese Datei durch eine kompilierte**.

Abgrenzung zum Template-Baukasten, entlang einer Kante:

> **Templates entscheidet, was auf der Seite steht. Design entscheidet, wie das aussieht,
> worauf es steht.**

Der Baukasten besitzt siebzehn Section-Presets mit Layouts und Areas. Design bringt
kein Markup für Sections mit — sonst gäbe es zwei Kataloge derselben Sache und die
Frage, welcher gewinnt. So komponieren die beiden Features, statt zu konkurrieren, und
jedes funktioniert ohne das andere.

## 2. Warum die alten Themes nicht gereicht haben

Nicht, weil sie zu ähnlich waren. Weil sie sich **nicht unterscheiden konnten**.

`Nino.css` verdrahtet die Gestaltungsentscheidungen hart:

```css
.nino-section-title { text-align: center; … }
.nino-article       { border-radius: var(--radius-small); padding: 0 0 var(--space-1); }
.nino-btn           { text-align: center; font-weight: bold; }
```

Ein Theme konnte davon nichts anfassen. Die zwölf Regler verschieben nur Werte
*innerhalb* dieser Entscheidungen. Nachgezählt: die zehn Theme-Stylesheets haben je
rund 200 Zeilen mit 4–9 Regelblöcken, im Kern ein `:root` mit Tokenzuweisungen, und
**kein einziges** überschreibt `text-align`, `border`, `::before`, `::after` oder
`position` an einem Titel oder einem Artikel. Das Ausdrucksstärkste im ganzen Katalog
ist Posters `text-transform: uppercase`.

Deshalb hat -1/0/+1 praktisch nichts bewirkt: es waren keine zehn Designs, es war ein
Design in zehn Farb- und Größenvarianten.

## 3. Zwei Sorten Katalogeintrag

**(A) Bauteil mit Markup.** Bringt ein `.tpl` und seine CSS mit, wird ins Projekt
kopiert, ist danach Projektdatei. Erneut anwenden überschreibt.

**(B) Design-Set.** Reine CSS über bestehendes Markup, kompiliert in `theme.css`. Wird
erzeugt, nicht kopiert.

Der Unterschied ist nicht kosmetisch: (A) hinterlässt eine Datei, die dem Projekt
gehört, (B) hinterlässt nur die kompilierte Ausgabe. Beides ist nach dem Löschen des
Features unbeschädigt — das ist die Bedingung, unter der das Feature überhaupt entfernbar
sein soll.

## 4. Die Bereiche

### (A) — drei Kataloge mit Markup

| Bereich | Bestand | Anmerkung |
|---|---|---|
| **Header** | 6 Varianten, 76–191 Zeilen CSS | Echte Strukturvarianten: Leiste, Overlay hinter einer Marke, Schiene an der Seite |
| **Footer** | 7 Varianten | Unterscheiden sich stärker im Inhalt als im Stil. Braucht daher eine eigene install-Unit mit den Textschlüsseln und ein `requires` wie ein Feature |
| **Schriftsätze** | neu | woff2-Dateien, `@font-face`-Block, Rollen (Display/Text/Mono). Dateien, die kopiert werden — also (A), nicht (B) |

Die (A)-Seite ist gut bestückt: die dreizehn Frame-Stylesheets tragen echte Struktur.

### (B) — sieben Teile, je 4–6 Sets

Geschnitten nach **Komponente**, nicht nach Eigenschaftstyp. Der Grund: ein
Article-Title darf relativ klein sein, während der Section-Title relativ groß ist. Nach
Titel/Text/Fläche zu schneiden schweißt genau das zusammen. Der Rahmen denkt selbst in
Komponenten — `Nino.css` trägt neun `--quiet`/`--loud`-Paare, und zwar je Bauteil:
`atf-title`, `atf-subtitle`, `section-title`, `section-subtitle`, `section-text`,
`article-title`, `article-descr`, `pricing-title`, `pricing-price`.

| # | Teil | umfasst |
|---|---|---|
| 1 | **ATF** | Title, Subtitle, Content |
| 2 | **Section** | Title, Subtitle, Padding, Margin, Border |
| 3 | **Article** | Image, Title, Subtitle, Descr, Padding, Margin, Border, Shadow |
| 4 | **Buttons** | Form, Füllung, Größenverhältnisse, Hover |
| 5 | **Forms** | Feldrahmen, Labelposition, Fokus |
| 6 | **Listen & Tabellen** | `nino-list-*`, `nino-table-*`, `nino-badge-*` |
| 7 | **Bausteine** | `nino-pricing-*`, `nino-timeline-*` |

6 und 7 sind nicht Zierde. Ohne sie bleiben Inseln, die keinem Teil gehören — gezählt in
Regeln mit echter Gestaltungsentscheidung: timeline 10 von 12, pricing 9 von 15, table 7
von 8, list 5 von 8, badge 4 von 8. Und die Presets holen sich nichts aus den Primitiven:
`pricing-plans` erzeugt `nino-pricing-*`, kein `.nino-article`. Eine Seite auf „eckig,
gerahmt, laut" hätte sonst eine Preistabelle von der Stange daneben. Dass zwei der neun
`--quiet`/`--loud`-Paare `nino-pricing-*` gehören, sagt dasselbe: Preispläne sind ein
Bauteil mit eigener Lautstärke, keine Insel, die man übersehen darf.

**ATF ist zu Recht ein eigener Teil.** `.nino-atf-title` ist
`calc( var(--text-4) * 1.2 )` — ein fest verdrahteter Faktor über der Section-Skala,
dazu `opacity: .8` am Untertitel und ein eigener Pfeil. Kein „Section mit großem Titel",
sondern ein eigenes Größenverhältnis.

## 5. Der Mechanismus

### Das Set liefert Tripel, der Regler wählt

Ein Regler modifiziert nichts. Das Set legt fest, was seine drei Stufen *sind*:

```php
'section-title-fontsize' => [ 'less' => '2rem', 'default' => '4rem', 'more' => '6rem' ],
'section-padding'        => [ 'less' => '1rem', 'default' => '3rem', 'more' => '5rem' ],
```

Der Grund: `+1rem` verhält sich bei einem Default von 5rem anders als bei 2rem. Und es
gibt Fälle, in denen nur `4rem / 6rem / 7rem` funktioniert — eine lineare Ableitung kann
das nie treffen. Das kostet Testaufwand und bringt dafür Unterschiede, die man sieht.

Benennung `less / default / more`: dasselbe Paar liest sich für Größe, Abstand *und*
Kontrast richtig. `--quiet`/`--loud` bleiben davon unberührt — das sind Klassen, die der
Baukasten je Instanz setzt. Zwei Mechanismen, zwei Vokabulare.

**Regler sind pro Teil.** Sonst ist „viel Space bei Sections, wenig bei Articles" wieder
unmöglich, und genau dafür ist der Schnitt gemacht.

### Werte sind absolut

Die Tripel stehen in `rem`, nicht in `var(--space-*)`. Global skaliert stattdessen ein
Regler s/m/l die Wurzel — und den gibt es schon:

```css
:root { --base-size: 16px; }
html   { font-size: var(--base-size); }
@media (min-width: 768px) { :root { --base-size: 18px; } }
```

Zwei Dinge dazu, beide Nino-Sache (siehe 7): es ist ein **Paar**, der Regler muss beide
Werte bewegen; und `px` überschreibt die Schriftgröße, die der Besucher im Browser
eingestellt hat.

Was mitskaliert, ist sauber getrennt: `--grid-max-width: 120rem` wächst mit, die
Breakpoints (640/768/1024/1280 px) nicht. Typo wird größer, das Layout bricht an
denselben Stellen.

### Kompiliert wird die Entscheidung, nicht die Auswahl

CSS kann keine Variablennamen zusammensetzen — `var(--x--$stand)` gibt es nicht. Alle
drei Stufen auszuliefern und per Auswahlzeile zu schalten wäre also machbar, kostet aber
rund 180 nie gelesene Deklarationen in jedem Seitenabruf jedes Besuchers. Ungenutzte CSS
ist ungenutzte CSS.

Also:

```
Eingang    Basis-Farben · Reglerstände · gewählte Sets
   ↓
Ausgabe    private/assets/theme.css      nur die getroffene Entscheidung
           private/data/design.php       das vollständige Setup
```

Das Setup in `data/` ist die Quelle, aus der neu kompiliert wird. Feature löschen: die
kompilierte CSS bleibt und funktioniert. Etwas ändern: Feature neu installieren (geht
schnell), es findet sein Setup vor und kompiliert daraus weiter.

Dafür braucht es **keine neue Mechanik**: `feature.php` hat den Schlüssel `data`, und
`\Nino\Backup` liest ihn. `'data' => [ '/data/design.php' ]` genügt, damit das Setup in
jedem Backup mitreist.

### Zwei Pflichten der kompilierten Datei

**Set-Identität mit Version im Setup.** Steht dort nur `section: editorial` und die neu
installierte Design-Version bringt ein überarbeitetes `editorial` mit, verändert das
Neukompilieren die Seite lautlos. Also `editorial@1.2` speichern und beim Kompilieren
sagen, wenn sich etwas bewegt hätte.

**Kopf und Hash in der Ausgabe.** Wer `theme.css` in zwei Jahren öffnet, muss sehen, dass
sie erzeugt ist und woraus. Mit einem Hash des Setups erkennt Design außerdem, dass
jemand von Hand editiert hat, und **weigert sich zu überschreiben**, bis man es ihm
sagt. Das ist wichtig, weil die mitgelieferte Base-Datei ausdrücklich von Hand editierbar
ist — ein Projekt kann sehr wohl etwas darin haben, wenn Design zum ersten Mal
installiert wird.

## 6. Die Oberfläche

Der Picker ist ein **Streifen kleiner Live-Muster nebeneinander** — fünf
Titelbehandlungen, fünf Artikelkarten —, kein Raster ganzer Seiten. Vergleichen ist die
einzige Aufgabe dieses Bildschirms, und ein Streifen kann das besser.

Die Ganzseitenvorschau bleibt als zweite Ansicht: „hängt das zusammen". Sie braucht eine
Beispielseite und Beispieltexte, die sie nicht vom Projekt nehmen kann, weil ein frisches
Projekt keine hat — das war `Design\Preview` mit `preview-example.tpl`, und in irgendeiner
Form kommt es zurück. Realistisch ist die Vorschau mehr Arbeit als der Katalog.

Nebenwirkung: derselbe Streifen ist der Test. 7 Teile × 5 Sets × 3 Stufen sind 105
Zustände, aber jeder betrifft nur *ein* Teil — also 105 Screenshots derselben Maschinerie,
die ohnehin für die Oberfläche entsteht. Was damit nicht geprüft ist: ob ein lautes
Section-Set neben einem leisen Article-Set gut aussieht. Das ist Geschmack, dafür ist die
Ganzseitenvorschau da.

## 7. Voraussetzungen in Nino

Drei Änderungen gehören in den Kernel und **vor** das Feature:

1. **`@layer nino.base, nino.design`.** `Nino.css` benutzt heute keine Layer. Ein Set,
   das `.nino-section-title` überschreibt, gewinnt über Quellreihenfolge — aber nur,
   solange Nino.css nicht spezifischer ist, und das ist es an vielen Stellen
   (`.nino-section--tint .nino-section-title` ist 0,2,0 gegen 0,1,0). Das Ergebnis wäre
   nicht „geht nicht", sondern „geht manchmal", und das ist schlimmer. Mit Layern schlägt
   jede Regel der Design-Schicht jede Regel der Basis, unabhängig von Spezifität.
2. **`--base-size` relativ statt absolut.** `100% / 106.25% / 112.5%` statt
   `16px / 17px / 18px` — dieselben Stufen, aber relativ zu dem, was der Besucher
   eingestellt hat. Einzeiler.
3. **Der Backup-Haken darf nicht an „aktiv" hängen.** `Backup` überspringt die
   `data`-Dateien eines deaktivierten Features. Das widerspricht dem, was Deaktivieren
   zusagt („Einstellungen, Daten … bleiben"): sie bleiben auf der Platte, aber ein Backup,
   das währenddessen läuft, hat sie nicht, und eine Wiederherstellung daraus verliert sie.
   Trifft Newsletter genauso. Die `data`-Deklaration soll gelten, solange das Feature *da*
   ist, nicht solange es *an* ist.

## 8. Bewusst offen

- **Prüfung der Set-Grenzen.** Ein Set soll nur die Klassen seines Teils anfassen und nur
  gegen die vordefinierten `var()` arbeiten. Beides wäre in `bin/build.php` maschinell
  prüfbar (~20 Zeilen). Bewusst *nicht* eingeplant — das Risiko ist, dass ein Set still in
  ein fremdes Teil greift und die Unabhängigkeit dort bricht, wo niemand hinsieht.
- **Die Presets behalten ihr eigenes Vokabular.** Sauberer wäre, wenn `pricing-plans` aus
  Article-Primitiven komponierte. Das ist aber eine Änderung am Baukasten, der schon als
  1.0.0 veröffentlicht ist — zwei Releases aneinanderzubinden bremst beide.
- **Was aus den zehn Themes wird.** Als **Startpunkte**, die alle Bereiche auf einen
  Schlag setzen und danach einzeln wechselbar bleiben. Jedes trägt bereits `header`,
  `footer` und einen vollständigen `design`-Block. Für die (B)-Sets sind sie *kein*
  Ausgangsmaterial — da ist nichts drin, was man abbauen könnte.

## 9. Aufwand, ehrlich

Der Katalog ist nicht die Arbeit. Die Arbeit sind:

- **etwa 35 handgeschriebene Sets** (7 Teile × 4–6), jedes muss auf jeder Palette, jedem
  Reglerstand, in hell und dunkel und über alle siebzehn Section-Presets halten;
- **die Vorschau**, die ohne Projektinhalt auskommen muss;
- **88 regelbare Eigenschaften** über die zehn Klassenfamilien, von denen zu entscheiden
  ist, welche wirklich drei Stufen brauchen.

Die (A)-Seite ist dagegen fast fertig — dreizehn Frames liegen hier und tragen echte
Struktur.
