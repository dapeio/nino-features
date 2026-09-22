# Changelog

All notable changes to the Before/After feature are documented in this file.
A release is the tag `compare-<version>` of dapeio/nino-features.

## Unreleased

- Needs Nino `^1.3`, where the constraint said `^1.1`. The sectioned `manual`
  map this manifest carries is only read by a kernel newer than the
  `v1.2.0-beta` tag - `Features::manifest()` refuses it on the tagged one -
  and `^1.1` is satisfied by that kernel, so the catalogue offered the
  feature to an installation that could not then install it. `^1.3` names
  only a kernel that can read the manifest.

### Changed

- **The "Asset bundling" note asked a project to do something it does not
  have to.** It said `/features/Compare/assets/...` resolves against the
  project root, and that a project which moved its features elsewhere with
  `NINO_FEATURES_DIR` has to name the two sources under their real path in
  `/nino/html/assets` itself. `\Nino\Filesystem` resolves the virtual
  `/features` prefix against `\Nino\Features::dir()` - a branch of its own,
  older than the `^1.3` this manifest names - so the sources are found after
  a relocation like every other file a feature addresses that way. The
  comment over `TEMPLATES` in `Compare.php` said as much four lines above the
  one that said otherwise; the README and that comment say it now too.

### Fixed

- **The control was laid over the caption as well as over the pictures.** It
  is stretched over the pair to be what a visitor drags, and it was stretched
  over the whole `<figure>` - which is the frame *plus* the caption under it.
  So its thumb rode below the middle of the picture (12 px with a one-line
  caption, 20 px with one that wraps to two), and the caption sat under an
  invisible control: text that could not be selected, and a line that dragged
  the comparison when it was pressed. The control takes its height from the
  same ratio the frame does now, so it covers the pictures and nothing else.

- **An invalid byte in a value rendered as nothing.** `htmlspecialchars()`
  answers input that is not valid UTF-8 with `''` unless `ENT_SUBSTITUTE` is
  among its flags, and every call here spelled the flags out without it.
  Every call carries it now, as the kernel's do, and
  `tests/escaping-smoke.php` reads every feature for the next one.

## 1.0.0 — Unreleased

First release.

- `[compare before="…" after="…"]` — two pictures of the same thing under one
  divider, with `alt`, `before-label`, `after-label`, `start` and `ratio` on the
  element being written.
- **The divider is an `<input type="range">.`** Mouse, finger and arrow keys all
  work without being written, and a screen reader gets a named control with a
  value. `compare.js` reads that value into `--nino-compare-position`; the
  clipping is the stylesheet's, so a drag is a paint rather than a call into
  JavaScript on every frame.
- Without JavaScript the same markup is two captioned pictures under one
  another — the layout the markup reads like, with `.nino-is-ready` the thing
  that turns it into a stack.
