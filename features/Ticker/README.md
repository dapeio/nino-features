# Ticker

A row that runs — one cycle, or round and round without a seam — a bar of
logos, a line of references, a strip of announcements.

```html
<div class="nino-ticker" data-ticker-speed="40">
	<div class="nino-ticker-track">
		<img src="…" alt="Kunde A">
		<img src="…" alt="Kunde B">
		<img src="…" alt="Kunde C">
	</div>
</div>
```

This feature renders nothing. What runs past is markup a project's own template
already carries, exactly like [Typewriter](../Typewriter/README.md) — the class
is the two lines that put `ticker.css` and `ticker.js` into the site's bundles.
Its install unit brings one text fill, the label of the pause button a row can
ask for.

## One cycle, once it has been seen

A row **runs exactly one cycle**: one original row width plus one gap, at
`data-ticker-speed` — three items of 200 px with gaps of 32 px are 696 px, which
at 40 px/s is 17.4 s. It stops on a frame identical to its first, because the
copy stands where the original stood, so nothing jumps; it then stands still,
with the items past the box edge cut off as in any frame before it.

It does not start before it has been **scrolled into view**: a row in a footer
would otherwise be through its pass before anybody had been there. Without
`IntersectionObserver` it starts at once.

`data-ticker-loop="1"` asks for the endless loop instead, which is what a ticker
was before. The attribute is read the way `data-typewriter-loop` is: missing or
empty is the default, which is one cycle; `0`, `false`, `off` and `no` say it
too; anything else asks for the loop. `ticker.js` writes the result as the class
`nino-is-looping`, so the stylesheet never repeats which values count.

**Existing pages.** Every `.nino-ticker` ran an endless loop from page load.
Afterwards it waits until it is scrolled into view and runs one cycle. The way
back is `data-ticker-loop="1"` on the row. `data-ticker-speed`, `-direction` and
`-pause`, the stop under the pointer and under keyboard focus, reduced motion
and the plain row without JavaScript are unchanged, and so are the markup and
what the server sends: the page cache is unaffected, and the new script reaches
browsers through the bundle's `?v=` hash.

## The seam is the whole problem

A row that simply scrolls runs out and jumps back, and the jump is what everybody
sees. So `ticker.js` copies the row's own children until they are wider than the
box plus one length of the row, and moves the whole of it by **exactly one
original width** — at which point the copy is standing where the original stood,
and starting again from zero moves nothing.

The copies are `aria-hidden`: to a screen reader the row is read **once**, which
is how many times it is there.

How many of them are needed follows from the box the row stands in, so the row
is measured and copied again when that box changes width — a window resized, a
phone turned — once the resizing has come to rest. Copies made for a narrower
box cover a wider one only in part, and what runs past for the rest of every
cycle is nothing at all.

The movement is a CSS animation, not a script moving something every frame. A
browser runs an animation off the main thread and stops paying for it in a
background tab; neither is true of a timer.

When a single cycle ends, `ticker.js` marks the row `nino-is-done` and the
stylesheet turns that into *no* animation, rather than a finished one — a later
resize sets the duration again, and a finished animation would start over. A
finished row is not measured or copied again either.

## Attributes

| Attribute | What it does |
| --- | --- |
| `data-ticker-speed="40"` | pixels per second. Default 40 — slow enough to read a word on the way past |
| `data-ticker-direction="right"` | runs the other way. Default is to the left |
| `data-ticker-pause="off"` | keeps running under the pointer. Default is to stop, so a logo can be looked at |
| `data-ticker-loop="1"` | runs round and round instead of one cycle. `0`, `false`, `off`, `no` and an empty value are the default; anything else is on |
| `data-ticker-toggle="[[/feature/ticker/pause/label]]"` | a pause button after the row; the value is its label. See *Pause, stop, hide* below |

A row also stops while something inside it has the keyboard focus: a row that
runs away under a tab stop is a row nobody can use.

## Pause, stop, hide (WCAG 2.2.2)

Success criterion 2.2.2 asks for a way to pause, stop or hide anything that moves
for more than five seconds, next to other content. A row that **loops** is that,
and **a single cycle longer than five seconds is too** — the 17.4 s of the
example above still needs the button to conform. The default of one cycle makes
the row stop on its own; it does not make it pausable.

`data-ticker-toggle="<label>"` puts a button after the row — the row's next
sibling, never inside it, because the track is cloned and a button in a clone is
a second one. A press pauses the animation (`aria-pressed="true"`, and
`nino-is-paused` on the row); the next press takes it up again. That holds for
touch and keyboard, unlike `:hover` and `:focus-within`. The button is drawn
once the row runs, once only however often the row is measured again, and
removed — not hidden, because `.nino-btn`'s own display rule beats the `[hidden]`
attribute — when a single cycle ends; a button that has the keyboard focus then
hands it to the row (`tabindex="-1"`). A looping row keeps it.

No button is drawn for an empty label or one that still holds a fill nobody
resolved (`[[`), none under `prefers-reduced-motion`, and none before the row
runs. The button wears the kernel's `nino-btn` classes; `ticker.css` adds its
pressed state.

## Without JavaScript, and with less motion

Before the script has measured anything, the track is a plain flex row — the
logos side by side, which is what the markup reads like. That is also what a
visitor who asked for less motion keeps: under `prefers-reduced-motion` the row
stands still and scrolls by hand. A row running across the screen is the most
literal reading of "less movement" there is.

## The words

The label of the pause button is a text fill, handed to the script as the value
of `data-ticker-toggle`.

| Fill | English | Deutsch |
| --- | --- | --- |
| `[[/feature/ticker/pause/label]]` | Pause animation | Animation pausieren |

The install unit merges it into the project's own `text/<locale>.php` at
activation, for every available locale and add-only, so a label a project
already wrote is kept; from then on it is the project's. A locale added later
needs the key by hand. The fill goes into the page as it stands, so a label must
not contain a double quote.

## Asset bundling

`ticker.css` and `ticker.js` are added to the **site's own** bundles —
`/.cache/style.css` and `/.cache/script.js` — the same two the base install's
`html-header.tpl`/`html-footer.tpl` already load on every page.

The sources are addressed as `/features/Ticker/assets/…`, which
`\Nino\Filesystem::path()` resolves against `\Nino\Features::dir()` — so they are
found wherever `NINO_FEATURES_DIR` put the features directory, and a project that
moved it has nothing to say in `/nino/html/assets` itself.

## Settings

None, for the reason Typewriter has none: every timing belongs to the row being
run, not to the site — a logo bar in a footer and a line of announcements in a
header are not one speed — and a static asset could not read a site-wide setting
anyway.

## Data

None.

## Tests

`tests/ticker-smoke.php` — the manifest, the activation and the label it merges
for both locales, add-only, the two files it puts into the site's bundles, what
those two files promise (one cycle by default, the loop only under
`nino-is-looping`, the waiting, paused and done rules) and deactivation. It runs
`tests/ticker-js-smoke.js` too where `node` is on the path.

`tests/ticker-js-smoke.js` — what `ticker.js` does over a DOM stand-in: how many
copies it makes for a given row and box, that it stops making them, that the
copies are hidden from a screen reader, and what it sets the animation's distance
and duration to; that a row runs one cycle unless it asks for the loop, waits to
be seen, is done when its animation ends and stays done; and that the pause
button is drawn once, after the row and never inside it, and goes with the cycle.
