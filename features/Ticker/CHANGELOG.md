# Changelog

All notable changes to the Ticker feature are documented in this file.
A release is the tag `ticker-<version>` of dapeio/nino-features.

## Unreleased

- Needs Nino `^1.3`, where the constraint said `^1.1`. The sectioned `manual`
  map this manifest carries is only read by a kernel newer than the
  `v1.2.0-beta` tag - `Features::manifest()` refuses it on the tagged one -
  and `^1.1` is satisfied by that kernel, so the catalogue offered the
  feature to an installation that could not then install it. `^1.3` names
  only a kernel that can read the manifest.

### Changed (behaviour)

- **A ticker runs one cycle, once it has been scrolled into view, instead of
  an endless loop from page load.** Every `.nino-ticker` ran an endless loop
  from page load until now. Afterwards it waits until it is scrolled into view
  and runs exactly one cycle: one original row width plus one gap at
  `data-ticker-speed` (three items of 200 px plus gaps = 696 px at 40 px/s =
  17.4 s). It stops on a frame identical to its first, because the copy stands
  where the original stood, so nothing jumps. It then stands still, with the
  items past the box edge cut off as in any frame before it.
  `data-ticker-loop="1"` restores the endless loop - read the way
  `data-typewriter-loop` is read: missing or empty is the default, `0`, `false`,
  `off` and `no` say it too, anything else asks for the loop - and the script
  writes the result as the class `nino-is-looping`. `data-ticker-speed`,
  `-direction` and `-pause`, the stop under the pointer and under keyboard
  focus, reduced motion and no-JavaScript behaviour are unchanged. The markup
  and the server output do not change, so the page cache is unaffected; the
  new script reaches browsers through the bundle's `?v=` hash. WCAG 2.2.2 is
  why: an endless loop is the plainest case of movement that has to be
  pausable; one pass ends by itself, but a pass longer than five seconds
  still needs `data-ticker-toggle` to conform.

### Added

- **The viewport start.** A row is measured at once but held
  (`nino-is-waiting`, `animation-play-state: paused`) until an
  `IntersectionObserver` has seen it, and starts at once where there is none
  - a footer row would otherwise be through its single pass before anybody
  scrolled to it. `nino-is-running` still means "measured".

- **When a pass ends it stays ended.** On the track's own `animationend` the
  row is marked `nino-is-done`, which the stylesheet turns into
  `animation: none`, so a later resize - which sets the duration again - can
  never start it over. A finished row is not measured or copied again.

- **A pause button, on request (WCAG 2.2.2).** `data-ticker-toggle="<label>"`
  puts a button after the row - its next sibling, never inside the track,
  which is cloned. A press pauses the animation (`aria-pressed`, and
  `nino-is-paused` on the row, which holds for touch and keyboard unlike
  `:hover` and `:focus-within`) and the next one takes it up again. It is
  drawn once the row runs, once however often the row is measured again,
  and removed when a single pass ends - the keyboard focus, where the button
  has it then, moves to the row (`tabindex="-1"`) instead of falling back to
  the top of the page; a looping row keeps it. No button for an
  empty label, for one that still holds a fill nobody resolved, or under
  `prefers-reduced-motion`. A cycle longer than five seconds still needs it to
  conform. The button wears the kernel's `nino-btn` classes; `ticker.css`
  adds its pressed state.

- **An install unit with one text fill, `[[/ticker/toggle]]`** - *Pause
  animation* / *Animation pausieren* - the label for the button. Activation,
  and the update, merge it add-only into `text/<locale>.php` for every
  available locale; a locale added later needs the key by hand. The fill goes
  into the page as it stands, so a label must not contain a double quote.

### Changed

- **"Wider than the box twice over" was never what the copies cover.**
  `ticker.js`'s file header, the docblock over `run()`, the comment beside
  the measurement ("Twice the box, not twice the row") and `Ticker.php`'s
  class docblock all said the row is copied until it is wider than the box
  twice over. `needed` has always been `row.clientWidth + one` - the box
  plus one length of the row, which is the distance the track travels plus
  the box it travels across, and which is what the README, the two tests and
  the rest of that same comment say. The four places say it too.

- **A check still said the two custom properties are written once.**
  `ticker-smoke.php`'s label read "what the script sets is the distance and
  the duration, once", and they are written again whenever the box has come
  to rest at a width its copies were not made for - which is the change
  `ticker.css`'s own header was corrected for. The label says what the check
  holds.

- **The "Asset bundling" note asked a project to do something it does not
  have to.** It said `/features/Ticker/assets/...` resolves against the
  project root, and that a project which moved its features elsewhere with
  `NINO_FEATURES_DIR` has to name the two sources under their real path in
  `/nino/html/assets` itself. `\Nino\Filesystem` resolves the virtual
  `/features` prefix against `\Nino\Features::dir()` - a branch of its own,
  older than the `^1.3` this manifest names - so the sources are found after
  a relocation like every other file a feature addresses that way.

### Fixed

- **A window that got wider left a stretch of nothing in the loop.** The
  copies are made to cover the box plus one length of the row, and they were
  made once, for the box as it was when the page loaded. A window widened
  afterwards was a box the row no longer reached across: in a box grown from
  600 to 1600 pixels, 936 of them were empty for the rest of every cycle. The
  rows are measured again once a resize has come to rest, and only where the
  box really is a different width than the one its copies were made for.

- **The loop jumped by one gap every time round** - the seam the copies are
  there to remove. The animation ran the width of the original row, which is
  the items plus the gaps *between* them; the copy that should stand where
  the original stood is one gap further along than that, because there is a
  gap between the last original and the first copy too. The distance is
  measured now, as where the first copy actually sits, so it is right for any
  gap a project sets.

- **A copy carried the original's ids and stayed in the tab order.** The
  copies are `aria-hidden`, which takes them out of what a screen reader
  reads but not out of what a keyboard walks through - so a row of five
  logos, copied four times, was twenty tab stops that all read the same, and
  every `id` in the row existed five times over. A copy loses its ids and its
  focusable parts are taken out of the tab order.

- **A row whose pictures have not loaded is left alone again.** The check for
  "nothing to show yet" looked at the track, which is as wide as its gaps
  even when everything in it is empty; it looks at the items themselves now.

## 1.0.0 — Unreleased

First release.

- `class="nino-ticker"` on a container with a `.nino-ticker-track` inside it — a
  row that runs and starts again without a seam, timed per element with
  `data-ticker-speed`, `-direction` and `-pause`.
- The row's own children are copied until they cover the box plus one length of
  the row, and the animation moves exactly one original width: the copy ends
  where the original stood, so starting again moves nothing. The copies are
  `aria-hidden` — the row is read once, which is how many times it is there.
- The movement is a CSS animation rather than a timer, so a browser runs it off
  the main thread and stops paying for it in a background tab.
- Stopped under the pointer and while something inside it has the keyboard
  focus. Standing still, and scrollable by hand, for a visitor who asked for less
  motion.
