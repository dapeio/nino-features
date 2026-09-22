# Changelog

All notable changes to the External Embeds feature are documented in this file.
A release is the tag `embed-<version>` of dapeio/nino-features.

## Unreleased

- Needs Nino `^1.3`, where the constraint said `^1.1`. The sectioned `manual`
  map this manifest carries is only read by a kernel newer than the
  `v1.2.0-beta` tag - `Features::manifest()` refuses it on the tagged one -
  and `^1.1` is satisfied by that kernel, so the catalogue offered the
  feature to an installation that could not then install it. `^1.3` names
  only a kernel that can read the manifest.

### Changed

- **The manual counted two words where the unit carries four.** The Features
  panel's entry for the install unit said "the two sentences the surface
  carries, into the Text panel", and `install/text/<locale>.php` has four
  fills in it: the two on the surface (`[[/embed/load]]`,
  `[[/embed/note]]`), the `<noscript>` way out (`[[/embed/open]]`) and the
  name the frame is given where the shortcode wrote no `title=`
  (`[[/embed/frame]]`). The README and `embed-smoke.php` both say four; the
  entry says four now, and which they are.

- **The "Asset bundling" note asked a project to do something it does not
  have to.** It said `/features/Embed/assets/...` resolves against the
  project root, and that a project which moved its features elsewhere with
  `NINO_FEATURES_DIR` has to name the two sources under their real path in
  `/nino/html/assets` itself. `\Nino\Filesystem` resolves the virtual
  `/features` prefix against `\Nino\Features::dir()` - a branch of its own,
  older than the `^1.3` this manifest names - so the sources are found after
  a relocation like every other file a feature addresses that way, the two
  templates this feature reads through the same prefix included.

### Fixed

- **An invalid byte in a value rendered as nothing.** `htmlspecialchars()`
  answers input that is not valid UTF-8 with `''` unless `ENT_SUBSTITUTE` is
  among its flags, and every call here spelled the flags out without it.
  Every call carries it now, as the kernel's do, and
  `tests/escaping-smoke.php` reads every feature for the next one.

## 1.0.0 — Unreleased

First release.

- `[embed youtube="…"]`, `[embed vimeo="…"]` and `[embed url="https://…"]` — a
  third-party frame as a surface the visitor presses, with `title`, `poster` and
  `ratio` on the element that is being embedded.
- **The iframe is built, not hidden.** A hidden iframe is still fetched: one
  inside a container with `hidden`, with `display:none` or with
  `visibility:hidden` loads exactly like a visible one, so the pattern of writing
  the frame and hiding it gives the visitor's address to the provider before
  anybody is asked. The address is carried in `data-embed-src` and `embed.js`
  creates the frame when it is released, so there is no request to suppress.
- Two things release one, both the visitor's: a press, and — where the Consent
  feature is installed and the settings name a category — consent they already
  gave. The two features meet over `<html data-consent>` and the `nino:consent`
  event, so neither imports the other and Consent is not a requirement.
- No thumbnail is ever fetched from the provider. A poster is one of the
  project's own images or none; a surface without one is a plain ground with the
  play mark and the host it would talk to.
- The surface is the kernel's own `.nino-video-poster`/`.nino-video-play`, in
  `Nino.css` since 1.0 with nothing driving them until now.
