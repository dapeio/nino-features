# Changelog

All notable changes to the Table of Contents feature are documented in this file.
A release is the tag `toc-<version>` of dapeio/nino-features.

## Unreleased

- Needs Nino `^1.3`, where the constraint said `^1.1`. The sectioned `manual`
  map this manifest carries is only read by a kernel newer than the
  `v1.2.0-beta` tag - `Features::manifest()` refuses it on the tagged one -
  and `^1.1` is satisfied by that kernel, so the catalogue offered the
  feature to an installation that could not then install it. `^1.3` names
  only a kernel that can read the manifest.

### Fixed

- **A second table of contents on the same page listed the first one's
  anchors.** A heading's words are read out of its `textContent`, and once a
  list has been built with anchors switched on, the `#` of the link the script
  appended is part of that `textContent` too. A page with two `[toc]` therefore
  came out with `Erstens#`, `Genauer#` and `Zweitens#` in the second list. The
  words are taken from the heading's own nodes now, with an anchor this script
  put there left out, so both lists say the same thing.

- **An invalid byte in a value rendered as nothing.** `htmlspecialchars()`
  answers input that is not valid UTF-8 with `''` unless `ENT_SUBSTITUTE` is
  among its flags, and every call here spelled the flags out without it.
  Every call carries it now, as the kernel's do, and
  `tests/escaping-smoke.php` reads every feature for the next one.

## 1.0.0 — Unreleased

First release.

- `[toc]` — a list of the page's own headings, with `levels`, `within` and
  `title` on the element being written, and `class="nino-toc-skip"` on a heading
  that should stay out of it.
- **Built in the browser.** A Nino page is assembled out of a template, sections,
  shortcodes and elements; what the headings finally are is only settled once all
  of that has run, and the finished page is the only place the answer is
  complete. The server writes an empty nav, `hidden`; a page whose script never
  runs keeps a nav that says nothing.
- Every listed heading is given an id made from its own words — and one the page
  already had is left exactly as it was, because that one may be linked to from
  somewhere else. Two headings with the same words get two different ids.
- An anchor on every heading, so a passage can be linked to; it stays reachable
  by keyboard rather than being a hover-only affordance.
- The section being read is marked with `aria-current`, so the mark and the
  announcement are one fact. Scrolling costs one animation frame at most.
