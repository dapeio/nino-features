# Changelog

All notable changes to the Copy to Clipboard feature are documented in this file.
A release is the tag `copy-<version>` of dapeio/nino-features.

## Unreleased

- Needs Nino `^1.3`, where the constraint said `^1.1`. The sectioned `manual`
  map this manifest carries is only read by a kernel newer than the
  `v1.2.0-beta` tag - `Features::manifest()` refuses it on the tagged one -
  and `^1.1` is satisfied by that kernel, so the catalogue offered the
  feature to an installation that could not then install it. `^1.3` names
  only a kernel that can read the manifest.

### Fixed

- **An invalid byte in a value rendered as nothing.** `htmlspecialchars()`
  answers input that is not valid UTF-8 with `''` unless `ENT_SUBSTITUTE` is
  among its flags, and every call here spelled the flags out without it.
  Every call carries it now, as the kernel's do, and
  `tests/escaping-smoke.php` reads every feature for the next one.

## 1.0.0 — Unreleased

First release.

- `[copy]…[/copy]` — a copy button beside a piece of text, with `value`, `label`
  and `block`.
- What is shown and what is copied are two things: a number grouped so it can be
  read is copied without the grouping.
- Two ways of copying, because one of them is not everywhere: the clipboard API
  is secure-context only, so a site served over `http` has none. An API that
  refuses falls back to the older way before anything is said.
- What happened is said in the word on the button, not only in its colour.
- Without JavaScript the button is not shown at all and the text is left
  selectable, which is what it was before.
