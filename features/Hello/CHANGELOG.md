# Changelog

All notable changes to the Hello World feature are documented in this file.
A release is the tag `hello-<version>` of dapeio/nino-features.

## Unreleased

- Needs Nino `^1.3`, where the constraint said `^1.1`. The sectioned `manual`
  map this manifest carries is only read by a kernel newer than the
  `v1.2.0-beta` tag - `Features::manifest()` refuses it on the tagged one -
  and `^1.1` is satisfied by that kernel, so the catalogue offered the
  feature to an installation that could not then install it. `^1.3` names
  only a kernel that can read the manifest.

### Changed

- **The greeting's markup is a template now.** `[hello]` filled a string; it
  fills `templates/hello.tpl` through a `template()` reader that goes through
  `\Nino\Filesystem` and says so in the log (`E_USER_WARNING`) when the file
  is missing - the shape every feature copies, see AGENTS.md, "Markup belongs
  in a template". The output is the same.

### Fixed

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
