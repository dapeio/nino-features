# Changelog

All notable changes to the Ticker feature are documented in this file.
A release is the tag `ticker-<version>` of dapeio/nino-features.

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
