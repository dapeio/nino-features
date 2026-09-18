# Changelog

All notable changes to the Reading Progress feature are documented in this file.
A release is the tag `progress-<version>` of dapeio/nino-features.

## Unreleased

- Needs Nino `^1.3`, where the constraint said `^1.1`. The sectioned `manual`
  map this manifest carries is only read by a kernel newer than the
  `v1.2.0-beta` tag - `Features::manifest()` refuses it on the tagged one -
  and `^1.1` is satisfied by that kernel, so the catalogue offered the
  feature to an installation that could not then install it. `^1.3` names
  only a kernel that can read the manifest.

## 1.0.0 — Unreleased

First release.

- `class="nino-progress"` on an empty element — a thin bar across the top of the
  window, over the whole page or over the one element `data-progress-of` names.
- What is travelled is the element's height less one screenful: a text is read
  when its last line has been, not when its bottom edge reaches the top of the
  window. An element shorter than the screen is finished the moment it is on it.
- `data-progress-label` decides what a screen reader gets: with it, a named
  progressbar with a value; without it, `aria-hidden`, because a progressbar with
  no name is a number nobody asked for.
- Nothing is on the page until something has been measured, the width is a custom
  property so a scroll costs a paint, and scrolling costs one animation frame at
  most.
