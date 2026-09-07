# Nino-Features

**Sprache:** [English](README.md) · Deutsch

Der Feature-Katalog von [Nino](https://github.com/dapeio/nino): die Features, die ein Nino-Projekt installieren kann, je eines pro Verzeichnis unter `features/`, mit eigener Version, eigenem Changelog und eigenen Tests.

## Was dieser Katalog ist

Ein Nino-Checkout liefert kein Feature aus. Was ein Projekt über den Kernel hinaus braucht – einen Newsletter, eine Suche –, kommt als **Feature**: ein Verzeichnis mit einer Laufzeitklasse, bei Bedarf einem Panel der Workbench, einer Install-Einheit mit Templates und Texten und einem Manifest `feature.php`, das sagt, was es ist, für welche Nino-Version es geschrieben wurde und welche Einstellungen es anbietet. Was ein Feature liefern muss, steht im Vertrag [Features](https://github.com/dapeio/nino/blob/main/docs/features.md) in Nino; jedes Feature hier folgt ihm.

Dieses Repository ist der Ort, von dem aus Ninos Features veröffentlicht werden. `features/<Name>/` ist genau das, was in einem Projekt landet – ein Projekt kopiert das Verzeichnis in sein eigenes `features/` und schaltet das Feature im Panel **Features** der Workbench ein. Der Katalog macht nichts über das Netz: Er ist ein Dateibestand, den man klont oder kopiert.

`Newsletter` und `Search` haben bis Nino 1.0.0-beta im Nino-Repository selbst gelegen, unter `app/Nino/Modules/`. Hier liegen sie unverändert und tragen von jetzt an ihre eigene Version.

## Die Features

| Key | Name | Version | Nino | Was es tut |
| --- | --- | --- | --- | --- |
| `newsletter` | [Newsletter](features/Newsletter/README.md) | 1.0.0 | `^1.0` | Double-Opt-in-Anmeldung mit Bestätigungs- und Abmeldelink, und die Abonnentenliste als Panel der Workbench |
| `search` | [Elemente-Suche](features/Search/README.md) | 1.0.0 | `^1.0` | Ein sprachbewusster unscharfer Suchindex über konfigurierte Elementfelder, neu gebaut bei jedem Speichern und aus dem Panel Suche |

Die README eines Features beschreibt seine Routen, sein Panel, seine Install-Einheit, seine Daten und seine Tests; sein `CHANGELOG.md` die Änderungen zwischen den Versionen. `bin/catalogue.php` liest dieselben Manifeste und gibt diese Tabelle als `catalogue.json` aus, siehe [Entwickeln und testen](#entwickeln-und-testen).

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

`bin/check.sh` kopiert jedes Feature in das `features/` des Checkouts – dieselbe Anordnung, die ein Projekt hat –, prüft jedes Manifest über `bin/catalogue.php`, führt die Tests jedes Features aus und entfernt die Kopien danach wieder. Ein Verzeichnis, das im Checkout schon liegt, rührt es nicht an und sagt das.

Ein einzelner Test läuft auch direkt. Er lädt `tests/harness.php` aus dem Checkout drei Ebenen über sich – so liegt er in einem Projekt – oder aus dem, den `NINO_ROOT` nennt, und definiert `NINO_FEATURES_DIR` auf sein eigenes Elternverzeichnis, sodass der Kernel die Klasse von dort bedient, wo das Feature gerade liegt:

```bash
NINO_ROOT=../nino php features/Search/tests/search-smoke.php
```

`bin/catalogue.php` prüft jedes Manifest durch den Kernel des Checkouts und gibt den Katalog als JSON aus – Key, Name, Beschreibung, Version, die Nino-Bedingung, PHP-Erweiterungen, benötigte Features und das Verzeichnis. Ein Manifest, das Nino überspringen würde, lässt den Lauf fehlschlagen: Der Katalog listet nie weniger, als im Verzeichnis liegt.

```bash
php bin/catalogue.php ../nino > catalogue.json
```

CI (`.github/workflows/ci.yml`) tut dasselbe gegen Ninos `main` und gegen sein jüngstes Tag: Syntaxprüfung aller PHP- und JavaScript-Dateien, Kopie der Features in den Checkout, Validierung der Manifeste, die Tests jedes Features, Ninos eigener Vertragstest `tests/features-smoke.php` mit den Features an Ort und Stelle, PHPStan und ESLint über `features/` aus dem Checkout heraus. Das `catalogue.json` des `main`-Laufs bleibt als Artefakt erhalten. Lokal laufen PHPStan und ESLint genauso: Features in den Checkout kopieren, dort `phpstan analyse` und `npx eslint features` ausführen.

## Ein Feature schreiben

Das [Feature-Rezept](https://github.com/dapeio/nino/blob/main/docs/recipes/feature.md) in Nino baut ein Feature Schritt für Schritt bis zum bestandenen Test; das Handbuch [Features](https://github.com/dapeio/nino/blob/main/docs/features.md) ist der Vertrag dahinter – das Manifest, das Settings-Schema, der Lebenszyklus. Jedes Verzeichnis in diesem Katalog folgt derselben Anordnung:

```text
features/<Name>/
├── feature.php              das Manifest: key, name, description, version, nino, requires, settings, data
├── <Name>.php               die Laufzeitklasse \Nino\Modules\<Name>
├── Admin/Admin.php          das Panel \Nino\Modules\<Name>\Admin, wenn es eines gibt
├── assets/                  Skript und Stylesheet des Panels
├── install/                 die Einheit, die das Aktivieren add-only anwendet: manifest.php, templates/, text/
├── text/<locale>.php        die Fills des Panels, solange das Feature aktiv ist
├── tests/<key>-smoke.php    der eigene Test des Features
├── README.md                was es tut, seine Routen, sein Panel, seine Einheit, seine Daten, seine Tests
└── CHANGELOG.md             die Änderungen je Version
```

Nur `feature.php` und `<Name>.php` verlangt der Kernel. Alles Weitere ist da, wenn das Feature es braucht – `Search` etwa hat keine Install-Einheit. `README.md`, `CHANGELOG.md` und einen Test unter `tests/` verlangt dieser Katalog: Ein Feature, das hier liegt, erklärt sich selbst und trägt seine Geschichte mit. Die Regeln für Agenten, die hier arbeiten, stehen in [AGENTS.md](AGENTS.md).

## Versionen und Releases

Jedes Feature trägt seine eigene `version` in `feature.php` – `major.minor.patch` – und sein eigenes `CHANGELOG.md`. Ein Release ist ein Git-Tag `<key>-<version>`, etwa `newsletter-1.0.0`; die Features eines Repositories werden unabhängig voneinander versioniert, und ein Tag benennt genau eines. Ein Projekt sieht die Version im Panel Features und bekommt ein Update angeboten, sobald das Verzeichnis eine neuere trägt.

`nino` im Manifest nennt die Nino-Versionen, für die das Feature geschrieben ist – heute `^1.0` für beide, was jede 1.x einschließt; ein Vorab-Kernel wie `1.0.0-beta` zählt als das Release, dem er vorausgeht. Die Bedingung ist eine Absicht, keine Garantie: Verträglichkeit wird getestet, nicht erklärt. Die CI dieses Repositories führt jedes Feature gegen Ninos `main` und gegen sein jüngstes Tag aus, und Ninos eigene CI klont diesen Katalog, kopiert die Features in ihren Checkout und führt deren Tests dort aus – eine Kernel-Änderung, die ein Feature bricht, schlägt auf beiden Seiten fehl.

## Ausblick

Geplant ist, dass getnino.dev diesen Katalog als signierte Archive veröffentlicht und das Panel Features die Features listet, die zur laufenden Nino-Version passen, und ein Archiv direkt nach `features/` installiert. `bin/catalogue.php` ist der Anfang davon – `catalogue.json` ist, was ein Katalog braucht. Nichts davon existiert heute: kein Download, keine Signaturprüfung, kein Netzzugriff, weder hier noch in Nino. Bis dahin kommt ein Feature als Checkout oder Kopie in das Verzeichnis.

## Lizenz

[MIT](LICENSE) – derselbe Autor wie [Nino](https://github.com/dapeio/nino).
