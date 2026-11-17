# Nino-Features

**Sprache:** [English](README.md) · Deutsch

Der Feature-Katalog von [Nino](https://github.com/dapeio/nino): die Features, die ein Nino-Projekt installieren kann, je eines pro Verzeichnis unter `features/`, mit eigener Version. Der Katalog selbst ist die Liste – [catalogue.getnino.dev](https://catalogue.getnino.dev) liefert ihn aus, und das Features-Panel eines Projekts liest ihn.

## Nino 2 entsteht

`main` wird für Nino 2 neu gebaut, das Komponenten, Blocks und Themes auf neue Weise liest. Jedes Feature und jede Anwendung, die für Nino 1 geschrieben sind, wartet in `features-1x/` und `applications-1x/`, die Tests, die sie lesen, in `tests-1x/`, bis sie umgezogen sind; `features/`, `applications/` und `tests/` enthalten, was schon Nino 2 ist. Kein Skript und kein Test liest die Warteordner, und `bin/release.sh` verweigert den Lauf, solange es `features-1x/` gibt: [catalogue.getnino.dev](https://catalogue.getnino.dev) liefert weiter den letzten Katalog für Nino 1, das ist der Tag `v1-final`. Der Rest dieses Dokuments beschreibt den Katalog, wie er nach dem Umzug arbeitet.

## Was dieser Katalog ist

Ein Nino-Checkout liefert kein Feature aus. Was ein Projekt über den Kernel hinaus braucht – einen Newsletter, eine Suche –, kommt als **Feature**: ein Verzeichnis mit einer Laufzeitklasse, bei Bedarf einem Panel der Workbench, einer Install-Einheit mit Templates und Texten und einem Manifest `feature.php`, das sagt, was es ist, für welche Nino-Version es geschrieben wurde und welche Einstellungen es anbietet. Was ein Feature liefern muss, steht im Vertrag [Features](https://github.com/dapeio/nino/blob/main/docs/features.md) in Nino; jedes Feature hier folgt ihm.

`features/<Name>/` ist genau das, was in einem Projekt landet. Das Repository macht nichts über das Netz: Es ist ein Dateibestand, den man klont oder kopiert, und die Quelle dessen, was getnino.dev als signierte Archive ausliefert. Was `main` trägt, listet der Katalog, siehe [Ein Release](#ein-release).

Ein Feature ist in seinem Manifest dokumentiert: Das `manual` in `feature.php` zeigt das Features-Panel auf dem Tab Beschreibung, und der `hint` einer Einstellung steht neben dem Feld im Formular. Weitere Dokumentation gibt es nicht zu pflegen. Die Geschichte eines Features ist die Commit-Historie.

Neben den Features veröffentlicht der Katalog zwei Sorten Paket, die keinen Code enthalten: ein **Theme** (`themes/<key>/`: `theme.json`, ein Teil – `part.json` und `part.css` – für die Seite und für jede Gruppe von Komponenten, eigene Blocks, eine `preview.webp`) und ein **Block-Paket** (`blocks/<key>/`: `pack.json`, je Block ein Ordner mit `block.json`, `block.tpl`, `text/<xx_XX>.json` und `images/<slot>.svg|webp|png|jpg`, eine `preview.webp`). Sie werden unter `private/packages/` installiert, das kein Webserver ausliefert, und zwar von derselben Funktion, die sie schon beim Bauen prüft: Was ein Projekt ausführt, steckt nicht in ihnen. [Wie ein Paket gebaut und geprüft wird](#wie-ein-paket-gebaut-und-geprüft-wird), steht weiter unten.

Neben den Features führt der Katalog wenige **Anwendungen** – benannte Verbünde von Features, die zusammen funktionieren, mit einem Einstieg; siehe [Eine Anwendung schreiben](#eine-anwendung-schreiben).

## Ein Feature installieren

Ein Feature wird nicht installiert, sondern hingelegt und eingeschaltet:

1. Kopiere `features/<Name>/` aus diesem Repository in das `features/` deines Projekts – als Ganzes, mit demselben Verzeichnisnamen. Der Name ist der Klassenname: `features/Newsletter/Newsletter.php` ist `\Nino\Modules\Newsletter`. Ein Projekt, das den Katalog liest, installiert stattdessen im Features-Panel.
2. Melde dich an `/_admin` an und öffne **Features** in der Gruppe System. Das Panel verlangt die Entwicklerberechtigung `/_admin/features/manage`. Es listet jedes Verzeichnis mit gültigem Manifest, eingeschaltet oder nicht, mit Version und mit dem, was einer Aktivierung entgegensteht – eine Nino-Version, für die das Feature nicht geschrieben wurde, eine fehlende PHP-Erweiterung, ein benötigtes Feature, das nicht da ist.
3. **Aktivieren.** Das wendet die Install-Einheit des Features an, ohne etwas zu überschreiben, das dein Projekt bereits hat, trägt die Klasse in `/nino/modules` ein und zeichnet die Version unter `/nino/features` in der `config.php` auf.

Ein Panel, das ein Feature mitbringt, erscheint mit dem nächsten Laden der Workbench, in der Gruppe Features der Leiste. Der Tab Nutzerrollen des Panels Nutzer bietet seine Berechtigung unter dieser Gruppe an, und die Rolle **Editor** erhält sie nicht von selbst.

**Update:** Ersetze `features/<Name>/` durch die neue Fassung und drücke **Update** im Panel Features. Das Panel bietet es an, sobald das Manifest eine andere Version nennt als die aufgezeichnete; die Einheit ergänzt, was neu ist, und lässt alles, was das Projekt bearbeitet hat, wie es ist. **Deaktivieren** trägt die Klasse aus `/nino/modules` aus – und sonst nichts: Einstellungen, Daten, kopierte Templates und Texte bleiben.

**Sicherheit:** Alles unter `features/` ist serverseitiger Quelltext. Der Nino-Checkout liefert `features/.htaccess` mit, das den Baum sperrt; ein Webserver, der `.htaccess` nicht liest, braucht die entsprechende Regel, siehe [Deployment](https://github.com/dapeio/nino/blob/main/docs/deployment.md) in Nino.

## Entwickeln und testen

Die Tests eines Features laufen gegen einen Nino-Checkout. Klone Nino neben dieses Repository – `bin/check.sh` erwartet ihn unter `../nino` oder dort, wohin `NINO_ROOT` zeigt:

```bash
git clone https://github.com/dapeio/nino.git ../nino
bin/check.sh
NINO_ROOT=/path/to/nino bin/check.sh
```

`bin/check.sh` kopiert jedes Feature, das der Checkout ausführen kann, in dessen `features/` – dieselbe Anordnung, die ein Projekt hat –, prüft jedes Manifest über `bin/catalogue.php`, führt die Tests dieser Features aus und entfernt die Kopien danach wieder; danach führt es jeden eigenen Test dieses Repositories unter `tests/` aus. Ein Feature, das für ein neueres Nino geschrieben ist als der Checkout, steht mit seinem Grund in der Liste und bleibt draußen (`bin/applicable.php` entscheidet das).

Ein einzelner Test läuft auch direkt:

```bash
NINO_ROOT=../nino php features/Hello/tests/hello-smoke.php
```

`bin/catalogue.php` gibt aus, was der Katalog listen würde, als JSON – die Anwendungen eingeschlossen –, ohne Archiv und ohne Signatur. CI (`.github/workflows/ci.yml`) führt dieselben Tests gegen Ninos `main` aus – den Katalog von Format 2 liest allein Nino 2, und Nino 1 hat keine Pakete, ihn damit zu lesen –, dazu eine Syntaxprüfung, Ninos eigenen Vertragstest sowie PHPStan und ESLint über `features/`. Nichts wartet darauf: Es ist eine Bequemlichkeit.

## Ein Feature schreiben

Das Handbuch [Developing a feature](https://github.com/dapeio/nino/blob/main/docs/features.md) in Nino führt ein Feature von der ersten Datei bis zum Test und beschreibt den Vertrag dahinter. `features/Hello/` ist das vollständige Beispiel dieses Handbuchs, zum Kopieren geschrieben: Kopiere es und benenne dann das Verzeichnis, den `key` in `feature.php` und die Klasse `\Nino\Modules\Hello` um, sodass die drei übereinstimmen.

```text
features/<Name>/
├── feature.php              das Manifest: key, name, description, manual, category, version, nino, requires, settings, data, assets
├── <Name>.php               die Laufzeitklasse \Nino\Modules\<Name>
├── Admin/Admin.php          das Panel, wenn es eines gibt
├── assets/ install/ text/ templates/   wenn das Feature sie braucht
└── tests/<key>-smoke.php    ein Test, wenn du einen willst
```

Verlangt sind `feature.php` und `<Name>.php`. Das `manual` des Manifests ist die Dokumentation: eine Map `section => handle => zeile` – Shortcodes, Markup, Routen, Panel, Callbacks, Install – mit dem Handle so, wie man ihn eintippt, und daneben einer Zeile auf Deutsch und Englisch. Ein Test ist willkommen und nie verlangt. Die Regeln, die bleiben, weil Tests sie prüfen, stehen in [AGENTS.md](AGENTS.md): kein Markup in PHP, Englisch im Code, Escaping, die Grammatik der Textschlüssel und ein Abschnitt der Datenschutzerklärung für ein Feature, das personenbezogene Daten verarbeitet. Ein Stylesheet antwortet auf die Breite auf eine Weise: drei Stufen – ein Telefon unter 768 px, ein Tablet ab 768 px, ein Desktop ab 1024 px –, vier Schwellen – 640, 768, 1024 und 1280 px – und immer `min-width`, damit eine Regel die des Telefons ist, bis eine breitere Stufe sie anders setzt.

`category` ist eine von `content`, `ui`, `communication`, `marketing`, `security` oder `system`, und kein Name kommt in irgendeiner Sprache bei zwei Features vor; `bin/build.php` weist beides ab.

## Eine Anwendung schreiben

Eine **Anwendung** ist ein benannter Verbund von Features, die nur zusammen funktionieren, mit einem Einstieg – eine Webseite, ein Blog, eine vCard, später ein CRM. Das ist der Maßstab: sie funktioniert nur im Verbund und hat einen Einstieg. Ein Zusatz wie Medien oder Statistik bleibt ein einzelnes Feature, das man im Features-Panel findet. Der Katalog führt absichtlich wenige Anwendungen: ein Türöffner für eine Art von Projekt, keine Sammlung von Kombinationen. Der Index trägt sie, damit die Einrichtung eines Kernels sie anbieten kann; welche davon eine Installation anbietet, entscheidet der Kernel, wie bei den Features.

Eine Anwendung ist eine Datei, `applications/<key>.php`, die ein Array zurückgibt. Jedes Feld steht da:

```php
return [
	'key'					=> 'website',
	'name'				=> [ 'en_US' => 'Website', 'de_DE' => 'Webseite' ],
	'description'	=> [ 'en_US' => '...', 'de_DE' => '...' ],
	'features'		=> [ 'builder', 'design', 'consent' ],
	'next'				=> '/_admin/#dashboard',
	'start'				=> 'starter',
	'menu'				=> null,
];
```

| Feld | Was es sagt |
| --- | --- |
| `key` | ein Slug, der Dateiname ohne `.php` |
| `name`, `description` | ein Text je Sprache, `de_DE` und `en_US` verlangt; kein Name kommt bei zwei Anwendungen vor |
| `features` | die Keys von Features dieses Katalogs, mindestens eines, keines doppelt |
| `next` | der Pfad im Projekt, zu dem die Einrichtung führt, mit optionalem Anker – `/_admin/#builder`, das Panel der Verwaltung mit seiner uri – oder `null`; kein Schema, kein `//` |
| `start` | wer die Startseite stellt: `starter` (die Starter-Seiten des Kernels), `own` (ein Feature der Anwendung bringt sie) oder `null` (keine Aussage). Anwendungen mit einem `start` schließen einander aus |
| `menu` | `"folded"`, wo die Verwaltung auf dem Panel von `next` mit eingeklapptem Menü öffnet, oder `null` für das Menü, wie es ist. Der Index trägt das Feld nur bei `"folded"`; ein Kernel, der es nicht kennt, übergeht es |

`bin/build.php` prüft jede Anwendung dagegen, und eine, die eine Regel verletzt, bricht den Build mit einer Meldung ab, die ihre Datei nennt. `bin/catalogue.php` listet sie über den Features. Der Index führt die Anwendungen nach Key, nur die mit den meisten Features – `full`, die Installation mit den eingeschalteten Features des Katalogs – steht zuletzt; ein Test hält ihre Liste gleich der Menge aller Features außer den Beispielen. In `full` behält die Website die Startseite und die vCard antwortet unter `/vcard`; ein Kernel, der die Startseite einer Anwendung mit `start: starter` noch nicht freihält, gibt sie dort der Karte, für die Startseite der Website braucht `full` also einen Kernel, der das tut.

## Ein Release

Ein Release ist `bin/release.sh`, vom Owner im Checkout auf dem Server ausgeführt. Der Katalog ist, was `main` trägt: Jeder Lauf baut jedes Feature nach `public/`, das Verzeichnis, das der Webserver ausliefert, und ein Feature, das sich nicht geändert hat, ergibt dieselben Bytes, sodass sich nichts bewegt, was ein Projekt sieht.

```bash
git pull
NINO_CATALOGUE_KEY=/safe/place/catalogue-key.pem bin/release.sh
```

Es führt zuerst `bin/check.sh` aus (`--quick` überspringt das), dann baut `bin/build.php` jedes Archiv und das Bild jedes Themes und Block-Pakets nach `public/`, schreibt `catalogue.json` und signiert es; jede Datei landet ganz, per Umbenennen. `NINO_ROOT` nennt den Nino-Checkout (Standard `../nino`), `NINO_CATALOGUE_URL`, wo `public/` ausgeliefert wird (Standard `https://catalogue.getnino.dev`). Eine Probe ist der Build in ein anderes Verzeichnis: `php bin/build.php ../nino /tmp/rehearsal`.

Es gibt einen Eintrag je Paket, die Version seines Manifests, von `theme.json` oder `pack.json`, und kein Tag. Eine Änderung ohne neue `version` erreicht neue Installationen, bestehende nicht: Das Features-Panel bietet ein Update aus dem Katalog nur für eine höhere Version an. Die `version` wird also erhöht, wenn installierte Projekte das Update angeboten bekommen sollen.

## Der Server

Der Checkout dieses Repositories, ein Nino-Checkout daneben, php und ein Webserver, der `public/` als statische Dateien über https ausliefert – nginx mit einem `root`, das darauf zeigt. Eine Datei, die nicht da ist, antwortet **404**. Kein PHP hinter dem Webserver, kein Upload, keine Konfiguration außer dem VirtualHost. Der Kernel liest die Bytes, welchen Content-Type der Server auch dafür nennt. Ein Server anderswo bekommt `public/` von dem, was Dateien dorthin bewegt.

## Der Signaturschlüssel

Das Schlüsselpaar entsteht einmal, offline, und die private Hälfte gelangt nie in ein Repository (`.gitignore` weist `*.pem` ab):

```bash
openssl ecparam -name prime256v1 -genkey -noout -out catalogue-key.pem
openssl ec -in catalogue-key.pem -pubout -out catalogue-key.pub.pem
```

Bewahre `catalogue-key.pem` an einem sicheren Ort außerhalb jedes Repositories auf, mit einer Kopie. `catalogue-key.pub.pem` ist öffentlich: Es ist, was Nino als `\Nino\Catalogue::PUBLIC_KEY` ausliefert, und was ein Projekt, das einen eigenen Katalog liest, unter `/nino/catalogue/key` in `config.php` einträgt. Ein leerer Schlüssel akzeptiert gar keinen Katalog. Ein neues Schlüsselpaar heißt ein neuer öffentlicher Schlüssel in Nino – einen mit dem neuen Schlüssel signierten Katalog weist jeder Kernel ab, der noch den alten trägt.

## Ein eigener Katalog

`bin/release.sh` funktioniert für jeden Satz Features – einen Fork dieses Repositories – mit deinem eigenen `NINO_CATALOGUE_URL` und einem Webserver, der sein `public/` ausliefert. `bin/build.php` allein baut dieselben Dateien in ein Verzeichnis:

```bash
php bin/build.php ../nino public --base-url https://example.org/features
openssl dgst -sha256 -sign catalogue-key.pem public/catalogue.json | base64 -w0 > public/catalogue.json.sig
```

Ohne `--key` schreibt es keine Signatur und gibt diesen Einzeiler aus; mit `--key catalogue-key.pem` signiert es selbst und prüft die Signatur mit der öffentlichen Hälfte, bevor es endet. Lade das Verzeichnis nach `https://example.org/features/` hoch und zeige ein Projekt dorthin: `/nino/catalogue/url` nennt die URL des Katalogs, `/nino/catalogue/key` seinen öffentlichen Schlüssel, beides in `config.php`.

## Wie ein Paket gebaut und geprüft wird

`bin/build.php` liest `features/<Name>/`, `themes/<key>/` und `blocks/<key>/` – die beiden letzten halten ein `.gitkeep`, solange es kein Theme und kein Block-Paket gibt – durch den Kernel eines Nino-Checkouts und bricht bei dem Ersten ab, was der Kernel ablehnen würde, mit dem Pfad der Datei und einem Satz, bevor es etwas schreibt:

- Jedes Paket wird nach den Regeln geprüft, nach denen der Kernel es installiert, mit derselben Funktion – `\Nino\Packages::check()` – über die Dateien seines Ordners, im Register der Komponenten aller Features dieses Repositorys, sodass jede Gruppe, die ein Teil nennt, als das gelesen wird, was sie ist. Ein Theme oder Block-Paket enthält, was seine Sorte aufzählt, und sonst nichts: eine `.php`, eine `.htaccess`, ein Skript oder eine versteckte Datei wird mit ihrem Pfad abgelehnt; die `part.css` eines Teils nach den Regeln eines Teils (kein `@import`, kein `url()`, kein `!important`, keine Klasse und kein Token einer anderen Gruppe); ein Block nach den Regeln eines Blocks (kein `[html]`, kein eigenes Markup, nur Komponenten);
- darüber hinaus: Ein SVG, dessen Neubau durch `\Nino\Images::cleanSvg()` ein Element oder ein Attribut verliert, bricht den Build ab – was der Kernel in ein Projekt schreibt, ist der Neubau, nie die Datei, wie sie kam, und der Autor soll sehen, was vom Bild übrig bleibt –, ebenso ein Teil einer Gruppe, die kein Feature dieses Repositorys mitbringt, und ein Theme oder Block-Paket ohne `preview.webp` oder mit einem Ordner, der nicht sein Key ist;
- was ein Eintrag über den Bedarf eines Pakets sagt, wird aus den Dateien gerechnet und nie hingeschrieben: Ein Theme braucht `themes`, das Feature jeder Gruppe seiner Teile und die Features, deren Komponenten seine Blocks aufrufen, und die Schriften, die seine Marke (`theme.json`, `brand`) vorschlägt, die Systemschriften ausgenommen; ein Block-Paket die Features, deren Komponenten seine Blocks aufrufen; ein Feature, was sein Manifest verlangt.

Jedes Archiv ist ein einfacher Tar, vom Skript geschrieben, deterministisch, und wird mit `\Nino\Archive` zurückgelesen – dem Leser, mit dem der Kernel installiert –, bevor ihm getraut wird: Es muss die Dateien enthalten, aus denen es gebaut wurde. Das fertige Dokument läuft durch `\Nino\Catalogue::parse()`, und ein Eintrag, den der Kernel nicht lesen kann, ist ein Fehler des Builds. Das Bild eines Themes oder Block-Pakets liegt neben seinem Archiv als `<kind>-<key>-<version>.webp`.

## Der Katalog, Format 2

```json
{ "format": 2, "generated": "2026-11-17T12:00:00Z", "applications": [ { "key": "website", "...": "..." } ], "packages": [ { "kind": "feature", "key": "newsletter", "...": "..." } ] }
```

Der Kernel liest Format 2 und sonst nichts; ein Eintrag einer `kind`, die er nicht kennt, wird übersprungen, und ein Eintrag, der nicht ist, was seine Sorte sagt, wird weggelassen und kostet nur sich selbst.

| Feld | Sorten | Was es sagt |
| --- | --- | --- |
| `kind` | alle | `feature`, `theme` oder `blocks` |
| `key` | alle | ein Slug, je Sorte eindeutig – `newsletter`, `craft`, `gastro` |
| `version` | alle | `major.minor.patch`: die des Manifests, von `theme.json`, von `pack.json` |
| `name`, `description` | alle | ein String oder eine Abbildung `locale => string` |
| `category`, `maturity` | feature | die des Manifests; `maturity` fehlt, wo es keine nennt |
| `directory` | feature | das eine Verzeichnis, das das Archiv enthält – `Newsletter`, der Klassenname |
| `requires` | alle | `{ "nino": "^2.0", "php": [ ... ], "features": [ ... ], "fonts": [ ... ] }`: der Kernel, gegen den das Paket geprüft wurde, die PHP-Erweiterungen eines Features, die Keys der Features, die es benötigt, die Schriften eines Themes |
| `parts` | theme | je Gruppe der Teile der Vertrag ihrer `part.json`: `{ "section": { "feature": "basis", "contract": "^1.0" }, "page": { "contract": "kernel@^2.0" } }` |
| `blocks` | theme, blocks | die Keys der Blocks eines Themes; bei einem Block-Paket Key und Kategorie jedes Blocks |
| `preview` | theme, blocks | `{ "url", "sha256", "size" }` der `preview.webp`, höchstens 256 KB |
| `archive` | alle | die https-URL des Archivs, `<kind>-<key>-<version>.tar.gz` – gepackt höchstens 8 MB bei einem Feature, 2 MB bei einem Theme oder Block-Paket |
| `sha256`, `size` | alle | die Hex-Prüfsumme des Archivs – wogegen der Kernel einen Download prüft – und seine Größe in Bytes |
| `released` | alle | der Tag, an dem dieses Archiv zum ersten Mal so veröffentlicht wurde, `YYYY-MM-DD` |

`applications` ist eine Liste nach Key, die Anwendung mit den meisten Features zuletzt, ein Eintrag je Datei von `applications/`, jeder so, wie die Datei ihn hat:

```json
{ "key": "website", "name": { "en_US": "Website", "de_DE": "Webseite" }, "description": { "en_US": "...", "de_DE": "..." }, "features": [ "builder", "design", "consent", "forms", "modeswitch", "seo" ], "next": "/_admin/#dashboard", "start": "starter" }
```

Ein Eintrag trägt `menu` nur bei `"folded"`; eine Anwendung ohne hat keinen Schlüssel dafür. Die Felder sind die Tabelle aus [Eine Anwendung schreiben](#eine-anwendung-schreiben).

`catalogue.json.sig` ist seine abgetrennte Signatur: ECDSA über SHA-256 der exakten Bytes, DER, base64 in einer Zeile. `generated` ist die Zeit der letzten Änderung, ISO 8601 UTC. Ein `catalogue.json` eines anderen Formats im Ausgabeverzeichnis wird nicht überbaut.

## Lizenz

[MIT](LICENSE) – derselbe Autor wie [Nino](https://github.com/dapeio/nino). Die Icons der
Feature-Panels stammen von [Lucide](https://lucide.dev) (ISC, die aus Feather übernommenen
zusätzlich MIT); ihre Lizenzhinweise stehen in [THIRD-PARTY-NOTICES.md](THIRD-PARTY-NOTICES.md).
