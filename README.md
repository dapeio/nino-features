# Nino Features

**Language:** English · [Deutsch](README.de.md)

The feature catalogue of [Nino](https://github.com/dapeio/nino): the features a Nino project can install, one per directory below `features/`, each with its own version, its own changelog and its own tests.

## What this catalogue is

A Nino checkout ships no feature. Whatever a project needs beyond the kernel - a newsletter, a search - arrives as a **feature**: a directory with a runtime class, a workbench panel when it has one, an install unit with templates and texts, and a manifest `feature.php` that says what it is, which Nino version it was written for and which settings it offers. What a feature has to deliver is the [Features](https://github.com/dapeio/nino/blob/main/docs/features.md) contract in Nino; every feature here follows it.

This repository is the place Nino's features are published from. `features/<Name>/` is exactly what lands in a project - a project copies the directory into its own `features/` and switches the feature on in the workbench's **Features** panel. The repository itself does nothing over the network: it is a set of files you clone or copy - and the source of what getnino.dev serves as signed archives, see [Publishing](#publishing).

`Newsletter` and `Search` lived in the Nino repository itself up to Nino 1.0.0-beta, under `app/Nino/Modules/`. They sit here unchanged and carry their own version from now on.

## The features

| Key | Name | Version | Nino | What it does |
| --- | --- | --- | --- | --- |
| `newsletter` | [Newsletter](features/Newsletter/README.md) | 1.0.0 | `^1.0` | Double opt-in signup with confirmation and unsubscribe links, and the subscriber list as a workbench panel |
| `search` | [Elements search](features/Search/README.md) | 1.0.0 | `^1.0` | A locale-aware fuzzy search index over configured Element fields, rebuilt on every save and from the Search panel |

A feature's README describes its routes, its panel, its install unit, its data and its tests; its `CHANGELOG.md` the changes between versions. `bin/catalogue.php` reads the same manifests and prints this table as JSON, `bin/build.php` builds the archives and the signed `catalogue.json` getnino.dev publishes - see [Develop and test](#develop-and-test) and [Publishing](#publishing).

## Install a feature

A feature is not installed; it is dropped in and switched on:

1. Copy `features/<Name>/` from this repository into your project's `features/` - as a whole, under the same directory name. The name is the class name: `features/Newsletter/Newsletter.php` is `\Nino\Modules\Newsletter`.
2. Sign in to `/_admin` and open **Features** in the System group. The panel asks for the developer permission `/_admin/features/manage`. It lists every directory with a valid manifest, switched on or not, with its version and with whatever stands in the way of an activation - a Nino version the feature was not written for, a missing PHP extension, a required feature that is not there.
3. **Activate.** That applies the feature's install unit without overwriting anything your project already has - an existing template, an existing text key, an existing route stay - lists the class in `/nino/modules` and records the version under `/nino/features` in `config.php`.

A panel a feature brings appears with the next load of the workbench - reload the page. A panel in the Content group, like the newsletter's, offers its permission on the roles tab of the Users panel; the **Editor** role does not receive it by itself.

**Update:** Replace `features/<Name>/` with the new release and press **Update** in the Features panel. The panel offers the button as soon as the manifest names a different version than the recorded one. The update is the same action as activating: the unit adds what is new and leaves everything the project has edited since the first activation as it is; a feature that has to migrate its own data does so in `upgrade()` before the new version is recorded.

**Deactivate** removes the class from `/nino/modules` - and nothing else. Settings, data, copied templates and texts stay; switching the feature back on finds everything as it was. There is no uninstall, deliberately: what a feature leaves behind, a developer removes knowingly and by hand.

**Security:** Everything below `features/` is server-side source. The Nino checkout ships `features/.htaccess`, which denies the tree; a web server that does not read `.htaccess` needs the equivalent rule, see [Deployment](https://github.com/dapeio/nino/blob/main/docs/deployment.md) in Nino.

## Develop and test

A feature's tests run against a Nino checkout. Clone Nino beside this repository - `bin/check.sh` expects it at `../nino`, or wherever `NINO_ROOT` points:

```bash
git clone https://github.com/dapeio/nino.git ../nino
bin/check.sh
NINO_ROOT=/path/to/nino bin/check.sh
```

`bin/check.sh` copies every feature into the checkout's `features/` - the same layout a project has - validates every manifest through `bin/catalogue.php`, runs every feature's tests and removes the copies afterwards; then it runs `tests/build-smoke.php`, the publishing tool's own test, which builds a signed catalogue into a directory of its own. A directory the checkout already carries it leaves alone, and says so.

A single test runs directly too. It loads `tests/harness.php` from the checkout three levels above itself - which is where it sits in a project - or from the one `NINO_ROOT` names, and defines `NINO_FEATURES_DIR` as its own parent directory, so the kernel serves the class from wherever the feature happens to be:

```bash
NINO_ROOT=../nino php features/Search/tests/search-smoke.php
```

`bin/catalogue.php` is the preview: it validates every manifest through the checkout's kernel and prints what the catalogue would list, as JSON - key, name, description, version, the Nino constraint, PHP extensions, required features and the directory - without an archive and without a signature. A manifest Nino would skip fails the run: the catalogue never lists less than the directory holds. `bin/build.php` builds the real thing, see [Publishing](#publishing).

```bash
php bin/catalogue.php ../nino > catalogue.json
```

CI (`.github/workflows/ci.yml`) does the same against Nino's `main` and against its latest tag: a syntax check of every PHP and JavaScript file, the features copied into the checkout, the manifests validated, every feature's tests, `tests/build-smoke.php`, Nino's own contract test `tests/features-smoke.php` with the features in place, PHPStan and ESLint over `features/` from inside the checkout. The `catalogue.json` of the `main` run is kept as an artifact. PHPStan and ESLint run the same way locally: copy the features into the checkout and run `phpstan analyse` and `npx eslint features` there.

## Write a feature

The [feature recipe](https://github.com/dapeio/nino/blob/main/docs/recipes/feature.md) in Nino builds a feature step by step up to a passing test; the [Features](https://github.com/dapeio/nino/blob/main/docs/features.md) manual is the contract behind it - the manifest, the settings schema, the lifecycle. Every directory in this catalogue follows the same layout:

```text
features/<Name>/
├── feature.php              the manifest: key, name, description, version, nino, requires, settings, data
├── <Name>.php               the runtime class \Nino\Modules\<Name>
├── Admin/Admin.php          the panel \Nino\Modules\<Name>\Admin, when there is one
├── assets/                  the panel's script and stylesheet
├── install/                 the unit activation applies add-only: manifest.php, templates/, text/
├── text/<locale>.php        the panel's fills, while the feature is active
├── tests/<key>-smoke.php    the feature's own test
├── README.md                what it does, its routes, its panel, its unit, its data, its tests
└── CHANGELOG.md             the changes per version
```

Only `feature.php` and `<Name>.php` are required by the kernel. Everything else is there when the feature needs it - `Search`, for one, has no install unit. `README.md`, `CHANGELOG.md` and a test under `tests/` are required by this catalogue: a feature that sits here explains itself and carries its history along. The rules for agents working here are in [AGENTS.md](AGENTS.md).

## Versions and releases

Every feature carries its own `version` in `feature.php` - `major.minor.patch` - and its own `CHANGELOG.md`. A release is a git tag `<key>-<version>`, such as `newsletter-1.0.0`; the features of one repository are versioned independently, and a tag names exactly one of them. Pushing the tag publishes that version to getnino.dev, see [Publishing](#publishing). A project sees the version in the Features panel and is offered an update as soon as the directory carries a newer one.

`nino` in the manifest names the Nino versions the feature is written for - `^1.0` for both today, which covers every 1.x; a pre-release kernel such as `1.0.0-beta` counts as the release it precedes. The constraint is an intention, not a guarantee: compatibility is tested, not declared. This repository's CI runs every feature against Nino's `main` and against its latest tag, and Nino's own CI clones this catalogue, copies the features into its checkout and runs their tests there - a kernel change that breaks a feature fails on both sides.

## Publishing

catalogue.getnino.dev publishes this catalogue as signed archives, and a Nino that carries `\Nino\Catalogue` reads it from there: the Features panel loads the catalogue when asked - never on its own - offers what fits the running kernel, and installs an archive straight into `features/` after checking its digest against the signed catalogue. Everything published is a static file over https:

| Path | What it is |
| --- | --- |
| `https://catalogue.getnino.dev/catalogue.json` | the catalogue, format 1 - see [The catalogue, format 1](#the-catalogue-format-1) |
| `https://catalogue.getnino.dev/catalogue.json.sig` | its detached signature: ECDSA over SHA-256 of the exact bytes of `catalogue.json`, DER, base64 on one line |
| `https://catalogue.getnino.dev/<key>-<version>.tar.gz` | one archive per feature version, holding exactly one directory `<Name>/` - what lands below a project's `features/`, without `tests/`; at most 20 MB packed, 50 MB unpacked, 5000 entries |

A published version is immutable: an archive that is on the server is never rebuilt or overwritten, and its entry keeps its digest, its size and its release date. What has to change is released as the next version.

### A release

1. Bump `version` in `features/<Name>/feature.php` and write the entry `## <version> — <date>` in its `CHANGELOG.md`; bring its `README.md` up to date where behaviour changed.
2. Run `bin/check.sh` - every manifest, every feature's tests, the publishing tool's own test.
3. With the change on `main`, tag the commit `<key>-<version>` and push the tag:

```bash
git tag newsletter-1.0.1
git push origin newsletter-1.0.1
```

The tag starts `.github/workflows/release.yml`, which

- checks out the tag and clones Nino's `main` beside it, as `../nino`;
- reads key and version from the tag and fails on one that is not `<key>-<major>.<minor>.<patch>`;
- finds the feature whose manifest carries that key, checks that its `feature.php` declares exactly that version and its `CHANGELOG.md` has the entry, and runs the feature's own tests against the checkout;
- fetches the published `catalogue.json` - and, for a re-run, the published archive of this version - into `dist/`; a 404 is the first release;
- writes the signing key from the secret to a temporary file, runs `php bin/build.php ../nino dist --only <key> --key <file>` and removes the key file again, whatever happened;
- keeps `dist/` as a workflow artifact;
- posts `catalogue.json`, `catalogue.json.sig` and this version's archive to `server/publish.php` over https, one `curl` (`PUBLISH_URL`, `PUBLISH_TOKEN`); the endpoint verifies the signature itself and never overwrites a published archive. No ssh.

A release that stopped half way - a failing test, a failing upload - is run again from **Actions → Release → Run workflow** with the key and the version: the workflow checks out the tag again, and an archive already on the server stays what it is. A release that went out with a mistake is followed by the next patch version, never replaced.

### The secrets

The repository needs these secrets (**Settings → Secrets and variables → Actions**):

| Secret | What it holds |
| --- | --- |
| `CATALOGUE_SIGNING_KEY` | the PEM private key `catalogue.json` is signed with - the whole file, `-----BEGIN EC PRIVATE KEY-----` included |
| `PUBLISH_URL` | the endpoint's url, `https://catalogue.getnino.dev/publish.php` |
| `PUBLISH_TOKEN` | the token `server/publish.php` is configured with (`NINO_CATALOGUE_TOKEN`) |

A fork that publishes a catalogue of its own sets the repository *variable* `CATALOGUE_URL` (same page, **Variables**) to where the files are served from; without it the workflow names `https://catalogue.getnino.dev`.

### The signing key

The key pair is made once, offline, and the private half never enters a repository (`.gitignore` refuses `*.pem`):

```bash
openssl ecparam -name prime256v1 -genkey -noout -out catalogue-key.pem
openssl ec -in catalogue-key.pem -pubout -out catalogue-key.pub.pem
```

`catalogue-key.pem` goes into the secret `CATALOGUE_SIGNING_KEY` and into a safe place. `catalogue-key.pub.pem` is public: it is what Nino ships as `\Nino\Catalogue::PUBLIC_KEY`, and what a project that reads a catalogue of its own puts under `/nino/catalogue/key` in `config.php`. An empty key accepts no catalogue at all. A new key pair means a new public key in Nino - a catalogue signed with the new key is refused by every kernel that still carries the old one.

### The server

catalogue.getnino.dev serves one directory as plain static files over https - `catalogue.json`, `catalogue.json.sig` and the archives, no directory listing; the kernel reads the bytes, whatever content type the web server names for them - and, in the same directory, `server/publish.php`: the endpoint the release workflow posts to. No ssh. The workflow sends one https POST with the signed catalogue, its signature and the new archive, and the endpoint takes it only when everything holds: the token matches, the signature verifies with the public key the endpoint holds, every uploaded archive is one the catalogue lists with the digest and the size it names, every archive the catalogue lists is published already or in this upload, and no published archive would change - other bytes under a published name are a 409. A leaked token alone publishes nothing: without the private key there is no catalogue the endpoint accepts.

Deploying it is copying `server/publish.php` into that directory and configuring three things, as environment variables (a container) or as `publish.config.php` beside the script, returning an array with the same keys (a plain web server; `.gitignore` keeps the file out of the repository):

| Setting | What it holds |
| --- | --- |
| `NINO_CATALOGUE_TOKEN` | the token the workflow sends, at least 32 characters - `openssl rand -hex 32`; the same string is the secret `PUBLISH_TOKEN` |
| `NINO_CATALOGUE_PUBLIC_KEY` | the PEM public key, `catalogue-key.pub.pem` - or `NINO_CATALOGUE_PUBLIC_KEY_FILE`, the path of a file holding it |
| `NINO_CATALOGUE_DIR` | the directory the files are written to; the script's own directory when unset |

php has to allow the upload - `upload_max_filesize` and `post_max_size` above the largest archive, `32M` and `64M` leave room - and a proxy in front of php needs its own body limit (`client_max_body_size 64m` for nginx). The directory is writable for the php user; a published archive may be made read-only afterwards, the endpoint never writes one twice. A file that is not there has to answer **404** - a front controller that answers 200 with an empty body for any path makes the workflow take an empty archive for a published one (`bin/build.php` removes a zero-byte file and builds afresh, and the workflow treats an empty 200 as "not published", but the server should be right in the first place). `tests/publish-smoke.php` is the endpoint's test. A release by hand is the same request the workflow makes:

```bash
curl -sS -H "X-Publish-Token: $TOKEN" -F catalogue=@dist/catalogue.json -F signature=@dist/catalogue.json.sig -F "archives[]=@dist/newsletter-1.0.0.tar.gz" https://catalogue.getnino.dev/publish.php
```

### A catalogue of your own

`bin/build.php` builds the same files for any set of features - this repository, a fork of it - against a Nino checkout:

```bash
php bin/build.php ../nino dist --base-url https://example.org/features
openssl dgst -sha256 -sign catalogue-key.pem dist/catalogue.json | base64 -w0 > dist/catalogue.json.sig
```

Without `--key` it writes no signature and prints that one-liner; with `--key catalogue-key.pem` it signs itself and verifies the signature with the public half before it exits. `--only <key>` builds one feature and keeps every other entry of a `catalogue.json` already in `dist/`; an archive already in `dist/` is kept, never rebuilt. Upload `dist/` to `https://example.org/features/` - by hand, or through `server/publish.php` deployed there with the public half of your key - and point a project there: `/nino/catalogue/url` names the catalogue's url, `/nino/catalogue/key` its public key, both in `config.php`. `tests/build-smoke.php` is the tool's own test.

### The catalogue, format 1

```json
{ "format": 1, "generated": "2026-09-07T12:00:00Z", "features": [ { "key": "newsletter", "...": "..." } ] }
```

| Field | What it says |
| --- | --- |
| `key` | the feature key, a slug - `newsletter` |
| `name`, `description` | as the manifest has them: a string, or a `locale => string` map |
| `version` | `major.minor.patch`, the manifest's |
| `nino` | the Nino version constraint, `^1.0` |
| `php` | `{ "ext": [ ... ] }` - the PHP extensions the feature needs |
| `requires` | the keys of the features it requires |
| `directory` | the one directory the archive holds - `Newsletter`, the class name |
| `archive` | the https url of the archive |
| `sha256` | the hex digest of the archive - what the kernel checks a download against |
| `size` | its size in bytes, at most 20 MB |
| `released` | the day it was published, `YYYY-MM-DD` |

`generated` is the time of the last build, ISO 8601 UTC. The entries are sorted by key, then by version descending; a kernel picks the highest version it can run. What `\Nino\Catalogue::parse()` in Nino refuses - a missing field, a url that is not https, an archive above 20 MB - refuses the whole catalogue, so `bin/build.php` checks what it wrote against `parse()` where the checkout has it.

## Outlook

The publishing side is here: `bin/build.php`, the release workflow, the signed catalogue on getnino.dev. The other side - `\Nino\Catalogue`, the Features panel that loads the catalogue on request and installs an archive - is what dapeio/nino is taking in; a kernel that carries it, and the public key with it, offers this catalogue in the panel. A kernel that does not, still takes a feature the way this README describes it: as a copy of `features/<Name>/`. Nothing here makes a network request either way.

## License

[MIT](LICENSE) - the same author as [Nino](https://github.com/dapeio/nino).
