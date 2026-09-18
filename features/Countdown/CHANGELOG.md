# Changelog

All notable changes to the Countdown feature are documented in this file.
A release is the tag `countdown-<version>` of dapeio/nino-features.

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

- `[countdown to="…"]` — the time left until a moment, with `units`, `format`
  and `done` on the element being written.
- The moment goes out **once**, as an ISO-8601 string with the site's own offset,
  so a reader in another timezone counts down to the same instant rather than the
  same wall clock. The arithmetic is the browser's: a page cached for an hour
  would otherwise be an hour wrong.
- Without JavaScript what stands there is the date itself, in a
  `<time datetime="…">` — written out for a reader and machine-readable for
  everything else. A countdown that cannot count is still a date.
- Every unit is named in both its forms, and both reach the browser as data
  attributes on the part they belong to. "1 days" is what a counter says
  otherwise, and a static asset cannot read a text fill.
- One timer serves every countdown on a page, and stops when the last one has run
  out. A page opened on a countdown that has already passed starts none at all.
