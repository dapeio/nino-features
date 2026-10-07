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
5. A feature's own code and tests define its current behaviour. Its manual
   in `feature.php` explains it; where manual and code disagree, the code is
   right and the manual is stale.

Do not invent an API because its name seems plausible. Search the Nino
checkout for the actual method, its signature, and at least one call site.

## 2. Required workflow for every change

Before editing:

1. Read the complete user request and list its observable requirements.
2. Run `git status --short --branch`. Preserve all unrelated user changes.
3. Make sure a Nino checkout is available: `../nino`, or set `NINO_ROOT`.
   Read Nino's `AGENTS.md` and `docs/recipes/feature.md` there before
   touching a feature.
4. Read the feature's `feature.php`, its class, its panel and - where it
   has one - its test in full. A feature is small; read all of it.
5. Identify authentication, CSRF, validation, persistence, escaping,
   concurrency, locale, and backward-compatibility consequences - the
   [security review](https://github.com/dapeio/nino/blob/main/AGENTS.md#8-security-review-required-for-every-extension)
   of Nino's guide applies to every feature.
6. Where the feature has a test, extend it so it fails for the old behaviour
   and passes for the new one. A feature without tests may stay without;
   a test is welcome, not required.

While editing, every change to a feature MUST keep `feature.php` in step,
and the test where the feature carries one:

| File | What changes with it |
| --- | --- |
| `feature.php` | `manual` names every shortcode, piece of markup, route, panel, callback and install file the change adds or removes, a line each in `en_US` and `de_DE` - it is the feature's only documentation, and a setting's `hint` is the rest of it. `version` is raised when installed projects should be offered the update - the Features panel can only offer an update it can see - and not for a change that only has to reach new installations. `nino` names the Nino versions the feature is written for; change it only when the feature really stops running on a version it named. A feature that reads a text key of the kernel's - `/project/company/general/name`, `/template/common/form/email`, `/_nino/webpage<uri>/title` - names the Nino that has the key in that form: `^1.4` (section 4b). `category` says what the feature is for, and is decided top-down by what a site owner wants rather than by how the feature is built - the rule is in Nino's [Features](https://github.com/dapeio/nino/blob/main/docs/features.md#categories) manual |
| `tests/<key>-smoke.php` | when present: the feature's own test covers the change; it loads the harness as described in section 5 |

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
  activations. Say so in the commit message and in the manual's `install`
  line when a project should copy it by hand.
- A change to files under `data/` that the feature owns needs `upgrade()`
  in the class and an entry under `data` in the manifest.

After editing:

1. Review `git diff --check` and the complete diff.
2. Run `php -l` over every changed PHP file and `node --check` over every
   changed JavaScript file.
3. Run `bin/check.sh` (or `NINO_ROOT=/path/to/nino bin/check.sh`). It
   validates every manifest through the checkout's kernel, runs the own
   test of every feature that checkout can run against it - a feature
   written for a newer Nino is listed with its reason and left out - then
   every test under `tests/`; the script is the list. It MUST pass before
   you report.
4. With the features copied into the checkout, run `phpstan analyse` and
   `npx eslint features` there - what CI does.
5. Report changed files, behaviour, tests, and any remaining limitation.

## 3. Repository map

| Path | Ownership |
| --- | --- |
| `features/<Name>/` | One feature, exactly what lands in a project's `features/`. The directory name is the class name `\Nino\Modules\<Name>` and MUST match `/^[A-Z][A-Za-z0-9]*$/`. Its documentation is the `manual` of its `feature.php`; the only other documents are the Template Builder handbook in `features/Templates/docs/` |
| `bin/catalogue.php` | The preview: reads every manifest through a Nino checkout and prints what the catalogue would list as JSON - key, name, description, `category`, `maturity`, version, `nino`, `php`, `requires`, directory - without an archive or a signature. A manifest Nino would skip fails the run. Defines `NINO_FEATURES_DIR` as this repository's `features/`, so the checkout's own directory is never what it reads. `bin/check.sh` and CI use it as the manifest check |
| `bin/build.php` | The build tool: `php bin/build.php <nino-checkout> <out-dir> [--base-url …] [--key private.pem]`. Validates every manifest the same way, builds `<out-dir>/<key>-<version>.tar.gz` for every feature on every run, as a plain ustar tar written by the script itself, every entry stamped with one fixed time - exactly one directory `<Name>/`, without `tests/`, `.git*`, `.DS_Store` and editor leftovers, sorted so a build is reproducible - and writes `<out-dir>/catalogue.json` in format 1 (see `\Nino\Catalogue` in Nino), one entry per feature, signed with `--key`. A feature that did not change gives the same bytes and its file is left alone; an archive no entry names is removed. The `catalogue.json` already in the directory is read for two things: `released` stays while an entry's `sha256` does, and `generated` stays while nothing else in the document changes - then neither `catalogue.json` nor its signature is rewritten |
| `bin/applicable.php` | `php bin/applicable.php <nino>` prints the directories of the features whose `nino` constraint the checkout's version satisfies (`\Nino\Features::satisfies()` of that checkout), one per line, and on STDERR the others with their reason (`Seo: skipped on Nino 1.3.2: needs ^1.4`). A feature written for a newer Nino claims nothing about an older one. `bin/check.sh`, CI, `tests/build-smoke.php` and `tests/keys-smoke.php` all ask it, so the question has one answer. `bin/catalogue.php` does not: it describes every feature of this repository |
| `bin/check.sh` | Copies every feature the checkout can run (`bin/applicable.php`) into it (`../nino` or `NINO_ROOT`), runs `bin/catalogue.php`, those features' tests and every test under `tests/` - its own lines are the list - and removes the copies again. A directory the checkout already carries is left alone |
| `bin/release.sh` | A release, run by the owner on their own machine: `bin/check.sh` (`--quick` skips it), the published catalogue fetched into `public/` from `NINO_CATALOGUE_TARGET` (`rsync -a --delete`, so `public/` is exactly the server's state), `bin/build.php` with `NINO_CATALOGUE_KEY` and `NINO_CATALOGUE_URL`, and `public/` put back with `rsync -a --delete --chmod=D755,F644` (`--dry-run` stops before that). It publishes what `main` carries, whether or not a version changed |
| `tests/keys-smoke.php` | The text key grammar over every feature. Part 1, against any checkout: what an install unit writes follows `/feature/<key>/<part>/<name>` or `/template/<category>/<part>/<name>`, reads the same in both languages, blacklists what its own code fills; no old key family is left in a shipped file; the key literals in the code name a namespace; no feature's code reads `/nino/locales/textfiles`. Part 2, against a Nino that has `\Nino\Modules\Template::category()` and for the features `bin/applicable.php` names: every key a template reads is a runtime fill, a key of the system or delivered, a template reads template keys of its own category or of `common` only, a template the kernel delivers too is byte for byte the kernel's. The vocabulary of the workbench is looked up where the checkout has one, as a note |
| `tests/legal-smoke.php` | The sections of the privacy policy the features bring (`install/elements/privacy.php`, section 4c). Part 1, against any checkout: each file is named in the manifest of its unit, brings no type of its own, has the same sections in `*`, `de_DE` and `en_US`, ids that are the feature's key or the key and a name, a position in the feature's range, only `p`, `br`, `ul`, `ol`, `li`, `strong`, `em` and `a` with a `#privacy-<id>` or `https://` link, no `&`, entity or `[`, only placeholders Nino's Legal module replaces. Part 2, against a Nino that has `\Nino\Modules\Legal`: the module's unit applied in a sandbox, then each feature's add-only - the sections are there, a second run changes nothing, no id is the module's, every anchor names a section, the field's own model leaves every text as it is, `Legal::contributions()` names them. Otherwise a line starting `note` |
| `tests/markup-smoke.php`, `tests/language-smoke.php`, `tests/escaping-smoke.php`, `tests/panels-smoke.php` | The rules every feature shares, read over the feature files: markup belongs in a template (section 4a), the text is English, an escape keeps what it cannot encode, a panel reaches the workbench the way it has to |
| `tests/build-smoke.php` | The build tool's own test over Nino's harness: a keypair per run, a signed build into a temporary directory, the archives' contents, the catalogue's fields, the signature, a second run that changes no byte and leaves `catalogue.json` and its signature untouched, a changed feature that gets new bytes under the same name and a new `released`, a stale archive that is removed, one entry per feature whatever the catalogue held before, the refusals - a category outside the kernel's six and a checkout without them among them - and an installation of the archives through `\Nino\Catalogue` |
| `tests/release-smoke.php` | `bin/release.sh` end to end from a copy of the repository, with `--quick`, a temporary key and a directory as `NINO_CATALOGUE_TARGET` (rsync works between two paths): the first run publishes every feature and a catalogue that verifies and parses, the second changes no byte, the third after a version bump of Hello replaces its archive and leaves every other one as it was, a dry run uploads nothing, and a file only `public/` holds is not put back. Skipped with a line where rsync is not installed |
| `.github/workflows/ci.yml` | The matrix: Nino `main` and Nino's latest tag. Each test `bin/check.sh` runs as a step of its own, and besides them a syntax check of every PHP and JavaScript file, Nino's `tests/features-smoke.php` with the features in place, PHPStan and ESLint - the workflow's steps are the list; `catalogue.json` kept as an artifact of the `main` run. A test run and nothing more: no release waits for it |
| `README.md`, `README.de.md` | The catalogue for humans, short: what it is, install, develop and test, write a feature, the release, the server, the signing key, a catalogue of your own, format 1. English is the primary version, German the author's; both have the same structure and identical commands and paths |
| `.gitignore` | Ignores `/nino/` (a checkout placed inside rather than beside), `*.patch`, `*.pem` (a key never enters the repository) and `/public/` (what `bin/release.sh` syncs and `bin/build.php` writes) |
| `LICENSE`, `.editorconfig` | MIT; tabs, LF, UTF-8, the same defaults Nino uses |

There is no `catalogue.json`, no archive and no `public/` in the repository:
they are generated, by `bin/catalogue.php` and `bin/build.php`, and published
by `bin/release.sh`. Do not commit one. A signing key is never written
anywhere but the owner's own machine, outside every repository.

## 4. What a feature MUST carry

Nino requires `feature.php` and `<Name>.php`, and so does this catalogue.
The manifest is stricter here, because a feature is published on its own:

| File | Requirement |
| --- | --- |
| `feature.php` | `key` (a slug), `name` (no two features may carry one, in any locale, case ignored - `bin/build.php` refuses a pair that does), `description`, `manual` (what the feature adds, which the panel draws on the Description tab of its screen, and the feature's only documentation: the `section => handle => line` map of Nino's [Features](https://github.com/dapeio/nino/blob/main/docs/features.md#the-manual) manual, as `features/Hello/feature.php` writes it - the handle as it is typed, every line a string or a `locale => string` map with `en_US` and `de_DE`; the sections are shortcodes, markup, routes, panel, callbacks and install; the older prose form is still read and no longer written), `category` (one of `content`, `ui`, `communication`, `marketing`, `security`, `system` - `bin/build.php` refuses anything else, and refuses a feature without one), `maturity` (optional: free text of at most 24 characters, a string or a `locale => string` map, drawn as a badge beside the name in the Features panel; `bin/build.php` publishes it where the manifest carries one, and a Nino that predates the key ignores it), `version` (`major.minor.patch`), `nino` (a version constraint, `^1.3` today, `^1.4` for a feature that reads a text key of the kernel's - section 4b), `requires`, `settings`, `data` - every key spelled out, even when empty, so a reader sees what the feature does not do - `maturity` is the one that may be left out |
| `<Name>.php` | the runtime class `\Nino\Modules\<Name>`; `init()` registers and outputs nothing; `adminPanels()` when there is a panel; `upgrade()` when a version changes the shape of its data; a `'/nino/admin/restore'` callback when its `data/` files must merge on restore |
| `Admin/Admin.php` | the panel, when there is one: `actions()`, `nav()`, `perm()`, `text()`, every action guarded with `\Nino\Admin\Admin::guardPerm()`; `assets()` named through `\Nino\Admin\Panels::relative()` so they move with the directory |
| `text/<locale>.php` | the panel's fills for every interface language Nino ships, `en_US` and `de_DE` |
| `templates/*.tpl` | the feature's own markup, when it draws anything: see section 4a. Not the same thing as `install/templates/`, which are the project's files, copied once |
| `install/` | the unit, when the feature ships templates, texts, routes or config defaults for the website; `install/manifest.php` in the wizard's library format |
| `install/elements/privacy.php` | the feature's section of the privacy policy, named under `elements` in the unit's manifest - **required** for a feature that processes personal data or loads something from a third party, section 4c |
| `tests/<key>-smoke.php` | optional; when present: the feature's own test over Nino's harness, section 5 |

A feature with only `feature.php` and `<Name>.php` is complete. Its
documentation is the `manual` of its manifest and the `hint` of its settings;
there is no README and no changelog per feature - the history is the commit
history - and a test is optional. An agent MUST NOT add a README, a changelog
or a test to a feature that has none unless asked.

A feature MUST NOT carry a `.htaccess`, a `composer.json`, a `package.json`,
a build output or a copy of anything from `_nino/`.

## 4a. Markup belongs in a template

PHP decides **what** is shown; a template decides **what it looks like**. This is
the framework's own rule (see `AGENTS.md` section 6 in dapeio/nino) and a feature
keeps it the same way:

```php
public const string TEMPLATES = '/features/<Name>/templates';

return str_replace(
	[ '[[greeting]]', '[[name]]' ],
	[ htmlspecialchars( $greeting, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' ), htmlspecialchars( $name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' ) ],
	self::template( $appData, 'hello' )
);
```

`features/Hello/` shows the whole of it - the constant, the `template()` reader
that goes through `\Nino\Filesystem`, and one `.tpl` with two tokens. Copy that.

- Every value is escaped **before** it is filled in, exactly as it was when the
  markup was a string in PHP. A template has no escaping of its own.
- `htmlspecialchars()` spells out `ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'`, every
  time. Without `ENT_SUBSTITUTE` it answers input that is not valid UTF-8 with
  `''`, and a value that renders as nothing is a bug nobody sees;
  `tests/escaping-smoke.php` reads every feature for a call that dropped it.
- Fill the tokens that carry **built markup last**: `str_replace()` works through
  its arrays in order, so a token after them is looked for in what they put in
  as well.
- Do not explain the file inside it. A template is output - what is in it is sent
  to whoever asked for the page. The explanation belongs in the class that fills
  it. A panel's own template (`templates/panel.tpl`) is the exception, since only
  a logged-in workbench user is ever sent it.
- A text fill (`[[/feature/hello/greeting/note]]`) stays in the template; the kernel resolves it
  when the result is rendered. That is the point of keeping the markup whole.

Three shapes MAY carry markup in PHP, and nothing else:

1. **A one-line fragment with `[[tokens]]`, declared once as a named property** at
   the top of the class, never built inside a method -
   `\Nino\Modules\Navigation::$html` is the shape to copy;
   `features/Design/Admin/Admin.php` is the one in this repository.
2. **An icon**, which is geometry rather than a view, and what the panel contract
   asks for as a string (`Admin::icon()`).
3. **A last-resort fallback** for when no template can be read at all.

Two things that look like exceptions and are not: a **document format** built
from data (`sitemap.xml` in `features/Seo/`) is a serialisation rather than a
view, and a **builder whose product is markup** (`features/Templates/`, which
composes `.tpl` source) is writing its output, not rendering itself. Both say so
where the markup is.

`tests/markup-smoke.php` holds the whole catalogue to this: it reads every
feature class and fails on a string that opens an html tag, unless the file is
one of the named exceptions in its own `ALLOWED` list - which carries the reason
beside the path. An exception that stops being needed fails it too, so the list
cannot outlive what it was for. Adding a file to that list is a decision to
argue for in the completion report, not a way to make the check pass.

## 4b. Text keys

A text key a feature ships has the form every key of Nino has (see the
[Features](https://github.com/dapeio/nino/blob/main/docs/features.md) manual and
Nino's `AGENTS.md`): `/<namespace>/<category>/<part>/<name>`, four segments,
each of lower-case words joined by hyphens (`[a-z0-9]+(-[a-z0-9]+)*`), in
English, with no language in them and no number as a name.

- **`/feature/<key>/<part>/<name>`** is a word of the feature's own function:
  what its code or its internal `templates/` print, set at request time or choose
  from a fixed set. `<key>` is the `key` of the manifest (`/feature/consent/action/save`).
  What the class fills at request time has no stored value and sits on the
  unit's `blacklist`. A word the code chooses between - the unit of a countdown,
  the outcome of a signup - belongs to the feature even where a template
  shows it, because that template is the project's to replace.
- **`/template/<category>/<part>/<name>`** is a word one template of the unit reads
  literally. `<category>` is the file name of that template without `.tpl`, prefix and
  all: `page-hello.tpl` is `page-hello`. A word more than one template reads is
  `/template/common/...` and the kernel's base unit delivers it - a feature reads it,
  and writes none (`/template/common/form/email` is the email field's label
  everywhere).
- **A feature delivers no `/project`, no `/module`, no `/_nino` and no
  `/template/common`**, and reads them as it likes. The words of its panel are
  `/_admin/...`, in `text/`, and stay as they are.
- **A list** with one word per entry has the identifier in the name
  (`/feature/modeswitch/mode/dark`); with several words per entry it is in the
  part (`/feature/consent/category-necessary/name`). A key is put together only
  from a whole segment or from the identifier at the end of such a part, and the
  feature's own test renders the words that can appear.
- **A Design frame** is the project's `frame-header.tpl` or `frame-footer.tpl`
  once applied, so it reads `/template/frame-header/...` and
  `/template/frame-footer/...`, which the base unit delivers; the Template
  Builder's keys are `/template/<the page template's name>/<section>/<name>`.
- **The project's text is in `/text`.** A feature that reads the text files
  reads `/text/global.php` and `/text/<locale>.php` - where the Text panel
  writes - and never `$appData['/nino/locales/textfiles']`, which Nino reads
  nowhere and keeps in its defaults only until 2.0.

`tests/keys-smoke.php` holds the catalogue to this. It does not accept an old
key form anywhere in a shipped file: a form that is renamed in Nino is renamed
here, in code, templates, texts and documents, in the same change.

## 4c. The privacy policy section

A feature that processes personal data - it stores something about a visitor,
sets a cookie, counts by address - or loads something from a third party
brings its section of the privacy policy: `install/elements/privacy.php`,
named under `elements` in its unit's manifest (`install/manifest.php`, which a
feature with nothing else to install carries just for this). Nino 1.4's Legal
module shows it in the type `privacy`; an older Nino ignores the key, so no
`nino` constraint changes for it. The recipe is in Nino's
[feature recipe](https://github.com/dapeio/nino/blob/main/docs/recipes/feature.md)
and its [Features](https://github.com/dapeio/nino/blob/main/docs/features.md)
manual; what this catalogue adds:

- **Add-only, and never a type.** The file has the buckets `*`, `de_DE` and
  `en_US` and nothing else - no `model`, no `title`. `\Nino\Elements::seed()`
  adds a section that is not there and never replaces one, so a section an
  editor changed stays and one deleted for good does not come back.
- **Ids and positions.** An id is the feature's `key` or the key and a name
  (`embed`, `embed-youtube`), the same in all three buckets. `order` is a
  position in the range of what the section is about: cookies and consent
  400-499, contact, forms and mail 500-599, statistics 600-699, embedded
  content 700-799, further features 800-899 - the module keeps 100-399 and
  900-999, and has sections at 400, 410, 500 and 510, so those two ranges are
  shared with it. The table of ranges is in `tests/legal-smoke.php`; a new feature
  adds its row.
- **Own words, German and English.** No sentence from a generator of legal
  texts or from a provider's privacy policy: a source line would be needed
  then. German addresses the reader as Du, capitalised. State only what the
  code does - the name and lifetime of a cookie, what is stored, for how long,
  which host is called - and let a placeholder name a fact of the website
  (`#/project/company/contact/email#`, only keys below `/project/company/` and
  `/project/website/general/`). Check every statement of fact against the code
  when you write it, and against the provider's own page for a provider.
- **The form the field gives.** The text is already what the sanitizer returns
  for a field with `blocks`: `p`, `br`, `ul`, `ol`, `li`, `strong`, `em` and
  `a`, a link only to `#privacy-<id>` or `https://`, no `&`, no entity, no `[`
  - or the form in the Elements panel reports an untouched section as unsaved.
- **Numbers from settings.** A section may name the default of a setting (180
  days, seven days). Whoever changes the value changes the section; the setting's
  `hint` says so, or - for a `config.php` key such as
  `/nino/newsletter/pending-days` - the manual's `elements/privacy.php` line.
- **The manual** names the file under `install` and says, in every feature
  that has one, that the section is a starting point and no legal advice. The
  notice in full is in Nino's `docs/development.md` ("Legal").
- **No dependency on the module.** A feature does not require `Legal`: the
  `elements` key is read by a Nino that has it and ignored by one that does
  not. What a feature does with the module itself - Consent's button, which a
  listener on `/nino/legal/section` appends to its own section, and its link
  to `\Nino\Modules\Legal::url()` - sits behind `class_exists()`, never
  `method_exists()`, which PHPStan reports as always false on a Nino without
  the class and as always true on one with it.

`tests/legal-smoke.php` holds the files to this.

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

A release is `bin/release.sh`, run by the owner on their own machine: it
publishes what `main` carries, so a feature that changed reaches new
installations with the next run, whether or not its `version` changed. A
version is raised when installed projects should be offered the update. An
agent prepares a release (the version where one is meant to go out,
`bin/check.sh`) and stops: it never runs the script, never creates or pushes
a tag - there are none - and never raises a version that is not meant to go
out with the next run.

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
  publishing side - `bin/build.php`, `bin/release.sh`, `tests/build-smoke.php`,
  `tests/release-smoke.php` - and nothing here makes a network request except
  `bin/release.sh`, which syncs with the server by rsync;
  `tests/release-smoke.php` syncs between two local directories only.

A feature that needs a kernel capability it does not have is blocked, not
patched: report the gap, do not copy kernel code into the feature.

## 8. Completion report format

Report changed files, the behaviour before and after, every command run
with its result (`bin/check.sh` at least - which includes
`tests/build-smoke.php` and `tests/release-smoke.php` - and against which
checkout), and any remaining limitation - a Nino version the feature
no longer runs on, a template a project has to copy by hand, a test that
does not exist yet. Name the patch file.
