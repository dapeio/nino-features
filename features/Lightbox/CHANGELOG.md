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
