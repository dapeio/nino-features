# Changelog

All notable changes to the Before/After feature are documented in this file.
A release is the tag `compare-<version>` of dapeio/nino-features.

## Unreleased

### Fixed

- **An invalid byte in a value rendered as nothing.** `htmlspecialchars()`
  answers input that is not valid UTF-8 with `''` unless `ENT_SUBSTITUTE` is
  among its flags, and every call here spelled the flags out without it.
  Every call carries it now, as the kernel's do, and
  `tests/escaping-smoke.php` reads every feature for the next one.

## 1.0.0 — Unreleased

First release.

- `[compare before="…" after="…"]` — two pictures of the same thing under one
  divider, with `alt`, `before-label`, `after-label`, `start` and `ratio` on the
  element being written.
- **The divider is an `<input type="range">.`** Mouse, finger and arrow keys all
  work without being written, and a screen reader gets a named control with a
  value. `compare.js` reads that value into `--nino-compare-position`; the
  clipping is the stylesheet's, so a drag is a paint rather than a call into
  JavaScript on every frame.
- Without JavaScript the same markup is two captioned pictures under one
  another — the layout the markup reads like, with `.nino-is-ready` the thing
  that turns it into a stack.
