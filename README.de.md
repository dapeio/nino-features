# Nino-Features

**Sprache:** [English](README.md) · Deutsch

Der Feature-Katalog von [Nino](https://github.com/dapeio/nino): die Features, die ein Nino-Projekt installieren kann, je eines pro Verzeichnis unter `features/`, mit eigener Version.

## Was dieser Katalog ist

Ein Nino-Checkout liefert kein Feature aus. Was ein Projekt über den Kernel hinaus braucht – einen Newsletter, eine Suche –, kommt als **Feature**: ein Verzeichnis mit einer Laufzeitklasse, bei Bedarf einem Panel der Workbench, einer Install-Einheit mit Templates und Texten und einem Manifest `feature.php`, das sagt, was es ist, für welche Nino-Version es geschrieben wurde und welche Einstellungen es anbietet. Was ein Feature liefern muss, steht im Vertrag [Features](https://github.com/dapeio/nino/blob/main/docs/features.md) in Nino; jedes Feature hier folgt ihm.

Dieses Repository ist der Ort, von dem aus Ninos Features veröffentlicht werden. `features/<Name>/` ist genau das, was in einem Projekt landet – ein Projekt kopiert das Verzeichnis in sein eigenes `features/` und schaltet das Feature im Panel **Features** der Workbench ein. Das Repository selbst macht nichts über das Netz: Es ist ein Dateibestand, den man klont oder kopiert – und die Quelle dessen, was getnino.dev als signierte Archive ausliefert, siehe [Veröffentlichen](#veröffentlichen).

`Newsletter` und `Search` haben bis Nino 1.0.0-beta im Nino-Repository selbst gelegen, unter `app/Nino/Modules/`. Hier liegen sie unverändert und tragen von jetzt an ihre eigene Version.

## Die Features

| Key | Name | Kategorie | Version | Nino | Was es tut |
| --- | --- | --- | --- | --- | --- |
| `consent` | [Consent](features/Consent/README.md) | `security` | 1.0.0 | `^1.1` | Ein Cookie-/Consent-Banner mit Kategorien und einwilligungsabhängigen Skripten, ohne Drittanbieter |
| `forms` | [Forms](features/Forms/README.md) | `communication` | 1.0.0 | `^1.1` | Ein Baukasten für Ninos eigenen Formular-Endpunkt: beliebig viele Formulare, ein Shortcode `[form]`, der sie zeichnet, und Spam-Schutz ohne Captcha |
| `gallery` | [Gallery](features/Gallery/README.md) | `content` | 1.0.0 | `^1.1` | Beliebig viele Bildergalerien, je ein Raster aus Vorschaubildern, die sich bildschirmfüllend öffnen – zwei abgeleitete Größen je Upload, das Original wird nie gespeichert |
| `lightbox` | [Lightbox](features/Lightbox/README.md) | `ui` | 1.0.0 | `^1.1` | Öffnet jeden Link auf ein Bild bildschirmfüllend, mit seiner Gruppe als Serie – Pfeile, Wischen, Bildunterschriften und ein Fokusrahmen, ohne Bibliothek |
| `mailer` | [Mailer](features/Mailer/README.md) | `system` | 1.0.0 | `^1.1` | Versendet jede von Nino verschickte Mail über SMTP statt über das `mail()` des Servers |
| `newsletter` | [Newsletter](features/Newsletter/README.md) | `communication` | 1.0.0 | `^1.0` | Double-Opt-in-Anmeldung mit Bestätigungs- und Abmeldelink, und die Abonnentenliste als Panel der Workbench |
| `protected` | [Protected area](features/ProtectedArea/README.md) | `security` | 1.0.0 | `^1.1` | Legt eine oder mehrere Seiten hinter ein gemeinsames Passwort, ohne Konten |
| `search` | [Elements search](features/Search/README.md) | `content` | 1.0.0 | `^1.0` | Ein sprachbewusster unscharfer Suchindex über konfigurierte Element-Felder, neu gebaut bei jedem Speichern und aus dem Such-Panel |
| `seo` | [SEO](features/Seo/README.md) | `marketing` | 1.0.0 | `^1.1` | Sitemap, robots.txt und llms.txt, erzeugt aus Routen, Sprachen und Texten, die Nino ohnehin hat |
| `stats` | [Stats](features/Stats/README.md) | `marketing` | 1.0.0 | `^1.1` | Seitenaufruf-Zählung für die Workbench, ohne Cookies, IP-Adressen oder irgendetwas pro Besucher |
| `templates` | [Template Builder](features/Templates/README.md) | `content` | 1.0.0 | `^1.2` | Baut die Seitentemplates des Projekts aus ganzen Abschnitten – eine Bibliothek aus siebzehn, eine Live-Vorschau, und dazwischen bleibt der Quelltext der Seite Deiner |
| `typewriter` | [Typewriter](features/Typewriter/README.md) | `ui` | 1.0.0 | `^1.1` | Tippt die Zeilen eines Containers nacheinander, mit Cursor am Schreibkopf, je Element getaktet |

**Kategorie** ist das, wonach das Features-Panel gruppiert und filtert, eine je Feature: `content`, `ui`, `communication`, `marketing`, `security` oder `system` – das Vokabular, das Nino als `\Nino\Features::CATEGORIES` veröffentlicht und das [Features](https://github.com/dapeio/nino/blob/main/docs/features.de.md#kategorien) erklärt, samt der Regel für den Fall, dass zwei davon passen. Nino selbst nimmt jeden Slug an, damit ein älterer Kernel einen Katalog lesen kann, der ein Feature unter einer Kategorie führt, die er nicht kennt; `bin/build.php` ist die Stelle, die ein veröffentlichtes Feature auf die sechs festlegt – ein Tippfehler wird also hier gefangen und nicht als eigene Überschrift in jemandes Panel angezeigt.

**Name** ist das, was eine Zeile im Features-Panel sagt – der Key steht dort nie, das Verzeichnis auch nicht –, deshalb dürfen zwei Features in keiner Sprache denselben Namen tragen, und `bin/build.php` weist ein solches Paar ab. Groß- und Kleinschreibung zählt dabei nicht, denn „Seo" und „SEO" sind so schwer auseinanderzuhalten wie zwei gleichlautende Zeilen; ein Name als einfacher String ist der Name in jeder Sprache und kollidiert entsprechend.

Die README eines Features, wenn es eine hat, beschreibt seine Routen, sein Panel, seine Install-Einheit, seine Daten und seine Tests; sein `CHANGELOG.md`, wenn es eines hat, die Änderungen zwischen den Versionen. `bin/catalogue.php` liest dieselben Manifeste und gibt diese Tabelle als JSON aus, `bin/build.php` baut die Archive und das signierte `catalogue.json`, das getnino.dev veröffentlicht – siehe [Entwickeln und testen](#entwickeln-und-testen) und [Veröffentlichen](#veröffentlichen).

## Die Design-Bibliothek

Neben `features/` liegt [`design-library/`](design-library/README.de.md) – **kein** Feature und nie veröffentlicht. Dort liegen die zehn Themes, sechs Header und sieben Footer, die Ninos Setup-Assistent bis 1.1 angeboten hat, geparkt für das Feature **Design**, das noch nicht geschrieben ist. `bin/build.php` und `bin/check.sh` lesen ausschließlich `features/`, dort wird also nichts in ein Archiv gepackt, in `catalogue.json` gelistet oder als Test ausgeführt.

## Ein Feature installieren

Ein Feature wird nicht installiert, sondern hingelegt und eingeschaltet:

1. Kopiere `features/<Name>/` aus diesem Repository in das `features/` deines Projekts – als Ganzes, mit demselben Verzeichnisnamen. Der Name ist der Klassenname: `features/Newsletter/Newsletter.php` ist `\Nino\Modules\Newsletter`.
2. Melde dich an `/_admin` an und öffne **Features** in der Gruppe System. Das Panel verlangt die Entwicklerberechtigung `/_admin/features/manage`. Es listet jedes Verzeichnis mit gültigem Manifest, eingeschaltet oder nicht, mit Version und mit dem, was einer Aktivierung entgegensteht – eine Nino-Version, für die das Feature nicht geschrieben wurde, eine fehlende PHP-Erweiterung, ein benötigtes Feature, das nicht da ist.
3. **Aktivieren.** Das wendet die Install-Einheit des Features an, ohne etwas zu überschreiben, das dein Projekt bereits hat – ein vorhandenes Template, ein vorhandener Textschlüssel, eine vorhandene Route bleiben –, trägt die Klasse in `/nino/modules` ein und zeichnet die Version unter `/nino/features` in der `config.php` auf.

Ein Panel, das ein Feature mitbringt, erscheint mit dem nächsten Laden der Workbench – lade die Seite neu. Ein Panel in der Gruppe Inhalt, wie das des Newsletters, bietet seine Berechtigung auf dem Tab Nutzerrollen des Panels Nutzer an; die Rolle **Editor** erhält sie nicht von selbst.

**Update:** Ersetze `features/<Name>/` durch die neue Fassung und drücke **Update** im Panel Features. Das Panel bietet den Knopf an, sobald das Manifest eine andere Version nennt als die aufgezeichnete. Die Aktualisierung ist dieselbe Aktion wie das Aktivieren: Die Einheit ergänzt, was neu ist, und lässt alles, was das Projekt seit der ersten Aktivierung bearbeitet hat, wie es ist; ein Feature, das seine eigenen Daten migrieren muss, tut das in `upgrade()`, bevor die neue Version aufgezeichnet wird.

**Deaktivieren** trägt die Klasse aus `/nino/modules` aus – und sonst nichts. Einstellungen, Daten, kopierte Templates und Texte bleiben; ein erneutes Einschalten findet alles vor. Eine Deinstallation gibt es bewusst nicht: Was ein Feature hinterlässt, entfernt ein Entwickler wissentlich und von Hand.

**Sicherheit:** Alles unter `features/` ist serverseitiger Quelltext. Der Nino-Checkout liefert `features/.htaccess` mit, das den Baum sperrt; ein Webserver, der `.htaccess` nicht liest, braucht die entsprechende Regel, siehe [Deployment](https://github.com/dapeio/nino/blob/main/docs/deployment.md) in Nino.

## Entwickeln und testen

Die Tests eines Features laufen gegen einen Nino-Checkout. Klone Nino neben dieses Repository – `bin/check.sh` erwartet ihn unter `../nino` oder dort, wohin `NINO_ROOT` zeigt:

```bash
git clone https://github.com/dapeio/nino.git ../nino
bin/check.sh
NINO_ROOT=/path/to/nino bin/check.sh
```

`bin/check.sh` kopiert jedes Feature in das `features/` des Checkouts – dieselbe Anordnung, die ein Projekt hat –, prüft jedes Manifest über `bin/catalogue.php`, führt die Tests jedes Features aus und entfernt die Kopien danach wieder; dann führt es `tests/build-smoke.php` aus, den eigenen Test des Veröffentlichungswerkzeugs, der einen signierten Katalog in ein eigenes Verzeichnis baut. Ein Verzeichnis, das im Checkout schon liegt, rührt es nicht an und sagt das.

Ein einzelner Test läuft auch direkt. Er lädt `tests/harness.php` aus dem Checkout drei Ebenen über sich – so liegt er in einem Projekt – oder aus dem, den `NINO_ROOT` nennt, und definiert `NINO_FEATURES_DIR` auf sein eigenes Elternverzeichnis, sodass der Kernel die Klasse von dort bedient, wo das Feature gerade liegt:

```bash
NINO_ROOT=../nino php features/Search/tests/search-smoke.php
```

`bin/catalogue.php` ist die Vorschau: Es prüft jedes Manifest durch den Kernel des Checkouts und gibt aus, was der Katalog listen würde, als JSON – Key, Name, Beschreibung, Version, die Nino-Bedingung, PHP-Erweiterungen, benötigte Features und das Verzeichnis –, ohne Archiv und ohne Signatur. Ein Manifest, das Nino überspringen würde, lässt den Lauf fehlschlagen: Der Katalog listet nie weniger, als im Verzeichnis liegt. `bin/build.php` baut das Eigentliche, siehe [Veröffentlichen](#veröffentlichen).

```bash
php bin/catalogue.php ../nino > catalogue.json
```

CI (`.github/workflows/ci.yml`) tut dasselbe gegen Ninos `main` und gegen sein jüngstes Tag: Syntaxprüfung aller PHP- und JavaScript-Dateien, Kopie der Features in den Checkout, Validierung der Manifeste, die Tests jedes Features, `tests/build-smoke.php`, Ninos eigener Vertragstest `tests/features-smoke.php` mit den Features an Ort und Stelle, PHPStan und ESLint über `features/` aus dem Checkout heraus. Das `catalogue.json` des `main`-Laufs bleibt als Artefakt erhalten. Lokal laufen PHPStan und ESLint genauso: Features in den Checkout kopieren, dort `phpstan analyse` und `npx eslint features` ausführen.

## Ein Feature schreiben

Das [Feature-Rezept](https://github.com/dapeio/nino/blob/main/docs/recipes/feature.md) in Nino baut ein Feature Schritt für Schritt bis zum bestandenen Test; das Handbuch [Features](https://github.com/dapeio/nino/blob/main/docs/features.md) ist der Vertrag dahinter – das Manifest, das Settings-Schema, der Lebenszyklus. Jedes Verzeichnis in diesem Katalog folgt derselben Anordnung:

```text
features/<Name>/
├── feature.php              das Manifest: key, name, description, manual, category, version, nino, requires, settings, data
├── <Name>.php               die Laufzeitklasse \Nino\Modules\<Name>
├── Admin/Admin.php          das Panel \Nino\Modules\<Name>\Admin, wenn es eines gibt
├── assets/                  Skript und Stylesheet des Panels
├── install/                 die Einheit, die das Aktivieren add-only anwendet: manifest.php, templates/, text/
├── text/<locale>.php        die Fills des Panels, solange das Feature aktiv ist
├── tests/<key>-smoke.php    der eigene Test des Features – wenn es einen gibt
├── README.md                was es tut, seine Routen, sein Panel, seine Einheit, seine Daten – wenn es eine gibt
└── CHANGELOG.md             die Änderungen je Version – wenn es eines gibt
```

`manual` ist die kurze Anleitung, mit der das Features-Panel den Bildschirm eines Features aufmacht – wo der Shortcode hingehört, was ein Attribut tut, in höchstens zwei Absätzen; schreib sie als Nowdoc, dann liest sie sich im Manifest wie im Panel, und alles Weitere gehört ins `README.md`, das für jemanden geschrieben ist, der den Quelltext liest. Nur `feature.php` und `<Name>.php` verlangt der Kernel. Alles Weitere ist da, wenn das Feature es braucht – `Search` etwa hat keine Install-Einheit. Ein `README.md`, ein `CHANGELOG.md` und ein Test unter `tests/` sind willkommen, nicht verlangt: Bei ihrer heutigen Größe liest man die Features in einer Minute, und ein Beitrag soll nicht mit drei Dateien Papierkram beginnen. Das Release-Werkzeug verlangt sie nur im Strict-Modus – `bin/release.sh --strict`, oder die Repository-Variable `RELEASE_STRICT` auf `1` für den Workflow – den dieser Katalog später einschalten kann. Die Regeln für Agenten, die hier arbeiten, stehen in [AGENTS.md](AGENTS.md).

## Versionen und Releases

Jedes Feature trägt seine eigene `version` in `feature.php` – `major.minor.patch`. Ein Release ist ein Git-Tag `<key>-<version>`, etwa `newsletter-1.0.0`; die Features eines Repositories werden unabhängig voneinander versioniert, und ein Tag benennt genau eines. Das Pushen des Tags veröffentlicht diese Version auf getnino.dev, siehe [Veröffentlichen](#veröffentlichen). Ein Projekt sieht die Version im Panel Features und bekommt ein Update angeboten, sobald das Verzeichnis eine neuere trägt.

`nino` im Manifest nennt die Nino-Versionen, für die das Feature geschrieben ist – heute `^1.0` für beide, was jede 1.x einschließt; ein Vorab-Kernel wie `1.0.0-beta` zählt als das Release, dem er vorausgeht. Die Bedingung ist eine Absicht, keine Garantie: Verträglichkeit wird getestet, nicht erklärt. Die CI dieses Repositories führt jedes Feature gegen Ninos `main` und gegen sein jüngstes Tag aus, und Ninos eigene CI klont diesen Katalog, kopiert die Features in ihren Checkout und führt deren Tests dort aus – eine Kernel-Änderung, die ein Feature bricht, schlägt auf beiden Seiten fehl.

## Veröffentlichen

catalogue.getnino.dev veröffentlicht diesen Katalog als signierte Archive, und ein Nino, das `\Nino\Catalogue` trägt, liest ihn von dort: Das Panel Features lädt den Katalog auf Anfrage – nie von selbst –, bietet an, was zum laufenden Kernel passt, und installiert ein Archiv direkt nach `features/`, nachdem es dessen Prüfsumme gegen den signierten Katalog geprüft hat. Alles Veröffentlichte ist eine statische Datei über https:

| Pfad | Was es ist |
| --- | --- |
| `https://catalogue.getnino.dev/catalogue.json` | der Katalog, Format 1 – siehe [Der Katalog, Format 1](#der-katalog-format-1) |
| `https://catalogue.getnino.dev/catalogue.json.sig` | seine abgetrennte Signatur: ECDSA über SHA-256 der exakten Bytes von `catalogue.json`, DER, base64 in einer Zeile |
| `https://catalogue.getnino.dev/<key>-<version>.tar.gz` | ein Archiv je Feature-Version, mit genau einem Verzeichnis `<Name>/` – was unter dem `features/` eines Projekts landet, ohne `tests/`; höchstens 20 MB gepackt, 50 MB entpackt, 5000 Einträge |

Eine veröffentlichte Version ist unveränderlich: Ein Archiv, das auf dem Server liegt, wird nie neu gebaut oder überschrieben, und sein Eintrag behält Prüfsumme, Größe und Veröffentlichungsdatum. Was sich ändern muss, erscheint als nächste Version.

### Ein Release

1. Erhöhe `version` in `features/<Name>/feature.php`; wo das Feature ein `CHANGELOG.md` hat, schreibe den Eintrag `## <version> — <datum>`, und bringe sein `README.md` auf den Stand, wo sich Verhalten geändert hat.
2. Führe `bin/check.sh` aus – jedes Manifest, die Tests jedes Features, der eigene Test des Veröffentlichungswerkzeugs.
3. Mit der Änderung auf `main`: Tagge den Commit `<key>-<version>` und pushe das Tag:

```bash
git tag newsletter-1.0.1
git push origin newsletter-1.0.1
```

Das Tag startet `.github/workflows/release.yml`, das

- das Tag auscheckt und Ninos `main` daneben klont, als `../nino`;
- Key und Version aus dem Tag liest und bei einem fehlschlägt, das nicht `<key>-<major>.<minor>.<patch>` ist;
- das Feature findet, dessen Manifest diesen Key trägt, prüft, dass sein `feature.php` genau diese Version nennt – und, wo es ein `CHANGELOG.md` hat, dass es den Eintrag trägt –, und die eigenen Tests des Features gegen den Checkout ausführt, wo es welche trägt (die Repository-Variable `RELEASE_STRICT` auf `1` macht Changelog-Eintrag, README und Test verpflichtend);
- das veröffentlichte `catalogue.json` – und, bei einem erneuten Lauf, das veröffentlichte Archiv dieser Version – nach `dist/` holt; ein 404 ist das erste Release;
- den Signaturschlüssel aus dem Secret in eine temporäre Datei schreibt, `php bin/build.php ../nino dist --only <key> --key <datei>` ausführt und die Schlüsseldatei wieder entfernt, was auch immer geschehen ist;
- `dist/` als Workflow-Artefakt behält;
- `catalogue.json`, `catalogue.json.sig` und das Archiv dieser Version über https an `server/publish.php` postet, ein `curl` (`PUBLISH_URL`, `PUBLISH_TOKEN`); der Endpunkt prüft die Signatur selbst und überschreibt nie ein veröffentlichtes Archiv. Kein ssh.

Ein Release, das auf halbem Weg stehen geblieben ist – ein fehlgeschlagener Test, ein fehlgeschlagener Upload –, wird unter **Actions → Release → Run workflow** mit Key und Version erneut gestartet: Der Workflow checkt das Tag erneut aus, und ein Archiv, das schon auf dem Server liegt, bleibt, was es ist. Auf ein Release, das mit einem Fehler hinausging, folgt die nächste Patch-Version; ersetzt wird es nie.

### Ein Release ohne GitHub

`bin/release.sh` geht dieselben Schritte auf deinem eigenen Rechner – GitHub hat immer nur die Automatik hinter dem Tag geliefert. Mit dem Nino-Checkout neben dem Repository, dem privaten Schlüssel und dem Token des Endpunkts zur Hand:

```bash
CATALOGUE_KEY=/sicherer/ort/catalogue-key.pem PUBLISH_TOKEN=... bin/release.sh newsletter
```

Es findet das Feature über seinen Key, prüft die Version in `feature.php` (und den Eintrag im `CHANGELOG.md`, wo es eines gibt), führt die Tests des Features, wo es welche hat, gegen `NINO_ROOT` aus (Standard `../nino`) – zusammen mit allem, was sein Manifest unter `requires` nennt, und allem, was jene nennen, im Checkout danebengelegt und danach wieder entfernt, denn ein Feature, das sich nicht aktivieren lässt, lässt sich auch nicht testen –, holt das veröffentlichte `catalogue.json` und das Archiv dieser Version nach `dist/` (ein 404 ist das erste Release, ein leeres 200 gilt als nicht veröffentlicht), baut und signiert mit `bin/build.php --only <key>` und postet die drei Dateien an `PUBLISH_URL` (Standard `$CATALOGUE_URL/publish.php`, `CATALOGUE_URL` standardmäßig `https://catalogue.getnino.dev`). `--dry-run` hält vor dem Posten an und lässt `dist/` zum Ansehen liegen; `--offline` überspringt das Holen und führt in das zusammen, was `dist/` schon enthält. `--strict` (oder `RELEASE_STRICT=1`) verlangt Changelog-Eintrag, README und Test so, wie es der Workflow mit gesetzter Variable tut. Tagge den Commit danach trotzdem, damit das Repository festhält, was hinausging – ein erneuter Lauf des Workflows findet das Archiv veröffentlicht und behält es. `tests/release-smoke.php` treibt das Skript gegen `server/publish.php` auf phps eingebautem Server.

### Die Secrets

Das Repository braucht diese Secrets (**Settings → Secrets and variables → Actions**):

| Secret | Was es enthält |
| --- | --- |
| `CATALOGUE_SIGNING_KEY` | der private PEM-Schlüssel, mit dem `catalogue.json` signiert wird – die ganze Datei, `-----BEGIN EC PRIVATE KEY-----` eingeschlossen |
| `PUBLISH_URL` | die URL des Endpunkts, `https://catalogue.getnino.dev/publish.php` |
| `PUBLISH_TOKEN` | das Token, mit dem `server/publish.php` konfiguriert ist (`NINO_CATALOGUE_TOKEN`) |

Ein Fork, der einen eigenen Katalog veröffentlicht, setzt die Repository-*Variable* `CATALOGUE_URL` (dieselbe Seite, **Variables**) auf die Adresse, unter der die Dateien ausgeliefert werden; ohne sie nennt der Workflow `https://catalogue.getnino.dev`.

### Der Signaturschlüssel

Das Schlüsselpaar entsteht einmal, offline, und die private Hälfte gelangt nie in ein Repository (`.gitignore` weist `*.pem` ab):

```bash
openssl ecparam -name prime256v1 -genkey -noout -out catalogue-key.pem
openssl ec -in catalogue-key.pem -pubout -out catalogue-key.pub.pem
```

`catalogue-key.pem` kommt in das Secret `CATALOGUE_SIGNING_KEY` und an einen sicheren Ort. `catalogue-key.pub.pem` ist öffentlich: Es ist, was Nino als `\Nino\Catalogue::PUBLIC_KEY` ausliefert, und was ein Projekt, das einen eigenen Katalog liest, unter `/nino/catalogue/key` in `config.php` einträgt. Ein leerer Schlüssel akzeptiert gar keinen Katalog. Ein neues Schlüsselpaar heißt ein neuer öffentlicher Schlüssel in Nino – einen mit dem neuen Schlüssel signierten Katalog weist jeder Kernel ab, der noch den alten trägt.

### Der Server

catalogue.getnino.dev liefert ein Verzeichnis als schlichte statische Dateien über https aus – `catalogue.json`, `catalogue.json.sig` und die Archive, keine Verzeichnisliste; der Kernel liest die Bytes, welchen Content-Type der Webserver auch nennt – und im selben Verzeichnis `server/publish.php`: den Endpunkt, an den der Release-Workflow postet. Kein ssh. Der Workflow schickt einen https-POST mit dem signierten Katalog, seiner Signatur und dem neuen Archiv, und der Endpunkt nimmt ihn nur an, wenn alles hält: Das Token stimmt, die Signatur prüft mit dem öffentlichen Schlüssel, den der Endpunkt hält, jedes hochgeladene Archiv ist eines, das der Katalog mit dieser Prüfsumme und Größe nennt, jedes Archiv, das der Katalog nennt, ist schon veröffentlicht oder in diesem Upload, und kein veröffentlichtes Archiv würde sich ändern – andere Bytes unter einem veröffentlichten Namen sind ein 409. Ein geleaktes Token allein veröffentlicht nichts: Ohne den privaten Schlüssel gibt es keinen Katalog, den der Endpunkt annimmt.

Ausrollen heißt, `server/publish.php` in dieses Verzeichnis zu kopieren und drei Dinge zu konfigurieren, als Umgebungsvariablen (ein Container) oder als `publish.config.php` neben dem Skript, das ein Array mit denselben Schlüsseln zurückgibt (ein gewöhnlicher Webserver; `.gitignore` hält die Datei aus dem Repository):

| Einstellung | Was sie enthält |
| --- | --- |
| `NINO_CATALOGUE_TOKEN` | das Token, das der Workflow schickt, mindestens 32 Zeichen – `openssl rand -hex 32`; dieselbe Zeichenkette ist das Secret `PUBLISH_TOKEN` |
| `NINO_CATALOGUE_PUBLIC_KEY` | der öffentliche PEM-Schlüssel, `catalogue-key.pub.pem` – oder `NINO_CATALOGUE_PUBLIC_KEY_FILE`, der Pfad einer Datei damit |
| `NINO_CATALOGUE_DIR` | das Verzeichnis, in das geschrieben wird; das des Skripts, wenn nicht gesetzt |

php muss den Upload zulassen – `upload_max_filesize` und `post_max_size` über dem größten Archiv, `32M` und `64M` lassen Luft –, und ein Proxy vor php braucht seine eigene Grenze (`client_max_body_size 64m` bei nginx). Das Verzeichnis ist für den php-Nutzer beschreibbar; ein veröffentlichtes Archiv darf danach schreibgeschützt werden, der Endpunkt schreibt keines zweimal. Eine Datei, die nicht da ist, muss mit **404** antworten – ein Front-Controller, der für jeden Pfad 200 mit leerem Body liefert, lässt den Workflow ein leeres Archiv für ein veröffentlichtes halten (`bin/build.php` entfernt eine Datei mit null Bytes und baut neu, und der Workflow behandelt ein leeres 200 als „nicht veröffentlicht“, aber der Server sollte von vornherein richtig antworten). `tests/publish-smoke.php` ist der Test des Endpunkts. Ein Release von Hand ist dieselbe Anfrage, die der Workflow stellt:

```bash
curl -sS -H "X-Publish-Token: $TOKEN" -F catalogue=@dist/catalogue.json -F signature=@dist/catalogue.json.sig -F "archives[]=@dist/newsletter-1.0.0.tar.gz" https://catalogue.getnino.dev/publish.php
```

### Ein eigener Katalog

`bin/build.php` baut dieselben Dateien für jeden Satz Features – dieses Repository, einen Fork davon – gegen einen Nino-Checkout:

```bash
php bin/build.php ../nino dist --base-url https://example.org/features
openssl dgst -sha256 -sign catalogue-key.pem dist/catalogue.json | base64 -w0 > dist/catalogue.json.sig
```

Ohne `--key` schreibt es keine Signatur und gibt diesen Einzeiler aus; mit `--key catalogue-key.pem` signiert es selbst und prüft die Signatur mit der öffentlichen Hälfte, bevor es endet. `--only <key>` baut ein Feature und behält jeden anderen Eintrag eines `catalogue.json`, das schon in `dist/` liegt; ein Archiv, das schon in `dist/` liegt, bleibt und wird nie neu gebaut. Lade `dist/` nach `https://example.org/features/` hoch – von Hand, oder über ein dort ausgerolltes `server/publish.php` mit der öffentlichen Hälfte deines Schlüssels – und zeige ein Projekt dorthin: `/nino/catalogue/url` nennt die URL des Katalogs, `/nino/catalogue/key` seinen öffentlichen Schlüssel, beides in `config.php`. `tests/build-smoke.php` ist der eigene Test des Werkzeugs.

### Der Katalog, Format 1

```json
{ "format": 1, "generated": "2026-09-07T12:00:00Z", "features": [ { "key": "newsletter", "...": "..." } ] }
```

| Feld | Was es sagt |
| --- | --- |
| `key` | der Feature-Key, ein Slug – `newsletter` |
| `name`, `description` | wie das Manifest sie hat: ein String oder eine Abbildung `locale => string` |
| `category` | wofür das Feature da ist, ein Slug – das des Manifests; fehlt, wo es keine nennt |
| `version` | `major.minor.patch`, die des Manifests |
| `nino` | die Nino-Versionsbedingung, `^1.0` |
| `php` | `{ "ext": [ ... ] }` – die PHP-Erweiterungen, die das Feature braucht |
| `requires` | die Keys der Features, die es benötigt |
| `directory` | das eine Verzeichnis, das das Archiv enthält – `Newsletter`, der Klassenname |
| `archive` | die https-URL des Archivs |
| `sha256` | die Hex-Prüfsumme des Archivs – wogegen der Kernel einen Download prüft |
| `size` | seine Größe in Bytes, höchstens 20 MB |
| `released` | der Tag der Veröffentlichung, `YYYY-MM-DD` |

`generated` ist die Zeit des letzten Builds, ISO 8601 UTC. Die Einträge sind nach Key sortiert, dann absteigend nach Version; ein Kernel nimmt die höchste Version, die er ausführen kann. Was `\Nino\Catalogue::parse()` in Nino abweist – ein fehlendes Feld, eine URL, die nicht https ist, ein Archiv über 20 MB –, weist den ganzen Katalog ab; darum prüft `bin/build.php`, was es geschrieben hat, gegen `parse()`, wo der Checkout es hat. `category` ist das einzige Feld, das verworfen statt abgewiesen wird: Es ist eine Überschrift in einer Liste, und ein Kernel, der einen signierten Katalog wegen einer Kategorie ablehnte, die er nie gehört hat, hörte an dem Tag auf, den Katalog zu lesen, an dem ein neuerer eine veröffentlicht. Deshalb hat es auch die Formatnummer nicht erhöht – `parse()` nimmt nur die Schlüssel, die es kennt.

## Ausblick

Die Veröffentlichungsseite ist hier: `bin/build.php`, der Release-Workflow, der signierte Katalog auf getnino.dev. Die andere Seite – `\Nino\Catalogue`, das Panel Features, das den Katalog auf Anfrage lädt und ein Archiv installiert – nimmt dapeio/nino gerade auf; ein Kernel, der sie trägt, und den öffentlichen Schlüssel mit ihr, bietet diesen Katalog im Panel an. Ein Kernel, der das nicht tut, nimmt ein Feature weiterhin so, wie diese README es beschreibt: als Kopie von `features/<Name>/`. Eine Netzanfrage macht hier so oder so nichts.

## Lizenz

[MIT](LICENSE) – derselbe Autor wie [Nino](https://github.com/dapeio/nino).
