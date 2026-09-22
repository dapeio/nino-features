# Changelog

All notable changes to the Copy to Clipboard feature are documented in this file.
A release is the tag `copy-<version>` of dapeio/nino-features.

## Unreleased

- Needs Nino `^1.3`, where the constraint said `^1.1`. The sectioned `manual`
  map this manifest carries is only read by a kernel newer than the
  `v1.2.0-beta` tag - `Features::manifest()` refuses it on the tagged one -
  and `^1.1` is satisfied by that kernel, so the catalogue offered the
  feature to an installation that could not then install it. `^1.3` names
  only a kernel that can read the manifest.

### Changed

- **The manual still offered `[copy block]` as a `<pre>`.** The Features
  panel's entry for the flag said "a block rather than a line - a `<pre>`,
  with the whitespace kept", and there has been no `<pre>` in
  `templates/copy.tpl` since the block form became a `<span>` that
  `copy.css` paints as one - which is the whole reason it was changed, so
  that a `[copy]` inside a paragraph keeps its button. The entry says what
  is written now, and why.

- **The "Asset bundling" note asked a project to do something it does not
  have to.** It said `/features/Copy/assets/...` resolves against the project
  root, and that a project which moved its features elsewhere with
  `NINO_FEATURES_DIR` has to name the two sources under their real path in
  `/nino/html/assets` itself. `\Nino\Filesystem` resolves the virtual
  `/features` prefix against `\Nino\Features::dir()` - a branch of its own,
  older than the `^1.3` this manifest names - so the sources are found after
  a relocation like every other file a feature addresses that way, the
  templates this feature reads through the same prefix included.

### Fixed

- **`[copy block]` inside a paragraph lost its button.** The block form wrote
  the text as a `<pre>`, and a `<pre>` - like any block element - inside a
  `<p>` is closed out of the paragraph by the html parser. In a browser the
  wrapping `<span>` was left empty and the text and the button became siblings
  of the paragraph, so `copy.js`, which looks for the button inside
  `.nino-copy`, never found it: the button stayed `hidden` for good. The
  markup is phrasing content throughout now - a `<span>` that `copy.css`
  paints as a block, keeping the whitespace and the code face the `<pre>` gave
  it. A project that styled `.nino-copy-text` as a `pre` element rather than by
  its class has to say the class instead.

- **A body, a `value=` or a `label=` that said "block" turned the line into a
  block.** The flag was looked for among all of the shortcode's arguments,
  which includes the values of the named ones, so `[copy]block[/copy]` came
  out as a block. It is read where the syntax puts a bare flag now: among the
  positional arguments.

- **An invalid byte in a value rendered as nothing.** `htmlspecialchars()`
  answers input that is not valid UTF-8 with `''` unless `ENT_SUBSTITUTE` is
  among its flags, and every call here spelled the flags out without it.
  Every call carries it now, as the kernel's do, and
  `tests/escaping-smoke.php` reads every feature for the next one.

## 1.0.0 — Unreleased

First release.

- `[copy]…[/copy]` — a copy button beside a piece of text, with `value`, `label`
  and `block`.
- What is shown and what is copied are two things: a number grouped so it can be
  read is copied without the grouping.
- Two ways of copying, because one of them is not everywhere: the clipboard API
  is secure-context only, so a site served over `http` has none. An API that
  refuses falls back to the older way before anything is said.
- What happened is said in the word on the button, not only in its colour.
- Without JavaScript the button is not shown at all and the text is left
  selectable, which is what it was before.
