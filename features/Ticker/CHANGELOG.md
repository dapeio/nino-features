# Changelog

All notable changes to the Ticker feature are documented in this file.
A release is the tag `ticker-<version>` of dapeio/nino-features.

## Unreleased

- Needs Nino `^1.3`, where the constraint said `^1.1`. The sectioned `manual`
  map this manifest carries is only read by a kernel newer than the
  `v1.2.0-beta` tag - `Features::manifest()` refuses it on the tagged one -
  and `^1.1` is satisfied by that kernel, so the catalogue offered the
  feature to an installation that could not then install it. `^1.3` names
  only a kernel that can read the manifest.

### Changed

- **"Wider than the box twice over" was never what the copies cover.**
  `ticker.js`'s file header, the docblock over `run()`, the comment beside
  the measurement ("Twice the box, not twice the row") and `Ticker.php`'s
  class docblock all said the row is copied until it is wider than the box
  twice over. `needed` has always been `row.clientWidth + one` - the box
  plus one length of the row, which is the distance the track travels plus
  the box it travels across, and which is what the README, the two tests and
  the rest of that same comment say. The four places say it too.

- **A check still said the two custom properties are written once.**
  `ticker-smoke.php`'s label read "what the script sets is the distance and
  the duration, once", and they are written again whenever the box has come
  to rest at a width its copies were not made for - which is the change
  `ticker.css`'s own header was corrected for. The label says what the check
  holds.

- **The "Asset bundling" note asked a project to do something it does not
  have to.** It said `/features/Ticker/assets/...` resolves against the
  project root, and that a project which moved its features elsewhere with
  `NINO_FEATURES_DIR` has to name the two sources under their real path in
  `/nino/html/assets` itself. `\Nino\Filesystem` resolves the virtual
  `/features` prefix against `\Nino\Features::dir()` - a branch of its own,
  older than the `^1.3` this manifest names - so the sources are found after
  a relocation like every other file a feature addresses that way.

### Fixed

- **A window that got wider left a stretch of nothing in the loop.** The
  copies are made to cover the box plus one length of the row, and they were
  made once, for the box as it was when the page loaded. A window widened
  afterwards was a box the row no longer reached across: in a box grown from
  600 to 1600 pixels, 936 of them were empty for the rest of every cycle. The
  rows are measured again once a resize has come to rest, and only where the
  box really is a different width than the one its copies were made for.

- **The loop jumped by one gap every time round** - the seam the copies are
  there to remove. The animation ran the width of the original row, which is
  the items plus the gaps *between* them; the copy that should stand where
  the original stood is one gap further along than that, because there is a
  gap between the last original and the first copy too. The distance is
  measured now, as where the first copy actually sits, so it is right for any
  gap a project sets.

- **A copy carried the original's ids and stayed in the tab order.** The
  copies are `aria-hidden`, which takes them out of what a screen reader
  reads but not out of what a keyboard walks through - so a row of five
  logos, copied four times, was twenty tab stops that all read the same, and
  every `id` in the row existed five times over. A copy loses its ids and its
  focusable parts are taken out of the tab order.

- **A row whose pictures have not loaded is left alone again.** The check for
  "nothing to show yet" looked at the track, which is as wide as its gaps
  even when everything in it is empty; it looks at the items themselves now.

## 1.0.0 — Unreleased

First release.

- `class="nino-ticker"` on a container with a `.nino-ticker-track` inside it — a
  row that runs and starts again without a seam, timed per element with
  `data-ticker-speed`, `-direction` and `-pause`.
- The row's own children are copied until they cover the box plus one length of
  the row, and the animation moves exactly one original width: the copy ends
  where the original stood, so starting again moves nothing. The copies are
  `aria-hidden` — the row is read once, which is how many times it is there.
- The movement is a CSS animation rather than a timer, so a browser runs it off
  the main thread and stops paying for it in a background tab.
- Stopped under the pointer and while something inside it has the keyboard
  focus. Standing still, and scrollable by hand, for a visitor who asked for less
  motion.
