# Nino feature catalogue guide for AI coding agents

This file is the operational specification for AI agents that modify this
repository, dapeio/nino-features. It is short because most of what applies
here is written down in Nino itself: the [Nino agent guide](https://github.com/dapeio/nino/blob/main/AGENTS.md)
states the rules every change to Nino code has to keep, and the
[feature recipe](https://github.com/dapeio/nino/blob/main/docs/recipes/feature.md)
walks a feature from its first file to its test. Both apply here unchanged.
This file adds what is specific to a catalogue of features that live apart
from the kernel they run on.

## 1. Rule strength and source of truth

The words **MUST**, **MUST NOT**, **SHOULD**, and **MAY** are normative.

1. The current user request and repository instructions have priority.
2. This file defines repository-wide defaults.
3. Nino's `AGENTS.md`, its feature recipe and its Features manual define how
   a feature is built. Read them from a Nino checkout beside this repository
   (`../nino`), never from memory.
4. `\Nino\Features` in the checkout (`_nino/Nino/Features/Features.php`) and
   `tests/features-smoke.php` define the contract a manifest and an
   activation have to meet. If this repository's documentation and that
   source disagree, follow the source and fix the documentation in the same
   change.
5. A feature's own code and tests define its current behaviour. Its
   `README.md` explains it; where README and code disagree, the code is
   right and the README is stale.

Do not invent an API because its name seems plausible. Search the Nino
checkout for the actual method, its signature, and at least one call site.

## 2. Required workflow for every change

Before editing:

1. Read the complete user request and list its observable requirements.
2. Run `git status --short --branch`. Preserve all unrelated user changes.
3. Make sure a Nino checkout is available: `../nino`, or set `NINO_ROOT`.
   Read Nino's `AGENTS.md` and `docs/recipes/feature.md` there before
   touching a feature.
4. Read the feature's `README.md`, `CHANGELOG.md`, `feature.php`, its class,
   its panel and its test in full. A feature is small; read all of it.
5. Identify authentication, CSRF, validation, persistence, escaping,
   concurrency, locale, and backward-compatibility consequences - the
   [security review](https://github.com/dapeio/nino/blob/main/AGENTS.md#8-security-review-required-for-every-extension)
   of Nino's guide applies to every feature.
6. Define a test that fails for the old behaviour and passes for the new one,
   in the feature's own `tests/`.

While editing, every change to a feature MUST keep these four in step:

| File | What changes with it |
| --- | --- |
| `feature.php` | `version` is bumped with every release a project should be able to tell apart - the Features panel can only offer an update it can see. `nino` names the Nino versions the feature is written for; change it only when the feature really stops running on a version it named |
| `CHANGELOG.md` | one entry per version, `## <version> — <date>`, newest first, with what changed for a project that installs it |
| `README.md` | every route, key, fill, method, file and permission it names exists in the code; a behaviour that changed is described as it is now |
| `tests/<key>-smoke.php` | the feature's own test covers the change; it loads the harness as described in section 5 |

Further rules:

- Make the smallest coherent change. Follow the formatting of the code
  around it: tabs, the docblock shape, `declare(strict_types=1);`.
- A feature MUST NOT depend on anything outside `\Nino\*`'s public API and
  its own directory. It cannot rely on `app/`, on another feature that its
  manifest does not list under `requires`, or on a kernel module a project
  may have switched off.
- A feature MUST NOT add a runtime dependency, a build step or a network
  request.
- A feature's install unit is applied add-only and never re-applied to fix
  a file: a change to a template the unit copies reaches only new
  activations. Say so in the changelog when a project should copy it by
  hand.
- A change to files under `data/` that the feature owns needs `upgrade()`
  in the class and an entry under `data` in the manifest.

After editing:

1. Review `git diff --check` and the complete diff.
2. Run `php -l` over every changed PHP file and `node --check` over every
   changed JavaScript file.
3. Run `bin/check.sh` (or `NINO_ROOT=/path/to/nino bin/check.sh`). It
   validates every manifest through the checkout's kernel, runs every
   feature's own test against it and then `tests/build-smoke.php`, the
   publishing tool's own test. It MUST pass before you report.
4. With the features copied into the checkout, run `phpstan analyse` and
   `npx eslint features` there - what CI does.
5. Report changed files, behaviour, tests, and any remaining limitation.

## 3. Repository map

| Path | Ownership |
| --- | --- |
| `features/<Name>/` | One feature, exactly what lands in a project's `features/`. `Newsletter` and `Search` today. The directory name is the class name `\Nino\Modules\<Name>` and MUST match `/^[A-Z][A-Za-z0-9]*$/` |
| `bin/catalogue.php` | The preview: reads every manifest through a Nino checkout and prints what the catalogue would list as JSON - key, name, description, version, `nino`, `php`, `requires`, directory - without an archive or a signature. A manifest Nino would skip fails the run. Defines `NINO_FEATURES_DIR` as this repository's `features/`, so the checkout's own directory is never what it reads. `bin/check.sh`, CI and the release workflow use it as the manifest check |
| `bin/build.php` | The publishing tool: `php bin/build.php <nino-checkout> <out-dir> [--base-url …] [--key private.pem] [--only <key>]`. Validates every manifest the same way, builds `<out-dir>/<key>-<version>.tar.gz` per feature as a plain ustar tar written by the script itself, every entry stamped with one fixed time - exactly one directory `<Name>/`, without `tests/`, `.git*`, `.DS_Store` and editor leftovers, sorted so a build is reproducible - and merges the entries into `<out-dir>/catalogue.json` in format 1 (see `\Nino\Catalogue` in Nino), signing it with `--key`. An archive that already exists is never rebuilt or overwritten and its entry is kept: a published version is immutable |
| `bin/check.sh` | Copies every feature into the checkout (`../nino` or `NINO_ROOT`), runs `bin/catalogue.php`, every feature's tests and `tests/build-smoke.php`, removes the copies again. A directory the checkout already carries is left alone |
| `tests/build-smoke.php` | The publishing tool's own test over Nino's harness: a keypair per run, a signed build into a temporary directory, the archives' contents, the catalogue's fields, the signature, a second run that rebuilds nothing, `--only`, the merge, the refusals - and, where the checkout has `\Nino\Catalogue`, an installation of the archives through it |
| `.github/workflows/ci.yml` | The matrix: Nino `main` and Nino's latest tag. Lint, copy, validate, every feature's tests, `tests/build-smoke.php`, Nino's `tests/features-smoke.php`, PHPStan, ESLint; `catalogue.json` kept as an artifact of the `main` run |
| `.github/workflows/release.yml` | Publishes one feature version to getnino.dev when the tag `<key>-<version>` is pushed (or on `workflow_dispatch` with `key` and `version`): checks the tag against the manifest and the changelog, runs the feature's tests, fetches the published catalogue, runs `bin/build.php --only <key> --key …` with the key from the secret `CATALOGUE_SIGNING_KEY`, uploads with rsync over ssh (`DEPLOY_HOST`, `DEPLOY_USER`, `DEPLOY_PATH`, `DEPLOY_KEY`, `DEPLOY_KNOWN_HOSTS`), archives with `--ignore-existing`. README.md, Publishing, is the manual |
| `README.md`, `README.de.md` | The catalogue for humans: what it is, the features table, install, develop, write, versions, publishing, outlook. English is the primary version, German the author's; both are published together with identical commands and paths |
| `.gitignore` | Ignores `/nino/` (a checkout placed inside rather than beside), `*.patch`, `/dist/` (what `bin/build.php` writes) and `*.pem` (a key never enters the repository) |
| `LICENSE`, `.editorconfig` | MIT; tabs, LF, UTF-8, the same defaults Nino uses |

There is no `catalogue.json`, no archive and no `dist/` in the repository:
they are generated, by `bin/catalogue.php`, `bin/build.php` and CI, and
published by the release workflow. Do not commit one. A signing key or a
deploy key is never written anywhere but the workflow's secrets.

## 4. What a feature MUST carry

Nino requires `feature.php` and `<Name>.php`. This catalogue requires more,
because a feature here is published on its own:

| File | Requirement |
| --- | --- |
| `feature.php` | `key` (a slug), `name`, `description` (a string or a `locale => string` map, `en_US` and `de_DE` at least), `version` (`major.minor.patch`), `nino` (a version constraint, `^1.0` today), `requires`, `settings`, `data` - every key spelled out, even when empty, so a reader sees what the feature does not do |
| `<Name>.php` | the runtime class `\Nino\Modules\<Name>`; `init()` registers and outputs nothing; `adminPanels()` when there is a panel; `upgrade()` when a version changes the shape of its data; a `'/nino/admin/restore'` callback when its `data/` files must merge on restore |
| `Admin/Admin.php` | the panel, when there is one: `actions()`, `nav()`, `perm()`, `text()`, every action guarded with `\Nino\Admin\Admin::guardPerm()`; `assets()` named through `\Nino\Admin\Panels::relative()` so they move with the directory |
| `text/<locale>.php` | the panel's fills for every interface language Nino ships, `en_US` and `de_DE` |
| `install/` | the unit, when the feature ships templates, texts, routes or config defaults for the website; `install/manifest.php` in the wizard's library format |
| `tests/<key>-smoke.php` | the feature's own test over Nino's harness, section 5. `Newsletter` does not carry one yet; a change to it SHOULD add one |
| `README.md` | English, in the shape of the two existing ones: what it does, routes, panel and permission, install unit, settings, data and restore, configuration, tests. Every name in it exists in the code |
| `CHANGELOG.md` | `## <version> — <date>` per release, newest first |

A feature MUST NOT carry a `.htaccess`, a `composer.json`, a `package.json`,
a build output or a copy of anything from `_nino/`.

## 5. How a test is written

A feature's test is a standalone script over Nino's `tests/harness.php`,
the way every Nino smoke test is. It lives in the feature's own `tests/`,
travels with it, and has to run from two places: inside a project's
`features/`, three levels below the checkout, and inside this repository,
against whatever checkout `NINO_ROOT` names. The first lines are therefore
always:

```php
$root = getenv( 'NINO_ROOT' ) ?: dirname( __DIR__, 3 );
defined( 'NINO_FEATURES_DIR' ) === true || define( 'NINO_FEATURES_DIR', dirname( __DIR__, 2 ) );
require $root. '/tests/harness.php';
```

`NINO_FEATURES_DIR` MUST be defined before the harness loads the kernel: the
autoloader and `\Nino\Features::dir()` read the same constant, so the
directory the feature actually sits in is the one that serves its class -
wherever `NINO_ROOT` points. The `defined()` guard keeps a constant that
was defined before the test was included, instead of failing on a redefinition.

The harness provides `check( $label, $condition )`, `ninoSandbox( $name )`
(a fresh isolated project directory in `$appData`, two locales, no modules),
`ninoSandboxDir()`, `ninoWarnings()` (the warnings recorded since the last
call - assert on a warning you expect rather than scrolling past it) and
`ninoDone( $appData )` (the summary, the sandbox removed, the exit status).
Test the visible contract: the manifest validates, activation lists the
class and records the version, the unit's files land add-only, the routes
and the panel's actions answer with the right status and body, the panel is
present while the feature is active and absent after, deactivation keeps
what the project has. `features/Search/tests/search-smoke.php` is the
reference.

Run one test directly, or all of them:

```bash
NINO_ROOT=../nino php features/Search/tests/search-smoke.php
bin/check.sh
```

PHPStan excludes `features/*/tests/*` in Nino's `phpstan.neon`: a test is a
script, not product code.

## 6. Delivery policy

Repository-owner delivery policy: do not create a commit and do not push.
Deliver the changes as a named `.patch` file - a plain `git diff --binary`
against the requested base, verified with `git apply --check` in a clean
checkout of that base - unless the user explicitly asks only for an inline
answer. A request to "implement", "finish", "release" or "apply" is not
permission to commit or to tag. `.gitignore` ignores `*.patch`, so a patch
written into the repository is never picked up by mistake.

A release - the tag `<key>-<version>` - is the owner's action: pushing the
tag starts `.github/workflows/release.yml`, which publishes that version to
getnino.dev, and a published version is immutable. Prepare it: bump
`version`, write the changelog entry, run `bin/check.sh`; then name the tag
in the report and stop. Never create or push a tag.

## 7. What belongs in Nino instead

This repository holds features and the tooling to publish them. It does not
hold Nino. Take these to a change in dapeio/nino, and say so in the report
rather than working around them here:

- a change to the kernel, to `\Nino\Features`, to the autoloader or to
  `tests/harness.php`;
- a change to the Features panel - its actions, its form, what it lists;
- a change to the feature contract - the manifest keys, the settings types,
  the lifecycle, the unit format; `docs/features.md` and
  `tests/features-smoke.php` live there;
- a workbench screen every project has regardless of its features (that is
  a module under `_admin/Nino/Modules/`), a kernel module, a section preset,
  an installer unit for the wizard;
- the download, the signature check and what a catalogue entry has to
  say: that is `\Nino\Catalogue` in Nino, and its `parse()` is the contract
  `bin/build.php` writes to. A new field or a new format goes to Nino first;
  the builder and `tests/build-smoke.php` follow. What lives here is the
  publishing side - `bin/build.php`, `tests/build-smoke.php`,
  `.github/workflows/release.yml` - and nothing here makes a network
  request except the release workflow, on the runner.

A feature that needs a kernel capability it does not have is blocked, not
patched: report the gap, do not copy kernel code into the feature.

## 8. Completion report format

Report changed files, the behaviour before and after, every command run
with its result (`bin/check.sh` at least - which includes
`tests/build-smoke.php` - and against which checkout), the
changelog entry, and any remaining limitation - a Nino version the feature
no longer runs on, a template a project has to copy by hand, a test that
does not exist yet. Name the patch file.
