# Changelog

All notable changes to the Hello World feature are documented in this file.
A release is the tag `hello-<version>` of dapeio/nino-features.

## Unreleased

- **The panel uses the workbench's request helper, its status line, its wording
  of a failure and its question about unsaved input, where the Nino has them -
  and says so, being the feature to copy.** Every request goes through
  `Nino.adminUi.api`; the save line is the workbench's status line ("saving",
  "saved at 09:41", "unsaved changes", the error); the name nobody has saved is
  registered with the shell, so a log out or a language change asks **Save**,
  **Discard** or **Cancel** first, and opening the panel again does not draw the
  screen over it. Each is a check for what the Nino has, so the same script runs
  on 1.3.0 and later. On a Nino without the helper (1.3.x) the panel posts from
  the project's own directory, not from `/_admin/` at the root of the domain.
  `tests/hello-js-smoke.js` is new, and `hello-smoke.php` runs it where node is
  on the path. `nino` stays `^1.3`.

- Carries a `maturity` badge, `Example` / `Beispiel`: the Features panel of
  a Nino that reads the field draws it beside the name, and the catalogue
  entry carries it. A Nino from before the field ignores the key.

- Needs Nino `^1.3`, where the constraint said `^1.1`. The sectioned `manual`
  map this manifest carries is only read by a kernel newer than the
  `v1.2.0-beta` tag - `Features::manifest()` refuses it on the tagged one -
  and `^1.1` is satisfied by that kernel, so the catalogue offered the
  feature to an installation that could not then install it. `^1.3` names
  only a kernel that can read the manifest.

### Changed

- **The panel no longer names itself a second time.** The workbench opens
  every pane with a head that names the panel with the label `nav()` gave, so
  the screen's own "Hello World" stood one line under "Hello World". The
  screen opens with its hint now, the first line under the head, and says in
  `assets/admin.js` why a screen draws no heading of its own - the example is
  what a panel is copied from. The fill `/_admin/hello/title` is gone from
  both text files, and the test's check that the panel's words stay out of the
  project holds all of them rather than that one. A kernel from before the
  head draws no name over any pane, its own included, and the screen opens
  with the hint there too.

- **The example left its own template out of the checklist.** The README's
  directory listing and the two sentences that enumerate what a feature can
  carry - in the README and at the top of `feature.php` - still named the nine
  parts of the 1.0.0 release, without the `templates/hello.tpl` the greeting is
  filled from since; and the rule about escaping pointed at four lines of
  `doShortcode()` that are not four lines any more. In the class, the comment
  on `PATH` had ended up above `TEMPLATES` when that constant was added, and
  `init()` still said the stylesheet's `/features/...` path resolves against
  the project root, where the kernel this feature names resolves it against
  `\Nino\Features::dir()` (`\Nino\Filesystem::FEATURES_DIR`). A feature
  written to be copied is copied with its words, so the words are what
  changed - the code is untouched.

- **The greeting's markup is a template now.** `[hello]` filled a string; it
  fills `templates/hello.tpl` through a `template()` reader that goes through
  `\Nino\Filesystem` and says so in the log (`E_USER_WARNING`) when the file
  is missing - the shape every feature copies, see AGENTS.md, "Markup belongs
  in a template". The output is the same.

### Fixed

- **The upgrade hook could not be called.** `Hello::upgrade()` took three
  arguments and answered nothing, where the kernel's contract
  (`docs/features.md`) is `upgrade( array &$appData, string $fromVersion ):
  bool` and `\Nino\Features::activate()` calls it with the recorded version
  alone and reads `false` as a refusal. The first activation of a Hello
  newer than the one a project recorded would have ended in an
  `ArgumentCountError`. The hook has the kernel's signature now and answers
  `true`; the test calls it the way the kernel does, so the next drift is a
  red line. `greeting()`'s comment also said `setting()`'s fourth argument
  is the value before anybody saves - it answers for a name the schema does
  not declare, and the manifest's default answers first. Hello is what a
  feature is copied from, so both were being copied.

- **The runtime route overwrote a project's own page at the same address.**
  `Hello::init()` assigned `GET://hello` on every request without looking, so
  a project that had made `/hello` its own page in `config.php` got the
  feature's demo back over it on every single request - and deactivating the
  feature gave nothing back, because there was nothing left to give back. The
  route is registered only where the address is free now, which is the rule
  the install unit beside it has always followed: a feature adds what a
  project does not have.

- **An invalid byte in a value rendered as nothing.** `htmlspecialchars()`
  answers input that is not valid UTF-8 with `''` unless `ENT_SUBSTITUTE` is
  among its flags, and every call here spelled the flags out without it.
  Every call carries it now, as the kernel's do, and
  `tests/escaping-smoke.php` reads every feature for the next one.

## 1.0.0 — 2026-09-12

First release.

- A complete feature that does one small thing, written to be copied: a
  shortcode, a route, a panel, a setting, an install unit, text fills, an asset,
  stored data, an upgrade hook and a test — each exactly once.
- `[hello]` and `[hello name="Ada"]` greet; `/hello` is a page of its own,
  installed with the feature.
- The greeting is a setting the Features panel draws by itself; the name is
  stored by the feature's own panel. The README says where the line between the
  two runs, which is the one decision the example exists to make.
- `tests/hello-smoke.php` is 45 checks, laid out in the order a request meets
  the parts.
