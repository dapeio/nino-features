# Nino Features

**Language:** English · [Deutsch](README.de.md)

The feature catalogue of [Nino](https://github.com/dapeio/nino): the features a Nino project can install, one per directory below `features/`, each with its own version. The catalogue itself is the list - [catalogue.getnino.dev](https://catalogue.getnino.dev) serves it, and the Features panel of a project reads it.

## What this catalogue is

A Nino checkout ships no feature. Whatever a project needs beyond the kernel - a newsletter, a search - arrives as a **feature**: a directory with a runtime class, a workbench panel when it has one, an install unit with templates and texts, and a manifest `feature.php` that says what it is, which Nino version it was written for and which settings it offers. What a feature has to deliver is the [Features](https://github.com/dapeio/nino/blob/main/docs/features.md) contract in Nino; every feature here follows it.

`features/<Name>/` is exactly what lands in a project. The repository does nothing over the network: it is a set of files you clone or copy, and the source of what getnino.dev serves as signed archives. What `main` carries is what the catalogue lists, see [A release](#a-release).

A feature is documented in its manifest: the `manual` in `feature.php` is what the Features panel shows on the Description tab, and the `hint` of a setting is what the form says beside it. There is no other documentation to keep. The history of a feature is the commit history.

## Install a feature

A feature is not installed; it is dropped in and switched on:

1. Copy `features/<Name>/` from this repository into your project's `features/` - as a whole, under the same directory name. The name is the class name: `features/Newsletter/Newsletter.php` is `\Nino\Modules\Newsletter`. A project that reads the catalogue installs from the Features panel instead.
2. Sign in to `/_admin` and open **Features** in the System group. The panel asks for the developer permission `/_admin/features/manage`. It lists every directory with a valid manifest, switched on or not, with its version and with whatever stands in the way of an activation - a Nino version the feature was not written for, a missing PHP extension, a required feature that is not there.
3. **Activate.** That applies the feature's install unit without overwriting anything your project already has, lists the class in `/nino/modules` and records the version under `/nino/features` in `config.php`.

A panel a feature brings appears with the next load of the workbench, in the Features group; the roles tab of the Users panel offers its permission there, and the **Editor** role does not receive it by itself.

**Update:** replace `features/<Name>/` with the new release and press **Update** in the Features panel. The panel offers it as soon as the manifest names a different version than the recorded one; the unit adds what is new and leaves everything the project has edited as it is. **Deactivate** removes the class from `/nino/modules` and nothing else - settings, data, copied templates and texts stay.

**Security:** everything below `features/` is server-side source. The Nino checkout ships `features/.htaccess`, which denies the tree; a web server that does not read `.htaccess` needs the equivalent rule, see [Deployment](https://github.com/dapeio/nino/blob/main/docs/deployment.md) in Nino.

## Develop and test

A feature's tests run against a Nino checkout. Clone Nino beside this repository - `bin/check.sh` expects it at `../nino`, or wherever `NINO_ROOT` points:

```bash
git clone https://github.com/dapeio/nino.git ../nino
bin/check.sh
NINO_ROOT=/path/to/nino bin/check.sh
```

`bin/check.sh` copies every feature the checkout can run into the checkout's `features/` - the same layout a project has -, validates every manifest through `bin/catalogue.php`, runs those features' own tests and removes the copies again; then it runs every test of this repository under `tests/` - the text key grammar, the privacy sections, the rules every feature shares, the build tool and the release script. The script's own lines are the list. A feature written for a newer Nino than the checkout is listed with its reason and left out (`bin/applicable.php` decides).

A single test runs directly too:

```bash
NINO_ROOT=../nino php features/Search/tests/search-smoke.php
```

`bin/catalogue.php` prints what the catalogue would list as JSON, without an archive and without a signature. CI (`.github/workflows/ci.yml`) runs the same tests against Nino's `main` and its latest tag, plus a syntax check, Nino's own contract test, PHPStan and ESLint over `features/`. Nothing waits for it: it is a convenience.

## Write a feature

The [feature recipe](https://github.com/dapeio/nino/blob/main/docs/recipes/feature.md) in Nino builds a feature step by step; the [Features](https://github.com/dapeio/nino/blob/main/docs/features.md) manual is the contract behind it. `features/Hello/` is a complete example written to be copied: copy it, then rename the directory, the `key` in `feature.php` and the class `\Nino\Modules\Hello` so the three agree.

```text
features/<Name>/
├── feature.php              the manifest: key, name, description, manual, category, version, nino, requires, settings, data
├── <Name>.php               the runtime class \Nino\Modules\<Name>
├── Admin/Admin.php          the panel, when there is one
├── assets/ install/ text/ templates/   when the feature needs them
└── tests/<key>-smoke.php    a test, when you want one
```

Required are `feature.php` and `<Name>.php`. The manifest's `manual` is the documentation: a `section => handle => line` map - shortcodes, markup, routes, panel, callbacks, install - with the handle as somebody types it and a line in German and English beside it. A test is welcome and never required. The rules that stay, because tests check them, are in [AGENTS.md](AGENTS.md): no markup in PHP, English in code, escaping, the text key grammar, and a section of the privacy policy for a feature that processes personal data.

`category` is one of `content`, `ui`, `communication`, `marketing`, `security` or `system`, and no two features carry the same `name` in any locale; `bin/build.php` refuses both.

## A release

A release is `bin/release.sh`, run by the owner on their own machine. The catalogue is what `main` carries: every run builds every feature and puts the result on the server, and a feature that did not change gives the same bytes and uploads nothing.

```bash
NINO_CATALOGUE_KEY=~/safe/catalogue-key.pem NINO_CATALOGUE_TARGET=nino@host:/srv/catalogue/ bin/release.sh
```

It runs `bin/check.sh` first (`--quick` skips it), fetches what the server holds into `public/` by rsync, lets `bin/build.php` build every archive and sign the catalogue, and puts `public/` back with `rsync --delete`. `--dry-run` stops before the upload. `NINO_ROOT` names the Nino checkout (default `../nino`), `NINO_CATALOGUE_URL` where the archives are served from (default `https://catalogue.getnino.dev`).

There is one entry per feature, the version of its manifest, and no tag. A change without a new `version` reaches new installations, not existing ones: the Features panel offers an update from the catalogue only for a higher version. So `version` is raised when installed projects should be offered the update. During an upload a download may fail its checksum; the project repeats it.

## The server

A directory served as static files over https, with ssh access for rsync. A file that is not there answers **404**. No PHP, no upload limits, no configuration beyond the virtual host. The directory exists, empty, before the first run; the upload makes its files world-readable. The kernel reads the bytes, whatever content type the server names for them.

## The signing key

The key pair is made once, offline, and the private half never enters a repository (`.gitignore` refuses `*.pem`):

```bash
openssl ecparam -name prime256v1 -genkey -noout -out catalogue-key.pem
openssl ec -in catalogue-key.pem -pubout -out catalogue-key.pub.pem
```

Keep `catalogue-key.pem` in a safe place outside every repository, with a copy. `catalogue-key.pub.pem` is public: it is what Nino ships as `\Nino\Catalogue::PUBLIC_KEY`, and what a project that reads a catalogue of its own puts under `/nino/catalogue/key` in `config.php`. An empty key accepts no catalogue at all. A new key pair means a new public key in Nino - a catalogue signed with the new key is refused by every kernel that still carries the old one.

## A catalogue of your own

`bin/release.sh` works for any set of features - a fork of this repository - with your own `NINO_CATALOGUE_URL` and `NINO_CATALOGUE_TARGET`. `bin/build.php` alone builds the same files into a directory:

```bash
php bin/build.php ../nino public --base-url https://example.org/features
openssl dgst -sha256 -sign catalogue-key.pem public/catalogue.json | base64 -w0 > public/catalogue.json.sig
```

Without `--key` it writes no signature and prints that one-liner; with `--key catalogue-key.pem` it signs itself and verifies the signature with the public half before it exits. Upload the directory to `https://example.org/features/` and point a project there: `/nino/catalogue/url` names the catalogue's url, `/nino/catalogue/key` its public key, both in `config.php`.

## The catalogue, format 1

```json
{ "format": 1, "generated": "2026-09-07T12:00:00Z", "features": [ { "key": "newsletter", "...": "..." } ] }
```

| Field | What it says |
| --- | --- |
| `key` | the feature key, a slug - `newsletter` |
| `name`, `description` | as the manifest has them: a string, or a `locale => string` map |
| `category` | what the feature is for, one slug - the manifest's |
| `maturity` | the badge the Features panel draws beside the name - the manifest's; left out where it names none |
| `version` | `major.minor.patch`, the manifest's |
| `nino` | the Nino version constraint, `^1.3` |
| `php` | `{ "ext": [ ... ] }` - the PHP extensions the feature needs |
| `requires` | the keys of the features it requires |
| `directory` | the one directory the archive holds - `Newsletter`, the class name |
| `archive` | the https url of the archive, `<key>-<version>.tar.gz`, at most 20 MB packed |
| `sha256` | the hex digest of the archive - what the kernel checks a download against |
| `size` | its size in bytes |
| `released` | the day this archive was first published as it is, `YYYY-MM-DD` |

`catalogue.json.sig` is its detached signature: ECDSA over SHA-256 of the exact bytes, DER, base64 on one line. `generated` is the time of the last change, ISO 8601 UTC. What `\Nino\Catalogue::parse()` refuses - a missing field, a url that is not https, an archive above 20 MB - refuses the whole catalogue, so `bin/build.php` runs what it is about to write through `parse()` first.

## License

[MIT](LICENSE) - the same author as [Nino](https://github.com/dapeio/nino). The icons of the
features' panels come from [Lucide](https://lucide.dev) (ISC, the ones taken over from Feather also
MIT); their notices are in [THIRD-PARTY-NOTICES.md](THIRD-PARTY-NOTICES.md).
