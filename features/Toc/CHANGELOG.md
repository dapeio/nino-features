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

### Added

- **Nothing held the half of the id rule the README states.** "An id is
  checked against the whole page rather than just the list" is what keeps a
  listed heading off an id some other element already carries - a heading
  marked `nino-toc-skip`, say - and only the collision between two listed
  headings had a check. `toc-js-smoke.js` holds both now.

### Changed

- **The anchors setting said the opposite of what switching it off does.**
  The hint in the manifest read "off: only the list's own headings get one,
  and only so the list can reach them", and off draws no anchor at all -
  `build()` calls `anchor()` only while `data-toc-anchors` is `1`, which is
  what `toc-js-smoke.js` holds and what the README's own settings table
  says. What the headings keep either way is their ids. The hint says that.

- **"Every heading of a page" was never every heading of a page.** The same
  hint, the README's settings table and `toc.js`'s file header all promised
  a link on every heading of a page that has a list; `build()` anchors the
  headings it listed, so one marked `nino-toc-skip` gets none - which the
  README says two chapters earlier - and neither does one of a level the
  list was not built from. All three say "every heading the list holds".

- **The "Asset bundling" note asked a project to do something it does not
  have to.** It said `/features/Toc/assets/...` resolves against the project
  root, and that a project which moved its features elsewhere with
  `NINO_FEATURES_DIR` has to name the two sources under their real path in
  `/nino/html/assets` itself. `\Nino\Filesystem` resolves the virtual
  `/features` prefix against `\Nino\Features::dir()` - a branch of its own,
  older than the `^1.3` this manifest names - so the sources are found after
  a relocation like every other file a feature addresses that way, the
  template this feature reads through the same prefix included.

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
