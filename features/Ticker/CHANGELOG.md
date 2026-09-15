# Changelog

All notable changes to the Ticker feature are documented in this file.
A release is the tag `ticker-<version>` of dapeio/nino-features.

## Unreleased

### Fixed

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
