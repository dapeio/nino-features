# Nino Features

**Language:** English · [Deutsch](README.de.md)

The feature catalogue of [Nino](https://github.com/dapeio/nino): the features a Nino project can install, one per directory below `features/`, each with its own version, its own changelog and its own tests.

## What this catalogue is

A Nino checkout ships no feature. Whatever a project needs beyond the kernel - a newsletter, a search - arrives as a **feature**: a directory with a runtime class, a workbench panel when it has one, an install unit with templates and texts, and a manifest `feature.php` that says what it is, which Nino version it was written for and which settings it offers. What a feature has to deliver is the [Features](https://github.com/dapeio/nino/blob/main/docs/features.md) contract in Nino; every feature here follows it.

This repository is the place Nino's features are published from. `features/<Name>/` is exactly what lands in a project - a project copies the directory into its own `features/` and switches the feature on in the workbench's **Features** panel. The catalogue does nothing over the network: it is a set of files you clone or copy.

`Newsletter` and `Search` lived in the Nino repository itself up to Nino 1.0.0-beta, under `app/Nino/Modules/`. They sit here unchanged and carry their own version from now on.

## The features

| Key | Name | Version | Nino | What it does |
| --- | --- | --- | --- | --- |
| `newsletter` | [Newsletter](features/Newsletter/README.md) | 1.0.0 | `^1.0` | Double opt-in signup with confirmation and unsubscribe links, and the subscriber list as a workbench panel |
| `search` | [Elements search](features/Search/README.md) | 1.0.0 | `^1.0` | A locale-aware fuzzy search index over configured Element fields, rebuilt on every save and from the Search panel |

A feature's README describes its routes, its panel, its install unit, its data and its tests; its `CHANGELOG.md` the changes between versions. `bin/catalogue.php` reads the same manifests and prints this table as `catalogue.json`, see [Develop and test](#develop-and-test).

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

`bin/check.sh` copies every feature into the checkout's `features/` - the same layout a project has - validates every manifest through `bin/catalogue.php`, runs every feature's tests and removes the copies afterwards. A directory the checkout already carries it leaves alone, and says so.

A single test runs directly too. It loads `tests/harness.php` from the checkout three levels above itself - which is where it sits in a project - or from the one `NINO_ROOT` names, and defines `NINO_FEATURES_DIR` as its own parent directory, so the kernel serves the class from wherever the feature happens to be:

```bash
NINO_ROOT=../nino php features/Search/tests/search-smoke.php
```

`bin/catalogue.php` validates every manifest through the checkout's kernel and prints the catalogue as JSON - key, name, description, version, the Nino constraint, PHP extensions, required features and the directory. A manifest Nino would skip fails the run: the catalogue never lists less than the directory holds.

```bash
php bin/catalogue.php ../nino > catalogue.json
```

CI (`.github/workflows/ci.yml`) does the same against Nino's `main` and against its latest tag: a syntax check of every PHP and JavaScript file, the features copied into the checkout, the manifests validated, every feature's tests, Nino's own contract test `tests/features-smoke.php` with the features in place, PHPStan and ESLint over `features/` from inside the checkout. The `catalogue.json` of the `main` run is kept as an artifact. PHPStan and ESLint run the same way locally: copy the features into the checkout and run `phpstan analyse` and `npx eslint features` there.

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

Every feature carries its own `version` in `feature.php` - `major.minor.patch` - and its own `CHANGELOG.md`. A release is a git tag `<key>-<version>`, such as `newsletter-1.0.0`; the features of one repository are versioned independently, and a tag names exactly one of them. A project sees the version in the Features panel and is offered an update as soon as the directory carries a newer one.

`nino` in the manifest names the Nino versions the feature is written for - `^1.0` for both today, which covers every 1.x; a pre-release kernel such as `1.0.0-beta` counts as the release it precedes. The constraint is an intention, not a guarantee: compatibility is tested, not declared. This repository's CI runs every feature against Nino's `main` and against its latest tag, and Nino's own CI clones this catalogue, copies the features into its checkout and runs their tests there - a kernel change that breaks a feature fails on both sides.

## Outlook

The plan is for getnino.dev to publish this catalogue as signed archives, and for the Features panel to list the features that match the running Nino version and install an archive straight into `features/`. `bin/catalogue.php` is the start of that - `catalogue.json` is what a catalogue needs. None of it exists today: no download, no signature check, no network access, neither here nor in Nino. Until then a feature arrives in the directory as a checkout or a copy.

## License

[MIT](LICENSE) - the same author as [Nino](https://github.com/dapeio/nino).
