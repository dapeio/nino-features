# Changelog

All notable changes to the Forms feature are documented in this file. The
format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), the
versions [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## Unreleased

- Needs Nino `^1.3`, where the constraint said `^1.1`. The sectioned `manual`
  map this manifest carries is only read by a kernel newer than the
  `v1.2.0-beta` tag - `Features::manifest()` refuses it on the tagged one -
  and `^1.1` is satisfied by that kernel, so the catalogue offered the
  feature to an installation that could not then install it. `^1.3` names
  only a kernel that can read the manifest.

### Changed

- **Four sentences named something the code does not.** The README dated the
  form engine to Nino 1.1, where `/nino/form/forms` arrived with 1.2.0-beta,
  and listed the directory's contents without the `templates/` the form's
  markup lives in now. In the class, the docblock still said the guards answer
  418 "for all of them" although the rate limit has answered 429 since the
  feature stopped replacing the endpoint, `_blocked()` counted four reserved
  field names where `\Nino\Form::RESERVED` holds seven, and the comment on
  `ROUTE` had ended up above `TEMPLATES` when that constant was added. The
  reverse-proxy note also had "one visitor's four submissions" turning the
  form off for everybody, a number neither cap carries - this feature's
  allowance is ten a hour by default and the kernel's mail cap is five - so it
  names the allowance rather than a number now. Words only - the code is
  unchanged.

### Fixed

- **An invalid byte in a value rendered as nothing.** `htmlspecialchars()`
  answers input that is not valid UTF-8 with `''` unless `ENT_SUBSTITUTE` is
  among its flags, and every call here spelled the flags out without it.
  Every call carries it now, as the kernel's do, and
  `tests/escaping-smoke.php` reads every feature for the next one.

- **A field label was drawn as markup while a select option was drawn as
  text.** Both are the same kind of value - a fill key, or a word an operator
  typed into the Forms panel - and both are rendered so a form can be
  localised. The option escaped what came out of that, the label did not, so
  one form treated one kind of value two ways. Whichever of the two is wrong
  it is the label: the `<label>` is the template's markup, and a form
  definition is not where more of it comes from.

- **Behind a reverse proxy the rate limit counted every visitor as one.**
  Both halves of the per-address cap - the counter written after an accepted
  submission and the check in front of the next one - asked
  `\Nino\Http::getClientIp()` without the app data it needs to resolve an
  address, so behind a proxy they shared one bucket and four submissions by
  anyone turned the form off for everybody. Both pass `$appData` now, so a
  project that named its proxies under `/nino/http/proxies` counts the
  visitor behind them. Needs the kernel change that added the key.

## 1.0.0 — 2026-09-08

First release.

- `[form]` and `[form key="…"]`, drawing a form from its definition under
  `/nino/form/forms` - the markup the shared `.nino-form` script drives, with
  labels resolved through the text system.
- The **Forms** panel: the forms of `config.php` as a list, one form's fields
  on a screen of its own, and the two keys that decide how long submissions
  are kept and whether they are kept at all.
- Three spam guards on the kernel's own route callback at priority 1: the
  moment a form was drawn, a blocklist over every value, and an hourly
  allowance per client address that only accepted submissions spend.

It extends Nino's form endpoint rather than replacing it: `\Nino\Form` stays
the engine and `\Nino\Modules\Form` keeps the route, so a project that
switches this feature off keeps every form it defined, every submission it
recorded, and its Submissions panel.
