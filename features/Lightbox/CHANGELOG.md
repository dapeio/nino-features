# Changelog

All notable changes to the Lightbox feature are documented in this file. The
format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), the
versions [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## Unreleased

- Needs Nino `^1.3`, where the constraint said `^1.1`. The sectioned `manual`
  map this manifest carries is only read by a kernel newer than the
  `v1.2.0-beta` tag - `Features::manifest()` refuses it on the tagged one -
  and `^1.1` is satisfied by that kernel, so the catalogue offered the
  feature to an installation that could not then install it. `^1.3` names
  only a kernel that can read the manifest.

### Added

- **The three `data-label-*` attributes had nothing holding them.** The
  README names them as how a page that is not in English gives the controls
  their words, since a static asset cannot read a textfill, and neither test
  looked at a single `aria-label`. `lightbox-js-smoke.js` now holds that
  every control an overlay offers is named, that the dialog around them is,
  and that a link carrying its own three words is where those names come
  from.

### Changed

- **The "Asset bundling" chapter asked a project to do something it does not
  have to.** It said `/features/Lightbox/assets/...` resolves against the
  project root, and that a project which moved its features directory with
  `NINO_FEATURES_DIR` has to add the two files under the path they actually
  live at instead. `\Nino\Filesystem` resolves the virtual `/features`
  prefix against `\Nino\Features::dir()` - a branch of its own, older than
  the `^1.3` this manifest names - so the two files are found after a
  relocation and there is nothing to add anywhere. The chapter and
  `Lightbox::init()`'s comment say that.

### Fixed

- **The lightbox vanished instead of fading out.** `close()` waited for a
  `transitionend` on the overlay to take it out of the document, but that
  event bubbles - and the close button is always in the middle of its own
  press transition when it is the thing that closed the lightbox. So the
  first report to arrive was the button's, after its `.15s` rather than the
  overlay's `.22s`, and in a real browser the overlay left after roughly
  120 ms with its opacity still at 0.64 - halfway through a fade nobody got
  to see. Only the overlay's own transition ends the closing now; the fade
  runs its full length and the timer stays the fallback for a browser that
  skipped the transition.

## 1.0.0 — 2026-09-09

First release.

- A link carrying `data-lightbox` opens its image full screen, with every
  other link of the same group as the set: arrows, buttons and swipe between
  them, both ends wrapping, the two neighbours preloaded.
- Captions from `data-caption`, the `<img>`'s `alt` or the link's `title` -
  never from a filename.
- Escape, the close button and the backdrop close it; the focus is trapped
  while it is open and goes back to the link that opened it afterwards.
- Nothing in the stylesheet matches before the script opens something, so a
  page without JavaScript keeps a working link to a bigger picture, and a
  reduced-motion preference stops the animation rather than the lightbox.
