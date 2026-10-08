# Nino-Features

**Sprache:** [English](README.md) · Deutsch

Der Feature-Katalog von [Nino](https://github.com/dapeio/nino): die Features, die ein Nino-Projekt installieren kann, je eines pro Verzeichnis unter `features/`, mit eigener Version. Der Katalog selbst ist die Liste – [catalogue.getnino.dev](https://catalogue.getnino.dev) liefert ihn aus, und das Features-Panel eines Projekts liest ihn.

## Was dieser Katalog ist

Ein Nino-Checkout liefert kein Feature aus. Was ein Projekt über den Kernel hinaus braucht – einen Newsletter, eine Suche –, kommt als **Feature**: ein Verzeichnis mit einer Laufzeitklasse, bei Bedarf einem Panel der Workbench, einer Install-Einheit mit Templates und Texten und einem Manifest `feature.php`, das sagt, was es ist, für welche Nino-Version es geschrieben wurde und welche Einstellungen es anbietet. Was ein Feature liefern muss, steht im Vertrag [Features](https://github.com/dapeio/nino/blob/main/docs/features.md) in Nino; jedes Feature hier folgt ihm.

`features/<Name>/` ist genau das, was in einem Projekt landet. Das Repository macht nichts über das Netz: Es ist ein Dateibestand, den man klont oder kopiert, und die Quelle dessen, was getnino.dev als signierte Archive ausliefert. Was `main` trägt, listet der Katalog, siehe [Ein Release](#ein-release).

Ein Feature ist in seinem Manifest dokumentiert: Das `manual` in `feature.php` zeigt das Features-Panel auf dem Tab Beschreibung, und der `hint` einer Einstellung steht neben dem Feld im Formular. Weitere Dokumentation gibt es nicht zu pflegen. Die Geschichte eines Features ist die Commit-Historie.

## Ein Feature installieren

Ein Feature wird nicht installiert, sondern hingelegt und eingeschaltet:

1. Kopiere `features/<Name>/` aus diesem Repository in das `features/` deines Projekts – als Ganzes, mit demselben Verzeichnisnamen. Der Name ist der Klassenname: `features/Newsletter/Newsletter.php` ist `\Nino\Modules\Newsletter`. Ein Projekt, das den Katalog liest, installiert stattdessen im Features-Panel.
2. Melde dich an `/_admin` an und öffne **Features** in der Gruppe System. Das Panel verlangt die Entwicklerberechtigung `/_admin/features/manage`. Es listet jedes Verzeichnis mit gültigem Manifest, eingeschaltet oder nicht, mit Version und mit dem, was einer Aktivierung entgegensteht – eine Nino-Version, für die das Feature nicht geschrieben wurde, eine fehlende PHP-Erweiterung, ein benötigtes Feature, das nicht da ist.
3. **Aktivieren.** Das wendet die Install-Einheit des Features an, ohne etwas zu überschreiben, das dein Projekt bereits hat, trägt die Klasse in `/nino/modules` ein und zeichnet die Version unter `/nino/features` in der `config.php` auf.

Ein Panel, das ein Feature mitbringt, erscheint mit dem nächsten Laden der Workbench, in der Gruppe Features; der Tab Nutzerrollen des Panels Nutzer bietet seine Berechtigung dort an, und die Rolle **Editor** erhält sie nicht von selbst.

**Update:** Ersetze `features/<Name>/` durch die neue Fassung und drücke **Update** im Panel Features. Das Panel bietet es an, sobald das Manifest eine andere Version nennt als die aufgezeichnete; die Einheit ergänzt, was neu ist, und lässt alles, was das Projekt bearbeitet hat, wie es ist. **Deaktivieren** trägt die Klasse aus `/nino/modules` aus – und sonst nichts: Einstellungen, Daten, kopierte Templates und Texte bleiben.

**Sicherheit:** Alles unter `features/` ist serverseitiger Quelltext. Der Nino-Checkout liefert `features/.htaccess` mit, das den Baum sperrt; ein Webserver, der `.htaccess` nicht liest, braucht die entsprechende Regel, siehe [Deployment](https://github.com/dapeio/nino/blob/main/docs/deployment.md) in Nino.

## Entwickeln und testen

Die Tests eines Features laufen gegen einen Nino-Checkout. Klone Nino neben dieses Repository – `bin/check.sh` erwartet ihn unter `../nino` oder dort, wohin `NINO_ROOT` zeigt:

```bash
git clone https://github.com/dapeio/nino.git ../nino
bin/check.sh
NINO_ROOT=/path/to/nino bin/check.sh
```

`bin/check.sh` kopiert jedes Feature, das der Checkout ausführen kann, in dessen `features/` – dieselbe Anordnung, die ein Projekt hat –, prüft jedes Manifest über `bin/catalogue.php`, führt die Tests dieser Features aus und entfernt die Kopien danach wieder; danach führt es jeden eigenen Test dieses Repositories unter `tests/` aus – die Grammatik der Textschlüssel, die Abschnitte der Datenschutzerklärung, die Regeln, die für jedes Feature gelten, die Demoseite des Kernels, die jede Klasse von `Nino.css` zeichnet, das Build-Werkzeug und das Release-Skript. Die Zeilen des Skripts selbst sind die Liste. Ein Feature, das für ein neueres Nino geschrieben ist als der Checkout, steht mit seinem Grund in der Liste und bleibt draußen (`bin/applicable.php` entscheidet das).

Ein einzelner Test läuft auch direkt:

```bash
NINO_ROOT=../nino php features/Search/tests/search-smoke.php
```

`bin/catalogue.php` gibt aus, was der Katalog listen würde, als JSON, ohne Archiv und ohne Signatur. CI (`.github/workflows/ci.yml`) führt dieselben Tests gegen Ninos `main` und gegen sein jüngstes Tag aus, dazu eine Syntaxprüfung, Ninos eigenen Vertragstest sowie PHPStan und ESLint über `features/`. Nichts wartet darauf: Es ist eine Bequemlichkeit.

## Ein Feature schreiben

Das [Feature-Rezept](https://github.com/dapeio/nino/blob/main/docs/recipes/feature.md) in Nino baut ein Feature Schritt für Schritt; das Handbuch [Features](https://github.com/dapeio/nino/blob/main/docs/features.md) ist der Vertrag dahinter. `features/Hello/` ist ein vollständiges Beispiel, das zum Kopieren geschrieben ist: Kopiere es und benenne dann das Verzeichnis, den `key` in `feature.php` und die Klasse `\Nino\Modules\Hello` um, sodass die drei übereinstimmen.

```text
features/<Name>/
├── feature.php              das Manifest: key, name, description, manual, category, version, nino, requires, settings, data
├── <Name>.php               die Laufzeitklasse \Nino\Modules\<Name>
├── Admin/Admin.php          das Panel, wenn es eines gibt
├── assets/ install/ text/ templates/   wenn das Feature sie braucht
└── tests/<key>-smoke.php    ein Test, wenn du einen willst
```

Verlangt sind `feature.php` und `<Name>.php`. Das `manual` des Manifests ist die Dokumentation: eine Map `section => handle => zeile` – Shortcodes, Markup, Routen, Panel, Callbacks, Install – mit dem Handle so, wie man ihn eintippt, und daneben einer Zeile auf Deutsch und Englisch. Ein Test ist willkommen und nie verlangt. Die Regeln, die bleiben, weil Tests sie prüfen, stehen in [AGENTS.md](AGENTS.md): kein Markup in PHP, Englisch im Code, Escaping, die Grammatik der Textschlüssel und ein Abschnitt der Datenschutzerklärung für ein Feature, das personenbezogene Daten verarbeitet.

`category` ist eine von `content`, `ui`, `communication`, `marketing`, `security` oder `system`, und kein Name kommt in irgendeiner Sprache bei zwei Features vor; `bin/build.php` weist beides ab.

## Ein Release

Ein Release ist `bin/release.sh`, vom Owner im Checkout auf dem Server ausgeführt. Der Katalog ist, was `main` trägt: Jeder Lauf baut jedes Feature nach `public/`, das Verzeichnis, das der Webserver ausliefert, und ein Feature, das sich nicht geändert hat, ergibt dieselben Bytes, sodass sich nichts bewegt, was ein Projekt sieht.

```bash
git pull
NINO_CATALOGUE_KEY=/sicherer/ort/catalogue-key.pem bin/release.sh
```

Es führt zuerst `bin/check.sh` aus (`--quick` überspringt das), dann baut `bin/build.php` jedes Archiv nach `public/`, schreibt `catalogue.json` und signiert es; jede Datei landet ganz, per Umbenennen. `NINO_ROOT` nennt den Nino-Checkout (Standard `../nino`), `NINO_CATALOGUE_URL`, wo `public/` ausgeliefert wird (Standard `https://catalogue.getnino.dev`). Eine Probe ist der Build in ein anderes Verzeichnis: `php bin/build.php ../nino /tmp/probe`.

Es gibt einen Eintrag je Feature, die Version seines Manifests, und kein Tag. Eine Änderung ohne neue `version` erreicht neue Installationen, bestehende nicht: Das Features-Panel bietet ein Update aus dem Katalog nur für eine höhere Version an. Die `version` wird also erhöht, wenn installierte Projekte das Update angeboten bekommen sollen.

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

## Der Katalog, Format 1

```json
{ "format": 1, "generated": "2026-09-07T12:00:00Z", "features": [ { "key": "newsletter", "...": "..." } ] }
```

| Feld | Was es sagt |
| --- | --- |
| `key` | der Feature-Key, ein Slug – `newsletter` |
| `name`, `description` | wie das Manifest sie hat: ein String oder eine Abbildung `locale => string` |
| `category` | wofür das Feature da ist, ein Slug – das des Manifests |
| `maturity` | das Abzeichen, das das Features-Panel neben den Namen zeichnet – das des Manifests; fehlt, wo es keines nennt |
| `version` | `major.minor.patch`, die des Manifests |
| `nino` | die Nino-Versionsbedingung, `^1.3` |
| `php` | `{ "ext": [ ... ] }` – die PHP-Erweiterungen, die das Feature braucht |
| `requires` | die Keys der Features, die es benötigt |
| `directory` | das eine Verzeichnis, das das Archiv enthält – `Newsletter`, der Klassenname |
| `archive` | die https-URL des Archivs, `<key>-<version>.tar.gz`, gepackt höchstens 20 MB |
| `sha256` | die Hex-Prüfsumme des Archivs – wogegen der Kernel einen Download prüft |
| `size` | seine Größe in Bytes |
| `released` | der Tag, an dem dieses Archiv zum ersten Mal so veröffentlicht wurde, `YYYY-MM-DD` |

`catalogue.json.sig` ist seine abgetrennte Signatur: ECDSA über SHA-256 der exakten Bytes, DER, base64 in einer Zeile. `generated` ist die Zeit der letzten Änderung, ISO 8601 UTC. Was `\Nino\Catalogue::parse()` abweist – ein fehlendes Feld, eine URL, die nicht https ist, ein Archiv über 20 MB –, weist den ganzen Katalog ab; darum lässt `bin/build.php` das, was es gleich schreibt, zuerst durch `parse()` laufen.

## Lizenz

[MIT](LICENSE) – derselbe Autor wie [Nino](https://github.com/dapeio/nino). Die Icons der
Feature-Panels stammen von [Lucide](https://lucide.dev) (ISC, die aus Feather übernommenen
zusätzlich MIT); ihre Lizenzhinweise stehen in [THIRD-PARTY-NOTICES.md](THIRD-PARTY-NOTICES.md).
