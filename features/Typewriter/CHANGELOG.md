# Changelog

All notable changes to the Typewriter feature are documented in this
file. A release is the tag `typewriter-<version>` of dapeio/nino-features.

## Unreleased

- **`Typewriter::init()`'s comment said the opposite of the README beside
  it.** It read that `/features/Typewriter/assets/...` resolves against the
  project root and reaches this feature's own copy "as long as features/
  sits where it does by default (NINO_FEATURES_DIR unmoved)", and pointed at
  the README's "Asset bundling" note for the relocated case - where that
  note says, correctly, that `\Nino\Filesystem::path()` resolves
  `/features/...` against the features directory wherever
  `NINO_FEATURES_DIR` put it. The comment says what the kernel does.

- **"Every rule hangs off `.nino-typewriter-line`" named one of four
  classes.** The README and `typewriter.css`'s own header both said it, and
  `.nino-typewriter-rest`, `.nino-typewriter-cursor` and
  `.nino-typewriter-reader` carry rules of their own. They are all classes
  the script writes, which is what the sentence was there to say and what
  keeps the stylesheet from matching anything before it runs, so both say
  that instead.

- The class file's own docblock named `Modules\\Typewriter`, with the
  backslash doubled, where every other feature's names `Modules\<Name>`.

- **A selector one container could not be read with stopped every typewriter
  after it on the page.** `data-typewriter-lines` went to `querySelectorAll()`
  unread, and a selector the browser refuses is answered there with a
  `SyntaxError` rather than with no elements - which left `run()`, and with it
  the loop over the page's containers. A `data-typewriter-lines="p:"` on the
  first one therefore left the page without a single typewriter. The selector
  is read like every other attribute now: one that cannot be read keeps the
  default, `p`, and costs that one attribute.

- Needs Nino `^1.3`, where the constraint said `^1.1`. The sectioned `manual`
  map this manifest carries is only read by a kernel newer than the
  `v1.2.0-beta` tag - `Features::manifest()` refuses it on the tagged one -
  and `^1.1` is satisfied by that kernel, so the catalogue offered the
  feature to an installation that could not then install it. `^1.3` names
  only a kernel that can read the manifest.

### Changed (behaviour)

- **A typewriter types once and stops, instead of looping forever.** Every
  `.nino-typewriter` whose `data-typewriter-loop` is missing or empty looped
  forever until now. Afterwards it types each line once and stops. The last
  line stays visible and complete, and its cursor is removed, so the blinking
  ends. Earlier lines stay faded out (`opacity: 0`; their text stays readable
  to screen readers through the reader spans), so a three-line headline ends
  showing only its last line. A one-line typewriter types once and stays.
  `data-typewriter-loop="0"`, `"false"`, `"off"` and `"no"` are unchanged;
  any other non-empty value (`1`, `true`, `on`, `yes`) keeps the old endless
  loop; spaces around the value are ignored, so `" 0"` is `"0"` and `" "` is
  empty, as in `data-ticker-loop`. The way back for an existing page is
  `data-typewriter-loop="1"` on its container. No JavaScript and reduced
  motion are unchanged. Nothing the server sends changes, so the page cache is unaffected, and the new script
  reaches browsers through the bundle's `?v=` hash. WCAG 2.2.2 is why: an
  endless loop is the plainest case of movement that has to be pausable; one
  pass ends by itself, but a pass longer than five seconds still needs
  `data-typewriter-toggle` to conform.

### Added

- **A pause button, on request.** `data-typewriter-toggle="<label>"` on a
  container puts a button after it - its next sibling, never a child, so a
  heading used as a typewriter does not take the button into its accessible
  name. A press pauses the typing and the cursor's blinking
  (`aria-pressed`, and `nino-is-paused` on the container) and the next one
  takes it up again with the full delay of the step that was waiting. The
  button is removed once nothing moves any more, after the last line of a
  single pass - the keyboard focus, where the button has it then, moves to the
  container (`tabindex="-1"`) instead of falling back to the top of the
  page. It wears the kernel's `nino-btn` classes; `typewriter.css` adds its
  pressed state. No button is drawn for an empty label, for one that
  still holds a fill nobody resolved, or under `prefers-reduced-motion`.

- **An install unit with one text fill, `[[/typewriter/toggle]]`** - *Pause
  animation* / *Animation pausieren* - the label for the button. Activation,
  and the update, merge it add-only into `text/<locale>.php` for every
  available locale; a locale added later needs the key by hand. The fill goes
  into the page as it stands, so a label must not contain a double quote.

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
