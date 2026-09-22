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

### Changed

- **The manual promised the parts in the order they are written.** The
  Features panel's entry for `units=` said "which parts are shown, in that
  order", and `Countdown::units()` has always answered a subset of `UNITS`
  in `UNITS`' order - largest first, because "3 Minuten 2 Tage" is not a
  duration anybody reads, which is what the README says and what
  `countdown-smoke.php` holds for `units="hours,days"`. The entry says that
  instead.

- **`countdown.js` still carried "the site's own offset".** Its file header
  described the moment the server writes as an ISO-8601 string with the
  site's own offset in it - the phrase the `tz=` change took out of the
  README, the manual and `Countdown.php`, because Nino declares no timezone
  and there is no site offset to write. The header names the two offsets
  there really are: the one `tz=` gave, or the one the php process runs in.

- **The README offered `done=""` as a way back to the date.** The attribute
  table said that where the shortcode writes `done=""` the date itself comes
  back. `doShortcode()` trims the value and falls back to
  `[[/countdown/done]]` for an empty one, so `done=""` renders exactly what
  writing no `done=` at all renders - the Text panel's sentence. The way to
  the date is to empty that fill in the Text panel, which leaves
  `data-countdown-done=""` on the element and is the branch `finish()` puts
  the date back on. The table says that.

- **`countdown-smoke.php`'s own header did not mention the half it grew.**
  The README names the timezone a wall-clock moment is read in among the
  things that test holds, and it does - the file's description of itself
  still listed only the units, the formats and the broken moments.

- **The "Asset bundling" note asked a project to do something it does not
  have to.** It said `/features/Countdown/assets/...` resolves against the
  project root, and that a project which moved its features elsewhere with
  `NINO_FEATURES_DIR` has to name the two sources under their real path in
  `/nino/html/assets` itself. `\Nino\Filesystem` resolves the virtual
  `/features` prefix against `\Nino\Features::dir()` - a branch of its own,
  older than the `^1.3` this manifest names - so the sources are found after
  a relocation like every other file a feature addresses that way, the two
  templates this feature reads through the same prefix included.

### Fixed

- **"The site's own timezone" was the server's, and there is no such thing as
  the site's.** `moment()` read a wall-clock `to="2026-12-24 18:00"` with
  `new \DateTimeImmutable( $to )`, which uses the php process's default
  timezone - and Nino declares none in `\Nino\AppData::DEFAULTS` and calls no
  `date_default_timezone_set()`, so on an ordinary installation that is `UTC`.
  A German site counting down to 18:00 on Christmas Eve therefore wrote
  `2026-12-24T18:00:00+00:00` and counted to 19:00 Berlin time, an hour late,
  with nothing on the page to show it. The shortcode says which timezone now -
  `[countdown to="2026-12-24 18:00" tz="Europe/Berlin"]` - which is where it
  belongs, beside the moment itself: a sale ending in Berlin and a conference
  opening in Lisbon are not the same countdown, and this feature has no
  settings for the same reason. Without `tz` the process default is still what
  is used, and the README and the manual say that instead of promising a site
  timezone; a `to` that carries its own offset is unaffected, as it always was.
  A `tz` php does not know renders nothing and says so in the log, the way an
  unreadable date does - a counter an hour off looks right, which is worse than
  one that is not there. And a `<time>` that had stopped being a time kept
  saying it was one: when the moment passes and the shortcode was given a
  `done` sentence, `finish()` wrote that sentence into the
  `<time class="nino-countdown-date">` and left
  `datetime="2026-12-24T18:00:00+01:00"` on it, so the element told every
  parser, calendar and screen reader that "The time has come" IS that instant.
  The attribute is removed with the text it belonged to; a countdown given no
  sentence puts its date back and keeps it, because there the element still
  says what the attribute claims.

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
