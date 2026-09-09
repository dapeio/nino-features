# Changelog

All notable changes to the Typewriter feature are documented in this
file. A release is the tag `typewriter-<version>` of dapeio/nino-features.

## 1.0.0 — 2026-09-08

- First release: `.nino-typewriter` types its lines - the container's own
  `<p>`s - one after the other, with the cursor at the writing head, looping
  or stopping on the last line.
- Timed per element: `data-typewriter-lines`, `-start` (`view`/`load`),
  `-start-delay`, `-speed`, `-hold`, `-exit` (`fade`/`backspace`), `-fade`,
  `-backspace-speed`, `-pause`, `-loop` and `-cursor`. An unreadable or
  negative value keeps the default instead of timing the animation with it.
- Nothing moves while it writes: the untyped rest of a line keeps its place
  (`visibility: hidden`), the lines share one grid cell, and the cursor is
  zero-width - so a line never rewraps and the text below a typewriter is
  never pushed down a row.
- The typed text and the cursor are `aria-hidden`, with each line's complete
  text beside them for a screen reader; under `prefers-reduced-motion`, and
  wherever JavaScript does not run, the markup is left exactly as it is -
  paragraphs below one another.
- `typewriter.css`/`typewriter.js` ship through the project's own
  `/.cache/style.css`/`/.cache/script.js` bundles
  (`\Nino\Html::addAsset()`), the same mechanism the kernel uses for
  `Nino.css`/`Nino.js`. No shortcode, no route, no settings, no state.
